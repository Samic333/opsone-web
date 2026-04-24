<?php
/**
 * FileController — document / manual management.
 *
 * Phase 4 (V2) additions:
 *   • Department + base targeting on upload/update.
 *   • Version chain: "replace" workflow creates a new file row that points
 *     back to the previous via replaces_file_id and marks the old one
 *     superseded/archived.
 *   • Read receipts recorded on download (markRead is idempotent).
 *   • In-app notifications dispatched to targeted users on publish.
 *   • Acknowledgement report and version-history views.
 */
class FileController {
    /**
     * Admin surface is gated to document managers. The crew-facing methods
     * (myFiles, acknowledgeFile, download) have their own checks so pilots
     * and cabin crew can reach them — the constructor used to block them.
     */
    private function requireAdmin(): void {
        RbacMiddleware::requireRole([
            'super_admin', 'airline_admin', 'hr', 'document_control', 'safety_officer'
        ]);
        // Module-enabled check only. The role list above is the authoritative
        // admin gate; adding a per-role capability-template check on top (via
        // requireModuleAccess) was fragile — it silently 302s any role whose
        // role_capability_templates row is missing or not yet backfilled.
        // We only need to confirm the manuals module is turned on for this
        // tenant; the rest is the role guard's job.
        if (!isPlatformUser()) {
            AuthorizationService::requireModuleEnabled('manuals');
        }
    }

    // ─── Admin list ─────────────────────────────────────────────────────

    public function index(): void {
        $this->requireAdmin();
        $tenantId   = currentTenantId();
        $files      = FileModel::allForTenant($tenantId);
        $categories = FileModel::getCategories($tenantId);

        // Precompute audience summary per file so the index view can show it.
        foreach ($files as &$f) {
            $f['audience_summary'] = FileModel::audienceSummary((int)$f['id']);
        }
        unset($f);

        require VIEWS_PATH . '/files/index.php';
    }

    // ─── Upload ─────────────────────────────────────────────────────────

    public function showUpload(): void {
        $this->requireAdmin();
        $tenantId = currentTenantId();
        [$categories, $roles, $departments, $bases] = $this->lookups($tenantId);

        // Optional ?replaces=<id> query → pre-fills a "new version of X" form.
        $replacesId   = isset($_GET['replaces']) ? (int)$_GET['replaces'] : 0;
        $replacesFile = null;
        $selectedRoles = $selectedDepts = $selectedBases = [];
        if ($replacesId) {
            $replacesFile = FileModel::find($replacesId);
            if ($replacesFile && (int)$replacesFile['tenant_id'] === $tenantId) {
                $selectedRoles = FileModel::getRoleVisibilityIds($replacesId);
                $selectedDepts = FileModel::getDepartmentVisibilityIds($replacesId);
                $selectedBases = FileModel::getBaseVisibilityIds($replacesId);
            } else {
                $replacesFile = null;
                $replacesId = 0;
            }
        }
        require VIEWS_PATH . '/files/upload.php';
    }

    public function upload(): void {
        $this->requireAdmin();
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/files/upload');
        }

        $tenantId      = currentTenantId();
        $title         = trim($_POST['title'] ?? '');
        $description   = trim($_POST['description'] ?? '');
        $categoryId    = (int)($_POST['category_id'] ?? 0);
        $version       = trim($_POST['version'] ?? '1.0');
        $status        = $_POST['status'] ?? 'draft';
        $effectiveDate = $_POST['effective_date'] ?? null;
        $requiresAck   = isset($_POST['requires_ack']) ? 1 : 0;
        $replacesId    = (int)($_POST['replaces_file_id'] ?? 0);

        $visibleRoles  = $_POST['visible_roles']        ?? [];
        $visibleDepts  = $_POST['visible_departments']  ?? [];
        $visibleBases  = $_POST['visible_bases']        ?? [];

        if ($title === '') {
            flash('error', 'Document title is required.');
            redirect('/files/upload');
        }

        if (empty($_FILES['file']['name']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Please select a file to upload.');
            redirect('/files/upload');
        }

        $file    = $_FILES['file'];
        $maxSize = config('upload.max_size', 52428800);
        if ($file['size'] > $maxSize) {
            flash('error', 'File exceeds maximum allowed size.');
            redirect('/files/upload');
        }

        $ext          = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedTypes = config('upload.allowed_types', ['pdf']);
        if (!in_array($ext, $allowedTypes, true)) {
            flash('error', "File type .$ext is not allowed.");
            redirect('/files/upload');
        }

        // Validate replacesId belongs to this tenant if supplied.
        if ($replacesId) {
            $prev = FileModel::find($replacesId);
            if (!$prev || (int)$prev['tenant_id'] !== $tenantId) {
                flash('error', 'Cannot replace a document that does not belong to this airline.');
                redirect('/files/upload');
            }
        }

        // Move file to storage.
        $uploadDir = storagePath("uploads/tenant_$tenantId/" . date('Y/m'));
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $safeName     = sanitizeFilename(pathinfo($file['name'], PATHINFO_FILENAME));
        $uniqueName   = $safeName . '_' . uniqid() . '.' . $ext;
        $relativePath = "uploads/tenant_$tenantId/" . date('Y/m') . '/' . $uniqueName;
        $fullPath     = storagePath($relativePath);

        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            flash('error', 'Failed to save uploaded file.');
            redirect('/files/upload');
        }

