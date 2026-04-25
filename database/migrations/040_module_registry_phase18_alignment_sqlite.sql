-- Migration 040 (SQLite) — Module registry alignment for the Phase 18 Premium Sync pass.
-- See 040_module_registry_phase18_alignment.sql (MySQL) for full rationale.
-- Additive only — no DDL.

BEGIN TRANSACTION;

-- 1. Module catalog
INSERT OR IGNORE INTO modules (code, name, description, icon, mobile_capable, sort_order, platform_status) VALUES
  ('duty_reporting',  'Duty Reporting',         'Crew check-in / clock-out / duty exception reporting',                          '⏱',  1, 130, 'available'),
  ('flight_folder',   'Flight Folder',          'Per-flight folder of operational forms (journey log, risk, briefing, navlog, post-arrival, verification, after-mission)', '📁', 1, 140, 'available'),
  ('per_diem',        'Per Diem & Parting',     'Per-diem rates, claim submission, and approval workflow',                       '💰', 1, 150, 'available'),
  ('appraisals',      'Appraisals',             'Crew appraisals — write, receive, review, ratings dashboard',                   '⭐', 1, 160, 'available'),
  ('logbook',         'Electronic Logbook',     'Pilot/crew electronic logbook entries fed from journey log',                    '📓', 1, 170, 'available'),
  ('help',            'Help & Support',         'In-app help centre, FAQs, and support request submission',                      '❓', 1, 180, 'available'),
  ('reports',         'Operational Reports',    'Operational (non-safety) reports — airstrip/runway, after-mission, verification', '📋', 1, 190, 'available'),
  ('notifications',   'Notifications Inbox',    'Unified notifications inbox (flight assignments, FDM, ack prompts, safety follow-ups) — distinct from notices', '🔔', 1, 55, 'available');

-- 2. Module capabilities
-- duty_reporting
INSERT OR IGNORE INTO module_capabilities (module_id, capability, description)
SELECT m.id, c.cap, c.descr FROM modules m
JOIN (
  SELECT 'view' AS cap,         'View own duty status / history' AS descr UNION ALL
  SELECT 'check_in',            'Submit duty check-in' UNION ALL
  SELECT 'clock_out',           'Submit duty clock-out / end of duty' UNION ALL
  SELECT 'review',              'Review submitted duty exceptions' UNION ALL
  SELECT 'approve',             'Approve / reject duty exceptions' UNION ALL
  SELECT 'export',              'Export duty data'
) c WHERE m.code = 'duty_reporting';

-- flight_folder
INSERT OR IGNORE INTO module_capabilities (module_id, capability, description)
SELECT m.id, c.cap, c.descr FROM modules m
JOIN (
  SELECT 'view' AS cap,                       'View own flight folders' AS descr UNION ALL
  SELECT 'submit_journey_log',                 'Submit journey log entry' UNION ALL
  SELECT 'submit_risk_assessment',             'Submit flight risk assessment' UNION ALL
  SELECT 'submit_crew_briefing',               'Submit crew briefing sheet' UNION ALL
  SELECT 'submit_navlog',                      'Submit navigation log' UNION ALL
  SELECT 'submit_post_arrival',                'Submit post-arrival checklist' UNION ALL
  SELECT 'submit_verification',                'Submit pre-flight verification' UNION ALL
  SELECT 'submit_after_mission_pilot',         'Submit pilot after-mission report' UNION ALL
  SELECT 'submit_after_mission_cabin',         'Submit cabin-crew after-mission report' UNION ALL
  SELECT 'review',                             'Review (base/station manager) submitted folder docs' UNION ALL
  SELECT 'approve',                            'Approve / lock submitted folder docs' UNION ALL
  SELECT 'export',                             'Export folder data'
) c WHERE m.code = 'flight_folder';

