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

    // ─── 0. Patch SQLite schema (migrations 004+005+010+012) ──
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

        // Migration 010+012 — SQLite cannot ALTER a NOT NULL column to nullable.
        // Detect whether users.tenant_id is still NOT NULL by inspecting PRAGMA
        // and recreate the table if needed (SQLite table-swap pattern).
        $pragmaUsers = $db->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
        $tenantCol   = array_filter($pragmaUsers, fn($c) => $c['name'] === 'tenant_id');
        $tenantCol   = reset($tenantCol);
        if ($tenantCol && (int)$tenantCol['notnull'] === 1) {
            echo "  Rebuilding users table (nullable tenant_id)...\n";
            $db->exec("PRAGMA foreign_keys = OFF");
            $db->exec("
                CREATE TABLE users_new (
                    id            INTEGER PRIMARY KEY AUTOINCREMENT,
                    tenant_id     INTEGER DEFAULT NULL,
                    name          TEXT    NOT NULL,
                    email         TEXT    NOT NULL,
                    password_hash TEXT    NOT NULL,
                    employee_id   TEXT,
                    department_id INTEGER,
                    base_id       INTEGER,
                    status        TEXT    NOT NULL DEFAULT 'pending',
                    mobile_access INTEGER NOT NULL DEFAULT 1,
                    avatar_path   TEXT,
                    last_login_at TEXT,
                    created_at    TEXT    NOT NULL DEFAULT (datetime('now')),
                    updated_at    TEXT    NOT NULL DEFAULT (datetime('now')),
                    web_access    INTEGER NOT NULL DEFAULT 1,
                    FOREIGN KEY (tenant_id)     REFERENCES tenants(id)     ON DELETE SET NULL,
                    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
                    FOREIGN KEY (base_id)       REFERENCES bases(id)       ON DELETE SET NULL
                )
            ");
            $db->exec("INSERT INTO users_new SELECT
                id, tenant_id, name, email, password_hash, employee_id,
                department_id, base_id, status, mobile_access, avatar_path,
                last_login_at, created_at, updated_at,
                COALESCE(web_access, 1)
              FROM users");
            $db->exec("DROP TABLE users");
            $db->exec("ALTER TABLE users_new RENAME TO users");
            $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS uq_users_email_tenant ON users(email, tenant_id)");
            $db->exec("PRAGMA foreign_keys = ON");
            echo "  ✓ users table rebuilt\n";
        }

        // Migration 012 — make user_roles.tenant_id nullable in SQLite
        $pragmaUR  = $db->query("PRAGMA table_info(user_roles)")->fetchAll(PDO::FETCH_ASSOC);
        $urTenantCol = array_filter($pragmaUR, fn($c) => $c['name'] === 'tenant_id');
        $urTenantCol = reset($urTenantCol);
        if ($urTenantCol && (int)$urTenantCol['notnull'] === 1) {
            echo "  Rebuilding user_roles table (nullable tenant_id)...\n";
            $db->exec("PRAGMA foreign_keys = OFF");
            $db->exec("
                CREATE TABLE user_roles_new (
                    id          INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id     INTEGER NOT NULL,
                    role_id     INTEGER NOT NULL,
                    tenant_id   INTEGER DEFAULT NULL,
                    assigned_at TEXT    NOT NULL DEFAULT (datetime('now')),
                    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
                    FOREIGN KEY (role_id)   REFERENCES roles(id)   ON DELETE CASCADE,
                    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL,
                    UNIQUE (user_id, role_id)
                )
            ");
            $db->exec("INSERT OR IGNORE INTO user_roles_new SELECT id, user_id, role_id, tenant_id, assigned_at FROM user_roles");
            $db->exec("DROP TABLE user_roles");
            $db->exec("ALTER TABLE user_roles_new RENAME TO user_roles");
            $db->exec("PRAGMA foreign_keys = ON");
            echo "  ✓ user_roles table rebuilt\n";
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
    // Clear airline-tenant demo users
    $db->exec("DELETE FROM users WHERE tenant_id = 1 AND (email LIKE '%@airline.com' OR email LIKE 'demo.%@acentoza.com')");
    // Clear platform-level demo users (NULL tenant) from previous seeds
    $db->exec("DELETE FROM users WHERE tenant_id IS NULL AND email LIKE 'demo.%@acentoza.com'");
    echo "✓\n";

    // ─── 7. Reload maps ───────────────────────────────────
    $deptMap = $db->query("SELECT code, id FROM departments WHERE tenant_id = 1")->fetchAll(PDO::FETCH_KEY_PAIR);
    $baseMap = $db->query("SELECT code, id FROM bases WHERE tenant_id = 1")->fetchAll(PDO::FETCH_KEY_PAIR);
    $pw = password_hash('DemoOps2026!', PASSWORD_BCRYPT);

    // ─── 8. Demo users ────────────────────────────────────
    echo "Creating demo users... ";

    // Build INSERT — handle whether web_access column exists (MySQL may need migration 005 first)
    $hasWebAccess = true;
    if ($driver === 'mysql') {
        $cols = $db->query("SHOW COLUMNS FROM users LIKE 'web_access'")->fetchColumn();
        $hasWebAccess = !empty($cols);
    }

    // ── 8a. Platform users (tenant_id = NULL) ────────────
    // These are pure platform-staff accounts with no airline affiliation.
    // [name, email, emp_id, web_access, mobile_access]
    $platformUserDefs = [
        ['Alex Mwangi',    'demo.superadmin@acentoza.com', 'PLT-001', 1, 0],
        ['Jordan Taylor',  'demo.support@acentoza.com',    'PLT-002', 1, 0],
        ['Sarah Kimani',   'demo.security@acentoza.com',   'PLT-003', 1, 0],
        ['James Okafor',   'demo.sysmonitor@acentoza.com', 'SYS-001', 1, 0],
    ];

    if ($hasWebAccess) {
        $platStmt = $db->prepare(
            "INSERT INTO users (tenant_id, name, email, employee_id, status, password_hash, web_access, mobile_access)
             VALUES (NULL, ?, ?, ?, 'active', ?, ?, ?)"
        );
        foreach ($platformUserDefs as [$name, $email, $empId, $webA, $mobileA]) {
            $platStmt->execute([$name, $email, $empId, $pw, $webA, $mobileA]);
        }
    } else {
        $platStmt = $db->prepare(
            "INSERT INTO users (tenant_id, name, email, employee_id, status, password_hash, mobile_access)
             VALUES (NULL, ?, ?, ?, 'active', ?, ?)"
        );
        foreach ($platformUserDefs as [$name, $email, $empId, $webA, $mobileA]) {
            $platStmt->execute([$name, $email, $empId, $pw, $mobileA]);
        }
    }

    // ── 8b. Airline users (tenant_id = 1) ────────────────
    // [name, email, emp_id, dept_code, base_code, web_access, mobile_access]
    $airlineUserDefs = [
        // Airline management
        ['Amara Diallo',          'demo.airadmin@acentoza.com',    'ODA-001', 'MGT', 'NBO', 1, 1],
        ['Fatima Al-Zaabi',       'demo.hr@acentoza.com',          'HR-001',  'HR',  'NBO', 1, 1],
        ['Layla Hassan',          'demo.scheduler@acentoza.com',   'CTC-001', 'CTC', 'NBO', 1, 1],
        ['Capt. Ahmed Mansoori',  'demo.chiefpilot@acentoza.com',  'FLT-CP1', 'FLT', 'NBO', 1, 1],
        ['Grace Okonkwo',         'demo.headcabin@acentoza.com',   'CAB-HM1', 'CAB', 'NBO', 1, 1],
        ['Mark Sullivan',         'demo.engmanager@acentoza.com',  'ENG-MGR', 'ENG', 'NBO', 1, 1],
        ['Dr. Nadia Okelo',       'demo.safety@acentoza.com',      'SAF-001', 'SAF', 'NBO', 1, 1],
        ['Dr. Priya Sharma',      'demo.fdm@acentoza.com',         'FDM-001', 'FDM', 'NBO', 1, 1],
        ['Sara Khalid',           'demo.doccontrol@acentoza.com',  'DOC-001', 'DOC', 'NBO', 1, 1],
        ['Joseph Kariuki',        'demo.basemanager@acentoza.com', 'BMS-NBO', 'BMS', 'NBO', 1, 1],
        // Operational (web + mobile)
        ['Capt. Rashid Hussein',  'demo.pilot@acentoza.com',       'FLT-001', 'FLT', 'NBO', 1, 1],
        ['Noor Al-Rashidi',       'demo.cabin@acentoza.com',       'CAB-020', 'CAB', 'EBB', 1, 1],
        ['Eric Mbeki',            'demo.engineer@acentoza.com',    'ENG-007', 'ENG', 'NBO', 1, 1],
        // Optional roles
        ['Ruth Nambozo',          'demo.training@acentoza.com',    'TRN-001', 'HR',  'NBO', 1, 0],
    ];

    if ($hasWebAccess) {
        $airStmt = $db->prepare(
            "INSERT INTO users (tenant_id, name, email, employee_id, department_id, base_id, status, password_hash, web_access, mobile_access)
             VALUES (1, ?, ?, ?, ?, ?, 'active', ?, ?, ?)"
        );
        foreach ($airlineUserDefs as [$name, $email, $empId, $dept, $base, $webA, $mobileA]) {
            $airStmt->execute([$name, $email, $empId, $deptMap[$dept] ?? null, $baseMap[$base] ?? null, $pw, $webA, $mobileA]);
        }
    } else {
        $airStmt = $db->prepare(
            "INSERT INTO users (tenant_id, name, email, employee_id, department_id, base_id, status, password_hash, mobile_access)
             VALUES (1, ?, ?, ?, ?, ?, 'active', ?, ?)"
        );
        foreach ($airlineUserDefs as [$name, $email, $empId, $dept, $base, $webA, $mobileA]) {
            $airStmt->execute([$name, $email, $empId, $deptMap[$dept] ?? null, $baseMap[$base] ?? null, $pw, $mobileA]);
        }
        echo "\n  ⚠ web_access column missing in MySQL — run migration 005 first!\n  ";
    }
    echo "✓\n";

    // ─── 9. Assign roles ─────────────────────────────────
    echo "Assigning roles... ";

    // System roles (NULL tenant) for platform users
    $systemRoleIds = $db->query("SELECT slug, id FROM roles WHERE tenant_id IS NULL")->fetchAll(PDO::FETCH_KEY_PAIR);
    // Tenant-1 roles for airline users
    $tenantRoles   = $db->query("SELECT slug, id FROM roles WHERE tenant_id = 1")->fetchAll(PDO::FETCH_KEY_PAIR);

    // Platform user IDs (NULL tenant_id)
    $platUserIds = $db->query("SELECT email, id FROM users WHERE tenant_id IS NULL AND email LIKE 'demo.%@acentoza.com'")->fetchAll(PDO::FETCH_KEY_PAIR);
    // Airline user IDs
    $airUserIds  = $db->query("SELECT email, id FROM users WHERE tenant_id = 1 AND email LIKE 'demo.%@acentoza.com'")->fetchAll(PDO::FETCH_KEY_PAIR);
    $userIds     = $platUserIds + $airUserIds;

    // Clear old role assignments for ALL demo users
    if (!empty($userIds)) {
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $db->prepare("DELETE FROM user_roles WHERE user_id IN ($placeholders)")
           ->execute(array_values($userIds));
    }

    // Platform role map — assigned using system roles (tenant_id = NULL in user_roles)
    $platformRoleMap = [
        'demo.superadmin@acentoza.com'  => ['super_admin'],       // pure platform — NO airline_admin
        'demo.support@acentoza.com'     => ['platform_support'],
        'demo.security@acentoza.com'    => ['platform_security'],
        'demo.sysmonitor@acentoza.com'  => ['system_monitoring'],
    ];

    $platAssignStmt = $db->prepare("$insertIgnore INTO user_roles (user_id, role_id, tenant_id) VALUES (?, ?, NULL)");
    foreach ($platformRoleMap as $email => $slugs) {
        if (!isset($platUserIds[$email])) continue;
        foreach ($slugs as $slug) {
            if (!isset($systemRoleIds[$slug])) continue;
            $platAssignStmt->execute([$platUserIds[$email], $systemRoleIds[$slug]]);
        }
    }

    // Airline role map — assigned using tenant-1 roles
    $roleMap = [
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
    ];

    $airAssignStmt = $db->prepare("$insertIgnore INTO user_roles (user_id, role_id, tenant_id) VALUES (?, ?, 1)");
    foreach ($roleMap as $email => $slugs) {
        if (!isset($airUserIds[$email])) continue;
        foreach ($slugs as $slug) {
            if (!isset($tenantRoles[$slug])) continue;
            $airAssignStmt->execute([$airUserIds[$email], $tenantRoles[$slug]]);
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

    // Get created_by user IDs (airline users from tenant 1; platform users from NULL tenant)
    $uids     = $db->query("SELECT email, id FROM users WHERE tenant_id = 1 AND email LIKE 'demo.%@acentoza.com'")->fetchAll(PDO::FETCH_KEY_PAIR);
    $platUids = $db->query("SELECT email, id FROM users WHERE tenant_id IS NULL AND email LIKE 'demo.%@acentoza.com'")->fetchAll(PDO::FETCH_KEY_PAIR);
    $docId    = $uids['demo.doccontrol@acentoza.com']      ?? null;
    $safetyId = $uids['demo.safety@acentoza.com']          ?? null;
    $adminId  = $platUids['demo.superadmin@acentoza.com']  ?? null; // platform user
    $pilotId  = $uids['demo.pilot@acentoza.com']           ?? null;
    $cabinId  = $uids['demo.cabin@acentoza.com']           ?? null;

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

    // ─── 15. Crew profiles & licenses ────────────────────
    try {
        $cpCheck = ($driver === 'sqlite')
            ? $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='crew_profiles'")->fetchColumn()
            : $db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='crew_profiles'")->fetchColumn();

        if ($cpCheck) {
            echo "Seeding crew profiles... ";
            $db->exec("DELETE FROM crew_profiles WHERE tenant_id = 1");
            $db->exec("DELETE FROM licenses WHERE tenant_id = 1");

            $cpStmt = $db->prepare(
                "INSERT INTO crew_profiles (user_id, tenant_id, date_of_birth, nationality, phone, emergency_name, emergency_phone, emergency_relation, passport_number, passport_country, passport_expiry, medical_class, medical_expiry, contract_type, contract_expiry)
                 VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $chiefPilotId = $uids['demo.chiefpilot@acentoza.com'] ?? null;
            $engId        = $uids['demo.engineer@acentoza.com']  ?? null;

            // Chief Pilot — medical expiring soon (60 days)
            if ($chiefPilotId) {
                $cpStmt->execute([
                    $chiefPilotId,
                    '1975-03-12', 'Kenyan',       '+254 722 100 200',
                    'Jane Kamau', '+254 722 100 201', 'Spouse',
                    'KE123456', 'Kenya', date('Y-m-d', strtotime('+18 months')),
                    'Class 1', date('Y-m-d', strtotime('+62 days')),
                    'permanent', null,
                ]);
            }

            // Pilot — licence expiring in 45 days, medical expiring in 80 days
            if ($pilotId) {
                $cpStmt->execute([
                    $pilotId,
                    '1988-07-24', 'Ugandan',       '+256 701 234 567',
                    'David Ochieng', '+256 701 234 568', 'Brother',
                    'UG987654', 'Uganda', date('Y-m-d', strtotime('+14 months')),
                    'Class 1', date('Y-m-d', strtotime('+78 days')),
                    'permanent', null,
                ]);
            }

            // Cabin crew — passport expiring in 5 months
            if ($cabinId) {
                $cpStmt->execute([
                    $cabinId,
                    '1994-11-05', 'Kenyan',       '+254 733 400 500',
                    'Mary Wanjiku', '+254 733 400 501', 'Mother',
                    'KE654321', 'Kenya', date('Y-m-d', strtotime('+5 months')),
                    'Cabin Crew Medical', date('Y-m-d', strtotime('+11 months')),
                    'fixed_term', date('Y-m-d', strtotime('+8 months')),
                ]);
            }

            // Engineer — contract expiring in 3 months
            if ($engId) {
                $cpStmt->execute([
                    $engId,
                    '1985-02-18', 'Congolese',    '+243 81 500 6000',
                    'Pierre Kabila', '+243 81 500 6001', 'Father',
                    'CD112233', 'DR Congo', date('Y-m-d', strtotime('+20 months')),
                    'Class 3', date('Y-m-d', strtotime('+9 months')),
                    'fixed_term', date('Y-m-d', strtotime('+3 months')),
                ]);
            }
            echo "✓\n";

            echo "Seeding demo licenses... ";
            $licStmt = $db->prepare(
                "INSERT INTO licenses (user_id, tenant_id, license_type, license_number, issuing_authority, issue_date, expiry_date, notes)
                 VALUES (?, 1, ?, ?, ?, ?, ?, ?)"
            );

            if ($chiefPilotId) {
                $licStmt->execute([$chiefPilotId, 'ATPL(A)', 'KEN-ATPL-0042', 'KCAA', '2018-06-01', date('Y-m-d', strtotime('+2 years')), null]);
                $licStmt->execute([$chiefPilotId, 'Type Rating B737-800', 'KEN-TR-0042-B738', 'KCAA', '2022-11-15', date('Y-m-d', strtotime('+1 year 4 months')), 'Initial type rating NBO sim centre']);
                $licStmt->execute([$chiefPilotId, 'ME/IR', 'KEN-MEIR-0042', 'KCAA', '2018-06-01', date('Y-m-d', strtotime('+8 months')), null]);
            }

            if ($pilotId) {
                // Pilot ATPL expiring in 20 days — triggers ⚠ warning badge (within 30-day threshold)
                $licStmt->execute([$pilotId, 'ATPL(A)', 'UGA-ATPL-1187', 'UCAA', '2020-03-10', date('Y-m-d', strtotime('+20 days')), 'Renewal due — exam booked']);
                $licStmt->execute([$pilotId, 'Type Rating B737-800', 'UGA-TR-1187-B738', 'UCAA', '2023-01-20', date('Y-m-d', strtotime('+1 year 7 months')), null]);
                $licStmt->execute([$pilotId, 'ME/IR', 'UGA-MEIR-1187', 'UCAA', '2020-03-10', date('Y-m-d', strtotime('+20 days')), 'Renewal with ATPL']);
            }

            if ($cabinId) {
                // Cabin crew attestation EXPIRED 3 months ago — triggers ✕ CRITICAL badge + replacement suggestion
                $licStmt->execute([$cabinId, 'Cabin Crew Attestation', 'KEN-CCA-4421', 'KCAA', '2022-06-01', date('Y-m-d', strtotime('-3 months')), 'EXPIRED — renewal overdue']);
                $licStmt->execute([$cabinId, 'Aviation Security (AVSEC)', 'KEN-AVSEC-4421', 'KCAA', '2024-01-10', date('Y-m-d', strtotime('+1 year 9 months')), null]);
            }

            if ($engId) {
                $licStmt->execute([$engId, 'EASA Part-66 Cat B1', 'DRC-B1-0088', 'ANAC DRC', '2017-09-01', date('Y-m-d', strtotime('+3 years')), 'Aircraft maintenance engineer']);
                $licStmt->execute([$engId, 'B737-800 Type Endorsement', 'DRC-B1-0088-B738', 'ANAC DRC', '2021-04-01', date('Y-m-d', strtotime('+2 years 6 months')), null]);
            }
            echo "✓\n";
        } else {
            echo "Crew profiles table not found — run migration 006 first.\n";
        }
    } catch (\Exception $e) {
        echo "(skipped crew profiles — " . $e->getMessage() . ")\n";
    }

    // ─── 16. Roster data ──────────────────────────────────
    echo "Step 16: Seeding demo roster... ";
    $hasRosters = $driver === 'sqlite'
        ? $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='rosters'")->fetchColumn()
        : $db->query("SHOW TABLES LIKE 'rosters'")->fetchColumn();

    if ($hasRosters) {
        try {
            // Clear existing roster data for tenant 1
            $db->exec("DELETE FROM rosters WHERE tenant_id = 1");

            $rStmt = $db->prepare(
                "INSERT OR IGNORE INTO rosters (tenant_id, user_id, roster_date, duty_type, duty_code, notes)
                 VALUES (1, ?, ?, ?, ?, ?)"
            );
            if ($driver !== 'sqlite') {
                $rStmt = $db->prepare(
                    "INSERT IGNORE INTO rosters (tenant_id, user_id, roster_date, duty_type, duty_code, notes)
                     VALUES (1, ?, ?, ?, ?, ?)"
                );
            }

            // Seed ~30 days around today (current month)
            $today    = new DateTime();
            $year     = (int) $today->format('Y');
            $month    = (int) $today->format('n');
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

            $pilotPattern   = ['flight','flight','flight','off','off','standby','flight','flight','rest','off','flight','sim','off','off','flight','flight','standby','off','leave','flight','flight','off','flight','rest','off','flight','flight','off','standby','flight','flight'];
            $cabinPattern   = ['flight','flight','flight','off','off','flight','flight','standby','off','off','flight','flight','rest','off','flight','flight','off','standby','leave','flight','flight','off','off','flight','rest','off','flight','flight','off','standby','flight'];
            $chiefPattern   = ['flight','off','off','flight','flight','training','off','flight','flight','off','off','sim','off','flight','flight','off','off','flight','flight','off','off','flight','training','off','flight','flight','off','off','flight','off','off'];
            $engPattern     = ['off','off','training','off','off','off','training','off','off','off','off','sim','off','off','training','off','off','off','off','off','training','off','off','off','off','off','training','off','off','off','off'];
            $headCabinPat   = ['off','flight','flight','off','off','flight','training','off','off','flight','flight','off','off','flight','flight','off','off','standby','off','flight','flight','off','off','flight','training','off','off','flight','flight','off','off'];

            $dutyNotes = [
                'flight'   => ['Route NBO-DXB','Route NBO-ADD','Route NBO-LHR','Route NBO-DAR','Route NBO-LOS','', ''],
                'sim'      => ['B737-800 OPC','B737-800 LPC','Emergency procedures','LOFT session'],
                'training' => ['CRM training','CFIT awareness','SMS induction','Leadership workshop','First aid refresher'],
                'standby'  => ['Airport standby','Home standby','', ''],
                'leave'    => ['Annual leave','Sick leave',''],
                'off'      => ['','',''],
                'rest'     => ['',''],
            ];

            $userPatterns = [];
            if ($pilotId)       $userPatterns[$pilotId]      = $pilotPattern;
            if ($cabinId)       $userPatterns[$cabinId]      = $cabinPattern;
            if ($chiefPilotId)  $userPatterns[$chiefPilotId] = $chiefPattern;
            if ($engId)         $userPatterns[$engId]        = $engPattern;
            $headCabinId = $uids['demo.headcabin@acentoza.com'] ?? null;
            if ($headCabinId) $userPatterns[$headCabinId] = $headCabinPat;

            foreach ($userPatterns as $uid => $pattern) {
                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $idx      = ($d - 1) % count($pattern);
                    $dutyType = $pattern[$idx];
                    $dateStr  = sprintf('%04d-%02d-%02d', $year, $month, $d);

                    $notesPool = $dutyNotes[$dutyType] ?? [''];
                    $note = $notesPool[array_rand($notesPool)];

                    $rStmt->execute([$uid, $dateStr, $dutyType, null, $note ?: null]);
                }
            }
            echo "✓\n";
        } catch (\Exception $e) {
            echo "(partial — " . $e->getMessage() . ")\n";
        }
    } else {
        echo "Rosters table not found — run migration 007 first.\n";
    }

    // ─── 17. FDM demo data ────────────────────────────────
    echo "Step 17: Seeding FDM demo data... ";
    $hasFdm = $driver === 'sqlite'
        ? $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='fdm_uploads'")->fetchColumn()
        : $db->query("SHOW TABLES LIKE 'fdm_uploads'")->fetchColumn();

    if ($hasFdm) {
        try {
            $db->exec("DELETE FROM fdm_events  WHERE tenant_id = 1");
            $db->exec("DELETE FROM fdm_uploads WHERE tenant_id = 1");

            $fdmUserId = $uids['demo.fdm@acentoza.com'] ?? ($uids['demo.airadmin@acentoza.com'] ?? 1);

            // Create 3 sample uploads
            $uploadSql = "INSERT INTO fdm_uploads (tenant_id, uploaded_by, filename, original_name, flight_date, aircraft_reg, flight_number, event_count, status, notes)
                          VALUES (1, ?, ?, ?, ?, ?, ?, ?, 'processed', ?)";
            $upStmt = $db->prepare($uploadSql);

            $upStmt->execute([$fdmUserId, 'fdm_demo_1.csv', 'KQ101_2026-04-05.csv', '2026-04-05', '5Y-KQX', 'KQ101', 0, 'Monthly FOQA download — Nairobi to Dubai']);
            $up1 = $db->lastInsertId();

            $upStmt->execute([$fdmUserId, 'fdm_demo_2.csv', 'KQ202_2026-04-07.csv', '2026-04-07', '5Y-KQY', 'KQ202', 0, 'Monthly FOQA download — Nairobi to London']);
            $up2 = $db->lastInsertId();

            $upStmt->execute([$fdmUserId, 'fdm_manual.csv', 'Manual Entry', '2026-04-08', '5Y-KQX', 'KQ105', 0, null]);
            $up3 = $db->lastInsertId();

            // Create sample events
            $evSql = "INSERT INTO fdm_events (tenant_id, fdm_upload_id, event_type, severity, flight_date, aircraft_reg, flight_number, flight_phase, parameter, value_recorded, threshold, notes)
                      VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $evStmt = $db->prepare($evSql);

            // Upload 1 events (KQ101)
            $evStmt->execute([$up1, 'hard_landing',          'medium',   '2026-04-05', '5Y-KQX', 'KQ101', 'landing',  'vertical_g',     2.3,  2.0,  'Firm touchdown — crew reported gusty conditions']);
            $evStmt->execute([$up1, 'exceedance',            'low',      '2026-04-05', '5Y-KQX', 'KQ101', 'climb',    'climb_rate',     3200, 3000, 'Brief rate exceedance during initial climb']);
            $evStmt->execute([$up1, 'unstabilised_approach', 'medium',   '2026-04-05', '5Y-KQX', 'KQ101', 'approach', 'airspeed',        148,  140,  'Speed above target inside 1000ft — crew corrected']);

            // Upload 2 events (KQ202 — more serious)
            $evStmt->execute([$up2, 'gpws',                  'high',     '2026-04-07', '5Y-KQY', 'KQ202', 'approach', 'terrain_alert',   1,    null, 'Mode 2 GPWS — terrain clearance — pulled up immediately']);
            $evStmt->execute([$up2, 'overspeed',             'high',     '2026-04-07', '5Y-KQY', 'KQ202', 'cruise',   'mmo_exceedance',  0.84, 0.82, 'Brief Mmo exceedance in cruise — turbulence encounter']);
            $evStmt->execute([$up2, 'hard_landing',          'critical', '2026-04-07', '5Y-KQY', 'KQ202', 'landing',  'vertical_g',     3.1,  2.0,  'Hard landing — maintenance inspection required']);
            $evStmt->execute([$up2, 'exceedance',            'medium',   '2026-04-07', '5Y-KQY', 'KQ202', 'approach', 'bank_angle',      32,   30,   'Bank angle exceedance on final']);

            // Upload 3 — manual entry
            $evStmt->execute([$up3, 'windshear',             'high',     '2026-04-08', '5Y-KQX', 'KQ105', 'approach', 'wind_shear',      18,   15,   'Windshear warning — go-around executed']);

            // Update event counts
            $db->prepare("UPDATE fdm_uploads SET event_count = 3 WHERE id = ?")->execute([$up1]);
            $db->prepare("UPDATE fdm_uploads SET event_count = 4 WHERE id = ?")->execute([$up2]);
            $db->prepare("UPDATE fdm_uploads SET event_count = 1 WHERE id = ?")->execute([$up3]);

            echo "✓\n";
        } catch (\Exception $e) {
            echo "(partial — " . $e->getMessage() . ")\n";
        }
    } else {
        echo "FDM tables not found — run migration 008 first.\n";
    }

    // ─── 18. Module catalog, capabilities, templates, tenant enablement ──
    echo "Step 18: Seeding module catalog... ";
    $hasModules = $driver === 'sqlite'
        ? $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='modules'")->fetchColumn()
        : $db->query("SHOW TABLES LIKE 'modules'")->fetchColumn();

    if ($hasModules) {
        try {
            // 18a. Modules
            $modules = [
                ['crew_profiles',       'Crew Profiles',           'Crew personal records, passport, and employment data',        '👤', 0, 10],
                ['licensing',           'Licensing',               'Pilot and crew licence and rating management',                 '📋', 1, 20],
                ['rostering',           'Rostering',               'Monthly duty assignment and crew scheduling',                  '📅', 1, 30],
                ['standby_pool',        'Standby Pool',            'Standby crew tracking and replacement suggestions',            '📋', 1, 35],
                ['manuals',             'Manuals & Documents',     'Document library with version control and acknowledgements',   '📄', 1, 40],
                ['notices',             'Notices',                 'Crew notices with targeting and acknowledgement tracking',     '📢', 1, 50],
                ['safety_reports',      'Safety Reports',          'Safety occurrence reporting and review workflow',              '⚠',  0, 60],
                ['fdm',                 'Flight Data Monitoring',  'FDM upload, event tagging, and analysis',                     '📊', 0, 70],
                ['compliance',          'Compliance Dashboard',    'Licence, medical, and passport expiry tracking',               '✅', 1, 80],
                ['training',            'Training Management',     'Training course assignments and records',                     '🎓', 0, 90],
                ['mobile_ipad_access',  'Mobile / iPad Access',   'iPad app access entitlement and sync control',                 '📱', 1, 100],
                ['sync_control',        'Sync Control',            'Manual sync trigger and last-sync visibility',                 '🔄', 1, 110],
                ['document_control',    'Document Control',        'Approval workflow for new documents and revisions',            '📁', 0, 120],
                ['flight_briefing',     'Flight Briefing',         'Pre-flight briefing package distribution (planned)',          '✈',  1, 200],
                ['future_jeppesen',     'Jeppesen Integration',    'Charts and navigation data integration (planned)',             '🗺',  1, 300],
                ['future_performance',  'Performance Tools',       'Take-off / landing performance calculation (planned)',         '📐', 0, 400],
            ];
            $modStmt = $db->prepare(
                "$insertIgnore INTO modules (code, name, description, icon, mobile_capable, sort_order, platform_status)
                 VALUES (?, ?, ?, ?, ?, ?, 'available')"
            );
            foreach ($modules as $m) { $modStmt->execute($m); }

            // 18b. Module capabilities
            $specialCaps = [
                'rostering'          => ['view','edit','publish','assign','export','view_audit'],
                'standby_pool'       => ['view','assign','export'],
                'manuals'            => ['view','upload','publish','delete','acknowledge','export'],
                'notices'            => ['view','create','edit','delete','publish','acknowledge','export'],
                'safety_reports'     => ['view','create','edit','submit','review','approve','export','view_audit'],
                'fdm'                => ['view','upload','create','edit','delete','export'],
                'compliance'         => ['view','export','view_audit'],
                'training'           => ['view','create','edit','delete','assign','approve','export'],
                'mobile_ipad_access' => ['view','manage_settings','sync_now'],
                'sync_control'       => ['view','sync_now'],
                'document_control'   => ['view','upload','approve','publish','delete','request_change','export','view_audit'],
                'licensing'          => ['view','create','edit','delete','export','view_audit'],
                'crew_profiles'      => ['view','create','edit','delete','export','view_audit'],
                'flight_briefing'    => ['view','upload','publish','export'],
                'future_jeppesen'    => ['view'],
                'future_performance' => ['view'],
            ];
            $capStmt = $db->prepare(
                "$insertIgnore INTO module_capabilities (module_id, capability)
                 SELECT id, ? FROM modules WHERE code = ?"
            );
            foreach ($specialCaps as $code => $caps) {
                foreach ($caps as $cap) { $capStmt->execute([$cap, $code]); }
            }

            // 18c. Role-capability templates
            $templates = [
                'pilot'              => ['crew_profiles'=>['view'],'licensing'=>['view'],'rostering'=>['view'],'manuals'=>['view','acknowledge'],'notices'=>['view','acknowledge'],'compliance'=>['view'],'mobile_ipad_access'=>['view'],'sync_control'=>['view','sync_now']],
                'cabin_crew'         => ['crew_profiles'=>['view'],'licensing'=>['view'],'rostering'=>['view'],'manuals'=>['view','acknowledge'],'notices'=>['view','acknowledge'],'compliance'=>['view'],'mobile_ipad_access'=>['view'],'sync_control'=>['view','sync_now']],
                'engineer'           => ['crew_profiles'=>['view'],'licensing'=>['view'],'rostering'=>['view'],'manuals'=>['view','acknowledge'],'notices'=>['view','acknowledge'],'compliance'=>['view'],'mobile_ipad_access'=>['view'],'sync_control'=>['view','sync_now']],
                'scheduler'          => ['rostering'=>['view','edit','publish','assign','export'],'standby_pool'=>['view','assign','export'],'crew_profiles'=>['view'],'compliance'=>['view','export'],'notices'=>['view'],'mobile_ipad_access'=>['view']],
                'chief_pilot'        => ['crew_profiles'=>['view','edit','view_audit'],'licensing'=>['view','edit','view_audit'],'rostering'=>['view','edit','publish','assign','export','view_audit'],'standby_pool'=>['view','assign','export'],'manuals'=>['view','upload','publish','delete','export'],'notices'=>['view','create','edit','publish'],'compliance'=>['view','export','view_audit'],'fdm'=>['view','export'],'mobile_ipad_access'=>['view','manage_settings']],
                'head_cabin_crew'    => ['crew_profiles'=>['view','edit'],'licensing'=>['view','edit'],'rostering'=>['view','edit','publish','assign','export'],'standby_pool'=>['view','assign'],'manuals'=>['view','upload','publish'],'notices'=>['view','create','edit','publish'],'compliance'=>['view','export'],'mobile_ipad_access'=>['view','manage_settings']],
                'engineering_manager'=> ['crew_profiles'=>['view'],'licensing'=>['view','edit'],'rostering'=>['view','export'],'manuals'=>['view','upload','publish','export'],'notices'=>['view','create','edit','publish'],'compliance'=>['view','export']],
                'safety_officer'     => ['safety_reports'=>['view','create','edit','submit','review','approve','export','view_audit'],'fdm'=>['view','upload','create','edit','export'],'compliance'=>['view','export','view_audit'],'crew_profiles'=>['view','view_audit'],'licensing'=>['view','view_audit'],'notices'=>['view','create','edit','publish'],'manuals'=>['view','upload','publish']],
                'fdm_analyst'        => ['fdm'=>['view','upload','create','edit','delete','export'],'compliance'=>['view'],'safety_reports'=>['view','export']],
                'document_control'   => ['document_control'=>['view','upload','approve','publish','delete','request_change','export','view_audit'],'manuals'=>['view','upload','publish','delete','export'],'notices'=>['view','create','edit','publish','delete']],
                'hr'                 => ['crew_profiles'=>['view','create','edit','delete','export','view_audit'],'licensing'=>['view','create','edit','delete','export','view_audit'],'compliance'=>['view','export','view_audit'],'notices'=>['view','create','edit','publish'],'training'=>['view','create','edit','assign','export'],'mobile_ipad_access'=>['view','manage_settings']],
                'training_admin'     => ['training'=>['view','create','edit','delete','assign','approve','export'],'crew_profiles'=>['view'],'licensing'=>['view'],'notices'=>['view','create'],'manuals'=>['view']],
                'base_manager'       => ['crew_profiles'=>['view'],'rostering'=>['view','export'],'standby_pool'=>['view'],'notices'=>['view','create'],'compliance'=>['view']],
                'airline_admin'      => ['crew_profiles'=>['view','create','edit','delete','export','view_audit'],'licensing'=>['view','create','edit','delete','export','view_audit'],'rostering'=>['view','edit','publish','assign','export','view_audit'],'standby_pool'=>['view','assign','export'],'manuals'=>['view','upload','publish','delete','export'],'notices'=>['view','create','edit','delete','publish','export'],'safety_reports'=>['view','create','review','approve','export','view_audit'],'fdm'=>['view','upload','create','edit','delete','export'],'compliance'=>['view','export','view_audit'],'training'=>['view','create','edit','delete','assign','approve','export'],'mobile_ipad_access'=>['view','manage_settings','sync_now'],'sync_control'=>['view','sync_now'],'document_control'=>['view','upload','approve','publish','delete','request_change','export','view_audit']],
            ];
            $rctStmt = $db->prepare(
                "$insertIgnore INTO role_capability_templates (role_slug, module_capability_id)
                 SELECT ?, mc.id FROM module_capabilities mc
                 JOIN modules m ON m.id = mc.module_id
                 WHERE m.code = ? AND mc.capability = ?"
            );
            foreach ($templates as $roleSlug => $moduleCaps) {
                foreach ($moduleCaps as $moduleCode => $caps) {
                    foreach ($caps as $cap) { $rctStmt->execute([$roleSlug, $moduleCode, $cap]); }
                }
            }

            // 18d. Enable default modules for demo tenant (id=1)
            $defaultModules = ['crew_profiles','licensing','rostering','standby_pool','manuals','notices','fdm','compliance','mobile_ipad_access','sync_control'];
            $tmStmt = $db->prepare(
                "$insertIgnore INTO tenant_modules (tenant_id, module_id, is_enabled)
                 SELECT 1, id, 1 FROM modules WHERE code = ?"
            );
            foreach ($defaultModules as $code) { $tmStmt->execute([$code]); }

            // 18e. Demo tenant settings & access policy (safe to run multiple times)
            $db->exec("$insertIgnore INTO tenant_settings (tenant_id) VALUES (1)");
            $db->exec("$insertIgnore INTO tenant_access_policies (tenant_id) VALUES (1)");

            // 18f. Demo tenant metadata (icao, iata, country, support tier)
            $nowSql = $driver === 'sqlite' ? "datetime('now')" : "NOW()";
            $db->exec("
                UPDATE tenants SET
                    legal_name        = 'OpsOne Demo Airline LLC',
                    display_name      = 'OpsOne Demo',
                    icao_code         = 'ODA',
                    iata_code         = 'OD',
                    primary_country   = 'UAE',
                    primary_base      = 'Dubai (DXB)',
                    support_tier      = 'standard',
                    onboarding_status = 'active',
                    onboarded_at      = $nowSql
                WHERE id = 1
            ");

            echo "✓ (" . count($modules) . " modules, " . count($defaultModules) . " enabled for demo tenant)\n";
        } catch (\Exception $e) {
            echo "(partial — " . $e->getMessage() . ")\n";
        }
    } else {
        echo "modules table not found — run migration 009 first.\n";
    }

    // ─── Step 19: Seed demo fleets ────────────────────────
    echo "Step 19: Seeding demo fleets... ";
    try {
        $fleetCheck = $driver === 'sqlite'
            ? $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='fleets'")->fetchColumn()
            : $db->query("SHOW TABLES LIKE 'fleets'")->fetchColumn();

        if ($fleetCheck) {
            $demoFleets = [
                ['Narrow-body Fleet', 'NBF', 'Boeing 737-800'],
                ['Wide-body Fleet',   'WBF', 'Airbus A330-300'],
                ['Regional Fleet',    'REG', 'ATR 72-600'],
            ];
            $fleetStmt = $db->prepare(
                "$insertIgnore INTO fleets (tenant_id, name, code, aircraft_type) VALUES (1, ?, ?, ?)"
            );
            foreach ($demoFleets as [$fname, $fcode, $ftype]) {
                $fleetStmt->execute([$fname, $fcode, $ftype]);
            }

            // Patch schema for SQLite: add columns if missing
            if ($driver === 'sqlite') {
                $userCols = array_column(
                    $db->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC), 'name'
                );
                foreach (['fleet_id','employment_status','profile_completion_pct'] as $col) {
                    if (!in_array($col, $userCols)) {
                        $default = $col === 'profile_completion_pct' ? 'DEFAULT 0' : 'DEFAULT NULL';
                        $db->exec("ALTER TABLE users ADD COLUMN $col TEXT $default");
                    }
                }
            }

            echo "✓ (3 fleets)\n";
        } else {
            echo "fleets table not found — run migration 013 first.\n";
        }
    } catch (\Exception $e) {
        echo "(partial — " . $e->getMessage() . ")\n";
    }

    // ─── Step 20: Seed qualifications + profile completion ─
    echo "Step 20: Seeding qualifications and profile completion... ";
    try {
        // Ensure qualifications table exists (migration 014)
        $qTables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='qualifications'")->fetchAll();
        if (empty($qTables)) {
            // Create table inline if migration not run
            $db->exec("
                CREATE TABLE IF NOT EXISTS qualifications (
                    id          INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id     INTEGER NOT NULL,
                    tenant_id   INTEGER NOT NULL,
                    qual_type   TEXT    NOT NULL,
                    qual_name   TEXT    NOT NULL,
                    reference_no TEXT,
                    authority   TEXT,
                    issue_date  TEXT,
                    expiry_date TEXT,
                    status      TEXT    NOT NULL DEFAULT 'active',
                    notes       TEXT,
                    created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
                )
            ");
        }

        $db->exec("DELETE FROM qualifications WHERE tenant_id = 1");

        $pilotUid   = $db->query("SELECT id FROM users WHERE email = 'demo.pilot@acentoza.com' AND tenant_id = 1")->fetchColumn();
        $cpUid      = $db->query("SELECT id FROM users WHERE email = 'demo.chiefpilot@acentoza.com' AND tenant_id = 1")->fetchColumn();
        $engUid     = $db->query("SELECT id FROM users WHERE email = 'demo.engineer@acentoza.com' AND tenant_id = 1")->fetchColumn();
        $cabinUid   = $db->query("SELECT id FROM users WHERE email = 'demo.cabin@acentoza.com' AND tenant_id = 1")->fetchColumn();

        $qStmt = $db->prepare(
            "INSERT OR IGNORE INTO qualifications (user_id, tenant_id, qual_type, qual_name, reference_no, authority, issue_date, expiry_date, status)
             VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?)"
        );

        if ($pilotUid) {
            $qStmt->execute([$pilotUid, 'Type Rating', 'B737-800 Type Rating', 'KEN-TR-B738-001', 'KCAA', '2022-06-15', date('Y-m-d', strtotime('+18 months')), 'active']);
            $qStmt->execute([$pilotUid, 'Instrument Rating', 'Multi-Engine / IR', 'KEN-ME-IR-0042', 'KCAA', '2021-03-10', date('Y-m-d', strtotime('+8 months')), 'active']);
        }
        if ($cpUid) {
            $qStmt->execute([$cpUid, 'Type Rating', 'A320 Type Rating', 'KEN-TR-A320-007', 'KCAA', '2020-01-20', date('Y-m-d', strtotime('+6 months')), 'active']);
            $qStmt->execute([$cpUid, 'Instructor Authority', 'B737 Type Rating Instructor', 'KEN-TRI-B738-001', 'KCAA', '2021-09-01', date('Y-m-d', strtotime('+12 months')), 'active']);
            $qStmt->execute([$cpUid, 'Check Airman', 'B737 Line Check Airman', 'KEN-LCA-001', 'KCAA', '2022-03-15', date('Y-m-d', strtotime('+5 months')), 'active']);
        }
        if ($engUid) {
            $qStmt->execute([$engUid, 'Endorsement', 'B737-800 Engine Run-up', 'KEN-ENG-B738-003', 'KCAA', '2023-01-10', date('Y-m-d', strtotime('+14 months')), 'active']);
            $qStmt->execute([$engUid, 'Approval', 'Part-66 B1 Aircraft Maintenance', 'P66-B1-KEN-0022', 'KCAA', '2019-05-01', null, 'active']);
        }
        if ($cabinUid) {
            $qStmt->execute([$cabinUid, 'Safety Course', 'Emergency Procedures & Safety Training', 'CAB-EPST-2024', 'KCAA', '2024-01-15', date('Y-m-d', strtotime('+10 months')), 'active']);
            $qStmt->execute([$cabinUid, 'CRM Course', 'Crew Resource Management', 'CAB-CRM-2023', 'KCAA', '2023-06-01', date('Y-m-d', strtotime('+20 months')), 'active']);
        }

        // Recalculate profile completion for all tenant-1 users
        $tenantUsers = $db->query("SELECT id FROM users WHERE tenant_id = 1")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tenantUsers as $uid) {
            // Count filled fields (simplified inline version for seeder)
            $u = $db->query("SELECT * FROM users WHERE id = $uid")->fetch(PDO::FETCH_ASSOC);
            $cp = $db->query("SELECT * FROM crew_profiles WHERE user_id = $uid")->fetch(PDO::FETCH_ASSOC);
            $licCount = (int) $db->query("SELECT COUNT(*) FROM licenses WHERE user_id = $uid")->fetchColumn();

            $score = 0;
            if (!empty($u['name']))              $score += 5;
            if (!empty($u['email']))             $score += 5;
            if (!empty($u['employee_id']))       $score += 5;
            if (!empty($u['department_id']))     $score += 5;
            if (!empty($u['base_id']))           $score += 5;
            if (!empty($u['employment_status'])) $score += 5;
            if ($cp) {
                if (!empty($cp['date_of_birth']))    $score += 5;
                if (!empty($cp['nationality']))      $score += 5;
                if (!empty($cp['phone']))            $score += 5;
                if (!empty($cp['emergency_name']))   $score += 5;
                if (!empty($cp['emergency_phone']))  $score += 5;
                if (!empty($cp['passport_number']))  $score += 5;
                if (!empty($cp['passport_expiry']))  $score += 5;
                if (!empty($cp['medical_class']))    $score += 5;
                if (!empty($cp['medical_expiry']))   $score += 5;
                if (!empty($cp['contract_type']))    $score += 5;
            }
            if ($licCount >= 1) $score += 10;
            if ($licCount >= 2) $score += 10;
            $score = min(100, $score);
            $db->exec("UPDATE users SET profile_completion_pct = $score WHERE id = $uid");
        }

        echo "✓\n";
    } catch (\Exception $e) {
        echo "(partial — " . $e->getMessage() . ")\n";
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
