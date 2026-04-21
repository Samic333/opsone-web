-- Phase 6: Personnel Compliance Record System (SQLite variant)
-- Mirrors 023_phase6_personnel_compliance.sql.
-- SQLite lacks "IF NOT EXISTS" on ALTER COLUMN and some ENUM types; we use
-- TEXT with CHECK constraints and tolerant ALTERs through PRAGMA/try patterns
-- that the migration applier in database/apply_sqlite_migrations.php handles.

-- ─── crew_profiles extensions ──────────────────────────────────────────────
ALTER TABLE crew_profiles ADD COLUMN profile_photo_path TEXT;
ALTER TABLE crew_profiles ADD COLUMN address            TEXT;
ALTER TABLE crew_profiles ADD COLUMN visa_number        TEXT;
ALTER TABLE crew_profiles ADD COLUMN visa_country       TEXT;
ALTER TABLE crew_profiles ADD COLUMN visa_type          TEXT;
ALTER TABLE crew_profiles ADD COLUMN visa_expiry        TEXT;
CREATE INDEX IF NOT EXISTS idx_crew_profiles_visa_expiry ON crew_profiles(visa_expiry);

-- ─── users: line manager linkage ───────────────────────────────────────────
ALTER TABLE users ADD COLUMN line_manager_id INTEGER;
CREATE INDEX IF NOT EXISTS idx_users_line_manager ON users(line_manager_id);

-- ─── licenses: approval + scan metadata ────────────────────────────────────
ALTER TABLE licenses ADD COLUMN status TEXT NOT NULL DEFAULT 'valid';
ALTER TABLE licenses ADD COLUMN approved_by INTEGER;
ALTER TABLE licenses ADD COLUMN approved_at TEXT;
ALTER TABLE licenses ADD COLUMN file_id INTEGER;
ALTER TABLE licenses ADD COLUMN document_scan_path TEXT;
ALTER TABLE licenses ADD COLUMN pending_change_request_id INTEGER;
CREATE INDEX IF NOT EXISTS idx_licenses_status ON licenses(status);

-- ─── qualifications: approval + scan metadata ─────────────────────────────
ALTER TABLE qualifications ADD COLUMN approved_by INTEGER;
ALTER TABLE qualifications ADD COLUMN approved_at TEXT;
ALTER TABLE qualifications ADD COLUMN file_id INTEGER;
ALTER TABLE qualifications ADD COLUMN document_scan_path TEXT;
ALTER TABLE qualifications ADD COLUMN pending_change_request_id INTEGER;

