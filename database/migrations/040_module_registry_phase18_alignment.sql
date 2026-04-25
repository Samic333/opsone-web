-- Migration 040 — Module registry alignment for the Phase 18 Premium Sync pass.
--
-- Adds catalog entries for the iPad-facing modules that the existing seed
-- (phase0_seed.php / demo_seed.php) didn't include. NavigationService::moduleEnabled
-- treats any module code that's not in the `modules` table as DISABLED, so until
-- these rows exist, web/admin module gating cannot be configured for them and
-- the iPad sidebar items have no enable/disable knob in admin.
--
-- This migration is **additive only** (INSERT IGNORE / safe). No DDL.
-- It does NOT enable the new modules for any tenant by default — platform admin
-- still chooses what to enable per airline (correct behaviour).
-- Companion seeder rows for capabilities + role templates are at the bottom.

START TRANSACTION;

-- 1. Module catalog ----------------------------------------------------------
INSERT IGNORE INTO `modules` (`code`, `name`, `description`, `icon`, `mobile_capable`, `sort_order`, `platform_status`) VALUES
  ('duty_reporting',  'Duty Reporting',         'Crew check-in / clock-out / duty exception reporting',                          '⏱',  1, 130, 'available'),
  ('flight_folder',   'Flight Folder',          'Per-flight folder of operational forms (journey log, risk, briefing, navlog, post-arrival, verification, after-mission)', '📁', 1, 140, 'available'),
  ('per_diem',        'Per Diem & Parting',     'Per-diem rates, claim submission, and approval workflow',                       '💰', 1, 150, 'available'),
  ('appraisals',      'Appraisals',             'Crew appraisals — write, receive, review, ratings dashboard',                   '⭐', 1, 160, 'available'),
  ('logbook',         'Electronic Logbook',     'Pilot/crew electronic logbook entries fed from journey log',                    '📓', 1, 170, 'available'),
  ('help',            'Help & Support',         'In-app help centre, FAQs, and support request submission',                      '❓', 1, 180, 'available'),
  ('reports',         'Operational Reports',    'Operational (non-safety) reports — airstrip/runway, after-mission, verification', '📋', 1, 190, 'available'),
  ('notifications',   'Notifications Inbox',    'Unified notifications inbox (flight assignments, FDM, ack prompts, safety follow-ups) — distinct from `notices`', '🔔', 1, 55, 'available');

-- 2. Module capabilities -----------------------------------------------------
-- duty_reporting
INSERT IGNORE INTO `module_capabilities` (`module_id`, `capability`, `description`)
SELECT m.id, c.cap, c.descr FROM `modules` m
JOIN (
  SELECT 'view'         AS cap, 'View own duty status / history' AS descr UNION ALL
  SELECT 'check_in',     'Submit duty check-in' UNION ALL
  SELECT 'clock_out',    'Submit duty clock-out / end of duty' UNION ALL
  SELECT 'review',       'Review submitted duty exceptions' UNION ALL
  SELECT 'approve',      'Approve / reject duty exceptions' UNION ALL
  SELECT 'export',       'Export duty data'
) c ON 1=1 WHERE m.code = 'duty_reporting';

