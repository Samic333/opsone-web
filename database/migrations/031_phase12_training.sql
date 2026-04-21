-- Phase 12 (V2) — Training Management  (MySQL)

CREATE TABLE IF NOT EXISTS `training_types` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`       INT UNSIGNED NOT NULL,
    `code`            VARCHAR(40)  NOT NULL,       -- e.g. recurrent_sim, crm, dangerous_goods
    `name`            VARCHAR(120) NOT NULL,
    `validity_months` SMALLINT DEFAULT NULL,       -- null = one-time
    `applicable_roles` VARCHAR(255) DEFAULT NULL,  -- comma-separated role slugs
    `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_tt_tenant_code` (`tenant_id`,`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `training_records` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`        INT UNSIGNED NOT NULL,
    `user_id`          INT UNSIGNED NOT NULL,
    `training_type_id` INT UNSIGNED DEFAULT NULL,
    `type_code`        VARCHAR(40)  DEFAULT NULL,
    `completed_date`   DATE         NOT NULL,
    `expires_date`     DATE         DEFAULT NULL,
    `provider`         VARCHAR(120) DEFAULT NULL,
    `result`           ENUM('pass','fail','in_progress','scheduled') NOT NULL DEFAULT 'pass',
    `certificate_path` VARCHAR(500) DEFAULT NULL,
    `notes`            TEXT         DEFAULT NULL,
    `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_trec_user`    (`user_id`),
    INDEX `idx_trec_expires` (`expires_date`),
    CONSTRAINT `fk_trec_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_trec_user`   FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)   ON DELETE CASCADE,
    CONSTRAINT `fk_trec_type`   FOREIGN KEY (`training_type_id`) REFERENCES `training_types`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