-- ─── crew_documents ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS crew_documents (
    id                    INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id             INTEGER NOT NULL,
    user_id               INTEGER NOT NULL,
    doc_type              TEXT    NOT NULL,
    doc_category          TEXT,
    doc_title             TEXT    NOT NULL,
    doc_number            TEXT,
    issuing_authority     TEXT,
    issue_date            TEXT,
    expiry_date           TEXT,
    file_path             TEXT,
    file_name             TEXT,
    file_mime             TEXT,
    file_size             INTEGER,
    status                TEXT    NOT NULL DEFAULT 'pending_approval',
    approved_by           INTEGER,
    approved_at           TEXT,
    rejection_reason      TEXT,
    replaces_document_id  INTEGER,
    uploaded_by           INTEGER,
    notes                 TEXT,
    created_at            TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at            TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_crew_docs_user   ON crew_documents(user_id);
CREATE INDEX IF NOT EXISTS idx_crew_docs_tenant ON crew_documents(tenant_id);
CREATE INDEX IF NOT EXISTS idx_crew_docs_expiry ON crew_documents(expiry_date);
CREATE INDEX IF NOT EXISTS idx_crew_docs_status ON crew_documents(status);
CREATE INDEX IF NOT EXISTS idx_crew_docs_type   ON crew_documents(doc_type);

-- ─── emergency_contacts ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS emergency_contacts (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id     INTEGER NOT NULL,
    user_id       INTEGER NOT NULL,
    contact_name  TEXT    NOT NULL,
    relation      TEXT,
    phone_primary TEXT,
    phone_alt     TEXT,
    email         TEXT,
    address       TEXT,
    is_primary    INTEGER NOT NULL DEFAULT 0,
    sort_order    INTEGER NOT NULL DEFAULT 0,
    created_at    TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at    TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_emg_user   ON emergency_contacts(user_id);
CREATE INDEX IF NOT EXISTS idx_emg_tenant ON emergency_contacts(tenant_id);

-- ─── role_required_documents ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS role_required_documents (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id      INTEGER,
    role_slug      TEXT    NOT NULL,
    doc_type       TEXT    NOT NULL,
    doc_label      TEXT    NOT NULL,
    is_mandatory   INTEGER NOT NULL DEFAULT 1,
    warning_days   INTEGER NOT NULL DEFAULT 60,
    critical_days  INTEGER NOT NULL DEFAULT 14,
    description    TEXT,
    is_active      INTEGER NOT NULL DEFAULT 1,
    created_at     TEXT    NOT NULL DEFAULT (datetime('now')),
    UNIQUE (tenant_id, role_slug, doc_type)
);
CREATE INDEX IF NOT EXISTS idx_role_req_role ON role_required_documents(role_slug);

-- ─── compliance_change_requests ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS compliance_change_requests (
    id                     INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id              INTEGER NOT NULL,
    user_id                INTEGER NOT NULL,
    requester_user_id      INTEGER NOT NULL,
    target_entity          TEXT    NOT NULL,
    target_id              INTEGER,
    change_type            TEXT    NOT NULL DEFAULT 'update',
    payload                TEXT    NOT NULL,
    supporting_file_id     INTEGER,
    supporting_document_id INTEGER,
    status                 TEXT    NOT NULL DEFAULT 'submitted',
    reviewer_user_id       INTEGER,
    reviewer_notes         TEXT,
    submitted_at           TEXT    NOT NULL DEFAULT (datetime('now')),
    reviewed_at            TEXT,
    created_at             TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at             TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_ccr_tenant    ON compliance_change_requests(tenant_id);
CREATE INDEX IF NOT EXISTS idx_ccr_user      ON compliance_change_requests(user_id);
CREATE INDEX IF NOT EXISTS idx_ccr_requester ON compliance_change_requests(requester_user_id);
CREATE INDEX IF NOT EXISTS idx_ccr_status    ON compliance_change_requests(status);
CREATE INDEX IF NOT EXISTS idx_ccr_submitted ON compliance_change_requests(submitted_at);

-- ─── expiry_alerts ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS expiry_alerts (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id       INTEGER NOT NULL,
    user_id         INTEGER NOT NULL,
    entity_type     TEXT    NOT NULL,
    entity_id       INTEGER NOT NULL,
    expiry_date     TEXT    NOT NULL,
    alert_level     TEXT    NOT NULL,
    sent_to_user    INTEGER NOT NULL DEFAULT 0,
    sent_to_hr      INTEGER NOT NULL DEFAULT 0,
    sent_to_manager INTEGER NOT NULL DEFAULT 0,
    last_sent_at    TEXT,
    cleared_at      TEXT,
    created_at      TEXT    NOT NULL DEFAULT (datetime('now')),
    UNIQUE (tenant_id, user_id, entity_type, entity_id, alert_level),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_expiry_alerts_user    ON expiry_alerts(user_id);
CREATE INDEX IF NOT EXISTS idx_expiry_alerts_tenant  ON expiry_alerts(tenant_id);
CREATE INDEX IF NOT EXISTS idx_expiry_alerts_pending ON expiry_alerts(cleared_at);

-- ─── Seed role_required_documents defaults (tenant_id NULL = system) ─────
INSERT OR IGNORE INTO role_required_documents (tenant_id, role_slug, doc_type, doc_label, is_mandatory, warning_days, critical_days, description) VALUES
(NULL, 'pilot',        'license',           'Pilot Licence',          1, 60, 14, 'ATPL / CPL / PPL'),
(NULL, 'pilot',        'medical',           'Medical Certificate',    1, 60, 14, 'Class 1 medical'),
(NULL, 'pilot',        'passport',          'Passport',               1, 180, 60, 'Valid passport'),
(NULL, 'pilot',        'type_rating',       'Type Rating',            1, 60, 14, 'Current aircraft type rating'),
(NULL, 'cabin_crew',   'cabin_attestation', 'Cabin Crew Attestation', 1, 60, 14, 'EASA/DGCA cabin crew attestation'),
(NULL, 'cabin_crew',   'medical',           'Medical Certificate',    1, 60, 14, 'Cabin crew medical fitness'),
(NULL, 'cabin_crew',   'passport',          'Passport',               1, 180, 60, 'Valid passport'),
(NULL, 'engineer',     'license',           'Engineer Licence',       1, 60, 14, 'AME/Part-66 licence'),
(NULL, 'engineer',     'company_id',        'Company ID',             1, 60, 14, 'Company-issued ID card'),
(NULL, 'engineer',     'type_auth',         'Type Authorization',     1, 60, 14, 'Aircraft type authorization'),
(NULL, 'base_manager', 'company_id',        'Company ID',             1, 60, 14, 'Base manager ID'),
(NULL, 'base_manager', 'contract',          'Employment Contract',    1, 60, 14, 'Signed contract'),
(NULL, 'base_manager', 'airside_permit',    'Airside Permit',         0, 60, 14, 'Station/airport airside pass'),
(NULL, 'scheduler',    'company_id',        'Company ID',             1, 60, 14, NULL),
(NULL, 'document_control','company_id',     'Company ID',             1, 60, 14, NULL),
(NULL, 'hr',           'company_id',        'Company ID',             1, 60, 14, NULL),
(NULL, 'airline_admin','company_id',        'Company ID',             0, 60, 14, NULL);
