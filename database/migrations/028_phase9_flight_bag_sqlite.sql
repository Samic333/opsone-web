-- Phase 9 (V2) — Flight Assignment + Flight Bag   (SQLite)

CREATE TABLE IF NOT EXISTS flights (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id     INTEGER NOT NULL,
    flight_date   TEXT    NOT NULL,
    flight_number TEXT    NOT NULL,
    departure     TEXT    DEFAULT NULL,
    arrival       TEXT    DEFAULT NULL,
    std           TEXT    DEFAULT NULL,
    sta           TEXT    DEFAULT NULL,
    aircraft_id   INTEGER DEFAULT NULL,
    captain_id    INTEGER DEFAULT NULL,
    fo_id         INTEGER DEFAULT NULL,
    status        TEXT    NOT NULL DEFAULT 'draft',    -- draft|published|in_flight|completed|cancelled
    notes         TEXT    DEFAULT NULL,
    created_at    TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at    TEXT    NOT NULL DEFAULT (datetime('now')),
    UNIQUE (tenant_id, flight_date, flight_number),
    FOREIGN KEY (tenant_id)   REFERENCES tenants(id)   ON DELETE CASCADE,
    FOREIGN KEY (aircraft_id) REFERENCES aircraft(id)  ON DELETE SET NULL,
    FOREIGN KEY (captain_id)  REFERENCES users(id)     ON DELETE SET NULL,
    FOREIGN KEY (fo_id)       REFERENCES users(id)     ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_flight_date     ON flights(flight_date);
CREATE INDEX IF NOT EXISTS idx_flight_captain  ON flights(captain_id);
CREATE INDEX IF NOT EXISTS idx_flight_fo       ON flights(fo_id);
CREATE INDEX IF NOT EXISTS idx_flight_aircraft ON flights(aircraft_id);

CREATE TABLE IF NOT EXISTS flight_bag_files (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    flight_id   INTEGER NOT NULL,
    tenant_id   INTEGER NOT NULL,
    file_type   TEXT    NOT NULL,          -- nav_plan|notam|weather|wb|opt|company|other
    title       TEXT    NOT NULL,
    file_path   TEXT    NOT NULL,
    file_name   TEXT    NOT NULL,
    file_size   INTEGER NOT NULL DEFAULT 0,
    uploaded_by INTEGER DEFAULT NULL,
    created_at  TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (flight_id) REFERENCES flights(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_fbf_flight ON flight_bag_files(flight_id);
