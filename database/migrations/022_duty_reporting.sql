-- =====================================================
-- Migration 022 — Duty Reporting module
--
-- Adds:
--   • bases.latitude, longitude, geofence_radius_m, timezone (nullable)
--   • duty_reports           — one record per check-in / check-out cycle
--   • duty_exceptions        — exception reasons + manager review lifecycle
--   • duty_reporting_settings — per-tenant module configuration
--   • modules row 'duty_reporting' + capabilities + tenant enablement
--
-- Design notes:
--   • tenant_id enforced on all duty tables (multi-tenant isolation)
--   • geofence columns on bases are nullable — geofence is optional per-tenant
--   • state column uses ENUM: supports full lifecycle incl. exception review
--   • FK to rosters is nullable (duty may be reported without roster assignment)
--
-- MySQL 8.0+, Namecheap compatible. Idempotent via INFORMATION_SCHEMA guards.
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─── 1. Extend bases with geo columns ─────────────────────────────────────────

DROP PROCEDURE IF EXISTS _m022_add_base_lat;
DELIMITER //
CREATE PROCEDURE _m022_add_base_lat()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'bases'
           AND COLUMN_NAME = 'latitude'
    ) THEN
        ALTER TABLE `bases`
            ADD COLUMN `latitude`  DECIMAL(10, 7) NULL DEFAULT NULL AFTER `code`,
            ADD COLUMN `longitude` DECIMAL(10, 7) NULL DEFAULT NULL AFTER `latitude`,
            ADD COLUMN `geofence_radius_m` INT UNSIGNED NULL DEFAULT NULL
                COMMENT 'Geofence radius in metres; NULL = no geofence on this base' AFTER `longitude`,
            ADD COLUMN `timezone` VARCHAR(64) NULL DEFAULT NULL
                COMMENT 'IANA zone, e.g. Europe/London' AFTER `geofence_radius_m`;
    END IF;
END//
DELIMITER ;
CALL _m022_add_base_lat();
DROP PROCEDURE IF EXISTS _m022_add_base_lat;

