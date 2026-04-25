-- Migration 041 — Demo airline (tenant_id=1) phase-18 seed.
--
-- Phase 0 audit found that the demo pilot account
-- (demo.pilot@acentoza.com) had no rows in training_records, licenses,
-- per_diem_claims, appraisals, and was NOT assigned as captain on the
-- single existing flight. That made every mobile tab look "empty" on
-- iPad/iPhone, so phase-18 spot checks couldn't actually exercise the
-- data layer.
--
-- This migration is **idempotent** — every statement is INSERT IGNORE
-- or guarded with a NOT EXISTS check. Re-running it is safe.
--
-- ⚠️  IMPORTANT: numeric user IDs differ between local SQLite (where the
-- demo pilot is id=341) and production MySQL. We resolve users by EMAIL
-- via session variables so the same SQL works in both environments.
-- Set the variables once at the top, then use them throughout.

START TRANSACTION;

-- Resolve demo accounts by email (tenant-scoped). LIMIT 1 keeps the
-- assignment well-defined even if a duplicate ever sneaks in.
SELECT @pilot_id := id FROM `users`
  WHERE email = 'demo.pilot@acentoza.com'      AND tenant_id = 1 LIMIT 1;
SELECT @chief_id := id FROM `users`
  WHERE email = 'demo.chiefpilot@acentoza.com' AND tenant_id = 1 LIMIT 1;

-- 1. Make sure the existing flight (id=1) actually has the demo pilot
--    as captain. Without this, /api/flights/mine returns nothing for
--    him.
UPDATE `flights`
   SET captain_id = @pilot_id
 WHERE tenant_id = 1
   AND id        = 1
   AND captain_id IS NULL
   AND @pilot_id IS NOT NULL;

-- 2. Add a second flight for tomorrow (return leg) so the roster shows
--    more than one entry. aircraft_id is resolved to whatever aircraft
--    exists for the demo tenant (production registration IDs differ
--    from local).
SELECT @aircraft_id := id FROM `aircraft`
  WHERE tenant_id = 1 ORDER BY id LIMIT 1;

INSERT IGNORE INTO `flights`
  (tenant_id, flight_date, flight_number, departure, arrival, std, sta,
   aircraft_id, captain_id, fo_id, status)
SELECT 1, '2026-04-26', 'MZ-225', 'HUEN', 'HKJK', '09:00', '11:00',
       @aircraft_id, @pilot_id, NULL, 'published'
 WHERE @pilot_id IS NOT NULL;

-- 3. Training records.
--    training_type_id is intentionally LEFT-JOIN-resolved by tenant_id+code.
--    If the type isn't seeded on this environment, NULL is fine — the
--    nullable FK lets the record stand and the `type_code` column carries
--    the display label for the mobile UI.
INSERT INTO `training_records`
  (tenant_id, user_id, training_type_id, type_code, completed_date,
   expires_date, provider, result, notes)
SELECT 1, @pilot_id,
       (SELECT id FROM `training_types` WHERE tenant_id = 1 AND code = '6MO_SIM' LIMIT 1),
       '6MO_SIM', '2026-02-15', '2026-08-15',
       'OpsOne Sim Centre', 'pass', 'Recurrent 6-monthly simulator check.'
 WHERE @pilot_id IS NOT NULL
   AND NOT EXISTS (
     SELECT 1 FROM `training_records`
      WHERE user_id = @pilot_id AND type_code = '6MO_SIM'
        AND completed_date = '2026-02-15'
   );

INSERT INTO `training_records`
  (tenant_id, user_id, training_type_id, type_code, completed_date,
   expires_date, provider, result, notes)
SELECT 1, @pilot_id,
       (SELECT id FROM `training_types` WHERE tenant_id = 1 AND code = 'CRM_REFR' LIMIT 1),
       'CRM_REFR', '2026-01-10', '2027-01-10',
       'OpsOne CRM Faculty', 'pass', 'Annual CRM refresher.'
 WHERE @pilot_id IS NOT NULL
   AND NOT EXISTS (
     SELECT 1 FROM `training_records`
      WHERE user_id = @pilot_id AND type_code = 'CRM_REFR'
        AND completed_date = '2026-01-10'
   );

-- 4. Licenses.
INSERT INTO `licenses`
  (tenant_id, user_id, license_type, license_number, issuing_authority,
   issue_date, expiry_date, status, notes)
