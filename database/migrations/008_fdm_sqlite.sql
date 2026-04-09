-- Phase 5: FDM Module (SQLite dev)

CREATE TABLE IF NOT EXISTS fdm_uploads (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id     INTEGER NOT NULL,
    uploaded_by   INTEGER NOT NULL,
    filename      TEXT NOT NULL,
    original_name TEXT NOT NULL,
    flight_date   TEXT,
    aircraft_reg  TEXT,
    flight_number TEXT,
    event_count   INTEGER NOT NULL DEFAULT 0,
    status        TEXT NOT NULL DEFAULT 'pending',
    notes         TEXT,
    created_at    TEXT DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_fdm_tenant ON fdm_uploads (tenant_id);


CREATE TABLE IF NOT EXISTS fdm_events (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id      INTEGER NOT NULL,
    fdm_upload_id  INTEGER,
    event_type     TEXT NOT NULL DEFAULT 'other',
    severity       TEXT NOT NULL DEFAULT 'medium',
    flight_date    TEXT,
    aircraft_reg   TEXT,
    flight_number  TEXT,
    flight_phase   TEXT,
    parameter      TEXT,
    value_recorded REAL,
    threshold      REAL,
    notes          TEXT,
    created_at     TEXT DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_fdm_ev_tenant ON fdm_events (tenant_id);
CREATE INDEX IF NOT EXISTS idx_fdm_ev_upload ON fdm_events (fdm_upload_id);
