-- =====================================================
-- CrewAssist Portal — SQLite Schema
-- Development/testing database
-- =====================================================

CREATE TABLE IF NOT EXISTS tenants (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT NOT NULL,
    code        TEXT NOT NULL UNIQUE,
    contact_email TEXT,
    logo_path   TEXT,
    is_active   INTEGER NOT NULL DEFAULT 1,
    settings    TEXT,
    created_at  TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at  TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS roles (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id   INTEGER,
    name        TEXT NOT NULL,
    slug        TEXT NOT NULL,
    description TEXT,
    is_system   INTEGER NOT NULL DEFAULT 0,
    created_at  TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS departments (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id   INTEGER NOT NULL,
    name        TEXT NOT NULL,
    code        TEXT,
    created_at  TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS bases (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id   INTEGER NOT NULL,
    name        TEXT NOT NULL,
    code        TEXT NOT NULL,
    created_at  TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS users (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id     INTEGER NOT NULL,
    name          TEXT NOT NULL,
    email         TEXT NOT NULL,
    password_hash TEXT NOT NULL,
    employee_id   TEXT,
    department_id INTEGER,
    base_id       INTEGER,
    status        TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending','active','suspended','inactive')),
    mobile_access INTEGER NOT NULL DEFAULT 1,
    avatar_path   TEXT,
    last_login_at TEXT,
    created_at    TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at    TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (base_id) REFERENCES bases(id) ON DELETE SET NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_users_email_tenant ON users(email, tenant_id);

CREATE TABLE IF NOT EXISTS user_roles (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id     INTEGER NOT NULL,
    role_id     INTEGER NOT NULL,
    tenant_id   INTEGER NOT NULL,
    assigned_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE(user_id, role_id)
);

CREATE TABLE IF NOT EXISTS devices (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id       INTEGER NOT NULL,
    user_id         INTEGER NOT NULL,
    device_uuid     TEXT NOT NULL,
    platform        TEXT,
    model           TEXT,
    os_version      TEXT,
    app_version     TEXT,
    approval_status TEXT NOT NULL DEFAULT 'pending' CHECK(approval_status IN ('pending','approved','rejected','revoked')),
    approved_by     INTEGER,
    approved_at     TEXT,
    revoked_by      INTEGER,
    revoked_at      TEXT,
    notes           TEXT,
    first_login_at  TEXT NOT NULL DEFAULT (datetime('now')),
    last_sync_at    TEXT,
    created_at      TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at      TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(device_uuid, user_id)
);

CREATE TABLE IF NOT EXISTS device_approval_logs (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    device_id   INTEGER NOT NULL,
    tenant_id   INTEGER NOT NULL,
    action      TEXT NOT NULL CHECK(action IN ('registered','approved','rejected','revoked')),
    performed_by INTEGER,
    notes       TEXT,
    created_at  TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS file_categories (
    id        INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER NOT NULL,
    name      TEXT NOT NULL,
    slug      TEXT NOT NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE(slug, tenant_id)
);

CREATE TABLE IF NOT EXISTS files (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id      INTEGER NOT NULL,
    category_id    INTEGER,
    title          TEXT NOT NULL,
    description    TEXT,
    file_path      TEXT NOT NULL,
    file_name      TEXT NOT NULL,
    file_size      INTEGER DEFAULT 0,
    mime_type      TEXT,
    version        TEXT DEFAULT '1.0',
    status         TEXT NOT NULL DEFAULT 'draft' CHECK(status IN ('draft','published','archived')),
    effective_date TEXT,
    requires_ack   INTEGER NOT NULL DEFAULT 0,
    uploaded_by    INTEGER,
    created_at     TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at     TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES file_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS file_role_visibility (
    id      INTEGER PRIMARY KEY AUTOINCREMENT,
    file_id INTEGER NOT NULL,
    role_id INTEGER NOT NULL,
    FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    UNIQUE(file_id, role_id)
);

CREATE TABLE IF NOT EXISTS api_tokens (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id      INTEGER NOT NULL,
    tenant_id    INTEGER NOT NULL,
    token        TEXT NOT NULL UNIQUE,
    device_id    INTEGER,
    expires_at   TEXT NOT NULL,
    revoked      INTEGER NOT NULL DEFAULT 0,
    last_used_at TEXT,
    created_at   TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS login_activity (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER,
    tenant_id  INTEGER,
    email      TEXT NOT NULL,
    ip_address TEXT,
    user_agent TEXT,
    success    INTEGER NOT NULL,
    source     TEXT NOT NULL DEFAULT 'web' CHECK(source IN ('web','api')),
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS audit_logs (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id   INTEGER,
    user_id     INTEGER,
    user_name   TEXT,
    action      TEXT NOT NULL,
    entity_type TEXT,
    entity_id   INTEGER,
    details     TEXT,
    ip_address  TEXT,
    created_at  TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS sync_events (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id  INTEGER NOT NULL,
    user_id    INTEGER NOT NULL,
    device_id  INTEGER,
    event_type TEXT NOT NULL DEFAULT 'heartbeat',
    ip_address TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL
);
