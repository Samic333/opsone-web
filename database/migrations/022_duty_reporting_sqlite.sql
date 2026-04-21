-- =====================================================
-- Migration 022 — Duty Reporting module (SQLite)
--
-- SQLite differences vs MySQL twin (022_duty_reporting.sql):
--   • No UNSIGNED on INTEGER columns
--   • ENUM replaced with VARCHAR + CHECK constraint
--   • No ON UPDATE CURRENT_TIMESTAMP (handle in application code / triggers)
--   • FOREIGN KEYs declared but not enforced unless PRAGMA foreign_keys = ON
--   • ALTER TABLE ADD COLUMN is not idempotent; the applier script must tolerate
--     "duplicate column" errors (see apply_022_duty_reporting.php).
-- =====================================================

-- ─── 1. Extend bases with geo columns ────────────────────────────────────────
-- These ALTERs will fail harmlessly if columns already exist; the applier
-- script catches and skips the error.
ALTER TABLE "bases" ADD COLUMN "latitude"          REAL    DEFAULT NULL;
ALTER TABLE "bases" ADD COLUMN "longitude"         REAL    DEFAULT NULL;
ALTER TABLE "bases" ADD COLUMN "geofence_radius_m" INTEGER DEFAULT NULL;
ALTER TABLE "bases" ADD COLUMN "timezone"          TEXT    DEFAULT NULL;

