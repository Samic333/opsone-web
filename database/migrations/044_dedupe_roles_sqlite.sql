-- Migration 044 (SQLite) — Dedupe roles and add partial UNIQUE constraints.
--
-- Problem: roles has no UNIQUE on (tenant_id, slug). phase0_seed.php and
-- earlier migrations inserted multiple system rows per slug as the role-type
-- model evolved. TenantController::store then clones every system row into
-- the new tenant, multiplying duplicates per tenant. DemoAir (created in
-- Phase 3 of the 2026-04-26 remediation) had 2-3 rows for every role slug.
--
-- Strategy:
--   1. For every (tenant_id, slug) cluster, keep the row with the MAX id.
--      (The MAX picks the most recent insert — newer rows reflect the latest
--      role_type architecture-correction values, e.g. role_type='tenant'
--      vs the historical role_type='end_user' on the original phase-0 row.)
--   2. Migrate user_roles links onto that surviving id.
--   3. Delete the duplicates.
--   4. Add a unique index per cluster.
--
-- Idempotent: rerun after the first apply is a no-op (the indexes are
-- IF NOT EXISTS, the duplicate removal pass affects 0 rows).

BEGIN TRANSACTION;

-- 1+2. Repoint user_roles to the surviving id (max id) per cluster.
UPDATE user_roles
   SET role_id = (
       SELECT MAX(r2.id)
         FROM roles r2
         JOIN roles r1 ON r1.id = user_roles.role_id
        WHERE COALESCE(r2.tenant_id, -1) = COALESCE(r1.tenant_id, -1)
          AND r2.slug = r1.slug
   )
 WHERE role_id IN (
     SELECT id FROM roles WHERE id NOT IN (
         SELECT MAX(id) FROM roles GROUP BY COALESCE(tenant_id, -1), slug
     )
 );

-- 3. Delete the now-orphaned non-canonical role rows.
DELETE FROM roles
 WHERE id NOT IN (
     SELECT MAX(id) FROM roles GROUP BY COALESCE(tenant_id, -1), slug
 );

-- 4. Prevent recurrence with two partial unique indexes:
--    (a) per-tenant slug uniqueness
--    (b) system slug uniqueness (where tenant_id IS NULL)
CREATE UNIQUE INDEX IF NOT EXISTS uq_roles_tenant_slug
    ON roles(tenant_id, slug)
    WHERE tenant_id IS NOT NULL;

CREATE UNIQUE INDEX IF NOT EXISTS uq_roles_system_slug
    ON roles(slug)
    WHERE tenant_id IS NULL;

COMMIT;
