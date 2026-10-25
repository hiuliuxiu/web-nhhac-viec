<?php
// Cấu hình chung của ứng dụng. Sửa thông tin MySQL cho đúng với máy của bạn.

define('APP_NAME', 'Nhắc Việc');
define('APP_TIMEZONE', 'Asia/Ho_Chi_Minh');

// Kết nối MySQL (XAMPP mặc định: user root, mật khẩu để trống)
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'webnhacviec_v7');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Trình duyệt tự tải lại dữ liệu sau mỗi 15 giây
define('POLL_INTERVAL_MS', 15000);

// Hai lượt quét deadline phải cách nhau ít nhất 60 giây
define('SCAN_MIN_INTERVAL_SEC', 60);

// Discord: số tin tối đa mỗi lượt quét, thời gian chờ tối đa khi gửi
define('DISCORD_MAX_PER_RUN', 5);
define('DISCORD_TIMEOUT_SEC', 8);

// Chỉ cho phép gửi webhook tới các tên miền của Discord
define('DISCORD_ALLOWED_HOSTS', ['discord.com', 'discordapp.com', 'ptb.discord.com', 'canary.discord.com']);
