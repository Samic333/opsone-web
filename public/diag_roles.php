<?php
/**
 * Role Check Script — Deep Dive
 */
require_once dirname(__DIR__) . '/config/app.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once dirname(__DIR__) . '/app/Helpers/functions.php';
require_once dirname(__DIR__) . '/config/database.php';

echo "<h2>System Roles Table</h2>";
$db = Database::getInstance();
$roles = $db->fetchAll("SELECT id, name, slug, role_type, tenant_id FROM roles ORDER BY slug");
echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Slug</th><th>Type</th><th>Tenant</th></tr>";
foreach($roles as $r) {
    echo "<tr><td>{$r['id']}</td><td>{$r['name']}</td><td>{$r['slug']}</td><td>{$r['role_type']}</td><td>{$r['tenant_id']}</td></tr>";
}
echo "</table>";

echo "<h2>Demo Users & Assignments</h2>";
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
