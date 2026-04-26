<?php


/**
 * Auth API Controller — mobile login/logout
 */
class AuthApiController {
    public function login(): void {
        $input = json_decode(file_get_contents('php://input'), true);
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';

        if (empty($email) || empty($password)) {
            jsonResponse(['error' => 'Email and password are required'], 400);
        }

        // Find user
        $user = null;
        if (isSingleTenant()) {
            $user = \UserModel::findByEmail($email, getFixedTenantId());
        } else {
            $user = \UserModel::findByEmail($email);
        }

        if (!$user || !password_verify($password, $user['password_hash'])) {
            // Always log failed attempts, even when the email is unknown
            $logUserId   = $user['id']        ?? 0;
            $logTenantId = $user['tenant_id'] ?? (isSingleTenant() ? getFixedTenantId() : 0);
            \AuditLog::logLogin($logUserId, $logTenantId, $email, false, 'api');
            jsonResponse(['error' => 'Invalid credentials'], 401);
        }

        if ($user['status'] !== 'active') {
            jsonResponse(['error' => 'Account is not active'], 403);
        }

        if (!$user['mobile_access']) {
            jsonResponse(['error' => 'Mobile access is not enabled for this account'], 403);
        }

        // Check tenant is active
        $tenant = \Tenant::find($user['tenant_id']);
        if (!$tenant || !$tenant['is_active']) {
            jsonResponse(['error' => 'Airline account is not active'], 403);
        }

        // Prune expired / revoked tokens for this user before issuing new one
        \Database::execute(
            "DELETE FROM api_tokens WHERE user_id = ? AND (expires_at < CURRENT_TIMESTAMP OR revoked = 1)",
            [$user['id']]
        );

        // Generate API token. Persist sha256(token) only — the plaintext token
        // is returned to the client this once and never stored at rest, so a
        // DB read does not yield usable bearer tokens. ApiAuthMiddleware looks
        // up incoming bearers by token_hash. (Migration 043.)
        $token = generateApiToken();
        $tokenHash = hash('sha256', $token);
        $expiryHours = config('api.token_expiry_hours', 168);
        $expiresAt = date('Y-m-d H:i:s', time() + ($expiryHours * 3600));
        // Persist token_hash. The legacy `token` column has a NOT NULL UNIQUE
        // constraint we can't drop without rebuilding the table on SQLite, so
        // we fill it with the hash itself — it's already unique per row, and
        // it leaks nothing the `token_hash` column doesn't already expose.
        // Phase 12 cleanup migration will drop the legacy column outright.
        // Lookups go through token_hash only (ApiAuthMiddleware), so the value
        // stored in `token` is never used for auth.
        \Database::insert(
            "INSERT INTO api_tokens (user_id, tenant_id, token, token_hash, expires_at)
             VALUES (?, ?, ?, ?, ?)",
            [$user['id'], $user['tenant_id'], $tokenHash, $tokenHash, $expiresAt]
        );

        // Get roles
        $roles = \UserModel::getRoleSlugs($user['id']);

        // Log login
        \UserModel::updateLastLogin($user['id']);
        \AuditLog::logLogin($user['id'], $user['tenant_id'], $email, true, 'api');

        jsonResponse([
            'success' => true,
            'token' => $token,
            'expires_in' => $expiryHours * 3600,
            'user' => [
                'id'          => (string) $user['id'],
                'name'        => $user['name'],
                'email'       => $user['email'],
                'employee_id' => $user['employee_id'],
                'roles'       => $roles,
                'tenant_id'   => (string) $user['tenant_id'],
                'tenant_name' => $tenant['name'],
                'tenant_code' => $tenant['code'],
            ],
        ]);
    }

    public function logout(): void {
        $token = $GLOBALS['api_token'] ?? '';
        if ($token) {
            \Database::execute("UPDATE api_tokens SET revoked = 1 WHERE token = ?", [$token]);
        }
        jsonResponse(['success' => true, 'message' => 'Logged out successfully']);
    }
}
