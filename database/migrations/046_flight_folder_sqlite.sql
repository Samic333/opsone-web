-- Migration 046 (SQLite) — Flight Folder tables.
--
-- Mirror of `036_flight_folder.sql` (MySQL). Migration 036 shipped without a
-- SQLite parallel, so any local dev or test using SQLite has every Flight
-- Folder API path crashing on missing-table errors. This migration creates
-- all 8 tables with SQLite-equivalent types:
--   ENUM(...)        → TEXT with CHECK constraint
--   JSON             → TEXT (use json1 ext functions if needed)
--   DATETIME         → TEXT
--   DECIMAL(9,2)     → REAL
--   INT UNSIGNED     → INTEGER
--   TIMESTAMP DEFAULT CURRENT_TIMESTAMP / ON UPDATE
--                    → TEXT NOT NULL DEFAULT (datetime('now'))
-- Idempotent — every CREATE uses IF NOT EXISTS.

BEGIN TRANSACTION;

-- 1. Journey Log
CREATE TABLE IF NOT EXISTS flight_journey_logs (
    id                    INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id             INTEGER NOT NULL,
    flight_id             INTEGER NOT NULL,
    submitted_by_user_id  INTEGER DEFAULT NULL,
    reviewed_by_user_id   INTEGER DEFAULT NULL,
    status                TEXT    NOT NULL DEFAULT 'draft'
        CHECK(status IN ('draft','submitted','accepted','rejected','returned_for_info')),
    off_blocks_utc        TEXT    DEFAULT NULL,
    takeoff_utc           TEXT    DEFAULT NULL,
    landing_utc           TEXT    DEFAULT NULL,
    on_blocks_utc         TEXT    DEFAULT NULL,
    fuel_uplift_kg        REAL    DEFAULT NULL,
    fuel_remaining_kg     REAL    DEFAULT NULL,
    pax_adult             INTEGER DEFAULT NULL,
    pax_child             INTEGER DEFAULT NULL,
    pax_infant            INTEGER DEFAULT NULL,
    defects               TEXT    DEFAULT NULL,
    remarks               TEXT    DEFAULT NULL,
    payload               TEXT    DEFAULT NULL,
    created_at            TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at            TEXT    NOT NULL DEFAULT (datetime('now')),
    submitted_at          TEXT    DEFAULT NULL,
    reviewed_at           TEXT    DEFAULT NULL,
    UNIQUE (flight_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (flight_id) REFERENCES flights(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_jl_tenant_status ON flight_journey_logs(tenant_id, status);

-- 2. Flight Risk Assessment
CREATE TABLE IF NOT EXISTS flight_risk_assessments (
    id                    INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id             INTEGER NOT NULL,
    flight_id             INTEGER NOT NULL,
    submitted_by_user_id  INTEGER DEFAULT NULL,
    reviewed_by_user_id   INTEGER DEFAULT NULL,
    status                TEXT    NOT NULL DEFAULT 'draft'
        CHECK(status IN ('draft','submitted','accepted','rejected','returned_for_info')),
    computed_score        INTEGER DEFAULT NULL,
    severity              TEXT    DEFAULT NULL CHECK(severity IS NULL OR severity IN ('green','amber','red')),
    answers               TEXT    DEFAULT NULL,
    mitigations           TEXT    DEFAULT NULL,
    payload               TEXT    DEFAULT NULL,
    created_at            TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at            TEXT    NOT NULL DEFAULT (datetime('now')),
    submitted_at          TEXT    DEFAULT NULL,
    reviewed_at           TEXT    DEFAULT NULL,
    UNIQUE (flight_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (flight_id) REFERENCES flights(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_risk_tenant_severity ON flight_risk_assessments(tenant_id, severity);

-- 3. Crew Briefing Sheet
CREATE TABLE IF NOT EXISTS crew_briefing_sheets (
    id                    INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id             INTEGER NOT NULL,
    flight_id             INTEGER NOT NULL,
    submitted_by_user_id  INTEGER DEFAULT NULL,
    reviewed_by_user_id   INTEGER DEFAULT NULL,
    status                TEXT    NOT NULL DEFAULT 'draft'
        CHECK(status IN ('draft','submitted','accepted','rejected','returned_for_info')),
    route_summary         TEXT    DEFAULT NULL,
    weather               TEXT    DEFAULT NULL,
    notams                TEXT    DEFAULT NULL,
    threats               TEXT    DEFAULT NULL,
    crew_acknowledgements TEXT    DEFAULT NULL,
    payload               TEXT    DEFAULT NULL,
    created_at            TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at            TEXT    NOT NULL DEFAULT (datetime('now')),
    submitted_at          TEXT    DEFAULT NULL,
    reviewed_at           TEXT    DEFAULT NULL,
    UNIQUE (flight_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (flight_id) REFERENCES flights(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_briefing_tenant_status ON crew_briefing_sheets(tenant_id, status);

-- 4. Navigation Log
CREATE TABLE IF NOT EXISTS flight_navlogs (
    id                    INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id             INTEGER NOT NULL,
    flight_id             INTEGER NOT NULL,
    submitted_by_user_id  INTEGER DEFAULT NULL,
    reviewed_by_user_id   INTEGER DEFAULT NULL,
    status                TEXT    NOT NULL DEFAULT 'draft'
        CHECK(status IN ('draft','submitted','accepted','rejected','returned_for_info')),
    route_text            TEXT    DEFAULT NULL,
    planned_fuel_kg       REAL    DEFAULT NULL,
    planned_time_min      INTEGER DEFAULT NULL,
    waypoints             TEXT    DEFAULT NULL,
    alternates            TEXT    DEFAULT NULL,
    payload               TEXT    DEFAULT NULL,
    created_at            TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at            TEXT    NOT NULL DEFAULT (datetime('now')),
    submitted_at          TEXT    DEFAULT NULL,
    reviewed_at           TEXT    DEFAULT NULL,
    UNIQUE (flight_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (flight_id) REFERENCES flights(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_navlog_tenant_status ON flight_navlogs(tenant_id, status);

-- 5. Post-Arrival Report
CREATE TABLE IF NOT EXISTS post_arrival_reports (
    id                    INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id             INTEGER NOT NULL,
    flight_id             INTEGER NOT NULL,
    submitted_by_user_id  INTEGER DEFAULT NULL,
    reviewed_by_user_id   INTEGER DEFAULT NULL,
    status                TEXT    NOT NULL DEFAULT 'draft'
        CHECK(status IN ('draft','submitted','accepted','rejected','returned_for_info')),
    on_block_time_utc     TEXT    DEFAULT NULL,
    fuel_remaining_kg     REAL    DEFAULT NULL,
    defects               TEXT    DEFAULT NULL,
    remarks               TEXT    DEFAULT NULL,
    payload               TEXT    DEFAULT NULL,
    created_at            TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at            TEXT    NOT NULL DEFAULT (datetime('now')),
    submitted_at          TEXT    DEFAULT NULL,
    reviewed_at           TEXT    DEFAULT NULL,
    UNIQUE (flight_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (flight_id) REFERENCES flights(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_par_tenant_status ON post_arrival_reports(tenant_id, status);

-- 6. Verification Form
CREATE TABLE IF NOT EXISTS flight_verification_forms (
    id                    INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id             INTEGER NOT NULL,
    flight_id             INTEGER NOT NULL,
    submitted_by_user_id  INTEGER DEFAULT NULL,
    reviewed_by_user_id   INTEGER DEFAULT NULL,
    status                TEXT    NOT NULL DEFAULT 'draft'
        CHECK(status IN ('draft','submitted','accepted','rejected','returned_for_info')),
    items                 TEXT    DEFAULT NULL,
    signatures            TEXT    DEFAULT NULL,
    payload               TEXT    DEFAULT NULL,
    created_at            TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at            TEXT    NOT NULL DEFAULT (datetime('now')),
    submitted_at          TEXT    DEFAULT NULL,
    reviewed_at           TEXT    DEFAULT NULL,
    UNIQUE (flight_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (flight_id) REFERENCES flights(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_verif_tenant_status ON flight_verification_forms(tenant_id, status);

-- 7. After-Mission Report (pilot OR cabin_crew variant per flight)
CREATE TABLE IF NOT EXISTS after_mission_reports (
    id                    INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id             INTEGER NOT NULL,
    flight_id             INTEGER NOT NULL,
    role_type             TEXT    NOT NULL CHECK(role_type IN ('pilot','cabin_crew')),
    submitted_by_user_id  INTEGER DEFAULT NULL,
    reviewed_by_user_id   INTEGER DEFAULT NULL,
    status                TEXT    NOT NULL DEFAULT 'draft'
        CHECK(status IN ('draft','submitted','accepted','rejected','returned_for_info')),
    template_version      TEXT    NOT NULL DEFAULT '1',
    payload               TEXT    DEFAULT NULL,
    narrative             TEXT    DEFAULT NULL,
    created_at            TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at            TEXT    NOT NULL DEFAULT (datetime('now')),
    submitted_at          TEXT    DEFAULT NULL,
    reviewed_at           TEXT    DEFAULT NULL,
    UNIQUE (flight_id, role_type),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (flight_id) REFERENCES flights(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_amr_tenant_status ON after_mission_reports(tenant_id, status);

-- 8. Shared status history (one row per state change across all 7 doc types)
CREATE TABLE IF NOT EXISTS flight_folder_status_history (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id   INTEGER NOT NULL,
    flight_id   INTEGER NOT NULL,
    doc_type    TEXT    NOT NULL CHECK(doc_type IN
        ('journey_log','risk_assessment','crew_briefing','navlog',
         'post_arrival','verification','after_mission_pilot','after_mission_cabin')),
    doc_id      INTEGER NOT NULL,
    old_status  TEXT    DEFAULT NULL,
    new_status  TEXT    NOT NULL,
    changed_by  INTEGER DEFAULT NULL,
    notes       TEXT    DEFAULT NULL,
    changed_at  TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (flight_id) REFERENCES flights(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_ffsh_flight ON flight_folder_status_history(flight_id);
CREATE INDEX IF NOT EXISTS idx_ffsh_doc    ON flight_folder_status_history(doc_type, doc_id);

COMMIT;
