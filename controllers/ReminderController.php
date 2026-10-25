<?php
/** Nhắc việc: xem nhật ký và chạy lượt quét hạn chót. */
class ReminderController extends Controller
{
    const TYPE_LABELS = [
        'due_soon'  => 'Sắp tới hạn',
        'overdue'   => 'Quá hạn',
        'completed' => 'Hoàn thành',
    ];

    const STATUS_LABELS = [
        'sent'    => 'Đã gửi',
        'failed'  => 'Thất bại',
        'mocked'  => 'Giả lập',
        'skipped' => 'Bỏ qua',
    ];

    /** GET ?url=reminder/list — nhật ký nhắc việc của một dự án (hoặc mọi dự án), mới nhất trước. */
    public function list()
    {
        $user      = $this->requireLogin();
        $projectId = $this->inputInt('project_id');

        if ($projectId > 0) {
            $this->requireProjectAccess($projectId, $user);
            $projectIds = [$projectId];
        } else {
            $projectModel = new ProjectModel();
            $projectIds   = $projectModel->getProjectIdsOfUser($user['id']);
        }

        $reminderModel = new ReminderModel();
        $lastRun = Helper::toIso($reminderModel->getLastScan());

        if ($projectIds === []) {
            $this->json(['notifications' => [], 'unsent_errors' => 0, 'last_cron_run' => $lastRun]);
            return;
        }

        // Lọc theo kênh: system (trên web) hoặc discord; để trống là cả hai
        $channel = $this->input('channel');
        if (!in_array($channel, ['system', 'discord'], true)) {
            $channel = '';
        }

        $limit = min(200, max(10, $this->inputInt('limit', 60)));

        $notifications = [];
        foreach ($reminderModel->listForProjects($projectIds, $channel, $limit) as $row) {
            $row['id']           = (int) $row['id'];
            $row['task_id']      = (int) $row['task_id'];
            $row['project_id']   = (int) $row['project_id'];
            $row['type_label']   = self::TYPE_LABELS[$row['type']] ?? $row['type'];
            $row['status_label'] = self::STATUS_LABELS[$row['status']] ?? $row['status'];
            $row['due_at']       = Helper::toIso($row['due_at']);
            $row['created_at']   = Helper::toIso($row['created_at']);
            $notifications[] = $row;
        }

        $this->json([
            'notifications' => $notifications,
            'unsent_errors' => $reminderModel->countFailed($projectIds),
            'last_cron_run' => $lastRun,
        ]);
    }

    /**
     * ?url=reminder/scan — chạy một lượt quét hạn chót.
     * GET: trình duyệt gọi 15 giây/lần, nhưng chỉ quét thật khi đã cách lượt trước ít nhất 60 giây.
     * POST kèm force=1: nút "Quét ngay", bỏ qua khoảng chờ.
     */
    public function scan()
    {
        $this->requireLogin();

        $force = $this->inputBool('force');
        if ($force) {
            $this->requirePost();
            $this->verifyCsrf();
        }

        $reminderService = new ReminderService();

        if (!$reminderService->tryBeginScan($force)) {
            $this->json([
                'ran'      => false,
                'reason'   => 'Chưa tới chu kỳ quét mới.',
                'last_run' => Helper::toIso($reminderService->lastScan()),
            ]);
            return;
        }

        $stats = $reminderService->scan();

        $this->json([
            'ran'      => true,
            'stats'    => $stats,
            'last_run' => Helper::toIso($reminderService->lastScan()),
        ]);
    }
}
