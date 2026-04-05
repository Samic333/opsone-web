<?php
/**
 * CrewAssist Portal — Setup & Migration Script
 * Run: php setup.php
 */

echo "╔══════════════════════════════════════════╗\n";
echo "║   OpsOne Platform — Setup Script         ║\n";
echo "╚══════════════════════════════════════════╝\n\n";

// Load config
define('BASE_PATH', __DIR__);
require __DIR__ . '/config/app.php';
loadEnv(__DIR__ . '/.env');
require __DIR__ . '/app/Helpers/functions.php';
require __DIR__ . '/config/database.php';

// 1. Check PHP version
echo "1. Checking PHP version... ";
if (PHP_VERSION_ID < 80200) {
    echo "❌ PHP 8.2+ required. You have " . PHP_VERSION . "\n";
    exit(1);
}
echo "✓ PHP " . PHP_VERSION . "\n";

// 2. Check extensions
echo "2. Checking extensions... ";
$driver = env('DB_DRIVER', 'mysql');
$required = ['pdo', 'json', 'mbstring'];
if ($driver === 'mysql') $required[] = 'pdo_mysql';
if ($driver === 'sqlite') $required[] = 'pdo_sqlite';
$missing = [];
foreach ($required as $ext) {
    if (!extension_loaded($ext)) $missing[] = $ext;
}
if (!empty($missing)) {
    echo "❌ Missing extensions: " . implode(', ', $missing) . "\n";
    exit(1);
}
echo "✓ All required extensions present (driver: $driver)\n";

// 3. Create directories
echo "3. Creating directories... ";
$dirs = [
    __DIR__ . '/storage/uploads',
    __DIR__ . '/storage/logs',
    __DIR__ . '/database',
];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0755, true);
}
foreach ([$dirs[0], $dirs[1]] as $dir) {
    $gitkeep = $dir . '/.gitkeep';
    if (!file_exists($gitkeep)) file_put_contents($gitkeep, '');
}
echo "✓\n";

// 4. Test database connection
echo "4. Connecting to database... ";
try {
    $pdo = Database::getInstance();
    if ($driver === 'sqlite') {
        echo "✓ SQLite at " . env('DB_DATABASE') . "\n";
    } else {
        echo "✓ MySQL at " . env('DB_HOST') . "\n";
    }
} catch (\Exception $e) {
    echo "❌ " . $e->getMessage() . "\n";
    exit(1);
}

// 5. Run migrations
echo "5. Running migrations...\n";
if ($driver === 'sqlite') {
    $migFile = __DIR__ . '/database/migrations/002_sqlite_schema.sql';
} else {
    $migFile = __DIR__ . '/database/migrations/001_create_schema.sql';
}

$sql = file_get_contents($migFile);
// Remove comments and split by semicolons
$cleanSql = preg_replace('/--[^\n]*/', '', $sql);
$rawStatements = explode(';', $cleanSql);
foreach ($rawStatements as $stmt) {
    $stmt = trim($stmt);
    if (empty($stmt)) continue;
    try {
        $pdo->exec($stmt);
    } catch (\PDOException $e) {
        if (!str_contains($e->getMessage(), 'already exists')) {
            echo "   ⚠ " . $e->getMessage() . "\n";
        }
    }
}
echo "   ✓ Schema created\n";

// 5b. Run OpsOne additions migration
echo "   Running OpsOne additions...\n";
$opsoneFile = __DIR__ . '/database/migrations/003_opsone_additions.sql';
if (file_exists($opsoneFile)) {
    $opsSql = file_get_contents($opsoneFile);
    $opsClean = preg_replace('/--[^\n]*/', '', $opsSql);
    $opsStatements = explode(';', $opsClean);
    foreach ($opsStatements as $stmt) {
        $stmt = trim($stmt);
        if (empty($stmt)) continue;
        try {
            $pdo->exec($stmt);
        } catch (\PDOException $e) {
            if (!str_contains($e->getMessage(), 'already exists')) {
                echo "   ⚠ " . $e->getMessage() . "\n";
            }
        }
    }
    echo "   ✓ OpsOne additions applied\n";
}

// 6. Seed data
echo "6. Seeding data...\n";
$db = Database::getInstance();

// System roles
$sysRoles = [
    ['Super Admin', 'super_admin', 'Full system access'],
    ['Airline Admin', 'airline_admin', 'Airline-level administration'],
    ['HR', 'hr', 'Human resources management'],
    ['Scheduler', 'scheduler', 'Scheduling and crew control'],
    ['Pilot', 'pilot', 'Flight crew - pilot'],
    ['Cabin Crew', 'cabin_crew', 'Cabin crew member'],
    ['Engineer', 'engineer', 'Engineering / maintenance'],
    ['Safety Officer', 'safety_officer', 'Safety management'],
    ['Document Control', 'document_control', 'Document management'],
    ['Chief Pilot', 'chief_pilot', 'Chief pilot - oversight'],
    ['FDM Analyst', 'fdm_analyst', 'Flight data monitoring'],
    ['Base Manager', 'base_manager', 'Base operations management'],
    ['Director', 'director', 'Executive director'],
];

