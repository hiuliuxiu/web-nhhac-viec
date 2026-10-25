<?php
// Nạp cấu hình và tất cả các lớp dùng chung.
// index.php (trang web), install.php và worker.php (dòng lệnh) đều bắt đầu bằng tệp này.
define('ROOT_PATH', __DIR__);

require_once ROOT_PATH . '/config/config.php';
date_default_timezone_set(APP_TIMEZONE);
mb_internal_encoding('UTF-8');

// Lớp nền
require_once ROOT_PATH . '/core/AppException.php';
require_once ROOT_PATH . '/core/Database.php';
require_once ROOT_PATH . '/core/Helper.php';
require_once ROOT_PATH . '/core/functions.php';
require_once ROOT_PATH . '/core/Model.php';
require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/core/Router.php';

// Model: làm việc với CSDL
require_once ROOT_PATH . '/models/UserModel.php';
require_once ROOT_PATH . '/models/ProjectModel.php';
require_once ROOT_PATH . '/models/MemberModel.php';
require_once ROOT_PATH . '/models/TaskModel.php';
require_once ROOT_PATH . '/models/ReminderModel.php';
require_once ROOT_PATH . '/models/DashboardModel.php';
require_once ROOT_PATH . '/models/InstallModel.php';

// Service: nghiệp vụ nhắc việc dùng chung
require_once ROOT_PATH . '/services/DiscordService.php';
require_once ROOT_PATH . '/services/ReminderService.php';

// Controller được Router nạp khi cần, theo đường dẫn người dùng mở.
