-- Phase 10 (V2) — FDM refinement (SQLite)

ALTER TABLE fdm_events ADD COLUMN pilot_user_id     INTEGER DEFAULT NULL;
ALTER TABLE fdm_events ADD COLUMN pilot_ack_at      TEXT    DEFAULT NULL;
ALTER TABLE fdm_events ADD COLUMN management_visible INTEGER NOT NULL DEFAULT 1;
CREATE INDEX IF NOT EXISTS idx_fdm_ev_pilot ON fdm_events(pilot_user_id);
