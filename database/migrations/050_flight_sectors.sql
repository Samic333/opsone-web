-- Migration 050 (MySQL) — Flight sectors (multi-leg duty support).
--
-- Sister of 050_flight_sectors_sqlite.sql. Adds the same `flight_sectors`
-- table for the production MySQL/MariaDB store and backfills existing
-- flight rows as single-sector duties.

CREATE TABLE IF NOT EXISTS flight_sectors (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id          INT UNSIGNED NOT NULL,
    flight_id          INT UNSIGNED NOT NULL,
    sector_index       INT UNSIGNED NOT NULL,
    departure_icao     VARCHAR(4)   DEFAULT NULL,
    arrival_icao       VARCHAR(4)   DEFAULT NULL,
    departure_iata     VARCHAR(3)   DEFAULT NULL,
    arrival_iata       VARCHAR(3)   DEFAULT NULL,
    std_utc            DATETIME     DEFAULT NULL,
    sta_utc            DATETIME     DEFAULT NULL,
    etd_utc            DATETIME     DEFAULT NULL,
    eta_utc            DATETIME     DEFAULT NULL,
    aircraft_id        INT UNSIGNED DEFAULT NULL,
    block_off_utc      DATETIME     DEFAULT NULL,
    takeoff_utc        DATETIME     DEFAULT NULL,
    landing_utc        DATETIME     DEFAULT NULL,
    block_on_utc       DATETIME     DEFAULT NULL,
    fuel_uplift_kg     DECIMAL(9,2) DEFAULT NULL,
    fuel_remaining_kg  DECIMAL(9,2) DEFAULT NULL,
    pax_total          INT UNSIGNED DEFAULT NULL,
    status             ENUM('planned','airborne','completed','cancelled','diverted')
                       NOT NULL DEFAULT 'planned',
    notes              TEXT         DEFAULT NULL,
    created_at         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                                    ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_sec_flight_index (flight_id, sector_index),
    KEY idx_sec_tenant   (tenant_id),
    KEY idx_sec_status   (status),
    KEY idx_sec_aircraft (aircraft_id),
    CONSTRAINT fk_sec_tenant   FOREIGN KEY (tenant_id)   REFERENCES tenants(id)  ON DELETE CASCADE,
    CONSTRAINT fk_sec_flight   FOREIGN KEY (flight_id)   REFERENCES flights(id)  ON DELETE CASCADE,
    CONSTRAINT fk_sec_aircraft FOREIGN KEY (aircraft_id) REFERENCES aircraft(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Backfill: every existing flight row becomes a single sector.
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