SELECT 1, @pilot_id, 'ATPL', 'UAE-ATPL-4821', 'UAE GCAA',
       '2021-09-15', '2026-09-15', 'valid', 'Airline Transport Pilot License.'
 WHERE @pilot_id IS NOT NULL
   AND NOT EXISTS (
     SELECT 1 FROM `licenses`
      WHERE user_id = @pilot_id AND license_type = 'ATPL'
   );

INSERT INTO `licenses`
  (tenant_id, user_id, license_type, license_number, issuing_authority,
   issue_date, expiry_date, status, notes)
SELECT 1, @pilot_id, 'Medical Class 1', 'MED-2026-0312', 'UAE GCAA AME',
       '2025-08-30', '2026-08-30', 'valid', 'Class 1 medical, 12-month validity.'
 WHERE @pilot_id IS NOT NULL
   AND NOT EXISTS (
     SELECT 1 FROM `licenses`
      WHERE user_id = @pilot_id AND license_type = 'Medical Class 1'
   );

-- 5. Per-diem rates (UAE / Kenya / Uganda — the demo route).
INSERT INTO `per_diem_rates`
  (tenant_id, country, station, currency, daily_rate, effective_from, notes)
SELECT 1, 'UAE', 'Dubai (DXB)', 'USD', 80.00, '2026-01-01', 'Standard outstation rate.'
 WHERE NOT EXISTS (
   SELECT 1 FROM `per_diem_rates`
    WHERE tenant_id = 1 AND country = 'UAE' AND station = 'Dubai (DXB)'
 );

INSERT INTO `per_diem_rates`
  (tenant_id, country, station, currency, daily_rate, effective_from, notes)
SELECT 1, 'Kenya', 'Nairobi (HKJK)', 'USD', 60.00, '2026-01-01', 'Nairobi outstation.'
 WHERE NOT EXISTS (
   SELECT 1 FROM `per_diem_rates`
    WHERE tenant_id = 1 AND country = 'Kenya' AND station = 'Nairobi (HKJK)'
 );

INSERT INTO `per_diem_rates`
  (tenant_id, country, station, currency, daily_rate, effective_from, notes)
SELECT 1, 'Uganda', 'Entebbe (HUEN)', 'USD', 50.00, '2026-01-01', 'Entebbe outstation.'
 WHERE NOT EXISTS (
   SELECT 1 FROM `per_diem_rates`
    WHERE tenant_id = 1 AND country = 'Uganda' AND station = 'Entebbe (HUEN)'
 );

-- 6. One submitted per-diem claim from the demo pilot.
INSERT INTO `per_diem_claims`
  (tenant_id, user_id, period_from, period_to, station, country,
   days, rate, currency, amount, status, notes)
SELECT 1, @pilot_id, '2026-04-22', '2026-04-24', 'Entebbe (HUEN)', 'Uganda',
       2, 50.00, 'USD', 100.00, 'submitted',
       'HKJK-HUEN-HKJK rotation, 2 nights down-route.'
 WHERE @pilot_id IS NOT NULL
   AND NOT EXISTS (
     SELECT 1 FROM `per_diem_claims`
      WHERE user_id = @pilot_id AND period_from = '2026-04-22' AND period_to = '2026-04-24'
   );

-- 7. One submitted appraisal: chief pilot → demo pilot.
INSERT INTO `appraisals`
  (tenant_id, subject_id, appraiser_id, rotation_ref, period_from, period_to,
   status, rating_overall, strengths, improvements, comments, submitted_at)
SELECT 1, @pilot_id, @chief_id, '2026-Q1', '2026-01-01', '2026-03-31',
       'submitted', 4,
       'Excellent CRM, calm under pressure, sets strong example for FOs.',
       'Continue refining short-field landing technique on grass strips.',
       'Solid quarter. Recommend SimT instructor track when current cycle completes.',
       '2026-04-05 09:00:00'
 WHERE @pilot_id IS NOT NULL
   AND @chief_id IS NOT NULL
   AND NOT EXISTS (
     SELECT 1 FROM `appraisals`
      WHERE subject_id = @pilot_id AND appraiser_id = @chief_id AND rotation_ref = '2026-Q1'
   );

COMMIT;
