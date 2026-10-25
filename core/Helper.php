<?php
/**
 * Các hàm tiện ích dùng chung: xử lý ngày giờ, tính hạn chót,
 * và định dạng dữ liệu trước khi gửi cho JavaScript.
 */
class Helper
{
    const STATUSES   = ['todo', 'in_progress', 'review', 'done'];
    const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    const STATUS_LABELS = [
        'todo'        => 'Cần làm',
        'in_progress' => 'Đang làm',
        'review'      => 'Chờ duyệt',
        'done'        => 'Hoàn thành',
    ];

    const PRIORITY_LABELS = [
        'low'    => 'Thấp',
        'medium' => 'Trung bình',
        'high'   => 'Cao',
        'urgent' => 'Khẩn cấp',
    ];

    /** Đổi DATETIME của MySQL sang chuẩn ISO-8601 để JavaScript đọc đúng múi giờ. */
    public static function toIso($mysqlDatetime)
    {
        if (!$mysqlDatetime) {
            return null;
        }

        try {
            $date = new DateTime($mysqlDatetime, new DateTimeZone(APP_TIMEZONE));
            return $date->format('c');
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Đổi ngày giờ người dùng nhập (ô datetime-local gửi lên dạng 2025-10-24T14:30)
     * sang định dạng DATETIME của MySQL. Trả về null nếu để trống hoặc sai định dạng.
     */
    public static function parseDateTime($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $value = str_replace('T', ' ', $value);

        foreach (['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d'] as $format) {
            $date   = DateTime::createFromFormat('!' . $format, $value, new DateTimeZone(APP_TIMEZONE));
            $errors = DateTime::getLastErrors();
            $valid  = $errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0);

            if ($date !== false && $valid) {
                // Chỉ nhập ngày thì coi hạn chót là cuối ngày hôm đó
                if ($format === 'Y-m-d') {
                    $date->setTime(23, 59, 0);
                }
                return $date->format('Y-m-d H:i:s');
            }
        }

        return null;
    }

    /** Mô tả thời gian còn lại hoặc đã trễ, ví dụ "còn 1 giờ 20 phút", "trễ 2 ngày". */
    public static function humanizeDue($dueAt)
    {
        if (!$dueAt) {
            return null;
        }

        $diff = strtotime($dueAt) - time();
        $late = $diff < 0;
        $diff = abs($diff);

        $days    = intdiv($diff, 86400);
        $hours   = intdiv($diff % 86400, 3600);
        $minutes = intdiv($diff % 3600, 60);

        if ($days > 0) {
            $text = $days . ' ngày' . ($hours > 0 ? ' ' . $hours . ' giờ' : '');
        } elseif ($hours > 0) {
            $text = $hours . ' giờ' . ($minutes > 0 ? ' ' . $minutes . ' phút' : '');
        } else {
            $text = max(1, $minutes) . ' phút';
        }

        return $late ? 'trễ ' . $text : 'còn ' . $text;
    }

    /**
     * Mức độ khẩn cấp của một công việc:
     *   overdue  - đã quá hạn mà chưa xong
     *   due_soon - sắp tới hạn (trong ngưỡng nhắc của dự án, mặc định 120 phút)
     *   upcoming - còn hạn
     *   none     - không đặt hạn hoặc đã hoàn thành
     */
    public static function dueState($dueAt, $status, $remindBeforeMinutes = 120)
    {
        if ($status === 'done' || !$dueAt) {
            return 'none';
        }

        $secondsLeft = strtotime($dueAt) - time();

        if ($secondsLeft < 0) {
            return 'overdue';
        }
        if ($secondsLeft <= $remindBeforeMinutes * 60) {
            return 'due_soon';
        }
        return 'upcoming';
    }

    /** Màu hợp lệ phải có dạng #rrggbb, sai thì dùng màu mặc định. */
    public static function safeColor($value, $fallback = '#6366f1')
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : $fallback;
    }

    /** Chuẩn bị một dòng công việc trước khi gửi cho JavaScript. */
    public static function formatTask(array $row)
    {
        foreach (['id', 'project_id', 'position', 'assignee_id', 'created_by'] as $key) {
            if (isset($row[$key])) {
                $row[$key] = (int) $row[$key];
            }
        }

        $remindBefore = isset($row['remind_before_minutes']) ? (int) $row['remind_before_minutes'] : 120;
        $row['remind_before_minutes'] = $remindBefore;

        // Tính mức khẩn cấp khi due_at còn ở dạng MySQL, trước khi đổi sang ISO
        $row['due_state'] = self::dueState($row['due_at'], $row['status'], $remindBefore);
        $row['due_human'] = self::humanizeDue($row['due_at']);

        foreach (['due_at', 'completed_at', 'created_at', 'updated_at'] as $key) {
            if (array_key_exists($key, $row)) {
                $row[$key] = self::toIso($row[$key]);
            }
        }

        $row['status_label']   = self::STATUS_LABELS[$row['status']] ?? $row['status'];
        $row['priority_label'] = self::PRIORITY_LABELS[$row['priority']] ?? $row['priority'];

        return $row;
    }

    /** Chuẩn bị một dòng dự án (kèm số liệu thống kê) trước khi gửi cho JavaScript. */
    public static function formatProject(array $row)
    {
        $intKeys = [
            'id', 'owner_id', 'total_tasks', 'todo_tasks', 'in_progress_tasks', 'review_tasks',
            'done_tasks', 'overdue_tasks', 'due_soon_tasks', 'member_count', 'remind_before_minutes',
        ];
        foreach ($intKeys as $key) {
            if (isset($row[$key])) {
                $row[$key] = (int) $row[$key];
            }
        }

        $boolKeys = ['discord_enabled', 'mock_mode', 'notify_due_soon', 'notify_overdue', 'notify_completed'];
        foreach ($boolKeys as $key) {
            if (isset($row[$key])) {
                $row[$key] = (bool) (int) $row[$key];
            }
        }

        // Phần trăm hoàn thành, dùng cho thanh tiến độ và biểu đồ
        $row['progress'] = $row['total_tasks'] > 0
            ? (int) round($row['done_tasks'] / $row['total_tasks'] * 100)
            : 0;

        // Chỉ chủ dự án mới thấy URL webhook đầy đủ, thành viên chỉ thấy bản đã che
        $isOwner = isset($row['my_role']) && $row['my_role'] === 'owner';
        $url     = $row['discord_webhook_url'] ?? null;

        $row['has_webhook']         = !empty($url);
        $row['webhook_masked']      = self::maskWebhook($url);
        $row['discord_webhook_url'] = $isOwner ? $url : null;
        $row['created_at']          = self::toIso($row['created_at']);

        return $row;
    }

    /** Che phần bí mật của URL webhook, chỉ giữ vài ký tự đầu và cuối để nhận ra. */
    private static function maskWebhook($url)
    {
        if (!$url) {
            return null;
        }

        $parts = explode('/', rtrim($url, '/'));
        $token = array_pop($parts) ?: '';
        $id    = array_pop($parts) ?: '';

        if (mb_strlen($token) > 8) {
            $shortToken = mb_substr($token, 0, 4) . str_repeat('•', 12) . mb_substr($token, -4);
        } else {
            $shortToken = str_repeat('•', 12);
        }

        return '.../webhooks/' . $id . '/' . $shortToken;
    }
}
