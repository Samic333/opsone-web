-- ────────────────────────────────────────────────────────────────────────────
-- 048_tenant_slug_sqlite.sql
-- Adds a URL-friendly `slug` column to tenants for tenant-scoped login URLs:
--     /airline/{slug}/login
-- ────────────────────────────────────────────────────────────────────────────

ALTER TABLE tenants ADD COLUMN slug TEXT NOT NULL DEFAULT '';

-- Backfill: lowercase, replace spaces with hyphens, drop common punctuation
UPDATE tenants
SET slug = REPLACE(
    REPLACE(
        REPLACE(
            REPLACE(
                REPLACE(LOWER(name), ' ', '-'),
                '&', 'and'
            ),
            '.', ''
        ),
        ',', ''
    ),
    '''', ''
)
WHERE slug = '';

-- Unique index (SQLite supports CREATE UNIQUE INDEX after ALTER TABLE)
CREATE UNIQUE INDEX IF NOT EXISTS idx_tenants_slug ON tenants (slug);
