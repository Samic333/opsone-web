<?php
/**
 * AuthController — web login/logout
 */
class AuthController {
    public function showLogin(): void {
        // If already logged in, redirect to dashboard
        if (!empty($_SESSION['user'])) {
            redirect('/dashboard');
        }
        $error = flash('error');
        require VIEWS_PATH . '/auth/login.php';
    }

    public function login(): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission. Please try again.');
            redirect('/login');
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            flash('error', 'Email and password are required.');
            redirect('/login');
        }

        // Find user (search across tenants or within fixed tenant)
        $user = null;
        if (isSingleTenant()) {
            $user = UserModel::findByEmail($email, getFixedTenantId());
        } else {
            $user = UserModel::findByEmail($email);
        }

        if (!$user || !password_verify($password, $user['password_hash'])) {
            // Log failed login — always record, even when email is not found
            $logUserId   = $user['id']        ?? null;
            $logTenantId = $user['tenant_id'] ?? (isSingleTenant() ? getFixedTenantId() : null);
            AuditLog::logLogin($logUserId, $logTenantId, $email, false, 'web');
            flash('error', 'Invalid email or password.');
            redirect('/login');
        }

        if ($user['status'] !== 'active') {
            flash('error', 'Your account is not active. Contact your administrator.');
            redirect('/login');
        }

        // Check user has web portal access
        if (empty($user['web_access'])) {
            flash('error', 'Your account does not have web portal access. Contact your administrator.');
            redirect('/login');
        }

        // Load roles first — needed to determine platform vs airline context
        $roles = UserModel::getRoleSlugs($user['id']);

        // Platform roles: users holding ONLY these roles are platform-only
        $platformRoleSlugs = ['super_admin', 'platform_support', 'platform_security', 'system_monitoring'];
        $hasPlatformRole   = !empty(array_intersect($roles, $platformRoleSlugs));
        $hasAirlineRole    = !empty(array_diff($roles, $platformRoleSlugs));
        $isPlatformOnlyUser = $hasPlatformRole && !$hasAirlineRole;

        if ($isPlatformOnlyUser) {
            // Platform users: no airline tenant required
            $tenant = null;
            $_SESSION['is_platform_session'] = true;
            $_SESSION['tenant_id']           = null;
            $_SESSION['tenant']              = null;
        } else {
            // Airline users: must belong to an active tenant
            // Defensive guard — tenant_id must be a valid int before calling Tenant::find()
            if (empty($user['tenant_id'])) {
                flash('error', 'Your account has no airline association. Contact your administrator.');
                redirect('/login');
            }
            $tenant = Tenant::find((int) $user['tenant_id']);
            if (!$tenant || !$tenant['is_active']) {
                flash('error', 'Your airline account is not active.');
                redirect('/login');
            }
            $_SESSION['is_platform_session'] = false;
            $_SESSION['tenant_id']           = (int) $user['tenant_id'];
            $_SESSION['tenant']              = $tenant;
        }

        // Regenerate session ID after auth to prevent session fixation
        session_regenerate_id(true);

        // Set session
        $_SESSION['user'] = [
            'id'          => $user['id'],
            'name'        => $user['name'],
            'email'       => $user['email'],
            'tenant_id'   => $user['tenant_id'],
            'employee_id' => $user['employee_id'],
        ];
        $_SESSION['user_roles'] = $roles;

        // Log successful login — pass null (not 0) for platform users with no tenant
        UserModel::updateLastLogin($user['id']);
        AuditLog::logLogin($user['id'], $user['tenant_id'] ?? null, $email, true, 'web');
        AuditLog::log('Web Login', 'user', $user['id'], "User logged in from web portal");

        redirect('/dashboard');
    }

    public function logout(): void {
        $user = currentUser();
        if ($user) {
            try {
                AuditLog::log('Web Logout', 'user', $user['id'], "User logged out");
            } catch (\Exception $e) {
                // Ignore logging errors to ensure successful logout redirect
            }
        }
        session_destroy();
        redirect('/login');
    }
}
