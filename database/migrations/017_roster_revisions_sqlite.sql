-- Phase 5 Extension: Roster Revisions (SQLite version — local dev only)
-- SQLite does not support ADD COLUMN IF NOT EXISTS or MODIFY COLUMN
-- Run this only if the columns/tables do not already exist

-- ─── roster_revisions ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS roster_revisions (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id        INTEGER NOT NULL,
    roster_period_id INTEGER DEFAULT NULL,
    revision_ref     TEXT NOT NULL,
    reason           TEXT NOT NULL,
    change_source    TEXT NOT NULL DEFAULT 'scheduler',
    status           TEXT NOT NULL DEFAULT 'draft',
    requested_by     INTEGER DEFAULT NULL,
    approved_by      INTEGER DEFAULT NULL,
    approved_at      TEXT DEFAULT NULL,
    issued_at        TEXT DEFAULT NULL,
    notes            TEXT DEFAULT NULL,
    created_at       TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at       TEXT NOT NULL DEFAULT (datetime('now'))
);

-- ─── roster_revision_items ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS roster_revision_items (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    roster_revision_id  INTEGER NOT NULL,
    tenant_id           INTEGER NOT NULL,
    user_id             INTEGER NOT NULL,
    roster_date         TEXT NOT NULL,
    old_duty_type       TEXT DEFAULT NULL,
    old_duty_code       TEXT DEFAULT NULL,
    new_duty_type       TEXT DEFAULT NULL,
    new_duty_code       TEXT DEFAULT NULL,
    change_note         TEXT DEFAULT NULL,
    acknowledged_at     TEXT DEFAULT NULL,
    created_at          TEXT NOT NULL DEFAULT (datetime('now'))
);

-- Note: SQLite ALTER TABLE only supports ADD COLUMN.
-- Run each separately if columns do not exist:
-- ALTER TABLE roster_periods ADD COLUMN crew_group TEXT DEFAULT NULL;
-- ALTER TABLE roster_periods ADD COLUMN fleet_id INTEGER DEFAULT NULL;
-- ALTER TABLE roster_periods ADD COLUMN published_by INTEGER DEFAULT NULL;
-- ALTER TABLE roster_periods ADD COLUMN published_at TEXT DEFAULT NULL;
-- ALTER TABLE roster_periods ADD COLUMN frozen_at TEXT DEFAULT NULL;
-- ALTER TABLE roster_periods ADD COLUMN revision_number INTEGER NOT NULL DEFAULT 0;
-- ALTER TABLE rosters ADD COLUMN acknowledged_at TEXT DEFAULT NULL;
-- ALTER TABLE rosters ADD COLUMN revision_id INTEGER DEFAULT NULL;
-- ALTER TABLE rosters ADD COLUMN is_revision INTEGER NOT NULL DEFAULT 0;
