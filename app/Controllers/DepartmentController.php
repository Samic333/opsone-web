<?php
/**
 * DepartmentController — airline department management
 *
 * Accessible by: airline_admin, hr
 */
class DepartmentController {

    public function __construct() {
        RbacMiddleware::requireRole(['super_admin', 'airline_admin', 'hr']);
    }

    public function index(): void {
        $tenantId    = currentTenantId();
        $departments = Department::allForTenant($tenantId);

        // Attach user counts
        foreach ($departments as &$d) {
            $d['user_count'] = Department::countUsers((int) $d['id']);
        }
        unset($d);

        $pageTitle    = 'Departments';
        $pageSubtitle = 'Manage airline departments';
        $headerAction = '<a href="/departments/create" class="btn btn-primary">+ New Department</a>';

        ob_start();
        require VIEWS_PATH . '/departments/index.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function create(): void {
        $pageTitle    = 'New Department';
        $headerAction = '<a href="/departments" class="btn btn-outline">← Back</a>';

        ob_start();
        require VIEWS_PATH . '/departments/create.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function store(): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/departments/create');
        }

        $tenantId = currentTenantId();
        $name     = trim($_POST['name'] ?? '');
        $code     = trim($_POST['code'] ?? '');

        if (empty($name)) {
            flash('error', 'Department name is required.');
            redirect('/departments/create');
        }

        $id = Department::create($tenantId, $name, $code ?: null);
        AuditLog::log('Created Department', 'department', $id, "Created department: $name");
        flash('success', "Department \"$name\" created.");
        redirect('/departments');
    }

    public function edit(int $id): void {
        $tenantId   = currentTenantId();
        $department = Department::find($id);

        if (!$department || (int) $department['tenant_id'] !== $tenantId) {
            flash('error', 'Department not found.');
            redirect('/departments');
        }

        $pageTitle    = 'Edit Department';
        $headerAction = '<a href="/departments" class="btn btn-outline">← Back</a>';

        ob_start();
        require VIEWS_PATH . '/departments/edit.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function update(int $id): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect("/departments/edit/$id");
        }

        $tenantId   = currentTenantId();
        $department = Department::find($id);

        if (!$department || (int) $department['tenant_id'] !== $tenantId) {
            flash('error', 'Department not found.');
            redirect('/departments');
        }

        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');

        if (empty($name)) {
            flash('error', 'Department name is required.');
            redirect("/departments/edit/$id");
        }

        Department::update($id, $name, $code ?: null);
        AuditLog::log('Updated Department', 'department', $id, "Updated department: $name");
        flash('success', "Department updated.");
        redirect('/departments');
    }

    public function delete(int $id): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid request.');
            redirect('/departments');
        }

        $tenantId   = currentTenantId();
        $department = Department::find($id);

        if (!$department || (int) $department['tenant_id'] !== $tenantId) {
            flash('error', 'Department not found.');
            redirect('/departments');
        }

        if (Department::countUsers($id) > 0) {
            flash('error', 'Cannot delete a department that has users assigned. Reassign users first.');
            redirect('/departments');
        }

        Department::delete($id);
        AuditLog::log('Deleted Department', 'department', $id, "Deleted department: {$department['name']}");
        flash('success', "Department deleted.");
        redirect('/departments');
    }
}
