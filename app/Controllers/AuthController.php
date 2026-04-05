<?php
/**
 * AuthController — web login/logout
 */
class AuthController {
    public function showLogin(): void {
        // If already logged in, redirect to dashboard
        if (!empty($_SESSION['user'])) {
            redirect('/');
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
            // Log failed login
            if ($user) {
                AuditLog::logLogin($user['id'], $user['tenant_id'], $email, false, 'web');
            }
            flash('error', 'Invalid email or password.');
            redirect('/login');
        }

        if ($user['status'] !== 'active') {
            flash('error', 'Your account is not active. Contact your administrator.');
            redirect('/login');
        }

        // Check tenant is active
        $tenant = Tenant::find($user['tenant_id']);
        if (!$tenant || !$tenant['is_active']) {
            flash('error', 'Your airline account is not active.');
            redirect('/login');
        }

        // Load roles
        $roles = UserModel::getRoleSlugs($user['id']);

        // Check user has web portal access (not mobile-only roles without admin access)
        $webRoles = ['super_admin', 'airline_admin', 'hr', 'scheduler', 'safety_officer', 'document_control', 'chief_pilot', 'fdm_analyst', 'director', 'base_manager'];
        $hasWebAccess = !empty(array_intersect($roles, $webRoles));
        
        if (!$hasWebAccess) {
            flash('error', 'Your role does not have web portal access.');
            redirect('/login');
        }

        // Set session
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'tenant_id' => $user['tenant_id'],
            'employee_id' => $user['employee_id'],
        ];
        $_SESSION['user_roles'] = $roles;
        $_SESSION['tenant_id'] = $user['tenant_id'];
        $_SESSION['tenant'] = $tenant;

        // Log successful login
        UserModel::updateLastLogin($user['id']);
        AuditLog::logLogin($user['id'], $user['tenant_id'], $email, true, 'web');
        AuditLog::log('Web Login', 'user', $user['id'], "User logged in from web portal");

        redirect('/');
    }

    public function logout(): void {
        $user = currentUser();
        if ($user) {
            AuditLog::log('Web Logout', 'user', $user['id'], "User logged out");
        }
        session_destroy();
        redirect('/login');
    }
}
