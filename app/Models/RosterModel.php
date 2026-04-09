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
            "SELECT r.*, CONCAT(u.first_name, ' ', u.last_name) AS user_name, u.employee_id
             FROM rosters r
             JOIN users u ON u.id = r.user_id
             WHERE r.tenant_id = ? AND r.roster_date BETWEEN ? AND ?
             ORDER BY u.last_name, u.first_name, r.roster_date",
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
            "SELECT DISTINCT u.id, CONCAT(u.first_name, ' ', u.last_name) AS user_name,
                    u.employee_id, r.name AS role_name
             FROM users u
             JOIN user_roles ur ON ur.user_id = u.id
             JOIN roles r ON r.id = ur.role_id
             WHERE u.tenant_id = ? AND u.status = 'active'
               AND r.slug IN ('pilot','cabin_crew','engineer','chief_pilot','head_cabin_crew')
             ORDER BY u.last_name, u.first_name",
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

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private static function isSqlite(): bool {
        return (getenv('DB_DRIVER') ?: 'mysql') === 'sqlite';
    }

    private static function nowExpr(): string {
        return self::isSqlite() ? "datetime('now')" : 'NOW()';
    }
}
