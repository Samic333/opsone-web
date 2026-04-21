-- Phase 11 (V2) — Per Diem Management  (MySQL)

CREATE TABLE IF NOT EXISTS `per_diem_rates` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`      INT UNSIGNED NOT NULL,
    `country`        VARCHAR(80)  NOT NULL,
    `station`        VARCHAR(80)  DEFAULT NULL,     -- NULL = country-wide default
    `currency`       VARCHAR(10)  NOT NULL DEFAULT 'USD',
    `daily_rate`     DECIMAL(10,2) NOT NULL,
    `effective_from` DATE         NOT NULL,
    `effective_to`   DATE         DEFAULT NULL,
    `notes`          VARCHAR(255) DEFAULT NULL,
    `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_pdr_country` (`country`),
    INDEX `idx_pdr_station` (`station`),
    CONSTRAINT `fk_pdr_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `per_diem_claims` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`       INT UNSIGNED NOT NULL,
    `user_id`         INT UNSIGNED NOT NULL,
    `period_from`     DATE         NOT NULL,
    `period_to`       DATE         NOT NULL,
    `station`         VARCHAR(80)  DEFAULT NULL,
    `country`         VARCHAR(80)  NOT NULL,
    `days`            DECIMAL(6,2) NOT NULL,
    `rate_id`         INT UNSIGNED DEFAULT NULL,
    `rate`            DECIMAL(10,2) NOT NULL,
    `currency`        VARCHAR(10)  NOT NULL,
    `amount`          DECIMAL(12,2) NOT NULL,
    `adjustment`      DECIMAL(12,2) NOT NULL DEFAULT 0,
    `status`          ENUM('draft','submitted','approved','rejected','paid') NOT NULL DEFAULT 'draft',
    `notes`           TEXT         DEFAULT NULL,
    `reviewed_by`     INT UNSIGNED DEFAULT NULL,
    `reviewed_at`     TIMESTAMP    NULL DEFAULT NULL,
    `paid_at`         TIMESTAMP    NULL DEFAULT NULL,
    `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_pdc_user`    (`user_id`),
    INDEX `idx_pdc_status`  (`status`),
    CONSTRAINT `fk_pdc_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pdc_user`   FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)   ON DELETE CASCADE,
    CONSTRAINT `fk_pdc_rate`   FOREIGN KEY (`rate_id`)   REFERENCES `per_diem_rates`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