-- ─── 2. duty_reports ──────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `duty_reports` (
    `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`           INT UNSIGNED NOT NULL,
    `user_id`             INT UNSIGNED NOT NULL,
    `role_at_event`       VARCHAR(60)  NULL DEFAULT NULL
                              COMMENT 'primary role slug at time of check-in',
    `state`               ENUM('checked_in','on_duty','checked_out',
                               'missed_report','exception_pending_review',
                               'exception_approved','exception_rejected')
                              NOT NULL DEFAULT 'checked_in',
    `check_in_at_utc`     TIMESTAMP NULL DEFAULT NULL,
    `check_in_at_local`   DATETIME  NULL DEFAULT NULL,
    `check_in_lat`        DECIMAL(10, 7) NULL DEFAULT NULL,
    `check_in_lng`        DECIMAL(10, 7) NULL DEFAULT NULL,
    `check_in_base_id`    INT UNSIGNED NULL DEFAULT NULL,
    `check_in_method`     ENUM('device','biometric','manual','offline_queue','admin_corrected')
                              NOT NULL DEFAULT 'device',
    `inside_geofence`     TINYINT(1) NULL DEFAULT NULL
                              COMMENT 'NULL when geofence not evaluated; 0/1 otherwise',
    `trusted_device_id`   INT UNSIGNED NULL DEFAULT NULL,
    `roster_id`           INT UNSIGNED NULL DEFAULT NULL,
    `check_out_at_utc`    TIMESTAMP NULL DEFAULT NULL,
    `check_out_at_local`  DATETIME  NULL DEFAULT NULL,
    `check_out_lat`       DECIMAL(10, 7) NULL DEFAULT NULL,
    `check_out_lng`       DECIMAL(10, 7) NULL DEFAULT NULL,
    `duration_minutes`    INT UNSIGNED NULL DEFAULT NULL,
    `notes`               TEXT NULL DEFAULT NULL,
    `device_uuid`         VARCHAR(64) NULL DEFAULT NULL,
    `created_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                              ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_dr_tenant`       (`tenant_id`),
    INDEX `idx_dr_tenant_user`  (`tenant_id`, `user_id`),
    INDEX `idx_dr_tenant_state` (`tenant_id`, `state`),
    INDEX `idx_dr_checkin`      (`tenant_id`, `check_in_at_utc`),
    INDEX `idx_dr_base`         (`check_in_base_id`),
    INDEX `idx_dr_roster`       (`roster_id`),
    CONSTRAINT `fk_dr_tenant` FOREIGN KEY (`tenant_id`)       REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_dr_user`   FOREIGN KEY (`user_id`)         REFERENCES `users`(`id`)   ON DELETE CASCADE,
    CONSTRAINT `fk_dr_base`   FOREIGN KEY (`check_in_base_id`) REFERENCES `bases`(`id`)  ON DELETE SET NULL,
    CONSTRAINT `fk_dr_device` FOREIGN KEY (`trusted_device_id`) REFERENCES `devices`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Duty check-in / check-out events';

-- Soft FK to rosters (left out of constraints — roster rows may be purged per retention)

-- ─── 3. duty_exceptions ───────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `duty_exceptions` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`         INT UNSIGNED NOT NULL,
    `duty_report_id`    INT UNSIGNED NOT NULL,
    `reason_code`       ENUM('outside_geofence','gps_unavailable','offline',
                             'forgot_clock_out','wrong_base_detected',
                             'duplicate_attempt','outstation','manual_correction',
                             'other')
                            NOT NULL,
    `reason_text`       VARCHAR(1000) NULL DEFAULT NULL,
    `submitted_by`      INT UNSIGNED NOT NULL,
    `submitted_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status`            ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `reviewed_by`       INT UNSIGNED NULL DEFAULT NULL,
    `reviewed_at`       TIMESTAMP NULL DEFAULT NULL,
    `review_notes`      VARCHAR(1000) NULL DEFAULT NULL,
    `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                            ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_dex_tenant`        (`tenant_id`),
    INDEX `idx_dex_report`        (`duty_report_id`),
    INDEX `idx_dex_tenant_status` (`tenant_id`, `status`),
    INDEX `idx_dex_submitter`     (`submitted_by`),
    CONSTRAINT `fk_dex_tenant`   FOREIGN KEY (`tenant_id`)      REFERENCES `tenants`(`id`)      ON DELETE CASCADE,
    CONSTRAINT `fk_dex_report`   FOREIGN KEY (`duty_report_id`) REFERENCES `duty_reports`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_dex_submitter` FOREIGN KEY (`submitted_by`)  REFERENCES `users`(`id`)        ON DELETE CASCADE,
    CONSTRAINT `fk_dex_reviewer` FOREIGN KEY (`reviewed_by`)    REFERENCES `users`(`id`)        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Exception reasons + manager review for duty reports';

-- ─── 4. duty_reporting_settings ───────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `duty_reporting_settings` (
    `tenant_id`                     INT UNSIGNED NOT NULL PRIMARY KEY,
    `enabled`                       TINYINT(1) NOT NULL DEFAULT 1,
    `allowed_roles`                 VARCHAR(500) NOT NULL
                                        DEFAULT 'pilot,cabin_crew,engineer'
                                        COMMENT 'CSV of role slugs permitted to use Duty Reporting',
    `geofence_required`             TINYINT(1) NOT NULL DEFAULT 0
                                        COMMENT 'if 1, outside-geofence check-in must go through exception flow',
    `default_radius_m`              INT UNSIGNED NOT NULL DEFAULT 500,
    `allow_outstation`              TINYINT(1) NOT NULL DEFAULT 1,
    `exception_approval_required`   TINYINT(1) NOT NULL DEFAULT 1,
    `clock_out_reminder_minutes`    INT UNSIGNED NOT NULL DEFAULT 840
                                        COMMENT '14h default reminder window',
    `trusted_device_required`       TINYINT(1) NOT NULL DEFAULT 0,
    `biometric_required`            TINYINT(1) NOT NULL DEFAULT 0,
    `retention_days`                INT UNSIGNED NOT NULL DEFAULT 180,
    `updated_at`                    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                                        ON UPDATE CURRENT_TIMESTAMP,
    `updated_by`                    INT UNSIGNED NULL DEFAULT NULL,
    CONSTRAINT `fk_drs_tenant`  FOREIGN KEY (`tenant_id`)  REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_drs_updater` FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Per-tenant Duty Reporting configuration';

-- ─── 5. Seed duty_reporting module row + capabilities ─────────────────────────

INSERT IGNORE INTO `modules` (code, name, description, icon, mobile_capable, sort_order, platform_status)
VALUES (
    'duty_reporting',
    'Duty Reporting',
    'Crew report-for-duty and clock-out with geo-fence and exception handling',
    '🟢',
    1,
    55,
    'available'
);

INSERT IGNORE INTO `module_capabilities` (module_id, capability)
SELECT m.id, cap
  FROM `modules` m
  CROSS JOIN (
      SELECT 'view'             AS cap UNION ALL
      SELECT 'check_in'         UNION ALL
      SELECT 'clock_out'        UNION ALL
      SELECT 'view_history'     UNION ALL
      SELECT 'view_all'         UNION ALL
      SELECT 'approve_exception' UNION ALL
      SELECT 'correct_record'   UNION ALL
      SELECT 'manage_settings'  UNION ALL
      SELECT 'export'           UNION ALL
      SELECT 'view_audit'
  ) caps
 WHERE m.code = 'duty_reporting';

-- Enable for all active tenants (existing installs will pick it up);
-- admins can later toggle off per tenant via /platform/modules.
INSERT IGNORE INTO `tenant_modules` (tenant_id, module_id, is_enabled)
SELECT t.id, m.id, 1
  FROM `tenants` t
  JOIN `modules` m ON m.code = 'duty_reporting'
 WHERE t.is_active = 1;

-- Seed default settings rows for all active tenants
INSERT IGNORE INTO `duty_reporting_settings` (tenant_id)
SELECT id FROM `tenants` WHERE is_active = 1;

SET FOREIGN_KEY_CHECKS = 1;
