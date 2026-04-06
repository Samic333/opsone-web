-- =====================================================
-- OpsOne — Additional Tables (Migration 003)
-- Notices, App Builds, Install Logs
-- =====================================================

-- Notices / Bulletins
CREATE TABLE IF NOT EXISTS notices (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id      INTEGER NOT NULL,
    title          TEXT NOT NULL,
    body           TEXT NOT NULL,
    priority       TEXT NOT NULL DEFAULT 'normal' CHECK(priority IN ('normal','urgent','critical')),
    category       TEXT DEFAULT 'general',
    published      INTEGER NOT NULL DEFAULT 0,
    published_at   TEXT,
    expires_at     TEXT,
    created_by     INTEGER,
    created_at     TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at     TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Add explicit web_access to users table
ALTER TABLE users ADD COLUMN web_access TINYINT(1) DEFAULT 1;

-- App Builds — tracks enterprise build versions
CREATE TABLE IF NOT EXISTS app_builds (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    version        TEXT NOT NULL,
    build_number   TEXT NOT NULL,
    platform       TEXT NOT NULL DEFAULT 'ios',
    release_notes  TEXT,
    file_path      TEXT,
    file_size      INTEGER DEFAULT 0,
    min_os_version TEXT DEFAULT '16.0',
    is_active      INTEGER NOT NULL DEFAULT 1,
    uploaded_by    INTEGER,
    created_at     TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Install Access Logs — tracks who accessed the install page
CREATE TABLE IF NOT EXISTS install_logs (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id        INTEGER,
    tenant_id      INTEGER,
    action         TEXT NOT NULL DEFAULT 'page_view' CHECK(action IN ('page_view','manifest_request','build_download','instructions_view')),
    build_id       INTEGER,
    ip_address     TEXT,
    user_agent     TEXT,
    created_at     TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL,
    FOREIGN KEY (build_id) REFERENCES app_builds(id) ON DELETE SET NULL
);

-- Additional file categories for new content types
INSERT OR IGNORE INTO file_categories (tenant_id, name, slug)
SELECT id, 'Briefings', 'briefings' FROM tenants WHERE NOT EXISTS (
    SELECT 1 FROM file_categories WHERE slug = 'briefings' AND tenant_id = tenants.id
);
INSERT OR IGNORE INTO file_categories (tenant_id, name, slug)
SELECT id, 'Safety Information', 'safety_info' FROM tenants WHERE NOT EXISTS (
    SELECT 1 FROM file_categories WHERE slug = 'safety_info' AND tenant_id = tenants.id
);
INSERT OR IGNORE INTO file_categories (tenant_id, name, slug)
SELECT id, 'Company Documents', 'company_docs' FROM tenants WHERE NOT EXISTS (
    SELECT 1 FROM file_categories WHERE slug = 'company_docs' AND tenant_id = tenants.id
);
INSERT OR IGNORE INTO file_categories (tenant_id, name, slug)
SELECT id, 'Forms', 'forms' FROM tenants WHERE NOT EXISTS (
    SELECT 1 FROM file_categories WHERE slug = 'forms' AND tenant_id = tenants.id
);
