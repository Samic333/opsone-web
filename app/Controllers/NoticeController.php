<?php
/**
 * NoticeController — CRUD for airline notices/bulletins
 *
 * GET  /notices                     → index (admin list)
 * GET  /notices/create              → create form
 * POST /notices/store               → store
 * GET  /notices/edit/{id}           → edit form
 * POST /notices/update/{id}         → update
 * POST /notices/toggle/{id}         → togglePublish
 * POST /notices/delete/{id}         → delete
 *
 * Category management:
 * GET  /notices/categories          → categories list
 * POST /notices/categories/store    → create category
 * POST /notices/categories/delete/{id} → delete category
 *
 * Crew portal:
 * GET  /my-notices                  → crew-facing read-only view
 */
class NoticeController {

    // ─── Admin: List ─────────────────────────────────────────

    public function index(): void {
        RbacMiddleware::requireRole(['airline_admin', 'hr', 'chief_pilot', 'head_cabin_crew',
                                     'safety_officer', 'document_control', 'training_admin', 'super_admin']);
        $tenantId   = currentTenantId();
        $category   = $_GET['category'] ?? null;
        $priority   = $_GET['priority'] ?? null;
        $notices    = Notice::allForTenant($tenantId, false, $category ?: null, $priority ?: null);
        $categories = Notice::getCategories($tenantId);

        $pageTitle    = 'Notices & Bulletins';
        $pageSubtitle = 'Manage airline notices and operational bulletins';
        $headerAction = '<a href="/notices/create" class="btn btn-primary">＋ New Notice</a>';

        ob_start();
        require VIEWS_PATH . '/notices/index.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    // ─── Admin: Create / Store ────────────────────────────────

    public function create(): void {
        RbacMiddleware::requireRole(['airline_admin', 'hr', 'chief_pilot', 'head_cabin_crew',
                                     'safety_officer', 'document_control', 'training_admin', 'super_admin']);
        $tenantId   = currentTenantId();
        $categories = Notice::getCategories($tenantId);
        $roles      = Database::fetchAll(
            "SELECT MIN(id) as id, name, slug FROM roles WHERE tenant_id = ? GROUP BY slug ORDER BY name",
            [$tenantId]
        );

        $pageTitle    = 'Create Notice';
        $pageSubtitle = 'Publish a notice or bulletin to your airline';

        ob_start();
        require VIEWS_PATH . '/notices/create.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function store(): void {
        RbacMiddleware::requireRole(['airline_admin', 'hr', 'chief_pilot', 'head_cabin_crew',
                                     'safety_officer', 'document_control', 'training_admin', 'super_admin']);
        if (!verifyCsrf()) {
            flash('error', 'Invalid security token. Please try again.');
            redirect('/notices/create');
        }

        $user     = currentUser();
        $tenantId = currentTenantId();

        $id = Notice::create([
            'tenant_id'   => $tenantId,
            'title'       => trim($_POST['title'] ?? ''),
            'body'        => trim($_POST['body'] ?? ''),
            'priority'    => $_POST['priority'] ?? 'normal',
            'category'    => $_POST['category'] ?? 'general',
            'published'   => isset($_POST['published']) ? 1 : 0,
            'expires_at'  => !empty($_POST['expires_at']) ? $_POST['expires_at'] : null,
            'requires_ack'=> isset($_POST['requires_ack']) ? 1 : 0,
            'created_by'  => $user['id'],
        ]);

        // Role visibility (empty = all roles)
        $visibleRoles = $_POST['visible_roles'] ?? [];
        if (!empty($visibleRoles)) {
            Notice::setRoleVisibility($id, array_map('intval', $visibleRoles));
        }

        AuditLog::log('notice_created', 'notice', $id, "Created notice: " . ($_POST['title'] ?? ''));
        flash('success', 'Notice created successfully.');
        redirect('/notices');
    }

    // ─── Admin: Edit / Update ─────────────────────────────────

    public function edit(int $id): void {
        RbacMiddleware::requireRole(['airline_admin', 'hr', 'chief_pilot', 'head_cabin_crew',
                                     'safety_officer', 'document_control', 'training_admin', 'super_admin']);
        $tenantId = currentTenantId();
        $notice   = Notice::find($id);
        if (!$notice || $notice['tenant_id'] != $tenantId) {
            flash('error', 'Notice not found.');
            redirect('/notices');
        }

        $categories   = Notice::getCategories($tenantId);
        $roles        = Database::fetchAll(
            "SELECT MIN(id) as id, name, slug FROM roles WHERE tenant_id = ? GROUP BY slug ORDER BY name",
            [$tenantId]
        );
        $selectedRoles = Notice::getRoleVisibilityIds($id);

        $pageTitle    = 'Edit Notice';
        $pageSubtitle = 'Update notice details';

        ob_start();
        require VIEWS_PATH . '/notices/edit.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function update(int $id): void {
        RbacMiddleware::requireRole(['airline_admin', 'hr', 'chief_pilot', 'head_cabin_crew',
                                     'safety_officer', 'document_control', 'training_admin', 'super_admin']);
        if (!verifyCsrf()) {
            flash('error', 'Invalid security token.');
            redirect("/notices/edit/$id");
        }

        $notice = Notice::find($id);
        if (!$notice || $notice['tenant_id'] != currentTenantId()) {
            flash('error', 'Notice not found.');
            redirect('/notices');
        }

        Notice::update($id, [
            'title'       => trim($_POST['title'] ?? ''),
            'body'        => trim($_POST['body'] ?? ''),
            'priority'    => $_POST['priority'] ?? 'normal',
            'category'    => $_POST['category'] ?? 'general',
            'published'   => isset($_POST['published']) ? 1 : 0,
            'expires_at'  => !empty($_POST['expires_at']) ? $_POST['expires_at'] : null,
            'requires_ack'=> isset($_POST['requires_ack']) ? 1 : 0,
        ]);

        $visibleRoles = $_POST['visible_roles'] ?? [];
        Notice::setRoleVisibility($id, array_map('intval', $visibleRoles));

        AuditLog::log('notice_updated', 'notice', $id, "Updated notice: " . ($_POST['title'] ?? ''));
        flash('success', 'Notice updated successfully.');
        redirect('/notices');
    }

    // ─── Admin: Toggle Publish / Delete ──────────────────────

    public function togglePublish(int $id): void {
        RbacMiddleware::requireRole(['airline_admin', 'hr', 'chief_pilot', 'head_cabin_crew',
                                     'safety_officer', 'document_control', 'training_admin', 'super_admin']);
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/notices');
        }
        $notice = Notice::find($id);
        if (!$notice || $notice['tenant_id'] != currentTenantId()) {
            flash('error', 'Notice not found.');
            redirect('/notices');
        }

        Notice::togglePublish($id);
        $status = $notice['published'] ? 'unpublished' : 'published';
        AuditLog::log("notice_$status", 'notice', $id, "Toggled notice: " . $notice['title']);
        flash('success', "Notice $status.");
        redirect('/notices');
    }

