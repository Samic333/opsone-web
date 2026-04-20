-- =============================================================================
-- Migration 020: Phase 1 — Safety Reporting (MySQL)
-- =============================================================================
-- Extends the safety_reports table added in migration 019 with Phase 1 columns,
-- and creates all supporting tables for the full safety reporting module.
--
-- Sections:
--   A. ALTER TABLE safety_reports  — add Phase 1 columns (idempotent)
--   B. safety_report_threads       — threaded discussion per report
--   C. safety_report_attachments   — file attachments (report or thread)
--   D. safety_report_status_history— immutable status-change log
--   E. safety_report_assignments   — assignment history
--   F. safety_publications         — safety bulletins / lessons learned
--   G. safety_publication_audiences— audience targeting for publications
--   H. safety_module_settings      — per-tenant feature toggles + seed
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================================
-- SECTION A: ALTER TABLE safety_reports — Phase 1 columns
-- Each ALTER is wrapped in a stored procedure so it is idempotent
-- (MySQL does not support IF NOT EXISTS on ALTER TABLE … ADD COLUMN directly).
-- =============================================================================

DROP PROCEDURE IF EXISTS _add_col_if_missing;
DELIMITER $$
CREATE PROCEDURE _add_col_if_missing(
    IN p_table  VARCHAR(64),
    IN p_column VARCHAR(64),
    IN p_def    TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME   = p_table
           AND COLUMN_NAME  = p_column
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_def);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL _add_col_if_missing('safety_reports', 'is_draft',             'TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`');
CALL _add_col_if_missing('safety_reports', 'event_utc_time',       'TIME NULL AFTER `event_date`');
CALL _add_col_if_missing('safety_reports', 'event_local_time',     'TIME NULL AFTER `event_utc_time`');
CALL _add_col_if_missing('safety_reports', 'location_name',        'VARCHAR(255) NULL AFTER `event_local_time`');
CALL _add_col_if_missing('safety_reports', 'icao_code',            'VARCHAR(10) NULL AFTER `location_name`');
CALL _add_col_if_missing('safety_reports', 'occurrence_type',      "ENUM('occurrence','hazard') NOT NULL DEFAULT 'occurrence' AFTER `icao_code`");
CALL _add_col_if_missing('safety_reports', 'event_type',           'VARCHAR(100) NULL AFTER `occurrence_type`');
CALL _add_col_if_missing('safety_reports', 'initial_risk_score',   "TINYINT(3) UNSIGNED NULL COMMENT '1-5 reporter self-assessment' AFTER `event_type`");
CALL _add_col_if_missing('safety_reports', 'final_severity',       "VARCHAR(20) NULL COMMENT 'safety team classification' AFTER `severity`");
CALL _add_col_if_missing('safety_reports', 'aircraft_registration', 'VARCHAR(20) NULL AFTER `final_severity`');
CALL _add_col_if_missing('safety_reports', 'call_sign',            'VARCHAR(20) NULL AFTER `aircraft_registration`');
CALL _add_col_if_missing('safety_reports', 'extra_fields',         "JSON NULL COMMENT 'template-specific field values' AFTER `call_sign`");
CALL _add_col_if_missing('safety_reports', 'submitted_at',         'TIMESTAMP NULL AFTER `updated_at`');
CALL _add_col_if_missing('safety_reports', 'closed_at',            'TIMESTAMP NULL AFTER `submitted_at`');
CALL _add_col_if_missing('safety_reports', 'template_version',     'TINYINT(3) UNSIGNED NOT NULL DEFAULT 1 AFTER `closed_at`');
CALL _add_col_if_missing('safety_reports', 'reporter_position',    'VARCHAR(100) NULL AFTER `template_version`');

DROP PROCEDURE IF EXISTS _add_col_if_missing;

-- =============================================================================
-- SECTION B: safety_report_threads — threaded discussion per report
-- =============================================================================

