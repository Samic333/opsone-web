<?php
/**
 * Test-pilot dashboard seeder.
 *
 * Populates the dashboard for `demo.pilot@acentoza.com` with realistic data
 * so the pilot-facing widgets (hero banner, today's duty, my flights, expiry
 * alerts, training, pending acks) have something to render during dogfood.
 *
 * Fully idempotent — every INSERT is guarded by an existence check so this
 * script can be re-run safely.
 *
 * Run:
 *   php database/seeders/seed_test_pilot.php
 *
 * Schema-aware: columns match the live opsone-web production database
 * (verified 2026-04-25 against /home/fruinxrj/acentoza.com).
 */

require dirname(__DIR__, 2) . '/config/app.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require dirname(__DIR__, 2) . '/app/Helpers/functions.php';
require dirname(__DIR__, 2) . '/config/database.php';

const PILOT_EMAIL = 'demo.pilot@acentoza.com';

$insertCount = 0;
$skipCount   = 0;

function note(string $prefix, string $msg): void {
    echo sprintf("  %-6s %s\n", $prefix, $msg);
}

// ─── Resolve pilot + tenant + base ─────────────────────────────
$pilot = Database::fetch(
    "SELECT id, tenant_id, base_id, name FROM users WHERE email = ? LIMIT 1",
    [PILOT_EMAIL]
);
if (!$pilot) {
    echo "✗ Test pilot " . PILOT_EMAIL . " not found — run seed.php first.\n";
    exit(1);
}
$pilotId   = (int)$pilot['id'];
$tenantId  = (int)$pilot['tenant_id'];
$baseId    = $pilot['base_id'] ? (int)$pilot['base_id'] : null;
$pilotName = (string)$pilot['name'];

echo "🌱 Seeding dashboard data for $pilotName (user#$pilotId, tenant#$tenantId)\n\n";

// ─── 1. Roster period (next 14 days) ───────────────────────────
// Live schema: id, tenant_id, name, start_date, end_date, status, notes, crew_group
echo "Roster period...\n";
$periodStart = date('Y-m-d');
$periodEnd   = date('Y-m-d', strtotime('+14 days'));
$periodName  = 'Rotation ' . date('M j') . ' – ' . date('M j', strtotime('+14 days'));

$existingPeriod = Database::fetch(
    "SELECT id FROM roster_periods
      WHERE tenant_id = ? AND start_date = ? AND end_date = ?",
    [$tenantId, $periodStart, $periodEnd]
);
if (!$existingPeriod) {
    $periodId = Database::insert(
        "INSERT INTO roster_periods (tenant_id, name, start_date, end_date, status)
         VALUES (?, ?, ?, ?, 'published')",
        [$tenantId, $periodName, $periodStart, $periodEnd]
    );
    note('ADD', "roster_period #$periodId ($periodStart → $periodEnd)");
    $insertCount++;
} else {
    $periodId = (int)$existingPeriod['id'];
    note('SKIP', "roster_period #$periodId already exists");
    $skipCount++;
}

// ─── 2. Roster entries (today / tomorrow / day-after) ──────────
// Live schema: tenant_id, user_id, roster_date, duty_type, duty_code, notes,
//              roster_period_id, base_id, ...
// duty_type ENUM('flight','standby','off','training','sim','leave','rest')
echo "\nRoster entries...\n";
$rosterDays = [
    [date('Y-m-d'),                        'flight',  'FLT'],
    [date('Y-m-d', strtotime('+1 day')),   'standby', 'STBY'],
    [date('Y-m-d', strtotime('+2 day')),   'off',     'OFF'],
];
foreach ($rosterDays as [$d, $type, $code]) {
    $exists = Database::fetch(
        "SELECT id FROM rosters WHERE tenant_id = ? AND user_id = ? AND roster_date = ?",
        [$tenantId, $pilotId, $d]
    );
    if ($exists) { note('SKIP', "roster $d ($code)"); $skipCount++; continue; }
    Database::insert(
        "INSERT INTO rosters (tenant_id, user_id, roster_date, duty_type, duty_code, roster_period_id, base_id)
         VALUES (?,?,?,?,?,?,?)",
        [$tenantId, $pilotId, $d, $type, $code, $periodId, $baseId]
    );
    note('ADD', "roster $d → $code");
    $insertCount++;
}

// ─── 3. Two published flights in the next 3 days ───────────────
echo "\nFlights...\n";
$aircraft = Database::fetch(
    "SELECT id FROM aircraft WHERE tenant_id = ? LIMIT 1",
    [$tenantId]
);
$aircraftId = $aircraft ? (int)$aircraft['id'] : null;

