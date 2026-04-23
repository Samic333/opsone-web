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

        // Legacy seeding produced duplicate (tenant_id, slug) rows; show one row
        // per slug (the lowest-id wins).
        $roles = Database::fetchAll(
            "SELECT r.* FROM roles r
               WHERE r.tenant_id = ?
                 AND r.id = (SELECT MIN(r2.id) FROM roles r2
                              WHERE r2.tenant_id = r.tenant_id AND r2.slug = r.slug)
               ORDER BY r.role_type, r.name",
            [$tenantId]
        );

        // Group by role_type (tenant vs end_user)
        $grouped = ['tenant' => [], 'end_user' => []];
        foreach ($roles as $role) {
            $type = $role['role_type'] ?? 'tenant';
            if (isset($grouped[$type])) $grouped[$type][] = $role;
        }

        $pageTitle = 'Role Management';
        $pageSubtitle = 'Rename roles, review base permissions, and create custom roles';

        ob_start();
        require VIEWS_PATH . '/roles/index.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    /**
     * Create a custom role scoped to the current tenant. Airline super admins
     * can add roles like "Flight Dispatcher" or "Ops Duty Officer" that
     * inherit the permission model but have a distinct display name.
     *
     * The slug is generated from the role name but prefixed with `c_` +
     * tenant_id so it can never collide with system slugs (pilot, hr, etc.).
     */
    public function store(): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/roles');
        }
        $tenantId = (int) currentTenantId();
        $name     = trim($_POST['name'] ?? '');
        $roleType = $_POST['role_type'] ?? 'tenant';
        $desc     = trim($_POST['description'] ?? '');

        if ($name === '' || strlen($name) > 80) {
            flash('error', 'Role name is required (1-80 chars).');
            redirect('/roles');
        }
        if (!in_array($roleType, ['tenant', 'end_user'], true)) {
            flash('error', 'Invalid role type.');
            redirect('/roles');
        }

        // Slug: c_<tenant>_<lower-kebab>  e.g.  c_1_flight_dispatcher
        $slugBase = preg_replace('/[^a-z0-9]+/', '_', strtolower($name));
        $slugBase = trim($slugBase, '_');
        if ($slugBase === '') {
            flash('error', 'Role name must contain letters or digits.');
            redirect('/roles');
        }
        $slug = "c_{$tenantId}_{$slugBase}";

        // Uniqueness inside this tenant
        $existing = Database::fetch(
            "SELECT id FROM roles WHERE tenant_id = ? AND slug = ?",
            [$tenantId, $slug]
        );
        if ($existing) {
            flash('error', 'A role with that name already exists.');
            redirect('/roles');
        }

        Database::insert(
            "INSERT INTO roles (tenant_id, slug, name, description, role_type)
             VALUES (?, ?, ?, ?, ?)",
            [$tenantId, $slug, $name, $desc ?: null, $roleType]
        );
        AuditLog::log('Created Role', 'tenant', $tenantId, "Created custom role {$name} ({$slug})");
        flash('success', "Role \"{$name}\" created. Assign capabilities from the Edit & Permissions screen.");
        redirect('/roles');
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
