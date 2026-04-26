-- Migration 044 (MySQL) — Dedupe roles and add UNIQUE constraints.
-- Mirror of 044_dedupe_roles_sqlite.sql. See that file for rationale.
-- MySQL does not support partial indexes, so the system-roles uniqueness
-- relies on (tenant_id, slug) where tenant_id IS NULL collapses to NULL
-- and MySQL treats NULLs as distinct under UNIQUE — meaning system-role
-- duplicates are NOT prevented at index level on MySQL. For MySQL we add
-- a procedural pre-insert check via the seeders instead. Application-level
-- guard: phase0_seed.php uses INSERT IGNORE keyed on (slug, tenant_id),
-- and after this migration the seeders should be normalised so that the
-- unique key works. See follow-up patch script.

DELIMITER $$
DROP PROCEDURE IF EXISTS apply_044 $$
CREATE PROCEDURE apply_044()
BEGIN
    -- 1+2. Repoint user_roles to the surviving id.
    UPDATE user_roles ur
      JOIN roles r1 ON r1.id = ur.role_id
       SET ur.role_id = (
           SELECT MAX(r2.id) FROM roles r2
            WHERE r2.slug = r1.slug
              AND ((r1.tenant_id IS NULL AND r2.tenant_id IS NULL)
                OR (r1.tenant_id = r2.tenant_id))
       )
     WHERE ur.role_id NOT IN (
         SELECT id FROM (
             SELECT MAX(id) AS id FROM roles GROUP BY tenant_id, slug
         ) keep_set
     );

    -- 3. Delete the orphaned duplicates.
    DELETE FROM roles
     WHERE id NOT IN (
         SELECT id FROM (
             SELECT MAX(id) AS id FROM roles GROUP BY tenant_id, slug
         ) keep_set
     );

    -- 4. Add the unique index (note MySQL caveat above).
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'roles'
           AND INDEX_NAME = 'uq_roles_tenant_slug'
    ) THEN
        CREATE UNIQUE INDEX uq_roles_tenant_slug ON roles(tenant_id, slug);
    END IF;
END $$
DELIMITER ;

CALL apply_044();
DROP PROCEDURE apply_044;
