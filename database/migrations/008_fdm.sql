-- Phase 5: FDM (Flight Data Monitoring) Module (MySQL 8.0)

CREATE TABLE IF NOT EXISTS fdm_uploads (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id     INT UNSIGNED NOT NULL,
    uploaded_by   INT UNSIGNED NOT NULL,
    filename      VARCHAR(255) NOT NULL COMMENT 'Stored filename on disk',
    original_name VARCHAR(255) NOT NULL COMMENT 'Original uploaded filename',
    flight_date   DATE         NULL,
    aircraft_reg  VARCHAR(20)  NULL,
    flight_number VARCHAR(20)  NULL,
    event_count   INT UNSIGNED NOT NULL DEFAULT 0,
    status        ENUM('pending','processed','failed') NOT NULL DEFAULT 'pending',
    notes         TEXT         NULL,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,

    KEY idx_fdm_tenant (tenant_id),
    CONSTRAINT fk_fdm_tenant FOREIGN KEY (tenant_id)   REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_fdm_user   FOREIGN KEY (uploaded_by) REFERENCES users(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS fdm_events (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id     INT UNSIGNED NOT NULL,
    fdm_upload_id INT UNSIGNED NULL COMMENT 'NULL = manually entered',
    event_type    ENUM('exceedance','hard_landing','unstabilised_approach','gpws','tcas','overspeed','tail_strike','windshear','other') NOT NULL DEFAULT 'other',
    severity      ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
    flight_date   DATE         NULL,
    aircraft_reg  VARCHAR(20)  NULL,
    flight_number VARCHAR(20)  NULL,
    flight_phase  VARCHAR(50)  NULL COMMENT 'e.g. climb, cruise, approach, landing',
    parameter     VARCHAR(100) NULL COMMENT 'e.g. vertical_g, airspeed, pitch_angle',
    value_recorded DECIMAL(10,3) NULL,
    threshold      DECIMAL(10,3) NULL,
    notes         TEXT NULL,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,

    KEY idx_fdm_ev_tenant (tenant_id),
    KEY idx_fdm_ev_upload (fdm_upload_id),
    CONSTRAINT fk_fdmev_tenant FOREIGN KEY (tenant_id)     REFERENCES tenants(id)      ON DELETE CASCADE,
    CONSTRAINT fk_fdmev_upload FOREIGN KEY (fdm_upload_id) REFERENCES fdm_uploads(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
