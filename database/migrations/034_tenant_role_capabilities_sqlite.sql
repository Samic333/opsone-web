-- Per-tenant role capability overrides.
-- When a row exists here, it wins over the system-wide role_capability_templates.
-- allowed=1 → grant; allowed=0 → explicit revoke.

CREATE TABLE IF NOT EXISTS tenant_role_capabilities (
    id                    INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id             INTEGER NOT NULL,
    role_slug             TEXT    NOT NULL,
    module_capability_id  INTEGER NOT NULL,
    allowed               INTEGER NOT NULL DEFAULT 1,
    updated_by            INTEGER DEFAULT NULL,
    updated_at            TEXT    NOT NULL DEFAULT (datetime('now')),
    UNIQUE (tenant_id, role_slug, module_capability_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (module_capability_id) REFERENCES module_capabilities(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_trc_tenant ON tenant_role_capabilities(tenant_id);
CREATE INDEX IF NOT EXISTS idx_trc_role   ON tenant_role_capabilities(tenant_id, role_slug);
