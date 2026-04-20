-- Migration 021: Safety Actions Table (MySQL)
-- Phase 1.2 — Corrective actions assigned from safety reports
--
-- REQUIREMENT: MySQL Event Scheduler must be enabled.
-- Run: SET GLOBAL event_scheduler = ON;
-- Or add event_scheduler = ON to my.cnf under [mysqld].

SET GLOBAL event_scheduler = ON;

CREATE TABLE IF NOT EXISTS `safety_actions` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `report_id`     INT UNSIGNED NOT NULL,
    `tenant_id`     INT UNSIGNED NOT NULL,
    `title`         VARCHAR(255) NOT NULL,
    `description`   TEXT DEFAULT NULL,
    `assigned_to`   INT UNSIGNED DEFAULT NULL,
    `assigned_by`   INT UNSIGNED NOT NULL,
    `assigned_role` VARCHAR(100) DEFAULT NULL COMMENT 'role slug if not assigned to specific user',
    `due_date`      DATE DEFAULT NULL,
    `status`        ENUM('open','in_progress','completed','overdue','cancelled') NOT NULL DEFAULT 'open',
    `completed_at`  TIMESTAMP NULL DEFAULT NULL,
    `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_sa_report`   (`report_id`),
    INDEX `idx_sa_tenant`   (`tenant_id`),
    INDEX `idx_sa_assignee` (`assigned_to`),
    INDEX `idx_sa_status`   (`status`),
    INDEX `idx_sa_due`      (`due_date`),
    CONSTRAINT `fk_sa_report`   FOREIGN KEY (`report_id`)   REFERENCES `safety_reports`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sa_tenant`   FOREIGN KEY (`tenant_id`)   REFERENCES `tenants`(`id`)         ON DELETE CASCADE,
    CONSTRAINT `fk_sa_assignee` FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`)            ON DELETE SET NULL,
    CONSTRAINT `fk_sa_assigner` FOREIGN KEY (`assigned_by`) REFERENCES `users`(`id`)            ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Corrective actions assigned from safety reports';

-- Nightly event: automatically mark open/in_progress actions as overdue
-- when their due_date has passed. Runs at 01:00 AM each day.
-- NOTE: Requires MySQL event_scheduler = ON (see above).
CREATE EVENT IF NOT EXISTS `mark_overdue_safety_actions`
ON SCHEDULE EVERY 1 DAY STARTS TIMESTAMP(CURDATE(), '01:00:00')
DO
  UPDATE `safety_actions`
  SET `status` = 'overdue'
  WHERE `status` IN ('open','in_progress')
    AND `due_date` < CURDATE();
