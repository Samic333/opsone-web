<?php
/**
 * teardown_tenant — fully remove a tenant and every row that points at it,
 * including rows in tables that were created without ON DELETE CASCADE on
 * tenant_id (`safety_reports`, `duty_reports`, `notifications`) and the
 * users with `tenant_id ON DELETE SET NULL` semantics (which would otherwise
 * leave orphan rows after `DELETE FROM tenants`).
 *
 * Usage:
 *   php bin/teardown_tenant.php DMA           # by code
 *   php bin/teardown_tenant.php --id=6        # by id
 *
 * LOCAL ONLY. Refuses to run unless APP_ENV is local/dev/development.
 * Idempotent. Designed for QA scenarios — never wire to a route.
 */

$ROOT = dirname(__DIR__);
require $ROOT . '/config/app.php';
loadEnv($ROOT . '/.env');
require $ROOT . '/app/Helpers/functions.php';
require $ROOT . '/config/database.php';

$env = env('APP_ENV', 'production');
if (!in_array($env, ['local', 'dev', 'development'], true)) {
    fwrite(STDERR, "REFUSED: APP_ENV={$env} — this script only runs in local/dev.\n");
    exit(2);
}

if ($argc < 2) {
    fwrite(STDERR, "Usage: php bin/teardown_tenant.php <CODE>  or  --id=N\n");
    exit(64);
}

$arg = $argv[1];
if (str_starts_with($arg, '--id=')) {
    $tenantId = (int) substr($arg, 5);
} else {
    $row = Database::fetch("SELECT id FROM tenants WHERE code = ?", [strtoupper($arg)]);
    $tenantId = $row['id'] ?? 0;
}

if ($tenantId <= 0) {
    fwrite(STDERR, "Tenant not found.\n");
    exit(1);
}

$pdo = Database::getInstance();
$pdo->exec('PRAGMA foreign_keys = ON;'); // SQLite-only; no-op on MySQL

// Tables that point at tenants WITHOUT ON DELETE CASCADE — clear explicitly.
$nonCascading = [
    'safety_reports',
    'duty_reports',
    'notifications',
    'safety_actions',
    'safety_publications',
    'safety_module_settings',
    'training_records',
    'training_types',
    'per_diem_rates',
    'per_diem_claims',
    'appraisals',
    'flight_logs',
    'fdm_events',
    'fdm_uploads',
    'expiry_alerts',
    'compliance_change_requests',
    'crew_documents',
    'crew_profiles',
    'qualifications',
    'licenses',
];

$totalRowsRemoved = 0;
foreach ($nonCascading as $tbl) {
    try {
        $cnt = (int) Database::execute("DELETE FROM $tbl WHERE tenant_id = ?", [$tenantId]);
        if ($cnt > 0) {
            printf("  cleared %s: %d row(s)\n", $tbl, $cnt);
            $totalRowsRemoved += $cnt;
        }
    } catch (\Throwable $e) {
        // Some tables may not have tenant_id depending on schema version — log and continue.
        // (qualifications.tenant_id only exists from 014_phase3 onwards, etc.)
        if (!str_contains($e->getMessage(), 'no such column')) {
            fwrite(STDERR, "  warn ($tbl): " . $e->getMessage() . "\n");
        }
    }
}

// users.tenant_id is ON DELETE SET NULL — explicit DELETE so we don't
// leave orphan rows colliding with future re-onboarding attempts.
$users = (int) Database::execute("DELETE FROM users WHERE tenant_id = ?", [$tenantId]);
printf("  cleared users: %d\n", $users);
$totalRowsRemoved += $users;

// Now drop the tenant — CASCADE wipes the rest (departments, bases, fleets,
// roles, tenant_modules, tenant_contacts, etc.).
$dropped = (int) Database::execute("DELETE FROM tenants WHERE id = ?", [$tenantId]);
printf("  dropped tenant id=%d: %s\n", $tenantId, $dropped ? 'yes' : 'already gone');

printf("\nTeardown complete. Removed %d non-cascading row(s) + cascaded the rest.\n", $totalRowsRemoved);
