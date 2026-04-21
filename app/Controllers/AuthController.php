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

        // Rate-limit repeated failed attempts per IP+email (5-min window).
        // Keeps the attempt log in a file so it survives server restarts.
        $__rlDir  = BASE_PATH . '/storage/login_throttle';
        if (!is_dir($__rlDir)) @mkdir($__rlDir, 0775, true);
        $__ip     = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $__key    = substr(sha1(strtolower($email) . '|' . $__ip), 0, 24);
        $__rlFile = $__rlDir . '/' . $__key . '.json';
        $__now    = time();
        $__entry  = ['attempts' => 0, 'first' => $__now, 'blocked_until' => 0];
        if (is_file($__rlFile)) {
            $__decoded = json_decode((string) @file_get_contents($__rlFile), true);
            if (is_array($__decoded)) $__entry = array_merge($__entry, $__decoded);
        }
        if (($__entry['blocked_until'] ?? 0) > $__now) {
            $__wait = $__entry['blocked_until'] - $__now;
            flash('error', "Too many failed login attempts. Please try again in " . max(1, (int) ceil($__wait / 60)) . " minute(s).");
            redirect('/login');
        }
        // Attach to current request scope so the failure/success block below can update it.
        $_REQUEST['__rlFile']  = $__rlFile;
        $_REQUEST['__rlEntry'] = $__entry;
        $_REQUEST['__rlNow']   = $__now;

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

            // Rate-limit bookkeeping on failure
            $__rlFile  = $_REQUEST['__rlFile']  ?? null;
            $__rlEntry = $_REQUEST['__rlEntry'] ?? null;
            $__now     = $_REQUEST['__rlNow']   ?? time();
            if ($__rlFile && is_array($__rlEntry)) {
                $__window = 300;                  // 5 minutes rolling
                if (($__now - ($__rlEntry['first'] ?? $__now)) > $__window) {
                    $__rlEntry = ['attempts' => 0, 'first' => $__now, 'blocked_until' => 0];
                }
                $__rlEntry['attempts'] = (int) ($__rlEntry['attempts'] ?? 0) + 1;
                if ($__rlEntry['attempts'] >= 5) {
                    // Exponential-ish lockout: 5→15min, 6→30min, 7+→60min
                    $__extra = min(3600, 900 * (1 + ($__rlEntry['attempts'] - 5)));
                    $__rlEntry['blocked_until'] = $__now + $__extra;
                }
                @file_put_contents($__rlFile, json_encode($__rlEntry));
            }

            flash('error', 'Invalid email or password.');
            redirect('/login');
        }

        // On success, clear the throttle file for this IP+email.
        $__rlFile = $_REQUEST['__rlFile'] ?? null;
        if ($__rlFile && is_file($__rlFile)) @unlink($__rlFile);

        if ($user['status'] !== 'active') {
            flash('error', 'Your account is not active. Contact your administrator.');
            redirect('/login');
        }

        // Check user has web portal access
        if (empty($user['web_access'])) {
            flash('error', 'Your account does not have web portal access. Contact your administrator.');
            redirect('/login');
        }

        // Load roles — used for platform detection and session state
        $roles = UserModel::getRoleSlugs($user['id']);

        // Platform detection:
        //   Primary:   tenant_id = NULL in the users table is the definitive marker
        //              for platform staff. These users have no airline affiliation.
        //   Secondary: a user with tenant_id set but holding ONLY platform roles is
        //              also treated as platform-only (edge case / misconfiguration safety).
        $platformRoleSlugs  = ['super_admin', 'platform_support', 'platform_security', 'system_monitoring'];
        $hasPlatformRole    = !empty(array_intersect($roles, $platformRoleSlugs));
        $hasAirlineRole     = !empty(array_diff($roles, $platformRoleSlugs));
        $isPlatformOnlyUser = ($user['tenant_id'] === null)
                           || ($hasPlatformRole && !$hasAirlineRole);

        if ($isPlatformOnlyUser) {
            // Platform users: no airline tenant required — never call Tenant::find()
            $_SESSION['is_platform_session'] = true;
            $_SESSION['tenant_id']           = null;
            $_SESSION['tenant']              = null;
        } else {
            // Airline users: must belong to an active tenant
            if (empty($user['tenant_id'])) {
                // Airline-scoped user record with no tenant linkage — block login
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
