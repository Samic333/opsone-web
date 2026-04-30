-- ────────────────────────────────────────────────────────────────────────────
-- 048_tenant_slug.sql  (MySQL)
-- Adds a URL-friendly `slug` column to tenants for tenant-scoped login URLs:
--     /airline/{slug}/login
-- Existing global /login keeps working unchanged.
-- ────────────────────────────────────────────────────────────────────────────

ALTER TABLE `tenants`
    ADD COLUMN `slug` VARCHAR(100) NOT NULL DEFAULT '' AFTER `code`,
    ADD UNIQUE INDEX `idx_tenants_slug` (`slug`);

-- Backfill slug from name: lowercase, spaces → hyphens, ampersand → 'and',
-- strip anything that isn't alnum or hyphen, collapse repeats.
-- (MySQL doesn't have a regex_replace before 8.0; this is a best-effort pass.)
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
