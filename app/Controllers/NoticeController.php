<?php
/**
 * NoticeController — CRUD for airline notices/bulletins
 */
class NoticeController {
    public function index(): void {
        $tenantId = currentTenantId();
        $notices = Notice::allForTenant($tenantId);

        $brand = require CONFIG_PATH . '/branding.php';
        $pageTitle = 'Notices & Bulletins';
        $pageSubtitle = 'Manage airline notices and operational bulletins';
        $headerAction = '<a href="/notices/create" class="btn btn-primary">＋ New Notice</a>';

        ob_start();
        require VIEWS_PATH . '/notices/index.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function create(): void {
        $brand = require CONFIG_PATH . '/branding.php';
        $pageTitle = 'Create Notice';
        $pageSubtitle = 'Publish a notice or bulletin to your airline';

        ob_start();
        require VIEWS_PATH . '/notices/create.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function store(): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid security token. Please try again.');
            redirect('/notices/create');
        }

        $user = currentUser();
        $tenantId = currentTenantId();

        $id = Notice::create([
            'tenant_id'  => $tenantId,
            'title'      => $_POST['title'] ?? '',
            'body'       => $_POST['body'] ?? '',
            'priority'   => $_POST['priority'] ?? 'normal',
            'category'   => $_POST['category'] ?? 'general',
            'published'  => isset($_POST['published']) ? 1 : 0,
            'expires_at' => !empty($_POST['expires_at']) ? $_POST['expires_at'] : null,
            'created_by' => $user['id'],
        ]);

        AuditLog::log('notice_created', 'notice', $id, "Created notice: " . ($_POST['title'] ?? ''));
        flash('success', 'Notice created successfully.');
        redirect('/notices');
    }

    public function edit(int $id): void {
        $notice = Notice::find($id);
        if (!$notice || $notice['tenant_id'] != currentTenantId()) {
            flash('error', 'Notice not found.');
            redirect('/notices');
        }

        $brand = require CONFIG_PATH . '/branding.php';
        $pageTitle = 'Edit Notice';
        $pageSubtitle = 'Update notice details';

        ob_start();
        require VIEWS_PATH . '/notices/edit.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function update(int $id): void {
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
            'title'      => $_POST['title'] ?? '',
            'body'       => $_POST['body'] ?? '',
            'priority'   => $_POST['priority'] ?? 'normal',
            'category'   => $_POST['category'] ?? 'general',
            'published'  => isset($_POST['published']) ? 1 : 0,
            'expires_at' => !empty($_POST['expires_at']) ? $_POST['expires_at'] : null,
        ]);

        AuditLog::log('notice_updated', 'notice', $id, "Updated notice: " . ($_POST['title'] ?? ''));
        flash('success', 'Notice updated successfully.');
        redirect('/notices');
    }

    public function togglePublish(int $id): void {
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
}
