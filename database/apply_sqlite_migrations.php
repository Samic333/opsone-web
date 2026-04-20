<?php
/**
 * Apply Phase 1 Safety Reporting migrations to SQLite dev DB.
 * Run once: php database/apply_sqlite_migrations.php
 */

$db = new SQLite3(__DIR__ . '/crewassist.sqlite');
$db->enableExceptions(true);

$ok  = 0;
$err = 0;

function run(SQLite3 $db, string $sql, string &$ok, string &$err): void {
    try {
        $db->exec($sql);
        echo "OK:   " . substr(trim($sql), 0, 70) . PHP_EOL;
        $ok++;
    } catch (Exception $e) {
        echo "SKIP: " . substr(trim($sql), 0, 60) . " -> " . $e->getMessage() . PHP_EOL;
        $err++;
    }
}

// ── Phase 1: ALTER safety_reports ────────────────────────────────────────────
$alters = [
    "ALTER TABLE safety_reports ADD COLUMN is_draft INTEGER NOT NULL DEFAULT 0",
    "ALTER TABLE safety_reports ADD COLUMN event_utc_time TEXT NULL",
    "ALTER TABLE safety_reports ADD COLUMN event_local_time TEXT NULL",
    "ALTER TABLE safety_reports ADD COLUMN location_name TEXT NULL",
    "ALTER TABLE safety_reports ADD COLUMN icao_code TEXT NULL",
    "ALTER TABLE safety_reports ADD COLUMN occurrence_type TEXT NOT NULL DEFAULT 'occurrence'",
    "ALTER TABLE safety_reports ADD COLUMN event_type TEXT NULL",
    "ALTER TABLE safety_reports ADD COLUMN initial_risk_score INTEGER NULL",
    "ALTER TABLE safety_reports ADD COLUMN final_severity TEXT NULL",
    "ALTER TABLE safety_reports ADD COLUMN aircraft_registration TEXT NULL",
    "ALTER TABLE safety_reports ADD COLUMN call_sign TEXT NULL",
    "ALTER TABLE safety_reports ADD COLUMN extra_fields TEXT NULL",
    "ALTER TABLE safety_reports ADD COLUMN submitted_at TEXT NULL",
    "ALTER TABLE safety_reports ADD COLUMN closed_at TEXT NULL",
    "ALTER TABLE safety_reports ADD COLUMN template_version INTEGER NOT NULL DEFAULT 1",
    "ALTER TABLE safety_reports ADD COLUMN reporter_position TEXT NULL",
];
foreach ($alters as $sql) { run($db, $sql, $ok, $err); }

// ── safety_report_threads ─────────────────────────────────────────────────────
run($db, "CREATE TABLE IF NOT EXISTS safety_report_threads (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    report_id INTEGER NOT NULL,
    author_id INTEGER NOT NULL,
    body TEXT NOT NULL,
    is_internal INTEGER NOT NULL DEFAULT 0,
    parent_id INTEGER NULL DEFAULT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
)", $ok, $err);
run($db, "CREATE INDEX IF NOT EXISTS idx_srt_report ON safety_report_threads (report_id)", $ok, $err);
run($db, "CREATE INDEX IF NOT EXISTS idx_srt_author ON safety_report_threads (author_id)", $ok, $err);

// ── safety_report_attachments ─────────────────────────────────────────────────
run($db, "CREATE TABLE IF NOT EXISTS safety_report_attachments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    report_id INTEGER NOT NULL,
    thread_id INTEGER NULL DEFAULT NULL,
    uploaded_by INTEGER NOT NULL,
    file_name TEXT NOT NULL,
    file_path TEXT NOT NULL,
    file_type TEXT NOT NULL,
    file_size INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
)", $ok, $err);
run($db, "CREATE INDEX IF NOT EXISTS idx_sra_report ON safety_report_attachments (report_id)", $ok, $err);

// ── safety_report_status_history ──────────────────────────────────────────────
run($db, "CREATE TABLE IF NOT EXISTS safety_report_status_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    report_id INTEGER NOT NULL,
    changed_by INTEGER NOT NULL,
    from_status TEXT NULL,
    to_status TEXT NOT NULL,
    comment TEXT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
)", $ok, $err);
run($db, "CREATE INDEX IF NOT EXISTS idx_srsh_report ON safety_report_status_history (report_id)", $ok, $err);

// ── safety_report_assignments ─────────────────────────────────────────────────
run($db, "CREATE TABLE IF NOT EXISTS safety_report_assignments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    report_id INTEGER NOT NULL,
    assigned_by INTEGER NOT NULL,
    assigned_to INTEGER NULL DEFAULT NULL,
    unassigned_at TEXT NULL DEFAULT NULL,
    note TEXT NULL DEFAULT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
)", $ok, $err);
run($db, "CREATE INDEX IF NOT EXISTS idx_sra2_report ON safety_report_assignments (report_id)", $ok, $err);

