<?php
// Quét deadline chạy nền, không cần mở trình duyệt.
// Chạy: php worker.php    Dừng: Ctrl+C    Chỉ quét một lần: php worker.php --once
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Chỉ chạy từ dòng lệnh.');
}

require __DIR__ . '/bootstrap.php';

$once = in_array('--once', $argv, true);
$reminderService = new ReminderService();

do {
    try {
        if ($reminderService->tryBeginScan()) {
            $stats = $reminderService->scan();
            echo date('H:i:s') . ' - Quét ' . $stats['scanned'] . ' việc, ghi ' . $stats['logged'] . ' thông báo.' . PHP_EOL;
        } else {
            echo date('H:i:s') . ' - Chưa đến chu kỳ quét tiếp theo.' . PHP_EOL;
        }
    } catch (Exception $e) {
        echo 'Lỗi: ' . $e->getMessage() . PHP_EOL;
        if ($once) {
            exit(1);
        }
    }

    if (!$once) {
        sleep(15);
    }
} while (!$once);
