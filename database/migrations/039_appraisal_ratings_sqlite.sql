-- Phase 14 add-on (deferred batch) — per-dimension ratings JSON for appraisals.
-- SQLite mirror of 039_appraisal_ratings.sql (MySQL version uses JSON type).
--
-- Stores a free-form map of competency → integer score, e.g.
--   {"communication":4,"professionalism":5,"team_spirit":3}
-- so the web/iPad appraisal views can render per-attribute scores instead of
-- only the single overall rating.
--
-- SQLite has no JSON type — we use TEXT and rely on the application layer
-- to encode/decode JSON. SQLite has no `ADD COLUMN IF NOT EXISTS`, so the
-- migration runner must skip this file when the column already exists.

ALTER TABLE appraisals ADD COLUMN ratings TEXT DEFAULT NULL;
