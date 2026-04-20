-- Migration 021: Safety Actions Table (SQLite)
-- Phase 1.2 — Corrective actions assigned from safety reports
--
-- SQLite differences vs MySQL version:
--   - No UNSIGNED on integer columns
--   - ENUM replaced with VARCHAR + CHECK constraint
--   - No ON UPDATE CURRENT_TIMESTAMP (SQLite does not support it)
--   - No FOREIGN KEY enforcement by default (enable with PRAGMA foreign_keys = ON)
--   - No MySQL EVENT support (implement overdue marking in application code or a cron job)

CREATE TABLE IF NOT EXISTS "safety_actions" (
    "id"            INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    "report_id"     INTEGER NOT NULL,
    "tenant_id"     INTEGER NOT NULL,
    "title"         VARCHAR(255) NOT NULL,
    "description"   TEXT DEFAULT NULL,
    "assigned_to"   INTEGER DEFAULT NULL,
    "assigned_by"   INTEGER NOT NULL,
    "assigned_role" VARCHAR(100) DEFAULT NULL,
    "due_date"      DATE DEFAULT NULL,
    "status"        VARCHAR(20) NOT NULL DEFAULT 'open'
                        CHECK ("status" IN ('open','in_progress','completed','overdue','cancelled')),
    "completed_at"  DATETIME DEFAULT NULL,
    "created_at"    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at"    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("report_id")   REFERENCES "safety_reports"("id") ON DELETE CASCADE,
    FOREIGN KEY ("tenant_id")   REFERENCES "tenants"("id")        ON DELETE CASCADE,
    FOREIGN KEY ("assigned_to") REFERENCES "users"("id")          ON DELETE SET NULL,
    FOREIGN KEY ("assigned_by") REFERENCES "users"("id")          ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS "idx_sa_report"   ON "safety_actions" ("report_id");
CREATE INDEX IF NOT EXISTS "idx_sa_tenant"   ON "safety_actions" ("tenant_id");
CREATE INDEX IF NOT EXISTS "idx_sa_assignee" ON "safety_actions" ("assigned_to");
CREATE INDEX IF NOT EXISTS "idx_sa_status"   ON "safety_actions" ("status");
CREATE INDEX IF NOT EXISTS "idx_sa_due"      ON "safety_actions" ("due_date");

-- NOTE: SQLite does not support MySQL EVENTs.
-- To replicate the nightly overdue-marking behaviour, add a cron job or
-- scheduled task that runs the following SQL once per day:
--
--   UPDATE "safety_actions"
--   SET "status" = 'overdue'
--   WHERE "status" IN ('open','in_progress')
--     AND "due_date" < DATE('now');
