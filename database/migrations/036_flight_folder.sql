-- Phase 5 (V2) — Flight Folder  (MySQL)
--
-- Seven per-flight documents (journey log, risk assessment, crew briefing,
-- navlog, post-arrival, verification, after-mission) that crew fill on the
-- iPad/iPhone and managers review on the web.  One row per (flight, doc).
--
-- Design notes:
--   * Each table stores its form payload as JSON in `payload` so iteration on
--     field design doesn't require schema migrations.  A handful of columns
--     are promoted out of the JSON for fast querying (status, submitted_at).
--   * After-mission has an explicit `role_type` so pilot and cabin-crew
--     variants live in the same table.
--   * Review/return-for-info flow mirrors the safety_report_status_history
--     pattern.  See `flight_folder_status_history` at the bottom.

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1. Journey Log
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `flight_journey_logs` (
    `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`           INT UNSIGNED NOT NULL,
    `flight_id`           INT UNSIGNED NOT NULL,
    `submitted_by_user_id` INT UNSIGNED DEFAULT NULL,
    `reviewed_by_user_id`  INT UNSIGNED DEFAULT NULL,
    `status`              ENUM('draft','submitted','accepted','rejected','returned_for_info') NOT NULL DEFAULT 'draft',
    `off_blocks_utc`      DATETIME     DEFAULT NULL,
    `takeoff_utc`         DATETIME     DEFAULT NULL,
    `landing_utc`         DATETIME     DEFAULT NULL,
    `on_blocks_utc`       DATETIME     DEFAULT NULL,
    `fuel_uplift_kg`      DECIMAL(9,2) DEFAULT NULL,
    `fuel_remaining_kg`   DECIMAL(9,2) DEFAULT NULL,
    `pax_adult`           SMALLINT     DEFAULT NULL,
    `pax_child`           SMALLINT     DEFAULT NULL,
    `pax_infant`          SMALLINT     DEFAULT NULL,
    `defects`             TEXT         DEFAULT NULL,
    `remarks`             TEXT         DEFAULT NULL,
    `payload`             JSON         DEFAULT NULL,
    `created_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `submitted_at`        TIMESTAMP    NULL DEFAULT NULL,
    `reviewed_at`         TIMESTAMP    NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_journey_per_flight` (`flight_id`),
    INDEX `idx_jl_tenant_status` (`tenant_id`, `status`),
    CONSTRAINT `fk_jl_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_jl_flight` FOREIGN KEY (`flight_id`) REFERENCES `flights`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- 2. Flight Risk Assessment
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `flight_risk_assessments` (
    `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`           INT UNSIGNED NOT NULL,
    `flight_id`           INT UNSIGNED NOT NULL,
    `submitted_by_user_id` INT UNSIGNED DEFAULT NULL,
    `reviewed_by_user_id`  INT UNSIGNED DEFAULT NULL,
    `status`              ENUM('draft','submitted','accepted','rejected','returned_for_info') NOT NULL DEFAULT 'draft',
    `computed_score`      INT          DEFAULT NULL,
    `severity`            ENUM('green','amber','red') DEFAULT NULL,
    `answers`             JSON         DEFAULT NULL,
    `mitigations`         TEXT         DEFAULT NULL,
    `payload`             JSON         DEFAULT NULL,
    `created_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `submitted_at`        TIMESTAMP    NULL DEFAULT NULL,
    `reviewed_at`         TIMESTAMP    NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_risk_per_flight` (`flight_id`),
    INDEX `idx_risk_tenant_severity` (`tenant_id`, `severity`),
    CONSTRAINT `fk_risk_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_risk_flight` FOREIGN KEY (`flight_id`) REFERENCES `flights`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- 3. Crew Briefing Sheet
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `crew_briefing_sheets` (
    `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`           INT UNSIGNED NOT NULL,
    `flight_id`           INT UNSIGNED NOT NULL,
    `submitted_by_user_id` INT UNSIGNED DEFAULT NULL,
    `reviewed_by_user_id`  INT UNSIGNED DEFAULT NULL,
    `status`              ENUM('draft','submitted','accepted','rejected','returned_for_info') NOT NULL DEFAULT 'draft',
    `route_summary`       TEXT         DEFAULT NULL,
    `weather`             TEXT         DEFAULT NULL,
    `notams`              TEXT         DEFAULT NULL,
    `threats`             TEXT         DEFAULT NULL,
    `crew_acknowledgements` JSON       DEFAULT NULL,  -- [{user_id, role, ack_at}]
    `payload`             JSON         DEFAULT NULL,
    `created_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `submitted_at`        TIMESTAMP    NULL DEFAULT NULL,
    `reviewed_at`         TIMESTAMP    NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_briefing_per_flight` (`flight_id`),
    INDEX `idx_briefing_tenant_status` (`tenant_id`, `status`),
    CONSTRAINT `fk_briefing_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_briefing_flight` FOREIGN KEY (`flight_id`) REFERENCES `flights`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- 4. Navigation Log
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `flight_navlogs` (
    `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`           INT UNSIGNED NOT NULL,
    `flight_id`           INT UNSIGNED NOT NULL,
    `submitted_by_user_id` INT UNSIGNED DEFAULT NULL,
    `reviewed_by_user_id`  INT UNSIGNED DEFAULT NULL,
    `status`              ENUM('draft','submitted','accepted','rejected','returned_for_info') NOT NULL DEFAULT 'draft',
    `route_text`          TEXT         DEFAULT NULL,
    `planned_fuel_kg`     DECIMAL(9,2) DEFAULT NULL,
    `planned_time_min`    INT          DEFAULT NULL,
    `waypoints`           JSON         DEFAULT NULL,  -- [{ident, lat, lng, alt, eto, actual}]
    `alternates`          JSON         DEFAULT NULL,
    `payload`             JSON         DEFAULT NULL,
    `created_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `submitted_at`        TIMESTAMP    NULL DEFAULT NULL,
    `reviewed_at`         TIMESTAMP    NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_navlog_per_flight` (`flight_id`),
    INDEX `idx_navlog_tenant_status` (`tenant_id`, `status`),
    CONSTRAINT `fk_navlog_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_navlog_flight` FOREIGN KEY (`flight_id`) REFERENCES `flights`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- 5. Post-Arrival Report
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `post_arrival_reports` (
    `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`           INT UNSIGNED NOT NULL,
    `flight_id`           INT UNSIGNED NOT NULL,
    `submitted_by_user_id` INT UNSIGNED DEFAULT NULL,
    `reviewed_by_user_id`  INT UNSIGNED DEFAULT NULL,
    `status`              ENUM('draft','submitted','accepted','rejected','returned_for_info') NOT NULL DEFAULT 'draft',
    `on_block_time_utc`   DATETIME     DEFAULT NULL,
    `fuel_remaining_kg`   DECIMAL(9,2) DEFAULT NULL,
    `defects`             TEXT         DEFAULT NULL,
    `remarks`             TEXT         DEFAULT NULL,
    `payload`             JSON         DEFAULT NULL,
    `created_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `submitted_at`        TIMESTAMP    NULL DEFAULT NULL,
    `reviewed_at`         TIMESTAMP    NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_post_arrival_per_flight` (`flight_id`),
    INDEX `idx_par_tenant_status` (`tenant_id`, `status`),
    CONSTRAINT `fk_par_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_par_flight` FOREIGN KEY (`flight_id`) REFERENCES `flights`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- 6. Verification Form (preflight verification)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `flight_verification_forms` (
    `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`           INT UNSIGNED NOT NULL,
    `flight_id`           INT UNSIGNED NOT NULL,
    `submitted_by_user_id` INT UNSIGNED DEFAULT NULL,
    `reviewed_by_user_id`  INT UNSIGNED DEFAULT NULL,
    `status`              ENUM('draft','submitted','accepted','rejected','returned_for_info') NOT NULL DEFAULT 'draft',
    `items`               JSON         DEFAULT NULL,   -- [{key,label,state(ok/na/issue),note}]
    `signatures`          JSON         DEFAULT NULL,   -- [{role,user_id,name,signed_at}]
    `payload`             JSON         DEFAULT NULL,
    `created_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `submitted_at`        TIMESTAMP    NULL DEFAULT NULL,
    `reviewed_at`         TIMESTAMP    NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_verif_per_flight` (`flight_id`),
    INDEX `idx_verif_tenant_status` (`tenant_id`, `status`),
    CONSTRAINT `fk_verif_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_verif_flight` FOREIGN KEY (`flight_id`) REFERENCES `flights`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- 7. After-Mission Report (pilot or cabin_crew variant)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `after_mission_reports` (
    `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`           INT UNSIGNED NOT NULL,
    `flight_id`           INT UNSIGNED NOT NULL,
    `role_type`           ENUM('pilot','cabin_crew') NOT NULL,
    `submitted_by_user_id` INT UNSIGNED DEFAULT NULL,
    `reviewed_by_user_id`  INT UNSIGNED DEFAULT NULL,
    `status`              ENUM('draft','submitted','accepted','rejected','returned_for_info') NOT NULL DEFAULT 'draft',
    `template_version`    VARCHAR(40)  NOT NULL DEFAULT '1',
    `payload`             JSON         DEFAULT NULL,   -- full form body
    `narrative`           TEXT         DEFAULT NULL,
    `created_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `submitted_at`        TIMESTAMP    NULL DEFAULT NULL,
    `reviewed_at`         TIMESTAMP    NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_amr_per_flight_role` (`flight_id`, `role_type`),
    INDEX `idx_amr_tenant_status` (`tenant_id`, `status`),
    CONSTRAINT `fk_amr_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_amr_flight` FOREIGN KEY (`flight_id`) REFERENCES `flights`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- Shared status history (one row per state change, across all 7 doc types)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `flight_folder_status_history` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`    INT UNSIGNED NOT NULL,
    `flight_id`    INT UNSIGNED NOT NULL,
    `doc_type`     ENUM('journey_log','risk_assessment','crew_briefing','navlog','post_arrival','verification','after_mission_pilot','after_mission_cabin') NOT NULL,
    `doc_id`       INT UNSIGNED NOT NULL,           -- row id in the source table
    `old_status`   VARCHAR(40)  DEFAULT NULL,
    `new_status`   VARCHAR(40)  NOT NULL,
    `changed_by`   INT UNSIGNED DEFAULT NULL,
    `notes`        TEXT         DEFAULT NULL,
    `changed_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_ffsh_flight`    (`flight_id`),
    INDEX `idx_ffsh_doc`       (`doc_type`, `doc_id`),
    CONSTRAINT `fk_ffsh_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ffsh_flight` FOREIGN KEY (`flight_id`) REFERENCES `flights`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
