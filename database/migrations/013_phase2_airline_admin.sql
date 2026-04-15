-- =====================================================
-- Migration 013 — Phase 2 Airline Admin Foundation
--
-- Adds:
--   • fleets table (aircraft type groupings per tenant)
--   • users.employment_status
--   • users.fleet_id FK → fleets
--   • users.profile_completion_pct
--
-- MySQL 8.0+ / Namecheap compatible.
-- Safe to run multiple times — guarded by INFORMATION_SCHEMA checks.
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─── 1. Create fleets table ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `fleets` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`    INT UNSIGNED NOT NULL,
    `name`         VARCHAR(100) NOT NULL,
    `code`         VARCHAR(20)  DEFAULT NULL,
    `aircraft_type` VARCHAR(100) DEFAULT NULL,
    `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    INDEX `idx_fleets_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 2. Add employment_status to users ───────────────────────────────────────
DROP PROCEDURE IF EXISTS _m013_add_employment_status;
DELIMITER //
CREATE PROCEDURE _m013_add_employment_status()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'employment_status'
    ) THEN
        ALTER TABLE `users`
            ADD COLUMN `employment_status`
                ENUM('full_time','part_time','contract','secondment','trainee') NULL DEFAULT NULL
            AFTER `status`;
    END IF;
END//
DELIMITER ;
CALL _m013_add_employment_status();
DROP PROCEDURE IF EXISTS _m013_add_employment_status;

-- ─── 3. Add fleet_id to users ────────────────────────────────────────────────
DROP PROCEDURE IF EXISTS _m013_add_fleet_id;
DELIMITER //
CREATE PROCEDURE _m013_add_fleet_id()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'fleet_id'
    ) THEN
        ALTER TABLE `users`
            ADD COLUMN `fleet_id` INT UNSIGNED NULL DEFAULT NULL
            AFTER `base_id`;
        ALTER TABLE `users`
            ADD CONSTRAINT `fk_users_fleet`
            FOREIGN KEY (`fleet_id`) REFERENCES `fleets`(`id`) ON DELETE SET NULL;
    END IF;
END//
DELIMITER ;
CALL _m013_add_fleet_id();
DROP PROCEDURE IF EXISTS _m013_add_fleet_id;

-- ─── 4. Add profile_completion_pct to users ──────────────────────────────────
DROP PROCEDURE IF EXISTS _m013_add_profile_pct;
DELIMITER //
CREATE PROCEDURE _m013_add_profile_pct()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'profile_completion_pct'
    ) THEN
        ALTER TABLE `users`
            ADD COLUMN `profile_completion_pct` TINYINT UNSIGNED NOT NULL DEFAULT 0
            AFTER `employment_status`;
    END IF;
END//
DELIMITER ;
CALL _m013_add_profile_pct();
DROP PROCEDURE IF EXISTS _m013_add_profile_pct;

SET FOREIGN_KEY_CHECKS = 1;
