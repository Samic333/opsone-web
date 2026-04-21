<?php
/**
 * Apply Migration 023 — Phase 6 Personnel Compliance — to the local SQLite dev DB.
 *
 * Usage:
 *   php database/apply_023_phase6_compliance.php
 *
 * Idempotent — duplicate column / already-exists errors are treated as SKIP.
 * Production MySQL equivalent: 023_phase6_personnel_compliance.sql
 * (apply via phpMyAdmin as per MASTER_PHASE_PLAN deployment checklist).
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

$ok   = 0;
$skip = 0;
$err  = 0;

function run(SQLite3 $db, string $sql, int &$ok, int &$skip, int &$err): void {
    try {
        $db->exec($sql);
        echo "OK    : " . substr(preg_replace('/\s+/', ' ', trim($sql)), 0, 88) . "\n";
        $ok++;
    } catch (Exception $e) {
        $msg    = $e->getMessage();
        $benign = str_contains($msg, 'duplicate column name')
               || str_contains($msg, 'already exists')
               || str_contains($msg, 'UNIQUE constraint failed');
        if ($benign) {
            echo "SKIP  : " . substr(preg_replace('/\s+/', ' ', trim($sql)), 0, 70) . " -> " . $msg . "\n";
            $skip++;
        } else {
            echo "ERROR : " . substr(preg_replace('/\s+/', ' ', trim($sql)), 0, 70) . " -> " . $msg . "\n";
            $err++;
        }
    }
}

echo "=== Migration 023 — Phase 6 Personnel Compliance (SQLite) ===\n\n";

// Apply the raw .sql file statement by statement.
$sqlFile = __DIR__ . '/migrations/023_phase6_personnel_compliance_sqlite.sql';
if (!file_exists($sqlFile)) {
    fwrite(STDERR, "ERROR: migration file missing: $sqlFile\n");
    exit(1);
}

$sqlBlob = file_get_contents($sqlFile);

// Strip comments and split on ";" followed by newline.
$statements = [];
$current    = '';
foreach (preg_split('/\r?\n/', $sqlBlob) as $line) {
    $trim = trim($line);
    if ($trim === '' || str_starts_with($trim, '--')) continue;
    $current .= ' ' . $line;
    if (str_ends_with(rtrim($line), ';')) {
        $statements[] = trim($current);
        $current = '';
    }
}

foreach ($statements as $sql) {
    run($db, $sql, $ok, $skip, $err);
}

echo "\n=== Done ===\n";
echo "OK:   $ok\n";
echo "SKIP: $skip\n";
echo "ERR:  $err\n";
exit($err > 0 ? 1 : 0);
