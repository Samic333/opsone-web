<?php
/**
 * TenantController — super admin tenant management
 */
class TenantController {
    public function __construct() {
        RbacMiddleware::requireRole('super_admin');
        if (isSingleTenant()) {
            flash('error', 'Tenant management is not available in single-tenant mode.');
            redirect('/dashboard');
        }
    }

    public function index(): void {
        $tenants = Tenant::all();
        // Enrich with stats
        foreach ($tenants as &$t) {
            $stats = Tenant::stats($t['id']);
            $t['user_count'] = $stats['user_count'];
            $t['pending_devices'] = $stats['pending_devices'];
        }
        unset($t); // Break reference to prevent view foreach bug
        require VIEWS_PATH . '/tenants/index.php';
    }

    public function create(): void {
        require VIEWS_PATH . '/tenants/create.php';
    }

    public function store(): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/tenants/create');
        }

        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $email = trim($_POST['contact_email'] ?? '');

        if (empty($name) || empty($code)) {
            flash('error', 'Airline name and code are required.');
            redirect('/tenants/create');
        }

        if (strlen($code) > 10) {
            flash('error', 'Airline code must be 10 characters or less.');
            redirect('/tenants/create');
        }

        $tenantId = Tenant::create([
            'name' => $name,
            'code' => $code,
            'contact_email' => $email ?: null,
        ]);

        // Create default roles for the new tenant
        $systemRoles = Database::fetchAll("SELECT name, slug, description FROM roles WHERE tenant_id IS NULL AND is_system = 1");
        foreach ($systemRoles as $role) {
            Database::insert(
                "INSERT INTO roles (tenant_id, name, slug, description, is_system) VALUES (?, ?, ?, ?, 0)",
                [$tenantId, $role['name'], $role['slug'], $role['description']]
            );
        }

        // Create default departments
        $defaultDepts = ['Flight Operations', 'Cabin Operations', 'Engineering', 'Human Resources', 'Safety', 'Operations', 'IT'];
        foreach ($defaultDepts as $dept) {
            Database::insert("INSERT INTO departments (tenant_id, name) VALUES (?, ?)", [$tenantId, $dept]);
        }

        // Create default file categories
        $defaultCats = [
            ['Manuals', 'manuals'], ['Notices', 'notices'], ['Licenses', 'licenses'],
            ['Training', 'training'], ['Memos', 'memos'], ['Safety Bulletins', 'safety_bulletins'],
            ['General Documents', 'general'],
        ];
        foreach ($defaultCats as [$catName, $catSlug]) {
            Database::insert("INSERT INTO file_categories (tenant_id, name, slug) VALUES (?, ?, ?)", [$tenantId, $catName, $catSlug]);
        }

        AuditLog::log('Created Tenant', 'tenant', $tenantId, "Created airline: $name ($code)");
        flash('success', "Airline \"$name\" created successfully.");
        redirect('/tenants');
    }

    public function edit(int $id): void {
        $tenant = Tenant::find($id);
        if (!$tenant) {
            flash('error', 'Tenant not found.');
            redirect('/tenants');
        }
        require VIEWS_PATH . '/tenants/edit.php';
    }

    public function update(int $id): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect("/tenants/edit/$id");
        }

        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $email = trim($_POST['contact_email'] ?? '');

        if (empty($name) || empty($code)) {
            flash('error', 'Airline name and code are required.');
            redirect("/tenants/edit/$id");
        }

        Tenant::update($id, [
            'name' => $name,
            'code' => $code,
            'contact_email' => $email ?: null,
        ]);

        AuditLog::log('Updated Tenant', 'tenant', $id, "Updated airline: $name ($code)");
        flash('success', "Airline \"$name\" updated successfully.");
        redirect('/tenants');
    }

    public function toggle(int $id): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/tenants');
        }
        $tenant = Tenant::find($id);
        Tenant::toggleActive($id);
        $newStatus = $tenant['is_active'] ? 'deactivated' : 'activated';
        AuditLog::log("Tenant $newStatus", 'tenant', $id, "{$tenant['name']} $newStatus");
        flash('success', "Airline \"{$tenant['name']}\" $newStatus.");
        redirect('/tenants');
    }
}
