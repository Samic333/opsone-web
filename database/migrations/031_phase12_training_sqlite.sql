-- Phase 12 (V2) — Training Management  (SQLite)

CREATE TABLE IF NOT EXISTS training_types (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id        INTEGER NOT NULL,
    code             TEXT    NOT NULL,
    name             TEXT    NOT NULL,
    validity_months  INTEGER DEFAULT NULL,
    applicable_roles TEXT    DEFAULT NULL,
    created_at       TEXT    NOT NULL DEFAULT (datetime('now')),
    UNIQUE (tenant_id, code),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS training_records (
    id                INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id         INTEGER NOT NULL,
    user_id           INTEGER NOT NULL,
    training_type_id  INTEGER DEFAULT NULL,
    type_code         TEXT    DEFAULT NULL,
    completed_date    TEXT    NOT NULL,
    expires_date      TEXT    DEFAULT NULL,
    provider          TEXT    DEFAULT NULL,
    result            TEXT    NOT NULL DEFAULT 'pass',
    certificate_path  TEXT    DEFAULT NULL,
    notes             TEXT    DEFAULT NULL,
    created_at        TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at        TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (tenant_id)        REFERENCES tenants(id)        ON DELETE CASCADE,
    FOREIGN KEY (user_id)          REFERENCES users(id)          ON DELETE CASCADE,
    FOREIGN KEY (training_type_id) REFERENCES training_types(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_trec_user    ON training_records(user_id);
CREATE INDEX IF NOT EXISTS idx_trec_expires ON training_records(expires_date);
