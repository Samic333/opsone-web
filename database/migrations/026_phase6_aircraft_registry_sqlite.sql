-- Phase 6 (V2) — Aircraft Registry, Documents, Maintenance  (SQLite)

CREATE TABLE IF NOT EXISTS aircraft (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id      INTEGER NOT NULL,
    fleet_id       INTEGER DEFAULT NULL,
    registration   TEXT    NOT NULL,
    aircraft_type  TEXT    NOT NULL,
    variant        TEXT    DEFAULT NULL,
    manufacturer   TEXT    DEFAULT NULL,
    msn            TEXT    DEFAULT NULL,
    year_built     INTEGER DEFAULT NULL,
    home_base_id   INTEGER DEFAULT NULL,
    status         TEXT    NOT NULL DEFAULT 'active',   -- active|maintenance|aog|stored|retired
    total_hours    REAL    NOT NULL DEFAULT 0,
    total_cycles   INTEGER NOT NULL DEFAULT 0,
    notes          TEXT    DEFAULT NULL,
    created_at     TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at     TEXT    NOT NULL DEFAULT (datetime('now')),
    UNIQUE (tenant_id, registration),
    FOREIGN KEY (tenant_id)    REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (fleet_id)     REFERENCES fleets(id)  ON DELETE SET NULL,
    FOREIGN KEY (home_base_id) REFERENCES bases(id)   ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_acft_fleet  ON aircraft(fleet_id);
CREATE INDEX IF NOT EXISTS idx_acft_base   ON aircraft(home_base_id);
CREATE INDEX IF NOT EXISTS idx_acft_status ON aircraft(status);

CREATE TABLE IF NOT EXISTS aircraft_documents (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    aircraft_id  INTEGER NOT NULL,
    tenant_id    INTEGER NOT NULL,
    doc_type     TEXT    NOT NULL,
    doc_number   TEXT    DEFAULT NULL,
    issued_date  TEXT    DEFAULT NULL,
    expiry_date  TEXT    DEFAULT NULL,
    file_path    TEXT    DEFAULT NULL,
    notes        TEXT    DEFAULT NULL,
    uploaded_by  INTEGER DEFAULT NULL,
    created_at   TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (aircraft_id) REFERENCES aircraft(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_acftdoc_acft   ON aircraft_documents(aircraft_id);
CREATE INDEX IF NOT EXISTS idx_acftdoc_expiry ON aircraft_documents(expiry_date);

CREATE TABLE IF NOT EXISTS aircraft_maintenance (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    aircraft_id     INTEGER NOT NULL,
    tenant_id       INTEGER NOT NULL,
    item_type       TEXT    NOT NULL,
    description     TEXT    DEFAULT NULL,
    due_date        TEXT    DEFAULT NULL,
    due_hours       REAL    DEFAULT NULL,
    due_cycles      INTEGER DEFAULT NULL,
    last_done_date  TEXT    DEFAULT NULL,
    last_done_hours REAL    DEFAULT NULL,
    interval_days   INTEGER DEFAULT NULL,
    interval_hours  REAL    DEFAULT NULL,
    status          TEXT    NOT NULL DEFAULT 'active',  -- active|completed|deferred|waived
    notes           TEXT    DEFAULT NULL,
    created_at      TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at      TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (aircraft_id) REFERENCES aircraft(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_acftmx_acft     ON aircraft_maintenance(aircraft_id);
CREATE INDEX IF NOT EXISTS idx_acftmx_due_date ON aircraft_maintenance(due_date);
CREATE INDEX IF NOT EXISTS idx_acftmx_status   ON aircraft_maintenance(status);
