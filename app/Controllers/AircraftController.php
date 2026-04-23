<?php
/**
 * AircraftController — Phase 6 aircraft registry + maintenance + documents.
 */
class AircraftController {

    private function requireAdmin(): void {
        RbacMiddleware::requireRole([
            'super_admin', 'airline_admin', 'engineering_manager', 'chief_pilot',
            'base_manager'
        ]);
    }

    public function index(): void {
        $this->requireAdmin();
        $tenantId = (int)currentTenantId();
        $aircraft = Aircraft::allForTenant($tenantId);
        $summary  = Aircraft::complianceSummary($tenantId);

        $pageTitle    = 'Aircraft Registry';
        $pageSubtitle = 'Fleet-wide aircraft, maintenance, and document status';

        ob_start();
        require VIEWS_PATH . '/aircraft/index.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function show(int $id): void {
        $this->requireAdmin();
        $aircraft = Aircraft::find($id);
        if (!$aircraft || (int)$aircraft['tenant_id'] !== (int)currentTenantId()) {
            flash('error', 'Aircraft not found.'); redirect('/aircraft');
        }
        $maintenance = Aircraft::maintenanceFor($id);
        $documents   = Aircraft::documentsFor($id);

        $pageTitle    = $aircraft['registration'];
        $pageSubtitle = $aircraft['aircraft_type'] . ($aircraft['variant'] ? ' / ' . $aircraft['variant'] : '');

        ob_start();
        require VIEWS_PATH . '/aircraft/show.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function create(): void {
        $this->requireAdmin();
        $tenantId = (int)currentTenantId();
        $fleets = Database::fetchAll("SELECT id, name FROM fleets WHERE tenant_id = ? ORDER BY name", [$tenantId]);
        $bases  = Database::fetchAll("SELECT id, name FROM bases  WHERE tenant_id = ? ORDER BY name", [$tenantId]);

        $pageTitle    = 'Add Aircraft';
        $pageSubtitle = 'Register a new aircraft';

        ob_start();
        require VIEWS_PATH . '/aircraft/form.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function store(): void {
        $this->requireAdmin();
        if (!verifyCsrf()) { flash('error','Invalid form.'); redirect('/aircraft/create'); }
        $tenantId = (int)currentTenantId();
        $reg = strtoupper(trim($_POST['registration'] ?? ''));
        if ($reg === '' || ($_POST['aircraft_type'] ?? '') === '') {
            flash('error','Registration and aircraft type required.');
            redirect('/aircraft/create');
        }
        $id = Aircraft::create([
            'tenant_id'     => $tenantId,
            'fleet_id'      => (int)($_POST['fleet_id'] ?? 0),
            'registration'  => $reg,
            'aircraft_type' => trim($_POST['aircraft_type']),
            'variant'       => trim($_POST['variant'] ?? ''),
            'manufacturer'  => trim($_POST['manufacturer'] ?? ''),
            'msn'           => trim($_POST['msn'] ?? ''),
            'year_built'    => (int)($_POST['year_built'] ?? 0),
            'home_base_id'  => (int)($_POST['home_base_id'] ?? 0),
            'status'        => $_POST['status'] ?? 'active',
            'total_hours'   => (float)($_POST['total_hours'] ?? 0),
            'total_cycles'  => (int)($_POST['total_cycles'] ?? 0),
            'notes'         => trim($_POST['notes'] ?? ''),
        ]);
        AuditLog::log('aircraft_created', 'aircraft', $id, "Registered $reg");
        flash('success', "Aircraft $reg added.");
        redirect("/aircraft/$id");
    }

    public function addMaintenance(int $id): void {
        $this->requireAdmin();
        if (!verifyCsrf()) { flash('error','Invalid form.'); redirect("/aircraft/$id"); }
        $aircraft = Aircraft::find($id);
        if (!$aircraft || (int)$aircraft['tenant_id'] !== (int)currentTenantId()) {
            flash('error','Aircraft not found.'); redirect('/aircraft');
        }
        Aircraft::addMaintenance([
            'aircraft_id'    => $id,
            'tenant_id'      => (int)$aircraft['tenant_id'],
            'item_type'      => trim($_POST['item_type'] ?? 'inspection'),
            'description'    => trim($_POST['description'] ?? ''),
            'due_date'       => $_POST['due_date'] ?: null,
            'due_hours'      => $_POST['due_hours'] ?: null,
            'interval_days'  => (int)($_POST['interval_days'] ?? 0) ?: null,
            'interval_hours' => $_POST['interval_hours'] ?: null,
        ]);
        AuditLog::log('aircraft_mx_added', 'aircraft', $id, "Added {$_POST['item_type']} due {$_POST['due_date']}");
        flash('success', 'Maintenance item added.');
        redirect("/aircraft/$id");
    }

    public function completeMaintenance(int $mxId): void {
        $this->requireAdmin();
        if (!verifyCsrf()) { flash('error','Invalid form.'); redirect('/aircraft'); }
        $row = Database::fetch("SELECT aircraft_id FROM aircraft_maintenance WHERE id = ?", [$mxId]);
        if (!$row) { flash('error','Item not found.'); redirect('/aircraft'); }
        Aircraft::completeMaintenance($mxId, $_POST['done_date'] ?? null,
            isset($_POST['done_hours']) ? (float)$_POST['done_hours'] : null);
        AuditLog::log('aircraft_mx_completed', 'aircraft_mx', $mxId, 'Completed');
        flash('success', 'Maintenance item marked complete.');
        redirect("/aircraft/{$row['aircraft_id']}");
    }

    public function addDocument(int $id): void {
        $this->requireAdmin();
        if (!verifyCsrf()) { flash('error','Invalid form.'); redirect("/aircraft/$id"); }
        $aircraft = Aircraft::find($id);
        if (!$aircraft || (int)$aircraft['tenant_id'] !== (int)currentTenantId()) {
            flash('error','Aircraft not found.'); redirect('/aircraft');
        }
        Aircraft::addDocument([
            'aircraft_id' => $id,
            'tenant_id'   => (int)$aircraft['tenant_id'],
            'doc_type'    => trim($_POST['doc_type'] ?? 'airworthiness'),
            'doc_number'  => trim($_POST['doc_number'] ?? ''),
            'issued_date' => $_POST['issued_date'] ?: null,
            'expiry_date' => $_POST['expiry_date'] ?: null,
            'notes'       => trim($_POST['notes'] ?? ''),
            'uploaded_by' => (int)currentUser()['id'],
        ]);
        AuditLog::log('aircraft_doc_added', 'aircraft', $id, "Added {$_POST['doc_type']}");
        flash('success', 'Document added.');
        redirect("/aircraft/$id");
    }
}
