<?php
/**
 * UserController — user management for airline admin / HR
 */
class UserController {
    public function __construct() {
        RbacMiddleware::requireRole(['super_admin', 'airline_admin', 'hr']);
    }

    public function index(): void {
        $tenantId = currentTenantId();
        $statusFilter = $_GET['status'] ?? null;
        $users = UserModel::allForTenant($tenantId, $statusFilter);
        require VIEWS_PATH . '/users/index.php';
    }

    public function create(): void {
        $tenantId = currentTenantId();
        $roles = Database::fetchAll("SELECT * FROM roles WHERE tenant_id = ? ORDER BY name", [$tenantId]);
        $departments = Database::fetchAll("SELECT * FROM departments WHERE tenant_id = ? ORDER BY name", [$tenantId]);
        $bases = Database::fetchAll("SELECT * FROM bases WHERE tenant_id = ? ORDER BY name", [$tenantId]);
        require VIEWS_PATH . '/users/create.php';
    }

    public function store(): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/users/create');
        }

        $tenantId = currentTenantId();
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $employeeId = trim($_POST['employee_id'] ?? '');
        $departmentId = (int)($_POST['department_id'] ?? 0);
        $baseId = (int)($_POST['base_id'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        $mobileAccess = isset($_POST['mobile_access']) ? 1 : 0;
        $webAccess = isset($_POST['web_access']) ? 1 : 0;
        $roleIds = $_POST['roles'] ?? [];

        if (empty($name) || empty($email) || empty($password)) {
            flash('error', 'Name, email, and password are required.');
            redirect('/users/create');
        }

        // Check email uniqueness within tenant
        $existing = UserModel::findByEmail($email, $tenantId);
        if ($existing) {
            flash('error', 'A user with this email already exists.');
            redirect('/users/create');
        }

        $userId = UserModel::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'employee_id' => $employeeId ?: null,
            'department_id' => $departmentId,
            'base_id' => $baseId,
            'status' => $status,
            'mobile_access' => $mobileAccess,
            'web_access' => $webAccess,
        ]);

        // Assign roles
        foreach ($roleIds as $roleId) {
            UserModel::assignRole($userId, (int)$roleId, $tenantId);
        }

        AuditLog::log('Created User', 'user', $userId, "Created user: $name ($email)");
        flash('success', "User \"$name\" created successfully.");
        redirect('/users');
    }

    public function edit(int $id): void {
        $user = UserModel::find($id);
        if (!$user || $user['tenant_id'] != currentTenantId()) {
            flash('error', 'User not found.');
            redirect('/users');
        }
        $tenantId = currentTenantId();
        $roles = Database::fetchAll("SELECT * FROM roles WHERE tenant_id = ? ORDER BY name", [$tenantId]);
        $departments = Database::fetchAll("SELECT * FROM departments WHERE tenant_id = ? ORDER BY name", [$tenantId]);
        $bases = Database::fetchAll("SELECT * FROM bases WHERE tenant_id = ? ORDER BY name", [$tenantId]);
        $userRoles = UserModel::getRoles($id);
        $userRoleIds = array_column($userRoles, 'id');
        $devices = Device::forUser($id);
        require VIEWS_PATH . '/users/edit.php';
    }

    public function update(int $id): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect("/users/edit/$id");
        }

        $user = UserModel::find($id);
        if (!$user || $user['tenant_id'] != currentTenantId()) {
            flash('error', 'User not found.');
            redirect('/users');
        }

        $tenantId = currentTenantId();
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $employeeId = trim($_POST['employee_id'] ?? '');
        $departmentId = (int)($_POST['department_id'] ?? 0);
        $baseId = (int)($_POST['base_id'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        $mobileAccess = isset($_POST['mobile_access']) ? 1 : 0;
        $webAccess = isset($_POST['web_access']) ? 1 : 0;
        $roleIds = $_POST['roles'] ?? [];

        if (empty($name) || empty($email)) {
            flash('error', 'Name and email are required.');
            redirect("/users/edit/$id");
        }

        UserModel::update($id, [
            'name' => $name,
            'email' => $email,
            'password' => $password ?: null,
            'employee_id' => $employeeId ?: null,
            'department_id' => $departmentId,
            'base_id' => $baseId,
            'status' => $status,
            'mobile_access' => $mobileAccess,
            'web_access' => $webAccess,
        ]);

        // Re-assign roles
        UserModel::clearRoles($id);
        foreach ($roleIds as $roleId) {
            UserModel::assignRole($id, (int)$roleId, $tenantId);
        }

        AuditLog::log('Updated User', 'user', $id, "Updated user: $name ($email)");
        flash('success', "User \"$name\" updated successfully.");
        redirect('/users');
    }

    public function toggleStatus(int $id): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/users');
        }
        $user = UserModel::find($id);
        if (!$user || $user['tenant_id'] != currentTenantId()) {
            flash('error', 'User not found.');
            redirect('/users');
        }
        UserModel::toggleStatus($id);
        $action = $user['status'] === 'active' ? 'Suspended' : 'Activated';
        AuditLog::log("$action User", 'user', $id, "$action user: {$user['name']}");
        flash('success', "User \"{$user['name']}\" $action.");
        redirect('/users');
    }
}
