<?php
/**
 * PlatformUsersController
 *
 * Manages platform-staff accounts (super_admin, platform_support,
 * platform_security, system_monitoring). These users have tenant_id = NULL
 * and never appear in airline /users lists.
 */
class PlatformUsersController {

    private const PLATFORM_ROLES = [
        'super_admin'       => 'Platform Super Admin',
        'platform_support'  => 'Platform Support Admin',
        'platform_security' => 'Platform Security Admin',
        'system_monitoring' => 'System Monitoring Admin',
    ];

    public function index(): void {
        RbacMiddleware::requirePlatformSuperAdmin();

        $isSqlite = env('DB_DRIVER', 'mysql') === 'sqlite';
        $gcSlugs  = $isSqlite
            ? "GROUP_CONCAT(r.slug, ',')"
            : "GROUP_CONCAT(r.slug ORDER BY r.slug SEPARATOR ',')";
        $gcNames  = $isSqlite
            ? "GROUP_CONCAT(r.name, ',')"
            : "GROUP_CONCAT(r.name ORDER BY r.slug SEPARATOR ',')";

        $db    = Database::getInstance();
        $users = $db->query(
            "SELECT u.id, u.name, u.email, u.employee_id, u.status, u.web_access,
                    u.last_login_at, u.created_at,
                    $gcSlugs AS role_slugs,
                    $gcNames AS role_names
             FROM users u
             LEFT JOIN user_roles ur ON ur.user_id = u.id
             LEFT JOIN roles r       ON r.id = ur.role_id
             WHERE u.tenant_id IS NULL
             GROUP BY u.id
             ORDER BY u.name ASC"
        )->fetchAll();

        $pageTitle    = 'Platform Staff';
        $pageSubtitle = 'Platform-level accounts';
        $headerAction = '<a href="/platform/users/create" class="btn btn-primary">+ Add Staff</a>';
        require VIEWS_PATH . '/platform/users.php';
    }

    public function create(): void {
        RbacMiddleware::requirePlatformSuperAdmin();

        $platformRoles = self::PLATFORM_ROLES;
        $pageTitle     = 'Add Platform Staff';
        $headerAction  = '<a href="/platform/users" class="btn btn-outline">← Back</a>';
        require VIEWS_PATH . '/platform/users_create.php';
    }

    public function store(): void {
        RbacMiddleware::requirePlatformSuperAdmin();

        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/platform/users/create');
        }

        $name      = trim($_POST['name'] ?? '');
        $email     = strtolower(trim($_POST['email'] ?? ''));
        $empId     = trim($_POST['employee_id'] ?? '');
        $roleSlug  = $_POST['role'] ?? '';
        $password  = $_POST['password'] ?? '';

        if (!$name || !$email || !$roleSlug || !$password) {
            flash('error', 'Name, email, role, and password are required.');
            redirect('/platform/users/create');
        }

        if (!array_key_exists($roleSlug, self::PLATFORM_ROLES)) {
            flash('error', 'Invalid role selected.');
            redirect('/platform/users/create');
        }

        $db = Database::getInstance();

        // Check email is unique across all users
        $existing = $db->prepare("SELECT id FROM users WHERE email = ?");
        $existing->execute([$email]);
        if ($existing->fetchColumn()) {
            flash('error', 'A user with that email already exists.');
            redirect('/platform/users/create');
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $db->prepare(
            "INSERT INTO users (tenant_id, name, email, employee_id, status, password_hash, web_access, mobile_access)
             VALUES (NULL, ?, ?, ?, 'active', ?, 1, 0)"
        )->execute([$name, $email, $empId ?: null, $hash]);

        $userId = (int) $db->lastInsertId();

        // Assign system role (NULL tenant)
        $roleRow = $db->prepare("SELECT id FROM roles WHERE slug = ? AND tenant_id IS NULL");
        $roleRow->execute([$roleSlug]);
        $roleId = (int) $roleRow->fetchColumn();

        if ($roleId) {
            $db->prepare(Database::insertIgnore() . " INTO user_roles (user_id, role_id, tenant_id) VALUES (?, ?, NULL)")
               ->execute([$userId, $roleId]);
        }

        AuditLog::log('Platform User Created', 'user', $userId, "Platform staff account created: $email ($roleSlug)");

        flash('success', 'Platform staff account created.');
        redirect('/platform/users');
    }

    public function toggle(int $id): void {
        RbacMiddleware::requirePlatformSuperAdmin();

        if (!verifyCsrf()) {
            redirect('/platform/users');
        }

        $db   = Database::getInstance();
        $user = $db->prepare("SELECT id, status FROM users WHERE id = ? AND tenant_id IS NULL");
        $user->execute([$id]);
        $user = $user->fetch();

        if (!$user) {
            flash('error', 'Platform user not found.');
            redirect('/platform/users');
        }

        $newStatus = $user['status'] === 'active' ? 'inactive' : 'active';
        $db->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$newStatus, $id]);

        AuditLog::log('Platform User Status Changed', 'user', $id, "Status set to $newStatus");
        redirect('/platform/users');
    }
}
