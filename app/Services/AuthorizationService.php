<?php
/**
 * AuthorizationService — platform/tenant/capability authorization
 *
 * Evaluates the full authorization chain:
 *   1. Is the user a platform-level admin?
 *   2. Is this a tenant-scoped action and is the user in the right tenant?
 *   3. Is the module enabled for the tenant?
 *   4. Does the user have the required capability?
 *   5. Are there user-level overrides to consider?
 */
class AuthorizationService {

    // ─── Platform role slugs ───────────────────────────────────────────────────

    public const PLATFORM_ROLES = [
        'super_admin',
        'platform_support',
        'platform_security',
        'system_monitoring',
    ];

    // ─── Platform checks ───────────────────────────────────────────────────────

    /**
     * True if the current session user holds any platform-level role.
     */
    public static function isPlatformUser(): bool {
        return hasAnyRole(self::PLATFORM_ROLES);
    }

    /**
     * True if the user is ONLY a platform user (not also an airline user).
     */
    public static function isPlatformOnly(): bool {
        return self::isPlatformUser() && !self::isAirlineUser();
    }

    /**
     * True if the user holds at least one non-platform role.
     */
    public static function isAirlineUser(): bool {
        $roles = $_SESSION['user_roles'] ?? [];
        foreach ($roles as $slug) {
            if (!in_array($slug, self::PLATFORM_ROLES, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * True if the current user is platform super admin.
     */
    public static function isPlatformSuperAdmin(): bool {
        return hasRole('super_admin');
    }

    /**
     * True if the current user is a platform security admin.
     */
    public static function isPlatformSecurityAdmin(): bool {
        return hasRole('platform_security');
    }

    /**
     * True if the current user is a platform support admin.
     */
    public static function isPlatformSupportAdmin(): bool {
        return hasRole('platform_support');
    }

    // ─── Tenant scope checks ───────────────────────────────────────────────────

    /**
     * Verify the current user can act on the given tenant.
     * Platform admins can act on any tenant.
     * Airline users can only act on their own tenant.
     */
    public static function canAccessTenant(int $tenantId): bool {
        if (self::isPlatformUser()) return true;
        return (int)(currentTenantId()) === $tenantId;
    }

    /**
     * Abort with 403 if the user cannot access the given tenant.
     */
    public static function requireTenantAccess(int $tenantId): void {
        if (!self::canAccessTenant($tenantId)) {
            AuditService::log('auth.cross_tenant_attempt', 'tenant', $tenantId, null, 'blocked');
            if (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
                jsonResponse(['error' => 'Access denied: tenant scope violation'], 403);
            }
            flash('error', 'You do not have permission to access that airline.');
            redirect('/dashboard');
        }
    }

    // ─── Module checks ─────────────────────────────────────────────────────────

    /**
     * Check if a module is enabled for a given tenant.
     */
    public static function isModuleEnabledForTenant(string $moduleCode, int $tenantId): bool {
        $row = Database::fetch(
            "SELECT tm.is_enabled
             FROM tenant_modules tm
             JOIN modules m ON m.id = tm.module_id
             WHERE m.code = ? AND tm.tenant_id = ?",
            [$moduleCode, $tenantId]
        );
        return $row && (bool)$row['is_enabled'];
    }

    /**
     * Check if the current user can access a specific module (enabled + capability).
     *
     * @param string $moduleCode    e.g. 'rostering'
     * @param string $capability    e.g. 'view', 'edit', 'publish'
     * @param int|null $tenantId    defaults to current tenant
     */
    public static function canAccessModule(string $moduleCode, string $capability = 'view', ?int $tenantId = null): bool {
        $tenantId = $tenantId ?? (int)currentTenantId();

        // Platform admins bypass module checks
        if (self::isPlatformUser()) return true;

        // Check module is enabled for tenant
        if (!self::isModuleEnabledForTenant($moduleCode, $tenantId)) {
            return false;
        }

        // Check via role capability template or user override
        return self::hasCapability($moduleCode, $capability, $tenantId);
    }

    /**
     * Abort if the user cannot access the given module+capability.
     */
    public static function requireModuleAccess(string $moduleCode, string $capability = 'view'): void {
        if (!self::canAccessModule($moduleCode, $capability)) {
            AuditService::log(
                'auth.module_access_denied',
                'module',
                null,
                "$moduleCode.$capability",
                'blocked'
            );
            if (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
                jsonResponse(['error' => "Module '$moduleCode' is not accessible"], 403);
            }
            flash('error', 'This module is not enabled or you do not have the required permission.');
            redirect('/dashboard');
        }
    }

    // ─── Capability checks ─────────────────────────────────────────────────────

    /**
     * Does the current user have a capability for a module?
     * Checks: user override first, then role templates, then broad role-based fallback.
     */
    public static function hasCapability(string $moduleCode, string $capability, int $tenantId): bool {
        $user = currentUser();
        if (!$user) return false;

        $userId = (int)$user['id'];

        // 1. Check per-user override (can grant OR revoke)
        $override = Database::fetch(
            "SELECT uco.granted
             FROM user_capability_overrides uco
             JOIN module_capabilities mc ON mc.id = uco.module_capability_id
             JOIN modules m ON m.id = mc.module_id
             WHERE uco.user_id = ? AND uco.tenant_id = ? AND m.code = ? AND mc.capability = ?",
            [$userId, $tenantId, $moduleCode, $capability]
        );
        if ($override !== null) {
            return (bool)$override['granted'];
        }

        // 2. Check role capability templates
        $roles = $_SESSION['user_roles'] ?? [];
        foreach ($roles as $roleSlug) {
            $match = Database::fetch(
                "SELECT 1
                 FROM role_capability_templates rct
                 JOIN module_capabilities mc ON mc.id = rct.module_capability_id
                 JOIN modules m ON m.id = mc.module_id
                 WHERE rct.role_slug = ? AND m.code = ? AND mc.capability = ?",
                [$roleSlug, $moduleCode, $capability]
            );
            if ($match) return true;
        }

        // 3. Fallback: broad admin check for airline admins
        if (hasAnyRole(['airline_admin', 'super_admin']) && $capability === 'view') {
            return true;
        }

        return false;
    }

    // ─── Navigation visibility helpers ────────────────────────────────────────

    /**
     * Returns the navigation context for the current user.
     * 'platform' → show platform sidebar only
     * 'airline'  → show airline/tenant sidebar
     */
    public static function navContext(): string {
        return self::isPlatformOnly() ? 'platform' : 'airline';
    }

    /**
     * Can the current user see the given sidebar section?
     * Sections: platform, people, scheduling, content, safety, security
     */
    public static function canSeeSidebarSection(string $section): bool {
        if (self::isPlatformOnly()) {
            // Platform users see ONLY platform navigation
            return in_array($section, ['platform', 'platform_security', 'platform_support'], true);
        }

        return match($section) {
            'people'      => hasAnyRole(['airline_admin', 'hr', 'training_admin', 'super_admin']),
            'devices'     => hasAnyRole(['airline_admin', 'hr', 'chief_pilot', 'head_cabin_crew',
                                         'engineering_manager', 'base_manager', 'safety_officer']),
            'scheduling'  => hasAnyRole(['airline_admin', 'scheduler', 'chief_pilot', 'head_cabin_crew',
                                         'base_manager', 'pilot', 'cabin_crew', 'engineer']),
            'content'     => hasAnyRole(['airline_admin', 'hr', 'document_control', 'safety_officer',
                                         'chief_pilot', 'head_cabin_crew', 'engineering_manager',
                                         'base_manager', 'training_admin', 'fdm_analyst',
                                         'pilot', 'cabin_crew', 'engineer']),
            'safety'      => hasAnyRole(['airline_admin', 'safety_officer', 'fdm_analyst',
                                         'chief_pilot', 'hr']),
            'security'    => hasAnyRole(['airline_admin', 'safety_officer']),
            'operations'  => hasAnyRole(['pilot', 'cabin_crew', 'engineer']) &&
                             !hasAnyRole(['airline_admin', 'hr', 'chief_pilot', 'head_cabin_crew',
                                          'engineering_manager', 'safety_officer', 'document_control',
                                          'fdm_analyst', 'base_manager', 'scheduler']),
            default       => false,
        };
    }
}
