-- SQLite version of migration 018 — feature_flags + tenant_feature_flags + integration_configs

CREATE TABLE IF NOT EXISTS feature_flags (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    code                TEXT    NOT NULL UNIQUE,
    name                TEXT    NOT NULL,
    description         TEXT    DEFAULT NULL,
    is_global           INTEGER NOT NULL DEFAULT 0,
    enabled_by_default  INTEGER NOT NULL DEFAULT 0,
    category            TEXT    DEFAULT 'general',
    created_at          TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at          TEXT    NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_ff_code     ON feature_flags(code);
CREATE INDEX IF NOT EXISTS idx_ff_category ON feature_flags(category);

CREATE TABLE IF NOT EXISTS tenant_feature_flags (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id  INTEGER NOT NULL,
    flag_id    INTEGER NOT NULL,
    enabled    INTEGER NOT NULL DEFAULT 0,
    enabled_at TEXT    DEFAULT NULL,
    enabled_by INTEGER DEFAULT NULL,
    notes      TEXT    DEFAULT NULL,
    UNIQUE (tenant_id, flag_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (flag_id)   REFERENCES feature_flags(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS integration_configs (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id       INTEGER NOT NULL,
    service         TEXT    NOT NULL,
    status          TEXT    NOT NULL DEFAULT 'not_configured',
    config_json     TEXT    DEFAULT NULL,
    last_tested_at  TEXT    DEFAULT NULL,
    created_at      TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at      TEXT    NOT NULL DEFAULT (datetime('now')),
    UNIQUE (tenant_id, service),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_ic_tenant  ON integration_configs(tenant_id);
CREATE INDEX IF NOT EXISTS idx_ic_service ON integration_configs(service);

-- Seed feature flags
INSERT OR IGNORE INTO feature_flags (code, name, description, is_global, enabled_by_default, category) VALUES
    ('jeppesen_charts_beta',    'Jeppesen Charts (Beta)',      'Enable Jeppesen chart viewer for enrolled airlines in the beta programme.',          0, 0, 'integration'),
    ('opt_performance_preview', 'Performance & OPT (Preview)', 'Enable the performance calculation module for airlines on the early-access list.', 0, 0, 'integration'),
    ('advanced_weather_beta',   'Advanced Weather (Beta)',     'Enable third-party weather data feeds for beta airline testers.',                   0, 0, 'integration'),
    ('elogbook_preview',        'E-Logbook (Preview)',         'Enable the electronic logbook feature for airlines piloting the programme.',        0, 0, 'integration'),
    ('crew_api_v2',             'Crew API v2',                 'Use the v2 response envelope for all /api/* endpoints (richer capability payload).',0, 0, 'mobile'),
    ('enhanced_sync',           'Enhanced Background Sync',    'Allow iPad clients to sync content in a tighter cadence (every 15 min vs hourly).', 0, 0, 'mobile'),
    ('safety_ai_assist',        'Safety AI Assist',            'Experimental AI-assisted safety report classification and severity scoring.',       0, 0, 'platform'),
    ('roster_auto_suggest',     'Roster Auto-Suggest',         'Enable AI-powered roster gap-filling suggestions in the scheduler workbench.',      0, 0, 'ops');
