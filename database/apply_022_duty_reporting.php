<?php
/**
 * Apply Migration 022 — Duty Reporting — to the local SQLite dev database.
 *
 * Usage:
 *   php database/apply_022_duty_reporting.php
 *
 * This applies the schema changes for the Duty Reporting module:
 *   • bases.latitude, longitude, geofence_radius_m, timezone (nullable)
 *   • duty_reports, duty_exceptions, duty_reporting_settings (new tables)
 *   • modules row 'duty_reporting' + capabilities + tenant enablement
 *
 * Safe to run repeatedly — existing columns/tables cause SKIP, not fail.
 * The production MySQL equivalent is 022_duty_reporting.sql (apply via
 * phpMyAdmin as per MASTER_PHASE_PLAN deployment checklist).
 */

$dbPath = __DIR__ . '/crewassist.sqlite';
if (!file_exists($dbPath)) {
    fwrite(STDERR, "ERROR: SQLite DB not found at {$dbPath}\n");
    fwrite(STDERR, "       Run the base migrations first (see apply_sqlite_migrations.php).\n");
    exit(1);
}

$db = new SQLite3($dbPath);
$db->enableExceptions(true);
$db->exec('PRAGMA foreign_keys = ON');

$ok = 0;
$skip = 0;

function run(SQLite3 $db, string $sql, int &$ok, int &$skip): void {
    try {
        $db->exec($sql);
        echo "OK   : " . substr(preg_replace('/\s+/', ' ', trim($sql)), 0, 80) . "\n";
        $ok++;
    } catch (Exception $e) {
        $msg = $e->getMessage();
        // Expected idempotency cases:
        //   "duplicate column name" → ALTER already applied
        //   "already exists"        → CREATE already applied
        $benign = str_contains($msg, 'duplicate column name')
               || str_contains($msg, 'already exists');
        $label  = $benign ? 'SKIP ' : 'ERROR';
        echo "{$label}: " . substr(preg_replace('/\s+/', ' ', trim($sql)), 0, 70)
           . " -> " . $msg . "\n";
        $benign ? $skip++ : $skip++;
    }
}

echo "=== Migration 022 — Duty Reporting (SQLite) ===\n\n";

// ─── 1. Extend bases ─────────────────────────────────────────────────────────
$alters = [
    'ALTER TABLE "bases" ADD COLUMN "latitude"          REAL    DEFAULT NULL',
    'ALTER TABLE "bases" ADD COLUMN "longitude"         REAL    DEFAULT NULL',
    'ALTER TABLE "bases" ADD COLUMN "geofence_radius_m" INTEGER DEFAULT NULL',
    'ALTER TABLE "bases" ADD COLUMN "timezone"          TEXT    DEFAULT NULL',
];
foreach ($alters as $sql) { run($db, $sql, $ok, $skip); }

