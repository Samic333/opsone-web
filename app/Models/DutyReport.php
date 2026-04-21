<?php
/**
 * DutyReport — Phase "Duty Reporting"
 *
 * Thin model layer over the `duty_reports` table.
 * Business rules (state transitions, geofence evaluation, exception triage)
 * live in app/Services/DutyReportingService.php — this file only does SQL.
 *
 * All queries are tenant-scoped. Callers must pass $tenantId.
 */
class DutyReport {

    // ─── State constants ──────────────────────────────────────────────────────

    const STATE_CHECKED_IN       = 'checked_in';
    const STATE_ON_DUTY          = 'on_duty';
    const STATE_CHECKED_OUT      = 'checked_out';
    const STATE_MISSED_REPORT    = 'missed_report';
    const STATE_EXCEPTION_PENDING  = 'exception_pending_review';
    const STATE_EXCEPTION_APPROVED = 'exception_approved';
    const STATE_EXCEPTION_REJECTED = 'exception_rejected';

    const STATES = [
        self::STATE_CHECKED_IN,
        self::STATE_ON_DUTY,
        self::STATE_CHECKED_OUT,
        self::STATE_MISSED_REPORT,
        self::STATE_EXCEPTION_PENDING,
        self::STATE_EXCEPTION_APPROVED,
        self::STATE_EXCEPTION_REJECTED,
    ];

    const STATES_OPEN = [
        self::STATE_CHECKED_IN,
        self::STATE_ON_DUTY,
        self::STATE_EXCEPTION_PENDING,
    ];

    const METHOD_DEVICE          = 'device';
    const METHOD_BIOMETRIC       = 'biometric';
    const METHOD_MANUAL          = 'manual';
    const METHOD_OFFLINE_QUEUE   = 'offline_queue';
    const METHOD_ADMIN_CORRECTED = 'admin_corrected';

    // ─── Reads ────────────────────────────────────────────────────────────────

    public static function find(int $tenantId, int $id): ?array {
        return Database::fetch(
            "SELECT * FROM duty_reports WHERE tenant_id = ? AND id = ?",
            [$tenantId, $id]
        ) ?: null;
    }

    /** The user's active (open) duty record, if any. */
    public static function findOpenForUser(int $tenantId, int $userId): ?array {
        return Database::fetch(
            "SELECT * FROM duty_reports
              WHERE tenant_id = ? AND user_id = ?
                AND state IN ('checked_in','on_duty','exception_pending_review')
           ORDER BY check_in_at_utc DESC
              LIMIT 1",
            [$tenantId, $userId]
        ) ?: null;
    }

    public static function historyForUser(int $tenantId, int $userId, int $limit = 30): array {
        return Database::fetchAll(
            "SELECT * FROM duty_reports
              WHERE tenant_id = ? AND user_id = ?
           ORDER BY COALESCE(check_in_at_utc, created_at) DESC
              LIMIT ?",
            [$tenantId, $userId, $limit]
        );
    }

    /**
     * All currently on-duty records for a tenant (management "On Duty Now" view).
     * Joins users + bases so the admin list can show name, role, and base.
     */
    public static function onDutyNow(int $tenantId): array {
        return Database::fetchAll(
            "SELECT dr.*, u.name AS user_name, u.email AS user_email,
                    b.name AS base_name, b.code AS base_code
               FROM duty_reports dr
               JOIN users u ON u.id = dr.user_id
          LEFT JOIN bases b ON b.id = dr.check_in_base_id
              WHERE dr.tenant_id = ?
                AND dr.state IN ('checked_in','on_duty')
           ORDER BY dr.check_in_at_utc DESC",
            [$tenantId]
        );
    }

