-- Phase 5: Rostering Foundation
-- MySQL version — run via phpMyAdmin or CLI on production
-- Run after 015_phase4_notices_files.sql

-- ─── Roster Periods ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `roster_periods` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`   INT UNSIGNED NOT NULL,
    `name`        VARCHAR(120) NOT NULL,
    `start_date`  DATE NOT NULL,
    `end_date`    DATE NOT NULL,
    `status`      ENUM('draft','published','frozen','archived') NOT NULL DEFAULT 'draft',
    `notes`       TEXT DEFAULT NULL,
    `created_by`  INT UNSIGNED DEFAULT NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_roster_periods_tenant` (`tenant_id`, `start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Extend rosters table ─────────────────────────────────────────────────────
ALTER TABLE `rosters`
    ADD COLUMN IF NOT EXISTS `roster_period_id` INT UNSIGNED DEFAULT NULL AFTER `notes`,
    ADD COLUMN IF NOT EXISTS `base_id`          INT UNSIGNED DEFAULT NULL AFTER `roster_period_id`,
    ADD COLUMN IF NOT EXISTS `fleet_id`         INT UNSIGNED DEFAULT NULL AFTER `base_id`,
    ADD COLUMN IF NOT EXISTS `reserve_type`     VARCHAR(20)  DEFAULT NULL AFTER `fleet_id`;

-- ─── Roster Change Requests / Comments ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS `roster_changes` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`        INT UNSIGNED NOT NULL,
    `roster_period_id` INT UNSIGNED DEFAULT NULL,
    `roster_id`        INT UNSIGNED DEFAULT NULL,
    `user_id`          INT UNSIGNED NOT NULL,
    `requested_by`     INT UNSIGNED NOT NULL,
    `change_type`      ENUM('comment','leave_request','swap_request','correction') NOT NULL,
    `status`           ENUM('pending','approved','rejected','noted') NOT NULL DEFAULT 'pending',
    `message`          TEXT NOT NULL,
    `response`         TEXT DEFAULT NULL,
    `responded_by`     INT UNSIGNED DEFAULT NULL,
    `responded_at`     DATETIME DEFAULT NULL,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_roster_changes_period` (`roster_period_id`),
    INDEX `idx_roster_changes_entry`  (`roster_id`),
    INDEX `idx_roster_changes_user`   (`user_id`, `tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
