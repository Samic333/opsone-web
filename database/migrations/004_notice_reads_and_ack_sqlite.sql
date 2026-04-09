-- =====================================================
-- Migration 004 (SQLite) — Notice Reads & File Acknowledgements
-- For local development with SQLite (002_sqlite_schema.sql base)
-- =====================================================

-- Notice Reads
CREATE TABLE IF NOT EXISTS notice_reads (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    notice_id       INTEGER NOT NULL,
    user_id         INTEGER NOT NULL,
    tenant_id       INTEGER NOT NULL,
    read_at         TEXT NOT NULL DEFAULT (datetime('now')),
    acknowledged_at TEXT DEFAULT NULL,
    FOREIGN KEY (notice_id)  REFERENCES notices(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id)  REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE (notice_id, user_id)
);

-- Add columns to notices (SQLite doesn't support ADD COLUMN IF NOT EXISTS pre-3.37)
-- Run each separately if column already exists to avoid error:
ALTER TABLE notices ADD COLUMN requires_ack INTEGER NOT NULL DEFAULT 0;
ALTER TABLE notices ADD COLUMN target_roles TEXT DEFAULT NULL;
ALTER TABLE notices ADD COLUMN target_departments TEXT DEFAULT NULL;
ALTER TABLE notices ADD COLUMN target_bases TEXT DEFAULT NULL;

-- File Acknowledgements
CREATE TABLE IF NOT EXISTS file_acknowledgements (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    file_id         INTEGER NOT NULL,
    user_id         INTEGER NOT NULL,
    tenant_id       INTEGER NOT NULL,
    version         TEXT DEFAULT NULL,
    device_id       INTEGER DEFAULT NULL,
    acknowledged_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (file_id)    REFERENCES files(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id)  REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE (file_id, user_id)
);
