<?php
/**
 * CrewDocumentModel — unified personnel document vault.
 *
 * Stores scanned/uploaded documents (passport, medical, visa, contract,
 * company ID, permits, certificates, etc.) attached to a staff member.
 * Each row has an approval lifecycle: pending_approval → valid | rejected | revoked.
 *
 * The table supports supersession (replaces_document_id) so the original
 * approved row is preserved when a new request is approved.
 */
class CrewDocumentModel {

    private static function isSqlite(): bool {
        return env('DB_DRIVER', 'mysql') === 'sqlite';
    }

    private static function currentDate(): string {
        return self::isSqlite() ? "DATE('now')" : "CURDATE()";
    }

    private static function dateAddDays(int $days): string {
        return self::isSqlite()
            ? "DATE('now', '+{$days} days')"
            : "DATE_ADD(CURDATE(), INTERVAL {$days} DAY)";
    }

    // ─── CRUD ───────────────────────────────────────────────────────────────

    public static function find(int $id): ?array {
        return Database::fetch("SELECT * FROM crew_documents WHERE id = ?", [$id]);
    }

    public static function forUser(int $userId, bool $includeSuperseded = false): array {
        $sql = "SELECT * FROM crew_documents WHERE user_id = ?";
        if (!$includeSuperseded) {
            // Exclude rows that have been replaced by a newer approved doc
            $sql .= " AND id NOT IN (SELECT replaces_document_id FROM crew_documents
                                     WHERE replaces_document_id IS NOT NULL AND status = 'valid')";
        }
        $sql .= " ORDER BY COALESCE(expiry_date, '9999-12-31') ASC, created_at DESC";
        return Database::fetchAll($sql, [$userId]);
    }

    public static function allForTenant(int $tenantId, array $filters = []): array {
        $where  = ['cd.tenant_id = ?'];
        $params = [$tenantId];

        if (!empty($filters['status'])) {
            $where[] = 'cd.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['doc_type'])) {
            $where[] = 'cd.doc_type = ?';
            $params[] = $filters['doc_type'];
        }
        if (!empty($filters['user_id'])) {
            $where[] = 'cd.user_id = ?';
            $params[] = $filters['user_id'];
        }

        $sql = "SELECT cd.*, u.name AS user_name, u.employee_id
                FROM crew_documents cd
                JOIN users u ON cd.user_id = u.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY cd.created_at DESC
                LIMIT 500";
        return Database::fetchAll($sql, $params);
    }

    public static function create(array $data): int {
        $sql = "INSERT INTO crew_documents
                (tenant_id, user_id, doc_type, doc_category, doc_title, doc_number,
                 issuing_authority, issue_date, expiry_date,
                 file_path, file_name, file_mime, file_size,
                 status, replaces_document_id, uploaded_by, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        return Database::insert($sql, [
            $data['tenant_id'],
            $data['user_id'],
            $data['doc_type'],
            $data['doc_category']       ?? null,
            $data['doc_title'],
            $data['doc_number']         ?? null,
            $data['issuing_authority']  ?? null,
            !empty($data['issue_date'])  ? $data['issue_date']  : null,
            !empty($data['expiry_date']) ? $data['expiry_date'] : null,
            $data['file_path']          ?? null,
            $data['file_name']          ?? null,
            $data['file_mime']          ?? null,
            $data['file_size']          ?? null,
            $data['status']             ?? 'pending_approval',
            $data['replaces_document_id'] ?? null,
            $data['uploaded_by']        ?? null,
            $data['notes']              ?? null,
        ]);
    }

    public static function approve(int $id, int $reviewerId): void {
        $doc = self::find($id);
        if (!$doc) return;

        Database::execute(
            "UPDATE crew_documents
             SET status = 'valid', approved_by = ?, approved_at = CURRENT_TIMESTAMP, rejection_reason = NULL
             WHERE id = ?",
            [$reviewerId, $id]
        );

        // If this supersedes an older doc, mark the previous one revoked
        if (!empty($doc['replaces_document_id'])) {
            Database::execute(
                "UPDATE crew_documents SET status = 'revoked' WHERE id = ?",
                [(int) $doc['replaces_document_id']]
            );
        }
    }

    public static function reject(int $id, int $reviewerId, string $reason): void {
        Database::execute(
            "UPDATE crew_documents
             SET status = 'rejected', approved_by = ?, approved_at = CURRENT_TIMESTAMP, rejection_reason = ?
             WHERE id = ?",
            [$reviewerId, $reason, $id]
        );
    }

    public static function revoke(int $id): void {
        Database::execute(
            "UPDATE crew_documents SET status = 'revoked' WHERE id = ?",
            [$id]
        );
    }

    // ─── Expiry queries ─────────────────────────────────────────────────────

    public static function expiringForTenant(int $tenantId, int $daysAhead = 60): array {
        $today  = self::currentDate();
        $future = self::dateAddDays($daysAhead);
        return Database::fetchAll(
            "SELECT cd.*, u.name AS user_name, u.employee_id
             FROM crew_documents cd
             JOIN users u ON cd.user_id = u.id
             WHERE cd.tenant_id = ? AND cd.status = 'valid'
               AND cd.expiry_date IS NOT NULL
               AND cd.expiry_date BETWEEN $today AND $future
             ORDER BY cd.expiry_date ASC",
            [$tenantId]
        );
    }

    public static function expiredForTenant(int $tenantId, int $limit = 100): array {
        $today = self::currentDate();
        return Database::fetchAll(
            "SELECT cd.*, u.name AS user_name, u.employee_id
             FROM crew_documents cd
             JOIN users u ON cd.user_id = u.id
             WHERE cd.tenant_id = ? AND cd.status IN ('valid','expired')
               AND cd.expiry_date IS NOT NULL AND cd.expiry_date < $today
             ORDER BY cd.expiry_date DESC
             LIMIT ?",
            [$tenantId, $limit]
        );
    }

    public static function pendingApprovalCount(int $tenantId): int {
        $row = Database::fetch(
            "SELECT COUNT(*) AS c FROM crew_documents WHERE tenant_id = ? AND status = 'pending_approval'",
            [$tenantId]
        );
        return (int)($row['c'] ?? 0);
    }
}
