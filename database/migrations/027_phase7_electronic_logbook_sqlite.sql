-- Phase 7 (V2) — Electronic Pilot Logbook  (SQLite)

CREATE TABLE IF NOT EXISTS flight_logs (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id       INTEGER NOT NULL,
    user_id         INTEGER NOT NULL,
    flight_date     TEXT    NOT NULL,
    aircraft_id     INTEGER DEFAULT NULL,
    aircraft_type   TEXT    DEFAULT NULL,
    registration    TEXT    DEFAULT NULL,
    flight_number   TEXT    DEFAULT NULL,
    departure       TEXT    DEFAULT NULL,
    arrival         TEXT    DEFAULT NULL,
    off_blocks      TEXT    DEFAULT NULL,
    takeoff         TEXT    DEFAULT NULL,
    landing         TEXT    DEFAULT NULL,
    on_blocks       TEXT    DEFAULT NULL,
    block_minutes   INTEGER DEFAULT NULL,
    air_minutes     INTEGER DEFAULT NULL,
    day_minutes     INTEGER DEFAULT NULL,
    night_minutes   INTEGER DEFAULT NULL,
    ifr_minutes     INTEGER DEFAULT NULL,
    pic_minutes     INTEGER DEFAULT NULL,
    sic_minutes     INTEGER DEFAULT NULL,
    landings_day    INTEGER NOT NULL DEFAULT 0,
    landings_night  INTEGER NOT NULL DEFAULT 0,
    rules           TEXT    NOT NULL DEFAULT 'IFR',     -- VFR|IFR|MIXED
    role            TEXT    NOT NULL DEFAULT 'PIC',     -- PIC|SIC|DUAL|INSTRUCTOR
    remarks         TEXT    DEFAULT NULL,
    created_at      TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at      TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (tenant_id)   REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)     REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (aircraft_id) REFERENCES aircraft(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_flog_user_date ON flight_logs(user_id, flight_date);
CREATE INDEX IF NOT EXISTS idx_flog_tenant    ON flight_logs(tenant_id);
