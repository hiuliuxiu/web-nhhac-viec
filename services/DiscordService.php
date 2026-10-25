<?php
/**
 * Gửi thông báo lên kênh chat Discord qua Webhook.
 * Discord nhận một HTTP POST có thân là JSON. Tin nhắn dùng dạng "embed"
 * để có viền màu và các ô thông tin cho dễ đọc.
 */
class DiscordService
{
    /** Màu viền tin nhắn theo loại sự kiện. */
    const COLORS = [
        'overdue'   => 0xEF4444,   // đỏ
        'due_soon'  => 0xF59E0B,   // cam
        'completed' => 0x10B981,   // xanh lá
        'test'      => 0x6366F1,   // tím
    ];

    const TITLES = [
        'overdue'   => '🔴 CÔNG VIỆC ĐÃ QUÁ HẠN',
        'due_soon'  => '⏰ CÔNG VIỆC SẮP TỚI HẠN',
        'completed' => '✅ CÔNG VIỆC ĐÃ HOÀN THÀNH',
    ];

    /**
     * URL webhook phải là https và thuộc tên miền của Discord.
     * Chặn các địa chỉ khác để không ai lợi dụng server gửi yêu cầu tới máy tuỳ ý (lỗi SSRF).
     */
    public function isValidWebhook($url)
    {
        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parts = parse_url($url);
        if (($parts['scheme'] ?? '') !== 'https') {
            return false;
        }

        $host = strtolower($parts['host'] ?? '');
        if (!in_array($host, DISCORD_ALLOWED_HOSTS, true)) {
            return false;
        }

        return str_starts_with($parts['path'] ?? '', '/api/webhooks/');
    }

    /** Gửi nội dung $payload tới webhook bằng cURL. Trả về ['ok' => true/false, 'error' => câu lỗi]. */
    public function send($webhookUrl, array $payload)
    {
        if (!$this->isValidWebhook($webhookUrl)) {
            return ['ok' => false, 'error' => 'URL webhook không hợp lệ (phải bắt đầu bằng https://discord.com/api/webhooks/).'];
        }

        $ch = curl_init($webhookUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => DISCORD_TIMEOUT_SEC,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['ok' => false, 'error' => 'Không gọi được Discord: ' . ($curlError ?: 'lỗi mạng không xác định')];
        }

        // Discord trả về 204 No Content khi gửi thành công
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['ok' => true, 'error' => null];
        }

        if ($httpCode === 401 || $httpCode === 404) {
            $hint = 'Webhook không tồn tại hoặc đã bị xoá trên Discord.';
        } elseif ($httpCode === 429) {
            $hint = 'Bị Discord giới hạn tốc độ gửi (rate limit).';
        } else {
            $hint = 'Discord từ chối yêu cầu.';
        }

        $detail = is_string($response) ? mb_substr($response, 0, 300) : '';
        return ['ok' => false, 'error' => $hint . ' (HTTP ' . $httpCode . ') ' . $detail];
    }

    /** Dựng tin nhắn cho một công việc. $type là due_soon, overdue hoặc completed. */
    public function buildTaskMessage(array $task, $type)
    {
        $assignee = $task['assignee_name'] ?: 'Chưa phân công';
        $priority = Helper::PRIORITY_LABELS[$task['priority']] ?? $task['priority'];
        $status   = Helper::STATUS_LABELS[$task['status']] ?? $task['status'];

        $fields = [
            ['name' => 'Dự án',           'value' => $task['project_name'], 'inline' => true],
            ['name' => 'Người thực hiện', 'value' => $assignee,             'inline' => true],
            ['name' => 'Mức ưu tiên',     'value' => $priority,             'inline' => true],
        ];

        if ($task['due_at']) {
            $fields[] = [
                'name'   => $type === 'overdue' ? 'Hạn chót (đã qua)' : 'Hạn chót',
                'value'  => date('H:i \n\g\à\y d/m/Y', strtotime($task['due_at'])) . ' — ' . Helper::humanizeDue($task['due_at']),
                'inline' => false,
            ];
        }

        if ($type !== 'completed') {
            $fields[] = ['name' => 'Trạng thái hiện tại', 'value' => $status, 'inline' => true];
        }

        $description = mb_substr(trim((string) $task['description']), 0, 300);

        return [
            'username' => APP_NAME,
            'embeds'   => [[
                'title'       => self::TITLES[$type] ?? 'Thông báo công việc',
                'description' => '**' . $task['title'] . '**' . ($description !== '' ? "\n" . $description : ''),
                'color'       => self::COLORS[$type] ?? self::COLORS['test'],
                'fields'      => $fields,
                'footer'      => ['text' => APP_NAME . ' • Hệ thống nhắc việc tự động'],
                'timestamp'   => date('c'),
            ]],
        ];
    }

    /** Tin nhắn kiểm tra khi bấm nút "Gửi thử" ở trang cấu hình. */
    public function buildTestMessage($projectName, $senderName)
    {
        return [
            'username' => APP_NAME,
            'embeds'   => [[
                'title'       => '🔔 Kết nối Webhook thành công',
                'description' => 'Dự án **' . $projectName . "** đã kết nối được với kênh chat này.\n"
                               . 'Từ giờ các công việc sắp tới hạn hoặc quá hạn sẽ được thông báo tại đây.',
                'color'       => self::COLORS['test'],
                'fields'      => [
                    ['name' => 'Người gửi thử', 'value' => $senderName,          'inline' => true],
                    ['name' => 'Thời điểm',     'value' => date('H:i:s d/m/Y'), 'inline' => true],
                ],
                'footer'      => ['text' => APP_NAME . ' • Tin nhắn kiểm tra'],
                'timestamp'   => date('c'),
            ]],
        ];
    }

    /** Rút gọn tin nhắn thành một dòng để lưu vào nhật ký trên web. */
    public function summarize(array $payload)
    {
        $embed = $payload['embeds'][0] ?? [];
        $title = $embed['title'] ?? '';
        $text  = strip_tags(str_replace('**', '', (string) ($embed['description'] ?? '')));

        return trim($title . ' — ' . str_replace("\n", ' | ', $text));
    }
}