-- per_diem
INSERT OR IGNORE INTO module_capabilities (module_id, capability, description)
SELECT m.id, c.cap, c.descr FROM modules m
JOIN (
  SELECT 'view' AS cap, 'View own per-diem entitlement / claims' AS descr UNION ALL
  SELECT 'claim',        'Submit per-diem claim' UNION ALL
  SELECT 'review',       'Review submitted claims' UNION ALL
  SELECT 'approve',      'Approve / reject claims' UNION ALL
  SELECT 'export',       'Export claims data'
) c WHERE m.code = 'per_diem';

-- appraisals
INSERT OR IGNORE INTO module_capabilities (module_id, capability, description)
SELECT m.id, c.cap, c.descr FROM modules m
JOIN (
  SELECT 'view' AS cap, 'View own appraisal inbox / received' AS descr UNION ALL
  SELECT 'write',       'Write appraisal of another crew' UNION ALL
  SELECT 'review',      'Review submitted appraisals (manager)' UNION ALL
  SELECT 'manage',      'Manage appraisal templates / rating scales' UNION ALL
  SELECT 'export',      'Export appraisals data'
) c WHERE m.code = 'appraisals';

-- logbook
INSERT OR IGNORE INTO module_capabilities (module_id, capability, description)
SELECT m.id, c.cap, c.descr FROM modules m
JOIN (
  SELECT 'view' AS cap, 'View own logbook entries' AS descr UNION ALL
  SELECT 'submit',      'Submit logbook entry' UNION ALL
  SELECT 'edit',        'Edit own draft logbook entries' UNION ALL
  SELECT 'review',      'Review (HR/CP) submitted logbook entries' UNION ALL
  SELECT 'export',      'Export logbook data'
) c WHERE m.code = 'logbook';

-- help
INSERT OR IGNORE INTO module_capabilities (module_id, capability, description)
SELECT m.id, c.cap, c.descr FROM modules m
JOIN (
  SELECT 'view' AS cap, 'View help/FAQs/contacts' AS descr UNION ALL
  SELECT 'submit_request', 'Submit help / support request' UNION ALL
  SELECT 'manage_content', 'Manage help content (admin)' UNION ALL
  SELECT 'manage_tickets', 'Triage / respond to support requests'
) c WHERE m.code = 'help';

-- reports
INSERT OR IGNORE INTO module_capabilities (module_id, capability, description)
SELECT m.id, c.cap, c.descr FROM modules m
JOIN (
  SELECT 'view' AS cap, 'View own submitted operational reports' AS descr UNION ALL
  SELECT 'submit_after_mission', 'Submit after-mission operational report' UNION ALL
  SELECT 'submit_airstrip',      'Submit airstrip / runway condition report' UNION ALL
  SELECT 'submit_verification',  'Submit verification report' UNION ALL
  SELECT 'view_all',             'View all reports for the tenant (manager)' UNION ALL
  SELECT 'review',               'Review submitted reports' UNION ALL
  SELECT 'export',               'Export reports data'
) c WHERE m.code = 'reports';

-- notifications
INSERT OR IGNORE INTO module_capabilities (module_id, capability, description)
SELECT m.id, c.cap, c.descr FROM modules m
JOIN (
  SELECT 'view' AS cap, 'View notification inbox' AS descr UNION ALL
  SELECT 'acknowledge', 'Acknowledge notifications that require it' UNION ALL
  SELECT 'create',      'Create / send a notification (admin)' UNION ALL
  SELECT 'manage',      'Manage notification templates / channels'
) c WHERE m.code = 'notifications';

-- 3. Role-capability templates (defaults)
-- Pilots / cabin crew / engineers — crew baseline.
INSERT OR IGNORE INTO role_capability_templates (role_slug, module_capability_id)
SELECT r.role_slug, mc.id FROM module_capabilities mc
JOIN modules m ON m.id = mc.module_id
JOIN (
  SELECT 'pilot' AS role_slug UNION ALL
  SELECT 'cabin_crew' UNION ALL
  SELECT 'engineer'
) r
WHERE
   (m.code = 'duty_reporting'  AND mc.capability IN ('view','check_in','clock_out'))
