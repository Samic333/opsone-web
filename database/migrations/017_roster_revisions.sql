-- Phase 5 Extension: Roster Revisions, Reserve Structure, Period Scope
-- MySQL / MariaDB version — run after 016_phase5_roster.sql
-- ─────────────────────────────────────────────────────────────────────────────

-- ─── 1. Extend roster_periods with scope columns ─────────────────────────────
ALTER TABLE `roster_periods`
    ADD COLUMN IF NOT EXISTS `crew_group`    VARCHAR(40) DEFAULT NULL AFTER `notes`,
    ADD COLUMN IF NOT EXISTS `fleet_id`      INT UNSIGNED DEFAULT NULL AFTER `crew_group`,
    ADD COLUMN IF NOT EXISTS `published_by`  INT UNSIGNED DEFAULT NULL AFTER `fleet_id`,
    ADD COLUMN IF NOT EXISTS `published_at`  DATETIME DEFAULT NULL AFTER `published_by`,
    ADD COLUMN IF NOT EXISTS `frozen_at`     DATETIME DEFAULT NULL AFTER `published_at`,
    ADD COLUMN IF NOT EXISTS `revision_number` SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER `frozen_at`;

-- ─── 2. Roster Revisions (post-publication change bundles) ───────────────────
CREATE TABLE IF NOT EXISTS `roster_revisions` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`        INT UNSIGNED NOT NULL,
    `roster_period_id` INT UNSIGNED DEFAULT NULL,
    `revision_ref`     VARCHAR(20) NOT NULL,
    `reason`           VARCHAR(255) NOT NULL,
    `change_source`    ENUM('scheduler','manager_request','crew_request','operational','system') NOT NULL DEFAULT 'scheduler',
    `status`           ENUM('draft','issued','withdrawn') NOT NULL DEFAULT 'draft',
    `requested_by`     INT UNSIGNED DEFAULT NULL,
    `approved_by`      INT UNSIGNED DEFAULT NULL,
    `approved_at`      DATETIME DEFAULT NULL,
    `issued_at`        DATETIME DEFAULT NULL,
    `notes`            TEXT DEFAULT NULL,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_rr_tenant_period` (`tenant_id`, `roster_period_id`),
    INDEX `idx_rr_status`        (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── 3. Roster Revision Items (individual duty changes within a revision) ────
CREATE TABLE IF NOT EXISTS `roster_revision_items` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `roster_revision_id` INT UNSIGNED NOT NULL,
    `tenant_id`         INT UNSIGNED NOT NULL,
    `user_id`           INT UNSIGNED NOT NULL,
    `roster_date`       DATE NOT NULL,
    `old_duty_type`     VARCHAR(30) DEFAULT NULL,
    `old_duty_code`     VARCHAR(20) DEFAULT NULL,
    `new_duty_type`     VARCHAR(30) DEFAULT NULL,
    `new_duty_code`     VARCHAR(20) DEFAULT NULL,
    `change_note`       VARCHAR(255) DEFAULT NULL,
    `acknowledged_at`   DATETIME DEFAULT NULL,
    `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_rri_revision`  (`roster_revision_id`),
    INDEX `idx_rri_user_date` (`user_id`, `roster_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── 4. Extend roster_changes with training_request change type ──────────────
ALTER TABLE `roster_changes`
    MODIFY COLUMN `change_type`
        ENUM('comment','leave_request','swap_request','correction','training_request','chief_pilot_comment','head_cabin_comment','engineering_comment') NOT NULL;

-- ─── 5. Extend rosters with acknowledgement tracking ─────────────────────────
ALTER TABLE `rosters`
    ADD COLUMN IF NOT EXISTS `acknowledged_at` DATETIME DEFAULT NULL AFTER `reserve_type`,
    ADD COLUMN IF NOT EXISTS `revision_id`     INT UNSIGNED DEFAULT NULL AFTER `acknowledged_at`,
    ADD COLUMN IF NOT EXISTS `is_revision`     TINYINT(1) NOT NULL DEFAULT 0 AFTER `revision_id`;
