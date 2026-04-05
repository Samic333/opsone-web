<?php
/**
 * API Authentication Middleware
 * Validates bearer token and loads user context
 */
class ApiAuthMiddleware {
    public function handle(): void {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!str_starts_with($header, 'Bearer ')) {
            jsonResponse(['error' => 'Authentication required'], 401);
        }

        $token = substr($header, 7);
        if (empty($token)) {
            jsonResponse(['error' => 'Invalid token'], 401);
        }

        // Look up token
        $tokenRecord = Database::fetch(
            "SELECT t.*, u.name, u.email, u.status, u.tenant_id
             FROM api_tokens t
             JOIN users u ON t.user_id = u.id
             WHERE t.token = ? AND t.expires_at > CURRENT_TIMESTAMP AND t.revoked = 0",
            [$token]
        );

        if (!$tokenRecord) {
            jsonResponse(['error' => 'Token expired or invalid'], 401);
        }

        if ($tokenRecord['status'] !== 'active') {
            jsonResponse(['error' => 'User account is not active'], 403);
        }

        // Store API user context
        $GLOBALS['api_user'] = $tokenRecord;
        $GLOBALS['api_token'] = $token;
        $GLOBALS['api_tenant_id'] = $tokenRecord['tenant_id'];

        // Load user roles
        $roles = Database::fetchAll(
            "SELECT r.slug FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE ur.user_id = ?",
            [$tokenRecord['user_id']]
        );
        $GLOBALS['api_user_roles'] = array_column($roles, 'slug');

        // Update token last used
        Database::execute(
            "UPDATE api_tokens SET last_used_at = CURRENT_TIMESTAMP WHERE token = ?",
            [$token]
        );
    }
}

/**
 * Get current API user
 */
function apiUser(): ?array {
    return $GLOBALS['api_user'] ?? null;
}

/**
 * Get current API tenant ID
 */
function apiTenantId(): ?int {
    if (isSingleTenant()) {
        return getFixedTenantId();
    }
    return $GLOBALS['api_tenant_id'] ?? null;
}

/**
 * Get current API user roles
 */
function apiUserRoles(): array {
    return $GLOBALS['api_user_roles'] ?? [];
}
