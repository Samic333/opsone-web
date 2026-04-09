-- Phase 4: Roster Foundation (SQLite dev)

CREATE TABLE IF NOT EXISTS rosters (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id   INTEGER NOT NULL,
    user_id     INTEGER NOT NULL,
    roster_date TEXT    NOT NULL,
    duty_type   TEXT    NOT NULL DEFAULT 'off',
    duty_code   TEXT,
    notes       TEXT,
    created_at  TEXT DEFAULT (datetime('now')),
    updated_at  TEXT DEFAULT (datetime('now')),
    UNIQUE (user_id, roster_date)
);

CREATE INDEX IF NOT EXISTS idx_rost_ten_date ON rosters (tenant_id, roster_date);
