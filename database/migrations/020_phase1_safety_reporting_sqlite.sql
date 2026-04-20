-- =============================================================================
-- Migration 020: Phase 1 — Safety Reporting (SQLite-compatible)
-- =============================================================================
-- Equivalent of 020_phase1_safety_reporting.sql for SQLite environments
-- (local dev / testing).
--
-- Key differences from MySQL version:
--   • No ENUM   → VARCHAR with a CHECK constraint where critical
--   • No JSON   → TEXT
--   • No UNSIGNED / TINYINT(n) → INTEGER
--   • No FK enforcement (SQLite FKs are syntactically accepted but not enforced
--     by default; enabling PRAGMA foreign_keys is optional)
--   • No stored procedures — ADD COLUMN statements are wrapped in separate
--     IF NOT EXISTS checks via SQLite's supported syntax (SQLite ≥ 3.37 supports
--     ALTER TABLE ADD COLUMN without conditional; duplicates fail silently when
--     each statement is run in isolation; use try/catch in migration runner)
--   • ON UPDATE CURRENT_TIMESTAMP unsupported — omitted on updated_at cols
--   • AUTO_INCREMENT → INTEGER PRIMARY KEY AUTOINCREMENT
-- =============================================================================

-- =============================================================================
-- SECTION A: ALTER TABLE safety_reports — Phase 1 columns
-- SQLite ignores ADD COLUMN if the column already exists when using
-- "OR IGNORE" via the migration runner. Each statement is safe to run twice.
-- =============================================================================

ALTER TABLE safety_reports ADD COLUMN is_draft             INTEGER NOT NULL DEFAULT 0;
ALTER TABLE safety_reports ADD COLUMN event_utc_time       TEXT NULL;
ALTER TABLE safety_reports ADD COLUMN event_local_time     TEXT NULL;
ALTER TABLE safety_reports ADD COLUMN location_name        TEXT NULL;
ALTER TABLE safety_reports ADD COLUMN icao_code            TEXT NULL;
ALTER TABLE safety_reports ADD COLUMN occurrence_type      TEXT NOT NULL DEFAULT 'occurrence';
ALTER TABLE safety_reports ADD COLUMN event_type           TEXT NULL;
ALTER TABLE safety_reports ADD COLUMN initial_risk_score   INTEGER NULL;
ALTER TABLE safety_reports ADD COLUMN final_severity       TEXT NULL;
ALTER TABLE safety_reports ADD COLUMN aircraft_registration TEXT NULL;
ALTER TABLE safety_reports ADD COLUMN call_sign            TEXT NULL;
ALTER TABLE safety_reports ADD COLUMN extra_fields         TEXT NULL;
ALTER TABLE safety_reports ADD COLUMN submitted_at         TEXT NULL;
ALTER TABLE safety_reports ADD COLUMN closed_at            TEXT NULL;
ALTER TABLE safety_reports ADD COLUMN template_version     INTEGER NOT NULL DEFAULT 1;
ALTER TABLE safety_reports ADD COLUMN reporter_position    TEXT NULL;

-- =============================================================================
-- SECTION B: safety_report_threads
-- =============================================================================

CREATE TABLE IF NOT EXISTS safety_report_threads (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    report_id   INTEGER NOT NULL,
    author_id   INTEGER NOT NULL,
    body        TEXT    NOT NULL,
    is_internal INTEGER NOT NULL DEFAULT 0,
    parent_id   INTEGER NULL DEFAULT NULL,
    created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_srt_report ON safety_report_threads (report_id);
CREATE INDEX IF NOT EXISTS idx_srt_author ON safety_report_threads (author_id);

-- =============================================================================
-- SECTION C: safety_report_attachments
-- =============================================================================

CREATE TABLE IF NOT EXISTS safety_report_attachments (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    report_id   INTEGER NOT NULL,
    thread_id   INTEGER NULL DEFAULT NULL,
    uploaded_by INTEGER NOT NULL,
    file_name   TEXT    NOT NULL,
    file_path   TEXT    NOT NULL,
    file_type   TEXT    NOT NULL,
    file_size   INTEGER NOT NULL DEFAULT 0,
    created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_sra_report ON safety_report_attachments (report_id);

-- =============================================================================
-- SECTION D: safety_report_status_history
-- =============================================================================

CREATE TABLE IF NOT EXISTS safety_report_status_history (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    report_id   INTEGER NOT NULL,
    changed_by  INTEGER NOT NULL,
    from_status TEXT    NULL,
    to_status   TEXT    NOT NULL,
    comment     TEXT    NULL,
    created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_srsh_report ON safety_report_status_history (report_id);

-- =============================================================================
-- SECTION E: safety_report_assignments
-- =============================================================================

CREATE TABLE IF NOT EXISTS safety_report_assignments (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    report_id     INTEGER NOT NULL,
    assigned_by   INTEGER NOT NULL,
    assigned_to   INTEGER NULL DEFAULT NULL,
    unassigned_at TEXT    NULL DEFAULT NULL,
    note          TEXT    NULL DEFAULT NULL,
    created_at    TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_sra2_report ON safety_report_assignments (report_id);

-- =============================================================================
-- SECTION F: safety_publications
-- =============================================================================

CREATE TABLE IF NOT EXISTS safety_publications (
    id                INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id         INTEGER NOT NULL,
    created_by        INTEGER NOT NULL,
    title             TEXT    NOT NULL,
    summary           TEXT    NULL,
    content           TEXT    NOT NULL,
    related_report_id INTEGER NULL DEFAULT NULL,
    status            TEXT    NOT NULL DEFAULT 'draft'
                      CHECK (status IN ('draft','published','archived')),
    published_at      TEXT    NULL DEFAULT NULL,
    created_at        TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at        TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_sp_tenant ON safety_publications (tenant_id);
CREATE INDEX IF NOT EXISTS idx_sp_status ON safety_publications (status);

-- =============================================================================
-- SECTION G: safety_publication_audiences
-- =============================================================================

CREATE TABLE IF NOT EXISTS safety_publication_audiences (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    publication_id   INTEGER NOT NULL,
    audience_type    TEXT    NOT NULL
                     CHECK (audience_type IN ('all','pilots','engineers','cabin_crew','management','department')),
    department_id    INTEGER NULL DEFAULT NULL,
    UNIQUE (publication_id, audience_type)
);

-- =============================================================================
-- SECTION H: safety_module_settings
-- =============================================================================

CREATE TABLE IF NOT EXISTS safety_module_settings (
    id                   INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id            INTEGER NOT NULL UNIQUE,
    enabled_types        TEXT    NULL,
    allow_anonymous      INTEGER NOT NULL DEFAULT 1,
    require_aircraft_reg INTEGER NOT NULL DEFAULT 0,
    risk_matrix_enabled  INTEGER NOT NULL DEFAULT 1,
    retention_days       INTEGER NOT NULL DEFAULT 2555,
    updated_at           TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- Seed one row per active tenant (idempotent)
INSERT OR IGNORE INTO safety_module_settings (tenant_id, enabled_types)
SELECT id,
       '["general_hazard","flight_crew_occurrence","maintenance_engineering","ground_ops","quality","hse","tcas","environmental","frat"]'
  FROM tenants
 WHERE status = 'active';
