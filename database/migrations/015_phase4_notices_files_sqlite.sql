-- Phase 4: Notice Categories, Role Visibility, File Expiry
-- SQLite version

-- Notice categories per tenant (replaces hardcoded strings)
CREATE TABLE IF NOT EXISTS notice_categories (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id  INTEGER NOT NULL,
    name       TEXT    NOT NULL,
    slug       TEXT    NOT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE (tenant_id, slug)
);

-- Notice → role visibility (replaces unused target_roles TEXT column)
CREATE TABLE IF NOT EXISTS notice_role_visibility (
    notice_id INTEGER NOT NULL,
    role_id   INTEGER NOT NULL,
    PRIMARY KEY (notice_id, role_id),
    FOREIGN KEY (notice_id) REFERENCES notices(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id)   REFERENCES roles(id)   ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_notice_roles_notice ON notice_role_visibility(notice_id);
CREATE INDEX IF NOT EXISTS idx_notice_roles_role   ON notice_role_visibility(role_id);

-- Add expires_at to files table (if not already present)
-- SQLite: ALTER TABLE ... ADD COLUMN is the only supported alter
ALTER TABLE files ADD COLUMN expires_at TEXT DEFAULT NULL;
