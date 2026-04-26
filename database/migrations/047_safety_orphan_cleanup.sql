-- Migration 047 (MySQL) — mirror of 047_safety_orphan_cleanup_sqlite.sql.
-- Idempotent.

UPDATE safety_reports sr
   SET sr.reporter_id  = NULL,
       sr.is_anonymous = 1
 WHERE sr.reporter_id IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM users u WHERE u.id = sr.reporter_id);
