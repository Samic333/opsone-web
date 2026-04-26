<?php
/**
 * Patch 043 — Backfill token_hash for existing api_tokens rows.
 *
 * Reads every row that has a non-empty plaintext `token` but a NULL
 * `token_hash`, computes sha256, and updates the row. Idempotent: rerun
 * is a no-op once every row has a hash.
 *
 * Run once after applying migration 043:
 *   php database/patches/043_backfill_token_hash.php
 */

require dirname(__DIR__, 2) . '/config/app.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require dirname(__DIR__, 2) . '/app/Helpers/functions.php';
require dirname(__DIR__, 2) . '/config/database.php';

$rows = Database::fetchAll(
    "SELECT id, token FROM api_tokens
      WHERE (token_hash IS NULL OR token_hash = '')
        AND token IS NOT NULL
        AND token <> ''"
);

$updated = 0;
foreach ($rows as $r) {
    $hash = hash('sha256', (string) $r['token']);
    Database::execute(
        "UPDATE api_tokens SET token_hash = ? WHERE id = ?",
        [$hash, (int) $r['id']]
    );
    $updated++;
}

printf("Backfilled %d api_tokens row(s).\n", $updated);
