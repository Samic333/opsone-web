-- Migration 051 (MySQL) — Crew document approval lock columns.
--
-- Sister of 051_crew_documents_lock_sqlite.sql. Adds approval-lock columns
-- to the production MySQL store and seeds locks on already-approved rows.

ALTER TABLE crew_documents
    ADD COLUMN IF NOT EXISTS is_locked       TINYINT(1)   NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS locked_at       DATETIME     DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS locked_by       INT UNSIGNED DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS unlocked_until  DATETIME     DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS unlocked_by     INT UNSIGNED DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS unlocked_reason TEXT         DEFAULT NULL,
    ADD KEY IF NOT EXISTS idx_cd_locked    (is_locked),
    ADD KEY IF NOT EXISTS idx_cd_unlocked  (unlocked_until);

UPDATE crew_documents
SET is_locked = 1,
    locked_at = COALESCE(approved_at, NOW()),
    locked_by = approved_by
WHERE status = 'approved' AND is_locked = 0;
