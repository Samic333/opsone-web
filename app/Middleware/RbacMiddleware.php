<?php
/**
 * RBAC Middleware — role-based access control
 *
 * Phase Zero additions:
 *   - requirePlatformRole()       → platform-layer routes only
 *   - requireAirlineRole()        → airline-layer routes only
 *   - requireModuleCapability()   → module + capability gate
 *   - blockPlatformUsersFromAirlineRoutes() → prevents platform admins
 *                                   from casually using airline UI routes
 */
class RbacMiddleware {

    // ─── Core role check ───────────────────────────────────────────────────────

    /**
     * Abort if the current user does not hold any of the given roles.
     */
    public static function requireRole(string|array $roles): void {
        $roles = is_array($roles) ? $roles : [$roles];

        if (!hasAnyRole($roles)) {
            self::denyAccess($roles);
        }
    }

    // ─── Platform-layer guards ─────────────────────────────────────────────────

    /**
     * Only platform admins may proceed.
     */
    public static function requirePlatformRole(?array $allowedSlugs = null): void {
        $platformRoles = $allowedSlugs ?? AuthorizationService::PLATFORM_ROLES;
        if (!hasAnyRole($platformRoles)) {
            self::denyAccess($platformRoles, 'Platform administrator access required.');
        }
    }

    /**
     * Platform super admin only.
     */
    public static function requirePlatformSuperAdmin(): void {
        self::requirePlatformRole(['super_admin']);
    }

    // ─── Airline-layer guards ──────────────────────────────────────────────────

    /**
     * Only airline/tenant users may proceed (blocks pure platform-only accounts).
     */
    public static function requireAirlineRole(string|array $roles = []): void {
        if (isPlatformOnly()) {
            // Platform users must use the controlled access workflow
            if (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
                jsonResponse(['error' => 'Platform admins must use the controlled access workflow'], 403);
            }
            flash('error', 'That section is airline-scoped. Use controlled access to enter an airline workspace.');
            redirect('/tenants');
        }

        if (!empty($roles)) {
            self::requireRole($roles);
        }
    }

    // ─── Module/capability guard ───────────────────────────────────────────────

    /**
     * Gate access by module code + capability.
     */
    public static function requireModuleCapability(string $moduleCode, string $capability = 'view'): void {
        if (!AuthorizationService::canAccessModule($moduleCode, $capability)) {
            AuditService::log(
                'auth.module_access_denied',
                'module',
                null,
                "$moduleCode.$capability",
                'blocked'
            );
            if (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
                jsonResponse(['error' => "Module '$moduleCode' access denied"], 403);
            }
            flash('error', 'This module is not available for your account.');
            redirect('/dashboard');
        }
    }

    // ─── Tenant scope guard ────────────────────────────────────────────────────

    /**
     * Ensure the current user can act on the given tenant ID.
     */
    public static function requireTenantScope(int $tenantId): void {
        if (!canAccessTenant($tenantId)) {
            AuditService::log(
                'auth.cross_tenant_attempt',
                'tenant',
                $tenantId,
                'Cross-tenant access attempt blocked',
                'blocked'
            );
            if (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
                jsonResponse(['error' => 'Tenant scope violation'], 403);
            }
            flash('error', 'You do not have permission to access that airline\'s data.');
            redirect('/dashboard');
        }
    }

    // ─── API role check ────────────────────────────────────────────────────────

    /**
     * API version of requireRole.
     */
    public static function apiRequireRole(string|array $roles): void {
        $roles    = is_array($roles) ? $roles : [$roles];
        $hasRole  = false;
        foreach ($roles as $role) {
            if (in_array($role, apiUserRoles(), true)) {
                $hasRole = true;
                break;
            }
        }
        if (!$hasRole) {
            jsonResponse(['error' => 'Insufficient permissions'], 403);
        }
    }

    // ─── Internal ─────────────────────────────────────────────────────────────

    private static function denyAccess(array $requiredRoles, string $msg = ''): void {
        // Log the access denial
        try {
            AuditService::logUnauthorizedAccess(
                $_SERVER['REQUEST_URI'] ?? 'unknown',
                $requiredRoles
            );
        } catch (\Throwable) {}

        http_response_code(403);
        if (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
            jsonResponse(['error' => 'Insufficient permissions'], 403);
        }
        flash('error', $msg ?: 'You do not have permission to access this resource.');
        // Route to context-appropriate home to prevent redirect loops
        redirect(isPlatformOnly() ? '/tenants' : '/dashboard');
    }
}
