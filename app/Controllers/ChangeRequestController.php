<?php
/**
 * ChangeRequestController — compliance change-request workflow.
 *
 * Flow:
 *   Crew submits a request (with optional document upload)
 *     → /my-profile/change-requests/submit
 *   HR / admin reviews the queue at /personnel/change-requests
 *   Approval applies the change via ChangeRequestApplier.
 */
class ChangeRequestController {

    private const REVIEW_ROLES = ['airline_admin', 'hr', 'chief_pilot', 'head_cabin_crew',
                                  'engineering_manager', 'training_admin', 'super_admin'];

    // ─── Reviewer: queue ─────────────────────────────────────────────────────

    public function index(): void {
        RbacMiddleware::requireRole(self::REVIEW_ROLES);
        $tenantId = currentTenantId();

        $status = $_GET['status'] ?? null;
        $requests = ChangeRequestModel::allForTenant($tenantId, $status);

        $counts = [
            'pending'   => ChangeRequestModel::pendingCount($tenantId),
            'approved'  => count(ChangeRequestModel::allForTenant($tenantId, 'approved', 500)),
            'rejected'  => count(ChangeRequestModel::allForTenant($tenantId, 'rejected', 500)),
        ];

        $pageTitle    = 'Change Requests';
        $pageSubtitle = 'Compliance change approval queue';

        ob_start();
        require VIEWS_PATH . '/personnel/change_requests_index.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function review(int $id): void {
        RbacMiddleware::requireRole(self::REVIEW_ROLES);
        $request = ChangeRequestModel::findWithContext($id);
        if (!$request || (int) $request['tenant_id'] !== (int) currentTenantId()) {
            flash('error', 'Change request not found.');
            redirect('/personnel/change-requests');
        }

        $payload = json_decode($request['payload'] ?? '{}', true) ?: [];
        $supportingDoc = null;
        if (!empty($request['supporting_document_id'])) {
            $supportingDoc = CrewDocumentModel::find((int) $request['supporting_document_id']);
        }

        $pageTitle    = 'Change Request #' . (int) $id;
        $pageSubtitle = 'Review & decision';

        ob_start();
        require VIEWS_PATH . '/personnel/change_request_review.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function markReview(int $id): void {
        RbacMiddleware::requireRole(self::REVIEW_ROLES);
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/personnel/change-requests');
        }
        $me = currentUser();
        ChangeRequestModel::markUnderReview($id, (int) $me['id']);
        AuditService::log('compliance.change_request.under_review', 'change_request', $id);
        flash('success', 'Marked under review.');
        redirect('/personnel/change-requests/' . $id);
    }

    public function approve(int $id): void {
        RbacMiddleware::requireRole(self::REVIEW_ROLES);
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/personnel/change-requests');
        }
        $request = ChangeRequestModel::find($id);
        if (!$request || (int) $request['tenant_id'] !== (int) currentTenantId()) {
            flash('error', 'Change request not found.');
            redirect('/personnel/change-requests');
        }
        $notes = trim($_POST['notes'] ?? '');
        $me = currentUser();
        try {
            ChangeRequestApplier::apply($id, (int) $me['id'], $notes ?: null);
            flash('success', 'Change request approved and applied.');
        } catch (\Throwable $e) {
            flash('error', 'Could not apply change: ' . $e->getMessage());
        }
        redirect('/personnel/change-requests/' . $id);
    }

    public function reject(int $id): void {
        RbacMiddleware::requireRole(self::REVIEW_ROLES);
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/personnel/change-requests');
        }
        $request = ChangeRequestModel::find($id);
        if (!$request || (int) $request['tenant_id'] !== (int) currentTenantId()) {
            flash('error', 'Change request not found.');
            redirect('/personnel/change-requests');
        }
        $notes = trim($_POST['notes'] ?? '');
        if ($notes === '') {
            flash('error', 'A reason is required when rejecting.');
            redirect('/personnel/change-requests/' . $id);
        }
        $me = currentUser();
        ChangeRequestModel::reject($id, (int) $me['id'], $notes);
        AuditService::log('compliance.change_request.rejected', 'change_request', $id,
            ['notes' => $notes]);
        flash('success', 'Change request rejected.');
        redirect('/personnel/change-requests/' . $id);
    }

    public function requestInfo(int $id): void {
        RbacMiddleware::requireRole(self::REVIEW_ROLES);
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/personnel/change-requests');
        }
        $notes = trim($_POST['notes'] ?? '');
        if ($notes === '') {
            flash('error', 'A message is required when requesting more info.');
            redirect('/personnel/change-requests/' . $id);
        }
        $me = currentUser();
        ChangeRequestModel::requestInfo($id, (int) $me['id'], $notes);
        AuditService::log('compliance.change_request.info_requested', 'change_request', $id);
        flash('success', 'Further info requested from crew.');
        redirect('/personnel/change-requests/' . $id);
    }

    // ─── Crew: submit & track ────────────────────────────────────────────────

    public function mine(): void {
        requireAuth();
        $me = currentUser();
        $mine = ChangeRequestModel::mineForUser((int) $me['id']);
        $pageTitle    = 'My Change Requests';
        $pageSubtitle = 'Status of your submitted compliance updates';
        ob_start();
        require VIEWS_PATH . '/personnel/my_change_requests.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    /**
     * Submit a change request for the current user.
     * Accepts multipart form:
     *   target_entity, target_id (optional), change_type,
     *   payload[k]=v (whitelisted per entity),
     *   supporting_file (optional upload)
     */
    public function submit(): void {
        requireAuth();
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/my-profile');
        }
        $me = currentUser();
        $userId = (int) $me['id'];
        $tenantId = currentTenantId();

        $targetEntity = $_POST['target_entity'] ?? '';
        if (!in_array($targetEntity, ChangeRequestModel::ENTITIES, true)) {
            flash('error', 'Unknown change target.');
            redirect('/my-profile');
        }
        $targetId = !empty($_POST['target_id']) ? (int) $_POST['target_id'] : null;
        $changeType = $_POST['change_type'] ?? 'update';

        // Whitelist payload fields per target
        $whitelist = self::payloadWhitelist($targetEntity);
        $payload = [];
        foreach ($whitelist as $f) {
            if (array_key_exists($f, $_POST)) {
                $payload[$f] = trim((string) $_POST[$f]);
            }
        }

        // Handle optional file upload → store as pending crew_document
        $supportingDocId = null;
        if (!empty($_FILES['supporting_file']['tmp_name'])) {
            $supportingDocId = self::storeUploadedDocument($userId, $tenantId, $targetEntity, $payload, $_FILES['supporting_file']);
        }

        $crId = ChangeRequestModel::create([
            'tenant_id'              => $tenantId,
            'user_id'                => $userId,
            'requester_user_id'      => $userId,
            'target_entity'          => $targetEntity,
            'target_id'              => $targetId,
            'change_type'            => $changeType,
            'payload'                => $payload,
            'supporting_document_id' => $supportingDocId,
        ]);

        AuditService::log('compliance.change_request.submitted', 'change_request', $crId,
            ['target_entity' => $targetEntity, 'target_id' => $targetId]);

        flash('success', 'Your change request has been submitted for review.');
        redirect('/my-profile/change-requests');
    }

    public function withdraw(int $id): void {
        requireAuth();
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/my-profile/change-requests');
        }
        $me = currentUser();
        ChangeRequestModel::withdraw($id, (int) $me['id']);
        AuditService::log('compliance.change_request.withdrawn', 'change_request', $id);
        flash('success', 'Change request withdrawn.');
        redirect('/my-profile/change-requests');
    }

    // ─── helpers ─────────────────────────────────────────────────────────────

    private static function payloadWhitelist(string $entity): array {
        return match ($entity) {
            'profile' => [
                'date_of_birth','nationality','phone','address',
                'passport_number','passport_country','passport_expiry',
                'visa_number','visa_country','visa_type','visa_expiry',
                'medical_class','medical_expiry',
                'contract_type','contract_expiry',
            ],
            'license' => ['license_type','license_number','issuing_authority','issue_date','expiry_date','notes'],
            'qualification' => ['qual_type','qual_name','reference_no','authority','issue_date','expiry_date','status','notes'],
            'document' => ['doc_type','doc_category','doc_title','doc_number','issuing_authority','issue_date','expiry_date','notes'],
            'emergency_contact' => ['contact_name','relation','phone_primary','phone_alt','email','address','is_primary'],
            'assignment' => ['department_id','base_id','line_manager_id','employment_status','status'],
            default => [],
        };
    }

    private static function storeUploadedDocument(int $userId, int $tenantId, string $entity, array $payload, array $upload): int {
        $storageDir = storagePath('crew_documents/' . $tenantId . '/' . $userId);
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0775, true);
        }
        $ext  = pathinfo($upload['name'] ?? 'upload', PATHINFO_EXTENSION) ?: 'bin';
        $safe = sanitizeFilename(($payload['doc_title'] ?? $entity) . '_' . date('Ymd_His')) . '.' . $ext;
        $target = $storageDir . '/' . $safe;
        move_uploaded_file($upload['tmp_name'], $target);

        return CrewDocumentModel::create([
            'tenant_id'      => $tenantId,
            'user_id'        => $userId,
            'doc_type'       => $payload['doc_type'] ?? ($entity === 'license' ? 'license' : ($entity === 'qualification' ? 'qualification' : 'other')),
            'doc_category'   => $payload['doc_category'] ?? null,
            'doc_title'      => $payload['doc_title'] ?? ucfirst($entity) . ' Upload',
            'doc_number'     => $payload['doc_number'] ?? null,
            'issuing_authority' => $payload['issuing_authority'] ?? null,
            'issue_date'     => $payload['issue_date']  ?? null,
            'expiry_date'    => $payload['expiry_date'] ?? null,
            'file_path'      => 'crew_documents/' . $tenantId . '/' . $userId . '/' . $safe,
            'file_name'      => $upload['name'] ?? $safe,
            'file_mime'      => $upload['type'] ?? null,
            'file_size'      => $upload['size'] ?? null,
            'status'         => 'pending_approval',
            'uploaded_by'    => $userId,
        ]);
    }
}
