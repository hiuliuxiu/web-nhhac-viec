<?php
// Mọi yêu cầu từ trình duyệt đều đi qua tệp này, ví dụ: index.php?url=task/create
require __DIR__ . '/bootstrap.php';

// Bật session để nhớ người đã đăng nhập
session_name('webnhacviec_v7_session');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'path' => '/']);
session_start();

$router = new Router();
$router->run();
