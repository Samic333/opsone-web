-- -----------------------------------------------------
-- FINAL DATABASE REPAIR & SYNC (Live to Local Alignment)
-- Run this ONCE in phpMyAdmin. Safe to re-run.
-- -----------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- STEP 1: Fix user_roles.tenant_id to allow NULL
-- Platform staff have NO tenant, so this column MUST be nullable.
-- =====================================================
ALTER TABLE `user_roles` MODIFY COLUMN `tenant_id` INT UNSIGNED DEFAULT NULL;

-- =====================================================
-- STEP 2: CLEANUP DUPLICATE ROLES
-- The roles table has ~30 duplicate sets. Keep only the
-- FIRST (lowest ID) for each unique slug+tenant_id pair.
-- =====================================================
DELETE r1 FROM `roles` r1
INNER JOIN `roles` r2 
  ON r1.slug = r2.slug
  AND (r1.tenant_id <=> r2.tenant_id)
  AND r1.id > r2.id;

-- =====================================================
-- STEP 3: Ensure the 4 platform roles exist and are
-- correctly typed. After dedup, these should be the only
-- ones with tenant_id IS NULL and role_type = 'platform'.
-- =====================================================
UPDATE `roles` SET role_type = 'platform', is_system = 1
WHERE slug IN ('super_admin', 'platform_support', 'platform_security', 'system_monitoring')
  AND tenant_id IS NULL;

-- =====================================================
-- STEP 4: Wipe all role assignments for demo users
-- (IDs 276-303) so we can cleanly re-assign them.
-- =====================================================
DELETE FROM `user_roles` WHERE user_id BETWEEN 276 AND 303;

-- =====================================================
-- STEP 5: Assign PLATFORM roles (tenant_id = NULL)
-- =====================================================
-- Alex Mwangi → super_admin
INSERT INTO `user_roles` (user_id, role_id, tenant_id)
SELECT 276, id, NULL FROM `roles`
WHERE slug = 'super_admin' AND tenant_id IS NULL LIMIT 1;

-- Jordan Taylor → platform_support
INSERT INTO `user_roles` (user_id, role_id, tenant_id)
SELECT 277, id, NULL FROM `roles`
WHERE slug = 'platform_support' AND tenant_id IS NULL LIMIT 1;

-- Sarah Kimani → platform_security
INSERT INTO `user_roles` (user_id, role_id, tenant_id)
SELECT 278, id, NULL FROM `roles`
WHERE slug = 'platform_security' AND tenant_id IS NULL LIMIT 1;

-- James Okafor → system_monitoring
INSERT INTO `user_roles` (user_id, role_id, tenant_id)
SELECT 279, id, NULL FROM `roles`
WHERE slug = 'system_monitoring' AND tenant_id IS NULL LIMIT 1;

-- =====================================================
-- STEP 6: Assign AIRLINE roles (tenant_id = 1)
-- Using tenant-scoped roles where available, falling
-- back to system roles if tenant-scoped don't exist.
-- =====================================================
INSERT INTO `user_roles` (user_id, role_id, tenant_id)
SELECT 280, COALESCE(
  (SELECT id FROM roles WHERE slug='airline_admin' AND tenant_id=1 LIMIT 1),
  (SELECT id FROM roles WHERE slug='airline_admin' AND tenant_id IS NULL LIMIT 1)
), 1;

INSERT INTO `user_roles` (user_id, role_id, tenant_id)
SELECT 281, COALESCE(
  (SELECT id FROM roles WHERE slug='hr' AND tenant_id=1 LIMIT 1),
  (SELECT id FROM roles WHERE slug='hr' AND tenant_id IS NULL LIMIT 1)
), 1;

INSERT INTO `user_roles` (user_id, role_id, tenant_id)
SELECT 282, COALESCE(
  (SELECT id FROM roles WHERE slug='scheduler' AND tenant_id=1 LIMIT 1),
  (SELECT id FROM roles WHERE slug='scheduler' AND tenant_id IS NULL LIMIT 1)
), 1;

