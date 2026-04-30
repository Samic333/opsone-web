-- ────────────────────────────────────────────────────────────────────────────
-- 048_tenant_slug_sqlite.sql
-- Adds a URL-friendly `slug` column to tenants for tenant-scoped login URLs:
--     /airline/{slug}/login
--
-- Steps mirror the MySQL variant (column → backfill → dedup → unique index).
-- Requires SQLite 3.25+ for ROW_NUMBER() in step 3 — every supported PHP
-- runtime ships a newer SQLite, so this is safe.
-- ────────────────────────────────────────────────────────────────────────────

-- 1. Add the column
ALTER TABLE tenants ADD COLUMN slug TEXT NOT NULL DEFAULT '';

-- 2. Backfill: lowercase, replace spaces with hyphens, drop common punctuation
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

-- 3. Append -2, -3, … to colliding slugs so step 4's UNIQUE index applies cleanly.
UPDATE tenants
SET slug = slug || '-' || (
    SELECT rn FROM (
        SELECT id, slug, ROW_NUMBER() OVER (PARTITION BY slug ORDER BY id) AS rn
          FROM tenants
    ) AS t WHERE t.id = tenants.id
)
WHERE id IN (
    SELECT id FROM (
        SELECT id, slug, ROW_NUMBER() OVER (PARTITION BY slug ORDER BY id) AS rn
          FROM tenants
    ) AS t WHERE t.rn > 1
);

-- 4. Now safe to enforce uniqueness
CREATE UNIQUE INDEX IF NOT EXISTS idx_tenants_slug ON tenants (slug);
