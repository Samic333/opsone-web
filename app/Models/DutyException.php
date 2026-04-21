<?php
/**
 * DutyException — exception reasons + manager review for duty_reports.
 *
 * Tenant-scoped. Business logic (who can review, notifications) lives in
 * DutyReportingService.
 */
class DutyException {

    const STATUS_PENDING  = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    const REASONS = [
        'outside_geofence'    => 'Outside geo-fence',
        'gps_unavailable'     => 'GPS unavailable',
        'offline'             => 'No connectivity',
        'forgot_clock_out'    => 'Forgot to clock out',
        'wrong_base_detected' => 'Wrong base detected',
        'duplicate_attempt'   => 'Duplicate check-in attempt',
        'outstation'          => 'Reporting from out-station',
        'manual_correction'   => 'Manual correction',
        'other'               => 'Other',
    ];

    // ─── Reads ────────────────────────────────────────────────────────────────

    public static function find(int $tenantId, int $id): ?array {
        return Database::fetch(
            "SELECT * FROM duty_exceptions WHERE tenant_id = ? AND id = ?",
            [$tenantId, $id]
        ) ?: null;
    }

    public static function forReport(int $tenantId, int $reportId): array {
        return Database::fetchAll(
            "SELECT * FROM duty_exceptions
              WHERE tenant_id = ? AND duty_report_id = ?
           ORDER BY submitted_at ASC",
            [$tenantId, $reportId]
        );
    }

    /** Management queue: exceptions pending review, newest first. */
    public static function pending(int $tenantId, int $limit = 100): array {
        return Database::fetchAll(
            "SELECT dex.*, dr.user_id, dr.check_in_at_utc, dr.state AS report_state,
                    u.name AS user_name,
                    b.name AS base_name
               FROM duty_exceptions dex
               JOIN duty_reports dr ON dr.id = dex.duty_report_id
               JOIN users u ON u.id = dr.user_id
          LEFT JOIN bases b ON b.id = dr.check_in_base_id
              WHERE dex.tenant_id = ?
                AND dex.status    = 'pending'
           ORDER BY dex.submitted_at DESC
              LIMIT ?",
            [$tenantId, $limit]
        );
    }

    public static function history(
        int $tenantId,
        ?string $status = null,
        int $limit      = 200
    ): array {
        $sql = "SELECT dex.*, dr.user_id, u.name AS user_name,
                       rev.name AS reviewer_name
                  FROM duty_exceptions dex
                  JOIN duty_reports dr ON dr.id = dex.duty_report_id
                  JOIN users u   ON u.id   = dr.user_id
             LEFT JOIN users rev ON rev.id = dex.reviewed_by
                 WHERE dex.tenant_id = ?";
        $params = [$tenantId];
        if ($status) { $sql .= " AND dex.status = ?"; $params[] = $status; }
        $sql .= " ORDER BY dex.submitted_at DESC LIMIT ?";
        $params[] = $limit;
        return Database::fetchAll($sql, $params);
    }

    // ─── Writes ───────────────────────────────────────────────────────────────

    public static function create(
        int $tenantId,
        int $dutyReportId,
        int $submittedBy,
        string $reasonCode,
        ?string $reasonText
    ): int {
        return Database::insert(
            "INSERT INTO duty_exceptions
                (tenant_id, duty_report_id, submitted_by, reason_code, reason_text)
             VALUES (?, ?, ?, ?, ?)",
            [$tenantId, $dutyReportId, $submittedBy, $reasonCode, $reasonText]
        );
    }

    public static function review(
        int $tenantId,
        int $id,
        int $reviewerId,
        string $status,
        ?string $notes
    ): void {
        if (!in_array($status, [self::STATUS_APPROVED, self::STATUS_REJECTED], true)) {
            throw new \InvalidArgumentException("Invalid review status: {$status}");
        }
        Database::execute(
            "UPDATE duty_exceptions
                SET status = ?, reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP,
                    review_notes = ?, updated_at = CURRENT_TIMESTAMP
              WHERE tenant_id = ? AND id = ?",
            [$status, $reviewerId, $notes, $tenantId, $id]
        );
    }
}
