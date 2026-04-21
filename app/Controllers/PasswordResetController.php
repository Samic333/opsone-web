<?php
/**
 * PasswordResetController — self-service forgot-password + reset flow.
 *
 * Public routes (no auth required):
 *   GET  /forgot-password         — request-token form
 *   POST /forgot-password         — issue token, show success + (dev-only) link
 *   GET  /reset-password          — reset form (requires ?token=...)
 *   POST /reset-password          — set new password
 *
 * Email delivery is not yet wired. For now the token is recorded in the
 * audit log as a `password_reset_issued` event so a platform admin can
 * retrieve the reset link for a stuck user. In local/dev the link is
 * shown on the success page to speed up manual testing.
 */
class PasswordResetController {

    public function showRequest(): void {
        $error = flash('error');
        $success = flash('success');
        require VIEWS_PATH . '/auth/forgot_password.php';
    }

    public function submitRequest(): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission. Please try again.');
            redirect('/forgot-password');
        }
        $email = trim($_POST['email'] ?? '');

        // Per-IP rate limit: at most 5 requests per 10 minutes.
        $throttleDir = BASE_PATH . '/storage/password_reset_throttle';
        if (!is_dir($throttleDir)) @mkdir($throttleDir, 0775, true);
        $ipKey = substr(sha1($_SERVER['REMOTE_ADDR'] ?? ''), 0, 16);
        $file  = "$throttleDir/$ipKey.json";
        $now   = time();
        $state = ['count' => 0, 'window_start' => $now];
        if (is_file($file)) {
            $decoded = json_decode((string) @file_get_contents($file), true);
            if (is_array($decoded)) $state = array_merge($state, $decoded);
        }
        if (($now - (int)$state['window_start']) > 600) {
            $state = ['count' => 0, 'window_start' => $now];
        }
        if ((int)$state['count'] >= 5) {
            flash('success', 'If the email is on file, a reset link has been issued.');
            redirect('/forgot-password');
        }
        $state['count'] = (int)$state['count'] + 1;
        @file_put_contents($file, json_encode($state));

        $result = PasswordResetService::issue($email);
        if (!empty($result['user_id'])) {
            $tokenForLog = $result['token_for_log'];
            $resetUrl = rtrim(env('APP_URL', 'http://localhost:8080'), '/')
                      . '/reset-password?token=' . $tokenForLog;
            // Persist a trail so a platform admin can find the token if email isn't wired yet.
            AuditLog::log(
                'password_reset_issued',
                'user',
                (int)$result['user_id'],
                "Reset link generated for {$email}. Expires {$result['expires_at']}. URL: {$resetUrl}"
            );
        }

        // Always show the same success, regardless of whether the email exists,
        // to avoid enumeration. In dev we additionally surface the link for testing.
        $devMode = in_array(env('APP_ENV', 'production'), ['development','local','dev'], true)
                && env('APP_DEBUG', 'false') === 'true';
        if ($devMode && !empty($result['user_id'])) {
            flash('success', "If the email is on file, a reset link has been issued. (Dev-only) token: {$result['token_for_log']}");
        } else {
            flash('success', 'If the email is on file, a reset link has been issued. Please check with your administrator if it has not arrived within a few minutes.');
        }
        redirect('/forgot-password');
    }

    public function showReset(): void {
        $token = trim($_GET['token'] ?? '');
        if ($token === '') {
            flash('error', 'Reset link is missing the token.');
            redirect('/login');
        }
        // Validate without consuming, just to show a sensible form vs. an error state.
        $hash = hash('sha256', $token);
        $row  = Database::fetch(
            "SELECT id, expires_at, used_at FROM password_reset_tokens WHERE token_hash = ? LIMIT 1",
            [$hash]
        );
        $valid = $row && !$row['used_at'] && strtotime($row['expires_at']) >= time();
        $error = flash('error');
        require VIEWS_PATH . '/auth/reset_password.php';
    }

    public function submitReset(): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission. Please try again.');
            redirect('/login');
        }
        $token = trim($_POST['token']     ?? '');
        $pw    = (string)($_POST['password'] ?? '');
        $pw2   = (string)($_POST['password_confirm'] ?? '');

        if ($pw !== $pw2) {
            flash('error', 'Passwords did not match.');
            redirect('/reset-password?token=' . urlencode($token));
        }
        $bad = PasswordResetService::validatePassword($pw);
        if ($bad) {
            flash('error', $bad);
            redirect('/reset-password?token=' . urlencode($token));
        }

        $user = PasswordResetService::consume($token);
        if (!$user) {
            flash('error', 'This reset link has expired or is invalid. Please request a new one.');
            redirect('/forgot-password');
        }

        PasswordResetService::setPassword((int)$user['id'], $pw);
        AuditLog::log('password_reset_completed', 'user', (int)$user['id'], "Password reset via self-service for {$user['email']}");

        flash('success', 'Password has been updated. Please sign in.');
        redirect('/login');
    }
}
