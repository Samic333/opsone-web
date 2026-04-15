<?php
/**
 * QualificationModel — type ratings, endorsements, courses, approvals
 * Linked to users; separate from licenses (which are regulatory documents).
 */
class QualificationModel {

    private static function isSqlite(): bool {
        return env('DB_DRIVER', 'mysql') === 'sqlite';
    }

    private static function currentDate(): string {
        return self::isSqlite() ? "DATE('now')" : 'CURDATE()';
    }

    private static function dateAddDays(int $days): string {
        return self::isSqlite()
            ? "DATE('now', '+{$days} days')"
            : "DATE_ADD(CURDATE(), INTERVAL {$days} DAY)";
    }

    // ─── CRUD ───────────────────────────────────────────────

    public static function forUser(int $userId): array {
        return Database::fetchAll(
            "SELECT * FROM qualifications WHERE user_id = ? ORDER BY expiry_date ASC, qual_type ASC",
            [$userId]
        );
    }

    public static function find(int $id): ?array {
        return Database::fetch("SELECT * FROM qualifications WHERE id = ?", [$id]);
    }

    public static function add(int $userId, int $tenantId, array $data): int {
        return Database::insert(
            "INSERT INTO qualifications
                (user_id, tenant_id, qual_type, qual_name, reference_no, authority, issue_date, expiry_date, status, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $userId, $tenantId,
                $data['qual_type'],
                $data['qual_name'],
                !empty($data['reference_no']) ? $data['reference_no'] : null,
                !empty($data['authority'])    ? $data['authority']    : null,
                !empty($data['issue_date'])   ? $data['issue_date']   : null,
                !empty($data['expiry_date'])  ? $data['expiry_date']  : null,
                $data['status'] ?? 'active',
                !empty($data['notes'])        ? $data['notes']        : null,
            ]
        );
    }

    public static function delete(int $id, int $userId): void {
        Database::execute(
            "DELETE FROM qualifications WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }

    // ─── Compliance queries ──────────────────────────────────

    public static function expiringForTenant(int $tenantId, int $daysAhead = 90): array {
        $today  = self::currentDate();
        $future = self::dateAddDays($daysAhead);
        return Database::fetchAll(
            "SELECT q.*, u.name as user_name, u.employee_id
             FROM qualifications q
             JOIN users u ON q.user_id = u.id
             WHERE q.tenant_id = ? AND q.expiry_date IS NOT NULL
               AND q.expiry_date BETWEEN $today AND $future
             ORDER BY q.expiry_date ASC",
            [$tenantId]
        );
    }

    public static function expiredForTenant(int $tenantId, int $limit = 20): array {
        $today = self::currentDate();
        return Database::fetchAll(
            "SELECT q.*, u.name as user_name, u.employee_id
             FROM qualifications q
             JOIN users u ON q.user_id = u.id
             WHERE q.tenant_id = ? AND q.expiry_date IS NOT NULL AND q.expiry_date < $today
             ORDER BY q.expiry_date DESC LIMIT ?",
            [$tenantId, $limit]
        );
    }

    // ─── Summary ────────────────────────────────────────────

    public static function countForUser(int $userId): int {
        $row = Database::fetch(
            "SELECT COUNT(*) as cnt FROM qualifications WHERE user_id = ?",
            [$userId]
        );
        return (int) ($row['cnt'] ?? 0);
    }
}
