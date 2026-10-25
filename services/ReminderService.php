<?php
/**
 * Nghiệp vụ nhắc việc: quét hạn chót, ghi nhật ký và gửi Discord.
 * Được gọi từ 3 nơi: ReminderController (trình duyệt gọi quét định kỳ),
 * TaskController (khi kéo thẻ sang "Hoàn thành") và worker.php (chạy nền).
 */
class ReminderService
{
    private $taskModel;
    private $reminderModel;
    private $discord;

    /** Số tin Discord còn được gửi trong lượt này (tránh gửi dồn dập, bị Discord chặn). */
    private $budget = 0;

    /** Thống kê của lượt đang chạy. */
    private $stats = [];

    public function __construct()
    {
        $this->taskModel     = new TaskModel();
        $this->reminderModel = new ReminderModel();
        $this->discord       = new DiscordService();
    }

    /**
     * Giành lượt quét. Trả về false nếu lượt trước mới chạy chưa đủ SCAN_MIN_INTERVAL_SEC giây.
     * $force = true khi người dùng bấm "Quét ngay": bỏ qua khoảng chờ.
     */
    public function tryBeginScan($force = false)
    {
        $now = date('Y-m-d H:i:s');

        if ($force) {
            $this->reminderModel->setLastScan($now);
            return true;
        }

        $threshold = date('Y-m-d H:i:s', time() - SCAN_MIN_INTERVAL_SEC);
        return $this->reminderModel->claimScan($now, $threshold);
    }

    /** Thời điểm quét gần nhất (dạng MySQL), hoặc null. */
    public function lastScan()
    {
        return $this->reminderModel->getLastScan();
    }

    /** Quét mọi dự án, nhắc các việc sắp tới hạn hoặc đã quá hạn. Trả về thống kê của lượt quét. */
    public function scan()
    {
        $this->resetStats();
        $this->stats['scanned']  = 0;
        $this->stats['due_soon'] = 0;
        $this->stats['overdue']  = 0;
        $this->stats['ran_at']   = date('Y-m-d H:i:s');

        foreach ($this->taskModel->findDueForReminder() as $task) {
            $type = strtotime($task['due_at']) < time() ? 'overdue' : 'due_soon';

            $this->stats['scanned']++;
            $this->stats[$type]++;

            $this->dispatch($task, $type);
        }

        return $this->stats;
    }

    /** Báo ngay khi một công việc vừa được chuyển sang "Hoàn thành". */
    public function notifyCompleted($taskId)
    {
        $this->resetStats();

        $task = $this->taskModel->findForReminder($taskId);
        if ($task) {
            $this->dispatch($task, 'completed');
        }

        return $this->stats;
    }

    /** Xoá nhật ký nhắc của một công việc để hệ thống được nhắc lại (khi đổi hạn chót, mở lại việc). */
    public function resetForTask($taskId, array $types)
    {
        $this->reminderModel->deleteForTask($taskId, $types);
    }

    // =====================================================================
    // CÁC HÀM PHỤ
    // =====================================================================

    private function resetStats()
    {
        $this->budget = DISCORD_MAX_PER_RUN;
        $this->stats  = ['logged' => 0, 'discord_sent' => 0, 'discord_failed' => 0, 'discord_mocked' => 0];
    }

    /** Ghi nhật ký và gửi Discord cho một sự kiện của một công việc. */
    private function dispatch(array $task, $type)
    {
        $message = $this->messageText($task, $type);

        // Bước 1: ghi nhật ký hiện trên web (kênh system), mỗi loại chỉ ghi một lần
        if (!$this->reminderModel->exists($task['id'], $type, 'system')) {
            if ($this->reminderModel->add($task['id'], $task['project_id'], $type, 'system', 'sent', $message)) {
                $this->stats['logged']++;
            }
        }

        // Bước 2: gửi lên Discord. Kiểm tra trước: đã gửi rồi thì thôi
        if (!$this->shouldSendDiscord($task, $type)) {
            return;
        }
        if ($this->budget <= 0) {
            return;   // hết hạn ngạch của lượt này, để lượt sau gửi
        }
        if ($this->reminderModel->exists($task['id'], $type, 'discord')) {
            return;
        }

        $this->budget--;
        $payload = $this->discord->buildTaskMessage($task, $type);
        $summary = $this->discord->summarize($payload);

        if ((int) $task['mock_mode'] === 1) {
            // Chế độ giả lập: chỉ ghi nhật ký, không gọi mạng thật
            $this->reminderModel->add($task['id'], $task['project_id'], $type, 'discord', 'mocked', $summary);
            $this->stats['discord_mocked']++;
            return;
        }

        $result = $this->discord->send($task['discord_webhook_url'], $payload);

        if ($result['ok']) {
            $this->reminderModel->add($task['id'], $task['project_id'], $type, 'discord', 'sent', $summary);
            $this->stats['discord_sent']++;
        } else {
            $this->reminderModel->add($task['id'], $task['project_id'], $type, 'discord', 'failed', $message, $result['error']);
            $this->stats['discord_failed']++;
        }
    }

    /** Dự án có bật gửi Discord cho loại sự kiện này không. */
    private function shouldSendDiscord(array $task, $type)
    {
        if ((int) $task['discord_enabled'] !== 1) {
            return false;
        }

        switch ($type) {
            case 'due_soon':
                $enabled = (int) $task['notify_due_soon'] === 1;
                break;
            case 'overdue':
                $enabled = (int) $task['notify_overdue'] === 1;
                break;
            case 'completed':
                $enabled = (int) $task['notify_completed'] === 1;
                break;
            default:
                $enabled = false;
        }

        if (!$enabled) {
            return false;
        }

        // Gửi thật thì URL phải hợp lệ; chế độ giả lập thì không cần
        if ((int) $task['mock_mode'] !== 1 && !$this->discord->isValidWebhook($task['discord_webhook_url'])) {
            return false;
        }

        return true;
    }

    /** Câu chữ hiện trong nhật ký nhắc việc trên web. */
    private function messageText(array $task, $type)
    {
        $who  = $task['assignee_name'] ?: 'chưa phân công';
        $when = Helper::humanizeDue($task['due_at']);

        switch ($type) {
            case 'overdue':
                return 'Đã quá hạn: «' . $task['title'] . '» (' . $who . ') — ' . $when;
            case 'due_soon':
                return 'Sắp tới hạn: «' . $task['title'] . '» (' . $who . ') — ' . $when;
            case 'completed':
                return 'Đã hoàn thành: «' . $task['title'] . '» (' . $who . ')';
            default:
                return $task['title'];
        }
    }
}
