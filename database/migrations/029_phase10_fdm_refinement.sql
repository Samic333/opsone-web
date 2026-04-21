-- Phase 10 (V2) — FDM refinement: pilot-specific events + ack  (MySQL)

ALTER TABLE `fdm_events`
    ADD COLUMN `pilot_user_id`    INT UNSIGNED DEFAULT NULL AFTER `aircraft_reg`,
    ADD COLUMN `pilot_ack_at`     TIMESTAMP    NULL DEFAULT NULL AFTER `notes`,
    ADD COLUMN `management_visible` TINYINT(1) NOT NULL DEFAULT 1 AFTER `pilot_ack_at`,
    ADD INDEX `idx_fdm_ev_pilot`   (`pilot_user_id`);
