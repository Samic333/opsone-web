-- =====================================================
-- Migration 004 — Notice Reads & File Acknowledgements
-- Phase 2: Persists read/ack state for notices and files
-- MySQL / MariaDB Compatible
-- Run: manually via phpMyAdmin or migration runner script
-- Safe to run multiple times (CREATE TABLE IF NOT EXISTS)
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─── Notice Reads ─────────────────────────────────
-- Tracks which users have read which notices.
-- Also supports acknowledgement where requires_ack = true on the notice.
-- NOTE: requires_ack column is added to notices table below.

CREATE TABLE IF NOT EXISTS `notice_reads` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `notice_id`       INT UNSIGNED NOT NULL,
    `user_id`         INT UNSIGNED NOT NULL,
    `tenant_id`       INT UNSIGNED NOT NULL,
    `read_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `acknowledged_at` TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (`notice_id`)  REFERENCES `notices`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tenant_id`)  REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uq_notice_read` (`notice_id`, `user_id`),
    INDEX `idx_nr_notice`  (`notice_id`),
    INDEX `idx_nr_user`    (`user_id`),
    INDEX `idx_nr_tenant`  (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add requires_ack to notices if not present (safe ALTER)
ALTER TABLE `notices`
    ADD COLUMN IF NOT EXISTS `requires_ack` TINYINT(1) NOT NULL DEFAULT 0 AFTER `published`,
    ADD COLUMN IF NOT EXISTS `target_roles` JSON DEFAULT NULL AFTER `requires_ack`,
    ADD COLUMN IF NOT EXISTS `target_departments` JSON DEFAULT NULL AFTER `target_roles`,
    ADD COLUMN IF NOT EXISTS `target_bases` JSON DEFAULT NULL AFTER `target_departments`;

-- ─── File Acknowledgements ────────────────────────
-- Tracks which users have acknowledged which files/manuals.
-- files.requires_ack = 1 triggers acknowledgement requirement.

CREATE TABLE IF NOT EXISTS `file_acknowledgements` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `file_id`         INT UNSIGNED NOT NULL,
    `user_id`         INT UNSIGNED NOT NULL,
    `tenant_id`       INT UNSIGNED NOT NULL,
    `version`         VARCHAR(50)  DEFAULT NULL,     -- version of the file when acked
    `device_id`       INT UNSIGNED DEFAULT NULL,     -- which device the ack came from
    `acknowledged_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`file_id`)    REFERENCES `files`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tenant_id`)  REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`device_id`)  REFERENCES `devices`(`id`) ON DELETE SET NULL,
    UNIQUE KEY `uq_file_ack` (`file_id`, `user_id`),
    INDEX `idx_fa_file`    (`file_id`),
    INDEX `idx_fa_user`    (`user_id`),
    INDEX `idx_fa_tenant`  (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Audit Log Entries for Acks ───────────────────
-- Ack actions are also written to audit_logs by the API controller.
-- No schema change needed — audit_logs already supports entity_type + entity_id.

SET FOREIGN_KEY_CHECKS = 1;
