-- Archive stale broadcast notices so they stop crowding the pilot dashboard.
--
-- Why: the pilot "Latest Notices" card shows every notice with
--   (expires_at IS NULL OR expires_at > NOW()) AND published = 1
-- so un-expired old notices from earlier phases (Manual Rev 12, Emergency
-- Evacuation, NBO 06R Closure, Leave Freeze Q3, etc.) keep showing up.
--
-- Uses existing schema columns — no migration.  Edit <TENANT_ID> below
-- before running.  Both variants are safe to run repeatedly.
--
-- Run:
--   mysql -u <user> -p <db> < opsone-web/database/scripts/archive_stale_notices.sql
-- or paste into phpMyAdmin on Namecheap.

-- Substitute the actual tenant id (e.g. 1 for the demo tenant).
SET @tenant_id := 1;

-- ─────────────────────────────────────────────────────────────────────────
-- Variant A (recommended) — soft archive.  Marks notices expired so they
-- drop off active feeds but remain retrievable by direct id.  Only touches
-- rows older than 4 months that aren't already expired.
-- ─────────────────────────────────────────────────────────────────────────
UPDATE notices
SET    expires_at = NOW()
WHERE  tenant_id = @tenant_id
  AND  (expires_at IS NULL OR expires_at > NOW())
  AND  created_at < DATE_SUB(NOW(), INTERVAL 4 MONTH);

-- Report how many rows were affected.
SELECT ROW_COUNT() AS rows_archived;

-- ─────────────────────────────────────────────────────────────────────────
-- Variant B (optional, more aggressive) — hard-unpublish.  Sets
-- `published = 0` so these notices don't return for any query that filters
-- on the publication flag.  Uncomment if Variant A didn't clear the feed.
-- ─────────────────────────────────────────────────────────────────────────
-- UPDATE notices
-- SET    published = 0
-- WHERE  tenant_id = @tenant_id
--   AND  created_at < DATE_SUB(NOW(), INTERVAL 4 MONTH);
-- SELECT ROW_COUNT() AS rows_unpublished;

-- ─────────────────────────────────────────────────────────────────────────
-- Variant C (spot surgery) — archive specific notices by title.  Use this
-- if you only want to drop the 4 items the pilot dashboard is showing.
-- ─────────────────────────────────────────────────────────────────────────
-- UPDATE notices
-- SET    expires_at = NOW()
-- WHERE  tenant_id = @tenant_id
--   AND  title IN (
--          'Operations Manual Revision 12 — Acknowledgement Required',
--          'SAFETY ALERT: Updated Emergency Evacuation Procedures',
--          'Operations Update: NBO Runway 06R Closure 15–20 Apr 2026',
--          'Crew Scheduling: Annual Leave Freeze Q3 2026'
--        );
-- SELECT ROW_COUNT() AS rows_spot_archived;
