-- =============================================================================
-- Phase 4 (V2) — Controlled Document Distribution & Acknowledgment
-- MySQL variant
--
-- Adds to existing files/file_acknowledgements/file_role_visibility:
--   • Department and base targeting (additional OR filters)
--   • Version chain (replaces_file_id + superseded_at)
--   • Read receipts distinct from acknowledgements
--
-- Read vs Acknowledged:
--   file_reads            = user opened/downloaded (implicit)
--   file_acknowledgements = user explicitly confirmed (requires_ack files)
-- =============================================================================

-- ── A. Department targeting ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `file_department_visibility` (
    `file_id`        INT UNSIGNED NOT NULL,
    `department_id`  INT UNSIGNED NOT NULL,
    PRIMARY KEY (`file_id`, `department_id`),
    INDEX `idx_fdv_file` (`file_id`),
    INDEX `idx_fdv_dept` (`department_id`),
    CONSTRAINT `fk_fdv_file` FOREIGN KEY (`file_id`) REFERENCES `files`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fdv_dept` FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── B. Base/station targeting ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `file_base_visibility` (
    `file_id`   INT UNSIGNED NOT NULL,
    `base_id`   INT UNSIGNED NOT NULL,
    PRIMARY KEY (`file_id`, `base_id`),
    INDEX `idx_fbv_file` (`file_id`),
    INDEX `idx_fbv_base` (`base_id`),
    CONSTRAINT `fk_fbv_file` FOREIGN KEY (`file_id`) REFERENCES `files`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fbv_base` FOREIGN KEY (`base_id`) REFERENCES `bases`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── C. Read receipts (separate from ack) ────────────────────────────────
CREATE TABLE IF NOT EXISTS `file_reads` (
    `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `file_id`    INT UNSIGNED  NOT NULL,
    `user_id`    INT UNSIGNED  NOT NULL,
    `tenant_id`  INT UNSIGNED  NOT NULL,
    `version`    VARCHAR(50)   DEFAULT NULL,
    `read_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_file_user` (`file_id`, `user_id`),
    INDEX `idx_fr_user_tenant` (`user_id`, `tenant_id`),
    CONSTRAINT `fk_fr_file`   FOREIGN KEY (`file_id`)   REFERENCES `files`(`id`)   ON DELETE CASCADE,
    CONSTRAINT `fk_fr_user`   FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)   ON DELETE CASCADE,
    CONSTRAINT `fk_fr_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── D. Version chain columns on files ───────────────────────────────────
ALTER TABLE `files` ADD COLUMN `replaces_file_id` INT UNSIGNED DEFAULT NULL AFTER `version`;
ALTER TABLE `files` ADD COLUMN `superseded_at`    TIMESTAMP    NULL DEFAULT NULL AFTER `replaces_file_id`;
ALTER TABLE `files` ADD CONSTRAINT `fk_files_replaces` FOREIGN KEY (`replaces_file_id`) REFERENCES `files`(`id`) ON DELETE SET NULL;
ALTER TABLE `files` ADD INDEX `idx_files_replaces` (`replaces_file_id`);
