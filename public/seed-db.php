<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/config/app.php';

$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    loadEnv($envFile);
}

require BASE_PATH . '/app/Helpers/functions.php';
require BASE_PATH . '/config/database.php';

echo "<h1>OpsOne Database Setup & Seeder</h1>";
echo "<p>Connecting to database...</p>";

try {
    $db = Database::getInstance();
    echo "<p>Connected successfully.</p>";
} catch (\Exception $e) {
    echo "<p><strong style='color:red;'>Connection Error:</strong> " . e($e->getMessage()) . "</p>";
    exit;
}

// 1. RUN MIGRATIONS (Create Tables)
echo "<h3>1. Creating Database Schema...</h3>";
$migrations = [
    BASE_PATH . '/database/migrations/001_create_schema.sql',
    BASE_PATH . '/database/migrations/003_opsone_additions.sql'
];

foreach ($migrations as $migFile) {
    if (!file_exists($migFile)) {
        echo "<p style='color:orange;'>Skipping missing migration: $migFile</p>";
        continue;
    }
    
    echo "<li>Executing " . basename($migFile) . "... ";
    $sql = file_get_contents($migFile);
    // Remove comments and multi-statements
    $cleanSql = preg_replace('/--[^\n]*/', '', $sql);
    $statements = explode(';', $cleanSql);
    
    $count = 0;
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if (empty($stmt)) continue;
        try {
            $db->exec($stmt);
            $count++;
        } catch (\PDOException $e) {
            // Silently ignore 'already exists' errors
            if (!str_contains($e->getMessage(), 'already exists')) {
                echo "<br><small style='color:red;'>Error in " . basename($migFile) . ": " . e($e->getMessage()) . "</small>";
            }
        }
    }
    echo "Done ($count statements executed)</li>";
}

// 2. SEED DATA
echo "<h3>2. Seeding Demo Data...</h3>";

// Check if already seeded
try {
    $existingTenant = Database::fetch("SELECT id FROM tenants WHERE code = 'GWA'");
} catch (\Exception $e) {
    $existingTenant = null;
}

if ($existingTenant) {
    echo "<p>Database already contains data. Wiping for a clean slate...</p>";
    Database::execute("DELETE FROM user_roles");
    Database::execute("DELETE FROM users");
    Database::execute("DELETE FROM roles");
    Database::execute("DELETE FROM file_categories");
    Database::execute("DELETE FROM bases");
    Database::execute("DELETE FROM departments");
    Database::execute("DELETE FROM tenants");
}

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

echo "<ul>";
echo "<li>Creating system roles...</li>";
foreach ($sysRoles as [$name, $slug, $desc]) {
    Database::insert("INSERT INTO roles (tenant_id, name, slug, description, is_system) VALUES (NULL, ?, ?, ?, 1)", [$name, $slug, $desc]);
}

echo "<li>Creating demo airline...</li>";
Database::insert("INSERT INTO tenants (name, code, contact_email, is_active) VALUES ('Gulf Wings Aviation', 'GWA', 'admin@gulfwings.aero', 1)");
$tenantId = (int) $db->lastInsertId();

echo "<li>Creating tenant roles...</li>";
foreach ($sysRoles as [$name, $slug, $desc]) {
    Database::insert("INSERT INTO roles (tenant_id, name, slug, description, is_system) VALUES (?, ?, ?, ?, 0)", [$tenantId, $name, $slug, $desc]);
}

echo "<li>Creating departments...</li>";
$depts = ['Flight Operations', 'Cabin Operations', 'Engineering', 'Human Resources', 'Safety', 'Operations', 'IT', 'Management'];
$deptIds = [];
foreach ($depts as $dept) {
    $deptIds[$dept] = Database::insert("INSERT INTO departments (tenant_id, name) VALUES (?, ?)", [$tenantId, $dept]);
}

echo "<li>Creating bases...</li>";
$baseId1 = Database::insert("INSERT INTO bases (tenant_id, name, code) VALUES (?, 'Dubai International', 'DXB')", [$tenantId]);
$baseId2 = Database::insert("INSERT INTO bases (tenant_id, name, code) VALUES (?, 'Abu Dhabi International', 'AUH')", [$tenantId]);
$baseId = $baseId1;

echo "<li>Creating demo users...</li>";
$pw = password_hash('demo', PASSWORD_BCRYPT);
$users = [
    ['Tariq Al-Fayed',          'ceo@airline.com',        'DIR-001', $deptIds['Management'],        $baseId, 'director'],
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

$roleRows = Database::fetchAll("SELECT id, slug FROM roles WHERE tenant_id = ?", [$tenantId]);
$roleMap = [];
foreach ($roleRows as $r) $roleMap[$r['slug']] = $r['id'];

foreach ($users as [$name, $email, $empId, $deptId, $bId, $roleSlug]) {
    $userId = Database::insert(
        "INSERT INTO users (tenant_id, name, email, password_hash, employee_id, department_id, base_id, status, mobile_access, web_access)
         VALUES (?, ?, ?, ?, ?, ?, ?, 'active', 1, 1)",
        [$tenantId, $name, $email, $pw, $empId, $deptId, $bId]
    );
    if (isset($roleMap[$roleSlug])) {
        Database::insert("INSERT INTO user_roles (user_id, role_id, tenant_id) VALUES (?, ?, ?)", [$userId, $roleMap[$roleSlug], $tenantId]);
    }
    if ($roleSlug === 'super_admin' && isset($roleMap['airline_admin'])) {
        Database::insert("INSERT INTO user_roles (user_id, role_id, tenant_id) VALUES (?, ?, ?)", [$userId, $roleMap['airline_admin'], $tenantId]);
    }
    if ($roleSlug === 'director' && isset($roleMap['airline_admin'])) {
        Database::insert("INSERT INTO user_roles (user_id, role_id, tenant_id) VALUES (?, ?, ?)", [$userId, $roleMap['airline_admin'], $tenantId]);
    }
}

echo "<li>Creating file categories...</li>";
$cats = [['Manuals','manuals'],['Notices','notices'],['Licenses','licenses'],['Training','training'],['Memos','memos'],['Safety Bulletins','safety_bulletins'],['General Documents','general']];
foreach ($cats as [$catName, $catSlug]) {
    Database::insert("INSERT INTO file_categories (tenant_id, name, slug) VALUES (?, ?, ?)", [$tenantId, $catName, $catSlug]);
}

echo "<li>Creating demo app build...</li>";
Database::insert(
    "INSERT INTO app_builds (version, build_number, platform, release_notes, file_path, is_active, uploaded_by) 
     VALUES (?, ?, ?, ?, ?, ?, ?)",
    ['1.0.0', '100', 'ios', 'Initial production release for CrewAssist.', 'OpsOne_v1.0.0.ipa', 1, $userId]
);

echo "</ul>";
echo "<h2>✅ Setup Complete! Tables created and data seeded.</h2>";
echo "<p>Please <strong>delete this file (seed-db.php)</strong> from the server for security.</p>";
echo "<p><a href='/login'>Go to Login</a></p>";
