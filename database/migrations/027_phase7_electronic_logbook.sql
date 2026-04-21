-- =============================================================================
-- Phase 7 (V2) — Electronic Pilot Logbook   (MySQL)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `flight_logs` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`        INT UNSIGNED NOT NULL,
    `user_id`          INT UNSIGNED NOT NULL,          -- pilot
    `flight_date`      DATE         NOT NULL,
    `aircraft_id`      INT UNSIGNED DEFAULT NULL,      -- FK to aircraft registry (optional)
    `aircraft_type`    VARCHAR(40)  DEFAULT NULL,
    `registration`     VARCHAR(20)  DEFAULT NULL,
    `flight_number`    VARCHAR(20)  DEFAULT NULL,
    `departure`        VARCHAR(10)  DEFAULT NULL,      -- ICAO/IATA
    `arrival`          VARCHAR(10)  DEFAULT NULL,
    `off_blocks`       TIME         DEFAULT NULL,
    `takeoff`          TIME         DEFAULT NULL,
    `landing`          TIME         DEFAULT NULL,
    `on_blocks`        TIME         DEFAULT NULL,
    `block_minutes`    INT UNSIGNED DEFAULT NULL,      -- on-blocks - off-blocks
    `air_minutes`      INT UNSIGNED DEFAULT NULL,      -- landing - takeoff
    `day_minutes`      INT UNSIGNED DEFAULT NULL,
    `night_minutes`    INT UNSIGNED DEFAULT NULL,
    `ifr_minutes`      INT UNSIGNED DEFAULT NULL,
    `pic_minutes`      INT UNSIGNED DEFAULT NULL,
    `sic_minutes`      INT UNSIGNED DEFAULT NULL,
    `landings_day`     INT UNSIGNED NOT NULL DEFAULT 0,
    `landings_night`   INT UNSIGNED NOT NULL DEFAULT 0,
    `rules`            ENUM('VFR','IFR','MIXED') NOT NULL DEFAULT 'IFR',
    `role`             ENUM('PIC','SIC','DUAL','INSTRUCTOR') NOT NULL DEFAULT 'PIC',
    `remarks`          TEXT         DEFAULT NULL,
    `created_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_flog_user_date` (`user_id`, `flight_date`),
    INDEX `idx_flog_tenant`    (`tenant_id`),
    CONSTRAINT `fk_flog_tenant`   FOREIGN KEY (`tenant_id`)   REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_flog_user`     FOREIGN KEY (`user_id`)     REFERENCES `users`(`id`)   ON DELETE CASCADE,
    CONSTRAINT `fk_flog_aircraft` FOREIGN KEY (`aircraft_id`) REFERENCES `aircraft`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
