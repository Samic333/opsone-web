-- ─────────────────────────────────────────────────────────────────────────────
-- Migration 018 — Phase 10: Future Integration Readiness
--
-- 1. Extend modules.platform_status to include 'coming_soon'
-- 2. Add feature_flags table for controlled per-tenant rollouts
-- 3. Add integration_configs table for future external service hooks
-- 4. Seed future/coming-soon modules (Jeppesen, OPT, EFB, Weather, ATC)
-- ─────────────────────────────────────────────────────────────────────────────

-- 1. Extend platform_status ENUM
ALTER TABLE `modules`
    MODIFY COLUMN `platform_status`
        ENUM('available','beta','coming_soon','disabled') NOT NULL DEFAULT 'available';

-- 2. Feature flags — per-tenant opt-in/out of experimental or phased features
CREATE TABLE IF NOT EXISTS `feature_flags` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code`        VARCHAR(80)  NOT NULL UNIQUE  COMMENT 'Machine-readable slug, e.g. jeppesen_charts',
    `name`        VARCHAR(120) NOT NULL         COMMENT 'Human-readable label',
    `description` TEXT         DEFAULT NULL,
    `is_global`   TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1 = applied to all tenants regardless of tenant_flags',
    `enabled_by_default` TINYINT(1) NOT NULL DEFAULT 0,
    `category`    VARCHAR(60)  DEFAULT 'general' COMMENT 'integration | mobile | ops | platform',
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_ff_code` (`code`),
    INDEX `idx_ff_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Per-tenant feature flag overrides
CREATE TABLE IF NOT EXISTS `tenant_feature_flags` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`  INT UNSIGNED NOT NULL,
    `flag_id`    INT UNSIGNED NOT NULL,
    `enabled`    TINYINT(1)   NOT NULL DEFAULT 0,
    `enabled_at` TIMESTAMP    NULL DEFAULT NULL,
    `enabled_by` INT UNSIGNED DEFAULT NULL COMMENT 'platform staff user id',
    `notes`      TEXT DEFAULT NULL,
    UNIQUE KEY `uq_tenant_flag` (`tenant_id`, `flag_id`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`flag_id`)   REFERENCES `feature_flags`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Integration configs — external service hook registry
--    Each row is a named integration slot. Config (API keys, endpoints) is
--    stored as JSON so we never need schema changes to add new fields.
CREATE TABLE IF NOT EXISTS `integration_configs` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`   INT UNSIGNED NOT NULL,
    `service`     VARCHAR(80)  NOT NULL COMMENT 'jeppesen | opt_performance | efb | weather_api | atc_comms',
    `status`      ENUM('not_configured','pending','active','disabled') NOT NULL DEFAULT 'not_configured',
    `config_json` JSON         DEFAULT NULL COMMENT 'Service-specific config (endpoint, api_key_hash, etc.)',
    `last_tested_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_tenant_service` (`tenant_id`, `service`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    INDEX `idx_ic_tenant` (`tenant_id`),
    INDEX `idx_ic_service` (`service`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Seed future/coming-soon modules
INSERT IGNORE INTO `modules`
    (code, name, description, icon, mobile_capable, sort_order, platform_status)
VALUES
    ('jeppesen_charts',   'Jeppesen Charts',          'Integrated Jeppesen aviation chart viewer with approach plates, SID/STAR, and airport diagrams. Requires active Jeppesen subscription.',   '🗺️',  1, 200, 'coming_soon'),
    ('opt_performance',   'Performance & OPT',        'Aircraft performance calculations, take-off/landing data, weight & balance, and operational performance tool (OPT) integration.',           '⚡',  1, 210, 'coming_soon'),
    ('advanced_weather',  'Advanced Weather',         'Integrated meteorological data — METARs, TAFs, SIGMETs, pilot weather reports, and route weather briefing from third-party providers.',    '🌤️', 1, 220, 'coming_soon'),
    ('atc_comms',         'ATC Communications Log',  'Crew-side ATC communication logging, frequency management, and voice data integration hook for compliant comms archiving.',                 '📡',  1, 230, 'coming_soon'),
    ('elogbook',          'Electronic Logbook',       'Automated crew logbook populated from roster, FDM, and flight data. Exports to EASA/ICAO format. Requires rostering and FDM modules.',    '📒',  1, 240, 'coming_soon'),
    ('ground_ops',        'Ground Operations',        'Turnaround coordination, ground crew tasking, load control, and fuelling integration for ramp operations management.',                      '🛞',  0, 250, 'coming_soon');

-- 5. Seed feature flags
INSERT IGNORE INTO `feature_flags` (code, name, description, is_global, enabled_by_default, category) VALUES
    ('jeppesen_charts_beta',    'Jeppesen Charts (Beta)',       'Enable Jeppesen chart viewer for enrolled airlines in the beta programme.',           0, 0, 'integration'),
    ('opt_performance_preview', 'Performance & OPT (Preview)', 'Enable the performance calculation module for airlines on the early-access list.',    0, 0, 'integration'),
    ('advanced_weather_beta',   'Advanced Weather (Beta)',      'Enable third-party weather data feeds for beta airline testers.',                     0, 0, 'integration'),
    ('elogbook_preview',        'E-Logbook (Preview)',          'Enable the electronic logbook feature for airlines piloting the programme.',          0, 0, 'integration'),
    ('crew_api_v2',             'Crew API v2',                  'Use the v2 response envelope for all /api/* endpoints (richer capability payload).', 0, 0, 'mobile'),
    ('enhanced_sync',           'Enhanced Background Sync',     'Allow iPad clients to sync content in a tighter cadence (every 15 min vs hourly).', 0, 0, 'mobile'),
    ('safety_ai_assist',        'Safety AI Assist',             'Experimental AI-assisted safety report classification and severity scoring.',         0, 0, 'platform'),
    ('roster_auto_suggest',     'Roster Auto-Suggest',          'Enable AI-powered roster gap-filling suggestions in the scheduler workbench.',       0, 0, 'ops');
