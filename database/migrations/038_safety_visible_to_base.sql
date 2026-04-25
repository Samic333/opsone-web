-- Phase 10 add-on (deferred batch) — base-scoped visibility for airstrip /
-- runway-condition reports.  When set by an admin reviewer, the report is
-- visible to other pilots assigned to the same departure / arrival base, so
-- the next crew sees the runway hazard before they fly.
--
-- Idempotent — safe to re-run.

ALTER TABLE `safety_reports`
    ADD COLUMN IF NOT EXISTS `visible_to_base`
        TINYINT(1) NOT NULL DEFAULT 0
        AFTER `aircraft_registration`;

-- Index for the base-scoped feed query — pilots reading airstrip reports for
-- their assigned base.
ALTER TABLE `safety_reports`
    ADD INDEX IF NOT EXISTS `idx_safety_visible_base` (`tenant_id`, `visible_to_base`, `report_type`, `icao_code`);