        $fileId = FileModel::create([
            'tenant_id'        => $tenantId,
            'category_id'      => $categoryId ?: null,
            'title'            => $title,
            'description'      => $description ?: null,
            'file_path'        => $relativePath,
            'file_name'        => $file['name'],
            'file_size'        => $file['size'],
            'mime_type'        => $file['type'],
            'version'          => $version,
            'replaces_file_id' => $replacesId ?: null,
            'status'           => $status,
            'effective_date'   => $effectiveDate ?: null,
            'requires_ack'     => $requiresAck,
            'uploaded_by'      => currentUser()['id'],
        ]);

        FileModel::setRoleVisibility($fileId,       array_map('intval', (array)$visibleRoles));
        FileModel::setDepartmentVisibility($fileId, array_map('intval', (array)$visibleDepts));
        FileModel::setBaseVisibility($fileId,       array_map('intval', (array)$visibleBases));

        // If this is a replacement, supersede the previous version.
        if ($replacesId) {
            FileModel::markSuperseded($replacesId);
            AuditLog::log(
                'File Superseded', 'file', $replacesId,
                "Superseded by v{$version} (file #{$fileId})"
            );
        }

        AuditLog::log(
            'Uploaded File', 'file', $fileId,
            "Uploaded: $title v$version ({$file['name']})"
            . ($replacesId ? " — replaces #$replacesId" : '')
        );

        // Notify targeted users if the new doc is published immediately.
        if ($status === 'published') {
            $this->notifyRecipients($fileId, $title, $version, (bool)$requiresAck, (bool)$replacesId);
        }

