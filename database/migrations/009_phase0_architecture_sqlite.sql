-- =====================================================
-- Migration 009 (SQLite) — Phase Zero Architecture
-- Equivalent of 009_phase0_architecture.sql + 011
-- for local SQLite development database.
--
-- Run: sqlite3 database/crewassist.sqlite < database/migrations/009_phase0_architecture_sqlite.sql
-- =====================================================

PRAGMA foreign_keys = OFF;

-- ─── 1. Enhance roles table ───────────────────────────────────────────────────
ALTER TABLE roles ADD COLUMN role_type TEXT NOT NULL DEFAULT 'tenant'
    CHECK(role_type IN ('platform','tenant','end_user'));

UPDATE roles SET role_type = 'platform'
    WHERE slug IN ('super_admin','platform_support','platform_security','system_monitoring')
      AND tenant_id IS NULL;

UPDATE roles SET role_type = 'end_user'
    WHERE slug IN ('pilot','cabin_crew','engineer')
      AND tenant_id IS NULL;

-- ─── 2. Enhance tenants table ─────────────────────────────────────────────────
ALTER TABLE tenants ADD COLUMN legal_name          TEXT DEFAULT NULL;
ALTER TABLE tenants ADD COLUMN display_name        TEXT DEFAULT NULL;
ALTER TABLE tenants ADD COLUMN icao_code           TEXT DEFAULT NULL;
ALTER TABLE tenants ADD COLUMN iata_code           TEXT DEFAULT NULL;
ALTER TABLE tenants ADD COLUMN primary_country     TEXT DEFAULT NULL;
ALTER TABLE tenants ADD COLUMN primary_base        TEXT DEFAULT NULL;
ALTER TABLE tenants ADD COLUMN support_tier        TEXT NOT NULL DEFAULT 'standard';
ALTER TABLE tenants ADD COLUMN onboarding_status   TEXT NOT NULL DEFAULT 'active';
ALTER TABLE tenants ADD COLUMN expected_headcount  INTEGER DEFAULT NULL;
ALTER TABLE tenants ADD COLUMN headcount_pilots    INTEGER DEFAULT NULL;
ALTER TABLE tenants ADD COLUMN headcount_cabin     INTEGER DEFAULT NULL;
ALTER TABLE tenants ADD COLUMN headcount_engineers INTEGER DEFAULT NULL;
ALTER TABLE tenants ADD COLUMN headcount_schedulers INTEGER DEFAULT NULL;
ALTER TABLE tenants ADD COLUMN headcount_training  INTEGER DEFAULT NULL;
ALTER TABLE tenants ADD COLUMN headcount_safety    INTEGER DEFAULT NULL;
ALTER TABLE tenants ADD COLUMN headcount_hr        INTEGER DEFAULT NULL;
ALTER TABLE tenants ADD COLUMN notes               TEXT DEFAULT NULL;
ALTER TABLE tenants ADD COLUMN onboarded_at        TEXT DEFAULT NULL;
ALTER TABLE tenants ADD COLUMN suspended_at        TEXT DEFAULT NULL;

-- ─── 3. Enhance audit_logs table ──────────────────────────────────────────────
ALTER TABLE audit_logs ADD COLUMN actor_role TEXT DEFAULT NULL;
ALTER TABLE audit_logs ADD COLUMN result     TEXT NOT NULL DEFAULT 'success';
ALTER TABLE audit_logs ADD COLUMN reason     TEXT DEFAULT NULL;
ALTER TABLE audit_logs ADD COLUMN user_agent TEXT DEFAULT NULL;

