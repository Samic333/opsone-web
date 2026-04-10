<?php
/**
 * RosterModel — crew roster CRUD + monthly grid queries
 */
class RosterModel {

    // ─── Duty type metadata ───────────────────────────────────────────────────

    public static function dutyTypes(): array {
        return [
            'flight'   => ['label' => 'Flight',    'color' => '#3b82f6', 'code' => 'FLT'],
            'standby'  => ['label' => 'Standby',   'color' => '#f59e0b', 'code' => 'SBY'],
            'off'      => ['label' => 'Day Off',   'color' => '#6b7280', 'code' => 'OFF'],
            'training' => ['label' => 'Training',  'color' => '#8b5cf6', 'code' => 'TRN'],
            'sim'      => ['label' => 'Simulator', 'color' => '#06b6d4', 'code' => 'SIM'],
            'leave'    => ['label' => 'Leave',     'color' => '#10b981', 'code' => 'LVE'],
            'rest'     => ['label' => 'Rest',      'color' => '#9ca3af', 'code' => 'RST'],
        ];
    }

    // ─── Queries ──────────────────────────────────────────────────────────────

    /**
     * All roster entries for a given tenant + month, joined with user name.
     * Returns rows keyed by user_id and date for easy grid rendering.
     */
    public static function getMonth(int $tenantId, int $year, int $month): array {
        $from = sprintf('%04d-%02d-01', $year, $month);
        $days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $to   = sprintf('%04d-%02d-%02d', $year, $month, $days);

        $rows = Database::fetchAll(
            "SELECT r.*, u.name AS user_name, u.employee_id
             FROM rosters r
             JOIN users u ON u.id = r.user_id
             WHERE r.tenant_id = ? AND r.roster_date BETWEEN ? AND ?
             ORDER BY u.name, r.roster_date",
            [$tenantId, $from, $to]
        );

        // Index by [user_id][date]
        $grid = [];
        foreach ($rows as $row) {
            $grid[$row['user_id']]['_meta'] = [
                'user_name'   => $row['user_name'],
                'employee_id' => $row['employee_id'],
            ];
            $grid[$row['user_id']][$row['roster_date']] = $row;
        }
        return $grid;
    }

    /**
     * Roster entries for a specific user + month (for iPad API).
     */
    public static function getUserMonth(int $userId, int $year, int $month): array {
        $from = sprintf('%04d-%02d-01', $year, $month);
        $days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $to   = sprintf('%04d-%02d-%02d', $year, $month, $days);

        return Database::fetchAll(
            "SELECT * FROM rosters
             WHERE user_id = ? AND roster_date BETWEEN ? AND ?
             ORDER BY roster_date",
            [$userId, $from, $to]
        );
    }

    /**
     * All crew who have ANY roster entry for this tenant (for the crew list in grid).
     * Also returns crew with NO entries so the scheduler can assign them.
     */
    public static function getCrewList(int $tenantId): array {
        return Database::fetchAll(
            "SELECT DISTINCT u.id, u.name AS user_name,
                    u.employee_id, r.name AS role_name
             FROM users u
             JOIN user_roles ur ON ur.user_id = u.id
             JOIN roles r ON r.id = ur.role_id
             WHERE u.tenant_id = ? AND u.status = 'active'
               AND r.slug IN ('pilot','cabin_crew','engineer','chief_pilot','head_cabin_crew')
             ORDER BY u.name",
            [$tenantId]
        );
    }

    // ─── CRUD ─────────────────────────────────────────────────────────────────

    public static function upsert(int $tenantId, int $userId, string $date, string $dutyType, ?string $dutyCode, ?string $notes): void {
        $existing = Database::fetch(
            "SELECT id FROM rosters WHERE user_id = ? AND roster_date = ?",
            [$userId, $date]
        );

        if ($existing) {
            Database::execute(
                "UPDATE rosters SET duty_type = ?, duty_code = ?, notes = ?, updated_at = " . self::nowExpr() . "
                 WHERE id = ?",
                [$dutyType, $dutyCode ?: null, $notes ?: null, $existing['id']]
            );
        } else {
            Database::execute(
                "INSERT INTO rosters (tenant_id, user_id, roster_date, duty_type, duty_code, notes)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$tenantId, $userId, $date, $dutyType, $dutyCode ?: null, $notes ?: null]
            );
        }
    }

    public static function delete(int $id, int $tenantId): void {
        Database::execute("DELETE FROM rosters WHERE id = ? AND tenant_id = ?", [$id, $tenantId]);
    }

    // ─── Phase 6: Standby pool + replacement suggestions ─────────────────────

