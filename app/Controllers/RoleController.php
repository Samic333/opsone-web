<?php
/**
 * RoleController - Airline admin role settings and capability review
 *
 * Airlines can rename their roles and view base capabilities.
 * Actual capability changes are done via per-user overrides.
 */
class RoleController {
    public function __construct() {
        RbacMiddleware::requireRole(['super_admin', 'airline_admin']);
    }

    public function index(): void {
        $tenantId = currentTenantId();
        
        $roles = Database::fetchAll(
            "SELECT * FROM roles WHERE tenant_id = ? ORDER BY role_type, name",
            [$tenantId]
        );
        
        // Group by role_type (tenant vs end_user)
        $grouped = [
            'tenant' => [],
            'end_user' => []
        ];
        
        foreach ($roles as $role) {
            $type = $role['role_type'];
            if (isset($grouped[$type])) {
                $grouped[$type][] = $role;
            }
        }
        
        $pageTitle = 'Role Management';
        $pageSubtitle = 'Rename roles and review base permissions';
        
        ob_start();
        require VIEWS_PATH . '/roles/index.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function show(int $id): void {
        $tenantId = currentTenantId();
        $role = Database::fetch("SELECT * FROM roles WHERE id = ? AND tenant_id = ?", [$id, $tenantId]);
        if (!$role) {
            flash('error', 'Role not found.');
            redirect('/roles');
        }
        
        // Get capabilities for this role slug, grouped by module
        $capabilities = Database::fetchAll(
            "SELECT mc.id, mc.capability, mc.description, m.name as module_name, m.code as module_code
             FROM role_capability_templates rct
             JOIN module_capabilities mc ON mc.id = rct.module_capability_id
             JOIN modules m ON m.id = mc.module_id
             LEFT JOIN tenant_modules tm ON tm.module_id = m.id AND tm.tenant_id = ?
             WHERE rct.role_slug = ? AND tm.is_enabled = 1
             ORDER BY m.sort_order, mc.capability",
            [$tenantId, $role['slug']]
        );
        
        $groupedCaps = [];
        foreach ($capabilities as $cap) {
            $groupedCaps[$cap['module_name']][] = $cap;
        }
        
        $pageTitle = 'Role: ' . $role['name'];
        $pageSubtitle = 'Base permissions dictionary';
        
        ob_start();
        require VIEWS_PATH . '/roles/show.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }
    
    public function updateCapabilities(int $id): void {
        // Airlines do not edit role templates directly. They only edit the role display name/description.
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect("/roles/{$id}");
        }
        
        $tenantId = currentTenantId();
        $role = Database::fetch("SELECT * FROM roles WHERE id = ? AND tenant_id = ?", [$id, $tenantId]);
        if (!$role) {
            flash('error', 'Role not found.');
            redirect('/roles');
        }
        
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($name)) {
            flash('error', 'Role name is required.');
            redirect("/roles/{$id}");
        }
        
        Database::execute(
            "UPDATE roles SET name = ?, description = ? WHERE id = ? AND tenant_id = ?",
            [$name, $description, $id, $tenantId]
        );
        
        AuditLog::log('Updated Role', 'tenant', $tenantId, "Updated role details for: {$role['slug']}");
        flash('success', 'Role updated successfully.');
        redirect("/roles/{$id}");
    }
}