    /**
     * Management history view with optional date range / role / user filters.
     */
    public static function history(
        int $tenantId,
        ?string $fromDate = null,
        ?string $toDate   = null,
        ?string $roleSlug = null,
        ?int    $userId   = null,
        int $limit        = 200
    ): array {
        $sql = "SELECT dr.*, u.name AS user_name, b.name AS base_name, b.code AS base_code
                  FROM duty_reports dr
                  JOIN users u ON u.id = dr.user_id
             LEFT JOIN bases b ON b.id = dr.check_in_base_id
                 WHERE dr.tenant_id = ?";
        $params = [$tenantId];

        if ($fromDate) { $sql .= " AND DATE(dr.check_in_at_utc) >= ?"; $params[] = $fromDate; }
        if ($toDate)   { $sql .= " AND DATE(dr.check_in_at_utc) <= ?"; $params[] = $toDate; }
        if ($roleSlug) { $sql .= " AND dr.role_at_event = ?";          $params[] = $roleSlug; }
        if ($userId)   { $sql .= " AND dr.user_id = ?";                $params[] = $userId; }

        $sql .= " ORDER BY dr.check_in_at_utc DESC LIMIT ?";
        $params[] = $limit;

        return Database::fetchAll($sql, $params);
    }

    /**
     * Open records whose check-in is older than $thresholdMinutes and that
     * have not yet been clocked out — used for overdue clock-out detection.
     */
    public static function overdueClockOuts(int $tenantId, int $thresholdMinutes): array {
        return Database::fetchAll(
            "SELECT dr.*, u.name AS user_name
               FROM duty_reports dr
               JOIN users u ON u.id = dr.user_id
              WHERE dr.tenant_id = ?
                AND dr.state IN ('checked_in','on_duty')
                AND dr.check_in_at_utc < DATETIME('now', ? )",
            [$tenantId, "-{$thresholdMinutes} minutes"]
        );
    }

    // ─── Writes ───────────────────────────────────────────────────────────────

    /**
     * Insert a new check-in row. Returns the new id.
     * Caller (DutyReportingService) is responsible for state selection and
     * audit logging.
     *
     * @param array $data  Must contain: tenant_id, user_id. May contain:
     *                     role_at_event, state, check_in_at_utc, check_in_at_local,
     *                     check_in_lat, check_in_lng, check_in_base_id, check_in_method,
     *                     inside_geofence, trusted_device_id, roster_id, device_uuid, notes
     */
    public static function createCheckIn(array $data): int {
        return Database::insert(
            "INSERT INTO duty_reports
                (tenant_id, user_id, role_at_event, state,
                 check_in_at_utc, check_in_at_local,
                 check_in_lat, check_in_lng, check_in_base_id, check_in_method,
                 inside_geofence, trusted_device_id, roster_id, device_uuid, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['tenant_id'],
                $data['user_id'],
                $data['role_at_event']     ?? null,
                $data['state']             ?? self::STATE_CHECKED_IN,
                $data['check_in_at_utc']   ?? gmdate('Y-m-d H:i:s'),
                $data['check_in_at_local'] ?? null,
                $data['check_in_lat']      ?? null,
                $data['check_in_lng']      ?? null,
                $data['check_in_base_id']  ?? null,
                $data['check_in_method']   ?? self::METHOD_DEVICE,
                isset($data['inside_geofence']) ? (int) $data['inside_geofence'] : null,
                $data['trusted_device_id'] ?? null,
                $data['roster_id']         ?? null,
                $data['device_uuid']       ?? null,
                $data['notes']             ?? null,
            ]
        );
    }

    /**
     * Update a duty record with clock-out information. Duration is computed
     * in minutes between check-in and check-out UTC timestamps.
     */
    public static function recordCheckOut(
        int $tenantId,
        int $id,
        array $data
    ): void {
        $existing = self::find($tenantId, $id);
        if (!$existing) return;

        $inUtc  = $existing['check_in_at_utc'];
        $outUtc = $data['check_out_at_utc'] ?? gmdate('Y-m-d H:i:s');
        $dur    = null;
        if ($inUtc) {
            $dur = (int) floor((strtotime($outUtc) - strtotime($inUtc)) / 60);
            if ($dur < 0) $dur = 0;
        }

        Database::execute(
            "UPDATE duty_reports
                SET check_out_at_utc   = ?,
                    check_out_at_local = ?,
                    check_out_lat      = ?,
                    check_out_lng      = ?,
                    duration_minutes   = ?,
                    state              = ?,
                    notes              = COALESCE(?, notes),
                    updated_at         = CURRENT_TIMESTAMP
              WHERE tenant_id = ? AND id = ?",
            [
                $outUtc,
                $data['check_out_at_local'] ?? null,
                $data['check_out_lat']      ?? null,
                $data['check_out_lng']      ?? null,
                $dur,
                $data['state']              ?? self::STATE_CHECKED_OUT,
                $data['notes']              ?? null,
                $tenantId,
                $id,
            ]
        );
    }