CREATE TABLE IF NOT EXISTS `safety_report_threads` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `report_id`   INT UNSIGNED  NOT NULL,
    `author_id`   INT UNSIGNED  NOT NULL,
    `body`        TEXT          NOT NULL,
    `is_internal` TINYINT(1)    NOT NULL DEFAULT 0
                  COMMENT 'Internal note hidden from the original reporter',
    `parent_id`   INT UNSIGNED  NULL DEFAULT NULL
                  COMMENT 'Parent thread entry for threaded replies',
    `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_srt_report` (`report_id`),
    INDEX `idx_srt_author` (`author_id`),
    CONSTRAINT `fk_srt_report`
        FOREIGN KEY (`report_id`) REFERENCES `safety_reports`(`id`)         ON DELETE CASCADE,
    CONSTRAINT `fk_srt_author`
        FOREIGN KEY (`author_id`) REFERENCES `users`(`id`)                  ON DELETE CASCADE,
    CONSTRAINT `fk_srt_parent`
        FOREIGN KEY (`parent_id`) REFERENCES `safety_report_threads`(`id`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Threaded discussion on safety reports (public + internal notes)';

-- =============================================================================
-- SECTION C: safety_report_attachments
-- =============================================================================

CREATE TABLE IF NOT EXISTS `safety_report_attachments` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `report_id`   INT UNSIGNED  NOT NULL,
    `thread_id`   INT UNSIGNED  NULL DEFAULT NULL
                  COMMENT 'Optional: attachment belongs to a specific thread reply',
    `uploaded_by` INT UNSIGNED  NOT NULL,
    `file_name`   VARCHAR(255)  NOT NULL,
    `file_path`   VARCHAR(500)  NOT NULL,
    `file_type`   VARCHAR(50)   NOT NULL,
    `file_size`   INT UNSIGNED  NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_sra_report` (`report_id`),
    CONSTRAINT `fk_sra_report`
        FOREIGN KEY (`report_id`) REFERENCES `safety_reports`(`id`)        ON DELETE CASCADE,
    CONSTRAINT `fk_sra_thread`
        FOREIGN KEY (`thread_id`) REFERENCES `safety_report_threads`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_sra_uploader`
        FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`)               ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='File attachments for safety reports and thread replies';

-- =============================================================================
-- SECTION D: safety_report_status_history — immutable status-change log
-- =============================================================================

CREATE TABLE IF NOT EXISTS `safety_report_status_history` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `report_id`   INT UNSIGNED  NOT NULL,
    `changed_by`  INT UNSIGNED  NOT NULL,
    `from_status` VARCHAR(50)   NULL DEFAULT NULL,
    `to_status`   VARCHAR(50)   NOT NULL,
    `comment`     TEXT          NULL,
    `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_srsh_report` (`report_id`),
    CONSTRAINT `fk_srsh_report`
        FOREIGN KEY (`report_id`)  REFERENCES `safety_reports`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_srsh_changer`
        FOREIGN KEY (`changed_by`) REFERENCES `users`(`id`)          ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Immutable audit trail of status changes on safety reports';

-- =============================================================================
-- SECTION E: safety_report_assignments — assignment history
-- =============================================================================

CREATE TABLE IF NOT EXISTS `safety_report_assignments` (
    `id`            INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `report_id`     INT UNSIGNED   NOT NULL,
    `assigned_by`   INT UNSIGNED   NOT NULL,
    `assigned_to`   INT UNSIGNED   NULL DEFAULT NULL,
    `unassigned_at` TIMESTAMP      NULL DEFAULT NULL,
    `note`          VARCHAR(255)   NULL DEFAULT NULL,
    `created_at`    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_sra2_report` (`report_id`),
    CONSTRAINT `fk_sra2_report`
        FOREIGN KEY (`report_id`)   REFERENCES `safety_reports`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sra2_assigner`
        FOREIGN KEY (`assigned_by`) REFERENCES `users`(`id`)          ON DELETE CASCADE,
    CONSTRAINT `fk_sra2_assignee`
        FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`)          ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Audit trail of safety report assignments';

-- =============================================================================
-- SECTION F: safety_publications — safety bulletins / lessons learned
-- =============================================================================

CREATE TABLE IF NOT EXISTS `safety_publications` (
    `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `tenant_id`        INT UNSIGNED  NOT NULL,
    `created_by`       INT UNSIGNED  NOT NULL,
    `title`            VARCHAR(255)  NOT NULL,
    `summary`          TEXT          NULL,
    `content`          LONGTEXT      NOT NULL,
    `related_report_id` INT UNSIGNED NULL DEFAULT NULL
                       COMMENT 'Optional link to a source safety report',
    `status`           ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    `published_at`     TIMESTAMP     NULL DEFAULT NULL,
    `created_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_sp_tenant` (`tenant_id`),
    INDEX `idx_sp_status` (`status`),
    CONSTRAINT `fk_sp_tenant`
        FOREIGN KEY (`tenant_id`)         REFERENCES `tenants`(`id`)        ON DELETE CASCADE,
    CONSTRAINT `fk_sp_creator`
        FOREIGN KEY (`created_by`)        REFERENCES `users`(`id`)          ON DELETE CASCADE,
    CONSTRAINT `fk_sp_report`
        FOREIGN KEY (`related_report_id`) REFERENCES `safety_reports`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Safety bulletins / lessons-learned publications';

-- =============================================================================
-- SECTION G: safety_publication_audiences — audience targeting
-- =============================================================================

CREATE TABLE IF NOT EXISTS `safety_publication_audiences` (
    `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `publication_id`  INT UNSIGNED  NOT NULL,
    `audience_type`   ENUM('all','pilots','engineers','cabin_crew','management','department') NOT NULL,
    `department_id`   INT UNSIGNED  NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_spa_pub_audience` (`publication_id`, `audience_type`),
    CONSTRAINT `fk_spa_publication`
        FOREIGN KEY (`publication_id`) REFERENCES `safety_publications`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Per-publication audience targeting for safety bulletins';

-- =============================================================================
-- SECTION H: safety_module_settings — per-tenant feature toggles
-- =============================================================================

CREATE TABLE IF NOT EXISTS `safety_module_settings` (
    `id`                   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `tenant_id`            INT UNSIGNED  NOT NULL,
    `enabled_types`        JSON          NULL
                           COMMENT 'Array of report type slugs enabled for this tenant',
    `allow_anonymous`      TINYINT(1)    NOT NULL DEFAULT 1,
    `require_aircraft_reg` TINYINT(1)    NOT NULL DEFAULT 0,
    `risk_matrix_enabled`  TINYINT(1)    NOT NULL DEFAULT 1,
    `retention_days`       INT UNSIGNED  NOT NULL DEFAULT 2555,
    `updated_at`           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_sms_tenant` (`tenant_id`),
    CONSTRAINT `fk_sms_tenant`
        FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Per-tenant safety module configuration';

-- Seed one row per active tenant (idempotent via INSERT IGNORE)
INSERT IGNORE INTO `safety_module_settings` (`tenant_id`, `enabled_types`)
SELECT `id`,
       '["general_hazard","flight_crew_occurrence","maintenance_engineering","ground_ops","quality","hse","tcas","environmental","frat"]'
  FROM `tenants`
 WHERE `status` = 'active';

SET FOREIGN_KEY_CHECKS = 1;
