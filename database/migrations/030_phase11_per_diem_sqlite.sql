-- Phase 11 (V2) — Per Diem  (SQLite)

CREATE TABLE IF NOT EXISTS per_diem_rates (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id      INTEGER NOT NULL,
    country        TEXT    NOT NULL,
    station        TEXT    DEFAULT NULL,
    currency       TEXT    NOT NULL DEFAULT 'USD',
    daily_rate     REAL    NOT NULL,
    effective_from TEXT    NOT NULL,
    effective_to   TEXT    DEFAULT NULL,
    notes          TEXT    DEFAULT NULL,
    created_at     TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS per_diem_claims (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id      INTEGER NOT NULL,
    user_id        INTEGER NOT NULL,
    period_from    TEXT    NOT NULL,
    period_to      TEXT    NOT NULL,
    station        TEXT    DEFAULT NULL,
    country        TEXT    NOT NULL,
    days           REAL    NOT NULL,
    rate_id        INTEGER DEFAULT NULL,
    rate           REAL    NOT NULL,
    currency       TEXT    NOT NULL,
    amount         REAL    NOT NULL,
    adjustment     REAL    NOT NULL DEFAULT 0,
    status         TEXT    NOT NULL DEFAULT 'draft',   -- draft|submitted|approved|rejected|paid
    notes          TEXT    DEFAULT NULL,
    reviewed_by    INTEGER DEFAULT NULL,
    reviewed_at    TEXT    DEFAULT NULL,
    paid_at        TEXT    DEFAULT NULL,
    created_at     TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at     TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (rate_id)   REFERENCES per_diem_rates(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_pdc_user   ON per_diem_claims(user_id);
CREATE INDEX IF NOT EXISTS idx_pdc_status ON per_diem_claims(status);
