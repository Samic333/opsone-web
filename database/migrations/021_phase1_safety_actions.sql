-- Migration 021: Safety Actions Table (MySQL)
-- Phase 1.2 — Corrective actions assigned from safety reports
--
-- NOTE: The MySQL EVENT for auto-marking overdue actions has been removed
-- because shared hosting (Namecheap) blocks SET GLOBAL / SUPER privilege.
-- Overdue marking is handled in PHP by SafetyReportModel::markOverdueActions()
-- which is called from the safety dashboard on each load.

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

-- Overdue marking is handled in PHP (SafetyReportModel::markOverdueActions)
-- called from SafetyController::safetyDashboard() on each dashboard load.
