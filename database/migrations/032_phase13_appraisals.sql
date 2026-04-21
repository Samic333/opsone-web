-- Phase 13 (V2) — Crew Appraisal / Performance  (MySQL)

CREATE TABLE IF NOT EXISTS `appraisals` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`      INT UNSIGNED NOT NULL,
    `subject_id`     INT UNSIGNED NOT NULL,   -- crew being appraised
    `appraiser_id`   INT UNSIGNED NOT NULL,   -- writer
    `rotation_ref`   VARCHAR(80)  DEFAULT NULL,  -- roster period / flight pair
    `period_from`    DATE         NOT NULL,
    `period_to`      DATE         NOT NULL,
    `status`         ENUM('draft','submitted','reviewed','accepted') NOT NULL DEFAULT 'draft',
    `rating_overall` TINYINT      DEFAULT NULL,   -- 1..5
    `strengths`      TEXT         DEFAULT NULL,
    `improvements`   TEXT         DEFAULT NULL,
    `comments`       TEXT         DEFAULT NULL,
    `confidential`   TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `submitted_at`   TIMESTAMP    NULL DEFAULT NULL,
    `reviewed_by`    INT UNSIGNED DEFAULT NULL,
    `reviewed_at`    TIMESTAMP    NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_app_subject`   (`subject_id`),
    INDEX `idx_app_appraiser` (`appraiser_id`),
    CONSTRAINT `fk_app_tenant`    FOREIGN KEY (`tenant_id`)    REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_app_subject`   FOREIGN KEY (`subject_id`)   REFERENCES `users`(`id`)   ON DELETE CASCADE,
    CONSTRAINT `fk_app_appraiser` FOREIGN KEY (`appraiser_id`) REFERENCES `users`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
