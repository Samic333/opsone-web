-- =============================================================================
-- Migration 019: Phase 0 — Safety Reports (MySQL), Notifications, Retention
-- =============================================================================
-- This migration covers tables that exist only in patch/SQLite files and
-- were missing from the numbered migration sequence for clean MySQL installs.
--
-- AUDIT OF UNVERSIONED PATCHES (as of 2026-04-20):
--   database/patches/phase6_safety.sql
--       → safety_reports, safety_report_updates  ← covered in Section A below
--   database/patches/mysql_missing_tables.sql / mysql_missing_tables_clean.sql
--       → Ad-hoc hotfix dump of many tables already in migs 001-018.
--         These files are deployment artefacts, NOT pending migrations.
--         Tables they introduce that are genuinely absent from migs 001-018:
--         roster_changes, roster_periods  ← already added in 016/017_phase5.
--         No net-new gaps found after cross-check.
--   database/patches/patch_001_demo_mobile_access.sql
--       → Data-only UPDATE, no schema changes.
--   database/patches/mysql_missing_columns.sql / mysql_final_repair.sql
--       → Column-level hotfixes; no new tables.
--
-- Sections in this file:
--   A. safety_reports + safety_report_updates  (from phase6_safety.sql)
--   B. notifications                           (new — NotificationService)
--   C. tenant_retention_policies               (new — RetentionService)
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================================
-- SECTION A: Safety Reports (Phase 6)
-- Source: database/patches/phase6_safety.sql (SQLite only, no numbered mig)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `safety_reports` (
    `id`            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `tenant_id`     INT UNSIGNED     NOT NULL,
    `reference_no`  VARCHAR(50)      NOT NULL,
    `report_type`   VARCHAR(50)      NOT NULL,
    `reporter_id`   INT UNSIGNED     DEFAULT NULL,
    `is_anonymous`  TINYINT(1)       NOT NULL DEFAULT 0,
    `event_date`    DATE             DEFAULT NULL,
    `title`         VARCHAR(255)     NOT NULL,
    `description`   TEXT             NOT NULL,
    `severity`      VARCHAR(20)      NOT NULL DEFAULT 'unassigned',
    `status`        VARCHAR(50)      NOT NULL DEFAULT 'submitted',
    `assigned_to`   INT UNSIGNED     DEFAULT NULL,
    `created_at`    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_safety_ref` (`tenant_id`, `reference_no`),
    INDEX `idx_safety_tenant`   (`tenant_id`),
    INDEX `idx_safety_reporter` (`reporter_id`),
    INDEX `idx_safety_assigned` (`assigned_to`),
    INDEX `idx_safety_status`   (`status`),
    CONSTRAINT `fk_safety_tenant`
        FOREIGN KEY (`tenant_id`)   REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_safety_reporter`
        FOREIGN KEY (`reporter_id`) REFERENCES `users`(`id`)   ON DELETE SET NULL,
    CONSTRAINT `fk_safety_assigned`
        FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Aviation safety occurrence reports (Phase 6)';

CREATE TABLE IF NOT EXISTS `safety_report_updates` (
    `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `report_id`       INT UNSIGNED  NOT NULL,
    `user_id`         INT UNSIGNED  NOT NULL,
    `status_change`   VARCHAR(50)   DEFAULT NULL,
    `severity_change` VARCHAR(50)   DEFAULT NULL,
    `comment`         TEXT          DEFAULT NULL,
    `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_sru_report` (`report_id`),
    INDEX `idx_sru_user`   (`user_id`),
    CONSTRAINT `fk_sru_report`
        FOREIGN KEY (`report_id`) REFERENCES `safety_reports`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sru_user`
        FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)          ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Audit trail of changes to safety reports';

-- =============================================================================
-- SECTION B: Notifications
-- Used by app/Services/NotificationService.php
-- =============================================================================

CREATE TABLE IF NOT EXISTS `notifications` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `tenant_id`   INT UNSIGNED  NOT NULL,
    `user_id`     INT UNSIGNED  NOT NULL,
    `title`       VARCHAR(255)  NOT NULL,
    `body`        TEXT          NOT NULL,
    `link`        VARCHAR(500)  DEFAULT NULL,
    `is_read`     TINYINT(1)    NOT NULL DEFAULT 0,
    `read_at`     TIMESTAMP     NULL DEFAULT NULL,
    `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_notif_tenant_user` (`tenant_id`, `user_id`),
    INDEX `idx_notif_unread`      (`user_id`, `is_read`),
    CONSTRAINT `fk_notif_tenant`
        FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_notif_user`
        FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='In-app notification inbox rows (NotificationService)';

-- =============================================================================
-- SECTION C: Tenant Retention Policies
-- Used by app/Services/RetentionService.php
-- =============================================================================

CREATE TABLE IF NOT EXISTS `tenant_retention_policies` (
    `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `tenant_id`     INT UNSIGNED  NOT NULL,
    `module`        VARCHAR(100)  NOT NULL,
    `retain_days`   INT UNSIGNED  NOT NULL,
    `note`          VARCHAR(255)  DEFAULT NULL,
    `updated_by`    INT UNSIGNED  DEFAULT NULL,
    `updated_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_retention` (`tenant_id`, `module`),
    CONSTRAINT `fk_retention_tenant`
        FOREIGN KEY (`tenant_id`)  REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_retention_updater`
        FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Per-tenant data retention window overrides (RetentionService)';

SET FOREIGN_KEY_CHECKS = 1;
