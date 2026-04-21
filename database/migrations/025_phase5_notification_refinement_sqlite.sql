-- Phase 5 (V2) — Notification Engine Refinement  (SQLite variant)

ALTER TABLE notifications ADD COLUMN priority        TEXT    NOT NULL DEFAULT 'normal';
ALTER TABLE notifications ADD COLUMN event           TEXT    DEFAULT NULL;
ALTER TABLE notifications ADD COLUMN ack_required    INTEGER NOT NULL DEFAULT 0;
ALTER TABLE notifications ADD COLUMN acknowledged_at TEXT    DEFAULT NULL;

CREATE INDEX IF NOT EXISTS idx_notif_priority ON notifications(priority);
CREATE INDEX IF NOT EXISTS idx_notif_event    ON notifications(event);
CREATE INDEX IF NOT EXISTS idx_notif_unack    ON notifications(user_id, ack_required, acknowledged_at);
