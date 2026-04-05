<?php
/**
 * FileController — document/manual management
 */
class FileController {
    public function __construct() {
        RbacMiddleware::requireRole(['super_admin', 'airline_admin', 'hr', 'document_control', 'safety_officer']);
    }

    public function index(): void {
        $tenantId = currentTenantId();
        $files = FileModel::allForTenant($tenantId);
        $categories = FileModel::getCategories($tenantId);
        require VIEWS_PATH . '/files/index.php';
    }

    public function showUpload(): void {
        $tenantId = currentTenantId();
        $categories = FileModel::getCategories($tenantId);
        $roles = Database::fetchAll("SELECT * FROM roles WHERE tenant_id = ? ORDER BY name", [$tenantId]);
        require VIEWS_PATH . '/files/upload.php';
    }

    public function upload(): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/files/upload');
        }

        $tenantId = currentTenantId();
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $version = trim($_POST['version'] ?? '1.0');
        $status = $_POST['status'] ?? 'draft';
        $effectiveDate = $_POST['effective_date'] ?? null;
        $requiresAck = isset($_POST['requires_ack']) ? 1 : 0;
        $visibleRoles = $_POST['visible_roles'] ?? [];

        if (empty($title)) {
            flash('error', 'Document title is required.');
            redirect('/files/upload');
        }

        // Handle file upload
        if (empty($_FILES['file']['name']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Please select a file to upload.');
            redirect('/files/upload');
        }

        $file = $_FILES['file'];
        $maxSize = config('upload.max_size', 52428800);
        if ($file['size'] > $maxSize) {
            flash('error', 'File exceeds maximum allowed size.');
            redirect('/files/upload');
        }

        // Check file type
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedTypes = config('upload.allowed_types', ['pdf']);
        if (!in_array($ext, $allowedTypes)) {
            flash('error', "File type .$ext is not allowed.");
            redirect('/files/upload');
        }

        // Create upload directory
        $uploadDir = storagePath("uploads/tenant_$tenantId/" . date('Y/m'));
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $safeName = sanitizeFilename(pathinfo($file['name'], PATHINFO_FILENAME));
        $uniqueName = $safeName . '_' . uniqid() . '.' . $ext;
        $relativePath = "uploads/tenant_$tenantId/" . date('Y/m') . '/' . $uniqueName;
        $fullPath = storagePath($relativePath);

        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            flash('error', 'Failed to save uploaded file.');
            redirect('/files/upload');
        }

        $fileId = FileModel::create([
            'tenant_id' => $tenantId,
            'category_id' => $categoryId ?: null,
            'title' => $title,
            'description' => $description ?: null,
            'file_path' => $relativePath,
            'file_name' => $file['name'],
            'file_size' => $file['size'],
            'mime_type' => $file['type'],
            'version' => $version,
            'status' => $status,
            'effective_date' => $effectiveDate ?: null,
            'requires_ack' => $requiresAck,
            'uploaded_by' => currentUser()['id'],
        ]);

        // Set role visibility
        if (!empty($visibleRoles)) {
            FileModel::setRoleVisibility($fileId, array_map('intval', $visibleRoles));
        }

        AuditLog::log('Uploaded File', 'file', $fileId, "Uploaded: $title ({$file['name']})");
        flash('success', "Document \"$title\" uploaded successfully.");
        redirect('/files');
    }

    public function togglePublish(int $id): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/files');
        }
        $file = FileModel::find($id);
        if (!$file || $file['tenant_id'] != currentTenantId()) {
            flash('error', 'File not found.');
            redirect('/files');
        }
        FileModel::togglePublish($id);
        $action = $file['status'] === 'published' ? 'unpublished' : 'published';
        AuditLog::log("File $action", 'file', $id, "{$file['title']} $action");
        flash('success', "Document \"{$file['title']}\" $action.");
        redirect('/files');
    }

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

        header('Content-Type: ' . ($file['mime_type'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . $file['file_name'] . '"');
        header('Content-Length: ' . filesize($fullPath));
        readfile($fullPath);
        exit;
    }

    public function delete(int $id): void {
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
}