    public static function setState(int $tenantId, int $id, string $state): void {
        Database::execute(
            "UPDATE duty_reports
                SET state = ?, updated_at = CURRENT_TIMESTAMP
              WHERE tenant_id = ? AND id = ?",
            [$state, $tenantId, $id]
        );
    }

    /**
     * Admin correction path — overwrites arbitrary fields, marks method
     * admin_corrected, appends correction note. Caller must audit.
     */
    public static function adminCorrect(int $tenantId, int $id, array $fields, string $note): void {
        $allowed = [
            'check_in_at_utc','check_in_at_local','check_in_base_id',
            'check_out_at_utc','check_out_at_local','duration_minutes','state',
        ];
        $sets = [];
        $params = [];
        foreach ($fields as $k => $v) {
            if (!in_array($k, $allowed, true)) continue;
            $sets[] = "{$k} = ?";
            $params[] = $v;
        }
        if (empty($sets)) return;

        $sets[] = "check_in_method = 'admin_corrected'";
        $sets[] = "notes = CASE WHEN notes IS NULL OR notes = '' THEN ? ELSE notes || CHAR(10) || ? END";
        $sets[] = "updated_at = CURRENT_TIMESTAMP";
        $params[] = "[admin correction] " . $note;
        $params[] = "[admin correction] " . $note;
        $params[] = $tenantId;
        $params[] = $id;

        Database::execute(
            "UPDATE duty_reports SET " . implode(', ', $sets) .
            " WHERE tenant_id = ? AND id = ?",
            $params
        );
    }

    // ─── Aggregates (for admin dashboard) ─────────────────────────────────────

    /**
     * Dashboard tiles: on-duty count, checked-in today, checked-out today,
     * overdue (>threshold), pending exceptions.
     */
    public static function counters(int $tenantId, int $overdueMinutes): array {
        $today = date('Y-m-d');

        $on = Database::fetch(
            "SELECT COUNT(*) AS c FROM duty_reports
              WHERE tenant_id = ? AND state IN ('checked_in','on_duty')",
            [$tenantId]
        );
        $inToday = Database::fetch(
            "SELECT COUNT(*) AS c FROM duty_reports
              WHERE tenant_id = ? AND DATE(check_in_at_utc) = ?",
            [$tenantId, $today]
        );
        $outToday = Database::fetch(
            "SELECT COUNT(*) AS c FROM duty_reports
              WHERE tenant_id = ? AND DATE(check_out_at_utc) = ?",
            [$tenantId, $today]
        );
        $overdue = Database::fetch(
            "SELECT COUNT(*) AS c FROM duty_reports
              WHERE tenant_id = ?
                AND state IN ('checked_in','on_duty')
                AND check_in_at_utc < DATETIME('now', ?)",
            [$tenantId, "-{$overdueMinutes} minutes"]
        );
        $pendingEx = Database::fetch(
            "SELECT COUNT(*) AS c FROM duty_exceptions
              WHERE tenant_id = ? AND status = 'pending'",
            [$tenantId]
        );

        return [
            'on_duty_now'        => (int) ($on['c']         ?? 0),
            'checked_in_today'   => (int) ($inToday['c']    ?? 0),
            'checked_out_today'  => (int) ($outToday['c']   ?? 0),
            'overdue_clock_out'  => (int) ($overdue['c']    ?? 0),
            'exceptions_pending' => (int) ($pendingEx['c']  ?? 0),
        ];
    }
}
