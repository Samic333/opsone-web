<?php
/**
 * Database Seeder
 * Seeds initial data: default roles, demo tenant, demo users
 * Run: php database/seeders/seed.php
 */

require dirname(__DIR__, 2) . '/config/app.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require dirname(__DIR__, 2) . '/app/Helpers/functions.php';
require dirname(__DIR__, 2) . '/config/database.php';

echo "🌱 Seeding OpsOne database...\n\n";

try {
    $db = Database::getInstance();

    // ─── 1. Create system roles ──────────────────────────
    echo "Creating system roles... ";
    $roles = [
        ['Super Admin',    'super_admin',   'Full system access', 1],
        ['Airline Admin',  'airline_admin', 'Airline-level administration', 1],
        ['HR',             'hr',            'Human resources management', 1],
        ['Scheduler',      'scheduler',     'Scheduling and crew control', 1],
        ['Pilot',          'pilot',         'Flight crew - pilot', 1],
        ['Cabin Crew',     'cabin_crew',    'Cabin crew member', 1],
        ['Engineer',       'engineer',      'Engineering / maintenance', 1],
        ['Safety Officer', 'safety_officer','Safety management', 1],
        ['Document Control','document_control','Document management', 1],
        ['Chief Pilot',    'chief_pilot',   'Chief pilot - oversight', 1],
        ['FDM Analyst',    'fdm_analyst',   'Flight data monitoring', 1],
        ['Base Manager',   'base_manager',  'Base operations management', 1],
        ['Director',       'director',      'Executive director', 1],
    ];

    $roleStmt = $db->prepare(
        "INSERT IGNORE INTO roles (tenant_id, name, slug, description, is_system) VALUES (NULL, ?, ?, ?, ?)"
    );
    foreach ($roles as $role) {
        $roleStmt->execute($role);
    }
    echo "✓\n";

    // ─── 2. Create demo tenant ───────────────────────────
    echo "Creating demo airline tenant... ";
    $db->prepare(
        "INSERT IGNORE INTO tenants (id, name, code, contact_email, is_active) VALUES (1, ?, ?, ?, 1)"
    )->execute(['Gulf Wings Aviation', 'GWA', 'admin@gulfwings.aero']);
    echo "✓\n";

    // ─── 3. Create tenant-specific roles ─────────────────
    echo "Creating tenant roles... ";
    $tenantRoleStmt = $db->prepare(
        "INSERT IGNORE INTO roles (tenant_id, name, slug, description, is_system) VALUES (1, ?, ?, ?, 0)"
    );
    foreach ($roles as $role) {
        $tenantRoleStmt->execute([$role[0], $role[1], $role[2], 0]);
    }
    echo "✓\n";

    // ─── 4. Create departments ───────────────────────────
    echo "Creating departments... ";
    $depts = [
        [1, 'Flight Operations', 'FLT'],
        [1, 'Cabin Operations', 'CAB'],
        [1, 'Engineering', 'ENG'],
        [1, 'Human Resources', 'HR'],
        [1, 'Safety', 'SAF'],
        [1, 'Operations', 'OPS'],
        [1, 'IT', 'IT'],
        [1, 'Management', 'MGT'],
    ];
    $deptStmt = $db->prepare(
        "INSERT IGNORE INTO departments (tenant_id, name, code) VALUES (?, ?, ?)"
    );
    foreach ($depts as $dept) {
        $deptStmt->execute($dept);
    }
    echo "✓\n";

    // ─── 5. Create bases ─────────────────────────────────
    echo "Creating bases... ";
    $bases = [
        [1, 'Dubai International', 'DXB'],
        [1, 'Abu Dhabi International', 'AUH'],
        [1, 'Sharjah Airport', 'SHJ'],
    ];
    $baseStmt = $db->prepare(
        "INSERT IGNORE INTO bases (tenant_id, name, code) VALUES (?, ?, ?)"
    );
    foreach ($bases as $base) {
        $baseStmt->execute($base);
    }
    echo "✓\n";

    // ─── 6. Create demo users ────────────────────────────
    echo "Creating demo users... ";
    $password = password_hash('demo', PASSWORD_BCRYPT);

    $users = [
        [1, 'Omar Bin Zayed',           'admin@airline.com',      'SYS-001', 7, 1, 'active', $password],
        [1, 'Fatima Al-Zaabi',          'hr@airline.com',         'HR-003',  4, 1, 'active', $password],
        [1, 'Captain Rashid Hussein',   'pilot@airline.com',      'FLT-001', 1, 1, 'active', $password],
        [1, 'Noor Al-Rashidi',          'cabin@airline.com',      'CAB-020', 2, 1, 'active', $password],
        [1, 'Mark Sullivan',            'engineer@airline.com',   'ENG-007', 3, 1, 'active', $password],
        [1, 'Layla Hassan',             'scheduling@airline.com', 'OPS-009', 6, 1, 'active', $password],
        [1, 'James Okafor',             'safety@airline.com',     'SAF-002', 5, 1, 'active', $password],
        [1, 'Sara Khalid',              'doccontrol@airline.com', 'DOC-001', 6, 1, 'active', $password],
        [1, 'Capt. Ahmed Al-Mansoori',  'chiefpilot@airline.com', 'FLT-CP1', 1, 1, 'active', $password],
        [1, 'Dr. Priya Sharma',         'fdm@airline.com',        'FDM-001', 5, 1, 'active', $password],
    ];

    $userStmt = $db->prepare(
        "INSERT IGNORE INTO users (tenant_id, name, email, employee_id, department_id, base_id, status, password_hash)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    foreach ($users as $user) {
        $userStmt->execute($user);
    }
    echo "✓\n";

    // ─── 7. Assign roles to users ────────────────────────
    echo "Assigning roles... ";
    // Get tenant role IDs
    $tenantRoles = $db->query("SELECT id, slug FROM roles WHERE tenant_id = 1")->fetchAll(PDO::FETCH_KEY_PAIR);
    $userEmails = $db->query("SELECT email, id FROM users WHERE tenant_id = 1")->fetchAll(PDO::FETCH_KEY_PAIR);

    $roleMap = [
        'admin@airline.com'       => 'super_admin',
        'hr@airline.com'          => 'hr',
        'pilot@airline.com'       => 'pilot',
        'cabin@airline.com'       => 'cabin_crew',
        'engineer@airline.com'    => 'engineer',
        'scheduling@airline.com'  => 'scheduler',
        'safety@airline.com'      => 'safety_officer',
        'doccontrol@airline.com'  => 'document_control',
        'chiefpilot@airline.com'  => 'chief_pilot',
        'fdm@airline.com'         => 'fdm_analyst',
    ];

    // Also give admin@airline.com the airline_admin role
    $roleAssignStmt = $db->prepare(
        "INSERT IGNORE INTO user_roles (user_id, role_id, tenant_id) VALUES (?, ?, 1)"
    );
    foreach ($roleMap as $email => $roleSlug) {
        if (isset($userEmails[$email]) && isset($tenantRoles[$roleSlug])) {
            $roleAssignStmt->execute([$userEmails[$email], $tenantRoles[$roleSlug]]);
        }
    }
    // Give admin airline_admin too
    if (isset($userEmails['admin@airline.com']) && isset($tenantRoles['airline_admin'])) {
        $roleAssignStmt->execute([$userEmails['admin@airline.com'], $tenantRoles['airline_admin']]);
    }
    // Give hr airline_admin too
    if (isset($userEmails['hr@airline.com']) && isset($tenantRoles['airline_admin'])) {
        $roleAssignStmt->execute([$userEmails['hr@airline.com'], $tenantRoles['airline_admin']]);
    }
    echo "✓\n";

    // ─── 8. Create file categories ───────────────────────
    echo "Creating file categories... ";
    $categories = [
        [1, 'Manuals', 'manuals'],
        [1, 'Notices', 'notices'],
        [1, 'Licenses', 'licenses'],
        [1, 'Training', 'training'],
        [1, 'Memos', 'memos'],
        [1, 'Safety Bulletins', 'safety_bulletins'],
        [1, 'General Documents', 'general'],
    ];
    $catStmt = $db->prepare(
        "INSERT IGNORE INTO file_categories (tenant_id, name, slug) VALUES (?, ?, ?)"
    );
    foreach ($categories as $cat) {
        $catStmt->execute($cat);
    }
    echo "✓\n";

    echo "\n✅ Seeding complete!\n";
    echo "   Demo credentials: any demo email / password: \"demo\"\n";
    echo "   Super admin: admin@airline.com / demo\n";
    echo "   HR: hr@airline.com / demo\n";

} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
}
