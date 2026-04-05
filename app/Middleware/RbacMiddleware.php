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
            http_response_code(403);
            if (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
                jsonResponse(['error' => 'Insufficient permissions'], 403);
            }
            flash('error', 'You do not have permission to access this resource.');
            redirect('/');
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
