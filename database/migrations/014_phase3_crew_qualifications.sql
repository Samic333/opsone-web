-- Phase 3: Crew Qualifications & Profile Enhancements (MySQL)
-- Adds: qualifications table

CREATE TABLE IF NOT EXISTS `qualifications` (
    `id`            INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    `user_id`       INT UNSIGNED    NOT NULL,
    `tenant_id`     INT UNSIGNED    NOT NULL,
    `qual_type`     VARCHAR(100)    NOT NULL COMMENT 'e.g. Type Rating, Instructor Auth, Course, Endorsement',
    `qual_name`     VARCHAR(200)    NOT NULL COMMENT 'e.g. B737-800 Type Rating',
    `reference_no`  VARCHAR(100)    DEFAULT NULL,
    `authority`     VARCHAR(150)    DEFAULT NULL,
    `issue_date`    DATE            DEFAULT NULL,
    `expiry_date`   DATE            DEFAULT NULL,
    `status`        ENUM('active','expired','pending_renewal','suspended') NOT NULL DEFAULT 'active',
    `notes`         TEXT            DEFAULT NULL,
    `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    INDEX `idx_qualifications_user`   (`user_id`),
    INDEX `idx_qualifications_tenant` (`tenant_id`),
    INDEX `idx_qualifications_expiry` (`expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