INSERT INTO `user_roles` (user_id, role_id, tenant_id)
SELECT 283, COALESCE(
  (SELECT id FROM roles WHERE slug='chief_pilot' AND tenant_id=1 LIMIT 1),
  (SELECT id FROM roles WHERE slug='chief_pilot' AND tenant_id IS NULL LIMIT 1)
), 1;

INSERT INTO `user_roles` (user_id, role_id, tenant_id)
SELECT 284, COALESCE(
  (SELECT id FROM roles WHERE slug='head_cabin_crew' AND tenant_id=1 LIMIT 1),
  (SELECT id FROM roles WHERE slug='head_cabin_crew' AND tenant_id IS NULL LIMIT 1)
), 1;

INSERT INTO `user_roles` (user_id, role_id, tenant_id)
SELECT 285, COALESCE(
  (SELECT id FROM roles WHERE slug='engineering_manager' AND tenant_id=1 LIMIT 1),
  (SELECT id FROM roles WHERE slug='engineering_manager' AND tenant_id IS NULL LIMIT 1)
), 1;

INSERT INTO `user_roles` (user_id, role_id, tenant_id)
SELECT 286, COALESCE(
  (SELECT id FROM roles WHERE slug='safety_officer' AND tenant_id=1 LIMIT 1),
  (SELECT id FROM roles WHERE slug='safety_officer' AND tenant_id IS NULL LIMIT 1)
), 1;

INSERT INTO `user_roles` (user_id, role_id, tenant_id)
SELECT 287, COALESCE(
  (SELECT id FROM roles WHERE slug='fdm_analyst' AND tenant_id=1 LIMIT 1),
  (SELECT id FROM roles WHERE slug='fdm_analyst' AND tenant_id IS NULL LIMIT 1)
), 1;

INSERT INTO `user_roles` (user_id, role_id, tenant_id)
SELECT 288, COALESCE(
  (SELECT id FROM roles WHERE slug='document_control' AND tenant_id=1 LIMIT 1),
  (SELECT id FROM roles WHERE slug='document_control' AND tenant_id IS NULL LIMIT 1)
), 1;

INSERT INTO `user_roles` (user_id, role_id, tenant_id)
SELECT 289, COALESCE(
  (SELECT id FROM roles WHERE slug='base_manager' AND tenant_id=1 LIMIT 1),
  (SELECT id FROM roles WHERE slug='base_manager' AND tenant_id IS NULL LIMIT 1)
), 1;

INSERT INTO `user_roles` (user_id, role_id, tenant_id)
SELECT 290, COALESCE(
  (SELECT id FROM roles WHERE slug='pilot' AND tenant_id=1 LIMIT 1),
  (SELECT id FROM roles WHERE slug='pilot' AND tenant_id IS NULL LIMIT 1)
), 1;

INSERT INTO `user_roles` (user_id, role_id, tenant_id)
SELECT 291, COALESCE(
  (SELECT id FROM roles WHERE slug='cabin_crew' AND tenant_id=1 LIMIT 1),
  (SELECT id FROM roles WHERE slug='cabin_crew' AND tenant_id IS NULL LIMIT 1)
), 1;

INSERT INTO `user_roles` (user_id, role_id, tenant_id)
SELECT 292, COALESCE(
  (SELECT id FROM roles WHERE slug='engineer' AND tenant_id=1 LIMIT 1),
  (SELECT id FROM roles WHERE slug='engineer' AND tenant_id IS NULL LIMIT 1)
), 1;

INSERT INTO `user_roles` (user_id, role_id, tenant_id)
SELECT 293, COALESCE(
  (SELECT id FROM roles WHERE slug='training_admin' AND tenant_id=1 LIMIT 1),
  (SELECT id FROM roles WHERE slug='training_admin' AND tenant_id IS NULL LIMIT 1)
), 1;

SET FOREIGN_KEY_CHECKS = 1;

-- Done! Log out and log back in to refresh your session.
