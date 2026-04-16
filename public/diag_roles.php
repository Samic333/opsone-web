<?php
/**
 * Role Check Script — Deep Dive
 */
require_once dirname(__DIR__) . '/config/app.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once dirname(__DIR__) . '/app/Helpers/functions.php';
require_once dirname(__DIR__) . '/config/database.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Deep Dive Diagnostic</h1>";

try {
    $db = Database::getInstance();
    
    echo "<h2>Checking Tables Structure</h2>";
    foreach (['roles', 'users', 'user_roles'] as $table) {
        try {
            $cols = $db->fetchAll("DESCRIBE `$table` ");
            echo "<h3>$table Structure</h3><pre>";
            foreach($cols as $c) echo "{$c['Field']} - {$c['Type']}\n";
            echo "</pre>";
        } catch(\Exception $e) {
            echo "<p style='color:red'>Error describing $table: " . $e->getMessage() . "</p>";
        }
    }

    echo "<h2>System Roles Table</h2>";
    try {
        $roles = $db->fetchAll("SELECT * FROM roles ORDER BY id ASC");
        echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Slug</th><th>Type</th><th>Tenant</th></tr>";
        foreach($roles as $r) {
            echo "<tr><td>{$r['id']}</td><td>{$r['name']}</td><td>{$r['slug']}</td><td>" . ($r['role_type'] ?? 'N/A') . "</td><td>{$r['tenant_id']}</td></tr>";
        }
        echo "</table>";
    } catch(\Exception $e) {
        echo "<p style='color:red'>Error fetching roles: " . $e->getMessage() . "</p>";
    }

    echo "<h2>Demo Users & Assignments</h2>";
    try {
        $users = $db->fetchAll("
            SELECT u.id, u.name, u.email, u.tenant_id, GROUP_CONCAT(r.slug) as roles
            FROM users u
            LEFT JOIN user_roles ur ON ur.user_id = u.id
            LEFT JOIN roles r ON r.id = ur.role_id
            WHERE u.email LIKE 'demo.%'
            GROUP BY u.id
        ");
        echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Email</th><th>Tenant</th><th>Roles (Session)</th></tr>";
        foreach($users as $u) {
            echo "<tr><td>{$u['id']}</td><td>{$u['name']}</td><td>{$u['email']}</td><td>{$u['tenant_id']}</td><td>{$u['roles']}</td></tr>";
        }
        echo "</table>";
    } catch(\Exception $e) {
        echo "<p style='color:red'>Error fetching users: " . $e->getMessage() . "</p>";
    }

} catch (\Exception $e) {
    echo "<h1>Critical Error</h1>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}

