-- =====================================================
-- CrewAssist Portal — Full Database Schema
-- MySQL 8.0+ Required
-- All tables are tenant-aware where applicable
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─── Tenants ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `tenants` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`           VARCHAR(255) NOT NULL,
    `code`           VARCHAR(10)  NOT NULL UNIQUE,
    `contact_email`  VARCHAR(255) DEFAULT NULL,
    `logo_path`      VARCHAR(500) DEFAULT NULL,
    `is_active`      TINYINT(1)   NOT NULL DEFAULT 1,
    `settings`       JSON         DEFAULT NULL,
    `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tenants_active` (`is_active`),
    INDEX `idx_tenants_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Roles ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `roles` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`   INT UNSIGNED DEFAULT NULL,
    `name`        VARCHAR(100) NOT NULL,
    `slug`        VARCHAR(50)  NOT NULL,
    `description` VARCHAR(500) DEFAULT NULL,
    `is_system`   TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uq_roles_slug_tenant` (`slug`, `tenant_id`),
    INDEX `idx_roles_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Departments ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS `departments` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`  INT UNSIGNED NOT NULL,
    `name`       VARCHAR(100) NOT NULL,
    `code`       VARCHAR(20)  DEFAULT NULL,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    INDEX `idx_departments_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Bases ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `bases` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`  INT UNSIGNED NOT NULL,
    `name`       VARCHAR(100) NOT NULL,
    `code`       VARCHAR(10)  NOT NULL,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    INDEX `idx_bases_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Users ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`     INT UNSIGNED NOT NULL,
    `name`          VARCHAR(255) NOT NULL,
    `email`         VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `employee_id`   VARCHAR(50)  DEFAULT NULL,
    `department_id` INT UNSIGNED DEFAULT NULL,
    `base_id`       INT UNSIGNED DEFAULT NULL,
    `status`        ENUM('pending','active','suspended','inactive') NOT NULL DEFAULT 'pending',
    `mobile_access` TINYINT(1) NOT NULL DEFAULT 1,
    `avatar_path`   VARCHAR(500) DEFAULT NULL,
    `last_login_at` TIMESTAMP    NULL,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`)     REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`base_id`)       REFERENCES `bases`(`id`) ON DELETE SET NULL,
    UNIQUE KEY `uq_users_email_tenant` (`email`, `tenant_id`),
    INDEX `idx_users_tenant` (`tenant_id`),
    INDEX `idx_users_status` (`status`),
    INDEX `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── User Roles ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS `user_roles` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT UNSIGNED NOT NULL,
    `role_id`    INT UNSIGNED NOT NULL,
    `tenant_id`  INT UNSIGNED NOT NULL,
    `assigned_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`role_id`)   REFERENCES `roles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uq_user_role` (`user_id`, `role_id`),
    INDEX `idx_user_roles_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Devices ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `devices` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`       INT UNSIGNED NOT NULL,
    `user_id`         INT UNSIGNED NOT NULL,
    `device_uuid`     VARCHAR(255) NOT NULL,
    `platform`        VARCHAR(50)  DEFAULT NULL,
    `model`           VARCHAR(100) DEFAULT NULL,
    `os_version`      VARCHAR(50)  DEFAULT NULL,
    `app_version`     VARCHAR(50)  DEFAULT NULL,
    `approval_status` ENUM('pending','approved','rejected','revoked') NOT NULL DEFAULT 'pending',
    `approved_by`     INT UNSIGNED DEFAULT NULL,
    `approved_at`     TIMESTAMP    NULL,
    `revoked_by`      INT UNSIGNED DEFAULT NULL,
    `revoked_at`      TIMESTAMP    NULL,
    `notes`           TEXT         DEFAULT NULL,
    `first_login_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_sync_at`    TIMESTAMP    NULL,
    `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`)   REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`)     REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`revoked_by`)  REFERENCES `users`(`id`) ON DELETE SET NULL,
    UNIQUE KEY `uq_device_user` (`device_uuid`, `user_id`),
    INDEX `idx_devices_tenant` (`tenant_id`),
    INDEX `idx_devices_status` (`approval_status`),
    INDEX `idx_devices_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Device Approval Logs ────────────────────────────
CREATE TABLE IF NOT EXISTS `device_approval_logs` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `device_id`  INT UNSIGNED NOT NULL,
    `tenant_id`  INT UNSIGNED NOT NULL,
    `action`     ENUM('registered','approved','rejected','revoked') NOT NULL,
    `performed_by` INT UNSIGNED DEFAULT NULL,
    `notes`      TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`device_id`)     REFERENCES `devices`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tenant_id`)     REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`performed_by`)  REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_device_logs_device` (`device_id`),
    INDEX `idx_device_logs_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── File Categories ─────────────────────────────────