-- ─── 4. Modules catalog ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS modules (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    code             TEXT    NOT NULL UNIQUE,
    name             TEXT    NOT NULL,
    description      TEXT    DEFAULT NULL,
    icon             TEXT    DEFAULT NULL,
    platform_status  TEXT    NOT NULL DEFAULT 'available',
    visibility       TEXT    NOT NULL DEFAULT 'visible',
    mobile_capable   INTEGER NOT NULL DEFAULT 0,
    requires_platform_enable INTEGER NOT NULL DEFAULT 1,
    sort_order       INTEGER NOT NULL DEFAULT 100,
    created_at       TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at       TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- ─── 5. Module capabilities ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS module_capabilities (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    module_id   INTEGER NOT NULL,
    capability  TEXT    NOT NULL,
    description TEXT    DEFAULT NULL,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    UNIQUE (module_id, capability)
);

-- ─── 6. Per-tenant module enablement ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS tenant_modules (
    id                 INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id          INTEGER NOT NULL,
    module_id          INTEGER NOT NULL,
    is_enabled         INTEGER NOT NULL DEFAULT 1,
    tenant_can_disable INTEGER NOT NULL DEFAULT 0,
    enabled_by         INTEGER DEFAULT NULL,
    enabled_at         TEXT    NOT NULL DEFAULT (datetime('now')),
    notes              TEXT    DEFAULT NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    UNIQUE (tenant_id, module_id)
);

-- ─── 7. Role capability templates ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS role_capability_templates (
    id                   INTEGER PRIMARY KEY AUTOINCREMENT,
    role_slug            TEXT    NOT NULL,
    module_capability_id INTEGER NOT NULL,
    FOREIGN KEY (module_capability_id) REFERENCES module_capabilities(id) ON DELETE CASCADE,
    UNIQUE (role_slug, module_capability_id)
);

-- ─── 8. Per-user capability overrides ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS user_capability_overrides (
    id                   INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id              INTEGER NOT NULL,
    tenant_id            INTEGER NOT NULL,
    module_capability_id INTEGER NOT NULL,
    granted              INTEGER NOT NULL DEFAULT 1,
    reason               TEXT    DEFAULT NULL,
    set_by               INTEGER DEFAULT NULL,
    created_at           TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (user_id)    REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (tenant_id)  REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (module_capability_id) REFERENCES module_capabilities(id) ON DELETE CASCADE,
    UNIQUE (user_id, module_capability_id)
);

-- ─── 9. Tenant settings ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS tenant_settings (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id       INTEGER NOT NULL UNIQUE,
    timezone        TEXT    NOT NULL DEFAULT 'UTC',
    date_format     TEXT    NOT NULL DEFAULT 'Y-m-d',
    language        TEXT    NOT NULL DEFAULT 'en',
    mobile_sync_interval_minutes INTEGER NOT NULL DEFAULT 60,
    require_device_approval      INTEGER NOT NULL DEFAULT 1,
    allow_self_registration      INTEGER NOT NULL DEFAULT 0,
    custom_fields   TEXT    DEFAULT NULL,
    updated_at      TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

-- ─── 10. Tenant contacts ──────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS tenant_contacts (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id    INTEGER NOT NULL,
    contact_type TEXT    NOT NULL DEFAULT 'primary_admin',
    name         TEXT    NOT NULL,
    email        TEXT    NOT NULL,
    phone        TEXT    DEFAULT NULL,
    title        TEXT    DEFAULT NULL,
    is_primary   INTEGER NOT NULL DEFAULT 0,
    created_at   TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

-- ─── 11. Tenant access policies ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS tenant_access_policies (
    id                       INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id                INTEGER NOT NULL UNIQUE,
    mfa_required             INTEGER NOT NULL DEFAULT 0,
    ip_whitelist             TEXT    DEFAULT NULL,
    session_timeout_minutes  INTEGER NOT NULL DEFAULT 120,
    api_access_enabled       INTEGER NOT NULL DEFAULT 1,
    mobile_access_enabled    INTEGER NOT NULL DEFAULT 1,
    platform_support_access  TEXT    NOT NULL DEFAULT 'readonly',
    updated_at               TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

-- ─── 12. Platform access log ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS platform_access_log (
    id                INTEGER PRIMARY KEY AUTOINCREMENT,
    platform_user_id  INTEGER NOT NULL,
    tenant_id         INTEGER NOT NULL,
    module_area       TEXT    DEFAULT NULL,
    reason            TEXT    NOT NULL,
    ticket_ref        TEXT    DEFAULT NULL,
    ip_address        TEXT    DEFAULT NULL,
    user_agent        TEXT    DEFAULT NULL,
    access_started_at TEXT    NOT NULL DEFAULT (datetime('now')),
    access_ended_at   TEXT    DEFAULT NULL,
    status            TEXT    NOT NULL DEFAULT 'active'
);

-- ─── 13. Tenant onboarding requests (includes migration 011: in_review) ───────
CREATE TABLE IF NOT EXISTS tenant_onboarding_requests (
    id                 INTEGER PRIMARY KEY AUTOINCREMENT,
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
    created_at         TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at         TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- ─── 14. Invitation tokens ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS invitation_tokens (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id   INTEGER NOT NULL,
    email       TEXT    NOT NULL,
    name        TEXT    DEFAULT NULL,
    role_slug   TEXT    NOT NULL DEFAULT 'airline_admin',
    token       TEXT    NOT NULL UNIQUE,
    expires_at  TEXT    NOT NULL,
    accepted_at TEXT    DEFAULT NULL,
    created_by  INTEGER DEFAULT NULL,
    created_at  TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

-- ─── 15. Mobile sync metadata ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS mobile_sync_meta (
    id                INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id         INTEGER NOT NULL,
    module_code       TEXT    NOT NULL,
    last_published_at TEXT    DEFAULT NULL,
    version_hash      TEXT    DEFAULT NULL,
    updated_at        TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE (tenant_id, module_code)
);

PRAGMA foreign_keys = ON;
