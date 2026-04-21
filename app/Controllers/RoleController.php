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

        // All capabilities for every enabled module on this tenant.
        $allCaps = Database::fetchAll(
            "SELECT mc.id, mc.capability, mc.description, m.name AS module_name, m.code AS module_code, m.sort_order
             FROM module_capabilities mc
             JOIN modules m ON m.id = mc.module_id
             LEFT JOIN tenant_modules tm ON tm.module_id = m.id AND tm.tenant_id = ?
             WHERE tm.is_enabled = 1
             ORDER BY m.sort_order, m.name, mc.capability",
            [$tenantId]
        );

        // Template-granted (system defaults) per role_slug.
        $templateRows = Database::fetchAll(
            "SELECT module_capability_id FROM role_capability_templates WHERE role_slug = ?",
            [$role['slug']]
        );
        $templateSet = array_flip(array_map(fn($r) => (int) $r['module_capability_id'], $templateRows));

        // Per-tenant overrides (explicit grants / revokes).
        $overrideRows = Database::fetchAll(
            "SELECT module_capability_id, allowed FROM tenant_role_capabilities WHERE tenant_id = ? AND role_slug = ?",
            [$tenantId, $role['slug']]
        );
        $overrideSet = [];
        foreach ($overrideRows as $r) $overrideSet[(int) $r['module_capability_id']] = (int) $r['allowed'];

        // Build view data: for each cap, effective allowed = override (if set) else template-default.
        $groupedCaps = [];
        foreach ($allCaps as $cap) {
            $capId = (int) $cap['id'];
            $templated = isset($templateSet[$capId]);
            $overridden = array_key_exists($capId, $overrideSet);
            $effective  = $overridden ? (bool) $overrideSet[$capId] : $templated;
            $cap['is_template_default'] = $templated;
            $cap['is_overridden']       = $overridden;
            $cap['effective_allowed']   = $effective;
            $groupedCaps[$cap['module_name']][] = $cap;
        }

        $pageTitle = 'Role: ' . $role['name'];
        $pageSubtitle = 'Edit role display name and per-airline capability overrides';

        ob_start();
        require VIEWS_PATH . '/roles/show.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function updateCapabilities(int $id): void {
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

        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($name === '') {
            flash('error', 'Role name is required.');
            redirect("/roles/{$id}");
        }

        Database::execute(
            "UPDATE roles SET name = ?, description = ? WHERE id = ? AND tenant_id = ?",
            [$name, $description, $id, $tenantId]
        );

        // Persist capability overrides, if the form posted them.
        // Posted fields:
        //   caps[<capId>] = 1        — the box is checked (granted)
        //   caps_all[]    = <capId>  — every capability the form rendered (so unchecked boxes come through)
        if (isset($_POST['caps_all']) && is_array($_POST['caps_all'])) {
            $postedCheckboxes = array_map('intval', $_POST['caps_all']);
            $postedGranted    = array_map('intval', array_keys($_POST['caps'] ?? []));
            $grantedSet       = array_flip($postedGranted);

            // Template defaults (to decide what is an override vs redundant).
            $templateRows = Database::fetchAll(
                "SELECT module_capability_id FROM role_capability_templates WHERE role_slug = ?",
                [$role['slug']]
            );
            $templateSet = array_flip(array_map(fn($r) => (int) $r['module_capability_id'], $templateRows));

            // Wipe prior overrides for this role and re-insert the delta.
            Database::execute(
                "DELETE FROM tenant_role_capabilities WHERE tenant_id = ? AND role_slug = ?",
                [$tenantId, $role['slug']]
            );
            $actor = currentUser();
            $actorId = $actor['id'] ?? null;
            foreach ($postedCheckboxes as $capId) {
                $wantAllowed = isset($grantedSet[$capId]) ? 1 : 0;
                $isTemplate  = isset($templateSet[$capId]) ? 1 : 0;
                // Only write a row when the user's choice diverges from the template default.
                if ($wantAllowed !== $isTemplate) {
                    Database::insert(
                        "INSERT INTO tenant_role_capabilities (tenant_id, role_slug, module_capability_id, allowed, updated_by)
                         VALUES (?, ?, ?, ?, ?)",
                        [$tenantId, $role['slug'], $capId, $wantAllowed, $actorId]
                    );
                }
            }
            AuditLog::log('Updated Role Capabilities', 'role', $id,
                "Tenant #{$tenantId} capability overrides updated for {$role['slug']}");
        }

        AuditLog::log('Updated Role', 'tenant', $tenantId, "Updated role details for: {$role['slug']}");
        flash('success', 'Role updated successfully.');
        redirect("/roles/{$id}");
    }
}