        $msg = "Document \"$title\" uploaded successfully.";
        if ($replacesId) $msg .= " Previous version archived.";
        flash('success', $msg);
        redirect('/files');
    }

    // ─── Edit ───────────────────────────────────────────────────────────

    public function edit(int $id): void {
        $this->requireAdmin();
        $file = FileModel::find($id);
        if (!$file || $file['tenant_id'] != currentTenantId()) {
            flash('error', 'Document not found.');
            redirect('/files');
        }

        $tenantId = currentTenantId();
        [$categories, $roles, $departments, $bases] = $this->lookups($tenantId);
        $selectedRoles = FileModel::getRoleVisibilityIds($id);
        $selectedDepts = FileModel::getDepartmentVisibilityIds($id);
        $selectedBases = FileModel::getBaseVisibilityIds($id);

        $pageTitle    = 'Edit Document';
        $pageSubtitle = e($file['title']);

        ob_start();
        require VIEWS_PATH . '/files/edit.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function update(int $id): void {
        $this->requireAdmin();
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect("/files/edit/$id");
        }

        $file = FileModel::find($id);
        if (!$file || $file['tenant_id'] != currentTenantId()) {
            flash('error', 'Document not found.');
            redirect('/files');
        }

        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            flash('error', 'Document title is required.');
            redirect("/files/edit/$id");
        }

        $prevStatus = $file['status'];
        $newStatus  = $_POST['status'] ?? 'draft';

        FileModel::update($id, [
            'title'          => $title,
            'description'    => trim($_POST['description'] ?? ''),
            'category_id'    => (int)($_POST['category_id'] ?? 0),
            'version'        => trim($_POST['version'] ?? '1.0'),
            'status'         => $newStatus,
            'effective_date' => $_POST['effective_date'] ?? null,
            'expires_at'     => $_POST['expires_at'] ?? null,
            'requires_ack'   => isset($_POST['requires_ack']) ? 1 : 0,
        ]);

        FileModel::setRoleVisibility($id,       array_map('intval', (array)($_POST['visible_roles']       ?? [])));
        FileModel::setDepartmentVisibility($id, array_map('intval', (array)($_POST['visible_departments'] ?? [])));
        FileModel::setBaseVisibility($id,       array_map('intval', (array)($_POST['visible_bases']       ?? [])));

        AuditLog::log('Updated File', 'file', $id, "Updated: $title");

        // Fire notifications only on the draft → published transition.
        if ($prevStatus !== 'published' && $newStatus === 'published') {
            $this->notifyRecipients($id, $title, trim($_POST['version'] ?? '1.0'),
                isset($_POST['requires_ack']), false);
        }

        flash('success', "Document \"$title\" updated.");
        redirect('/files');
    }

    // ─── Status transitions ─────────────────────────────────────────────

    public function togglePublish(int $id): void {
        $this->requireAdmin();
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/files');
        }
        $file = FileModel::find($id);
        if (!$file || $file['tenant_id'] != currentTenantId()) {
            flash('error', 'File not found.');
            redirect('/files');
        }
        $wasPublished = $file['status'] === 'published';
        FileModel::togglePublish($id);
        $action = $wasPublished ? 'unpublished' : 'published';
        AuditLog::log("File $action", 'file', $id, "{$file['title']} $action");

        if (!$wasPublished) { // just became published
            $this->notifyRecipients($id, $file['title'], $file['version'],
                (bool)$file['requires_ack'], false);
        }

        flash('success', "Document \"{$file['title']}\" $action.");
        redirect('/files');
    }

    public function archive(int $id): void {
        $this->requireAdmin();
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/files');
        }
        $file = FileModel::find($id);
        if (!$file || $file['tenant_id'] != currentTenantId()) {
            flash('error', 'File not found.');
            redirect('/files');
        }
        FileModel::setStatus($id, 'archived');
        AuditLog::log('File archived', 'file', $id, "{$file['title']} archived");
        flash('success', "Document \"{$file['title']}\" archived.");
        redirect('/files');
    }

    // ─── Download ───────────────────────────────────────────────────────

    public function download(int $id): void {
        $file = FileModel::find($id);
        if (!$file || $file['tenant_id'] != currentTenantId()) {
            http_response_code(404);
            echo "File not found.";
            exit;
        }

        $fullPath = storagePath($file['file_path']);
        if (!file_exists($fullPath)) {
            http_response_code(404);
            echo "File not found on disk.";
            exit;
        }

        // Record read receipt (idempotent — first view sticks).
        $user = currentUser();
        if ($user) {
            FileModel::markRead(
                (int)$file['id'],
                (int)$user['id'],
                (int)$file['tenant_id'],
                $file['version'] ?? null
            );
        }

        header('Content-Type: ' . ($file['mime_type'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . $file['file_name'] . '"');
        header('Content-Length: ' . filesize($fullPath));
        readfile($fullPath);
        exit;
    }

    // ─── Delete (hard) ──────────────────────────────────────────────────

    public function delete(int $id): void {
        $this->requireAdmin();
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/files');
        }
        $file = FileModel::find($id);
        if (!$file || $file['tenant_id'] != currentTenantId()) {
            flash('error', 'File not found.');
            redirect('/files');
        }
        $title = $file['title'];
        FileModel::delete($id);
        AuditLog::log('Deleted File', 'file', $id, "Deleted: $title");
        flash('success', "Document \"$title\" deleted.");
        redirect('/files');
    }

    // ─── Version history ────────────────────────────────────────────────

    public function history(int $id): void {
        $this->requireAdmin();
        $file = FileModel::find($id);
        if (!$file || $file['tenant_id'] != currentTenantId()) {
            flash('error', 'Document not found.');
            redirect('/files');
        }
        $data = FileModel::versionHistory($id);

        $pageTitle    = 'Version History';
        $pageSubtitle = e($file['title']);

        ob_start();
        require VIEWS_PATH . '/files/history.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    // ─── Per-file acknowledgement & read report (admin) ─────────────────

    public function ackReport(int $id): void {
        $this->requireAdmin();
        $file = FileModel::find($id);
        if (!$file || $file['tenant_id'] != currentTenantId()) {
            flash('error', 'Document not found.');
            redirect('/files');
        }
        $recipients = FileModel::recipientReport($id, (int)$file['tenant_id']);

        $totals = [
            'recipients'     => count($recipients),
            'read'           => 0,
            'acknowledged'   => 0,
        ];
        foreach ($recipients as $r) {
            if (!empty($r['read_at']))         $totals['read']++;
            if (!empty($r['acknowledged_at'])) $totals['acknowledged']++;
        }

        $pageTitle    = 'Acknowledgement Report';
        $pageSubtitle = e($file['title']) . ' v' . e($file['version']);

        ob_start();
        require VIEWS_PATH . '/files/ack_report.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    // ─── Crew Portal: My Files ──────────────────────────────────────────

    public function myFiles(): void {
        requireAuth();
        AuthorizationService::requireModuleAccess('manuals', 'view');

        $tenantId  = currentTenantId();
        $session   = currentUser();
        $userId    = (int)$session['id'];

        // Session only carries id/name/email/tenant/employee_id — fetch the full
        // row so department_id/base_id targeting filters work correctly.
        $user = Database::fetch(
            "SELECT id, department_id, base_id FROM users WHERE id = ?",
            [$userId]
        ) ?? $session;

        $userRoles = UserModel::getRoles($userId);
        $roleSlugs = array_column($userRoles, 'slug');

        $files = FileModel::forUser(
            $tenantId,
            $userId,
            $roleSlugs,
            !empty($user['department_id']) ? (int)$user['department_id'] : null,
            !empty($user['base_id'])       ? (int)$user['base_id']       : null
        );

        // Build status map for each file.
        // unread → no file_reads row; read → row exists, no matching ack; acknowledged → ack row.
        $fileStatus = [];
        foreach ($files as $f) {
            $status = 'unread';
            if (!empty($f['user_acknowledged_at']) && (string)$f['user_acknowledged_version'] === (string)$f['version']) {
                $status = 'acknowledged';
            } elseif (!empty($f['user_acknowledged_at']) && (string)$f['user_acknowledged_version'] !== (string)$f['version']) {
                $status = 'ack_outdated'; // previous version was acked, new version needs re-ack
            } elseif (!empty($f['user_read_at'])) {
                $status = 'read';
            }
            $fileStatus[$f['id']] = $status;
        }

        $pageTitle    = 'My Documents';
        $pageSubtitle = 'Active manuals and documents for your role';

        ob_start();
        require VIEWS_PATH . '/files/my_files.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function acknowledgeFile(int $id): void {
        requireAuth();
        AuthorizationService::requireModuleAccess('manuals', 'view');

        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/my-files');
        }

        $userId   = (int) currentUser()['id'];
        $tenantId = currentTenantId();
        $file     = FileModel::find($id);

        if (!$file || (int)$file['tenant_id'] !== $tenantId || $file['status'] !== 'published') {
            flash('error', 'File not found or unavailable.');
            redirect('/my-files');
        }
        if (empty($file['requires_ack'])) {
            flash('error', 'This file does not require acknowledgement.');
            redirect('/my-files');
        }

        // Use PHP-formatted timestamp to avoid dbNow() returning a literal
        // string like "datetime('now')" which wouldn't evaluate as a parameter.
        $now      = date('Y-m-d H:i:s');
        $existing = Database::fetch(
            "SELECT id FROM file_acknowledgements WHERE file_id = ? AND user_id = ?",
            [$id, $userId]
        );

        if (!$existing) {
            Database::insert(
                "INSERT INTO file_acknowledgements
                    (file_id, user_id, tenant_id, version, device_id, acknowledged_at)
                 VALUES (?, ?, ?, ?, NULL, ?)",
                [$id, $userId, $tenantId, $file['version'], $now]
            );
        } else {
            Database::execute(
                "UPDATE file_acknowledgements SET acknowledged_at = ?, version = ?
                   WHERE file_id = ? AND user_id = ?",
                [$now, $file['version'], $id, $userId]
            );
        }

        // Also mark it read (ack implies read).
        FileModel::markRead($id, $userId, $tenantId, $file['version']);

        AuditLog::log(
            'file_acknowledged', 'file', $id,
            "File '{$file['title']}' v{$file['version']} acknowledged via web"
        );
        flash('success', 'Document acknowledged successfully.');
        redirect('/my-files');
    }

    // ─── Helpers ────────────────────────────────────────────────────────

    /** @return array{0:array,1:array,2:array,3:array}  [categories, roles, departments, bases] */
    private function lookups(int $tenantId): array {
        $categories = FileModel::getCategories($tenantId);
        $roles      = Database::fetchAll(
            "SELECT MIN(id) as id, name, slug FROM roles WHERE tenant_id = ? GROUP BY slug ORDER BY name",
            [$tenantId]
        );
        $departments = Database::fetchAll(
            "SELECT id, name FROM departments WHERE tenant_id = ? ORDER BY name",
            [$tenantId]
        );
        $bases = Database::fetchAll(
            "SELECT id, name FROM bases WHERE tenant_id = ? ORDER BY name",
            [$tenantId]
        );
        return [$categories, $roles, $departments, $bases];
    }

    /**
     * Notify all targeted users that a document has been published (or revised).
     * Uses FileModel::recipientReport so the recipient set exactly matches
     * what forUser() would surface in the crew portal.
     */
    private function notifyRecipients(int $fileId, string $title, string $version, bool $requiresAck, bool $isRevision): void {
        $file = FileModel::find($fileId);
        if (!$file) return;

        $recipients = FileModel::recipientReport($fileId, (int)$file['tenant_id']);
        if (empty($recipients)) return;

        $verb  = $isRevision ? 'Revised' : 'New';
        $title = "$verb document: $title (v$version)";
        $body  = $requiresAck
            ? 'Acknowledgement required — open in My Documents to review and acknowledge.'
            : 'A new document has been published to you. Open My Documents to review.';
        $link  = '/my-files';

        foreach ($recipients as $r) {
            NotificationService::notifyUser((int)$r['id'], $title, $body, $link);
        }
    }
}
