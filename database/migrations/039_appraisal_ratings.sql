-- Phase 14 add-on (deferred batch) — per-dimension ratings JSON for appraisals.
--
-- Stores a free-form map of competency → integer score, e.g.
--   {"communication":4,"teamwork":5,"punctuality":3,"airmanship":4}
-- so the mobile appraisal view can render a radar / bar chart instead of just
-- the single overall rating.
--
-- Idempotent — safe to re-run.

ALTER TABLE `appraisals`
    ADD COLUMN IF NOT EXISTS `ratings`
        JSON DEFAULT NULL
        AFTER `rating_overall`;
