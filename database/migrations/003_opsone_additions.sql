-- =====================================================
-- OpsOne — Additional Tables (Migration 003)
-- Notices, App Builds, Install Logs
-- MySQL / MariaDB Compatible Version
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─── Notices / Bulletins ───────────────────────────
CREATE TABLE IF NOT EXISTS `notices` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`      INT UNSIGNED NOT NULL,
    `title`          VARCHAR(255) NOT NULL,
    `body`           LONGTEXT NOT NULL,
    `priority`       ENUM('normal','urgent','critical') NOT NULL DEFAULT 'normal',
    `category`       VARCHAR(100) DEFAULT 'general',
    `published`      TINYINT(1) NOT NULL DEFAULT 0,
    `published_at`   TIMESTAMP NULL DEFAULT NULL,
    `expires_at`     TIMESTAMP NULL DEFAULT NULL,
    `created_by`     INT UNSIGNED DEFAULT NULL,
    `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_notices_tenant` (`tenant_id`),
    INDEX `idx_notices_published` (`published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Note: user table already contains web_access in the fixed 001 schema, 
-- but we add it if it might be missing from an old build.
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS web_access TINYINT(1) DEFAULT 1;

-- ─── App Builds ────────────────────────────────────
CREATE TABLE IF NOT EXISTS `app_builds` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `version`        VARCHAR(50) NOT NULL,
    `build_number`   VARCHAR(50) NOT NULL,
    `platform`       VARCHAR(20) NOT NULL DEFAULT 'ios',
    `release_notes`  TEXT DEFAULT NULL,
    `file_path`      VARCHAR(500) DEFAULT NULL,
    `file_size`      INT UNSIGNED DEFAULT 0,
    `min_os_version` VARCHAR(20) DEFAULT '16.0',
    `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
    `uploaded_by`    INT UNSIGNED DEFAULT NULL,
    `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_builds_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Install Access Logs ───────────────────────────
CREATE TABLE IF NOT EXISTS `install_logs` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`        INT UNSIGNED DEFAULT NULL,
    `tenant_id`      INT UNSIGNED DEFAULT NULL,
    `action`         ENUM('page_view','manifest_request','build_download','instructions_view') NOT NULL DEFAULT 'page_view',
    `build_id`       INT UNSIGNED DEFAULT NULL,
    `ip_address`     VARCHAR(45) DEFAULT NULL,
    `user_agent`     VARCHAR(500) DEFAULT NULL,
    `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`build_id`) REFERENCES `app_builds`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Additional file categories ─────────────────────
INSERT IGNORE INTO file_categories (tenant_id, name, slug)
SELECT id, 'Briefings', 'briefings' FROM tenants;

INSERT IGNORE INTO file_categories (tenant_id, name, slug)
SELECT id, 'Safety Information', 'safety_info' FROM tenants;

INSERT IGNORE INTO file_categories (tenant_id, name, slug)
SELECT id, 'Company Documents', 'company_docs' FROM tenants;

INSERT IGNORE INTO file_categories (tenant_id, name, slug)
SELECT id, 'Forms', 'forms' FROM tenants;

SET FOREIGN_KEY_CHECKS = 1;
