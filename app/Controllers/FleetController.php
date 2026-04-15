<?php
/**
 * FleetController — airline fleet management
 *
 * Accessible by: airline_admin, chief_pilot, engineering_manager
 */
class FleetController {

    public function __construct() {
        RbacMiddleware::requireRole(['super_admin', 'airline_admin', 'chief_pilot', 'engineering_manager']);
    }

    public function index(): void {
        $tenantId = currentTenantId();
        $fleets   = Fleet::allForTenant($tenantId);

        foreach ($fleets as &$f) {
            $f['user_count'] = Fleet::countUsers((int) $f['id']);
        }
        unset($f);

        $pageTitle    = 'Fleets';
        $pageSubtitle = 'Manage aircraft fleets and types';
        $headerAction = '<a href="/fleets/create" class="btn btn-primary">+ New Fleet</a>';

        ob_start();
        require VIEWS_PATH . '/fleets/index.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function create(): void {
        $pageTitle    = 'New Fleet';
        $headerAction = '<a href="/fleets" class="btn btn-outline">← Back</a>';

        ob_start();
        require VIEWS_PATH . '/fleets/create.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function store(): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/fleets/create');
        }

        $tenantId    = currentTenantId();
        $name        = trim($_POST['name'] ?? '');
        $code        = strtoupper(trim($_POST['code'] ?? ''));
        $aircraftType = trim($_POST['aircraft_type'] ?? '');

        if (empty($name)) {
            flash('error', 'Fleet name is required.');
            redirect('/fleets/create');
        }

        $id = Fleet::create($tenantId, $name, $code ?: null, $aircraftType ?: null);
        AuditLog::log('Created Fleet', 'fleet', $id, "Created fleet: $name");
        flash('success', "Fleet \"$name\" created.");
        redirect('/fleets');
    }

    public function edit(int $id): void {
        $tenantId = currentTenantId();
        $fleet    = Fleet::find($id);

        if (!$fleet || (int) $fleet['tenant_id'] !== $tenantId) {
            flash('error', 'Fleet not found.');
            redirect('/fleets');
        }

        $pageTitle    = 'Edit Fleet';
        $headerAction = '<a href="/fleets" class="btn btn-outline">← Back</a>';

        ob_start();
        require VIEWS_PATH . '/fleets/edit.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function update(int $id): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect("/fleets/edit/$id");
        }

        $tenantId = currentTenantId();
        $fleet    = Fleet::find($id);

        if (!$fleet || (int) $fleet['tenant_id'] !== $tenantId) {
            flash('error', 'Fleet not found.');
            redirect('/fleets');
        }

        $name        = trim($_POST['name'] ?? '');
        $code        = strtoupper(trim($_POST['code'] ?? ''));
        $aircraftType = trim($_POST['aircraft_type'] ?? '');

        if (empty($name)) {
            flash('error', 'Fleet name is required.');
            redirect("/fleets/edit/$id");
        }

        Fleet::update($id, $name, $code ?: null, $aircraftType ?: null);
        AuditLog::log('Updated Fleet', 'fleet', $id, "Updated fleet: $name");
        flash('success', "Fleet updated.");
        redirect('/fleets');
    }

    public function delete(int $id): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid request.');
            redirect('/fleets');
        }

        $tenantId = currentTenantId();
        $fleet    = Fleet::find($id);

        if (!$fleet || (int) $fleet['tenant_id'] !== $tenantId) {
            flash('error', 'Fleet not found.');
            redirect('/fleets');
        }

        if (Fleet::countUsers($id) > 0) {
            flash('error', 'Cannot delete a fleet that has users assigned. Reassign users first.');
            redirect('/fleets');
        }

        Fleet::delete($id);
        AuditLog::log('Deleted Fleet', 'fleet', $id, "Deleted fleet: {$fleet['name']}");
        flash('success', "Fleet deleted.");
        redirect('/fleets');
    }
}
