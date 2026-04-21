-- Phase 6: Personnel Compliance Record System (MySQL)
-- Extends Phase 3 (crew_profiles, licenses, qualifications) with:
--   - Document vault (any-type scans with approval state)
--   - Multiple emergency contacts per staff
--   - Role-based required document catalogue (tenant configurable)
--   - Compliance change-request approval workflow
--   - Expiry alert ledger (dedup + multi-recipient tracking)
--   - Line-manager linkage on users (for alert routing)
--   - Approval metadata on licenses/qualifications
--   - Visa fields + profile photo + address on crew_profiles
--
-- Safe to re-run (uses IF NOT EXISTS / conditional ALTER guards).

-- ─── crew_profiles extensions ──────────────────────────────────────────────
ALTER TABLE `crew_profiles`
    ADD COLUMN IF NOT EXISTS `profile_photo_path` VARCHAR(500) DEFAULT NULL AFTER `phone`,
    ADD COLUMN IF NOT EXISTS `address`            VARCHAR(500) DEFAULT NULL AFTER `profile_photo_path`,
    ADD COLUMN IF NOT EXISTS `visa_number`        VARCHAR(100) DEFAULT NULL AFTER `passport_expiry`,
    ADD COLUMN IF NOT EXISTS `visa_country`       VARCHAR(100) DEFAULT NULL AFTER `visa_number`,
    ADD COLUMN IF NOT EXISTS `visa_type`          VARCHAR(100) DEFAULT NULL AFTER `visa_country`,
    ADD COLUMN IF NOT EXISTS `visa_expiry`        DATE          DEFAULT NULL AFTER `visa_type`;

CREATE INDEX IF NOT EXISTS `idx_crew_profiles_visa_expiry` ON `crew_profiles` (`visa_expiry`);

-- ─── users: line manager linkage ───────────────────────────────────────────
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `line_manager_id` INT UNSIGNED DEFAULT NULL AFTER `base_id`;

CREATE INDEX IF NOT EXISTS `idx_users_line_manager` ON `users` (`line_manager_id`);

-- ─── licenses: approval + scan metadata ────────────────────────────────────
ALTER TABLE `licenses`
    ADD COLUMN IF NOT EXISTS `status`            ENUM('valid','expired','pending_approval','pending_renewal','suspended') NOT NULL DEFAULT 'valid' AFTER `notes`,
    ADD COLUMN IF NOT EXISTS `approved_by`       INT UNSIGNED DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `approved_at`       TIMESTAMP NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `file_id`           INT UNSIGNED DEFAULT NULL COMMENT 'Scanned licence document',
    ADD COLUMN IF NOT EXISTS `document_scan_path` VARCHAR(500) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `pending_change_request_id` INT UNSIGNED DEFAULT NULL;

CREATE INDEX IF NOT EXISTS `idx_licenses_status` ON `licenses` (`status`);

-- ─── qualifications: approval + scan metadata ─────────────────────────────
ALTER TABLE `qualifications`
    ADD COLUMN IF NOT EXISTS `approved_by` INT UNSIGNED DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `approved_at` TIMESTAMP NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `file_id`     INT UNSIGNED DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `document_scan_path` VARCHAR(500) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `pending_change_request_id` INT UNSIGNED DEFAULT NULL;

