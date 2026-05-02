-- Migration 051 (SQLite) — Crew document approval lock columns.
--
-- After admin approval, a crew document (license, medical, passport, etc.)
-- becomes immutable from the iPad: the user cannot delete or replace it
-- unless one of these is true:
--   1. The document is within `unlock_window_days` of expiry
--   2. The document has expired
--   3. An admin temporarily unlocks it via `unlocked_until`
--
-- These columns make rule (3) explicit. Rules (1) and (2) are computed
-- at read time from `expiry_date` and a tenant-level config (default 10
-- days, mirrors the user's spec).
--
-- SQLite cannot ADD COLUMN with conditional checks the way MySQL can. We
-- query the schema first; the migration runner is responsible for skipping
-- this file if the columns already exist. The CREATE INDEX guards are
-- idempotent on their own.

-- NOTE: SQLite "ALTER TABLE ADD COLUMN" is not idempotent. Wrap in PRAGMA
-- check via the runner; for manual application, the runner should pass
-- on duplicate-column-name errors. The seed-db.php runner already
-- tolerates this in earlier migrations (see 042_*).

ALTER TABLE crew_documents ADD COLUMN is_locked       INTEGER NOT NULL DEFAULT 0;
ALTER TABLE crew_documents ADD COLUMN locked_at       TEXT    DEFAULT NULL;
ALTER TABLE crew_documents ADD COLUMN locked_by       INTEGER DEFAULT NULL;
ALTER TABLE crew_documents ADD COLUMN unlocked_until  TEXT    DEFAULT NULL;
ALTER TABLE crew_documents ADD COLUMN unlocked_by     INTEGER DEFAULT NULL;
ALTER TABLE crew_documents ADD COLUMN unlocked_reason TEXT    DEFAULT NULL;

CREATE INDEX IF NOT EXISTS idx_cd_locked    ON crew_documents(is_locked);
CREATE INDEX IF NOT EXISTS idx_cd_unlocked  ON crew_documents(unlocked_until);

-- Lock every already-approved document so existing prod data picks up
-- the rule without an admin re-approving each one.
UPDATE crew_documents
SET is_locked = 1,
    locked_at = COALESCE(approved_at, datetime('now')),
    locked_by = approved_by
WHERE status = 'approved' AND is_locked = 0;
