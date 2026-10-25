-- ============================================================================
--  WEB NHẮC VIỆC - Hệ thống quản lý & theo dõi tiến độ công việc
--  Tệp: schema.sql  |  CSDL: MySQL / MariaDB (XAMPP)
--  Mô tả: Tạo cấu trúc CSDL. Chạy tệp này TRƯỚC seed.sql
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `webnhacviec_v7`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `webnhacviec_v7`;

-- Xoá theo thứ tự ngược khoá ngoại để chạy lại được nhiều lần
DROP TABLE IF EXISTS `reminders`;
DROP TABLE IF EXISTS `tasks`;
DROP TABLE IF EXISTS `project_members`;
DROP TABLE IF EXISTS `projects`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `app_state`;

-- ---------------------------------------------------------------------------
-- 1. NGƯỜI DÙNG  (F11 - Đăng nhập & Xác thực)
-- ---------------------------------------------------------------------------
CREATE TABLE `users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(50)  NOT NULL,
  `email`         VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,           -- bcrypt (password_hash của PHP)
  `full_name`     VARCHAR(120) NOT NULL,
  `avatar_color`  CHAR(7)      NOT NULL DEFAULT '#6366f1',
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email`    (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 2. DỰ ÁN  (F10 - Cấu hình dự án & Webhook)
--    Mỗi dự án tự giữ cấu hình nhắc việc riêng.
-- ---------------------------------------------------------------------------
CREATE TABLE `projects` (
  `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`                  VARCHAR(150) NOT NULL,
  `description`           TEXT         NULL,
  `color`                 CHAR(7)      NOT NULL DEFAULT '#6366f1',
  `owner_id`              INT UNSIGNED NOT NULL,

  -- --- Cấu hình nhắc việc (F08/F10) ---
  `discord_webhook_url`   VARCHAR(500) NULL,
  `discord_enabled`       TINYINT(1)   NOT NULL DEFAULT 0,  -- checkbox "Gửi nhắc nhở lên Discord"
  `mock_mode`             TINYINT(1)   NOT NULL DEFAULT 0,  -- chỉ ghi log, không gọi mạng thật
  `notify_due_soon`       TINYINT(1)   NOT NULL DEFAULT 1,  -- nhắc khi sắp tới hạn
  `notify_overdue`        TINYINT(1)   NOT NULL DEFAULT 1,  -- nhắc khi đã quá hạn
  `notify_completed`      TINYINT(1)   NOT NULL DEFAULT 0,  -- báo khi task vừa hoàn thành
  `remind_before_minutes` INT          NOT NULL DEFAULT 120, -- ngưỡng "sắp tới hạn" (mặc định 2 giờ)

  `created_at`            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_projects_owner` (`owner_id`),
  CONSTRAINT `fk_projects_owner` FOREIGN KEY (`owner_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 3. THÀNH VIÊN DỰ ÁN  (F06 - Phân công / F11 - phân tách dữ liệu)
--    Bảng này quyết định "ai thấy được dự án nào".
-- ---------------------------------------------------------------------------
CREATE TABLE `project_members` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED NOT NULL,
  `user_id`    INT UNSIGNED NOT NULL,
  `role`       ENUM('owner','member') NOT NULL DEFAULT 'member',
  `joined_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_member` (`project_id`, `user_id`),
  KEY `idx_member_user` (`user_id`),
  CONSTRAINT `fk_member_project` FOREIGN KEY (`project_id`)
    REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_member_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 4. CÔNG VIỆC  (F04, F05)
-- ---------------------------------------------------------------------------
CREATE TABLE `tasks` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id`   INT UNSIGNED NOT NULL,
  `title`        VARCHAR(255) NOT NULL,
  `description`  TEXT         NULL,
  `assignee_id`  INT UNSIGNED NULL,                 -- NULL = chưa phân công
  `created_by`   INT UNSIGNED NULL,
  `priority`     ENUM('low','medium','high','urgent')       NOT NULL DEFAULT 'medium',
  `status`       ENUM('todo','in_progress','review','done') NOT NULL DEFAULT 'todo',
  `due_at`       DATETIME     NULL,                 -- hạn chót có giờ/phút
  `position`     INT          NOT NULL DEFAULT 0,   -- thứ tự thẻ trong cột Kanban
  `completed_at` DATETIME     NULL,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tasks_project_status` (`project_id`, `status`),
  KEY `idx_tasks_due`            (`due_at`),
  KEY `idx_tasks_assignee`       (`assignee_id`),
  CONSTRAINT `fk_tasks_project` FOREIGN KEY (`project_id`)
    REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tasks_assignee` FOREIGN KEY (`assignee_id`)
    REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tasks_creator` FOREIGN KEY (`created_by`)
    REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 5. NHẬT KÝ NHẮC VIỆC  (F07, F08)
--    Vừa là log hiển thị trên web, vừa là cơ chế CHỐNG GỬI TRÙNG:
--    khoá UNIQUE (task_id, type, channel) đảm bảo mỗi task chỉ bị nhắc
--    đúng 1 lần cho mỗi loại sự kiện trên mỗi kênh.
-- ---------------------------------------------------------------------------
CREATE TABLE `reminders` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `task_id`    INT UNSIGNED NOT NULL,
  `project_id` INT UNSIGNED NOT NULL,
  `type`       ENUM('due_soon','overdue','completed') NOT NULL,
  `channel`    ENUM('discord','system')               NOT NULL DEFAULT 'system',
  `status`     ENUM('sent','failed','mocked','skipped') NOT NULL DEFAULT 'sent',
  `message`    TEXT         NULL,
  `error`      TEXT         NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reminder_once` (`task_id`, `type`, `channel`),
  KEY `idx_reminders_project` (`project_id`, `created_at`),
  CONSTRAINT `fk_reminders_task` FOREIGN KEY (`task_id`)
    REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reminders_project` FOREIGN KEY (`project_id`)
    REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 6. TRẠNG THÁI HỆ THỐNG
--    Giữ mốc thời gian lần quét deadline gần nhất.
--    Hàng chục tab trình duyệt cùng polling 15 giây, nhưng câu lệnh
--        UPDATE app_state SET v = NOW() WHERE k='last_cron_run' AND v <= <mốc>
--    là một phép so-sánh-và-gán nguyên tử: chỉ đúng MỘT lời gọi thắng trong
--    mỗi chu kỳ, các lời gọi còn lại nhận rowCount = 0 và bỏ qua.
--    Đây vừa là bộ chặn tần suất (throttle) vừa là khoá chống chạy chồng.
-- ---------------------------------------------------------------------------
CREATE TABLE `app_state` (
  `k`          VARCHAR(64)  NOT NULL,
  `v`          VARCHAR(255) NOT NULL,
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `app_state` (`k`, `v`) VALUES
  ('last_cron_run', '1970-01-01 00:00:00');
