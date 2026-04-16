-- -----------------------------------------------------
-- MySQL Missing Tables Patch for Namecheap (Robust Version)
-- -----------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;

-- Notices Table
CREATE TABLE IF NOT EXISTS `notices` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`      INT UNSIGNED NOT NULL,
    `title`          VARCHAR(255) NOT NULL,
    `body`           TEXT NOT NULL,
    `priority`       VARCHAR(20) NOT NULL DEFAULT 'normal',
    `category`       VARCHAR(100) DEFAULT 'general',
    `published`      TINYINT(1) NOT NULL DEFAULT 0,
    `published_at`   TIMESTAMP NULL DEFAULT NULL,
    `expires_at`     TIMESTAMP NULL DEFAULT NULL,
    `created_by`     INT UNSIGNED DEFAULT NULL,
    `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `requires_ack`   TINYINT(1) NOT NULL DEFAULT 0,
    `target_roles`   TEXT DEFAULT NULL,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notice Reads (Track who read what)
CREATE TABLE IF NOT EXISTS `notice_reads` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `notice_id`       INT UNSIGNED NOT NULL,
    `user_id`         INT UNSIGNED NOT NULL,
    `tenant_id`       INT UNSIGNED NOT NULL,
    `read_at`         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `acknowledged_at` TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (`notice_id`) REFERENCES `notices`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)   ON DELETE CASCADE,
    UNIQUE KEY `uq_notice_user` (`notice_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- File Acknowledgements
CREATE TABLE IF NOT EXISTS `file_acknowledgements` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `file_id`         INT UNSIGNED NOT NULL,
    `user_id`         INT UNSIGNED NOT NULL,
    `tenant_id`       INT UNSIGNED NOT NULL,
    `version`         VARCHAR(50) DEFAULT NULL,
    `device_id`       INT UNSIGNED DEFAULT NULL,
    `acknowledged_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_file_user` (`file_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crew Profiles
CREATE TABLE IF NOT EXISTS `crew_profiles` (
    `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`             INT UNSIGNED NOT NULL UNIQUE,
    `tenant_id`           INT UNSIGNED NOT NULL,
    `date_of_birth`       DATE DEFAULT NULL,
    `nationality`         VARCHAR(100) DEFAULT NULL,
    `phone`               VARCHAR(50) DEFAULT NULL,
    `emergency_name`      VARCHAR(255) DEFAULT NULL,
    `emergency_phone`     VARCHAR(50) DEFAULT NULL,
    `emergency_relation`  VARCHAR(100) DEFAULT NULL,
    `passport_number`     VARCHAR(50) DEFAULT NULL,
    `passport_country`    VARCHAR(100) DEFAULT NULL,
    `passport_expiry`     DATE DEFAULT NULL,
    `medical_class`       VARCHAR(50) DEFAULT NULL,
    `medical_expiry`      DATE DEFAULT NULL,
    `contract_type`       VARCHAR(50) DEFAULT NULL,
    `contract_expiry`     DATE DEFAULT NULL,
    `created_at`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Licenses
CREATE TABLE IF NOT EXISTS `licenses` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`           INT UNSIGNED NOT NULL,
    `tenant_id`         INT UNSIGNED NOT NULL,
    `license_type`      VARCHAR(100) NOT NULL,
    `license_number`    VARCHAR(100) DEFAULT NULL,
    `issuing_authority` VARCHAR(255) DEFAULT NULL,
    `issue_date`        DATE DEFAULT NULL,
    `expiry_date`       DATE DEFAULT NULL,
    `notes`             TEXT DEFAULT NULL,
    `created_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Rosters
CREATE TABLE IF NOT EXISTS `rosters` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`        INT UNSIGNED NOT NULL,
    `user_id`          INT UNSIGNED NOT NULL,
    `roster_date`      DATE NOT NULL,
    `duty_type`        VARCHAR(50) NOT NULL DEFAULT 'off',
    `duty_code`        VARCHAR(50) DEFAULT NULL,
    `notes`            TEXT DEFAULT NULL,
    `roster_period_id` INT UNSIGNED DEFAULT NULL,
    `base_id`          INT UNSIGNED DEFAULT NULL,
    `fleet_id`         INT UNSIGNED DEFAULT NULL,
    `reserve_type`     VARCHAR(50) DEFAULT NULL,
    `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_user_date` (`user_id`, `roster_date`),
    INDEX `idx_roster_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- FDM Uploads
CREATE TABLE IF NOT EXISTS `fdm_uploads` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`     INT UNSIGNED NOT NULL,
    `uploaded_by`   INT UNSIGNED NOT NULL,
    `filename`      VARCHAR(255) NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `flight_date`   DATE DEFAULT NULL,
    `aircraft_reg`  VARCHAR(20) DEFAULT NULL,
    `flight_number` VARCHAR(20) DEFAULT NULL,
    `event_count`   INT DEFAULT 0,
    `status`        VARCHAR(50) NOT NULL DEFAULT 'pending',
    `notes`         TEXT DEFAULT NULL,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- FDM Events
CREATE TABLE IF NOT EXISTS `fdm_events` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`      INT UNSIGNED NOT NULL,
    `fdm_upload_id`  INT UNSIGNED DEFAULT NULL,
    `event_type`     VARCHAR(100) NOT NULL DEFAULT 'other',
    `severity`       VARCHAR(20) NOT NULL DEFAULT 'medium',
    `flight_date`    DATE DEFAULT NULL,
    `aircraft_reg`   VARCHAR(20) DEFAULT NULL,
    `flight_number`  VARCHAR(20) DEFAULT NULL,
    `flight_phase`   VARCHAR(100) DEFAULT NULL,
    `parameter`      VARCHAR(255) DEFAULT NULL,
    `value_recorded` DOUBLE DEFAULT NULL,
    `threshold`      DOUBLE DEFAULT NULL,
    `notes`          TEXT DEFAULT NULL,
    `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Modules
CREATE TABLE IF NOT EXISTS `modules` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code`             VARCHAR(100) NOT NULL UNIQUE,
    `name`             VARCHAR(255) NOT NULL,
    `description`      TEXT DEFAULT NULL,
    `icon`             VARCHAR(100) DEFAULT NULL,
    `platform_status`  VARCHAR(50) NOT NULL DEFAULT 'available',
    `visibility`       VARCHAR(50) NOT NULL DEFAULT 'visible',
    `mobile_capable`   TINYINT(1) NOT NULL DEFAULT 0,
    `requires_platform_enable` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order`       INT NOT NULL DEFAULT 100,
    `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Module Capabilities
CREATE TABLE IF NOT EXISTS `module_capabilities` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `module_id`   INT UNSIGNED NOT NULL,
    `capability`  VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    FOREIGN KEY (`module_id`) REFERENCES `modules`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uq_module_cap` (`module_id`, `capability`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tenant Modules
CREATE TABLE IF NOT EXISTS `tenant_modules` (
    `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`          INT UNSIGNED NOT NULL,
    `module_id`          INT UNSIGNED NOT NULL,
    `is_enabled`         TINYINT(1) NOT NULL DEFAULT 1,
    `tenant_can_disable` TINYINT(1) NOT NULL DEFAULT 0,
    `enabled_by`         INT UNSIGNED DEFAULT NULL,
    `enabled_at`         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `notes`              TEXT DEFAULT NULL,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`module_id`) REFERENCES `modules`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uq_tenant_module` (`tenant_id`, `module_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Role Capability Templates
CREATE TABLE IF NOT EXISTS `role_capability_templates` (
    `id`                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_slug`            VARCHAR(100) NOT NULL,
    `module_capability_id` INT UNSIGNED NOT NULL,
    FOREIGN KEY (`module_capability_id`) REFERENCES `module_capabilities`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uq_role_cap` (`role_slug`, `module_capability_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User Capability Overrides
CREATE TABLE IF NOT EXISTS `user_capability_overrides` (
    `id`                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`              INT UNSIGNED NOT NULL,
    `tenant_id`            INT UNSIGNED NOT NULL,
    `module_capability_id` INT UNSIGNED NOT NULL,
    `granted`              TINYINT(1) NOT NULL DEFAULT 1,
    `reason`               TEXT DEFAULT NULL,
    `set_by`               INT UNSIGNED DEFAULT NULL,
    `created_at`           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`tenant_id`)  REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`module_capability_id`) REFERENCES `module_capabilities`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uq_user_cap` (`user_id`, `module_capability_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tenant Settings
CREATE TABLE IF NOT EXISTS `tenant_settings` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`       INT UNSIGNED NOT NULL UNIQUE,
    `timezone`        VARCHAR(100) NOT NULL DEFAULT 'UTC',
    `date_format`     VARCHAR(50) NOT NULL DEFAULT 'Y-m-d',
    `language`        VARCHAR(10) NOT NULL DEFAULT 'en',
    `mobile_sync_interval_minutes` INT NOT NULL DEFAULT 60,
    `require_device_approval`      TINYINT(1) NOT NULL DEFAULT 1,
    `allow_self_registration`      TINYINT(1) NOT NULL DEFAULT 0,
    `custom_fields`   JSON DEFAULT NULL,
    `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tenant Contacts
CREATE TABLE IF NOT EXISTS `tenant_contacts` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`    INT UNSIGNED NOT NULL,
    `contact_type` VARCHAR(50) NOT NULL DEFAULT 'primary_admin',
    `name`         VARCHAR(255) NOT NULL,
    `email`        VARCHAR(255) NOT NULL,
    `phone`        VARCHAR(50) DEFAULT NULL,
    `title`        VARCHAR(100) DEFAULT NULL,
    `is_primary`   TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tenant Access Policies
CREATE TABLE IF NOT EXISTS `tenant_access_policies` (
    `id`                       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`                INT UNSIGNED NOT NULL UNIQUE,
    `mfa_required`             TINYINT(1) NOT NULL DEFAULT 0,
    `ip_whitelist`             TEXT DEFAULT NULL,
    `session_timeout_minutes`  INT NOT NULL DEFAULT 120,
    `api_access_enabled`       TINYINT(1) NOT NULL DEFAULT 1,
    `mobile_access_enabled`    TINYINT(1) NOT NULL DEFAULT 1,
    `platform_support_access`  VARCHAR(20) NOT NULL DEFAULT 'readonly',
    `updated_at`               TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Platform Access Log
CREATE TABLE IF NOT EXISTS `platform_access_log` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `platform_user_id`  INT UNSIGNED NOT NULL,
    `tenant_id`         INT UNSIGNED NOT NULL,
    `module_area`       VARCHAR(100) DEFAULT NULL,
    `reason`            TEXT NOT NULL,
    `ticket_ref`        VARCHAR(50) DEFAULT NULL,
    `ip_address`        VARCHAR(45) DEFAULT NULL,
    `user_agent`        TEXT DEFAULT NULL,
    `access_started_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `access_ended_at`   TIMESTAMP NULL DEFAULT NULL,
    `status`            VARCHAR(20) NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tenant Onboarding Requests
CREATE TABLE IF NOT EXISTS `tenant_onboarding_requests` (
    `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `legal_name`         VARCHAR(255) NOT NULL,
    `display_name`       VARCHAR(255) DEFAULT NULL,
    `icao_code`          VARCHAR(10) DEFAULT NULL,
    `iata_code`          VARCHAR(10) DEFAULT NULL,
    `primary_country`    VARCHAR(100) DEFAULT NULL,
    `contact_name`       VARCHAR(255) NOT NULL,
    `contact_email`      VARCHAR(255) NOT NULL,
    `contact_phone`      VARCHAR(50) DEFAULT NULL,
    `expected_headcount` INT DEFAULT NULL,
    `support_tier`       VARCHAR(50) NOT NULL DEFAULT 'standard',
    `requested_modules`  TEXT DEFAULT NULL,
    `notes`              TEXT DEFAULT NULL,
    `status`             VARCHAR(50) NOT NULL DEFAULT 'pending',
    `reviewed_by`        INT UNSIGNED DEFAULT NULL,
    `reviewed_at`        TIMESTAMP NULL DEFAULT NULL,
    `review_notes`       TEXT DEFAULT NULL,
    `tenant_id`          INT UNSIGNED DEFAULT NULL,
    `created_at`         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Invitation Tokens
CREATE TABLE IF NOT EXISTS `invitation_tokens` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`   INT UNSIGNED NOT NULL,
    `email`       VARCHAR(255) NOT NULL,
    `name`        VARCHAR(255) DEFAULT NULL,
    `role_slug`   VARCHAR(100) NOT NULL DEFAULT 'airline_admin',
    `token`       VARCHAR(255) NOT NULL UNIQUE,
    `expires_at`  TIMESTAMP NOT NULL,
    `accepted_at` TIMESTAMP NULL DEFAULT NULL,
    `created_by`  INT UNSIGNED DEFAULT NULL,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Mobile Sync Meta
CREATE TABLE IF NOT EXISTS `mobile_sync_meta` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`         INT UNSIGNED NOT NULL,
    `module_code`       VARCHAR(100) NOT NULL,
    `last_published_at` TIMESTAMP NULL DEFAULT NULL,
    `version_hash`      VARCHAR(255) DEFAULT NULL,
    `updated_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uq_sync` (`tenant_id`, `module_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Fleets
CREATE TABLE IF NOT EXISTS `fleets` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`     INT UNSIGNED NOT NULL,
    `name`          VARCHAR(255) NOT NULL,
    `code`          VARCHAR(50) DEFAULT NULL,
    `aircraft_type` VARCHAR(100) DEFAULT NULL,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Qualifications
CREATE TABLE IF NOT EXISTS `qualifications` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`      INT UNSIGNED NOT NULL,
    `tenant_id`    INT UNSIGNED NOT NULL,
    `qual_type`    VARCHAR(100) NOT NULL,
    `qual_name`    VARCHAR(255) NOT NULL,
    `reference_no` VARCHAR(100) DEFAULT NULL,
    `authority`    VARCHAR(255) DEFAULT NULL,
    `issue_date`   DATE DEFAULT NULL,
    `expiry_date`  DATE DEFAULT NULL,
    `status`       VARCHAR(20) NOT NULL DEFAULT 'active',
    `notes`        TEXT DEFAULT NULL,
    `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    INDEX `idx_qual_user` (`user_id`),
    INDEX `idx_qual_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notice Categories
CREATE TABLE IF NOT EXISTS `notice_categories` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`  INT UNSIGNED NOT NULL,
    `name`       VARCHAR(255) NOT NULL,
    `slug`       VARCHAR(100) NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uq_notice_cat` (`tenant_id`, `slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notice Role Visibility
CREATE TABLE IF NOT EXISTS `notice_role_visibility` (
    `notice_id` INT UNSIGNED NOT NULL,
    `role_id`   INT UNSIGNED NOT NULL,
    PRIMARY KEY (`notice_id`, `role_id`),
    FOREIGN KEY (`notice_id`) REFERENCES `notices`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`role_id`)   REFERENCES `roles`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Roster Periods
CREATE TABLE IF NOT EXISTS `roster_periods` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`   INT UNSIGNED NOT NULL,
    `name`        VARCHAR(255) NOT NULL,
    `start_date`  DATE NOT NULL,
    `end_date`    DATE NOT NULL,
    `status`      VARCHAR(50) NOT NULL DEFAULT 'draft',
    `notes`       TEXT DEFAULT NULL,
    `created_by`  INT UNSIGNED DEFAULT NULL,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Roster Changes
CREATE TABLE IF NOT EXISTS `roster_changes` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`        INT UNSIGNED NOT NULL,
    `roster_period_id` INT UNSIGNED DEFAULT NULL,
    `roster_id`        INT UNSIGNED DEFAULT NULL,
    `user_id`          INT UNSIGNED NOT NULL,
    `requested_by`     INT UNSIGNED NOT NULL,
    `change_type`      VARCHAR(50) NOT NULL,
    `status`           VARCHAR(50) NOT NULL DEFAULT 'pending',
    `message`          TEXT NOT NULL,
    `response`         TEXT DEFAULT NULL,
    `responded_by`     INT UNSIGNED DEFAULT NULL,
    `responded_at`     TIMESTAMP NULL DEFAULT NULL,
    `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- PHASE 2: Platform Roles & Data Fix
-- -----------------------------------------------------

-- Ensure platform roles exist in the roles table (matching seeder)
INSERT IGNORE INTO `roles` (`name`, `slug`, `description`, `is_system`, `role_type`) VALUES
('Platform Super Admin',    'super_admin',         'Full platform and all airline access',               1, 'platform'),
('Platform Security Admin', 'platform_security',   'Platform security monitoring and audit access',      1, 'platform'),
('Platform Support Admin',  'platform_support',    'Read-only platform support access',                  1, 'platform'),
('System Monitoring',       'system_monitoring',   'System health and sync monitoring',                  1, 'platform');

-- Fix missing role assignments for identified users (Jordan Taylor = id 259 in production)
-- Platform Support Admin assignment
INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`)
SELECT 259, id FROM roles WHERE slug = 'platform_support' LIMIT 1;

-- If Alex Mwangi or other users were also identified as platform super admin (id 231 local, probably around 250+ prod)
-- Let's also search for Alex Mwangi by email to be safe
INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`)
SELECT id, (SELECT id FROM roles WHERE slug = 'super_admin' LIMIT 1) 
FROM users WHERE email = 'demo.superadmin@acentoza.com' LIMIT 1;

SET FOREIGN_KEY_CHECKS = 1;