CREATE TABLE IF NOT EXISTS `file_categories` (
    `id`        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `name`      VARCHAR(100) NOT NULL,
    `slug`      VARCHAR(50) NOT NULL,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uq_file_cat` (`slug`, `tenant_id`),
    INDEX `idx_file_categories_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Files ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `files` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`      INT UNSIGNED NOT NULL,
    `category_id`    INT UNSIGNED DEFAULT NULL,
    `title`          VARCHAR(500) NOT NULL,
    `description`    TEXT DEFAULT NULL,
    `file_path`      VARCHAR(1000) NOT NULL,
    `file_name`      VARCHAR(255) NOT NULL,
    `file_size`      BIGINT UNSIGNED DEFAULT 0,
    `mime_type`      VARCHAR(100) DEFAULT NULL,
    `version`        VARCHAR(50) DEFAULT '1.0',
    `status`         ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    `effective_date` DATE DEFAULT NULL,
    `requires_ack`   TINYINT(1) NOT NULL DEFAULT 0,
    `uploaded_by`    INT UNSIGNED DEFAULT NULL,
    `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`)    REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`)  REFERENCES `file_categories`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`uploaded_by`)  REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_files_tenant` (`tenant_id`),
    INDEX `idx_files_status` (`status`),
    INDEX `idx_files_category` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── File Role Visibility ────────────────────────────
CREATE TABLE IF NOT EXISTS `file_role_visibility` (
    `id`      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `file_id` INT UNSIGNED NOT NULL,
    `role_id` INT UNSIGNED NOT NULL,
    FOREIGN KEY (`file_id`) REFERENCES `files`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uq_file_role` (`file_id`, `role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── API Tokens ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS `api_tokens` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`      INT UNSIGNED NOT NULL,
    `tenant_id`    INT UNSIGNED NOT NULL,
    `token`        VARCHAR(128) NOT NULL UNIQUE,
    `device_id`    INT UNSIGNED DEFAULT NULL,
    `expires_at`   TIMESTAMP    NOT NULL,
    `revoked`      TINYINT(1)   NOT NULL DEFAULT 0,
    `last_used_at` TIMESTAMP    NULL,
    `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`device_id`) REFERENCES `devices`(`id`) ON DELETE SET NULL,
    INDEX `idx_api_tokens_token` (`token`),
    INDEX `idx_api_tokens_user` (`user_id`),
    INDEX `idx_api_tokens_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Login Activity ──────────────────────────────────
CREATE TABLE IF NOT EXISTS `login_activity` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT UNSIGNED DEFAULT NULL,
    `tenant_id`  INT UNSIGNED DEFAULT NULL,
    `email`      VARCHAR(255) NOT NULL,
    `ip_address` VARCHAR(45)  DEFAULT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `success`    TINYINT(1)   NOT NULL,
    `source`     ENUM('web','api') NOT NULL DEFAULT 'web',
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_login_user` (`user_id`),
    INDEX `idx_login_tenant` (`tenant_id`),
    INDEX `idx_login_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Audit Logs ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`  INT UNSIGNED DEFAULT NULL,
    `user_id`    INT UNSIGNED DEFAULT NULL,
    `user_name`  VARCHAR(255) DEFAULT NULL,
    `action`     VARCHAR(255) NOT NULL,
    `entity_type` VARCHAR(100) DEFAULT NULL,
    `entity_id`  INT UNSIGNED DEFAULT NULL,
    `details`    TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE SET NULL,
    INDEX `idx_audit_tenant` (`tenant_id`),
    INDEX `idx_audit_user` (`user_id`),
    INDEX `idx_audit_action` (`action`),
    INDEX `idx_audit_entity` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Sync Events ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS `sync_events` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`  INT UNSIGNED NOT NULL,
    `user_id`    INT UNSIGNED NOT NULL,
    `device_id`  INT UNSIGNED DEFAULT NULL,
    `event_type` VARCHAR(50) NOT NULL DEFAULT 'heartbeat',
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`device_id`) REFERENCES `devices`(`id`) ON DELETE SET NULL,
    INDEX `idx_sync_tenant` (`tenant_id`),
    INDEX `idx_sync_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
