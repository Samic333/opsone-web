-- =====================================================
-- Migration 005 — web_access column + new platform roles
-- MySQL 8.0+ Compatible (stored procedure for IF NOT EXISTS)
-- Safe to re-run: INSERT IGNORE + INFORMATION_SCHEMA guard
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─── Add web_access to users if missing ─────────────
-- This column guards web portal login in AuthController
DROP PROCEDURE IF EXISTS _migration_005_web_access;
DELIMITER //
CREATE PROCEDURE _migration_005_web_access()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'users'
          AND COLUMN_NAME  = 'web_access'
    ) THEN
        ALTER TABLE `users`
            ADD COLUMN `web_access` TINYINT(1) NOT NULL DEFAULT 1 AFTER `mobile_access`;
        -- Enable web access for all currently active users
        UPDATE `users` SET `web_access` = 1 WHERE `status` = 'active';
    END IF;
END//
DELIMITER ;
CALL _migration_005_web_access();
DROP PROCEDURE IF EXISTS _migration_005_web_access;

-- ─── New system roles ────────────────────────────────
-- INSERT IGNORE = safe to re-run
INSERT IGNORE INTO `roles` (tenant_id, name, slug, description, is_system) VALUES
(NULL, 'Platform Support Admin',   'platform_support',    'Read-only platform support access', 1),
(NULL, 'Platform Security Admin',  'platform_security',   'Security monitoring and audit access', 1),
(NULL, 'Head of Cabin Crew',       'head_cabin_crew',     'Head of cabin crew department', 1),
(NULL, 'Engineering Manager',      'engineering_manager', 'Engineering/maintenance management', 1),
(NULL, 'Training Admin',           'training_admin',      'Training programme administration', 1),
(NULL, 'System Monitoring Admin',  'system_monitoring',   'System health and sync monitoring', 1);

-- Also rename Safety Officer → Safety Manager in display name
UPDATE `roles` SET `name` = 'Safety Manager' WHERE `slug` = 'safety_officer' AND `is_system` = 1;

SET FOREIGN_KEY_CHECKS = 1;
