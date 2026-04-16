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
        $tenantId    = currentTenantId();
        $roles       = Database::fetchAll("SELECT MIN(id) as id, name, slug FROM roles WHERE tenant_id = ? GROUP BY slug ORDER BY name", [$tenantId]);
        $departments = Database::fetchAll("SELECT * FROM departments WHERE tenant_id = ? ORDER BY name", [$tenantId]);
        $bases       = Database::fetchAll("SELECT * FROM bases WHERE tenant_id = ? ORDER BY name", [$tenantId]);
        $fleets      = Fleet::allForTenant($tenantId);
        require VIEWS_PATH . '/users/create.php';
    }

    public function store(): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/users/create');
        }

        $tenantId         = currentTenantId();
        $name             = trim($_POST['name'] ?? '');
        $email            = trim($_POST['email'] ?? '');
        $password         = $_POST['password'] ?? '';
        $employeeId       = trim($_POST['employee_id'] ?? '');
        $departmentId     = (int)($_POST['department_id'] ?? 0);
        $baseId           = (int)($_POST['base_id'] ?? 0);
        $fleetId          = (int)($_POST['fleet_id'] ?? 0);
        $status           = $_POST['status'] ?? 'active';
        $employmentStatus = $_POST['employment_status'] ?? null;
        $mobileAccess     = isset($_POST['mobile_access']) ? 1 : 0;
        $webAccess        = isset($_POST['web_access']) ? 1 : 0;
        $roleIds          = $_POST['roles'] ?? [];

        $allowedEmpStatus = ['full_time','part_time','contract','secondment','trainee'];
        if ($employmentStatus && !in_array($employmentStatus, $allowedEmpStatus)) {
            $employmentStatus = null;
        }

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
            'tenant_id'         => $tenantId,
            'name'              => $name,
            'email'             => $email,
            'password'          => $password,
            'employee_id'       => $employeeId ?: null,
            'department_id'     => $departmentId ?: null,
            'base_id'           => $baseId ?: null,
            'fleet_id'          => $fleetId ?: null,
            'employment_status' => $employmentStatus,
            'status'            => $status,
            'mobile_access'     => $mobileAccess,
            'web_access'        => $webAccess,
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
        $tenantId    = currentTenantId();
        $roles       = Database::fetchAll("SELECT MIN(id) as id, name, slug FROM roles WHERE tenant_id = ? GROUP BY slug ORDER BY name", [$tenantId]);
        $departments = Database::fetchAll("SELECT * FROM departments WHERE tenant_id = ? ORDER BY name", [$tenantId]);
        $bases       = Database::fetchAll("SELECT * FROM bases WHERE tenant_id = ? ORDER BY name", [$tenantId]);
        $fleets      = Fleet::allForTenant($tenantId);
        $userRoles   = UserModel::getRoles($id);
        $userRoleIds = array_column($userRoles, 'id');
        $devices     = Device::forUser($id);
        $crewProfile = CrewProfileModel::findByUser($id) ?? [];
        $licenses    = CrewProfileModel::getLicenses($id);
        
        // Capabilities & Overrides
        $allCapabilities = Database::fetchAll(
            "SELECT mc.id, mc.capability, mc.description, m.name as module_name, m.code as module_code
             FROM module_capabilities mc
             JOIN modules m ON m.id = mc.module_id
             JOIN tenant_modules tm ON tm.module_id = m.id
             WHERE tm.tenant_id = ? AND tm.is_enabled = 1
             ORDER BY m.sort_order, mc.capability",
            [$tenantId]
        );
        
        $roleCaps = [];
        if (!empty($userRoles)) {
            $placeholders = implode(',', array_fill(0, count($userRoles), '?'));
            $roleCapsRaw = Database::fetchAll(
                "SELECT module_capability_id 
                 FROM role_capability_templates 
                 WHERE role_slug IN ($placeholders)",
                array_column($userRoles, 'slug')
            );
            $roleCaps = array_column($roleCapsRaw, 'module_capability_id');
        }
        
        $overridesRaw = Database::fetchAll("SELECT module_capability_id, granted FROM user_capability_overrides WHERE user_id = ? AND tenant_id = ?", [$id, $tenantId]);
        $overrides = [];
        foreach ($overridesRaw as $ov) {
            $overrides[$ov['module_capability_id']] = (bool)$ov['granted'];
        }
        
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

        $tenantId         = currentTenantId();
        $name             = trim($_POST['name'] ?? '');
        $email            = trim($_POST['email'] ?? '');
        $password         = $_POST['password'] ?? '';
        $employeeId       = trim($_POST['employee_id'] ?? '');
        $departmentId     = (int)($_POST['department_id'] ?? 0);
        $baseId           = (int)($_POST['base_id'] ?? 0);
        $fleetId          = (int)($_POST['fleet_id'] ?? 0);
        $status           = $_POST['status'] ?? 'active';
        $employmentStatus = $_POST['employment_status'] ?? null;
        $mobileAccess     = isset($_POST['mobile_access']) ? 1 : 0;
        $webAccess        = isset($_POST['web_access']) ? 1 : 0;
        $roleIds          = $_POST['roles'] ?? [];

        $allowedEmpStatus = ['full_time','part_time','contract','secondment','trainee'];
        if ($employmentStatus && !in_array($employmentStatus, $allowedEmpStatus)) {
            $employmentStatus = null;
        }

        if (empty($name) || empty($email)) {
            flash('error', 'Name and email are required.');
            redirect("/users/edit/$id");
        }

        UserModel::update($id, [
            'name'              => $name,
            'email'             => $email,
            'password'          => $password ?: null,
            'employee_id'       => $employeeId ?: null,
            'department_id'     => $departmentId ?: null,
            'base_id'           => $baseId ?: null,
            'fleet_id'          => $fleetId ?: null,
            'employment_status' => $employmentStatus,
            'status'            => $status,
            'mobile_access'     => $mobileAccess,
            'web_access'        => $webAccess,
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

    // ─── Capability Overrides ────────────────────────────

    public function updateCapabilities(int $id): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect("/users/edit/$id#capabilities");
        }
        $user = UserModel::find($id);
        if (!$user || $user['tenant_id'] != currentTenantId()) {
            flash('error', 'User not found.');
            redirect('/users');
        }
        
        $tenantId = currentTenantId();
        
        Database::execute("DELETE FROM user_capability_overrides WHERE user_id = ? AND tenant_id = ?", [$id, $tenantId]);
        
        $overridesPost = $_POST['overrides'] ?? [];
        $currentUser = currentUser();
        
        foreach ($overridesPost as $capId => $val) {
            if ($val === 'grant') {
                Database::execute("INSERT INTO user_capability_overrides (user_id, tenant_id, module_capability_id, granted, set_by) VALUES (?, ?, ?, 1, ?)", [$id, $tenantId, $capId, $currentUser['id']]);
            } elseif ($val === 'revoke') {
                Database::execute("INSERT INTO user_capability_overrides (user_id, tenant_id, module_capability_id, granted, set_by) VALUES (?, ?, ?, 0, ?)", [$id, $tenantId, $capId, $currentUser['id']]);
            }
        }
        
        AuditLog::log('Updated Capabilities', 'user', $id, "Updated capability overrides for {$user['name']}");
        flash('success', 'User capability overrides updated successfully.');
        redirect("/users/edit/$id#capabilities");
    }

    // ─── Crew Profile ────────────────────────────────────

    public function saveProfile(int $id): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect("/users/edit/$id");
        }
        $user = UserModel::find($id);
        if (!$user || $user['tenant_id'] != currentTenantId()) {
            flash('error', 'User not found.');
            redirect('/users');
        }

        CrewProfileModel::save($id, currentTenantId(), $_POST);
        CrewProfileModel::updateCompletion($id);
        AuditLog::log('Updated Crew Profile', 'user', $id, "Updated crew profile for: {$user['name']}");
        flash('success', "Crew profile for \"{$user['name']}\" saved.");
        redirect("/users/edit/$id#crew-profile");
    }

    // ─── Licenses ────────────────────────────────────────

    public function addLicense(int $id): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect("/users/edit/$id");
        }
        $user = UserModel::find($id);
        if (!$user || $user['tenant_id'] != currentTenantId()) {
            flash('error', 'User not found.');
            redirect('/users');
        }

        $licenseType = trim($_POST['license_type'] ?? '');
        if (empty($licenseType)) {
            flash('error', 'Licence type is required.');
            redirect("/users/edit/$id#licenses");
        }

        CrewProfileModel::addLicense($id, currentTenantId(), $_POST);
        CrewProfileModel::updateCompletion($id);
        AuditLog::log('Added Licence', 'user', $id, "Added licence '{$licenseType}' for: {$user['name']}");
        flash('success', "Licence added for \"{$user['name']}\".");
        redirect("/users/edit/$id#licenses");
    }

    public function deleteLicense(int $userId, int $licId): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect("/users/edit/$userId");
        }
        $user = UserModel::find($userId);
        if (!$user || $user['tenant_id'] != currentTenantId()) {
            flash('error', 'User not found.');
            redirect('/users');
        }

        $lic = CrewProfileModel::findLicense($licId);
        CrewProfileModel::deleteLicense($licId, $userId);
        AuditLog::log('Deleted Licence', 'user', $userId, "Deleted licence '{$lic['license_type']}' for: {$user['name']}");
        flash('success', 'Licence removed.');
        redirect("/users/edit/$userId#licenses");
    }

    // ─── Status toggle ────────────────────────────────────

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
