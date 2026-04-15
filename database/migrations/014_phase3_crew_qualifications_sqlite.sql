-- Phase 3: Crew Qualifications & Profile Enhancements (SQLite)
-- Adds: qualifications table

CREATE TABLE IF NOT EXISTS qualifications (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id         INTEGER NOT NULL,
    tenant_id       INTEGER NOT NULL,
    qual_type       TEXT    NOT NULL,  -- e.g. "Type Rating", "Instructor Auth", "Course", "Endorsement"
    qual_name       TEXT    NOT NULL,  -- e.g. "B737-800 Type Rating", "CRM Facilitator"
    reference_no    TEXT,              -- certificate / reference number
    authority       TEXT,              -- issuing authority
    issue_date      TEXT,
    expiry_date     TEXT,
    status          TEXT    NOT NULL DEFAULT 'active', -- active, expired, pending_renewal, suspended
    notes           TEXT,
    created_at      TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_qualifications_user   ON qualifications(user_id);
CREATE INDEX IF NOT EXISTS idx_qualifications_tenant ON qualifications(tenant_id);
CREATE INDEX IF NOT EXISTS idx_qualifications_expiry ON qualifications(expiry_date);
