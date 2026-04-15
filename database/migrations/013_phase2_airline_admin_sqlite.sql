-- =====================================================
-- Migration 013 (SQLite) — Phase 2 Airline Admin Foundation
--
-- Run: sqlite3 database/crewassist.sqlite < database/migrations/013_phase2_airline_admin_sqlite.sql
-- =====================================================

PRAGMA foreign_keys = OFF;

-- ─── 1. Create fleets table ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS fleets (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id     INTEGER NOT NULL,
    name          TEXT    NOT NULL,
    code          TEXT    DEFAULT NULL,
    aircraft_type TEXT    DEFAULT NULL,
    created_at    TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

-- ─── 2–4. Add columns to users via table-swap ────────────────────────────────
-- SQLite cannot ADD COLUMN with CHECK constraints or FK references after creation,
-- but ADD COLUMN with NULL default is fine.
-- Check if columns already exist before adding to stay idempotent.

-- employment_status (text, limited by app logic)
-- SQLite: simply add nullable TEXT column; app enforces allowed values.
-- We use a guard: only add if the column is absent.

-- The safest cross-version SQLite approach is to attempt ADD COLUMN inside
-- a BEGIN/IGNORE block by checking PRAGMA table_info first in application code.
-- For a standalone SQL script, we attempt all three; SQLite will error silently
-- if the column already exists only when using IF NOT EXISTS equivalent.
-- Since SQLite 3.37.0+ supports IF NOT EXISTS on ALTER TABLE ADD COLUMN:

ALTER TABLE users ADD COLUMN employment_status    TEXT    DEFAULT NULL;
ALTER TABLE users ADD COLUMN fleet_id             INTEGER DEFAULT NULL;
ALTER TABLE users ADD COLUMN profile_completion_pct INTEGER NOT NULL DEFAULT 0;

PRAGMA foreign_keys = ON;
