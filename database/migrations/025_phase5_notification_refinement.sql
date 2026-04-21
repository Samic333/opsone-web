-- =============================================================================
-- Phase 5 (V2) — Notification Engine Refinement
-- MySQL variant
--
-- Additive columns on `notifications`:
--   priority         — critical | important | normal | silent
--   event            — short machine key for the event that fired this row
--   ack_required     — 1 if the user must acknowledge, not just read
--   acknowledged_at  — set when user explicitly acks (ack_required rows only)
-- =============================================================================

ALTER TABLE `notifications`
    ADD COLUMN `priority`        VARCHAR(20)  NOT NULL DEFAULT 'normal' AFTER `body`,
    ADD COLUMN `event`           VARCHAR(80)  DEFAULT NULL AFTER `priority`,
    ADD COLUMN `ack_required`    TINYINT(1)   NOT NULL DEFAULT 0 AFTER `event`,
    ADD COLUMN `acknowledged_at` TIMESTAMP    NULL DEFAULT NULL AFTER `read_at`;

ALTER TABLE `notifications`
    ADD INDEX `idx_notif_priority` (`priority`),
    ADD INDEX `idx_notif_event`    (`event`),
    ADD INDEX `idx_notif_unack`    (`user_id`, `ack_required`, `acknowledged_at`);
