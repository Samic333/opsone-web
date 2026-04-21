-- Phase 16 (V2) — Integrations registry  (SQLite)

CREATE TABLE IF NOT EXISTS integrations (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id     INTEGER NOT NULL,
    provider      TEXT    NOT NULL,
    display_name  TEXT    NOT NULL,
    status        TEXT    NOT NULL DEFAULT 'disabled',  -- disabled|pending|live|error
    config_json   TEXT    DEFAULT NULL,
    last_sync_at  TEXT    DEFAULT NULL,
    last_error    TEXT    DEFAULT NULL,
    created_at    TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at    TEXT    NOT NULL DEFAULT (datetime('now')),
    UNIQUE (tenant_id, provider),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