-- ─── 2. duty_reports ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS "duty_reports" (
    "id"                 INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    "tenant_id"          INTEGER NOT NULL,
    "user_id"            INTEGER NOT NULL,
    "role_at_event"      VARCHAR(60) DEFAULT NULL,
    "state"              VARCHAR(30) NOT NULL DEFAULT 'checked_in'
                             CHECK ("state" IN (
                                 'checked_in','on_duty','checked_out',
                                 'missed_report','exception_pending_review',
                                 'exception_approved','exception_rejected'
                             )),
    "check_in_at_utc"    DATETIME DEFAULT NULL,
    "check_in_at_local"  DATETIME DEFAULT NULL,
    "check_in_lat"       REAL     DEFAULT NULL,
    "check_in_lng"       REAL     DEFAULT NULL,
    "check_in_base_id"   INTEGER  DEFAULT NULL,
    "check_in_method"    VARCHAR(30) NOT NULL DEFAULT 'device'
                             CHECK ("check_in_method" IN (
                                 'device','biometric','manual','offline_queue','admin_corrected'
                             )),
    "inside_geofence"    INTEGER  DEFAULT NULL,
    "trusted_device_id"  INTEGER  DEFAULT NULL,
    "roster_id"          INTEGER  DEFAULT NULL,
    "check_out_at_utc"   DATETIME DEFAULT NULL,
    "check_out_at_local" DATETIME DEFAULT NULL,
    "check_out_lat"      REAL     DEFAULT NULL,
    "check_out_lng"      REAL     DEFAULT NULL,
    "duration_minutes"   INTEGER  DEFAULT NULL,
    "notes"              TEXT     DEFAULT NULL,
    "device_uuid"        VARCHAR(64) DEFAULT NULL,
    "created_at"         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at"         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("tenant_id")        REFERENCES "tenants"(id) ON DELETE CASCADE,
    FOREIGN KEY ("user_id")          REFERENCES "users"(id)   ON DELETE CASCADE,
    FOREIGN KEY ("check_in_base_id") REFERENCES "bases"(id)   ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS "idx_dr_tenant"       ON "duty_reports" ("tenant_id");
CREATE INDEX IF NOT EXISTS "idx_dr_tenant_user"  ON "duty_reports" ("tenant_id", "user_id");
CREATE INDEX IF NOT EXISTS "idx_dr_tenant_state" ON "duty_reports" ("tenant_id", "state");
CREATE INDEX IF NOT EXISTS "idx_dr_checkin"      ON "duty_reports" ("tenant_id", "check_in_at_utc");
CREATE INDEX IF NOT EXISTS "idx_dr_base"         ON "duty_reports" ("check_in_base_id");
CREATE INDEX IF NOT EXISTS "idx_dr_roster"       ON "duty_reports" ("roster_id");

-- ─── 3. duty_exceptions ──────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS "duty_exceptions" (
    "id"              INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    "tenant_id"       INTEGER NOT NULL,
    "duty_report_id"  INTEGER NOT NULL,
    "reason_code"     VARCHAR(30) NOT NULL
                          CHECK ("reason_code" IN (
                              'outside_geofence','gps_unavailable','offline',
                              'forgot_clock_out','wrong_base_detected',
                              'duplicate_attempt','outstation','manual_correction',
                              'other'
                          )),
    "reason_text"     VARCHAR(1000) DEFAULT NULL,
    "submitted_by"    INTEGER NOT NULL,
    "submitted_at"    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "status"          VARCHAR(12) NOT NULL DEFAULT 'pending'
                          CHECK ("status" IN ('pending','approved','rejected')),
    "reviewed_by"     INTEGER DEFAULT NULL,
    "reviewed_at"     DATETIME DEFAULT NULL,
    "review_notes"    VARCHAR(1000) DEFAULT NULL,
    "created_at"      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at"      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("tenant_id")      REFERENCES "tenants"(id)       ON DELETE CASCADE,
    FOREIGN KEY ("duty_report_id") REFERENCES "duty_reports"(id)  ON DELETE CASCADE,
    FOREIGN KEY ("submitted_by")   REFERENCES "users"(id)         ON DELETE CASCADE,
    FOREIGN KEY ("reviewed_by")    REFERENCES "users"(id)         ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS "idx_dex_tenant"        ON "duty_exceptions" ("tenant_id");
CREATE INDEX IF NOT EXISTS "idx_dex_report"        ON "duty_exceptions" ("duty_report_id");
CREATE INDEX IF NOT EXISTS "idx_dex_tenant_status" ON "duty_exceptions" ("tenant_id", "status");
CREATE INDEX IF NOT EXISTS "idx_dex_submitter"     ON "duty_exceptions" ("submitted_by");

-- ─── 4. duty_reporting_settings ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS "duty_reporting_settings" (
    "tenant_id"                   INTEGER NOT NULL PRIMARY KEY,
    "enabled"                     INTEGER NOT NULL DEFAULT 1,
    "allowed_roles"               VARCHAR(500) NOT NULL DEFAULT 'pilot,cabin_crew,engineer',
    "geofence_required"           INTEGER NOT NULL DEFAULT 0,
    "default_radius_m"            INTEGER NOT NULL DEFAULT 500,
    "allow_outstation"            INTEGER NOT NULL DEFAULT 1,
    "exception_approval_required" INTEGER NOT NULL DEFAULT 1,
    "clock_out_reminder_minutes"  INTEGER NOT NULL DEFAULT 840,
    "trusted_device_required"     INTEGER NOT NULL DEFAULT 0,
    "biometric_required"          INTEGER NOT NULL DEFAULT 0,
    "retention_days"              INTEGER NOT NULL DEFAULT 180,
    "updated_at"                  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_by"                  INTEGER DEFAULT NULL,
    FOREIGN KEY ("tenant_id")  REFERENCES "tenants"(id) ON DELETE CASCADE,
    FOREIGN KEY ("updated_by") REFERENCES "users"(id)   ON DELETE SET NULL
);

-- ─── 5. Seed duty_reporting module + capabilities + tenant enablement ────────
INSERT OR IGNORE INTO "modules"
    (code, name, description, icon, mobile_capable, sort_order, platform_status)
VALUES (
    'duty_reporting',
    'Duty Reporting',
    'Crew report-for-duty and clock-out with geo-fence and exception handling',
    '🟢',
    1,
    55,
    'available'
);

-- Capabilities (one INSERT per capability; IF NOT EXISTS via "OR IGNORE" on UNIQUE)
INSERT OR IGNORE INTO "module_capabilities" (module_id, capability)
SELECT m.id, 'view'              FROM "modules" m WHERE m.code = 'duty_reporting';
INSERT OR IGNORE INTO "module_capabilities" (module_id, capability)
SELECT m.id, 'check_in'          FROM "modules" m WHERE m.code = 'duty_reporting';
INSERT OR IGNORE INTO "module_capabilities" (module_id, capability)
SELECT m.id, 'clock_out'         FROM "modules" m WHERE m.code = 'duty_reporting';
INSERT OR IGNORE INTO "module_capabilities" (module_id, capability)
SELECT m.id, 'view_history'      FROM "modules" m WHERE m.code = 'duty_reporting';
INSERT OR IGNORE INTO "module_capabilities" (module_id, capability)
SELECT m.id, 'view_all'          FROM "modules" m WHERE m.code = 'duty_reporting';
INSERT OR IGNORE INTO "module_capabilities" (module_id, capability)
SELECT m.id, 'approve_exception' FROM "modules" m WHERE m.code = 'duty_reporting';
INSERT OR IGNORE INTO "module_capabilities" (module_id, capability)
SELECT m.id, 'correct_record'    FROM "modules" m WHERE m.code = 'duty_reporting';
INSERT OR IGNORE INTO "module_capabilities" (module_id, capability)
SELECT m.id, 'manage_settings'   FROM "modules" m WHERE m.code = 'duty_reporting';
INSERT OR IGNORE INTO "module_capabilities" (module_id, capability)
SELECT m.id, 'export'            FROM "modules" m WHERE m.code = 'duty_reporting';
INSERT OR IGNORE INTO "module_capabilities" (module_id, capability)
SELECT m.id, 'view_audit'        FROM "modules" m WHERE m.code = 'duty_reporting';

-- Enable for all active tenants
INSERT OR IGNORE INTO "tenant_modules" (tenant_id, module_id, is_enabled)
SELECT t.id, m.id, 1
  FROM "tenants" t
  JOIN "modules" m ON m.code = 'duty_reporting'
 WHERE t.is_active = 1;

-- Default per-tenant settings
INSERT OR IGNORE INTO "duty_reporting_settings" (tenant_id)
SELECT id FROM "tenants" WHERE is_active = 1;
