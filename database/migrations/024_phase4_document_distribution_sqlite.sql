-- =============================================================================
-- Phase 4 (V2) — Controlled Document Distribution & Acknowledgment
-- SQLite variant
-- =============================================================================

-- ── A. Department targeting ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS file_department_visibility (
    file_id       INTEGER NOT NULL,
    department_id INTEGER NOT NULL,
    PRIMARY KEY (file_id, department_id),
    FOREIGN KEY (file_id)       REFERENCES files(id)       ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_fdv_file ON file_department_visibility(file_id);
CREATE INDEX IF NOT EXISTS idx_fdv_dept ON file_department_visibility(department_id);

-- ── B. Base/station targeting ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS file_base_visibility (
    file_id INTEGER NOT NULL,
    base_id INTEGER NOT NULL,
    PRIMARY KEY (file_id, base_id),
    FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE,
    FOREIGN KEY (base_id) REFERENCES bases(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_fbv_file ON file_base_visibility(file_id);
CREATE INDEX IF NOT EXISTS idx_fbv_base ON file_base_visibility(base_id);

-- ── C. Read receipts (separate from ack) ────────────────────────────────
CREATE TABLE IF NOT EXISTS file_reads (
    id        INTEGER PRIMARY KEY AUTOINCREMENT,
    file_id   INTEGER NOT NULL,
    user_id   INTEGER NOT NULL,
    tenant_id INTEGER NOT NULL,
    version   TEXT    DEFAULT NULL,
    read_at   TEXT    NOT NULL DEFAULT (datetime('now')),
    UNIQUE (file_id, user_id),
    FOREIGN KEY (file_id)   REFERENCES files(id)   ON DELETE CASCADE,
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_fr_user_tenant ON file_reads(user_id, tenant_id);

-- ── D. Version chain columns on files ───────────────────────────────────
ALTER TABLE files ADD COLUMN replaces_file_id INTEGER DEFAULT NULL;
ALTER TABLE files ADD COLUMN superseded_at    TEXT    DEFAULT NULL;
CREATE INDEX IF NOT EXISTS idx_files_replaces ON files(replaces_file_id);
