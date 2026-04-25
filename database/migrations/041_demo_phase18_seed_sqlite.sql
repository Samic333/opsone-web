-- Migration 041 (SQLite) — Demo airline phase-18 seed.
-- See 041_demo_phase18_seed.sql for full rationale. Idempotent / re-runnable.

BEGIN TRANSACTION;

-- 1. Assign demo pilot (id=341) as captain on the existing demo flight.
UPDATE flights
   SET captain_id = 341
 WHERE tenant_id = 1
   AND id        = 1
   AND captain_id IS NULL;

-- 2. Second flight (return leg) for the demo pilot.
INSERT OR IGNORE INTO flights
  (tenant_id, flight_date, flight_number, departure, arrival, std, sta,
   aircraft_id, captain_id, fo_id, status)
VALUES
  (1, '2026-04-26', 'MZ-225', 'HUEN', 'HKJK', '09:00', '11:00',
   2, 341, NULL, 'published');

-- 3. Training records.
INSERT INTO training_records
  (tenant_id, user_id, training_type_id, type_code, completed_date,
   expires_date, provider, result, notes)
SELECT 1, 341, 1, '6MO_SIM', '2026-02-15', '2026-08-15',
       'OpsOne Sim Centre', 'pass', 'Recurrent 6-monthly simulator check.'
 WHERE NOT EXISTS (
   SELECT 1 FROM training_records
    WHERE user_id = 341 AND type_code = '6MO_SIM'
      AND completed_date = '2026-02-15'
 );

INSERT INTO training_records
  (tenant_id, user_id, training_type_id, type_code, completed_date,
   expires_date, provider, result, notes)
SELECT 1, 341, 2, 'CRM_REFR', '2026-01-10', '2027-01-10',
       'OpsOne CRM Faculty', 'pass', 'Annual CRM refresher.'
 WHERE NOT EXISTS (
   SELECT 1 FROM training_records
    WHERE user_id = 341 AND type_code = 'CRM_REFR'
      AND completed_date = '2026-01-10'
 );

-- 4. Licenses.
INSERT INTO licenses
  (tenant_id, user_id, license_type, license_number, issuing_authority,
   issue_date, expiry_date, status, notes)
SELECT 1, 341, 'ATPL', 'UAE-ATPL-4821', 'UAE GCAA',
       '2021-09-15', '2026-09-15', 'valid', 'Airline Transport Pilot License.'
 WHERE NOT EXISTS (
   SELECT 1 FROM licenses
    WHERE user_id = 341 AND license_type = 'ATPL'
 );

INSERT INTO licenses
  (tenant_id, user_id, license_type, license_number, issuing_authority,
   issue_date, expiry_date, status, notes)
SELECT 1, 341, 'Medical Class 1', 'MED-2026-0312', 'UAE GCAA AME',
       '2025-08-30', '2026-08-30', 'valid', 'Class 1 medical, 12-month validity.'
 WHERE NOT EXISTS (
   SELECT 1 FROM licenses
    WHERE user_id = 341 AND license_type = 'Medical Class 1'
 );

-- 5. Per-diem rates (UAE / Kenya / Uganda).
INSERT INTO per_diem_rates
  (tenant_id, country, station, currency, daily_rate, effective_from, notes)
SELECT 1, 'UAE', 'Dubai (DXB)', 'USD', 80.00, '2026-01-01', 'Standard outstation rate.'
 WHERE NOT EXISTS (
   SELECT 1 FROM per_diem_rates
    WHERE tenant_id = 1 AND country = 'UAE' AND station = 'Dubai (DXB)'
 );

INSERT INTO per_diem_rates
  (tenant_id, country, station, currency, daily_rate, effective_from, notes)
SELECT 1, 'Kenya', 'Nairobi (HKJK)', 'USD', 60.00, '2026-01-01', 'Nairobi outstation.'
 WHERE NOT EXISTS (
   SELECT 1 FROM per_diem_rates
    WHERE tenant_id = 1 AND country = 'Kenya' AND station = 'Nairobi (HKJK)'
 );

INSERT INTO per_diem_rates
  (tenant_id, country, station, currency, daily_rate, effective_from, notes)
SELECT 1, 'Uganda', 'Entebbe (HUEN)', 'USD', 50.00, '2026-01-01', 'Entebbe outstation.'
 WHERE NOT EXISTS (
   SELECT 1 FROM per_diem_rates
    WHERE tenant_id = 1 AND country = 'Uganda' AND station = 'Entebbe (HUEN)'
 );

-- 6. One submitted per-diem claim from demo pilot.
INSERT INTO per_diem_claims
  (tenant_id, user_id, period_from, period_to, station, country,
   days, rate, currency, amount, status, notes)
SELECT 1, 341, '2026-04-22', '2026-04-24', 'Entebbe (HUEN)', 'Uganda',
       2, 50.00, 'USD', 100.00, 'submitted',
       'HKJK-HUEN-HKJK rotation, 2 nights down-route.'
 WHERE NOT EXISTS (
   SELECT 1 FROM per_diem_claims
    WHERE user_id = 341 AND period_from = '2026-04-22' AND period_to = '2026-04-24'
 );

-- 7. One submitted appraisal: chief pilot (334) → demo pilot (341).
INSERT INTO appraisals
  (tenant_id, subject_id, appraiser_id, rotation_ref, period_from, period_to,
   status, rating_overall, strengths, improvements, comments, submitted_at)
SELECT 1, 341, 334, '2026-Q1', '2026-01-01', '2026-03-31',
       'submitted', 4,
       'Excellent CRM, calm under pressure, sets strong example for FOs.',
       'Continue refining short-field landing technique on grass strips.',
       'Solid quarter. Recommend SimT instructor track when current cycle completes.',
       '2026-04-05 09:00:00'
 WHERE NOT EXISTS (
   SELECT 1 FROM appraisals
    WHERE subject_id = 341 AND appraiser_id = 334 AND rotation_ref = '2026-Q1'
 );

COMMIT;
