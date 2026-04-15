-- Phase 5: Rostering Foundation
-- SQLite version
-- Run after 015_phase4_notices_files_sqlite.sql

-- ─── Roster Periods ──────────────────────────────────────────────────────────
-- A period is a named scheduling cycle (e.g. "April 2026", "W16-W19")
-- Scheduler builds the roster in 'draft', publishes when ready.
-- Crew only see published/frozen periods via the API.

CREATE TABLE IF NOT EXISTS roster_periods (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id   INTEGER NOT NULL,
    name        TEXT    NOT NULL,                          -- e.g. "April 2026"
    start_date  TEXT    NOT NULL,                          -- YYYY-MM-DD
    end_date    TEXT    NOT NULL,                          -- YYYY-MM-DD
    status      TEXT    NOT NULL DEFAULT 'draft',          -- draft | published | frozen | archived
    notes       TEXT    DEFAULT NULL,
    created_by  INTEGER DEFAULT NULL,
    created_at  TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_roster_periods_tenant ON roster_periods (tenant_id, start_date);

-- ─── Extend rosters table ──────────────────────────────────────────────────
-- Safely add new columns (SQLite: one ALTER per column)

ALTER TABLE rosters ADD COLUMN roster_period_id INTEGER DEFAULT NULL;
ALTER TABLE rosters ADD COLUMN base_id          INTEGER DEFAULT NULL;
ALTER TABLE rosters ADD COLUMN fleet_id         INTEGER DEFAULT NULL;
ALTER TABLE rosters ADD COLUMN reserve_type     TEXT    DEFAULT NULL; -- immediate | short_notice | extended

-- ─── Roster Change Requests / Comments ────────────────────────────────────
-- Tracks change requests and comment threads on roster entries or periods.
-- Supports the chief pilot / manager review workflow.

CREATE TABLE IF NOT EXISTS roster_changes (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id        INTEGER NOT NULL,
    roster_period_id INTEGER DEFAULT NULL,    -- period-level comment
    roster_id        INTEGER DEFAULT NULL,    -- entry-level comment (specific day)
    user_id          INTEGER NOT NULL,        -- crew member making the request
    requested_by     INTEGER NOT NULL,        -- user submitting this record (may differ for manager comments)
    change_type      TEXT    NOT NULL,        -- comment | leave_request | swap_request | correction
    status           TEXT    NOT NULL DEFAULT 'pending',  -- pending | approved | rejected | noted
    message          TEXT    NOT NULL,
    response         TEXT    DEFAULT NULL,    -- manager/scheduler response
    responded_by     INTEGER DEFAULT NULL,
    responded_at     TEXT    DEFAULT NULL,
    created_at       TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at       TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_roster_changes_period   ON roster_changes (roster_period_id);
CREATE INDEX IF NOT EXISTS idx_roster_changes_entry    ON roster_changes (roster_id);
CREATE INDEX IF NOT EXISTS idx_roster_changes_user     ON roster_changes (user_id, tenant_id);
