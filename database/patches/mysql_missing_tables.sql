-- -----------------------------------------------------
-- MySQL Missing Tables Patch for Namecheap
-- -----------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `audit_logs` 
  ADD COLUMN `actor_role` VARCHAR(50) DEFAULT NULL,
  ADD COLUMN `result` VARCHAR(50) NOT NULL DEFAULT 'success',
  ADD COLUMN `reason` TEXT DEFAULT NULL,
  ADD COLUMN `user_agent` TEXT DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `notice_reads` (
                id INTEGER PRIMARY KEY AUTO_INCREMENT,
                notice_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                tenant_id INTEGER NOT NULL,
                read_at TEXT NOT NULL DEFAULT (CURRENT_TIMESTAMP),
                acknowledged_at TEXT DEFAULT NULL,
                FOREIGN KEY (notice_id) REFERENCES notices(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
                UNIQUE (notice_id, user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `file_acknowledgements` (
                id INTEGER PRIMARY KEY AUTO_INCREMENT,
                file_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                tenant_id INTEGER NOT NULL,
                version TEXT DEFAULT NULL,
                device_id INTEGER DEFAULT NULL,
                acknowledged_at TEXT NOT NULL DEFAULT (CURRENT_TIMESTAMP),
                UNIQUE (file_id, user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `crew_profiles` (
    `id`                  INTEGER PRIMARY KEY AUTO_INCREMENT,
    `user_id`             INTEGER NOT NULL UNIQUE,
    `tenant_id`           INTEGER NOT NULL,
    `date_of_birth`       TEXT    DEFAULT NULL,
    `nationality`         TEXT    DEFAULT NULL,
    `phone`               TEXT    DEFAULT NULL,
    `emergency_name`      TEXT    DEFAULT NULL,
    `emergency_phone`     TEXT    DEFAULT NULL,
    `emergency_relation`  TEXT    DEFAULT NULL,
    `passport_number`     TEXT    DEFAULT NULL,
    `passport_country`    TEXT    DEFAULT NULL,
    `passport_expiry`     TEXT    DEFAULT NULL,
    `medical_class`       TEXT    DEFAULT NULL,
    `medical_expiry`      TEXT    DEFAULT NULL,
    `contract_type`       TEXT    DEFAULT NULL,
    `contract_expiry`     TEXT    DEFAULT NULL,
    `created_at`          TEXT    NOT NULL DEFAULT (CURRENT_TIMESTAMP),
    `updated_at`          TEXT    NOT NULL DEFAULT (CURRENT_TIMESTAMP),
    FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `licenses` (
    `id`                INTEGER PRIMARY KEY AUTO_INCREMENT,
    `user_id`           INTEGER NOT NULL,
    `tenant_id`         INTEGER NOT NULL,
    `license_type`      TEXT    NOT NULL,
    `license_number`    TEXT    DEFAULT NULL,
    `issuing_authority` TEXT    DEFAULT NULL,
    `issue_date`        TEXT    DEFAULT NULL,
    `expiry_date`       TEXT    DEFAULT NULL,
    `notes`             TEXT    DEFAULT NULL,
    `created_at`        TEXT    NOT NULL DEFAULT (CURRENT_TIMESTAMP),
    `updated_at`        TEXT    NOT NULL DEFAULT (CURRENT_TIMESTAMP),
    FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `rosters` (
    id          INTEGER PRIMARY KEY AUTO_INCREMENT,
    tenant_id   INTEGER NOT NULL,
    user_id     INTEGER NOT NULL,
    roster_date TEXT    NOT NULL,
    duty_type   TEXT    NOT NULL DEFAULT 'off',
    duty_code   TEXT,
    notes       TEXT,
    created_at  TEXT DEFAULT (CURRENT_TIMESTAMP),
    updated_at  TEXT DEFAULT (CURRENT_TIMESTAMP), roster_period_id INTEGER DEFAULT NULL, base_id          INTEGER DEFAULT NULL, fleet_id         INTEGER DEFAULT NULL, reserve_type     TEXT    DEFAULT NULL,
    UNIQUE (user_id, roster_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `fdm_uploads` (
    id            INTEGER PRIMARY KEY AUTO_INCREMENT,
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
    created_at    TEXT DEFAULT (CURRENT_TIMESTAMP)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `fdm_events` (
    id             INTEGER PRIMARY KEY AUTO_INCREMENT,
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
    created_at     TEXT DEFAULT (CURRENT_TIMESTAMP)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `modules` (
    id               INTEGER PRIMARY KEY AUTO_INCREMENT,
    code             TEXT    NOT NULL UNIQUE,
    name             TEXT    NOT NULL,
    description      TEXT    DEFAULT NULL,
    icon             TEXT    DEFAULT NULL,
    platform_status  TEXT    NOT NULL DEFAULT 'available',
    visibility       TEXT    NOT NULL DEFAULT 'visible',
    mobile_capable   INTEGER NOT NULL DEFAULT 0,
    requires_platform_enable INTEGER NOT NULL DEFAULT 1,
    sort_order       INTEGER NOT NULL DEFAULT 100,
    created_at       TEXT    NOT NULL DEFAULT (CURRENT_TIMESTAMP),
    updated_at       TEXT    NOT NULL DEFAULT (CURRENT_TIMESTAMP)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `module_capabilities` (
    id          INTEGER PRIMARY KEY AUTO_INCREMENT,
    module_id   INTEGER NOT NULL,
    capability  TEXT    NOT NULL,
    description TEXT    DEFAULT NULL,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    UNIQUE (module_id, capability)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tenant_modules` (
    id                 INTEGER PRIMARY KEY AUTO_INCREMENT,
    tenant_id          INTEGER NOT NULL,
    module_id          INTEGER NOT NULL,
    is_enabled         INTEGER NOT NULL DEFAULT 1,
    tenant_can_disable INTEGER NOT NULL DEFAULT 0,
    enabled_by         INTEGER DEFAULT NULL,
    enabled_at         TEXT    NOT NULL DEFAULT (CURRENT_TIMESTAMP),
    notes              TEXT    DEFAULT NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    UNIQUE (tenant_id, module_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `role_capability_templates` (
    id                   INTEGER PRIMARY KEY AUTO_INCREMENT,
    role_slug            TEXT    NOT NULL,
    module_capability_id INTEGER NOT NULL,
    FOREIGN KEY (module_capability_id) REFERENCES module_capabilities(id) ON DELETE CASCADE,
    UNIQUE (role_slug, module_capability_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user_capability_overrides` (
    id                   INTEGER PRIMARY KEY AUTO_INCREMENT,
    user_id              INTEGER NOT NULL,
    tenant_id            INTEGER NOT NULL,
    module_capability_id INTEGER NOT NULL,
    granted              INTEGER NOT NULL DEFAULT 1,
    reason               TEXT    DEFAULT NULL,
    set_by               INTEGER DEFAULT NULL,
    created_at           TEXT    NOT NULL DEFAULT (CURRENT_TIMESTAMP),
    FOREIGN KEY (user_id)    REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (tenant_id)  REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (module_capability_id) REFERENCES module_capabilities(id) ON DELETE CASCADE,
    UNIQUE (user_id, module_capability_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tenant_settings` (
    id              INTEGER PRIMARY KEY AUTO_INCREMENT,
    tenant_id       INTEGER NOT NULL UNIQUE,
    timezone        TEXT    NOT NULL DEFAULT 'UTC',
    date_format     TEXT    NOT NULL DEFAULT 'Y-m-d',
    language        TEXT    NOT NULL DEFAULT 'en',
    mobile_sync_interval_minutes INTEGER NOT NULL DEFAULT 60,
    require_device_approval      INTEGER NOT NULL DEFAULT 1,
    allow_self_registration      INTEGER NOT NULL DEFAULT 0,
    custom_fields   TEXT    DEFAULT NULL,
    updated_at      TEXT    NOT NULL DEFAULT (CURRENT_TIMESTAMP),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tenant_contacts` (
    id           INTEGER PRIMARY KEY AUTO_INCREMENT,
    tenant_id    INTEGER NOT NULL,
    contact_type TEXT    NOT NULL DEFAULT 'primary_admin',
    name         TEXT    NOT NULL,
    email        TEXT    NOT NULL,
    phone        TEXT    DEFAULT NULL,
    title        TEXT    DEFAULT NULL,
    is_primary   INTEGER NOT NULL DEFAULT 0,
    created_at   TEXT    NOT NULL DEFAULT (CURRENT_TIMESTAMP),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tenant_access_policies` (
    id                       INTEGER PRIMARY KEY AUTO_INCREMENT,
    tenant_id                INTEGER NOT NULL UNIQUE,
    mfa_required             INTEGER NOT NULL DEFAULT 0,
    ip_whitelist             TEXT    DEFAULT NULL,
    session_timeout_minutes  INTEGER NOT NULL DEFAULT 120,
    api_access_enabled       INTEGER NOT NULL DEFAULT 1,
    mobile_access_enabled    INTEGER NOT NULL DEFAULT 1,
    platform_support_access  TEXT    NOT NULL DEFAULT 'readonly',
    updated_at               TEXT    NOT NULL DEFAULT (CURRENT_TIMESTAMP),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `platform_access_log` (
    id                INTEGER PRIMARY KEY AUTO_INCREMENT,
    platform_user_id  INTEGER NOT NULL,
    tenant_id         INTEGER NOT NULL,
    module_area       TEXT    DEFAULT NULL,
    reason            TEXT    NOT NULL,
    ticket_ref        TEXT    DEFAULT NULL,
    ip_address        TEXT    DEFAULT NULL,
    user_agent        TEXT    DEFAULT NULL,
    access_started_at TEXT    NOT NULL DEFAULT (CURRENT_TIMESTAMP),
    access_ended_at   TEXT    DEFAULT NULL,
    status            TEXT    NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tenant_onboarding_requests` (
    id                 INTEGER PRIMARY KEY AUTO_INCREMENT,
    legal_name         TEXT    NOT NULL,
    display_name       TEXT    DEFAULT NULL,
    icao_code          TEXT    DEFAULT NULL,
    iata_code          TEXT    DEFAULT NULL,
    primary_country    TEXT    DEFAULT NULL,
    contact_name       TEXT    NOT NULL,
    contact_email      TEXT    NOT NULL,
    contact_phone      TEXT    DEFAULT NULL,
    expected_headcount INTEGER DEFAULT NULL,
    support_tier       TEXT    NOT NULL DEFAULT 'standard',
    requested_modules  TEXT    DEFAULT NULL,
    notes              TEXT    DEFAULT NULL,
    status             TEXT    NOT NULL DEFAULT 'pending'
                       CHECK(status IN ('pending','in_review','approved','rejected','provisioned')),
    reviewed_by        INTEGER DEFAULT NULL,
    reviewed_at        TEXT    DEFAULT NULL,
    review_notes       TEXT    DEFAULT NULL,
    tenant_id          INTEGER DEFAULT NULL,
    created_at         TEXT    NOT NULL DEFAULT (CURRENT_TIMESTAMP),
    updated_at         TEXT    NOT NULL DEFAULT (CURRENT_TIMESTAMP)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `invitation_tokens` (
    id          INTEGER PRIMARY KEY AUTO_INCREMENT,
    tenant_id   INTEGER NOT NULL,
    email       TEXT    NOT NULL,
    name        TEXT    DEFAULT NULL,
    role_slug   TEXT    NOT NULL DEFAULT 'airline_admin',
    token       TEXT    NOT NULL UNIQUE,
    expires_at  TEXT    NOT NULL,
    accepted_at TEXT    DEFAULT NULL,
    created_by  INTEGER DEFAULT NULL,
    created_at  TEXT    NOT NULL DEFAULT (CURRENT_TIMESTAMP),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `mobile_sync_meta` (
    id                INTEGER PRIMARY KEY AUTO_INCREMENT,
    tenant_id         INTEGER NOT NULL,
    module_code       TEXT    NOT NULL,
    last_published_at TEXT    DEFAULT NULL,
    version_hash      TEXT    DEFAULT NULL,
    updated_at        TEXT    NOT NULL DEFAULT (CURRENT_TIMESTAMP),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE (tenant_id, module_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `fleets` (
    id            INTEGER PRIMARY KEY AUTO_INCREMENT,
    tenant_id     INTEGER NOT NULL,
    name          TEXT    NOT NULL,
    code          TEXT    DEFAULT NULL,
    aircraft_type TEXT    DEFAULT NULL,
    created_at    TEXT    NOT NULL DEFAULT (CURRENT_TIMESTAMP),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `qualifications` (
                    id          INTEGER PRIMARY KEY AUTO_INCREMENT,
                    user_id     INTEGER NOT NULL,
                    tenant_id   INTEGER NOT NULL,
                    qual_type   TEXT    NOT NULL,
                    qual_name   TEXT    NOT NULL,
                    reference_no TEXT,
                    authority   TEXT,
                    issue_date  TEXT,
                    expiry_date TEXT,
                    status      TEXT    NOT NULL DEFAULT 'active',
                    notes       TEXT,
                    created_at  TEXT    NOT NULL DEFAULT (CURRENT_TIMESTAMP)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `notice_categories` (
    id         INTEGER PRIMARY KEY AUTO_INCREMENT,
    tenant_id  INTEGER NOT NULL,
    name       TEXT    NOT NULL,
    slug       TEXT    NOT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TEXT    NOT NULL DEFAULT (CURRENT_TIMESTAMP),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE (tenant_id, slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `notice_role_visibility` (
    notice_id INTEGER NOT NULL,
    role_id   INTEGER NOT NULL,
    PRIMARY KEY (notice_id, role_id),
    FOREIGN KEY (notice_id) REFERENCES notices(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id)   REFERENCES roles(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `roster_periods` (
    id          INTEGER PRIMARY KEY AUTO_INCREMENT,
    tenant_id   INTEGER NOT NULL,
    name        TEXT    NOT NULL,                          -- e.g. `April 2026`
    start_date  TEXT    NOT NULL,                          -- YYYY-MM-DD
    end_date    TEXT    NOT NULL,                          -- YYYY-MM-DD
    status      TEXT    NOT NULL DEFAULT 'draft',          -- draft | published | frozen | archived
    notes       TEXT    DEFAULT NULL,
    created_by  INTEGER DEFAULT NULL,
    created_at  TEXT    NOT NULL DEFAULT (CURRENT_TIMESTAMP),
    updated_at  TEXT    NOT NULL DEFAULT (CURRENT_TIMESTAMP)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `roster_changes` (
    id               INTEGER PRIMARY KEY AUTO_INCREMENT,
    tenant_id        INTEGER NOT NULL,
    roster_period_id INTEGER DEFAULT NULL,    -- period-level comment
    roster_id        INTEGER DEFAULT NULL,    -- entry-level comment (specific day)
    user_id          INTEGER NOT NULL,        -- crew member making the request
    requested_by     INTEGER NOT NULL,        -- user submitting this record (may differ for manager comments)
    change_type      TEXT    NOT NULL,        -- comment | leave_request | swap_request | correction
    status           TEXT    NOT NULL DEFAULT 'pending',  -- pending | approved | rejected | noted
    message          TEXT    NOT NULL,
    response         TEXT    DEFAULT NULL,    -- manager/scheduler response
    responded_by     INTEGER DEFAULT NULL,
    responded_at     TEXT    DEFAULT NULL,
    created_at       TEXT    NOT NULL DEFAULT (CURRENT_TIMESTAMP),
    updated_at       TEXT    NOT NULL DEFAULT (CURRENT_TIMESTAMP)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
