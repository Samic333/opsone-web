<?php
/**
 * ChangeRequestApplier — applies an approved change request to the target
 * record (profile, license, qualification, document, emergency_contact).
 *
 * Keeps the approval workflow atomic: a reviewer calls
 * ChangeRequestApplier::apply($requestId, $reviewerId, $notes) which
 * marks the CR approved and mutates the target in one transaction.
 */
class ChangeRequestApplier {

    /**
     * Apply an approved change request. Throws on invalid state.
     * Returns ['target_entity' => ..., 'target_id' => ...] on success.
     */
    public static function apply(int $requestId, int $reviewerId, ?string $notes = null): array {
        $cr = ChangeRequestModel::find($requestId);
        if (!$cr) {
            throw new \RuntimeException('Change request not found');
        }
        if (!in_array($cr['status'], [ChangeRequestModel::STATUS_SUBMITTED,
                                      ChangeRequestModel::STATUS_UNDER_REVIEW,
                                      ChangeRequestModel::STATUS_INFO_REQUESTED], true)) {
            throw new \RuntimeException('Change request is not in a reviewable state');
        }

        $payload = json_decode($cr['payload'] ?? '{}', true) ?: [];
        $userId  = (int) $cr['user_id'];
        $tenantId = (int) $cr['tenant_id'];
        $target   = $cr['target_entity'];
        $targetId = $cr['target_id'] !== null ? (int) $cr['target_id'] : null;

        Database::beginTransaction();
        try {
            $outId = match ($target) {
                'profile'           => self::applyProfile($userId, $tenantId, $payload),
                'license'           => self::applyLicense($userId, $tenantId, $targetId, $cr['change_type'], $payload),
                'qualification'     => self::applyQualification($userId, $tenantId, $targetId, $cr['change_type'], $payload),
                'document'          => self::applyDocument($userId, $tenantId, $targetId, $cr, $reviewerId),
                'emergency_contact' => self::applyEmergencyContact($userId, $tenantId, $targetId, $cr['change_type'], $payload),
                'assignment'        => self::applyAssignment($userId, $payload),
                default             => throw new \RuntimeException("Unknown target_entity: $target"),
            };

            ChangeRequestModel::approve($requestId, $reviewerId, $notes);

            AuditService::log(
                'compliance.change_request.approved',
                'change_request',
                $requestId,
                ['target_entity' => $target, 'target_id' => $outId, 'notes' => $notes]
            );

            Database::commit();
            return ['target_entity' => $target, 'target_id' => $outId];
        } catch (\Throwable $e) {
            Database::rollback();
            throw $e;
        }
    }

    // ─── Appliers per target ─────────────────────────────────────────────────

    private static function applyProfile(int $userId, int $tenantId, array $payload): int {
        $existing = CrewProfileModel::findByUser($userId) ?? [];
        // Only apply whitelisted keys; payload from a request is already
        // vetted by the submission controller but we re-filter defensively.
        $allowed = [
            'date_of_birth', 'nationality', 'phone', 'address', 'profile_photo_path',
            'emergency_name', 'emergency_phone', 'emergency_relation',
            'passport_number', 'passport_country', 'passport_expiry',
            'visa_number', 'visa_country', 'visa_type', 'visa_expiry',
            'medical_class', 'medical_expiry',
            'contract_type', 'contract_expiry',
        ];
        $merged = $existing;
        foreach ($allowed as $f) {
            if (array_key_exists($f, $payload)) {
                $merged[$f] = $payload[$f];
            }
        }
        CrewProfileModel::save($userId, $tenantId, $merged);
        CrewProfileModel::updateCompletion($userId);
        return $userId;
    }

    private static function applyLicense(int $userId, int $tenantId, ?int $targetId, string $changeType, array $payload): int {
        if ($changeType === 'delete' && $targetId) {
            CrewProfileModel::deleteLicense($targetId, $userId);
            return $targetId;
        }
        if ($changeType === 'create' || !$targetId) {
            $newId = CrewProfileModel::addLicense($userId, $tenantId, $payload);
            Database::execute(
                "UPDATE licenses SET status = 'valid', approved_by = ?, approved_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$payload['_reviewer_id'] ?? null, $newId]
            );
            return $newId;
        }
        // Update
        $fields = ['license_type', 'license_number', 'issuing_authority', 'issue_date', 'expiry_date', 'notes'];
        $sets = [];
        $params = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $payload)) {
                $sets[] = "$f = ?";
                $params[] = $payload[$f] !== '' ? $payload[$f] : null;
            }
        }
        if (!empty($sets)) {
            $params[] = $targetId;
            Database::execute("UPDATE licenses SET " . implode(', ', $sets) . ", status = 'valid' WHERE id = ?", $params);
        }
        return $targetId;
    }

    private static function applyQualification(int $userId, int $tenantId, ?int $targetId, string $changeType, array $payload): int {
        if ($changeType === 'delete' && $targetId) {
            QualificationModel::delete($targetId, $userId);
            return $targetId;
        }
        if ($changeType === 'create' || !$targetId) {
            return QualificationModel::add($userId, $tenantId, $payload);
        }
        $fields = ['qual_type','qual_name','reference_no','authority','issue_date','expiry_date','status','notes'];
        $sets = []; $params = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $payload)) {
                $sets[] = "$f = ?";
                $params[] = $payload[$f] !== '' ? $payload[$f] : null;
            }
        }
        if (!empty($sets)) {
            $params[] = $targetId;
            Database::execute("UPDATE qualifications SET " . implode(', ', $sets) . " WHERE id = ?", $params);
        }
        return $targetId;
    }

    private static function applyDocument(int $userId, int $tenantId, ?int $targetId, array $cr, int $reviewerId): int {
        // The document row was created at submission time with status='pending_approval'.
        // Approving simply flips the status to 'valid' and optionally supersedes the old one.
        $docId = $cr['supporting_document_id'] !== null ? (int) $cr['supporting_document_id'] : $targetId;
        if (!$docId) {
            throw new \RuntimeException('No document attached to request');
        }
        CrewDocumentModel::approve($docId, $reviewerId);
        return $docId;
    }

    private static function applyEmergencyContact(int $userId, int $tenantId, ?int $targetId, string $changeType, array $payload): int {
        if ($changeType === 'delete' && $targetId) {
            EmergencyContactModel::delete($targetId, $userId);
            return $targetId;
        }
        if ($changeType === 'create' || !$targetId) {
            return EmergencyContactModel::create($userId, $tenantId, $payload);
        }
        EmergencyContactModel::update($targetId, $payload);
        return $targetId;
    }

    private static function applyAssignment(int $userId, array $payload): int {
        // Role / department / base assignment updates happen on the users table.
        $allowed = ['department_id', 'base_id', 'line_manager_id', 'employment_status', 'status'];
        $sets = []; $params = [];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $payload)) {
                $sets[] = "$f = ?";
                $params[] = $payload[$f] !== '' ? $payload[$f] : null;
            }
        }
        if (!empty($sets)) {
            $params[] = $userId;
            Database::execute("UPDATE users SET " . implode(', ', $sets) . " WHERE id = ?", $params);
        }
        return $userId;
    }
}
