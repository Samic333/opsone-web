<?php
/**
 * BaseController — airline base/location management
 *
 * Accessible by: airline_admin, base_manager
 */
class BaseController {

    public function __construct() {
        RbacMiddleware::requireRole(['super_admin', 'airline_admin', 'base_manager']);
    }

    public function index(): void {
        $tenantId = currentTenantId();
        $bases    = Base::allForTenant($tenantId);

        foreach ($bases as &$b) {
            $b['user_count'] = Base::countUsers((int) $b['id']);
        }
        unset($b);

        $pageTitle    = 'Bases';
        $pageSubtitle = 'Manage operating bases and stations';
        $headerAction = '<a href="/bases/create" class="btn btn-primary">+ New Base</a>';

        ob_start();
        require VIEWS_PATH . '/bases/index.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function create(): void {
        $pageTitle    = 'New Base';
        $headerAction = '<a href="/bases" class="btn btn-outline">← Back</a>';

        ob_start();
        require VIEWS_PATH . '/bases/create.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function store(): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/bases/create');
        }

        $tenantId = currentTenantId();
        $name     = trim($_POST['name'] ?? '');
        $code     = strtoupper(trim($_POST['code'] ?? ''));

        if (empty($name) || empty($code)) {
            flash('error', 'Base name and IATA/station code are required.');
            redirect('/bases/create');
        }

        $id = Base::create($tenantId, $name, $code);
        AuditLog::log('Created Base', 'base', $id, "Created base: $name ($code)");
        flash('success', "Base \"$name\" created.");
        redirect('/bases');
    }

    public function edit(int $id): void {
        $tenantId = currentTenantId();
        $base     = Base::find($id);

        if (!$base || (int) $base['tenant_id'] !== $tenantId) {
            flash('error', 'Base not found.');
            redirect('/bases');
        }

        $pageTitle    = 'Edit Base';
        $headerAction = '<a href="/bases" class="btn btn-outline">← Back</a>';

        ob_start();
        require VIEWS_PATH . '/bases/edit.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function update(int $id): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect("/bases/edit/$id");
        }

        $tenantId = currentTenantId();
        $base     = Base::find($id);

        if (!$base || (int) $base['tenant_id'] !== $tenantId) {
            flash('error', 'Base not found.');
            redirect('/bases');
        }

        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));

        if (empty($name) || empty($code)) {
            flash('error', 'Base name and code are required.');
            redirect("/bases/edit/$id");
        }

        Base::update($id, $name, $code);
        AuditLog::log('Updated Base', 'base', $id, "Updated base: $name ($code)");
        flash('success', "Base updated.");
        redirect('/bases');
    }

    public function delete(int $id): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid request.');
            redirect('/bases');
        }

        $tenantId = currentTenantId();
        $base     = Base::find($id);

        if (!$base || (int) $base['tenant_id'] !== $tenantId) {
            flash('error', 'Base not found.');
            redirect('/bases');
        }

        if (Base::countUsers($id) > 0) {
            flash('error', 'Cannot delete a base that has users assigned. Reassign users first.');
            redirect('/bases');
        }

        Base::delete($id);
        AuditLog::log('Deleted Base', 'base', $id, "Deleted base: {$base['name']}");
        flash('success', "Base deleted.");
        redirect('/bases');
    }
}