-- ─── crew_documents: unified document vault ────────────────────────────────
CREATE TABLE IF NOT EXISTS `crew_documents` (
    `id`                  INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`           INT UNSIGNED    NOT NULL,
    `user_id`             INT UNSIGNED    NOT NULL,
    `doc_type`            VARCHAR(80)     NOT NULL COMMENT 'e.g. passport, medical, visa, contract, company_id, permit, certificate, dangerous_goods',
    `doc_category`        VARCHAR(60)     DEFAULT NULL COMMENT 'Grouping: identification, medical, regulatory, training, contract, company',
    `doc_title`           VARCHAR(200)    NOT NULL,
    `doc_number`          VARCHAR(100)    DEFAULT NULL,
    `issuing_authority`   VARCHAR(150)    DEFAULT NULL,
    `issue_date`          DATE            DEFAULT NULL,
    `expiry_date`         DATE            DEFAULT NULL,
    `file_path`           VARCHAR(500)    DEFAULT NULL,
    `file_name`           VARCHAR(255)    DEFAULT NULL,
    `file_mime`           VARCHAR(100)    DEFAULT NULL,
    `file_size`           BIGINT UNSIGNED DEFAULT NULL,
    `status`              ENUM('valid','expired','pending_approval','rejected','revoked') NOT NULL DEFAULT 'pending_approval',
    `approved_by`         INT UNSIGNED    DEFAULT NULL,
    `approved_at`         TIMESTAMP NULL  DEFAULT NULL,
    `rejection_reason`    VARCHAR(500)    DEFAULT NULL,
    `replaces_document_id` INT UNSIGNED   DEFAULT NULL COMMENT 'If this row supersedes an older approved doc',
    `uploaded_by`         INT UNSIGNED    DEFAULT NULL,
    `notes`               TEXT            DEFAULT NULL,
    `created_at`          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)   ON DELETE CASCADE,
    INDEX `idx_crew_docs_user`        (`user_id`),
    INDEX `idx_crew_docs_tenant`      (`tenant_id`),
    INDEX `idx_crew_docs_expiry`      (`expiry_date`),
    INDEX `idx_crew_docs_status`      (`status`),
    INDEX `idx_crew_docs_type`        (`doc_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── emergency_contacts: additional/secondary contacts ─────────────────────
CREATE TABLE IF NOT EXISTS `emergency_contacts` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`     INT UNSIGNED NOT NULL,
    `user_id`       INT UNSIGNED NOT NULL,
    `contact_name`  VARCHAR(200) NOT NULL,
    `relation`      VARCHAR(100) DEFAULT NULL,
    `phone_primary` VARCHAR(40)  DEFAULT NULL,
    `phone_alt`     VARCHAR(40)  DEFAULT NULL,
    `email`         VARCHAR(255) DEFAULT NULL,
    `address`       VARCHAR(500) DEFAULT NULL,
    `is_primary`    TINYINT(1)   NOT NULL DEFAULT 0,
    `sort_order`    INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)   ON DELETE CASCADE,
    INDEX `idx_emg_user`   (`user_id`),
    INDEX `idx_emg_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── role_required_documents: tenant-configurable requirement matrix ──────
CREATE TABLE IF NOT EXISTS `role_required_documents` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`       INT UNSIGNED DEFAULT NULL COMMENT 'NULL = system default applicable to all tenants',
    `role_slug`       VARCHAR(50)  NOT NULL,
    `doc_type`        VARCHAR(80)  NOT NULL,
    `doc_label`       VARCHAR(150) NOT NULL,
    `is_mandatory`    TINYINT(1)   NOT NULL DEFAULT 1,
    `warning_days`    INT UNSIGNED NOT NULL DEFAULT 60 COMMENT 'Days before expiry to flag warning',
    `critical_days`   INT UNSIGNED NOT NULL DEFAULT 14 COMMENT 'Days before expiry to flag critical',
    `description`     VARCHAR(500) DEFAULT NULL,
    `is_active`       TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_role_doc` (`tenant_id`, `role_slug`, `doc_type`),
    INDEX `idx_role_req_role` (`role_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── compliance_change_requests: approval workflow ─────────────────────────
CREATE TABLE IF NOT EXISTS `compliance_change_requests` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`         INT UNSIGNED NOT NULL,
    `user_id`           INT UNSIGNED NOT NULL COMMENT 'Target of the change',
    `requester_user_id` INT UNSIGNED NOT NULL,
    `target_entity`     ENUM('profile','license','qualification','document','emergency_contact','assignment') NOT NULL,
    `target_id`         INT UNSIGNED DEFAULT NULL COMMENT 'ID of existing record being modified; NULL for new',
    `change_type`       ENUM('create','update','delete','replace') NOT NULL DEFAULT 'update',
    `payload`           TEXT NOT NULL COMMENT 'JSON of proposed values',
    `supporting_file_id`    INT UNSIGNED DEFAULT NULL COMMENT 'Link to files table for supporting upload',
    `supporting_document_id` INT UNSIGNED DEFAULT NULL COMMENT 'Link to crew_documents when uploaded via this request',
    `status`            ENUM('submitted','under_review','approved','rejected','info_requested','withdrawn') NOT NULL DEFAULT 'submitted',
    `reviewer_user_id`  INT UNSIGNED DEFAULT NULL,
    `reviewer_notes`    TEXT DEFAULT NULL,
    `submitted_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reviewed_at`       TIMESTAMP NULL DEFAULT NULL,
    `created_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)   ON DELETE CASCADE,
    INDEX `idx_ccr_tenant`    (`tenant_id`),
    INDEX `idx_ccr_user`      (`user_id`),
    INDEX `idx_ccr_requester` (`requester_user_id`),
    INDEX `idx_ccr_status`    (`status`),
    INDEX `idx_ccr_submitted` (`submitted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── expiry_alerts: notification ledger ────────────────────────────────────
CREATE TABLE IF NOT EXISTS `expiry_alerts` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`        INT UNSIGNED NOT NULL,
    `user_id`          INT UNSIGNED NOT NULL,
    `entity_type`      VARCHAR(40)  NOT NULL COMMENT 'license, medical, passport, visa, document, qualification, contract',
    `entity_id`        INT UNSIGNED NOT NULL,
    `expiry_date`      DATE         NOT NULL,
    `alert_level`      ENUM('warning','critical','expired') NOT NULL,
    `sent_to_user`     TINYINT(1)   NOT NULL DEFAULT 0,
    `sent_to_hr`       TINYINT(1)   NOT NULL DEFAULT 0,
    `sent_to_manager`  TINYINT(1)   NOT NULL DEFAULT 0,
    `last_sent_at`     TIMESTAMP NULL DEFAULT NULL,
    `cleared_at`       TIMESTAMP NULL DEFAULT NULL COMMENT 'Set when replacement doc approved',
    `created_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_expiry_alert` (`tenant_id`, `user_id`, `entity_type`, `entity_id`, `alert_level`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)   ON DELETE CASCADE,
    INDEX `idx_expiry_alerts_user`    (`user_id`),
    INDEX `idx_expiry_alerts_tenant`  (`tenant_id`),
    INDEX `idx_expiry_alerts_pending` (`cleared_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Seed: system-default role_required_documents (tenant_id NULL) ────────
-- These are defaults; tenants can override/extend.
INSERT IGNORE INTO `role_required_documents` (`tenant_id`, `role_slug`, `doc_type`, `doc_label`, `is_mandatory`, `warning_days`, `critical_days`, `description`) VALUES
-- Pilot
(NULL, 'pilot',        'license',        'Pilot Licence',          1, 60, 14, 'ATPL / CPL / PPL'),
(NULL, 'pilot',        'medical',        'Medical Certificate',    1, 60, 14, 'Class 1 medical'),
(NULL, 'pilot',        'passport',       'Passport',               1, 180, 60, 'Valid passport'),
(NULL, 'pilot',        'type_rating',    'Type Rating',            1, 60, 14, 'Current aircraft type rating'),
-- Cabin crew
(NULL, 'cabin_crew',   'cabin_attestation', 'Cabin Crew Attestation', 1, 60, 14, 'EASA/DGCA cabin crew attestation'),
(NULL, 'cabin_crew',   'medical',        'Medical Certificate',    1, 60, 14, 'Cabin crew medical fitness'),
(NULL, 'cabin_crew',   'passport',       'Passport',               1, 180, 60, 'Valid passport'),
-- Engineer
(NULL, 'engineer',     'license',        'Engineer Licence',       1, 60, 14, 'AME/Part-66 licence'),
(NULL, 'engineer',     'company_id',     'Company ID',             1, 60, 14, 'Company-issued ID card'),
(NULL, 'engineer',     'type_auth',      'Type Authorization',     1, 60, 14, 'Aircraft type authorization'),
-- Base manager
(NULL, 'base_manager', 'company_id',     'Company ID',             1, 60, 14, 'Base manager ID'),
(NULL, 'base_manager', 'contract',       'Employment Contract',    1, 60, 14, 'Signed contract'),
(NULL, 'base_manager', 'airside_permit', 'Airside Permit',         0, 60, 14, 'Station/airport airside pass'),
-- Station staff / operational
(NULL, 'scheduler',    'company_id',     'Company ID',             1, 60, 14, NULL),
(NULL, 'document_control','company_id',  'Company ID',             1, 60, 14, NULL),
-- HR / Admin
(NULL, 'hr',           'company_id',     'Company ID',             1, 60, 14, NULL),
(NULL, 'airline_admin','company_id',     'Company ID',             0, 60, 14, NULL);
