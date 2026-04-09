-- =====================================================
-- Migration 006: Crew Profiles & Licences (MySQL 8.0)
-- crew_profiles: extended per-user compliance record
-- licenses: multi-row licences/ratings per user
-- =====================================================

CREATE TABLE IF NOT EXISTS `crew_profiles` (
    `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`             INT UNSIGNED NOT NULL,
    `tenant_id`           INT UNSIGNED NOT NULL,
    -- Personal
    `date_of_birth`       DATE         DEFAULT NULL,
    `nationality`         VARCHAR(100) DEFAULT NULL,
    `phone`               VARCHAR(30)  DEFAULT NULL,
    -- Emergency contact
    `emergency_name`      VARCHAR(255) DEFAULT NULL,
    `emergency_phone`     VARCHAR(30)  DEFAULT NULL,
    `emergency_relation`  VARCHAR(100) DEFAULT NULL,
    -- Passport / travel doc
    `passport_number`     VARCHAR(50)  DEFAULT NULL,
    `passport_country`    VARCHAR(100) DEFAULT NULL,
    `passport_expiry`     DATE         DEFAULT NULL,
    -- Medical
    `medical_class`       VARCHAR(50)  DEFAULT NULL,
    `medical_expiry`      DATE         DEFAULT NULL,
    -- Contract
    `contract_type`       ENUM('permanent','fixed_term','probation','contractor') DEFAULT NULL,
    `contract_expiry`     DATE         DEFAULT NULL,
    -- Timestamps
    `created_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_crew_profile_user` (`user_id`),
    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tenant_id`)  REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    INDEX `idx_crew_profiles_tenant`           (`tenant_id`),
    INDEX `idx_crew_profiles_passport_expiry`  (`passport_expiry`),
    INDEX `idx_crew_profiles_medical_expiry`   (`medical_expiry`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `licenses` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`           INT UNSIGNED NOT NULL,
    `tenant_id`         INT UNSIGNED NOT NULL,
    `license_type`      VARCHAR(100) NOT NULL,
    `license_number`    VARCHAR(100) DEFAULT NULL,
    `issuing_authority` VARCHAR(100) DEFAULT NULL,
    `issue_date`        DATE         DEFAULT NULL,
    `expiry_date`       DATE         DEFAULT NULL,
    `notes`             TEXT         DEFAULT NULL,
    `created_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    INDEX `idx_licenses_tenant` (`tenant_id`),
    INDEX `idx_licenses_user`   (`user_id`),
    INDEX `idx_licenses_expiry` (`expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
