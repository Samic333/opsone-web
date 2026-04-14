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
        if (!verifyCsrf()) {
            jsonResponse(['error' => 'Invalid CSRF token'], 400);
        }

        $tenant = Tenant::find($tenantId);
        $module = Module::find($moduleId);
        if (!$tenant || !$module) {
            jsonResponse(['error' => 'Not found'], 404);
        }

        $enabled = TenantModule::toggle($tenantId, $moduleId);
        AuditService::logModuleToggle($tenantId, $tenant['name'], $module['code'], $enabled);

        jsonResponse(['success' => true, 'enabled' => $enabled, 'module' => $module['code']]);
    }
}
