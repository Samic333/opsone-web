-- =============================================================================
-- Phase 6 (V2) — Aircraft Registry, Documents, and Maintenance Tracking
-- MySQL variant
-- =============================================================================

CREATE TABLE IF NOT EXISTS `aircraft` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`        INT UNSIGNED NOT NULL,
    `fleet_id`         INT UNSIGNED DEFAULT NULL,
    `registration`     VARCHAR(20)  NOT NULL,        -- e.g. 5X-ACZ
    `aircraft_type`    VARCHAR(40)  NOT NULL,        -- e.g. DHC-8 Q400
    `variant`          VARCHAR(40)  DEFAULT NULL,    -- e.g. -Q400, -A321
    `manufacturer`     VARCHAR(80)  DEFAULT NULL,
    `msn`              VARCHAR(40)  DEFAULT NULL,    -- manufacturer serial number
    `year_built`       SMALLINT     DEFAULT NULL,
    `home_base_id`     INT UNSIGNED DEFAULT NULL,
    `status`           ENUM('active','maintenance','aog','stored','retired') NOT NULL DEFAULT 'active',
    `total_hours`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_cycles`     INT UNSIGNED NOT NULL DEFAULT 0,
    `notes`            TEXT DEFAULT NULL,
    `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_acft_tenant_reg` (`tenant_id`, `registration`),
    INDEX `idx_acft_fleet`   (`fleet_id`),
    INDEX `idx_acft_base`    (`home_base_id`),
    INDEX `idx_acft_status`  (`status`),
    CONSTRAINT `fk_acft_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_acft_fleet`  FOREIGN KEY (`fleet_id`)  REFERENCES `fleets`(`id`)  ON DELETE SET NULL,
    CONSTRAINT `fk_acft_base`   FOREIGN KEY (`home_base_id`) REFERENCES `bases`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Aircraft-level documents (airworthiness, insurance, registration cert, etc.)
CREATE TABLE IF NOT EXISTS `aircraft_documents` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `aircraft_id`    INT UNSIGNED NOT NULL,
    `tenant_id`      INT UNSIGNED NOT NULL,
    `doc_type`       VARCHAR(60)  NOT NULL,           -- airworthiness, insurance, registration, noise_cert
    `doc_number`     VARCHAR(80)  DEFAULT NULL,
    `issued_date`    DATE         DEFAULT NULL,
    `expiry_date`    DATE         DEFAULT NULL,
    `file_path`      VARCHAR(500) DEFAULT NULL,
    `notes`          VARCHAR(255) DEFAULT NULL,
    `uploaded_by`    INT UNSIGNED DEFAULT NULL,
    `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_acftdoc_acft`   (`aircraft_id`),
    INDEX `idx_acftdoc_expiry` (`expiry_date`),
    CONSTRAINT `fk_acftdoc_acft` FOREIGN KEY (`aircraft_id`) REFERENCES `aircraft`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Maintenance items (inspections, overhauls, expiry items)
CREATE TABLE IF NOT EXISTS `aircraft_maintenance` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `aircraft_id`     INT UNSIGNED NOT NULL,
    `tenant_id`       INT UNSIGNED NOT NULL,
    `item_type`       VARCHAR(60)  NOT NULL,        -- A_check, C_check, engine_oh, prop_oh, gear_insp
    `description`     VARCHAR(255) DEFAULT NULL,
    `due_date`        DATE         DEFAULT NULL,
    `due_hours`       DECIMAL(10,2) DEFAULT NULL,
    `due_cycles`      INT UNSIGNED DEFAULT NULL,
    `last_done_date`  DATE         DEFAULT NULL,
    `last_done_hours` DECIMAL(10,2) DEFAULT NULL,
    `interval_days`   INT UNSIGNED DEFAULT NULL,
    `interval_hours`  DECIMAL(10,2) DEFAULT NULL,
    `status`          ENUM('active','completed','deferred','waived') NOT NULL DEFAULT 'active',
    `notes`           TEXT DEFAULT NULL,
    `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_acftmx_acft`     (`aircraft_id`),
    INDEX `idx_acftmx_due_date` (`due_date`),
    INDEX `idx_acftmx_status`   (`status`),
    CONSTRAINT `fk_acftmx_acft` FOREIGN KEY (`aircraft_id`) REFERENCES `aircraft`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