    public function delete(int $id): void {
        RbacMiddleware::requireRole(['airline_admin', 'hr', 'chief_pilot', 'head_cabin_crew',
                                     'safety_officer', 'document_control', 'training_admin', 'super_admin']);
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/notices');
        }
        $notice = Notice::find($id);
        if (!$notice || $notice['tenant_id'] != currentTenantId()) {
            flash('error', 'Notice not found.');
            redirect('/notices');
        }

        Notice::delete($id);
        AuditLog::log('notice_deleted', 'notice', $id, "Deleted notice: " . $notice['title']);
        flash('success', 'Notice deleted.');
        redirect('/notices');
    }

    // ─── Category Management ──────────────────────────────────

    public function categories(): void {
        RbacMiddleware::requireRole(['airline_admin', 'document_control', 'super_admin']);
        $tenantId   = currentTenantId();
        $categories = Notice::getCategories($tenantId);

        $pageTitle    = 'Notice Categories';
        $pageSubtitle = 'Manage notice category types';
        $headerAction = '<a href="/notices" class="btn btn-outline btn-sm">← Back to Notices</a>';

        ob_start();
        require VIEWS_PATH . '/notices/categories.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function storeCategory(): void {
        RbacMiddleware::requireRole(['airline_admin', 'document_control', 'super_admin']);
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/notices/categories');
        }

        $tenantId = currentTenantId();
        $name     = trim($_POST['name'] ?? '');
        if (empty($name)) {
            flash('error', 'Category name is required.');
            redirect('/notices/categories');
        }

        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $name));
        Notice::createCategory($tenantId, $name, $slug);
        flash('success', "Category \"$name\" added.");
        redirect('/notices/categories');
    }

    public function deleteCategory(int $id): void {
        RbacMiddleware::requireRole(['airline_admin', 'document_control', 'super_admin']);
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/notices/categories');
        }
        Notice::deleteCategory($id, currentTenantId());
        flash('success', 'Category removed.');
        redirect('/notices/categories');
    }

    // ─── Crew Portal: My Notices ──────────────────────────────

    public function myNotices(): void {
        requireAuth();
        $tenantId  = currentTenantId();
        $userId    = (int) currentUser()['id'];
        $userRoles = UserModel::getRoles($userId);
        $roleSlugs = array_column($userRoles, 'slug');
        $notices   = Notice::forUserRoles($tenantId, $roleSlugs);

        // Mark all as read
        foreach ($notices as $notice) {
            Database::execute(
                "INSERT OR IGNORE INTO notice_reads (notice_id, user_id, tenant_id) VALUES (?, ?, ?)",
                [$notice['id'], $userId, $tenantId]
            );
        }

        $pageTitle    = 'My Notices';
        $pageSubtitle = 'Active notices and bulletins for your role';

        ob_start();
        require VIEWS_PATH . '/notices/my_notices.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function acknowledgeNotice(int $id): void {
        requireAuth();
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/my-notices');
        }
        $userId   = (int) currentUser()['id'];
        $tenantId = currentTenantId();
        Database::execute(
            "INSERT OR IGNORE INTO notice_reads (notice_id, user_id, tenant_id) VALUES (?, ?, ?)",
            [$id, $userId, $tenantId]
        );
        Database::execute(
            "UPDATE notice_reads SET acknowledged_at = CURRENT_TIMESTAMP WHERE notice_id = ? AND user_id = ?",
            [$id, $userId]
        );
        flash('success', 'Notice acknowledged.');
        redirect('/my-notices');
    }
}
