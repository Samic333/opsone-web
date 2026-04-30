-- ────────────────────────────────────────────────────────────────────────────
-- 048_tenant_slug.sql  (MySQL / MariaDB)
-- Adds a URL-friendly `slug` column to tenants for tenant-scoped login URLs:
--     /airline/{slug}/login
-- Existing global /login keeps working unchanged.
--
-- This migration is split into FOUR statements deliberately:
--   1) Add the column with default ''
--   2) Backfill slugs from each tenant's name
--   3) De-duplicate any colliding slugs by appending -2, -3, …
--   4) Add the UNIQUE index — only safe AFTER 2+3 because step 1 leaves
--      every existing row with the same '' default.
-- Earlier versions of this file did 1+4 atomically and failed on production
-- with `Duplicate entry '' for key 'idx_tenants_slug'` whenever there were
-- ≥2 existing tenants. (See the Phase H+I deploy report for the incident.)
-- ────────────────────────────────────────────────────────────────────────────

-- 1. Add the column
ALTER TABLE `tenants`
    ADD COLUMN `slug` VARCHAR(100) NOT NULL DEFAULT '' AFTER `code`;

-- 2. Backfill slug from name: lowercase, spaces → hyphens, & → 'and',
--    strip dots/commas/apostrophes. (MySQL has no regex_replace before 8.0,
--    so this is a chained REPLACE — same rules used by Tenant::generateUniqueSlug.)
UPDATE `tenants`
SET `slug` = LOWER(
    REPLACE(
        REPLACE(
            REPLACE(
                REPLACE(
                    REPLACE(`name`, ' ', '-'),
                    '&', 'and'
                ),
                '.', ''
            ),
            ',', ''
        ),
        '''', ''
    )
)
WHERE `slug` = '';

-- 3. Append -2, -3, … to colliding slugs so step 4's UNIQUE index applies
--    cleanly. Requires MySQL 8.0+ / MariaDB 10.2+ (window functions).
UPDATE `tenants` t1
JOIN (
    SELECT id,
           slug,
           ROW_NUMBER() OVER (PARTITION BY slug ORDER BY id) AS rn
      FROM `tenants`
) t2 ON t1.id = t2.id
SET t1.slug = CONCAT(t1.slug, '-', t2.rn)
WHERE t2.rn > 1;

-- 4. Now safe to enforce uniqueness
ALTER TABLE `tenants`
    ADD UNIQUE INDEX `idx_tenants_slug` (`slug`);
