-- Phase 11 add-on (deferred batch) — pilot comment / explanation on FDM events
--
-- Lets a pilot record an explanation or response to an FDM event from the
-- mobile detail screen.  Optional, can be empty.  When set, the analyst sees
-- the comment in the existing FDM admin views.
--
-- Idempotent — safe to re-run.

ALTER TABLE `fdm_events`
    ADD COLUMN IF NOT EXISTS `pilot_comment`    TEXT       DEFAULT NULL AFTER `pilot_ack_at`,
    ADD COLUMN IF NOT EXISTS `pilot_comment_at` TIMESTAMP  NULL DEFAULT NULL AFTER `pilot_comment`;
