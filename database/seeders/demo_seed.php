<?php
/**
 * OpsOne Demo Environment Seeder
 * ─────────────────────────────────────────────────────
 * Wipes and re-creates the complete demo tenant with all
 * 18 role archetypes, sample data, notices, files,
 * devices, and notice reads.
 *
 * Run:   php database/seeders/demo_seed.php
 *
 * DEMO CREDENTIALS
 *   Password for ALL demo accounts: DemoOps2026!
 *   Email pattern:                  demo.[role]@acentoza.com
 *
 * Safe to re-run — deletes only demo.* @acentoza.com users
 * and tenant-1 content. Does NOT touch tenant 2+.
 */

require dirname(__DIR__, 2) . '/config/app.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require dirname(__DIR__, 2) . '/app/Helpers/functions.php';
require dirname(__DIR__, 2) . '/config/database.php';

echo "\n🌱  OpsOne Demo Environment Seeder\n";
echo str_repeat('─', 55) . "\n\n";

$driver = env('DB_DRIVER', 'mysql');
$insertIgnore = ($driver === 'sqlite') ? 'INSERT OR IGNORE' : 'INSERT IGNORE';

try {
    $db = Database::getInstance();

    // ─── 0. Patch SQLite schema (migrations 004+005) ───────
    if ($driver === 'sqlite') {
        echo "Patching SQLite schema...\n";

        $ddl = [
            // Migration 004 tables
            "CREATE TABLE IF NOT EXISTS notice_reads (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                notice_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                tenant_id INTEGER NOT NULL,
                read_at TEXT NOT NULL DEFAULT (datetime('now')),
                acknowledged_at TEXT DEFAULT NULL,
                FOREIGN KEY (notice_id) REFERENCES notices(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
                UNIQUE (notice_id, user_id)
            )",
            "CREATE TABLE IF NOT EXISTS file_acknowledgements (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                file_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                tenant_id INTEGER NOT NULL,
                version TEXT DEFAULT NULL,
                device_id INTEGER DEFAULT NULL,
                acknowledged_at TEXT NOT NULL DEFAULT (datetime('now')),
                UNIQUE (file_id, user_id)
            )",
            // Notices columns from migration 004
            "ALTER TABLE notices ADD COLUMN requires_ack INTEGER NOT NULL DEFAULT 0",
            "ALTER TABLE notices ADD COLUMN target_roles TEXT DEFAULT NULL",
            // Migration 005 — web_access
            "ALTER TABLE users ADD COLUMN web_access INTEGER NOT NULL DEFAULT 1",
        ];

        foreach ($ddl as $sql) {
            try {
                $db->exec($sql);
            } catch (\PDOException $e) {
                // Ignore "already exists" / "duplicate column" errors
            }
        }
        echo "  ✓ Schema patches applied\n\n";
    }

    // ─── 1. System roles ──────────────────────────────────
    echo "Seeding system roles... ";

    $systemRoles = [
        // slug                    display name                    description
        ['super_admin',          'Super Admin',                  'Full platform access',                      1],
        ['platform_support',     'Platform Support Admin',       'Read-only platform support access',         1],
        ['platform_security',    'Platform Security Admin',      'Security monitoring and audit access',      1],
        ['airline_admin',        'Airline Admin',                'Airline-level administration',              1],
        ['hr',                   'HR Admin',                     'Human resources management',                1],
        ['scheduler',            'Scheduler Admin',              'Crew scheduling and control',               1],
        ['chief_pilot',          'Chief Pilot',                  'Chief pilot — flight ops oversight',        1],
        ['head_cabin_crew',      'Head of Cabin Crew',           'Head of cabin crew department',             1],
        ['engineering_manager',  'Engineering Manager',          'Engineering/maintenance management',        1],
        ['safety_officer',       'Safety Manager',               'Safety management and compliance',          1],
        ['fdm_analyst',          'FDM Analyst',                  'Flight data monitoring',                    1],
        ['document_control',     'Document Control Manager',     'Document management',                       1],
        ['base_manager',         'Base Manager',                 'Base operations management',                1],
        ['pilot',                'Pilot',                        'Flight crew — pilot',                       1],
        ['cabin_crew',           'Cabin Crew',                   'Cabin crew member',                         1],
        ['engineer',             'Engineer',                     'Engineering/maintenance technician',         1],
        ['training_admin',       'Training Admin',               'Training programme administration',         1],
        ['system_monitoring',    'System Monitoring Admin',      'System health and sync monitoring',         1],
        ['director',             'Director',                     'Executive director',                        1],
    ];

    $sysStmt = $driver === 'sqlite'
        ? $db->prepare("INSERT OR IGNORE INTO roles (tenant_id, name, slug, description, is_system) VALUES (NULL, ?, ?, ?, ?)")
        : $db->prepare("INSERT IGNORE INTO roles (tenant_id, name, slug, description, is_system) VALUES (NULL, ?, ?, ?, ?)");

    foreach ($systemRoles as [$slug, $name, $desc, $isSys]) {
        $sysStmt->execute([$name, $slug, $desc, $isSys]);
    }
    // Update display names for any already-existing rows
    $updStmt = $db->prepare("UPDATE roles SET name = ? WHERE slug = ? AND tenant_id IS NULL");
    foreach ($systemRoles as [$slug, $name]) {
        $updStmt->execute([$name, $slug]);
    }
    echo "✓\n";

    // ─── 2. Demo tenant ───────────────────────────────────
    echo "Configuring demo tenant... ";

    $hasTenant = $db->query("SELECT id FROM tenants WHERE id = 1")->fetchColumn();
    if ($hasTenant) {
        $db->prepare("UPDATE tenants SET name = ?, code = ?, contact_email = ?, is_active = 1 WHERE id = 1")
           ->execute(['OpsOne Demo Airline', 'ODA', 'admin@opsone-demo.aero']);
    } else {
        $db->prepare("$insertIgnore INTO tenants (id, name, code, contact_email, is_active) VALUES (1,?,?,?,1)")
           ->execute(['OpsOne Demo Airline', 'ODA', 'admin@opsone-demo.aero']);
    }
    echo "✓\n";

    // ─── 3. Tenant role copies ────────────────────────────
    echo "Seeding tenant role copies... ";
    $tenantRoleStmt = $db->prepare(
        "$insertIgnore INTO roles (tenant_id, name, slug, description, is_system) VALUES (1, ?, ?, ?, 0)"
    );
    foreach ($systemRoles as [$slug, $name, $desc]) {
        $tenantRoleStmt->execute([$name, $slug, $desc]);
    }
    // Also update existing tenant-1 role display names
    $updTenantStmt = $db->prepare("UPDATE roles SET name = ? WHERE slug = ? AND tenant_id = 1");
    foreach ($systemRoles as [$slug, $name]) {
        $updTenantStmt->execute([$name, $slug]);
    }
    echo "✓\n";

    // ─── 4. Departments ───────────────────────────────────
    echo "Seeding departments... ";
    $db->exec("DELETE FROM departments WHERE tenant_id = 1");
    $depts = [
        ['Flight Operations',    'FLT'],
        ['Cabin Services',       'CAB'],
        ['Engineering',          'ENG'],
        ['Human Resources',      'HR'],
        ['Safety',               'SAF'],
        ['Crew Control',         'CTC'],
        ['Flight Data (FDM)',    'FDM'],
        ['Document Control',     'DOC'],
        ['Base Management',      'BMS'],
        ['Management',           'MGT'],
    ];
    $deptStmt = $db->prepare("INSERT INTO departments (tenant_id, name, code) VALUES (1, ?, ?)");
    foreach ($depts as [$name, $code]) {
        $deptStmt->execute([$name, $code]);
    }
    echo "✓\n";

    // ─── 5. Bases ─────────────────────────────────────────
    echo "Seeding bases... ";
    $db->exec("DELETE FROM bases WHERE tenant_id = 1");
    $bases = [
        ['Nairobi HQ',   'NBO'],
        ['Entebbe',      'EBB'],
        ['Kinshasa',     'FIH'],
        ['Juba',         'JUB'],
        ['Addis Ababa',  'ADD'],
        ['Niamey',       'NIM'],
    ];
    $baseStmt = $db->prepare("INSERT INTO bases (tenant_id, name, code) VALUES (1, ?, ?)");
    foreach ($bases as [$name, $code]) {
        $baseStmt->execute([$name, $code]);
    }
    echo "✓\n";

    // ─── 6. Clear old demo users ──────────────────────────
    echo "Clearing old demo users... ";
    $db->exec("DELETE FROM users WHERE tenant_id = 1 AND (email LIKE '%@airline.com' OR email LIKE 'demo.%@acentoza.com')");
    echo "✓\n";

    // ─── 7. Reload maps ───────────────────────────────────
    $deptMap = $db->query("SELECT code, id FROM departments WHERE tenant_id = 1")->fetchAll(PDO::FETCH_KEY_PAIR);
    $baseMap = $db->query("SELECT code, id FROM bases WHERE tenant_id = 1")->fetchAll(PDO::FETCH_KEY_PAIR);
    $pw = password_hash('DemoOps2026!', PASSWORD_BCRYPT);

    // ─── 8. Demo users ────────────────────────────────────
    echo "Creating demo users... ";
    // [name, email, emp_id, dept_code, base_code, web_access, mobile_access]
    $userDefs = [
        // Platform level (web portal only — no mobile)
        ['Alex Mwangi',           'demo.superadmin@acentoza.com',  'PLT-001', 'MGT', 'NBO', 1, 0],
        ['Jordan Taylor',         'demo.support@acentoza.com',     'PLT-002', 'MGT', 'NBO', 1, 0],
        ['Sarah Kimani',          'demo.security@acentoza.com',    'PLT-003', 'MGT', 'NBO', 1, 0],
        // Airline management
        ['Amara Diallo',          'demo.airadmin@acentoza.com',    'ODA-001', 'MGT', 'NBO', 1, 0],
        ['Fatima Al-Zaabi',       'demo.hr@acentoza.com',          'HR-001',  'HR',  'NBO', 1, 0],
        ['Layla Hassan',          'demo.scheduler@acentoza.com',   'CTC-001', 'CTC', 'NBO', 1, 0],
        ['Capt. Ahmed Mansoori',  'demo.chiefpilot@acentoza.com',  'FLT-CP1', 'FLT', 'NBO', 1, 0],
        ['Grace Okonkwo',         'demo.headcabin@acentoza.com',   'CAB-HM1', 'CAB', 'NBO', 1, 0],
        ['Mark Sullivan',         'demo.engmanager@acentoza.com',  'ENG-MGR', 'ENG', 'NBO', 1, 0],
        ['Dr. Nadia Okelo',       'demo.safety@acentoza.com',      'SAF-001', 'SAF', 'NBO', 1, 0],
        ['Dr. Priya Sharma',      'demo.fdm@acentoza.com',         'FDM-001', 'FDM', 'NBO', 1, 0],
        ['Sara Khalid',           'demo.doccontrol@acentoza.com',  'DOC-001', 'DOC', 'NBO', 1, 0],
        ['Joseph Kariuki',        'demo.basemanager@acentoza.com', 'BMS-NBO', 'BMS', 'NBO', 1, 0],
        // Operational (web + mobile)
        ['Capt. Rashid Hussein',  'demo.pilot@acentoza.com',       'FLT-001', 'FLT', 'NBO', 1, 1],
        ['Noor Al-Rashidi',       'demo.cabin@acentoza.com',       'CAB-020', 'CAB', 'EBB', 1, 1],
        ['Eric Mbeki',            'demo.engineer@acentoza.com',    'ENG-007', 'ENG', 'NBO', 1, 1],
        // Optional roles
        ['Ruth Nambozo',          'demo.training@acentoza.com',    'TRN-001', 'HR',  'NBO', 1, 0],
        ['James Okafor',          'demo.sysmonitor@acentoza.com',  'SYS-001', 'MGT', 'NBO', 1, 0],
    ];

    // Build INSERT — handle whether web_access column exists (MySQL may need migration 005 first)
    $hasWebAccess = true;
    if ($driver === 'mysql') {
        $cols = $db->query("SHOW COLUMNS FROM users LIKE 'web_access'")->fetchColumn();
        $hasWebAccess = !empty($cols);
    }

    if ($hasWebAccess) {
        $userStmt = $db->prepare(
            "INSERT INTO users (tenant_id, name, email, employee_id, department_id, base_id, status, password_hash, web_access, mobile_access)
             VALUES (1, ?, ?, ?, ?, ?, 'active', ?, ?, ?)"
        );
        foreach ($userDefs as [$name, $email, $empId, $dept, $base, $webA, $mobileA]) {
            $userStmt->execute([$name, $email, $empId, $deptMap[$dept] ?? null, $baseMap[$base] ?? null, $pw, $webA, $mobileA]);
        }
    } else {
        $userStmt = $db->prepare(
            "INSERT INTO users (tenant_id, name, email, employee_id, department_id, base_id, status, password_hash, mobile_access)
             VALUES (1, ?, ?, ?, ?, ?, 'active', ?, ?)"
        );
        foreach ($userDefs as [$name, $email, $empId, $dept, $base, $webA, $mobileA]) {
            $userStmt->execute([$name, $email, $empId, $deptMap[$dept] ?? null, $baseMap[$base] ?? null, $pw, $mobileA]);
        }
        echo "\n  ⚠ web_access column missing in MySQL — run migration 005 first!\n  ";
    }
    echo "✓\n";

    // ─── 9. Assign roles ─────────────────────────────────
    echo "Assigning roles... ";
    $tenantRoles = $db->query("SELECT slug, id FROM roles WHERE tenant_id = 1")->fetchAll(PDO::FETCH_KEY_PAIR);
    $userIds     = $db->query("SELECT email, id FROM users WHERE tenant_id = 1 AND email LIKE 'demo.%@acentoza.com'")->fetchAll(PDO::FETCH_KEY_PAIR);

    $roleMap = [
        'demo.superadmin@acentoza.com'  => ['super_admin', 'airline_admin'],
        'demo.support@acentoza.com'     => ['platform_support'],
        'demo.security@acentoza.com'    => ['platform_security'],
        'demo.airadmin@acentoza.com'    => ['airline_admin'],
        'demo.hr@acentoza.com'          => ['hr'],
        'demo.scheduler@acentoza.com'   => ['scheduler'],
        'demo.chiefpilot@acentoza.com'  => ['chief_pilot'],
        'demo.headcabin@acentoza.com'   => ['head_cabin_crew'],
        'demo.engmanager@acentoza.com'  => ['engineering_manager'],
        'demo.safety@acentoza.com'      => ['safety_officer'],
        'demo.fdm@acentoza.com'         => ['fdm_analyst'],
        'demo.doccontrol@acentoza.com'  => ['document_control'],
        'demo.basemanager@acentoza.com' => ['base_manager'],
        'demo.pilot@acentoza.com'       => ['pilot'],
        'demo.cabin@acentoza.com'       => ['cabin_crew'],
        'demo.engineer@acentoza.com'    => ['engineer'],
        'demo.training@acentoza.com'    => ['training_admin'],
        'demo.sysmonitor@acentoza.com'  => ['system_monitoring'],
    ];

    // Clear old role assignments for demo users first
    if (!empty($userIds)) {
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $db->prepare("DELETE FROM user_roles WHERE user_id IN ($placeholders)")
           ->execute(array_values($userIds));
    }

    $assignStmt = $db->prepare("$insertIgnore INTO user_roles (user_id, role_id, tenant_id) VALUES (?, ?, 1)");
    foreach ($roleMap as $email => $slugs) {
        if (!isset($userIds[$email])) continue;
        foreach ($slugs as $slug) {
            if (!isset($tenantRoles[$slug])) continue;
            $assignStmt->execute([$userIds[$email], $tenantRoles[$slug]]);
        }
    }
    echo "✓\n";

    // ─── 10. File categories ─────────────────────────────
    echo "Seeding file categories... ";
    $catDefs = [
        ['Manuals',             'manuals'],
        ['Safety Bulletins',    'safety_bulletins'],
        ['Training',            'training'],
        ['Memos',               'memos'],
        ['Engineering Orders',  'engineering_orders'],
        ['FDM Reports',         'fdm_reports'],
        ['Notices',             'notices'],
        ['Licenses',            'licenses'],
        ['General Documents',   'general'],
    ];
    $catStmt = $db->prepare("$insertIgnore INTO file_categories (tenant_id, name, slug) VALUES (1, ?, ?)");
    foreach ($catDefs as [$name, $slug]) {
        $catStmt->execute([$name, $slug]);
    }
    echo "✓\n";

    // ─── 11. Demo notices ─────────────────────────────────
    echo "Seeding demo notices... ";
    $db->exec("DELETE FROM notices WHERE tenant_id = 1");

    // Get created_by user IDs
    $uids = $db->query("SELECT email, id FROM users WHERE tenant_id = 1 AND email LIKE 'demo.%@acentoza.com'")->fetchAll(PDO::FETCH_KEY_PAIR);
    $docId    = $uids['demo.doccontrol@acentoza.com']  ?? null;
    $safetyId = $uids['demo.safety@acentoza.com']      ?? null;
    $adminId  = $uids['demo.superadmin@acentoza.com']  ?? null;
    $pilotId  = $uids['demo.pilot@acentoza.com']       ?? null;
    $cabinId  = $uids['demo.cabin@acentoza.com']       ?? null;

    $now       = date('Y-m-d H:i:s');
    $yesterday = date('Y-m-d H:i:s', strtotime('-1 day'));
    $lastWeek  = date('Y-m-d H:i:s', strtotime('-7 days'));
    $plus20    = date('Y-m-d H:i:s', strtotime('+20 days'));
    $plus30    = date('Y-m-d H:i:s', strtotime('+30 days'));
    $plus45    = date('Y-m-d H:i:s', strtotime('+45 days'));

    // Check whether requires_ack column now exists on notices
    $noticeHasAck = false;
    try {
        if ($driver === 'sqlite') {
            $pragmaRows = $db->query("PRAGMA table_info(notices)")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($pragmaRows as $row) {
                if ($row['name'] === 'requires_ack') { $noticeHasAck = true; break; }
            }
        } else {
            $c = $db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='notices' AND COLUMN_NAME='requires_ack'")->fetchColumn();
            $noticeHasAck = ($c > 0);
        }
    } catch (\Exception $e) {}

    $noticeRows = [
        ['SAFETY ALERT: Updated Emergency Evacuation Procedures',
         "All flight crew must review and acknowledge the updated emergency evacuation procedures effective immediately.\n\nKey changes:\n• Revised brace positions (Chapter 5, Section 2)\n• Updated ditching checklist (Appendix A)\n• New crew communication protocols during decompression\n\nPlease review the attached document in the Documents section and acknowledge within 72 hours.",
         'critical', 'safety', 1, $yesterday, $plus45, $safetyId, 1],

        ['Operations Update: NBO Runway 06R Closure 15–20 Apr 2026',
         "Nairobi (NBO) Runway 06R will be closed for resurfacing between 15–20 April 2026.\n\nAll arrivals and departures will use Runway 06L. Expect increased taxi times of 8–12 minutes. NOTAM published. All crew operating NBO flights must review before sign-on.\n\nContact Crew Control for any scheduling questions.",
         'urgent', 'operations', 1, $now, $plus20, $adminId, 0],

        ['Operations Manual Revision 12 — Acknowledgement Required',
         "Operations Manual Revision 12 is now in effect. All licensed crew must acknowledge receipt by 30 April 2026.\n\nKey changes:\n• Chapter 4 — Navigation Procedures (updated RVSM requirements)\n• Chapter 8 — Weather Minimums (new CAT III criteria)\n• Appendix C — Fuel Policy (revised tankering rules)\n\nThe updated manual is available in the Documents section of your CrewAssist app.",
         'urgent', 'documentation', 1, $lastWeek, $plus45, $docId, 1],

        ['Crew Scheduling: Annual Leave Freeze Q3 2026',
         "Crew Control advises that annual leave requests for 1 July – 31 August 2026 will not be approved unless already confirmed in writing.\n\nThis applies to all flight crew, cabin crew, and engineering personnel. Requests already approved remain valid.\n\nContact Crew Control for exceptional circumstances.",
         'normal', 'hr', 1, $lastWeek, null, $adminId, 0],

        ['SMS Safety Report — March 2026 Summary',
         "Monthly Safety Management System report for March 2026:\n\n• 3 hazard reports received — all resolved\n• 0 incidents this month\n• 1 mandatory occurrence report filed (MEL deviation on 5B-ODA, resolved)\n• Crew safety culture score: +4% improvement vs. February\n\nFull report available from the Safety department. Well done to all crews for continued safety awareness.",
         'normal', 'safety', 1, date('Y-m-d H:i:s', strtotime('-3 days')), null, $safetyId, 0],

        ['CrewAssist iPad App v2.1 Now Available',
         "CrewAssist version 2.1 is available for download via the Install section of the web portal.\n\nThis release includes:\n• Improved notice acknowledgement flow with offline queuing\n• Document sync performance improvements (40% faster)\n• Bug fix: roster view no longer flickers on iPad mini\n• New: push notification support for critical notices\n\nAll crew iPads must be updated within 30 days.",
         'normal', 'system', 1, date('Y-m-d H:i:s', strtotime('-2 days')), $plus30, $adminId, 0],
    ];

    if ($noticeHasAck) {
        $nStmt = $db->prepare(
            "INSERT INTO notices (tenant_id, title, body, priority, category, published, published_at, expires_at, created_by, requires_ack)
             VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        foreach ($noticeRows as $n) {
            $nStmt->execute($n);
        }
    } else {
        $nStmt = $db->prepare(
            "INSERT INTO notices (tenant_id, title, body, priority, category, published, published_at, expires_at, created_by)
             VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        foreach ($noticeRows as $n) {
            $nStmt->execute(array_slice($n, 0, 8));   // drop requires_ack
        }
    }
    echo "✓\n";

    // ─── 12. Demo files ───────────────────────────────────
    echo "Seeding demo files... ";
    $db->exec("DELETE FROM files WHERE tenant_id = 1");

    $catMap = $db->query("SELECT slug, id FROM file_categories WHERE tenant_id = 1")->fetchAll(PDO::FETCH_KEY_PAIR);

    $fileDefs = [
        [$catMap['manuals']            ?? null, 'Operations Manual Rev 12',
         'Current operations manual for all licensed crew. Acknowledgement required.',
         'OM-Rev12-Apr2026.pdf', 'demo/OM-Rev12-Apr2026.pdf', 4200000, 'application/pdf',
         '12.0', 'published', '2026-04-01', 1, $docId],

        [$catMap['safety_bulletins']   ?? null, 'Safety Bulletin: Lithium Battery Transport Restrictions',
         'Updated crew briefing on lithium battery transport — IATA DGR Amendment 14.',
         'SB-LithiumBattery-2026.pdf', 'demo/SB-LithiumBattery-2026.pdf', 890000, 'application/pdf',
         '2.1', 'published', '2026-03-15', 1, $safetyId],

        [$catMap['training']           ?? null, 'CRM Training Syllabus 2026',
         'Crew Resource Management annual training syllabus and completion requirements.',
         'CRM-Syllabus-2026.pdf', 'demo/CRM-Syllabus-2026.pdf', 1200000, 'application/pdf',
         '3.0', 'published', '2026-01-01', 0, $docId],

        [$catMap['engineering_orders'] ?? null, 'Engineering Order EO-2026-014: Landing Gear Inspection',
         'Mandatory EO for 1000-cycle landing gear inspection on B737 fleet.',
         'EO-2026-014-LG-Inspection.pdf', 'demo/EO-2026-014-LG-Inspection.pdf', 650000, 'application/pdf',
         '1.0', 'published', '2026-04-05', 1, $adminId],

        [$catMap['general']            ?? null, 'Staff Benefits Guide 2026',
         'Annual staff benefits and entitlements guide for all employees.',
         'Benefits-Guide-2026.pdf', 'demo/Benefits-Guide-2026.pdf', 2100000, 'application/pdf',
         '1.0', 'published', '2026-01-01', 0, $adminId],
    ];

    $fStmt = $db->prepare(
        "INSERT INTO files (tenant_id, category_id, title, description, file_name, file_path, file_size, mime_type, version, status, effective_date, requires_ack, uploaded_by)
         VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    foreach ($fileDefs as $f) {
        $fStmt->execute($f);
    }
    echo "✓\n";

    // ─── 13. Demo devices ─────────────────────────────────
    echo "Seeding demo devices... ";
    $db->exec("DELETE FROM devices WHERE tenant_id = 1");

    $airAdminId = $uids['demo.airadmin@acentoza.com'] ?? null;

    $devStmt = $db->prepare(
        "INSERT INTO devices (tenant_id, user_id, device_uuid, platform, model, os_version, app_version, approval_status, approved_by, approved_at)
         VALUES (1, ?, ?, 'ios', ?, '17.4.1', '2.1.0', ?, ?, ?)"
    );
    $devStmt->execute([$pilotId, 'DEMO-PILOT-001', 'iPad Pro 12.9"',  'approved', $airAdminId, $now]);
    $devStmt->execute([$cabinId, 'DEMO-CABIN-001', 'iPad Air 5th Gen','approved', $airAdminId, $now]);

    $devPendStmt = $db->prepare(
        "INSERT INTO devices (tenant_id, user_id, device_uuid, platform, model, os_version, app_version, approval_status)
         VALUES (1, ?, ?, 'ios', ?, '17.2', '2.0.9', 'pending')"
    );
    $devPendStmt->execute([$uids['demo.engineer@acentoza.com'] ?? null, 'DEMO-ENG-001', 'iPad 10th Gen']);
    $devPendStmt->execute([$pilotId, 'DEMO-PILOT-002', 'iPad mini 6th Gen']);
    echo "✓\n";

    // ─── 14. Demo notice reads ────────────────────────────
    try {
        $tableCheck = ($driver === 'sqlite')
            ? $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='notice_reads'")->fetchColumn()
            : $db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='notice_reads'")->fetchColumn();

        if ($tableCheck) {
            echo "Seeding notice reads... ";
            $db->exec("DELETE FROM notice_reads WHERE tenant_id = 1");
            $noticeIds = $db->query("SELECT id FROM notices WHERE tenant_id = 1 ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);

            $rStmt = $db->prepare("$insertIgnore INTO notice_reads (notice_id, user_id, tenant_id, read_at) VALUES (?, ?, 1, ?)");
            // Pilot has read notices 2, 4, 5 (ops update, leave freeze, SMS report)
            foreach (array_intersect_key($noticeIds, [1 => 1, 3 => 1, 4 => 1]) as $nid) {
                if ($pilotId) $rStmt->execute([$nid, $pilotId, $now]);
            }
            // Cabin crew has read notices 4, 5
            foreach (array_intersect_key($noticeIds, [3 => 1, 4 => 1]) as $nid) {
                if ($cabinId) $rStmt->execute([$nid, $cabinId, $now]);
            }
            echo "✓\n";
        }
    } catch (\Exception $e) {
        echo "(skipped — notice_reads table not yet created)\n";
    }

    // ─── Summary ──────────────────────────────────────────
    echo "\n" . str_repeat('─', 55) . "\n";
    echo "✅  Demo environment ready!\n\n";
    echo "DEMO PASSWORD (all accounts):  DemoOps2026!\n\n";
    printf("  %-34s  %s\n", 'ROLE', 'EMAIL');
    echo str_repeat('─', 55) . "\n";

    $summary = [
        ['Platform Super Admin',        'demo.superadmin@acentoza.com'],
        ['Platform Support Admin',      'demo.support@acentoza.com'],
        ['Platform Security Admin',     'demo.security@acentoza.com'],
        ['Airline Super Admin',         'demo.airadmin@acentoza.com'],
        ['HR Admin',                    'demo.hr@acentoza.com'],
        ['Scheduler Admin',             'demo.scheduler@acentoza.com'],
        ['Chief Pilot',                 'demo.chiefpilot@acentoza.com'],
        ['Head of Cabin Crew',          'demo.headcabin@acentoza.com'],
        ['Engineering Manager',         'demo.engmanager@acentoza.com'],
        ['Safety Manager',              'demo.safety@acentoza.com'],
        ['FDM Analyst',                 'demo.fdm@acentoza.com'],
        ['Document Control Manager',    'demo.doccontrol@acentoza.com'],
        ['Base Manager',                'demo.basemanager@acentoza.com'],
        ['Pilot',                       'demo.pilot@acentoza.com'],
        ['Cabin Crew',                  'demo.cabin@acentoza.com'],
        ['Engineer',                    'demo.engineer@acentoza.com'],
        ['Training Admin',              'demo.training@acentoza.com'],
        ['System Monitoring Admin',     'demo.sysmonitor@acentoza.com'],
    ];
    foreach ($summary as [$role, $email]) {
        printf("  %-34s  %s\n", $role, $email);
    }
    echo str_repeat('─', 55) . "\n\n";

} catch (PDOException $e) {
    echo "\n❌  Database error: " . $e->getMessage() . "\n";
    exit(1);
}