    /**
     * Returns compliance issues keyed by user_id for a given tenant.
     * Each entry: ['issues' => [string,...], 'severity' => 'critical'|'warning']
     * critical = expired; warning = expiring within 30 days.
     */
    public static function getComplianceIssues(int $tenantId): array {
        $today   = self::isSqlite() ? "DATE('now')" : 'CURDATE()';
        $soon    = self::isSqlite() ? "DATE('now', '+30 days')" : 'DATE_ADD(CURDATE(), INTERVAL 30 DAY)';

        $flags = [];

        // Expired licenses
        $expiredLic = Database::fetchAll(
            "SELECT l.user_id, l.license_type, l.expiry_date
             FROM licenses l
             WHERE l.tenant_id = ? AND l.expiry_date IS NOT NULL AND l.expiry_date < $today",
            [$tenantId]
        );
        foreach ($expiredLic as $row) {
            $uid = $row['user_id'];
            $flags[$uid]['severity'] = 'critical';
            $flags[$uid]['issues'][] = "License expired: {$row['license_type']} ({$row['expiry_date']})";
        }

        // Licenses expiring within 30 days
        $soonLic = Database::fetchAll(
            "SELECT l.user_id, l.license_type, l.expiry_date
             FROM licenses l
             WHERE l.tenant_id = ? AND l.expiry_date IS NOT NULL
               AND l.expiry_date >= $today AND l.expiry_date <= $soon",
            [$tenantId]
        );
        foreach ($soonLic as $row) {
            $uid = $row['user_id'];
            if (!isset($flags[$uid])) {
                $flags[$uid]['severity'] = 'warning';
                $flags[$uid]['issues']   = [];
            }
            $flags[$uid]['issues'][] = "License expiring: {$row['license_type']} ({$row['expiry_date']})";
        }

        // Expired medicals
        $expMed = Database::fetchAll(
            "SELECT cp.user_id, cp.medical_expiry
             FROM crew_profiles cp
             WHERE cp.tenant_id = ? AND cp.medical_expiry IS NOT NULL AND cp.medical_expiry < $today",
            [$tenantId]
        );
        foreach ($expMed as $row) {
            $uid = $row['user_id'];
            $flags[$uid]['severity'] = 'critical';
            $flags[$uid]['issues'][] = "Medical expired ({$row['medical_expiry']})";
        }

        // Medicals expiring within 30 days
        $soonMed = Database::fetchAll(
            "SELECT cp.user_id, cp.medical_expiry
             FROM crew_profiles cp
             WHERE cp.tenant_id = ? AND cp.medical_expiry IS NOT NULL
               AND cp.medical_expiry >= $today AND cp.medical_expiry <= $soon",
            [$tenantId]
        );
        foreach ($soonMed as $row) {
            $uid = $row['user_id'];
            if (!isset($flags[$uid])) {
                $flags[$uid]['severity'] = 'warning';
                $flags[$uid]['issues']   = [];
            }
            $flags[$uid]['issues'][] = "Medical expiring ({$row['medical_expiry']})";
        }

        return $flags;
    }

    /**
     * Standby pool for a given date — crew rostered as 'standby' on that date,
     * with their compliance status attached.
     */
    public static function getStandbyPool(int $tenantId, string $date): array {
        $rows = Database::fetchAll(
            "SELECT r.id AS roster_id, r.user_id, r.duty_code, r.notes,
                    u.name AS user_name, u.employee_id,
                    ro.name AS role_name, ro.slug AS role_slug
             FROM rosters r
             JOIN users u ON u.id = r.user_id
             JOIN user_roles ur ON ur.user_id = u.id
             JOIN roles ro ON ro.id = ur.role_id
             WHERE r.tenant_id = ? AND r.roster_date = ? AND r.duty_type = 'standby'
               AND u.status = 'active'
               AND ro.slug IN ('pilot','cabin_crew','engineer','chief_pilot','head_cabin_crew')
             ORDER BY ro.slug, u.name",
            [$tenantId, $date]
        );

        $complianceFlags = self::getComplianceIssues($tenantId);
        foreach ($rows as &$row) {
            $uid = $row['user_id'];
            $row['compliance'] = $complianceFlags[$uid] ?? null;
        }
        unset($row);
        return $rows;
    }

    /**
     * Suggest replacement crew for a given date.
     * Finds crew who are on standby (preferred) or off/rest (fallback),
     * filtered to those with no critical compliance issues.
     * Excludes the $excludeUserId (the person being replaced).
     * Returns results grouped: standby first, then off/rest.
     */
    public static function suggestReplacements(int $tenantId, string $date, int $excludeUserId): array {
        // All active crew with their duty on the given date (LEFT JOIN = includes unrostered)
        $rows = Database::fetchAll(
            "SELECT u.id AS user_id, u.name AS user_name, u.employee_id,
                    ro.name AS role_name, ro.slug AS role_slug,
                    COALESCE(r.duty_type, 'unrostered') AS duty_type,
                    r.duty_code, r.notes AS duty_notes
             FROM users u
             JOIN user_roles ur ON ur.user_id = u.id
             JOIN roles ro ON ro.id = ur.role_id
             LEFT JOIN rosters r ON r.user_id = u.id AND r.tenant_id = ? AND r.roster_date = ?
             WHERE u.tenant_id = ? AND u.status = 'active'
               AND u.id != ?
               AND ro.slug IN ('pilot','cabin_crew','engineer','chief_pilot','head_cabin_crew')
             ORDER BY ro.slug, u.name",
            [$tenantId, $date, $tenantId, $excludeUserId]
        );

        $complianceFlags = self::getComplianceIssues($tenantId);
        $standby  = [];
        $available = [];

        foreach ($rows as $row) {
            $uid = $row['user_id'];
            $compliance = $complianceFlags[$uid] ?? null;
            // Skip crew with critical issues (expired documents)
            if ($compliance && $compliance['severity'] === 'critical') {
                continue;
            }
            $row['compliance'] = $compliance;

            if ($row['duty_type'] === 'standby') {
                $standby[] = $row;
            } elseif (in_array($row['duty_type'], ['off', 'rest', 'unrostered'])) {
                $available[] = $row;
            }
        }

        return ['standby' => $standby, 'available' => $available];
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private static function isSqlite(): bool {
        return (getenv('DB_DRIVER') ?: 'mysql') === 'sqlite';
    }

    private static function nowExpr(): string {
        return self::isSqlite() ? "datetime('now')" : 'NOW()';
    }
}
