-- Migration 047 (SQLite) — Anonymize safety_reports rows whose reporter_id
-- points at a deleted user. The reporter_id-as-token-id bug fixed in Phase 9
-- of the 2026-04-26 remediation left historical rows with stale ids whose
-- original reporter is unrecoverable. Mark them anonymous so they remain
-- visible to safety officers but no current user is wrongly attributed.
-- Idempotent.

BEGIN TRANSACTION;
UPDATE safety_reports
   SET reporter_id  = NULL,
       is_anonymous = 1
 WHERE reporter_id IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM users WHERE users.id = safety_reports.reporter_id);
COMMIT;