OR (m.code = 'flight_folder'   AND mc.capability IN ('view','submit_journey_log','submit_risk_assessment','submit_crew_briefing','submit_navlog','submit_post_arrival','submit_verification'))
OR (m.code = 'per_diem'        AND mc.capability IN ('view','claim'))
OR (m.code = 'appraisals'      AND mc.capability IN ('view','write'))
OR (m.code = 'logbook'         AND mc.capability IN ('view','submit','edit'))
OR (m.code = 'help'            AND mc.capability IN ('view','submit_request'))
OR (m.code = 'reports'         AND mc.capability IN ('view','submit_after_mission','submit_airstrip','submit_verification'))
OR (m.code = 'notifications'   AND mc.capability IN ('view','acknowledge'));

-- Pilot-only / cabin-only after-mission caps.
INSERT OR IGNORE INTO role_capability_templates (role_slug, module_capability_id)
SELECT 'pilot', mc.id FROM module_capabilities mc
JOIN modules m ON m.id = mc.module_id
WHERE m.code = 'flight_folder' AND mc.capability = 'submit_after_mission_pilot';

INSERT OR IGNORE INTO role_capability_templates (role_slug, module_capability_id)
SELECT 'cabin_crew', mc.id FROM module_capabilities mc
JOIN modules m ON m.id = mc.module_id
WHERE m.code = 'flight_folder' AND mc.capability = 'submit_after_mission_cabin';

-- Manager surfaces.
INSERT OR IGNORE INTO role_capability_templates (role_slug, module_capability_id)
SELECT r.role_slug, mc.id FROM module_capabilities mc
JOIN modules m ON m.id = mc.module_id
JOIN (
  SELECT 'base_manager' AS role_slug UNION ALL
  SELECT 'chief_pilot' UNION ALL
  SELECT 'scheduler'
) r
WHERE
   (m.code = 'duty_reporting'  AND mc.capability IN ('view','review','approve','export'))
OR (m.code = 'flight_folder'   AND mc.capability IN ('view','review','approve','export'))
OR (m.code = 'reports'         AND mc.capability IN ('view','view_all','review','export'))
OR (m.code = 'notifications'   AND mc.capability IN ('view','create','acknowledge'))
OR (m.code = 'logbook'         AND mc.capability IN ('view','review','export'))
OR (m.code = 'appraisals'      AND mc.capability IN ('view','review','export'));

-- HR.
INSERT OR IGNORE INTO role_capability_templates (role_slug, module_capability_id)
SELECT 'hr', mc.id FROM module_capabilities mc
JOIN modules m ON m.id = mc.module_id
WHERE
   (m.code = 'appraisals' AND mc.capability IN ('view','write','review','manage','export'))
OR (m.code = 'logbook'    AND mc.capability IN ('view','review','export'))
OR (m.code = 'per_diem'   AND mc.capability IN ('view','review','approve','export'))
OR (m.code = 'help'       AND mc.capability IN ('view','manage_tickets'));

-- Airline admin — full kit on all new modules.
INSERT OR IGNORE INTO role_capability_templates (role_slug, module_capability_id)
SELECT 'airline_admin', mc.id FROM module_capabilities mc
JOIN modules m ON m.id = mc.module_id
WHERE m.code IN ('duty_reporting','flight_folder','per_diem','appraisals','logbook','help','reports','notifications');

-- 4. Demo tenant (id=1) — enable the new modules.
INSERT OR IGNORE INTO tenant_modules (tenant_id, module_id, is_enabled)
SELECT 1, id, 1 FROM modules
WHERE code IN ('duty_reporting','flight_folder','per_diem','appraisals','logbook','help','reports','notifications');

COMMIT;