// Check if already seeded
$existingTenant = Database::fetch("SELECT id FROM tenants WHERE code = 'GWA'");
if ($existingTenant) {
    echo "   ⚠ Database already seeded, skipping...\n";
} else {
    // System roles
    echo "   Creating system roles... ";
    foreach ($sysRoles as [$name, $slug, $desc]) {
        Database::insert("INSERT INTO roles (tenant_id, name, slug, description, is_system) VALUES (NULL, ?, ?, ?, 1)", [$name, $slug, $desc]);
    }
    echo "✓\n";

    // Demo tenant
    echo "   Creating demo airline... ";
    Database::insert("INSERT INTO tenants (name, code, contact_email, is_active) VALUES ('Gulf Wings Aviation', 'GWA', 'admin@gulfwings.aero', 1)");
    $tenantId = (int) $db->lastInsertId();
    echo "✓ (id=$tenantId)\n";

    // Tenant roles
    echo "   Creating tenant roles... ";
    foreach ($sysRoles as [$name, $slug, $desc]) {
        Database::insert("INSERT INTO roles (tenant_id, name, slug, description, is_system) VALUES (?, ?, ?, ?, 0)", [$tenantId, $name, $slug, $desc]);
    }
    echo "✓\n";

    // Departments
    echo "   Creating departments... ";
    $depts = ['Flight Operations', 'Cabin Operations', 'Engineering', 'Human Resources', 'Safety', 'Operations', 'IT', 'Management'];
    $deptIds = [];
    foreach ($depts as $dept) {
        $deptIds[$dept] = Database::insert("INSERT INTO departments (tenant_id, name) VALUES (?, ?)", [$tenantId, $dept]);
    }
    echo "✓\n";

    // Bases
    echo "   Creating bases... ";
    $baseId = Database::insert("INSERT INTO bases (tenant_id, name, code) VALUES (?, 'Dubai International', 'DXB')", [$tenantId]);
    Database::insert("INSERT INTO bases (tenant_id, name, code) VALUES (?, 'Abu Dhabi International', 'AUH')", [$tenantId]);
    echo "✓\n";

    // Demo users
    echo "   Creating demo users... ";
    $pw = password_hash('demo', PASSWORD_BCRYPT);
    $users = [
        ['Omar Bin Zayed',          'admin@airline.com',      'SYS-001', $deptIds['IT'],                $baseId, 'super_admin'],
        ['Fatima Al-Zaabi',         'hr@airline.com',         'HR-003',  $deptIds['Human Resources'],   $baseId, 'hr'],
        ['Captain Rashid Hussein',  'pilot@airline.com',      'FLT-001', $deptIds['Flight Operations'], $baseId, 'pilot'],
        ['Noor Al-Rashidi',         'cabin@airline.com',      'CAB-020', $deptIds['Cabin Operations'],  $baseId, 'cabin_crew'],
        ['Mark Sullivan',           'engineer@airline.com',   'ENG-007', $deptIds['Engineering'],       $baseId, 'engineer'],
        ['Layla Hassan',            'scheduling@airline.com', 'OPS-009', $deptIds['Operations'],        $baseId, 'scheduler'],
        ['James Okafor',            'safety@airline.com',     'SAF-002', $deptIds['Safety'],            $baseId, 'safety_officer'],
        ['Sara Khalid',             'doccontrol@airline.com', 'DOC-001', $deptIds['Operations'],        $baseId, 'document_control'],
        ['Capt. Ahmed Al-Mansoori', 'chiefpilot@airline.com', 'FLT-CP1', $deptIds['Flight Operations'], $baseId, 'chief_pilot'],
        ['Dr. Priya Sharma',        'fdm@airline.com',        'FDM-001', $deptIds['Safety'],            $baseId, 'fdm_analyst'],
    ];

    // Get tenant role IDs
    $roleRows = Database::fetchAll("SELECT id, slug FROM roles WHERE tenant_id = ?", [$tenantId]);
    $roleMap = [];
    foreach ($roleRows as $r) $roleMap[$r['slug']] = $r['id'];

    foreach ($users as [$name, $email, $empId, $deptId, $bId, $roleSlug]) {
        $userId = Database::insert(
            "INSERT INTO users (tenant_id, name, email, password_hash, employee_id, department_id, base_id, status, mobile_access)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'active', 1)",
            [$tenantId, $name, $email, $pw, $empId, $deptId, $bId]
        );
        // Assign role
        if (isset($roleMap[$roleSlug])) {
            Database::insert("INSERT INTO user_roles (user_id, role_id, tenant_id) VALUES (?, ?, ?)", [$userId, $roleMap[$roleSlug], $tenantId]);
        }
        // Give admin and hr additional airline_admin role
        if ($roleSlug === 'super_admin' && isset($roleMap['airline_admin'])) {
            Database::insert("INSERT INTO user_roles (user_id, role_id, tenant_id) VALUES (?, ?, ?)", [$userId, $roleMap['airline_admin'], $tenantId]);
        }
        if ($roleSlug === 'hr' && isset($roleMap['airline_admin'])) {
            Database::insert("INSERT INTO user_roles (user_id, role_id, tenant_id) VALUES (?, ?, ?)", [$userId, $roleMap['airline_admin'], $tenantId]);
        }
    }
    echo "✓ (10 users created)\n";

    // File categories
    echo "   Creating file categories... ";
    $cats = [['Manuals','manuals'],['Notices','notices'],['Licenses','licenses'],['Training','training'],['Memos','memos'],['Safety Bulletins','safety_bulletins'],['General Documents','general']];
    foreach ($cats as [$catName, $catSlug]) {
        Database::insert("INSERT INTO file_categories (tenant_id, name, slug) VALUES (?, ?, ?)", [$tenantId, $catName, $catSlug]);
    }
    echo "✓\n";
}

echo "\n╔══════════════════════════════════════════╗\n";
echo "║   ✅ Setup Complete!                      ║\n";
echo "╠══════════════════════════════════════════╣\n";
echo "║   Start the dev server:                   ║\n";
echo "║   php -S localhost:8080 -t public/         ║\n";
echo "║                                            ║\n";
echo "║   Homepage: http://localhost:8080/home      ║\n";
echo "║   Login: admin@airline.com / demo          ║\n";
echo "║   HR:    hr@airline.com / demo             ║\n";
echo "╚══════════════════════════════════════════╝\n";
