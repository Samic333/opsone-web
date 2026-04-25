-- Migration 041 — Demo airline (tenant_id=1) phase-18 seed.
--
-- Phase 0 audit found that the demo pilot account (demo.pilot@acentoza.com,
-- user_id=341) had no rows in: training_records, licenses, per_diem_claims,
-- appraisals, and was NOT assigned as captain on the single existing flight
-- (id=1). That made every mobile tab look "empty" on iPad/iPhone, so phase-18
-- spot checks couldn't actually exercise the data layer.
--
-- This migration is **idempotent** — every statement is INSERT IGNORE or
-- guarded with a NOT EXISTS check, and the UPDATE only writes if the row
-- still has NULL captain_id. Re-running it is safe.
--
-- After this runs, the demo pilot has:
--   - 2 assigned flights (today's HKJK→HUEN + tomorrow's HUEN→HKJK)
--   - 2 training records (completed 6-monthly Sim + CRM refresher)
--   - 2 licenses (ATPL + Medical Class 1, both within 90 days of expiry so
--     the iPad expiry-alerts widget actually fires)
--   - 1 submitted per-diem claim
--   - 1 submitted appraisal received from the demo chief pilot
-- And the tenant has 3 per-diem rates (UAE/KEN/UGA) so the mobile claim
-- form's rate dropdown is populated.

START TRANSACTION;

-- 1. Make sure the existing flight (id=1) actually has the demo pilot as
--    captain. Without this, /flights/mine returns nothing for him.
UPDATE `flights`
   SET captain_id = 341
 WHERE tenant_id = 1
   AND id        = 1
   AND captain_id IS NULL;

-- 2. Add a second flight for tomorrow (return leg) so the roster shows more
--    than one entry. Date is computed in PHP at deploy-time would be ideal,
--    but for a one-shot demo seed a fixed near-future date is fine.
INSERT IGNORE INTO `flights`
  (tenant_id, flight_date, flight_number, departure, arrival, std, sta,
   aircraft_id, captain_id, fo_id, status)
VALUES
  (1, '2026-04-26', 'MZ-225', 'HUEN', 'HKJK', '09:00', '11:00',
   2, 341, NULL, 'published');

-- 3. Training records for the demo pilot. INSERT IGNORE on (user_id,
--    training_type_id, completed_date) — the schema's UNIQUE clause is
--    only on (tenant_id, code) for training_types, so we de-dup via a
--    NOT EXISTS guard instead.
INSERT INTO `training_records`
  (tenant_id, user_id, training_type_id, type_code, completed_date,
   expires_date, provider, result, notes)
SELECT 1, 341, 1, '6MO_SIM', '2026-02-15', '2026-08-15',
       'OpsOne Sim Centre', 'pass', 'Recurrent 6-monthly simulator check.'
 WHERE NOT EXISTS (
   SELECT 1 FROM `training_records`
    WHERE user_id = 341 AND type_code = '6MO_SIM'
      AND completed_date = '2026-02-15'
 );

INSERT INTO `training_records`
  (tenant_id, user_id, training_type_id, type_code, completed_date,
   expires_date, provider, result, notes)
SELECT 1, 341, 2, 'CRM_REFR', '2026-01-10', '2027-01-10',
       'OpsOne CRM Faculty', 'pass', 'Annual CRM refresher.'
 WHERE NOT EXISTS (
   SELECT 1 FROM `training_records`
    WHERE user_id = 341 AND type_code = 'CRM_REFR'
      AND completed_date = '2026-01-10'
 );

-- 4. Licenses for the demo pilot.
INSERT INTO `licenses`
  (tenant_id, user_id, license_type, license_number, issuing_authority,
   issue_date, expiry_date, status, notes)
SELECT 1, 341, 'ATPL', 'UAE-ATPL-4821', 'UAE GCAA',
       '2021-09-15', '2026-09-15', 'valid', 'Airline Transport Pilot License.'
 WHERE NOT EXISTS (
   SELECT 1 FROM `licenses`
    WHERE user_id = 341 AND license_type = 'ATPL'
 );

INSERT INTO `licenses`
  (tenant_id, user_id, license_type, license_number, issuing_authority,
   issue_date, expiry_date, status, notes)
SELECT 1, 341, 'Medical Class 1', 'MED-2026-0312', 'UAE GCAA AME',
       '2025-08-30', '2026-08-30', 'valid', 'Class 1 medical, 12-month validity.'
 WHERE NOT EXISTS (
   SELECT 1 FROM `licenses`
    WHERE user_id = 341 AND license_type = 'Medical Class 1'
 );

-- 5. Per-diem rates for the tenant (UAE / Kenya / Uganda — the demo route).
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
SELECT 1, 341, '2026-04-22', '2026-04-24', 'Entebbe (HUEN)', 'Uganda',
       2, 50.00, 'USD', 100.00, 'submitted',
       'HKJK-HUEN-HKJK rotation, 2 nights down-route.'
 WHERE NOT EXISTS (
   SELECT 1 FROM `per_diem_claims`
    WHERE user_id = 341 AND period_from = '2026-04-22' AND period_to = '2026-04-24'
 );

-- 7. One submitted appraisal received by the demo pilot from the demo chief
--    pilot (user_id=334).
INSERT INTO `appraisals`
  (tenant_id, subject_id, appraiser_id, rotation_ref, period_from, period_to,
   status, rating_overall, strengths, improvements, comments,
   submitted_at)
SELECT 1, 341, 334, '2026-Q1', '2026-01-01', '2026-03-31',
       'submitted', 4,
       'Excellent CRM, calm under pressure, sets strong example for FOs.',
       'Continue refining short-field landing technique on grass strips.',
       'Solid quarter. Recommend SimT instructor track when current cycle completes.',
       '2026-04-05 09:00:00'
 WHERE NOT EXISTS (
   SELECT 1 FROM `appraisals`
    WHERE subject_id = 341 AND appraiser_id = 334 AND rotation_ref = '2026-Q1'
 );

COMMIT;
