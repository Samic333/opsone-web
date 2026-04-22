<?php
/**
 * ModuleCatalogController — platform module catalog management
 *
 * Platform super admin can view all modules and enable/disable per tenant.
 */
class ModuleCatalogController {
    public function __construct() {
        RbacMiddleware::requirePlatformRole(['super_admin', 'platform_support', 'platform_security']);
    }

    public function index(): void {
        $modules = Module::all();
        foreach ($modules as &$m) {
            $m['capabilities'] = Module::getCapabilities($m['id']);
        }
        unset($m);
        require VIEWS_PATH . '/modules/index.php';
    }

    public function forTenant(int $tenantId): void {
        RbacMiddleware::requirePlatformSuperAdmin();
        $tenant  = Tenant::find($tenantId);
        if (!$tenant) {
            flash('error', 'Airline not found.');
            redirect('/tenants');
        }
        $modules = Module::allWithTenantStatus($tenantId);
        require VIEWS_PATH . '/modules/tenant.php';
    }

    public function toggleForTenant(int $tenantId, int $moduleId): void {
        RbacMiddleware::requirePlatformSuperAdmin();

        $isAjax = self::isAjaxRequest();

        if (!verifyCsrf()) {
            if ($isAjax) jsonResponse(['error' => 'Invalid CSRF token'], 400);
            flash('error', 'Invalid form submission.');
            redirect("/platform/modules/tenant/{$tenantId}");
        }

        $tenant = Tenant::find($tenantId);
        $module = Module::find($moduleId);
        if (!$tenant || !$module) {
            if ($isAjax) jsonResponse(['error' => 'Not found'], 404);
            flash('error', 'Airline or module not found.');
            redirect('/tenants');
        }

        $enabled = TenantModule::toggle($tenantId, $moduleId);
        AuditService::logModuleToggle($tenantId, $tenant['name'], $module['code'], $enabled);

        if ($isAjax) {
            jsonResponse(['success' => true, 'enabled' => $enabled, 'module' => $module['code']]);
        }

        flash('success', sprintf(
            'Module "%s" %s for %s.',
            $module['name'], $enabled ? 'enabled' : 'disabled', $tenant['name']
        ));
        redirect("/platform/modules/tenant/{$tenantId}");
    }

    /**
     * True when the request wants JSON back (XHR or Accept: application/json).
     */
    private static function isAjaxRequest(): bool {
        $xhr = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        if ($xhr === 'xmlhttprequest') return true;
        $accept = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
        return str_contains($accept, 'application/json');
    }
}
