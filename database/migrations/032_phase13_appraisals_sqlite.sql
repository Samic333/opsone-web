-- Phase 13 (V2) — Crew Appraisal  (SQLite)

CREATE TABLE IF NOT EXISTS appraisals (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id       INTEGER NOT NULL,
    subject_id      INTEGER NOT NULL,
    appraiser_id    INTEGER NOT NULL,
    rotation_ref    TEXT    DEFAULT NULL,
    period_from     TEXT    NOT NULL,
    period_to       TEXT    NOT NULL,
    status          TEXT    NOT NULL DEFAULT 'draft',  -- draft|submitted|reviewed|accepted
    rating_overall  INTEGER DEFAULT NULL,
    strengths       TEXT    DEFAULT NULL,
    improvements    TEXT    DEFAULT NULL,
    comments        TEXT    DEFAULT NULL,
    confidential    INTEGER NOT NULL DEFAULT 1,
    created_at      TEXT    NOT NULL DEFAULT (datetime('now')),
    submitted_at    TEXT    DEFAULT NULL,
    reviewed_by     INTEGER DEFAULT NULL,
    reviewed_at     TEXT    DEFAULT NULL,
    FOREIGN KEY (tenant_id)    REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id)   REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (appraiser_id) REFERENCES users(id)   ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_app_subject   ON appraisals(subject_id);
CREATE INDEX IF NOT EXISTS idx_app_appraiser ON appraisals(appraiser_id);
