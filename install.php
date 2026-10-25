<?php
// Cài đặt CSDL. Dòng lệnh: php install.php (thêm --no-seed để không nạp dữ liệu mẫu).
// Mở bằng trình duyệt thì chuyển sang trang cài đặt.
if (PHP_SAPI !== 'cli') {
    header('Location: index.php?url=install');
    exit;
}

require __DIR__ . '/bootstrap.php';

$withSeed = !in_array('--no-seed', $argv, true);

$installModel = new InstallModel();
$result = $installModel->install($withSeed);

foreach ($result['steps'] as $step) {
    echo '[OK] ' . $step['label'] . PHP_EOL;
}

if ($result['fatal'] !== null) {
    echo $result['fatal'] . PHP_EOL;
    exit(1);
}

echo 'Cài đặt xong. Mở index.php để đăng nhập.' . PHP_EOL;
