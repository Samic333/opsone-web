<?php
/**
 * ChangeRequestModel — compliance change-request approval workflow.
 *
 * Any update to a sensitive compliance field (license number/expiry, medical,
 * passport, visa, contract, document) must go through a change request.
 * HR / authorized reviewers approve or reject; the original approved record
 * remains untouched until approval lands.
 *
 * Target entities: profile | license | qualification | document | emergency_contact | assignment
 * Statuses: submitted → under_review → approved | rejected | info_requested | withdrawn
 */
class ChangeRequestModel {

    public const STATUS_SUBMITTED     = 'submitted';
    public const STATUS_UNDER_REVIEW  = 'under_review';
    public const STATUS_APPROVED      = 'approved';
    public const STATUS_REJECTED      = 'rejected';
    public const STATUS_INFO_REQUESTED = 'info_requested';
    public const STATUS_WITHDRAWN     = 'withdrawn';

    public const ENTITIES = [
        'profile', 'license', 'qualification', 'document', 'emergency_contact', 'assignment',
    ];

    // ─── CRUD ───────────────────────────────────────────────────────────────

    public static function find(int $id): ?array {
        return Database::fetch("SELECT * FROM compliance_change_requests WHERE id = ?", [$id]);
    }

    public static function findWithContext(int $id): ?array {
        $row = Database::fetch(
            "SELECT cr.*,
                    u.name  AS user_name,  u.employee_id AS user_employee_id,
                    ru.name AS requester_name,
                    rv.name AS reviewer_name
             FROM compliance_change_requests cr
             JOIN users u ON cr.user_id = u.id
             LEFT JOIN users ru ON cr.requester_user_id = ru.id
             LEFT JOIN users rv ON cr.reviewer_user_id  = rv.id
             WHERE cr.id = ?",
            [$id]
        );
        return $row ?: null;
    }

    public static function create(array $data): int {
        $sql = "INSERT INTO compliance_change_requests
                (tenant_id, user_id, requester_user_id, target_entity, target_id,
                 change_type, payload, supporting_file_id, supporting_document_id, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        return Database::insert($sql, [
            $data['tenant_id'],
            $data['user_id'],
            $data['requester_user_id'],
            $data['target_entity'],
            $data['target_id'] ?? null,
            $data['change_type'] ?? 'update',
            is_array($data['payload']) ? json_encode($data['payload']) : (string) $data['payload'],
            $data['supporting_file_id']     ?? null,
            $data['supporting_document_id'] ?? null,
            $data['status'] ?? self::STATUS_SUBMITTED,
        ]);
    }

    public static function markUnderReview(int $id, int $reviewerId): void {
        Database::execute(
            "UPDATE compliance_change_requests
             SET status = ?, reviewer_user_id = ?
             WHERE id = ? AND status = ?",
            [self::STATUS_UNDER_REVIEW, $reviewerId, $id, self::STATUS_SUBMITTED]
        );
    }

    public static function approve(int $id, int $reviewerId, ?string $notes = null): void {
        Database::execute(
            "UPDATE compliance_change_requests
             SET status = ?, reviewer_user_id = ?, reviewer_notes = ?, reviewed_at = CURRENT_TIMESTAMP
             WHERE id = ?",
            [self::STATUS_APPROVED, $reviewerId, $notes, $id]
        );
    }

    public static function reject(int $id, int $reviewerId, string $notes): void {
        Database::execute(
            "UPDATE compliance_change_requests
             SET status = ?, reviewer_user_id = ?, reviewer_notes = ?, reviewed_at = CURRENT_TIMESTAMP
             WHERE id = ?",
            [self::STATUS_REJECTED, $reviewerId, $notes, $id]
        );
    }

    public static function requestInfo(int $id, int $reviewerId, string $notes): void {
        Database::execute(
            "UPDATE compliance_change_requests
             SET status = ?, reviewer_user_id = ?, reviewer_notes = ?, reviewed_at = CURRENT_TIMESTAMP
             WHERE id = ?",
            [self::STATUS_INFO_REQUESTED, $reviewerId, $notes, $id]
        );
    }

    public static function withdraw(int $id, int $userId): void {
        Database::execute(
            "UPDATE compliance_change_requests
             SET status = ?, reviewed_at = CURRENT_TIMESTAMP
             WHERE id = ? AND requester_user_id = ? AND status IN (?, ?)",
            [self::STATUS_WITHDRAWN, $id, $userId, self::STATUS_SUBMITTED, self::STATUS_INFO_REQUESTED]
        );
    }

    // ─── Queries ────────────────────────────────────────────────────────────

    /** All requests for a tenant with optional filter. */
    public static function allForTenant(int $tenantId, ?string $status = null, int $limit = 200): array {
        $where  = ['cr.tenant_id = ?'];
        $params = [$tenantId];

        if ($status) {
            $where[] = 'cr.status = ?';
            $params[] = $status;
        }

        $sql = "SELECT cr.*,
                       u.name  AS user_name,  u.employee_id AS user_employee_id,
                       ru.name AS requester_name
                FROM compliance_change_requests cr
                JOIN users u  ON cr.user_id = u.id
                LEFT JOIN users ru ON cr.requester_user_id = ru.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY CASE cr.status
                  WHEN 'submitted'      THEN 1
                  WHEN 'under_review'   THEN 2
                  WHEN 'info_requested' THEN 3
                  ELSE 4 END,
                  cr.submitted_at DESC
                LIMIT ?";
        $params[] = $limit;
        return Database::fetchAll($sql, $params);
    }

    /** Pending (submitted + under_review + info_requested) count. */
    public static function pendingCount(int $tenantId): int {
        $row = Database::fetch(
            "SELECT COUNT(*) AS c FROM compliance_change_requests
             WHERE tenant_id = ? AND status IN ('submitted','under_review','info_requested')",
            [$tenantId]
        );
        return (int)($row['c'] ?? 0);
    }

    /** Change requests submitted by a user (self-service). */
    public static function mineForUser(int $userId, int $limit = 50): array {
        return Database::fetchAll(
            "SELECT * FROM compliance_change_requests
             WHERE requester_user_id = ?
             ORDER BY submitted_at DESC
             LIMIT ?",
            [$userId, $limit]
        );
    }

    /** True if a given target has an unresolved change request. */
    public static function hasPendingForTarget(int $userId, string $entity, ?int $targetId): bool {
        $sql = "SELECT 1 FROM compliance_change_requests
                WHERE user_id = ? AND target_entity = ?
                  AND status IN ('submitted','under_review','info_requested')";
        $params = [$userId, $entity];
        if ($targetId !== null) {
            $sql .= " AND target_id = ?";
            $params[] = $targetId;
        }
        $sql .= " LIMIT 1";
        return (bool) Database::fetch($sql, $params);
    }
}