$flightPlans = [
    [
        'flight_date'   => date('Y-m-d', strtotime('+1 day')),
        'flight_number' => 'MZ214',
        'departure'     => 'HKJK',
        'arrival'       => 'HUEN',
        'std'           => '06:30:00',
        'sta'           => '07:45:00',
    ],
    [
        'flight_date'   => date('Y-m-d', strtotime('+3 day')),
        'flight_number' => 'MZ218',
        'departure'     => 'HUEN',
        'arrival'       => 'HKJK',
        'std'           => '11:10:00',
        'sta'           => '12:25:00',
    ],
];
foreach ($flightPlans as $p) {
    $exists = Database::fetch(
        "SELECT id FROM flights
          WHERE tenant_id = ? AND flight_number = ? AND flight_date = ?",
        [$tenantId, $p['flight_number'], $p['flight_date']]
    );
    if ($exists) {
        note('SKIP', "flight {$p['flight_number']} {$p['flight_date']}");
        $skipCount++;
        continue;
    }
    $flightId = Database::insert(
        "INSERT INTO flights
            (tenant_id, flight_date, flight_number, departure, arrival, std, sta,
             aircraft_id, captain_id, status)
         VALUES (?,?,?,?,?,?,?,?,?, 'published')",
        [
            $tenantId, $p['flight_date'], $p['flight_number'],
            $p['departure'], $p['arrival'], $p['std'], $p['sta'],
            $aircraftId, $pilotId,
        ]
    );
    note('ADD', "flight #$flightId {$p['flight_number']} {$p['departure']}→{$p['arrival']} ({$p['flight_date']})");
    $insertCount++;
}

// ─── 4. Notice requiring acknowledgement ───────────────────────
// Live schema: notices.priority ENUM('normal','urgent','critical')
// Live schema: notices.requires_ack (NOT ack_required)
echo "\nNotice (requires_ack)...\n";
$noticeTitle = 'Updated duty-time crew rest policy';
$existing = Database::fetch(
    "SELECT id FROM notices WHERE tenant_id = ? AND title = ? LIMIT 1",
    [$tenantId, $noticeTitle]
);
if ($existing) {
    note('SKIP', "notice \"$noticeTitle\" exists");
    $skipCount++;
} else {
    Database::insert(
        "INSERT INTO notices
            (tenant_id, title, body, priority, category, requires_ack, published, published_at)
         VALUES (?,?,?,?,?,?,?,NOW())",
        [
            $tenantId, $noticeTitle,
            'Please review the revised crew rest requirements before your next duty. Acknowledge to confirm.',
            'urgent', 'operations', 1, 1,
        ]
    );
    note('ADD', "notice \"$noticeTitle\" (requires_ack)");
    $insertCount++;
}

// ─── 5. Crew document expiring in 12 days ──────────────────────
// Live schema: crew_documents.doc_type (NOT document_type), doc_title required
echo "\nCrew document (expiring soon)...\n";
$expiryDate = date('Y-m-d', strtotime('+12 days'));
$existingDoc = Database::fetch(
    "SELECT id FROM crew_documents
      WHERE user_id = ? AND doc_type = 'medical' AND expiry_date = ?",
    [$pilotId, $expiryDate]
);
if ($existingDoc) {
    note('SKIP', "medical expiring $expiryDate exists");
    $skipCount++;
} else {
    Database::insert(
        "INSERT INTO crew_documents
            (tenant_id, user_id, doc_type, doc_title, doc_number,
             issue_date, expiry_date, status, uploaded_by)
         VALUES (?,?,?,?,?,?,?, 'valid', ?)",
        [
            $tenantId, $pilotId, 'medical',
            'Medical Class 1 Certificate',
            'MED-2026-' . $pilotId,
            date('Y-m-d', strtotime('-11 months')),
            $expiryDate,
            $pilotId,
        ]
    );
    note('ADD', "medical class 1 expiring $expiryDate");
    $insertCount++;
}

// ─── 6. Training record expiring in 45 days ────────────────────
// Live schema: training_types.code (NOT type_code); training_records has its own type_code denormalized
echo "\nTraining record...\n";
$trainingExpiry = date('Y-m-d', strtotime('+45 days'));
$ttype = Database::fetch(
    "SELECT id FROM training_types
      WHERE tenant_id = ? AND (code = 'RECURRENT' OR name LIKE '%recurrent%') LIMIT 1",
    [$tenantId]
);
if (!$ttype) {
    $typeId = Database::insert(
        "INSERT INTO training_types (tenant_id, name, code, validity_months)
         VALUES (?,?,?,?)",
        [$tenantId, 'Recurrent Training', 'RECURRENT', 12]
    );
    note('ADD', "training_type #$typeId Recurrent Training");
    $insertCount++;
} else {
    $typeId = (int)$ttype['id'];
    note('SKIP', "training_type #$typeId already exists");
    $skipCount++;
}

$existingRec = Database::fetch(
    "SELECT id FROM training_records
      WHERE tenant_id = ? AND user_id = ? AND training_type_id = ? AND expires_date = ?",
    [$tenantId, $pilotId, $typeId, $trainingExpiry]
);
if ($existingRec) {
    note('SKIP', "training record expiring $trainingExpiry exists");
    $skipCount++;
} else {
    Database::insert(
        "INSERT INTO training_records
            (tenant_id, user_id, training_type_id, type_code,
             completed_date, expires_date, provider, result)
         VALUES (?,?,?,?,?,?,?, 'pass')",
        [
            $tenantId, $pilotId, $typeId, 'RECURRENT',
            date('Y-m-d', strtotime('-11 months')),
            $trainingExpiry,
            'CAE Approved',
        ]
    );
    note('ADD', "training_record recurrent expires $trainingExpiry");
    $insertCount++;
}

echo "\n";
echo "─────────────────────────────────────────\n";
echo "✓ Done — $insertCount added, $skipCount skipped.\n";
echo "Log in as " . PILOT_EMAIL . " on mobile to verify.\n";
