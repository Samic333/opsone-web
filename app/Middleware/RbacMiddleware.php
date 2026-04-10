<?php
/**
 * RBAC Middleware
 * Role-based access control for routes
 */
class RbacMiddleware {
    /**
     * Check if user has required role(s)
     */
    public static function requireRole(string|array $roles): void {
        $roles = is_array($roles) ? $roles : [$roles];

        if (!hasAnyRole($roles)) {
            // Log the unauthorized access attempt
            try {
                $uri = $_SERVER['REQUEST_URI'] ?? 'unknown';
                AuditLog::log(
                    'Unauthorized Access Attempt',
                    'security',
                    null,
                    "Access denied to {$uri} — required roles: " . implode(', ', $roles)
                );
            } catch (\Throwable $e) {
                // Never block redirect because audit write failed
            }

            http_response_code(403);
            if (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
                jsonResponse(['error' => 'Insufficient permissions'], 403);
            }
            flash('error', 'You do not have permission to access this resource.');
            redirect('/dashboard');
        }
    }

    /**
     * Check if API user has required role(s)
     */
    public static function apiRequireRole(string|array $roles): void {
        $roles = is_array($roles) ? $roles : [$roles];
        $userRoles = apiUserRoles();
        
        $hasRole = false;
        foreach ($roles as $role) {
            if (in_array($role, $userRoles)) {
                $hasRole = true;
                break;
            }
        }
        
        if (!$hasRole) {
            jsonResponse(['error' => 'Insufficient permissions'], 403);
        }
    }
}
