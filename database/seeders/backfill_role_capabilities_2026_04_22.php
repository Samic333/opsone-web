<?php
/**
 * Backfill role_capability_templates with the gaps identified by the
 * 2026-04-22 phased QA pass (Phase 9).
 *
 * Safe to run on any DB: uses INSERT IGNORE / ON CONFLICT DO NOTHING,
 * so re-running is a no-op. Does NOT modify any user data, tenant data,
 * or other seed tables — only fills missing rows in
 *   role_capability_templates(role_slug, module_capability_id).
 *
 * Usage:
 *   php database/seeders/backfill_role_capabilities_2026_04_22.php
 *
 * Works with both SQLite (local dev) and MySQL (production) because it
 * relies on the existing Database wrapper and uses standard SQL only.
 */

require dirname(__DIR__, 2) . '/config/app.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require dirname(__DIR__, 2) . '/app/Helpers/functions.php';
require dirname(__DIR__, 2) . '/config/database.php';

echo "OpsOne — Role Capability Template Backfill (2026-04-22)\n";
echo "=======================================================\n\n";

// ─── What we're adding ─────────────────────────────────────────────────────
$delta = [
    // Schedulers + Base Manager + HR + each manager need duty_reporting.view
    'airline_admin'       => ['duty_reporting' => ['view','view_history','view_all','approve_exception','correct_record','manage_settings','export','view_audit']],
    'hr'                  => [
        'duty_reporting'   => ['view','view_history','view_all'],
        'manuals'          => ['view','upload'],
        'safety_reports'   => ['view','export'],
    ],
    'scheduler'           => ['duty_reporting' => ['view','view_history']],
    'chief_pilot'         => [
        'duty_reporting'   => ['view','view_history','approve_exception'],
        'safety_reports'   => ['view','review','export'],
    ],
    'head_cabin_crew'     => [
        'duty_reporting'   => ['view','view_history','approve_exception'],
        'safety_reports'   => ['view','review','export'],
    ],
    'engineering_manager' => [
        'duty_reporting'   => ['view','view_history','approve_exception'],
        'safety_reports'   => ['view','review','export'],
    ],
    'base_manager'        => [
        'duty_reporting'   => ['view','view_history','approve_exception'],
        'manuals'          => ['view'],
        'mobile_ipad_access' => ['view'],
    ],
    'training_admin'      => [
        'compliance'       => ['view','export'],
        'mobile_ipad_access' => ['view'],
    ],
];

// ─── Driver-agnostic insert ─────────────────────────────────────────────────
$driver = env('DB_DRIVER', 'mysql');
$insertSql = $driver === 'sqlite'
    ? "INSERT OR IGNORE INTO role_capability_templates (role_slug, module_capability_id)
       SELECT ?, mc.id
         FROM module_capabilities mc
         JOIN modules m ON m.id = mc.module_id
        WHERE m.code = ? AND mc.capability = ?"
    : "INSERT IGNORE INTO role_capability_templates (role_slug, module_capability_id)
       SELECT ?, mc.id
         FROM module_capabilities mc
         JOIN modules m ON m.id = mc.module_id
        WHERE m.code = ? AND mc.capability = ?";

$applied = 0;
$skipped = 0;
$missing = [];

foreach ($delta as $role => $modules) {
    echo "• $role\n";
    foreach ($modules as $module => $caps) {
        foreach ($caps as $cap) {
            // Confirm the capability exists before trying to insert
            $exists = Database::fetch(
                "SELECT mc.id
                   FROM module_capabilities mc
                   JOIN modules m ON m.id = mc.module_id
                  WHERE m.code = ? AND mc.capability = ?",
                [$module, $cap]
            );
            if (!$exists) {
                $missing[] = "$module.$cap";
                echo "    ⚠ skip {$module}.{$cap} — capability not in catalog\n";
                $skipped++;
                continue;
            }

            // Was this already granted before the backfill?
            $already = Database::fetch(
                "SELECT 1 FROM role_capability_templates
                  WHERE role_slug = ? AND module_capability_id = ?",
                [$role, (int) $exists['id']]
            );

            Database::execute($insertSql, [$role, $module, $cap]);

            if ($already) {
                $skipped++;
            } else {
                $applied++;
                echo "    ✓ {$module}.{$cap}\n";
            }
        }
    }
}

echo "\n─────────────────────────────────────\n";
echo "  applied:  $applied new grant(s)\n";
echo "  skipped:  $skipped (already present)\n";
if (!empty($missing)) {
    echo "  missing:  " . count($missing) . " capability rows not in catalog:\n";
    foreach (array_unique($missing) as $m) echo "              $m\n";
}
echo "\nBackfill complete. No other tables were modified.\n";
