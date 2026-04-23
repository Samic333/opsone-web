-- ─────────────────────────────────────────────────────────────────────────
--  OpsOne — Role Capability Template Backfill (2026-04-22 / Phase 9)
--  PHPMYADMIN-SAFE SQL VERSION (MariaDB/MySQL)
--
--  Equivalent to:
--    database/seeders/backfill_role_capabilities_2026_04_22.php
--
--  Use this if you cannot SSH in. Paste into phpMyAdmin → SQL tab, or
--  Import as a .sql file.
--
--  Safe properties:
--    * INSERT IGNORE → re-running is a no-op
--    * Only writes to role_capability_templates
--    * Does NOT touch users, tenants, roles, modules, or any other table
--    * No schema changes
-- ─────────────────────────────────────────────────────────────────────────

-- Before: optional sanity check — count existing rows for these roles
-- SELECT role_slug, COUNT(*) AS rows_before
--   FROM role_capability_templates
--  WHERE role_slug IN ('airline_admin','hr','scheduler','chief_pilot',
--                      'head_cabin_crew','engineering_manager','base_manager','training_admin')
--  GROUP BY role_slug;

-- ─── airline_admin — full duty_reporting capability set ──────────────────
INSERT IGNORE INTO role_capability_templates (role_slug, module_capability_id)
SELECT 'airline_admin', mc.id
  FROM module_capabilities mc
  JOIN modules m ON m.id = mc.module_id
 WHERE m.code = 'duty_reporting'
   AND mc.capability IN ('view','view_history','view_all','approve_exception',
                          'correct_record','manage_settings','export','view_audit');

-- ─── hr — duty_reporting view, manuals upload, safety_reports view/export ─
INSERT IGNORE INTO role_capability_templates (role_slug, module_capability_id)
SELECT 'hr', mc.id
  FROM module_capabilities mc
  JOIN modules m ON m.id = mc.module_id
 WHERE (m.code = 'duty_reporting' AND mc.capability IN ('view','view_history','view_all'))
    OR (m.code = 'manuals'        AND mc.capability IN ('view','upload'))
    OR (m.code = 'safety_reports' AND mc.capability IN ('view','export'));

-- ─── scheduler — duty_reporting view ─────────────────────────────────────
INSERT IGNORE INTO role_capability_templates (role_slug, module_capability_id)
SELECT 'scheduler', mc.id
  FROM module_capabilities mc
  JOIN modules m ON m.id = mc.module_id
 WHERE m.code = 'duty_reporting'
   AND mc.capability IN ('view','view_history');

-- ─── chief_pilot — duty_reporting + safety_reports review ────────────────
INSERT IGNORE INTO role_capability_templates (role_slug, module_capability_id)
SELECT 'chief_pilot', mc.id
  FROM module_capabilities mc
  JOIN modules m ON m.id = mc.module_id
 WHERE (m.code = 'duty_reporting' AND mc.capability IN ('view','view_history','approve_exception'))
    OR (m.code = 'safety_reports' AND mc.capability IN ('view','review','export'));

-- ─── head_cabin_crew — duty_reporting + safety_reports review ────────────
INSERT IGNORE INTO role_capability_templates (role_slug, module_capability_id)
SELECT 'head_cabin_crew', mc.id
  FROM module_capabilities mc
  JOIN modules m ON m.id = mc.module_id
 WHERE (m.code = 'duty_reporting' AND mc.capability IN ('view','view_history','approve_exception'))
    OR (m.code = 'safety_reports' AND mc.capability IN ('view','review','export'));

-- ─── engineering_manager — duty_reporting + safety_reports review ───────
INSERT IGNORE INTO role_capability_templates (role_slug, module_capability_id)
SELECT 'engineering_manager', mc.id
  FROM module_capabilities mc
  JOIN modules m ON m.id = mc.module_id
 WHERE (m.code = 'duty_reporting' AND mc.capability IN ('view','view_history','approve_exception'))
    OR (m.code = 'safety_reports' AND mc.capability IN ('view','review','export'));

-- ─── base_manager — duty_reporting + manuals view + ipad access ──────────
INSERT IGNORE INTO role_capability_templates (role_slug, module_capability_id)
SELECT 'base_manager', mc.id
  FROM module_capabilities mc
  JOIN modules m ON m.id = mc.module_id
 WHERE (m.code = 'duty_reporting'     AND mc.capability IN ('view','view_history','approve_exception'))
    OR (m.code = 'manuals'            AND mc.capability IN ('view'))
    OR (m.code = 'mobile_ipad_access' AND mc.capability IN ('view'));

-- ─── training_admin — compliance + ipad access ───────────────────────────
INSERT IGNORE INTO role_capability_templates (role_slug, module_capability_id)
SELECT 'training_admin', mc.id
  FROM module_capabilities mc
  JOIN modules m ON m.id = mc.module_id
 WHERE (m.code = 'compliance'         AND mc.capability IN ('view','export'))
    OR (m.code = 'mobile_ipad_access' AND mc.capability IN ('view'));

-- ─── After: verification — should show the NEW row counts per role ───────
-- SELECT rct.role_slug, m.code AS module, mc.capability
--   FROM role_capability_templates rct
--   JOIN module_capabilities mc ON mc.id = rct.module_capability_id
--   JOIN modules m ON m.id = mc.module_id
--  WHERE rct.role_slug IN ('airline_admin','hr','scheduler','chief_pilot',
--                           'head_cabin_crew','engineering_manager','base_manager','training_admin')
--    AND m.code IN ('duty_reporting','safety_reports','manuals','compliance','mobile_ipad_access')
--  ORDER BY rct.role_slug, m.code, mc.capability;
--
-- Expected new rows: 43 total across 8 roles.
