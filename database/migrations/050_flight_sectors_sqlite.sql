-- Migration 050 (SQLite) — Flight sectors (multi-leg duty support).
--
-- Until now, `flights` has been a single departure→arrival row. Real airline
-- duty days are multi-leg (e.g. ENTEBBE→BENI→ENTEBBE→KIBATI→...). To support
-- this on iPad without losing the existing flight row as the "duty wrapper",
-- we add a child `flight_sectors` table. Each flight owns N sectors numbered
-- 1..N. Existing rows backfill to one sector each.
--
-- Idempotent — every CREATE/INSERT uses IF NOT EXISTS / a NOT EXISTS guard.

BEGIN TRANSACTION;

CREATE TABLE IF NOT EXISTS flight_sectors (
    id                 INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id          INTEGER NOT NULL,
    flight_id          INTEGER NOT NULL,
    sector_index       INTEGER NOT NULL,                 -- 1-based ordinal within the flight
    departure_icao     TEXT    DEFAULT NULL,
    arrival_icao       TEXT    DEFAULT NULL,
    departure_iata     TEXT    DEFAULT NULL,
    arrival_iata       TEXT    DEFAULT NULL,
    std_utc            TEXT    DEFAULT NULL,             -- scheduled times of departure / arrival
    sta_utc            TEXT    DEFAULT NULL,
    etd_utc            TEXT    DEFAULT NULL,             -- estimated (revised) times
    eta_utc            TEXT    DEFAULT NULL,
    aircraft_id        INTEGER DEFAULT NULL,
    block_off_utc      TEXT    DEFAULT NULL,             -- actuals captured by crew
    takeoff_utc        TEXT    DEFAULT NULL,
    landing_utc        TEXT    DEFAULT NULL,
    block_on_utc       TEXT    DEFAULT NULL,
    fuel_uplift_kg     REAL    DEFAULT NULL,
    fuel_remaining_kg  REAL    DEFAULT NULL,
    pax_total          INTEGER DEFAULT NULL,
    status             TEXT    NOT NULL DEFAULT 'planned'
        CHECK(status IN ('planned','airborne','completed','cancelled','diverted')),
    notes              TEXT    DEFAULT NULL,
    created_at         TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at         TEXT    NOT NULL DEFAULT (datetime('now')),
    UNIQUE (flight_id, sector_index),
    FOREIGN KEY (tenant_id)   REFERENCES tenants(id)  ON DELETE CASCADE,
    FOREIGN KEY (flight_id)   REFERENCES flights(id)  ON DELETE CASCADE,
    FOREIGN KEY (aircraft_id) REFERENCES aircraft(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_sec_flight   ON flight_sectors(flight_id);
CREATE INDEX IF NOT EXISTS idx_sec_tenant   ON flight_sectors(tenant_id);
CREATE INDEX IF NOT EXISTS idx_sec_status   ON flight_sectors(status);
CREATE INDEX IF NOT EXISTS idx_sec_aircraft ON flight_sectors(aircraft_id);

-- Backfill: every existing flight row becomes a single sector.
-- The NOT EXISTS guard makes this safe to re-run.
INSERT INTO flight_sectors (
    tenant_id, flight_id, sector_index,
    departure_icao, arrival_icao,
    std_utc, sta_utc,
    aircraft_id, status
)
SELECT
    f.tenant_id, f.id, 1,
    f.departure, f.arrival,
    f.std, f.sta,
    f.aircraft_id,
    CASE
        WHEN f.status = 'completed' THEN 'completed'
        WHEN f.status = 'in_flight' THEN 'airborne'
        WHEN f.status = 'cancelled' THEN 'cancelled'
        ELSE 'planned'
    END
FROM flights f
WHERE NOT EXISTS (
    SELECT 1 FROM flight_sectors s WHERE s.flight_id = f.id
);

COMMIT;