// ── safety_publications ───────────────────────────────────────────────────────
run($db, "CREATE TABLE IF NOT EXISTS safety_publications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER NOT NULL,
    created_by INTEGER NOT NULL,
    title TEXT NOT NULL,
    summary TEXT NULL,
    content TEXT NOT NULL,
    related_report_id INTEGER NULL DEFAULT NULL,
    status TEXT NOT NULL DEFAULT 'draft',
    published_at TEXT NULL DEFAULT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
)", $ok, $err);
run($db, "CREATE INDEX IF NOT EXISTS idx_sp_tenant ON safety_publications (tenant_id)", $ok, $err);
run($db, "CREATE INDEX IF NOT EXISTS idx_sp_status ON safety_publications (status)", $ok, $err);

// ── safety_publication_audiences ──────────────────────────────────────────────
run($db, "CREATE TABLE IF NOT EXISTS safety_publication_audiences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    publication_id INTEGER NOT NULL,
    audience_type TEXT NOT NULL,
    department_id INTEGER NULL DEFAULT NULL,
    UNIQUE (publication_id, audience_type)
)", $ok, $err);

// ── safety_module_settings ────────────────────────────────────────────────────
run($db, "CREATE TABLE IF NOT EXISTS safety_module_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER NOT NULL UNIQUE,
    enabled_types TEXT NULL,
    allow_anonymous INTEGER NOT NULL DEFAULT 1,
    require_aircraft_reg INTEGER NOT NULL DEFAULT 0,
    risk_matrix_enabled INTEGER NOT NULL DEFAULT 1,
    retention_days INTEGER NOT NULL DEFAULT 2555,
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
)", $ok, $err);

// Seed — use is_active (SQLite has no 'status' column)
run($db,
    "INSERT OR IGNORE INTO safety_module_settings (tenant_id, enabled_types)
     SELECT id, '[\"general_hazard\",\"flight_crew_occurrence\",\"maintenance_engineering\",\"ground_ops\",\"quality\",\"hse\",\"tcas\",\"environmental\",\"frat\"]'
     FROM tenants WHERE is_active = 1",
$ok, $err);

// ── safety_actions ────────────────────────────────────────────────────────────
run($db, "CREATE TABLE IF NOT EXISTS safety_actions (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    report_id INTEGER NOT NULL,
    tenant_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    description TEXT DEFAULT NULL,
    assigned_to INTEGER DEFAULT NULL,
    assigned_by INTEGER NOT NULL,
    assigned_role TEXT DEFAULT NULL,
    due_date TEXT DEFAULT NULL,
    status TEXT NOT NULL DEFAULT 'open',
    completed_at TEXT DEFAULT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
)", $ok, $err);
run($db, "CREATE INDEX IF NOT EXISTS idx_sa_report ON safety_actions (report_id)", $ok, $err);
run($db, "CREATE INDEX IF NOT EXISTS idx_sa_tenant ON safety_actions (tenant_id)", $ok, $err);
run($db, "CREATE INDEX IF NOT EXISTS idx_sa_assignee ON safety_actions (assigned_to)", $ok, $err);
run($db, "CREATE INDEX IF NOT EXISTS idx_sa_status ON safety_actions (status)", $ok, $err);

// ── notifications ─────────────────────────────────────────────────────────────
run($db, "CREATE TABLE IF NOT EXISTS notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    body TEXT NOT NULL,
    link TEXT DEFAULT NULL,
    is_read INTEGER NOT NULL DEFAULT 0,
    read_at TEXT NULL DEFAULT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
)", $ok, $err);
run($db, "CREATE INDEX IF NOT EXISTS idx_notif_tenant_user ON notifications (tenant_id, user_id)", $ok, $err);
run($db, "CREATE INDEX IF NOT EXISTS idx_notif_unread ON notifications (user_id, is_read)", $ok, $err);

// ── tenant_retention_policies ─────────────────────────────────────────────────
run($db, "CREATE TABLE IF NOT EXISTS tenant_retention_policies (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER NOT NULL,
    module TEXT NOT NULL,
    retain_days INTEGER NOT NULL,
    note TEXT DEFAULT NULL,
    updated_by INTEGER DEFAULT NULL,
    updated_at TEXT NOT NULL DEFAULT (datetime('now')),
    UNIQUE (tenant_id, module)
)", $ok, $err);

// ── Report ────────────────────────────────────────────────────────────────────
echo PHP_EOL . "Done. OK: $ok  SKIPPED/ERR: $err" . PHP_EOL . PHP_EOL;

$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
echo "Final table list:" . PHP_EOL;
while ($r = $tables->fetchArray(SQLITE3_ASSOC)) {
    echo "  " . $r['name'] . PHP_EOL;
}
