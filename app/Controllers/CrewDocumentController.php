<?php
/**
 * CrewDocumentController — personnel document vault.
 *
 * Routes:
 *   GET  /personnel/documents                           admin list (all tenant docs)
 *   GET  /personnel/documents/user/{id}                 per-staff documents
 *   GET  /personnel/documents/{id}/download             download scan
 *   POST /personnel/documents/{id}/approve              reviewer approve
 *   POST /personnel/documents/{id}/reject               reviewer reject
 *   POST /personnel/documents/{id}/revoke               reviewer revoke
 *
 * Crew upload happens through the change-request flow at
 * /my-profile/documents/upload (ChangeRequestController::submitDocument).
 */
class CrewDocumentController {

    private const REVIEW_ROLES = ['airline_admin', 'hr', 'chief_pilot', 'head_cabin_crew',
                                  'engineering_manager', 'training_admin', 'super_admin'];

    public function index(): void {
        RbacMiddleware::requireRole(self::REVIEW_ROLES);
        $tenantId = currentTenantId();

        $filters = [
            'status'   => $_GET['status']   ?? null,
            'doc_type' => $_GET['doc_type'] ?? null,
            'user_id'  => isset($_GET['user_id']) ? (int) $_GET['user_id'] : null,
        ];
        $documents = CrewDocumentModel::allForTenant($tenantId, array_filter($filters));
        $pendingCount = CrewDocumentModel::pendingApprovalCount($tenantId);

        $docTypes = Database::fetchAll(
            "SELECT DISTINCT doc_type FROM crew_documents WHERE tenant_id = ? ORDER BY doc_type",
            [$tenantId]
        );

        $pageTitle    = 'Personnel Documents';
        $pageSubtitle = 'Document Vault & Approval Queue';

        ob_start();
        require VIEWS_PATH . '/personnel/documents_index.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function forUser(int $userId): void {
        RbacMiddleware::requireRole(self::REVIEW_ROLES);
        $tenantId = currentTenantId();

        $user = UserModel::find($userId);
        if (!$user || (int) $user['tenant_id'] !== (int) $tenantId) {
            flash('error', 'Staff member not found.');
            redirect('/personnel/documents');
        }

        $documents = CrewDocumentModel::forUser($userId, includeSuperseded: true);
        $required  = RoleRequiredDocumentModel::forRoles(
            array_column(UserModel::getRoles($userId), 'slug'),
            $tenantId
        );

        $pageTitle    = e($user['name']) . ' — Documents';
        $pageSubtitle = 'All compliance documents for this staff member';

        ob_start();
        require VIEWS_PATH . '/personnel/documents_user.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function download(int $id): void {
        $this->serve($id, inline: false);
    }

    /** Inline view — renders PDF/image in-browser without forcing download. */
    public function view(int $id): void {
        $this->serve($id, inline: true);
    }

    /**
     * Common file-serving pipeline.
     * Tenant + owner/reviewer guarded.
     * For inline=true, only known-safe MIME types (application/pdf, image/*)
     * are sent as inline — everything else falls back to attachment so that
     * an uploaded HTML/SVG can't be executed in the portal origin.
     */
    private function serve(int $id, bool $inline): void {
        $doc = CrewDocumentModel::find($id);
        if (!$doc) {
            http_response_code(404);
            echo 'Document not found';
            return;
        }
        $tenantId = currentTenantId();
        $me = currentUser();
        $isOwner = $me && (int) $me['id'] === (int) $doc['user_id'];

        if ((int) $doc['tenant_id'] !== (int) $tenantId) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }
        if (!$isOwner) {
            RbacMiddleware::requireRole(self::REVIEW_ROLES);
        }

        $path = $doc['file_path'];
        if (!$path || !file_exists(storagePath($path))) {
            http_response_code(404);
            echo 'File missing';
            return;
        }

        $full = storagePath($path);
        $mime = $doc['file_mime'] ?: 'application/octet-stream';

        // Safe inline render only for PDF + common images.
        $safeInline = $mime === 'application/pdf' || str_starts_with($mime, 'image/');
        $disposition = ($inline && $safeInline) ? 'inline' : 'attachment';

        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' . $disposition . '; filename="' . basename($doc['file_name'] ?: $path) . '"');
        header('Content-Length: ' . filesize($full));
        // Tight CSP for inline rendering — prevents executable content
        // masquerading as a document even if MIME sniffing goes wrong.
        header("X-Content-Type-Options: nosniff");
        if ($inline) {
            header("Content-Security-Policy: default-src 'none'; img-src 'self' data:; object-src 'self';");
        }
        readfile($full);
        AuditService::log(
            $inline ? 'compliance.document.viewed' : 'compliance.document.downloaded',
            'crew_document', $id
        );
        exit;
    }

    public function approve(int $id): void {
        RbacMiddleware::requireRole(self::REVIEW_ROLES);
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/personnel/documents');
        }
        $doc = CrewDocumentModel::find($id);
        if (!$doc || (int) $doc['tenant_id'] !== (int) currentTenantId()) {
            flash('error', 'Document not found.');
            redirect('/personnel/documents');
        }
        $me = currentUser();
        CrewDocumentModel::approve($id, (int) $me['id']);
        AuditService::log('compliance.document.approved', 'crew_document', $id,
            ['doc_type' => $doc['doc_type'], 'user_id' => $doc['user_id']]);
        flash('success', 'Document approved.');
        redirect('/personnel/documents/user/' . (int) $doc['user_id']);
    }

    public function reject(int $id): void {
        RbacMiddleware::requireRole(self::REVIEW_ROLES);
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/personnel/documents');
        }
        $doc = CrewDocumentModel::find($id);
        if (!$doc || (int) $doc['tenant_id'] !== (int) currentTenantId()) {
            flash('error', 'Document not found.');
            redirect('/personnel/documents');
        }
        $reason = trim($_POST['reason'] ?? '');
        if ($reason === '') {
            flash('error', 'A reason is required when rejecting a document.');
            redirect('/personnel/documents/user/' . (int) $doc['user_id']);
        }
        $me = currentUser();
        CrewDocumentModel::reject($id, (int) $me['id'], $reason);
        AuditService::log('compliance.document.rejected', 'crew_document', $id,
            ['reason' => $reason, 'user_id' => $doc['user_id']]);
        flash('success', 'Document rejected.');
        redirect('/personnel/documents/user/' . (int) $doc['user_id']);
    }

    public function revoke(int $id): void {
        RbacMiddleware::requireRole(self::REVIEW_ROLES);
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/personnel/documents');
        }
        $doc = CrewDocumentModel::find($id);
        if (!$doc || (int) $doc['tenant_id'] !== (int) currentTenantId()) {
            flash('error', 'Document not found.');
            redirect('/personnel/documents');
        }
        CrewDocumentModel::revoke($id);
        AuditService::log('compliance.document.revoked', 'crew_document', $id);
        flash('success', 'Document revoked.');
        redirect('/personnel/documents/user/' . (int) $doc['user_id']);
    }
}
