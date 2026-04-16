<?php
/**
 * Diagnostic Page — check session and roles
 * To be run on Namecheap to debug Alex Mwangi's sidebar issue.
 */
require_once __DIR__ . '/config/app.php';
loadEnv(__DIR__ . '/.env');
require_once __DIR__ . '/app/Helpers/functions.php';
require_once __DIR__ . '/config/database.php';

session_start();

$user = $_SESSION['user'] ?? null;
$roles = $_SESSION['user_roles'] ?? [];
$isPlat = isPlatformOnly();

echo "<h2>Session Diagnostic</h2>";
echo "<pre>";
echo "User: " . print_r($user, true) . "\n";
echo "Roles in Session: " . print_r($roles, true) . "\n";
echo "Is Platform Session: " . ($isPlat ? 'YES' : 'NO') . "\n";
echo "</pre>";

if ($user) {
    try {
        $db = Database::getInstance();
        $dbRoles = $db->fetchAll("
            SELECT r.name, r.slug, r.role_type 
            FROM roles r 
            JOIN user_roles ur ON ur.role_id = r.id 
            WHERE ur.user_id = ?", 
            [$user['id']]
        );
        echo "<h2>Database Roles</h2>";
        echo "<pre>" . print_r($dbRoles, true) . "</pre>";
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