-- flight_folder (per-doc-type capabilities mirror migration 036's doc_type ENUM)
INSERT IGNORE INTO `module_capabilities` (`module_id`, `capability`, `description`)
SELECT m.id, c.cap, c.descr FROM `modules` m
JOIN (
  SELECT 'view'                       AS cap, 'View own flight folders' AS descr UNION ALL
  SELECT 'submit_journey_log',         'Submit journey log entry' UNION ALL
  SELECT 'submit_risk_assessment',     'Submit flight risk assessment' UNION ALL
  SELECT 'submit_crew_briefing',       'Submit crew briefing sheet' UNION ALL
  SELECT 'submit_navlog',              'Submit navigation log' UNION ALL
  SELECT 'submit_post_arrival',        'Submit post-arrival checklist' UNION ALL
  SELECT 'submit_verification',        'Submit pre-flight verification' UNION ALL
  SELECT 'submit_after_mission_pilot', 'Submit pilot after-mission report' UNION ALL
  SELECT 'submit_after_mission_cabin', 'Submit cabin-crew after-mission report' UNION ALL
  SELECT 'review',                     'Review (base/station manager) submitted folder docs' UNION ALL
  SELECT 'approve',                    'Approve / lock submitted folder docs' UNION ALL
  SELECT 'export',                     'Export folder data'
) c ON 1=1 WHERE m.code = 'flight_folder';

-- per_diem
INSERT IGNORE INTO `module_capabilities` (`module_id`, `capability`, `description`)
SELECT m.id, c.cap, c.descr FROM `modules` m
JOIN (
  SELECT 'view'   AS cap, 'View own per-diem entitlement / claims' AS descr UNION ALL
  SELECT 'claim',  'Submit per-diem claim' UNION ALL
  SELECT 'review', 'Review submitted claims' UNION ALL
  SELECT 'approve','Approve / reject claims' UNION ALL
  SELECT 'export', 'Export claims data'
) c ON 1=1 WHERE m.code = 'per_diem';

-- appraisals
INSERT IGNORE INTO `module_capabilities` (`module_id`, `capability`, `description`)
SELECT m.id, c.cap, c.descr FROM `modules` m
JOIN (
  SELECT 'view'        AS cap, 'View own appraisal inbox / received' AS descr UNION ALL
  SELECT 'write',       'Write appraisal of another crew' UNION ALL
  SELECT 'review',      'Review submitted appraisals (manager)' UNION ALL
  SELECT 'manage',      'Manage appraisal templates / rating scales' UNION ALL
  SELECT 'export',      'Export appraisals data'
) c ON 1=1 WHERE m.code = 'appraisals';

-- logbook
INSERT IGNORE INTO `module_capabilities` (`module_id`, `capability`, `description`)
SELECT m.id, c.cap, c.descr FROM `modules` m
JOIN (
  SELECT 'view'   AS cap, 'View own logbook entries' AS descr UNION ALL
  SELECT 'submit', 'Submit logbook entry' UNION ALL
  SELECT 'edit',   'Edit own draft logbook entries' UNION ALL
  SELECT 'review', 'Review (HR/CP) submitted logbook entries' UNION ALL
  SELECT 'export', 'Export logbook data'
) c ON 1=1 WHERE m.code = 'logbook';

-- help
INSERT IGNORE INTO `module_capabilities` (`module_id`, `capability`, `description`)
SELECT m.id, c.cap, c.descr FROM `modules` m
JOIN (
  SELECT 'view'           AS cap, 'View help/FAQs/contacts' AS descr UNION ALL
  SELECT 'submit_request', 'Submit help / support request' UNION ALL
  SELECT 'manage_content', 'Manage help content (admin)' UNION ALL
  SELECT 'manage_tickets', 'Triage / respond to support requests'
) c ON 1=1 WHERE m.code = 'help';

-- reports (operational, non-safety)
INSERT IGNORE INTO `module_capabilities` (`module_id`, `capability`, `description`)
SELECT m.id, c.cap, c.descr FROM `modules` m
JOIN (
  SELECT 'view'                  AS cap, 'View own submitted operational reports' AS descr UNION ALL
  SELECT 'submit_after_mission',  'Submit after-mission operational report' UNION ALL
  SELECT 'submit_airstrip',       'Submit airstrip / runway condition report' UNION ALL
  SELECT 'submit_verification',   'Submit verification report' UNION ALL
  SELECT 'view_all',              'View all reports for the tenant (manager)' UNION ALL
  SELECT 'review',                'Review submitted reports' UNION ALL
  SELECT 'export',                'Export reports data'
) c ON 1=1 WHERE m.code = 'reports';

-- notifications (inbox — distinct from notices)
INSERT IGNORE INTO `module_capabilities` (`module_id`, `capability`, `description`)
SELECT m.id, c.cap, c.descr FROM `modules` m
JOIN (
  SELECT 'view'        AS cap, 'View notification inbox' AS descr UNION ALL
  SELECT 'acknowledge', 'Acknowledge notifications that require it' UNION ALL
  SELECT 'create',      'Create / send a notification (admin)' UNION ALL
  SELECT 'manage',      'Manage notification templates / channels'
) c ON 1=1 WHERE m.code = 'notifications';

-- 3. Role-capability templates (defaults) -----------------------------------
-- Pilots / cabin crew / engineers — the crew baseline (views + own submits).
INSERT IGNORE INTO `role_capability_templates` (`role_slug`, `module_capability_id`)
SELECT r.role_slug, mc.id FROM `module_capabilities` mc
JOIN `modules` m ON m.id = mc.module_id
JOIN (
  SELECT 'pilot' AS role_slug UNION ALL
  SELECT 'cabin_crew' UNION ALL
  SELECT 'engineer'
) r ON 1=1
WHERE
   (m.code = 'duty_reporting'  AND mc.capability IN ('view','check_in','clock_out'))
OR (m.code = 'flight_folder'   AND mc.capability IN ('view','submit_journey_log','submit_risk_assessment','submit_crew_briefing','submit_navlog','submit_post_arrival','submit_verification'))
OR (m.code = 'per_diem'        AND mc.capability IN ('view','claim'))
OR (m.code = 'appraisals'      AND mc.capability IN ('view','write'))
OR (m.code = 'logbook'         AND mc.capability IN ('view','submit','edit'))
OR (m.code = 'help'            AND mc.capability IN ('view','submit_request'))
OR (m.code = 'reports'         AND mc.capability IN ('view','submit_after_mission','submit_airstrip','submit_verification'))
OR (m.code = 'notifications'   AND mc.capability IN ('view','acknowledge'));

-- Pilot-only after-mission_pilot; cabin-only after-mission_cabin
INSERT IGNORE INTO `role_capability_templates` (`role_slug`, `module_capability_id`)
SELECT 'pilot', mc.id FROM `module_capabilities` mc
JOIN `modules` m ON m.id = mc.module_id
WHERE m.code = 'flight_folder' AND mc.capability = 'submit_after_mission_pilot';

INSERT IGNORE INTO `role_capability_templates` (`role_slug`, `module_capability_id`)
SELECT 'cabin_crew', mc.id FROM `module_capabilities` mc
JOIN `modules` m ON m.id = mc.module_id
WHERE m.code = 'flight_folder' AND mc.capability = 'submit_after_mission_cabin';

-- Base manager / scheduler / chief pilot — review surfaces.
INSERT IGNORE INTO `role_capability_templates` (`role_slug`, `module_capability_id`)
SELECT r.role_slug, mc.id FROM `module_capabilities` mc
JOIN `modules` m ON m.id = mc.module_id
JOIN (
  SELECT 'base_manager' AS role_slug UNION ALL
  SELECT 'chief_pilot' UNION ALL
  SELECT 'scheduler'
) r ON 1=1
WHERE
   (m.code = 'duty_reporting'  AND mc.capability IN ('view','review','approve','export'))
OR (m.code = 'flight_folder'   AND mc.capability IN ('view','review','approve','export'))
OR (m.code = 'reports'         AND mc.capability IN ('view','view_all','review','export'))
OR (m.code = 'notifications'   AND mc.capability IN ('view','create','acknowledge'))
OR (m.code = 'logbook'         AND mc.capability IN ('view','review','export'))
OR (m.code = 'appraisals'      AND mc.capability IN ('view','review','export'));

-- HR — appraisal + logbook + per-diem review/export.
INSERT IGNORE INTO `role_capability_templates` (`role_slug`, `module_capability_id`)
SELECT 'hr', mc.id FROM `module_capabilities` mc
JOIN `modules` m ON m.id = mc.module_id
WHERE
   (m.code = 'appraisals' AND mc.capability IN ('view','write','review','manage','export'))
OR (m.code = 'logbook'    AND mc.capability IN ('view','review','export'))
OR (m.code = 'per_diem'   AND mc.capability IN ('view','review','approve','export'))
OR (m.code = 'help'       AND mc.capability IN ('view','manage_tickets'));

-- Airline admin — full kit on all the new modules.
INSERT IGNORE INTO `role_capability_templates` (`role_slug`, `module_capability_id`)
SELECT 'airline_admin', mc.id FROM `module_capabilities` mc
JOIN `modules` m ON m.id = mc.module_id
WHERE m.code IN ('duty_reporting','flight_folder','per_diem','appraisals','logbook','help','reports','notifications');

-- 4. Demo tenant (id=1) — enable the new modules so dev/test sees them.
--    Production tenants are not auto-enabled; that remains a platform-admin choice.
INSERT IGNORE INTO `tenant_modules` (`tenant_id`, `module_id`, `is_enabled`)
SELECT 1, id, 1 FROM `modules`
WHERE code IN ('duty_reporting','flight_folder','per_diem','appraisals','logbook','help','reports','notifications');

COMMIT;
