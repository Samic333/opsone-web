-- =====================================================
-- Migration 009 — Phase Zero Architecture Correction
-- Adds: enhanced tenants, role_type, module catalog,
--       capabilities, audit enhancements, onboarding,
--       platform access log, invitation tokens
-- Safe to run multiple times (IF NOT EXISTS + IGNORE)
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─── 1. Enhance roles table ───────────────────────────────────────────────────
-- Adds role_type to clearly separate platform / tenant / end-user roles
ALTER TABLE `roles`
    ADD COLUMN IF NOT EXISTS `role_type`
        ENUM('platform','tenant','end_user') NOT NULL DEFAULT 'tenant'
        AFTER `is_system`;

-- Classify existing system roles
UPDATE `roles` SET `role_type` = 'platform'
    WHERE `slug` IN ('super_admin','platform_support','platform_security','system_monitoring')
      AND `tenant_id` IS NULL;

UPDATE `roles` SET `role_type` = 'end_user'
    WHERE `slug` IN ('pilot','cabin_crew','engineer')
      AND `tenant_id` IS NULL;

UPDATE `roles` SET `role_type` = 'tenant'
    WHERE `role_type` = 'tenant'     -- default; covers airline_admin, hr, scheduler, etc.
      AND `tenant_id` IS NULL
      AND `slug` NOT IN ('super_admin','platform_support','platform_security',
                         'system_monitoring','pilot','cabin_crew','engineer');

-- ─── 2. Enhance tenants table ─────────────────────────────────────────────────
ALTER TABLE `tenants`
    ADD COLUMN IF NOT EXISTS `legal_name`         VARCHAR(255) DEFAULT NULL AFTER `name`,
    ADD COLUMN IF NOT EXISTS `display_name`       VARCHAR(100) DEFAULT NULL AFTER `legal_name`,
    ADD COLUMN IF NOT EXISTS `icao_code`          VARCHAR(10)  DEFAULT NULL AFTER `display_name`,
    ADD COLUMN IF NOT EXISTS `iata_code`          VARCHAR(5)   DEFAULT NULL AFTER `icao_code`,
    ADD COLUMN IF NOT EXISTS `primary_country`    VARCHAR(100) DEFAULT NULL AFTER `iata_code`,
    ADD COLUMN IF NOT EXISTS `primary_base`       VARCHAR(100) DEFAULT NULL AFTER `primary_country`,
    ADD COLUMN IF NOT EXISTS `support_tier`       ENUM('standard','premium','enterprise') NOT NULL DEFAULT 'standard' AFTER `primary_base`,
    ADD COLUMN IF NOT EXISTS `onboarding_status`  ENUM('onboarding','active','suspended','offboarding') NOT NULL DEFAULT 'active' AFTER `support_tier`,
    ADD COLUMN IF NOT EXISTS `expected_headcount` INT UNSIGNED DEFAULT NULL AFTER `onboarding_status`,
    ADD COLUMN IF NOT EXISTS `headcount_pilots`   INT UNSIGNED DEFAULT NULL AFTER `expected_headcount`,
    ADD COLUMN IF NOT EXISTS `headcount_cabin`    INT UNSIGNED DEFAULT NULL AFTER `headcount_pilots`,
    ADD COLUMN IF NOT EXISTS `headcount_engineers` INT UNSIGNED DEFAULT NULL AFTER `headcount_cabin`,
    ADD COLUMN IF NOT EXISTS `headcount_schedulers` INT UNSIGNED DEFAULT NULL AFTER `headcount_engineers`,
    ADD COLUMN IF NOT EXISTS `headcount_training` INT UNSIGNED DEFAULT NULL AFTER `headcount_schedulers`,
    ADD COLUMN IF NOT EXISTS `headcount_safety`   INT UNSIGNED DEFAULT NULL AFTER `headcount_training`,
    ADD COLUMN IF NOT EXISTS `headcount_hr`       INT UNSIGNED DEFAULT NULL AFTER `headcount_safety`,
    ADD COLUMN IF NOT EXISTS `notes`              TEXT DEFAULT NULL AFTER `headcount_hr`,
    ADD COLUMN IF NOT EXISTS `onboarded_at`       TIMESTAMP NULL AFTER `notes`,
    ADD COLUMN IF NOT EXISTS `suspended_at`       TIMESTAMP NULL AFTER `onboarded_at`;

-- ─── 3. Enhance audit_logs table ──────────────────────────────────────────────
ALTER TABLE `audit_logs`
    ADD COLUMN IF NOT EXISTS `actor_role`  VARCHAR(100)  DEFAULT NULL AFTER `user_name`,
    ADD COLUMN IF NOT EXISTS `result`      ENUM('success','failure','blocked') NOT NULL DEFAULT 'success' AFTER `details`,
    ADD COLUMN IF NOT EXISTS `reason`      TEXT          DEFAULT NULL AFTER `result`,
    ADD COLUMN IF NOT EXISTS `user_agent`  VARCHAR(500)  DEFAULT NULL AFTER `ip_address`;