// ─── 2. duty_reports ─────────────────────────────────────────────────────────
run($db, 'CREATE TABLE IF NOT EXISTS "duty_reports" (
    "id"                 INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    "tenant_id"          INTEGER NOT NULL,
    "user_id"            INTEGER NOT NULL,
    "role_at_event"      VARCHAR(60) DEFAULT NULL,
    "state"              VARCHAR(30) NOT NULL DEFAULT ' . "'checked_in'" . '
                             CHECK ("state" IN (
                                 ' . "'checked_in','on_duty','checked_out'," . '
                                 ' . "'missed_report','exception_pending_review'," . '
                                 ' . "'exception_approved','exception_rejected'" . '
                             )),
    "check_in_at_utc"    DATETIME DEFAULT NULL,
    "check_in_at_local"  DATETIME DEFAULT NULL,
    "check_in_lat"       REAL     DEFAULT NULL,
    "check_in_lng"       REAL     DEFAULT NULL,
    "check_in_base_id"   INTEGER  DEFAULT NULL,
    "check_in_method"    VARCHAR(30) NOT NULL DEFAULT ' . "'device'" . '
                             CHECK ("check_in_method" IN (
                                 ' . "'device','biometric','manual','offline_queue','admin_corrected'" . '
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
    "updated_at"         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
)', $ok, $skip);
run($db, 'CREATE INDEX IF NOT EXISTS "idx_dr_tenant"       ON "duty_reports" ("tenant_id")', $ok, $skip);
run($db, 'CREATE INDEX IF NOT EXISTS "idx_dr_tenant_user"  ON "duty_reports" ("tenant_id", "user_id")', $ok, $skip);
run($db, 'CREATE INDEX IF NOT EXISTS "idx_dr_tenant_state" ON "duty_reports" ("tenant_id", "state")', $ok, $skip);
run($db, 'CREATE INDEX IF NOT EXISTS "idx_dr_checkin"      ON "duty_reports" ("tenant_id", "check_in_at_utc")', $ok, $skip);
run($db, 'CREATE INDEX IF NOT EXISTS "idx_dr_base"         ON "duty_reports" ("check_in_base_id")', $ok, $skip);
run($db, 'CREATE INDEX IF NOT EXISTS "idx_dr_roster"       ON "duty_reports" ("roster_id")', $ok, $skip);

// ─── 3. duty_exceptions ──────────────────────────────────────────────────────
run($db, 'CREATE TABLE IF NOT EXISTS "duty_exceptions" (
    "id"              INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    "tenant_id"       INTEGER NOT NULL,
    "duty_report_id"  INTEGER NOT NULL,
    "reason_code"     VARCHAR(30) NOT NULL
                          CHECK ("reason_code" IN (
                              ' . "'outside_geofence','gps_unavailable','offline'," . '
                              ' . "'forgot_clock_out','wrong_base_detected'," . '
                              ' . "'duplicate_attempt','outstation','manual_correction'," . '
                              ' . "'other'" . '
                          )),
    "reason_text"     VARCHAR(1000) DEFAULT NULL,
    "submitted_by"    INTEGER NOT NULL,
    "submitted_at"    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "status"          VARCHAR(12) NOT NULL DEFAULT ' . "'pending'" . '
                          CHECK ("status" IN (' . "'pending','approved','rejected'" . ')),
    "reviewed_by"     INTEGER DEFAULT NULL,
    "reviewed_at"     DATETIME DEFAULT NULL,
    "review_notes"    VARCHAR(1000) DEFAULT NULL,
    "created_at"      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_at"      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
)', $ok, $skip);
run($db, 'CREATE INDEX IF NOT EXISTS "idx_dex_tenant"        ON "duty_exceptions" ("tenant_id")', $ok, $skip);
run($db, 'CREATE INDEX IF NOT EXISTS "idx_dex_report"        ON "duty_exceptions" ("duty_report_id")', $ok, $skip);
run($db, 'CREATE INDEX IF NOT EXISTS "idx_dex_tenant_status" ON "duty_exceptions" ("tenant_id", "status")', $ok, $skip);
run($db, 'CREATE INDEX IF NOT EXISTS "idx_dex_submitter"     ON "duty_exceptions" ("submitted_by")', $ok, $skip);

// ─── 4. duty_reporting_settings ──────────────────────────────────────────────
run($db, 'CREATE TABLE IF NOT EXISTS "duty_reporting_settings" (
    "tenant_id"                   INTEGER NOT NULL PRIMARY KEY,
    "enabled"                     INTEGER NOT NULL DEFAULT 1,
    "allowed_roles"               VARCHAR(500) NOT NULL DEFAULT ' . "'pilot,cabin_crew,engineer'" . ',
    "geofence_required"           INTEGER NOT NULL DEFAULT 0,
    "default_radius_m"            INTEGER NOT NULL DEFAULT 500,
    "allow_outstation"            INTEGER NOT NULL DEFAULT 1,
    "exception_approval_required" INTEGER NOT NULL DEFAULT 1,
    "clock_out_reminder_minutes"  INTEGER NOT NULL DEFAULT 840,
    "trusted_device_required"     INTEGER NOT NULL DEFAULT 0,
    "biometric_required"          INTEGER NOT NULL DEFAULT 0,
    "retention_days"              INTEGER NOT NULL DEFAULT 180,
    "updated_at"                  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updated_by"                  INTEGER DEFAULT NULL
)', $ok, $skip);

// ─── 5. Seed module + capabilities + enablement ──────────────────────────────
run($db,
    'INSERT OR IGNORE INTO "modules" (code, name, description, icon, mobile_capable, sort_order, platform_status)
     VALUES (' . "'duty_reporting','Duty Reporting'," .
           "'Crew report-for-duty and clock-out with geo-fence and exception handling'," .
           "'🟢',1,55,'available'" . ')',
$ok, $skip);

$caps = ['view','check_in','clock_out','view_history','view_all',
         'approve_exception','correct_record','manage_settings','export','view_audit'];
foreach ($caps as $cap) {
    run($db,
        'INSERT OR IGNORE INTO "module_capabilities" (module_id, capability)
         SELECT m.id, ' . "'{$cap}'" . ' FROM "modules" m WHERE m.code = ' . "'duty_reporting'",
    $ok, $skip);
}

run($db,
    'INSERT OR IGNORE INTO "tenant_modules" (tenant_id, module_id, is_enabled)
     SELECT t.id, m.id, 1
       FROM "tenants" t
       JOIN "modules" m ON m.code = ' . "'duty_reporting'" . '
      WHERE t.is_active = 1',
$ok, $skip);

run($db,
    'INSERT OR IGNORE INTO "duty_reporting_settings" (tenant_id)
     SELECT id FROM "tenants" WHERE is_active = 1',
$ok, $skip);

echo "\nDone. OK: {$ok}  SKIP/ERR: {$skip}\n\n";

// ─── Verification dump ───────────────────────────────────────────────────────
echo "Duty-reporting tables present:\n";
$tbls = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE 'duty_%' ORDER BY name");
while ($r = $tbls->fetchArray(SQLITE3_ASSOC)) {
    echo "  ✓ {$r['name']}\n";
}

$mod = $db->querySingle("SELECT id FROM modules WHERE code = 'duty_reporting'");
echo "\nduty_reporting module id: " . ($mod ?: 'NOT FOUND') . "\n";

$tmCount = $db->querySingle("SELECT COUNT(*) FROM tenant_modules tm
                             JOIN modules m ON m.id = tm.module_id
                             WHERE m.code = 'duty_reporting' AND tm.is_enabled = 1");
echo "Enabled in {$tmCount} active tenant(s)\n";

$setCount = $db->querySingle("SELECT COUNT(*) FROM duty_reporting_settings");
echo "Default settings rows: {$setCount}\n";
