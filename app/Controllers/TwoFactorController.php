<?php
/**
 * TwoFactorController — TOTP-based 2FA enrolment + login challenge.
 *
 * Flow:
 *   1. User submits correct email+password to /login.
 *   2. AuthController checks user_2fa.enabled_at. If enrolled, it sets
 *      $_SESSION['pending_2fa_user_id'] = <id> and redirects to /2fa/challenge.
 *   3. /2fa/challenge asks for the 6-digit code. On verify, we promote the
 *      pending session into a full authenticated session.
 *
 * Enrolment:
 *   /2fa/setup (authenticated) shows a provisioning URL + otpauth link
 *   and asks the user to confirm by entering the current code. Only after
 *   confirmation does enabled_at become non-null — so a user who mis-enters
 *   during enrolment doesn't lock themselves out.
 */
class TwoFactorController {

    // ─── Challenge (post-password) ─────────────────────────────

    public function showChallenge(): void {
        if (empty($_SESSION['pending_2fa_user_id'])) {
            redirect('/login');
        }
        $error = flash('error');
        require VIEWS_PATH . '/auth/2fa_challenge.php';
    }

    public function submitChallenge(): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/2fa/challenge');
        }
        $pendingId = (int) ($_SESSION['pending_2fa_user_id'] ?? 0);
        if ($pendingId <= 0) redirect('/login');

        $code = preg_replace('/\s+/', '', (string)($_POST['code'] ?? ''));
        $row  = Database::fetch("SELECT * FROM user_2fa WHERE user_id = ? AND enabled_at IS NOT NULL", [$pendingId]);
        if (!$row) {
            // Safety net — if no enrolment row, clear pending and send back to login.
            unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_remember']);
            redirect('/login');
        }

        $isBackup = false;
        $ok = TotpService::verify($row['secret'], $code);
        if (!$ok) {
            // Try a backup code (one-time use).
            $codes = json_decode($row['backup_codes'] ?? '[]', true) ?: [];
            $hash  = hash('sha256', strtoupper($code));
            if (in_array($hash, $codes, true)) {
                $codes = array_values(array_diff($codes, [$hash]));
                Database::execute(
                    "UPDATE user_2fa SET backup_codes = ?, last_used_at = " . dbNow() . " WHERE user_id = ?",
                    [json_encode($codes), $pendingId]
                );
                $ok = true;
                $isBackup = true;
            }
        }

        if (!$ok) {
            AuditLog::log('2fa_failed', 'user', $pendingId, 'Invalid 2FA code');
            flash('error', 'Invalid authentication code. Try again.');
            redirect('/2fa/challenge');
        }

        if (!$isBackup) {
            Database::execute("UPDATE user_2fa SET last_used_at = " . dbNow() . " WHERE user_id = ?", [$pendingId]);
        }

        // Promote session — replay the same bookkeeping AuthController did
        // before it redirected here.
        self::promotePending((int)$pendingId);
        AuditLog::log('2fa_success', 'user', $pendingId, $isBackup ? 'Logged in with backup code' : 'Logged in with TOTP');
        redirect('/dashboard');
    }

    /**
     * Re-creates the full session from the pending user id left by AuthController.
     * Kept small and explicit; mirrors the block in AuthController::login after
     * `password_verify()` succeeds.
     */
    public static function promotePending(int $userId): void {
        $user = UserModel::find($userId);
        if (!$user) {
            unset($_SESSION['pending_2fa_user_id']);
            redirect('/login');
        }
        $roles = UserModel::getRoleSlugs($userId);
        $platformRoleSlugs = ['super_admin','platform_support','platform_security','system_monitoring'];
        $hasPlatformRole   = !empty(array_intersect($roles, $platformRoleSlugs));
        $hasAirlineRole    = !empty(array_diff($roles, $platformRoleSlugs));
        $isPlatformOnly    = ($user['tenant_id'] === null) || ($hasPlatformRole && !$hasAirlineRole);

        session_regenerate_id(true);
        if ($isPlatformOnly) {
            $_SESSION['is_platform_session'] = true;
            $_SESSION['tenant_id'] = null;
            $_SESSION['tenant']    = null;
        } else {
            $tenant = Tenant::find((int) $user['tenant_id']);
            $_SESSION['is_platform_session'] = false;
            $_SESSION['tenant_id'] = (int) $user['tenant_id'];
            $_SESSION['tenant']    = $tenant;
        }
        $_SESSION['user'] = [
            'id'          => $user['id'],
            'name'        => $user['name'],
            'email'       => $user['email'],
            'tenant_id'   => $user['tenant_id'],
            'employee_id' => $user['employee_id'],
        ];
        $_SESSION['user_roles'] = $roles;
        UserModel::updateLastLogin($user['id']);
        AuditLog::logLogin($user['id'], $user['tenant_id'] ?? null, $user['email'], true, 'web');
        unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_remember']);
    }

    // ─── Enrolment (authenticated) ──────────────────────────────

    public function showSetup(): void {
        if (empty($_SESSION['user'])) redirect('/login');
        $userId = (int) $_SESSION['user']['id'];
        $email  = (string) $_SESSION['user']['email'];

        // If migration 035 hasn't been applied yet, render a friendly placeholder
        // instead of a 500 — points the operator at the deploy step they missed.
        try {
            $row = Database::fetch("SELECT * FROM user_2fa WHERE user_id = ?", [$userId]);
        } catch (\Throwable $e) {
            error_log('[OpsOne 2FA showSetup skipped] ' . $e->getMessage());
            $pageTitle    = 'Two-Factor Authentication';
            $pageSubtitle = 'Module not yet enabled — database migration pending';
            $isEnabled = false;
            $secret = '';
            $provisioningUri = '';
            $migrationPending = true;
            $error    = flash('error');
            $success  = flash('success');
            $justGeneratedBackup = null;
            ob_start();
            require VIEWS_PATH . '/auth/2fa_setup.php';
            $content = ob_get_clean();
            require VIEWS_PATH . '/layouts/app.php';
            return;
        }
        $isEnabled = $row && !empty($row['enabled_at']);

        // Generate secret if none — stored immediately but enabled_at is null until confirmed.
        if (!$row) {
            $secret = TotpService::generateSecret();
            Database::insert("INSERT INTO user_2fa (user_id, secret) VALUES (?, ?)", [$userId, $secret]);
            $row = Database::fetch("SELECT * FROM user_2fa WHERE user_id = ?", [$userId]);
        } elseif (empty($row['enabled_at'])) {
            $secret = $row['secret'];
        } else {
            $secret = $row['secret'];
        }
        $migrationPending = false;

        $brand = file_exists(CONFIG_PATH . '/branding.php')
            ? require CONFIG_PATH . '/branding.php'
            : ['product_name' => 'OpsOne'];
        $issuer = $brand['product_name'] ?? 'OpsOne';
        $provisioningUri = TotpService::uri($issuer, $email, $secret);
        $error    = flash('error');
        $success  = flash('success');
        // Grab backup codes (if any) to display just once after generation.
        $justGeneratedBackup = $_SESSION['_fresh_backup_codes'] ?? null;
        unset($_SESSION['_fresh_backup_codes']);

        $pageTitle    = 'Two-Factor Authentication';
        $pageSubtitle = 'Secure your account with an authenticator app';

        ob_start();
        require VIEWS_PATH . '/auth/2fa_setup.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function submitSetup(): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/2fa/setup');
        }
        if (empty($_SESSION['user'])) redirect('/login');
        $userId = (int) $_SESSION['user']['id'];
        $code   = preg_replace('/\s+/', '', (string)($_POST['code'] ?? ''));

        $row = Database::fetch("SELECT * FROM user_2fa WHERE user_id = ?", [$userId]);
        if (!$row) {
            flash('error', 'No enrolment in progress. Please restart.');
            redirect('/2fa/setup');
        }

        if (!TotpService::verify($row['secret'], $code)) {
            AuditLog::log('2fa_enroll_failed', 'user', $userId, 'Wrong confirmation code during enrolment');
            flash('error', 'Code did not match. Make sure your phone clock is correct and try again.');
            redirect('/2fa/setup');
        }

        // Generate 8 one-time backup codes (each shown once, stored hashed).
        $backupRaw    = [];
        $backupHashes = [];
        for ($i = 0; $i < 8; $i++) {
            $code = strtoupper(bin2hex(random_bytes(4))); // 8 hex chars = 32 bits
            $backupRaw[]    = $code;
            $backupHashes[] = hash('sha256', $code);
        }

        Database::execute(
            "UPDATE user_2fa SET enabled_at = " . dbNow() . ", backup_codes = ? WHERE user_id = ?",
            [json_encode($backupHashes), $userId]
        );
        $_SESSION['_fresh_backup_codes'] = $backupRaw;

        AuditLog::log('2fa_enabled', 'user', $userId, 'User enabled TOTP 2FA');
        flash('success', 'Two-factor authentication is now active. Save your backup codes — they are shown once.');
        redirect('/2fa/setup');
    }

    public function disable(): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/2fa/setup');
        }
        if (empty($_SESSION['user'])) redirect('/login');
        $userId = (int) $_SESSION['user']['id'];
        // Require the user to enter a current valid code before disabling, to avoid
        // a stolen session turning off 2FA.
        $row = Database::fetch("SELECT * FROM user_2fa WHERE user_id = ?", [$userId]);
        if (!$row || empty($row['enabled_at'])) {
            redirect('/2fa/setup');
        }
        $code = preg_replace('/\s+/', '', (string)($_POST['code'] ?? ''));
        if (!TotpService::verify($row['secret'], $code)) {
            flash('error', 'Enter a valid current code to disable 2FA.');
            redirect('/2fa/setup');
        }
        Database::execute("DELETE FROM user_2fa WHERE user_id = ?", [$userId]);
        AuditLog::log('2fa_disabled', 'user', $userId, 'User disabled TOTP 2FA');
        flash('success', 'Two-factor authentication has been disabled.');
        redirect('/2fa/setup');
    }
}
