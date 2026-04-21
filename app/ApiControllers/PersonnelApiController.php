<?php
/**
 * PersonnelApiController — Phase 6 compliance API for the iPad app.
 *
 * Endpoints:
 *   GET  /api/personnel/documents                         own documents
 *   POST /api/personnel/change-request                    submit change request
 *   GET  /api/personnel/change-requests                   own change requests
 *   POST /api/personnel/change-requests/{id}/withdraw     withdraw own CR
 *   GET  /api/personnel/eligibility                       own readiness
 *   GET  /api/personnel/eligibility/{userId}              reviewer: any staff (RBAC)
 *   GET  /api/personnel/required-docs                     role-required docs for current user
 *
 * All auth handled by ApiAuthMiddleware via bearer token upstream.
 */
class PersonnelApiController {

    private const REVIEW_ROLES = ['airline_admin', 'hr', 'chief_pilot', 'head_cabin_crew',
                                  'engineering_manager', 'training_admin', 'super_admin',
                                  'safety_officer', 'scheduler', 'base_manager'];

    public function myDocuments(): void {
        $user = apiUser();
        $docs = CrewDocumentModel::forUser((int) $user['user_id']);
        jsonResponse([
            'success' => true,
            'documents' => array_map(fn($d) => self::serializeDoc($d), $docs),
        ]);
    }

    public function myEligibility(): void {
        $user = apiUser();
        jsonResponse([
            'success' => true,
            'eligibility' => EligibilityService::computeForUser((int) $user['user_id']),
        ]);
    }

    public function userEligibility(int $userId): void {
        RbacMiddleware::apiRequireRole(self::REVIEW_ROLES);
        $tenantId = apiTenantId();
        $target = UserModel::find($userId);
        if (!$target || (int) $target['tenant_id'] !== (int) $tenantId) {
            jsonResponse(['error' => 'User not found'], 404);
        }
        jsonResponse([
            'success' => true,
            'user_id' => $userId,
            'eligibility' => EligibilityService::computeForUser($userId),
        ]);
    }

    public function myChangeRequests(): void {
        $user = apiUser();
        $mine = ChangeRequestModel::mineForUser((int) $user['user_id'], 50);
        jsonResponse([
            'success' => true,
            'requests' => array_map(fn($r) => self::serializeCR($r), $mine),
        ]);
    }

    public function submitChangeRequest(): void {
        $user = apiUser();
        $userId = (int) $user['user_id'];
        $tenantId = (int) apiTenantId();

        // JSON body (preferred) or form body
        $body = json_decode(file_get_contents('php://input'), true);
        if (!is_array($body)) $body = $_POST;

        $targetEntity = $body['target_entity'] ?? '';
        if (!in_array($targetEntity, ChangeRequestModel::ENTITIES, true)) {
            jsonResponse(['error' => 'Invalid target_entity'], 422);
        }
        $targetId = !empty($body['target_id']) ? (int) $body['target_id'] : null;
        $changeType = $body['change_type'] ?? 'update';
        $payload = is_array($body['payload'] ?? null) ? $body['payload'] : [];

        $crId = ChangeRequestModel::create([
            'tenant_id'         => $tenantId,
            'user_id'           => $userId,
            'requester_user_id' => $userId,
            'target_entity'     => $targetEntity,
            'target_id'         => $targetId,
            'change_type'       => $changeType,
            'payload'           => $payload,
        ]);

        AuditService::logApi('compliance.change_request.submitted', 'change_request', $crId,
            ['target_entity' => $targetEntity, 'via' => 'ipad']);

        jsonResponse([
            'success' => true,
            'request_id' => $crId,
            'status' => ChangeRequestModel::STATUS_SUBMITTED,
        ]);
    }

    public function withdrawChangeRequest(int $id): void {
        $user = apiUser();
        ChangeRequestModel::withdraw($id, (int) $user['user_id']);
        AuditService::logApi('compliance.change_request.withdrawn', 'change_request', $id);
        jsonResponse(['success' => true]);
    }

    public function myRequiredDocs(): void {
        $user = apiUser();
        $tenantId = (int) apiTenantId();
        $slugs = array_column(UserModel::getRoles((int) $user['user_id']), 'slug');
        $required = RoleRequiredDocumentModel::forRoles($slugs, $tenantId);
        jsonResponse([
            'success'  => true,
            'required' => array_map(fn($r) => [
                'doc_type'      => $r['doc_type'],
                'doc_label'     => $r['doc_label'],
                'is_mandatory'  => (bool) $r['is_mandatory'],
                'warning_days'  => (int) $r['warning_days'],
                'critical_days' => (int) $r['critical_days'],
                'description'   => $r['description'] ?? null,
            ], $required),
        ]);
    }

    // ─── serializers ─────────────────────────────────────────────────────────

    private static function serializeDoc(array $d): array {
        return [
            'id'                => (string) $d['id'],
            'doc_type'          => $d['doc_type'],
            'doc_category'      => $d['doc_category'] ?? null,
            'doc_title'         => $d['doc_title'],
            'doc_number'        => $d['doc_number'] ?? null,
            'issuing_authority' => $d['issuing_authority'] ?? null,
            'issue_date'        => $d['issue_date'] ?? null,
            'expiry_date'       => $d['expiry_date'] ?? null,
            'status'            => $d['status'],
            'approved_at'       => $d['approved_at'] ?? null,
            'rejection_reason'  => $d['rejection_reason'] ?? null,
            'has_file'          => !empty($d['file_path']),
        ];
    }

    private static function serializeCR(array $r): array {
        return [
            'id'             => (string) $r['id'],
            'target_entity'  => $r['target_entity'],
            'target_id'      => isset($r['target_id']) ? (string) $r['target_id'] : null,
            'change_type'    => $r['change_type'],
            'status'         => $r['status'],
            'submitted_at'   => $r['submitted_at'],
            'reviewed_at'    => $r['reviewed_at'] ?? null,
            'reviewer_notes' => $r['reviewer_notes'] ?? null,
            'payload'        => json_decode($r['payload'] ?? '{}', true) ?: [],
        ];
    }
}
