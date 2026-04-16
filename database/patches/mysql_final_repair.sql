-- -----------------------------------------------------
-- FINAL DATABASE REPAIR & SYNC (Live to Local Alignment)
-- -----------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;

-- 1. CLEANUP DUPLICATE ROLES
-- Keep only the newest roles (highest IDs) for each slug/tenant pair and delete the rest
DELETE r1 FROM roles r1
INNER JOIN roles r2 
WHERE r1.id < r2.id 
  AND r1.slug = r2.slug 
  AND (r1.tenant_id = r2.tenant_id OR (r1.tenant_id IS NULL AND r2.tenant_id IS NULL));

-- 2. ENSURE PLATFORM ROLES ARE CORRECTLY MARKED
UPDATE roles SET role_type = 'platform', is_system = 1 WHERE slug IN ('super_admin', 'platform_support', 'platform_security', 'system_monitoring') AND tenant_id IS NULL;

-- 3. RESET ROLE ASSIGNMENTS FOR PLATFORM STAFF (Removing duplicates/orphans)
DELETE FROM user_roles WHERE user_id IN (276, 277, 278, 279);

SET FOREIGN_KEY_CHECKS = 0;

-- 4. RE-ASSIGN PLATFORM ROLES
-- Alex Mwangi (Super Admin)
INSERT INTO user_roles (user_id, role_id, tenant_id) 
SELECT 276, id, NULL FROM roles WHERE slug = 'super_admin' AND tenant_id IS NULL LIMIT 1;

-- Jordan Taylor (Platform Support)
INSERT INTO user_roles (user_id, role_id, tenant_id) 
SELECT 277, id, NULL FROM roles WHERE slug = 'platform_support' AND tenant_id IS NULL LIMIT 1;

-- Sarah Kimani (Platform Security)
INSERT INTO user_roles (user_id, role_id, tenant_id) 
SELECT 278, id, NULL FROM roles WHERE slug = 'platform_security' AND tenant_id IS NULL LIMIT 1;

-- James Okafor (System Monitoring)
INSERT INTO user_roles (user_id, role_id, tenant_id) 
SELECT 279, id, NULL FROM roles WHERE slug = 'system_monitoring' AND tenant_id IS NULL LIMIT 1;

-- 5. ENSURE AIRLINE DEMO ROLES (Tenant 1) ARE CORRECT
-- Clearing existing assignments for these to ensure no duplicates
DELETE FROM user_roles WHERE user_id BETWEEN 280 AND 303;

-- Re-assign based on specific slugs to match local exactly
INSERT INTO user_roles (user_id, role_id, tenant_id) SELECT 280, id, 1 FROM roles WHERE slug = 'airline_admin' AND tenant_id = 1 LIMIT 1;
INSERT INTO user_roles (user_id, role_id, tenant_id) SELECT 281, id, 1 FROM roles WHERE slug = 'hr' AND tenant_id = 1 LIMIT 1;
INSERT INTO user_roles (user_id, role_id, tenant_id) SELECT 282, id, 1 FROM roles WHERE slug = 'scheduler' AND tenant_id = 1 LIMIT 1;
INSERT INTO user_roles (user_id, role_id, tenant_id) SELECT 283, id, 1 FROM roles WHERE slug = 'chief_pilot' AND tenant_id = 1 LIMIT 1;
INSERT INTO user_roles (user_id, role_id, tenant_id) SELECT 284, id, 1 FROM roles WHERE slug = 'head_cabin_crew' AND tenant_id = 1 LIMIT 1;
INSERT INTO user_roles (user_id, role_id, tenant_id) SELECT 285, id, 1 FROM roles WHERE slug = 'engineering_manager' AND tenant_id = 1 LIMIT 1;
INSERT INTO user_roles (user_id, role_id, tenant_id) SELECT 286, id, 1 FROM roles WHERE slug = 'safety_officer' AND tenant_id = 1 LIMIT 1;
INSERT INTO user_roles (user_id, role_id, tenant_id) SELECT 287, id, 1 FROM roles WHERE slug = 'fdm_analyst' AND tenant_id = 1 LIMIT 1;
INSERT INTO user_roles (user_id, role_id, tenant_id) SELECT 288, id, 1 FROM roles WHERE slug = 'document_control' AND tenant_id = 1 LIMIT 1;
INSERT INTO user_roles (user_id, role_id, tenant_id) SELECT 289, id, 1 FROM roles WHERE slug = 'base_manager' AND tenant_id = 1 LIMIT 1;
INSERT INTO user_roles (user_id, role_id, tenant_id) SELECT 290, id, 1 FROM roles WHERE slug = 'pilot' AND tenant_id = 1 LIMIT 1;
INSERT INTO user_roles (user_id, role_id, tenant_id) SELECT 291, id, 1 FROM roles WHERE slug = 'cabin_crew' AND tenant_id = 1 LIMIT 1;
INSERT INTO user_roles (user_id, role_id, tenant_id) SELECT 292, id, 1 FROM roles WHERE slug = 'engineer' AND tenant_id = 1 LIMIT 1;
INSERT INTO user_roles (user_id, role_id, tenant_id) SELECT 293, id, 1 FROM roles WHERE slug = 'training_admin' AND tenant_id = 1 LIMIT 1;

SET FOREIGN_KEY_CHECKS = 1;