-- ─── 4. Modules catalog ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `modules` (
    `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code`                VARCHAR(60)  NOT NULL UNIQUE,
    `name`                VARCHAR(150) NOT NULL,
    `description`         TEXT         DEFAULT NULL,
    `icon`                VARCHAR(50)  DEFAULT NULL,
    `platform_status`     ENUM('available','beta','disabled') NOT NULL DEFAULT 'available',
    `visibility`          ENUM('visible','hidden') NOT NULL DEFAULT 'visible',
    `mobile_capable`      TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Can be surfaced on iPad',
    `requires_platform_enable` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Must be enabled at platform level first',
    `sort_order`          SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    `created_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_modules_code` (`code`),
    INDEX `idx_modules_status` (`platform_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 5. Module capabilities ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `module_capabilities` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `module_id`   INT UNSIGNED NOT NULL,
    `capability`  VARCHAR(60)  NOT NULL COMMENT 'e.g. view, create, edit, delete, approve, publish',
    `description` VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (`module_id`) REFERENCES `modules`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uq_module_cap` (`module_id`, `capability`),
    INDEX `idx_mc_module` (`module_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 6. Per-tenant module enablement ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `tenant_modules` (
    `id`                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`              INT UNSIGNED NOT NULL,
    `module_id`              INT UNSIGNED NOT NULL,
    `is_enabled`             TINYINT(1)   NOT NULL DEFAULT 1,
    `tenant_can_disable`     TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Can the tenant turn this off themselves?',
    `enabled_by`             INT UNSIGNED DEFAULT NULL COMMENT 'Platform user who enabled',
    `enabled_at`             TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `notes`                  TEXT         DEFAULT NULL,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`module_id`) REFERENCES `modules`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uq_tenant_module` (`tenant_id`, `module_id`),
    INDEX `idx_tm_tenant` (`tenant_id`),
    INDEX `idx_tm_module` (`module_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 7. Role capability templates ─────────────────────────────────────────────
-- Maps system roles → capabilities they have by default for a module
CREATE TABLE IF NOT EXISTS `role_capability_templates` (
    `id`                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_slug`            VARCHAR(50)  NOT NULL,
    `module_capability_id` INT UNSIGNED NOT NULL,
    FOREIGN KEY (`module_capability_id`) REFERENCES `module_capabilities`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uq_rct` (`role_slug`, `module_capability_id`),
    INDEX `idx_rct_role` (`role_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 8. Per-user capability overrides ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `user_capability_overrides` (
    `id`                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`              INT UNSIGNED NOT NULL,
    `tenant_id`            INT UNSIGNED NOT NULL,
    `module_capability_id` INT UNSIGNED NOT NULL,
    `granted`              TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '1=grant, 0=revoke',
    `reason`               TEXT         DEFAULT NULL,
    `set_by`               INT UNSIGNED DEFAULT NULL,
    `created_at`           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tenant_id`)  REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`module_capability_id`) REFERENCES `module_capabilities`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uq_uco` (`user_id`, `module_capability_id`),
    INDEX `idx_uco_user` (`user_id`),
    INDEX `idx_uco_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 9. Tenant settings ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `tenant_settings` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`  INT UNSIGNED NOT NULL UNIQUE,
    `timezone`   VARCHAR(60)  NOT NULL DEFAULT 'UTC',
    `date_format` VARCHAR(20) NOT NULL DEFAULT 'Y-m-d',
    `language`   VARCHAR(10)  NOT NULL DEFAULT 'en',
    `mobile_sync_interval_minutes` SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    `require_device_approval`      TINYINT(1)         NOT NULL DEFAULT 1,
    `allow_self_registration`      TINYINT(1)         NOT NULL DEFAULT 0,
    `custom_fields`  JSON         DEFAULT NULL,
    `updated_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 10. Tenant contacts ──────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `tenant_contacts` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`    INT UNSIGNED NOT NULL,
    `contact_type` ENUM('primary_admin','billing','technical','safety','security','other') NOT NULL DEFAULT 'primary_admin',
    `name`         VARCHAR(150) NOT NULL,
    `email`        VARCHAR(255) NOT NULL,
    `phone`        VARCHAR(50)  DEFAULT NULL,
    `title`        VARCHAR(150) DEFAULT NULL,
    `is_primary`   TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    INDEX `idx_tc_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 11. Tenant access policies ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `tenant_access_policies` (
    `id`                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`               INT UNSIGNED NOT NULL UNIQUE,
    `mfa_required`            TINYINT(1)   NOT NULL DEFAULT 0,
    `ip_whitelist`            JSON         DEFAULT NULL COMMENT 'Array of CIDR ranges',
    `session_timeout_minutes` SMALLINT UNSIGNED NOT NULL DEFAULT 120,
    `api_access_enabled`      TINYINT(1)   NOT NULL DEFAULT 1,
    `mobile_access_enabled`   TINYINT(1)   NOT NULL DEFAULT 1,
    `platform_support_access` ENUM('full','readonly','none') NOT NULL DEFAULT 'readonly',
    `updated_at`              TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 12. Platform access log ──────────────────────────────────────────────────
-- Audited log of platform admins accessing tenant areas
CREATE TABLE IF NOT EXISTS `platform_access_log` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `platform_user_id` INT UNSIGNED NOT NULL COMMENT 'Platform admin who initiated access',
    `tenant_id`     INT UNSIGNED NOT NULL,
    `module_area`   VARCHAR(100) DEFAULT NULL COMMENT 'Which module/area was accessed',
    `reason`        TEXT         NOT NULL,
    `ticket_ref`    VARCHAR(100) DEFAULT NULL,
    `ip_address`    VARCHAR(45)  DEFAULT NULL,
    `user_agent`    VARCHAR(500) DEFAULT NULL,
    `access_started_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `access_ended_at`   TIMESTAMP NULL,
    `status`        ENUM('active','ended','denied') NOT NULL DEFAULT 'active',
    INDEX `idx_pal_platform_user` (`platform_user_id`),
    INDEX `idx_pal_tenant` (`tenant_id`),
    INDEX `idx_pal_started` (`access_started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 13. Tenant onboarding requests ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `tenant_onboarding_requests` (
    `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `legal_name`          VARCHAR(255) NOT NULL,
    `display_name`        VARCHAR(100) DEFAULT NULL,
    `icao_code`           VARCHAR(10)  DEFAULT NULL,
    `iata_code`           VARCHAR(5)   DEFAULT NULL,
    `primary_country`     VARCHAR(100) DEFAULT NULL,
    `contact_name`        VARCHAR(150) NOT NULL,
    `contact_email`       VARCHAR(255) NOT NULL,
    `contact_phone`       VARCHAR(50)  DEFAULT NULL,
    `expected_headcount`  INT UNSIGNED DEFAULT NULL,
    `support_tier`        ENUM('standard','premium','enterprise') NOT NULL DEFAULT 'standard',
    `requested_modules`   JSON         DEFAULT NULL COMMENT 'Array of module codes',
    `notes`               TEXT         DEFAULT NULL,
    `status`              ENUM('pending','approved','rejected','provisioned') NOT NULL DEFAULT 'pending',
    `reviewed_by`         INT UNSIGNED DEFAULT NULL COMMENT 'Platform admin who reviewed',
    `reviewed_at`         TIMESTAMP    NULL,
    `review_notes`        TEXT         DEFAULT NULL,
    `tenant_id`           INT UNSIGNED DEFAULT NULL COMMENT 'Set when provisioned',
    `created_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tor_status` (`status`),
    INDEX `idx_tor_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 14. Invitation tokens ────────────────────────────────────────────────────
-- Used for initial admin invitation (no plain-text password emails)
CREATE TABLE IF NOT EXISTS `invitation_tokens` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`     INT UNSIGNED NOT NULL,
    `email`         VARCHAR(255) NOT NULL,
    `name`          VARCHAR(255) DEFAULT NULL,
    `role_slug`     VARCHAR(50)  NOT NULL DEFAULT 'airline_admin',
    `token`         VARCHAR(128) NOT NULL UNIQUE,
    `expires_at`    TIMESTAMP    NOT NULL,
    `accepted_at`   TIMESTAMP    NULL,
    `created_by`    INT UNSIGNED DEFAULT NULL COMMENT 'Platform admin who created invite',
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    INDEX `idx_it_token` (`token`),
    INDEX `idx_it_tenant` (`tenant_id`),
    INDEX `idx_it_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 15. Mobile sync metadata (placeholder) ──────────────────────────────────
-- Lightweight table to support future full sync engine
CREATE TABLE IF NOT EXISTS `mobile_sync_meta` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`       INT UNSIGNED NOT NULL,
    `module_code`     VARCHAR(60)  NOT NULL,
    `last_published_at` TIMESTAMP  NULL COMMENT 'When data last changed and requires re-push',
    `version_hash`    VARCHAR(64)  DEFAULT NULL COMMENT 'Content hash for change detection',
    `updated_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uq_msm` (`tenant_id`, `module_code`),
    INDEX `idx_msm_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
