-- =============================================================================
-- Phase 9 (V2) — Flight Assignment + Flight Bag    (MySQL)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `flights` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`        INT UNSIGNED NOT NULL,
    `flight_date`      DATE         NOT NULL,
    `flight_number`    VARCHAR(20)  NOT NULL,
    `departure`        VARCHAR(10)  DEFAULT NULL,
    `arrival`          VARCHAR(10)  DEFAULT NULL,
    `std`              TIME         DEFAULT NULL,    -- scheduled time of departure (dep local)
    `sta`              TIME         DEFAULT NULL,    -- scheduled time of arrival
    `aircraft_id`      INT UNSIGNED DEFAULT NULL,
    `captain_id`       INT UNSIGNED DEFAULT NULL,
    `fo_id`            INT UNSIGNED DEFAULT NULL,
    `status`           ENUM('draft','published','in_flight','completed','cancelled') NOT NULL DEFAULT 'draft',
    `notes`            TEXT         DEFAULT NULL,
    `created_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_flight_tenant_date_no` (`tenant_id`, `flight_date`, `flight_number`),
    INDEX `idx_flight_date`     (`flight_date`),
    INDEX `idx_flight_captain`  (`captain_id`),
    INDEX `idx_flight_fo`       (`fo_id`),
    INDEX `idx_flight_aircraft` (`aircraft_id`),
    CONSTRAINT `fk_flt_tenant`   FOREIGN KEY (`tenant_id`)   REFERENCES `tenants`(`id`)   ON DELETE CASCADE,
    CONSTRAINT `fk_flt_aircraft` FOREIGN KEY (`aircraft_id`) REFERENCES `aircraft`(`id`)  ON DELETE SET NULL,
    CONSTRAINT `fk_flt_captain`  FOREIGN KEY (`captain_id`)  REFERENCES `users`(`id`)     ON DELETE SET NULL,
    CONSTRAINT `fk_flt_fo`       FOREIGN KEY (`fo_id`)       REFERENCES `users`(`id`)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `flight_bag_files` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `flight_id`    INT UNSIGNED NOT NULL,
    `tenant_id`    INT UNSIGNED NOT NULL,
    `file_type`    VARCHAR(30)  NOT NULL,     -- nav_plan|notam|weather|wb|opt|company|other
    `title`        VARCHAR(200) NOT NULL,
    `file_path`    VARCHAR(500) NOT NULL,
    `file_name`    VARCHAR(200) NOT NULL,
    `file_size`    INT UNSIGNED NOT NULL DEFAULT 0,
    `uploaded_by`  INT UNSIGNED DEFAULT NULL,
    `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_fbf_flight` (`flight_id`),
    CONSTRAINT `fk_fbf_flight` FOREIGN KEY (`flight_id`) REFERENCES `flights`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
