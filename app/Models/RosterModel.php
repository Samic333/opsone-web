<?php
/**
 * RosterModel — crew roster CRUD + monthly grid queries
 */
class RosterModel {

    // ─── Duty type metadata ───────────────────────────────────────────────────

    public static function dutyTypes(): array {
        return [
            'flight'    => ['label' => 'Flight',       'color' => '#2563eb', 'bg' => '#dbeafe', 'code' => 'FLT', 'group' => 'flying'],
            'standby'   => ['label' => 'Standby',      'color' => '#d97706', 'bg' => '#fef3c7', 'code' => 'SBY', 'group' => 'standby'],
            'reserve'   => ['label' => 'Reserve',      'color' => '#b45309', 'bg' => '#fde68a', 'code' => 'RES', 'group' => 'standby'],
            'off'       => ['label' => 'Day Off',      'color' => '#6b7280', 'bg' => '#f3f4f6', 'code' => 'OFF', 'group' => 'off'],
            'training'  => ['label' => 'Training',     'color' => '#7c3aed', 'bg' => '#ede9fe', 'code' => 'TRN', 'group' => 'training'],
            'sim'       => ['label' => 'Simulator',    'color' => '#0891b2', 'bg' => '#cffafe', 'code' => 'SIM', 'group' => 'training'],
            'check'     => ['label' => 'Check',        'color' => '#be185d', 'bg' => '#fce7f3', 'code' => 'CHK', 'group' => 'training'],
            'leave'     => ['label' => 'Leave',        'color' => '#059669', 'bg' => '#d1fae5', 'code' => 'LVE', 'group' => 'leave'],
            'sick'      => ['label' => 'Sick Leave',   'color' => '#dc2626', 'bg' => '#fee2e2', 'code' => 'SCK', 'group' => 'leave'],
            'rest'      => ['label' => 'Rest Day',     'color' => '#9ca3af', 'bg' => '#f9fafb', 'code' => 'RST', 'group' => 'off'],
            'admin'     => ['label' => 'Admin Duty',   'color' => '#64748b', 'bg' => '#f1f5f9', 'code' => 'ADM', 'group' => 'ground'],
            'pos'       => ['label' => 'Positioning',  'color' => '#0284c7', 'bg' => '#e0f2fe', 'code' => 'POS', 'group' => 'flying'],
            'deadhead'  => ['label' => 'Deadhead',     'color' => '#0369a1', 'bg' => '#bae6fd', 'code' => 'DH',  'group' => 'flying'],
            'maint'     => ['label' => 'Maint Duty',   'color' => '#92400e', 'bg' => '#fef9c3', 'code' => 'MNT', 'group' => 'ground'],
            'base_duty' => ['label' => 'Base Duty',    'color' => '#065f46', 'bg' => '#d1fae5', 'code' => 'BASE','group' => 'ground'],
        ];
    }

    /**
     * Duty types grouped for UI selector display.
     */
    public static function dutyTypeGroups(): array {
        $groups = [];
        foreach (self::dutyTypes() as $key => $dt) {
            $groups[$dt['group']][$key] = $dt;
        }
        return $groups;
    }

    // ─── Queries ──────────────────────────────────────────────────────────────

    /**
     * All roster entries for a given tenant + month, joined with user name.
     * Returns rows keyed by user_id and date for easy grid rendering.
     */
    public static function getMonth(int $tenantId, int $year, int $month, ?int $baseId = null, ?string $roleSlug = null): array {
        $from = sprintf('%04d-%02d-01', $year, $month);
        $days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $to   = sprintf('%04d-%02d-%02d', $year, $month, $days);

        $sql = "SELECT r.*, u.name AS user_name, u.employee_id
                FROM rosters r
                JOIN users u ON u.id = r.user_id ";
                
        if ($roleSlug) {
            $sql .= " JOIN user_roles ur ON ur.user_id = u.id JOIN roles rl ON rl.id = ur.role_id ";
        }
        
        $sql .= " WHERE r.tenant_id = ? AND r.roster_date BETWEEN ? AND ? ";
        $params = [$tenantId, $from, $to];
        
        if ($baseId) {
            $sql .= " AND u.base_id = ? ";
            $params[] = $baseId;
        }
        
        if ($roleSlug) {
            $sql .= " AND rl.slug = ? ";
            $params[] = $roleSlug;
        }
        
        $sql .= " ORDER BY u.name, r.roster_date";

        $rows = Database::fetchAll($sql, $params);

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
            "SELECT r.* FROM rosters r
             LEFT JOIN roster_periods p ON p.id = r.roster_period_id
             WHERE r.user_id = ? AND r.roster_date BETWEEN ? AND ?
               AND (r.roster_period_id IS NULL OR p.status != 'draft')
             ORDER BY r.roster_date",
            [$userId, $from, $to]
        );
    }

    /**
     * All crew who have ANY roster entry for this tenant (for the crew list in grid).
     * Also returns crew with NO entries so the scheduler can assign them.
     */
    public static function getCrewList(int $tenantId, ?int $baseId = null, ?string $roleSlug = null): array {
        $sql = "SELECT DISTINCT u.id, u.name AS user_name,
                    u.employee_id, u.base_id, r.name AS role_name, r.slug AS role_slug
             FROM users u
             JOIN user_roles ur ON ur.user_id = u.id
             JOIN roles r ON r.id = ur.role_id
             WHERE u.tenant_id = ? AND u.status = 'active'
               AND r.slug IN ('pilot','cabin_crew','engineer','chief_pilot','head_cabin_crew')";
               
        $params = [$tenantId];

        if ($baseId) {
            $sql .= " AND u.base_id = ?";
            $params[] = $baseId;
        }

        if ($roleSlug) {
            $sql .= " AND r.slug = ?";
            $params[] = $roleSlug;
        }

        $sql .= " ORDER BY u.name";
        return Database::fetchAll($sql, $params);
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

        // Expired Qualifications (Training/Checks)
        $expiredQuals = QualificationModel::expiredForTenant($tenantId, 1000);
        foreach ($expiredQuals as $row) {
            $uid = $row['user_id'];
            $flags[$uid]['severity'] = 'critical';
            $flags[$uid]['issues'][] = "Expired Training/Check: {$row['qual_name']} ({$row['expiry_date']})";
        }

        // Qualifications expiring within 30 days
        $soonQuals = QualificationModel::expiringForTenant($tenantId, 30);
        foreach ($soonQuals as $row) {
            $uid = $row['user_id'];
            if (!isset($flags[$uid])) {
                $flags[$uid]['severity'] = 'warning';
                $flags[$uid]['issues']   = [];
            }
            // avoid appending if already marked critical (though severity would upgrade if we re-assign, but here we just append info)
            if (($flags[$uid]['severity'] ?? '') !== 'critical') {
                $flags[$uid]['severity'] = 'warning';
            }
            $flags[$uid]['issues'][] = "Training/Check Expiring: {$row['qual_name']} ({$row['expiry_date']})";
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

    // ─── Phase 5: Roster Periods ─────────────────────────────────────────────

    public static function getPeriods(int $tenantId): array {
        return Database::fetchAll(
            "SELECT p.*, u.name AS created_by_name
             FROM roster_periods p
             LEFT JOIN users u ON u.id = p.created_by
             WHERE p.tenant_id = ?
             ORDER BY p.start_date DESC",
            [$tenantId]
        );
    }

    public static function getPeriod(int $id, int $tenantId): ?array {
        return Database::fetch(
            "SELECT * FROM roster_periods WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        ) ?: null;
    }

    public static function createPeriod(int $tenantId, string $name, string $startDate, string $endDate, ?string $notes, int $createdBy): int {
        Database::execute(
            "INSERT INTO roster_periods (tenant_id, name, start_date, end_date, notes, created_by)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$tenantId, $name, $startDate, $endDate, $notes ?: null, $createdBy]
        );
        return (int)Database::lastInsertId();
    }

    public static function updatePeriodStatus(int $id, int $tenantId, string $status): void {
        Database::execute(
            "UPDATE roster_periods SET status = ?, updated_at = " . self::nowExpr() . " WHERE id = ? AND tenant_id = ?",
            [$status, $id, $tenantId]
        );
    }

    public static function updatePeriod(int $id, int $tenantId, string $name, string $startDate, string $endDate, ?string $notes): void {
        Database::execute(
            "UPDATE roster_periods SET name = ?, start_date = ?, end_date = ?, notes = ?, updated_at = " . self::nowExpr() . "
             WHERE id = ? AND tenant_id = ? AND status = 'draft'",
            [$name, $startDate, $endDate, $notes ?: null, $id, $tenantId]
        );
    }

    public static function deletePeriod(int $id, int $tenantId): void {
        // Only draft periods can be deleted
        Database::execute(
            "DELETE FROM roster_periods WHERE id = ? AND tenant_id = ? AND status = 'draft'",
            [$id, $tenantId]
        );
    }

    /**
     * Get the active/most recent published period for a tenant.
     */
    public static function getActivePeriod(int $tenantId): ?array {
        return Database::fetch(
            "SELECT * FROM roster_periods
             WHERE tenant_id = ? AND status IN ('published','frozen')
             ORDER BY start_date DESC LIMIT 1",
            [$tenantId]
        ) ?: null;
    }

    // ─── Phase 5: Bulk Assign ─────────────────────────────────────────────────

    /**
     * Assign a duty type to one or more crew members across a date range.
     * Skips existing entries unless $overwrite is true.
     * Returns the count of entries written.
     */
    public static function bulkAssign(
        int $tenantId,
        array $userIds,
        string $fromDate,
        string $toDate,
        string $dutyType,
        ?string $dutyCode,
        ?string $notes,
        bool $overwrite = false,
        ?int $periodId = null
    ): int {
        $current = new \DateTime($fromDate);
        $end     = new \DateTime($toDate);
        $count   = 0;

        while ($current <= $end) {
            $date = $current->format('Y-m-d');
            foreach ($userIds as $userId) {
                $userId = (int)$userId;
                $existing = Database::fetch(
                    "SELECT id FROM rosters WHERE user_id = ? AND roster_date = ?",
                    [$userId, $date]
                );
                if ($existing) {
                    if ($overwrite) {
                        Database::execute(
                            "UPDATE rosters SET duty_type = ?, duty_code = ?, notes = ?, roster_period_id = ?, updated_at = " . self::nowExpr() . "
                             WHERE id = ?",
                            [$dutyType, $dutyCode ?: null, $notes ?: null, $periodId, $existing['id']]
                        );
                        $count++;
                    }
                } else {
                    Database::execute(
                        "INSERT INTO rosters (tenant_id, user_id, roster_date, duty_type, duty_code, notes, roster_period_id)
                         VALUES (?, ?, ?, ?, ?, ?, ?)",
                        [$tenantId, $userId, $date, $dutyType, $dutyCode ?: null, $notes ?: null, $periodId]
                    );
                    $count++;
                }
            }
            $current->modify('+1 day');
        }
        return $count;
    }

    // ─── Phase 5: Change Requests / Comments ──────────────────────────────────

    public static function createChangeRequest(
        int $tenantId,
        int $userId,
        int $requestedBy,
        string $changeType,
        string $message,
        ?int $periodId = null,
        ?int $rosterId = null
    ): void {
        Database::execute(
            "INSERT INTO roster_changes (tenant_id, roster_period_id, roster_id, user_id, requested_by, change_type, message)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$tenantId, $periodId, $rosterId, $userId, $requestedBy, $changeType, $message]
        );
    }

    public static function getChangesForPeriod(int $tenantId, int $periodId): array {
        return Database::fetchAll(
            "SELECT rc.*, u.name AS user_name, u.employee_id,
                    rb.name AS responded_by_name
             FROM roster_changes rc
             JOIN users u ON u.id = rc.user_id
             LEFT JOIN users rb ON rb.id = rc.responded_by
             WHERE rc.tenant_id = ? AND rc.roster_period_id = ?
             ORDER BY rc.created_at DESC",
            [$tenantId, $periodId]
        );
    }

    public static function getPendingChanges(int $tenantId): array {
        return Database::fetchAll(
            "SELECT rc.*, u.name AS user_name, u.employee_id,
                    p.name AS period_name
             FROM roster_changes rc
             JOIN users u ON u.id = rc.user_id
             LEFT JOIN roster_periods p ON p.id = rc.roster_period_id
             WHERE rc.tenant_id = ? AND rc.status = 'pending'
             ORDER BY rc.created_at ASC",
            [$tenantId]
        );
    }

    public static function respondToChange(int $id, int $tenantId, string $status, string $response, int $respondedBy): void {
        Database::execute(
            "UPDATE roster_changes
             SET status = ?, response = ?, responded_by = ?, responded_at = " . self::nowExpr() . ", updated_at = " . self::nowExpr() . "
             WHERE id = ? AND tenant_id = ?",
            [$status, $response, $respondedBy, $id, $tenantId]
        );
    }

    public static function getChangeRequest(int $id, int $tenantId): ?array {
        return Database::fetch(
            "SELECT rc.*, u.name AS user_name FROM roster_changes rc
             JOIN users u ON u.id = rc.user_id
             WHERE rc.id = ? AND rc.tenant_id = ?",
            [$id, $tenantId]
        ) ?: null;
    }

    // ─── Roster Revisions ────────────────────────────────────────────────────

    /**
     * Create a new revision bundle.
     */
    public static function createRevision(
        int $tenantId,
        ?int $periodId,
        string $revisionRef,
        string $reason,
        string $changeSource,
        int $requestedBy,
        ?string $notes = null
    ): int {
        Database::execute(
            "INSERT INTO roster_revisions
             (tenant_id, roster_period_id, revision_ref, reason, change_source, requested_by, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$tenantId, $periodId, $revisionRef, $reason, $changeSource, $requestedBy, $notes]
        );
        return (int)Database::lastInsertId();
    }

    /**
     * Add individual duty change items to a revision.
     */
    public static function addRevisionItem(
        int $revisionId,
        int $tenantId,
        int $userId,
        string $date,
        ?string $oldDuty,
        ?string $oldCode,
        ?string $newDuty,
        ?string $newCode,
        ?string $note = null
    ): void {
        Database::execute(
            "INSERT INTO roster_revision_items
             (roster_revision_id, tenant_id, user_id, roster_date,
              old_duty_type, old_duty_code, new_duty_type, new_duty_code, change_note)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$revisionId, $tenantId, $userId, $date, $oldDuty, $oldCode, $newDuty, $newCode, $note]
        );
    }

    /**
     * Issue (publish) a revision, marking it as sent to crew.
     */
    public static function issueRevision(int $id, int $tenantId, int $approvedBy): void {
        Database::execute(
            "UPDATE roster_revisions
             SET status = 'issued', approved_by = ?, approved_at = " . self::nowExpr() . ",
                 issued_at = " . self::nowExpr() . ", updated_at = " . self::nowExpr() . "
             WHERE id = ? AND tenant_id = ?",
            [$approvedBy, $id, $tenantId]
        );
    }

    /**
     * Get all revisions for a tenant, with period name and creator.
     */
    public static function getRevisions(int $tenantId, ?int $periodId = null): array {
        $sql = "SELECT rv.*, rp.name AS period_name,
                       u.name AS requested_by_name, ua.name AS approved_by_name,
                       COUNT(rvi.id) AS item_count
                FROM roster_revisions rv
                LEFT JOIN roster_periods rp ON rp.id = rv.roster_period_id
                LEFT JOIN users u  ON u.id  = rv.requested_by
                LEFT JOIN users ua ON ua.id = rv.approved_by
                LEFT JOIN roster_revision_items rvi ON rvi.roster_revision_id = rv.id
                WHERE rv.tenant_id = ?";
        $params = [$tenantId];
        if ($periodId) {
            $sql .= " AND rv.roster_period_id = ?";
            $params[] = $periodId;
        }
        $sql .= " GROUP BY rv.id ORDER BY rv.created_at DESC";
        return Database::fetchAll($sql, $params);
    }

    /**
     * Get a single revision with its items.
     */
    public static function getRevision(int $id, int $tenantId): ?array {
        $rev = Database::fetch(
            "SELECT rv.*, rp.name AS period_name, u.name AS requested_by_name
             FROM roster_revisions rv
             LEFT JOIN roster_periods rp ON rp.id = rv.roster_period_id
             LEFT JOIN users u ON u.id = rv.requested_by
             WHERE rv.id = ? AND rv.tenant_id = ?",
            [$id, $tenantId]
        );
        if (!$rev) return null;

        $rev['items'] = Database::fetchAll(
            "SELECT rvi.*, u.name AS user_name, u.employee_id
             FROM roster_revision_items rvi
             JOIN users u ON u.id = rvi.user_id
             WHERE rvi.roster_revision_id = ?
             ORDER BY rvi.roster_date, u.name",
            [$id]
        );
        return $rev;
    }

    /**
     * Next revision reference number for a tenant (e.g. REV-001).
     */
    public static function nextRevisionRef(int $tenantId, ?int $periodId): string {
        $count = (int)Database::fetch(
            "SELECT COUNT(*) AS cnt FROM roster_revisions WHERE tenant_id = ?",
            [$tenantId]
        )['cnt'];
        return 'REV-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }

    // ─── Coverage & Conflicts ─────────────────────────────────────────────────

    /**
     * Analyse roster coverage gaps and conflicts for a given month.
     *
     * Returns:
     * - uncovered_dates : dates in the month with zero rostered crew
     * - understaffed    : dates with fewer than 2 flight/standby crew
     * - conflicts       : per-user compliance/expiry issues
     * - leave_overlaps  : dates where 3+ crew are on leave simultaneously
     * - reserve_gaps    : dates with no reserve/standby crew
     * - heatmap         : [date => ['count' => n, 'level' => 'ok|warn|critical']]
     */
    public static function getCoverage(int $tenantId, int $year, int $month): array {
        $from = sprintf('%04d-%02d-01', $year, $month);
        $days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $to   = sprintf('%04d-%02d-%02d', $year, $month, $days);

        // All entries for the month
        $entries = Database::fetchAll(
            "SELECT r.roster_date, r.duty_type, r.user_id,
                    u.name AS user_name, ro.slug AS role_slug
             FROM rosters r
             JOIN users u ON u.id = r.user_id
             JOIN user_roles ur ON ur.user_id = u.id
             JOIN roles ro ON ro.id = ur.role_id
             WHERE r.tenant_id = ? AND r.roster_date BETWEEN ? AND ?
               AND ro.slug IN ('pilot','cabin_crew','engineer','chief_pilot','head_cabin_crew')
             ORDER BY r.roster_date",
            [$tenantId, $from, $to]
        );

        // Build per-date stats
        $byDate = [];
        for ($d = 1; $d <= $days; $d++) {
            $dt = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $byDate[$dt] = ['flight' => 0, 'standby' => 0, 'reserve' => 0,
                            'leave' => 0, 'off' => 0, 'total' => 0,
                            'users' => []];
        }

        foreach ($entries as $e) {
            $dt = $e['roster_date'];
            if (!isset($byDate[$dt])) continue;
            $byDate[$dt]['total']++;
            $duty = $e['duty_type'];
            if (in_array($duty, ['flight', 'pos', 'deadhead'])) $byDate[$dt]['flight']++;
            elseif (in_array($duty, ['standby', 'reserve']))    $byDate[$dt]['standby']++;
            elseif ($duty === 'leave' || $duty === 'sick')       $byDate[$dt]['leave']++;
            elseif ($duty === 'off' || $duty === 'rest')         $byDate[$dt]['off']++;
            $byDate[$dt]['users'][] = ['id' => $e['user_id'], 'name' => $e['user_name'], 'duty' => $duty];
        }

        // Heatmap levels
        $heatmap = [];
        $uncoveredDates = [];
        $reserveGaps    = [];
        $understaffed   = [];
        $leaveOverlaps  = [];

        foreach ($byDate as $dt => $stats) {
            if ($stats['total'] === 0) {
                $level = 'empty';
                $uncoveredDates[] = $dt;
            } elseif ($stats['flight'] === 0 && $stats['standby'] === 0) {
                $level = 'critical';
            } elseif ($stats['flight'] < 2) {
                $level = 'warn';
                $understaffed[] = $dt;
            } else {
                $level = 'ok';
            }
            if ($stats['standby'] === 0) $reserveGaps[] = $dt;
            if ($stats['leave'] >= 3)    $leaveOverlaps[] = $dt;
            $heatmap[$dt] = ['count' => $stats['total'], 'flight' => $stats['flight'],
                             'standby' => $stats['standby'], 'leave' => $stats['leave'],
                             'level' => $level];
        }

        // Compliance conflicts
        $conflicts = self::getComplianceIssues($tenantId);

        return [
            'heatmap'         => $heatmap,
            'uncovered_dates' => $uncoveredDates,
            'understaffed'    => $understaffed,
            'reserve_gaps'    => $reserveGaps,
            'leave_overlaps'  => $leaveOverlaps,
            'conflicts'       => $conflicts,
            'by_date'         => $byDate,
        ];
    }

    // ─── Personal Roster (crew self-service) ──────────────────────────────────

    /**
     * Get personal roster for a user — only published/frozen period entries,
     * or entries with no period restriction.
     */
    public static function getPersonalMonth(int $userId, int $tenantId, int $year, int $month): array {
        $from = sprintf('%04d-%02d-01', $year, $month);
        $days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $to   = sprintf('%04d-%02d-%02d', $year, $month, $days);

        return Database::fetchAll(
            "SELECT r.*
             FROM rosters r
             LEFT JOIN roster_periods p ON p.id = r.roster_period_id
             WHERE r.user_id = ? AND r.tenant_id = ?
               AND r.roster_date BETWEEN ? AND ?
               AND (r.roster_period_id IS NULL OR p.status IN ('published','frozen'))
             ORDER BY r.roster_date",
            [$userId, $tenantId, $from, $to]
        );
    }

    /**
     * Get upcoming 14-day window for personal roster.
     */
    public static function getPersonalUpcoming(int $userId, int $tenantId, int $days = 14): array {
        $today = self::isSqlite() ? "DATE('now')" : 'CURDATE()';
        $until = self::isSqlite()
            ? "DATE('now', '+{$days} days')"
            : "DATE_ADD(CURDATE(), INTERVAL {$days} DAY)";

        return Database::fetchAll(
            "SELECT r.*
             FROM rosters r
             LEFT JOIN roster_periods p ON p.id = r.roster_period_id
             WHERE r.user_id = ? AND r.tenant_id = ?
               AND r.roster_date BETWEEN $today AND $until
               AND (r.roster_period_id IS NULL OR p.status IN ('published','frozen'))
             ORDER BY r.roster_date",
            [$userId, $tenantId]
        );
    }

    /**
     * Summary stats for a user's month (flight count, leave days, standby days, etc).
     */
    public static function getPersonalMonthlySummary(int $userId, int $tenantId, int $year, int $month): array {
        $entries = self::getPersonalMonth($userId, $tenantId, $year, $month);
        $summary = ['flight' => 0, 'standby' => 0, 'reserve' => 0,
                    'training' => 0, 'leave' => 0, 'off' => 0, 'rest' => 0, 'total' => 0];
        foreach ($entries as $e) {
            $summary['total']++;
            $t = $e['duty_type'];
            if ($t === 'flight' || $t === 'pos' || $t === 'deadhead') $summary['flight']++;
            elseif ($t === 'standby')  $summary['standby']++;
            elseif ($t === 'reserve')  $summary['reserve']++;
            elseif (in_array($t, ['training','sim','check'])) $summary['training']++;
            elseif ($t === 'leave' || $t === 'sick') $summary['leave']++;
            elseif ($t === 'off')   $summary['off']++;
            elseif ($t === 'rest')  $summary['rest']++;
        }
        return $summary;
    }

    // ─── Row summary for workbench ─────────────────────────────────────────────

    /**
     * Per-user duty summary for a month (used for row stats in the grid).
     * Returns [ user_id => [flight=>n, standby=>n, training=>n, leave=>n, ...] ]
     */
    public static function getMonthSummary(int $tenantId, int $year, int $month): array {
        $from = sprintf('%04d-%02d-01', $year, $month);
        $days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $to   = sprintf('%04d-%02d-%02d', $year, $month, $days);

        $rows = Database::fetchAll(
            "SELECT user_id, duty_type, COUNT(*) AS cnt
             FROM rosters
             WHERE tenant_id = ? AND roster_date BETWEEN ? AND ?
             GROUP BY user_id, duty_type",
            [$tenantId, $from, $to]
        );

        $summary = [];
        foreach ($rows as $row) {
            $uid = $row['user_id'];
            $t   = $row['duty_type'];
            if (!isset($summary[$uid])) {
                $summary[$uid] = ['flight'=>0,'standby'=>0,'reserve'=>0,'training'=>0,'leave'=>0,'off'=>0,'rest'=>0,'total'=>0];
            }
            $summary[$uid]['total'] += $row['cnt'];
            if (in_array($t, ['flight','pos','deadhead']))         $summary[$uid]['flight']   += $row['cnt'];
            elseif (in_array($t, ['standby']))                     $summary[$uid]['standby']  += $row['cnt'];
            elseif ($t === 'reserve')                              $summary[$uid]['reserve']  += $row['cnt'];
            elseif (in_array($t, ['training','sim','check']))      $summary[$uid]['training'] += $row['cnt'];
            elseif (in_array($t, ['leave','sick']))                $summary[$uid]['leave']    += $row['cnt'];
            elseif ($t === 'off')                                  $summary[$uid]['off']      += $row['cnt'];
            elseif ($t === 'rest')                                 $summary[$uid]['rest']     += $row['cnt'];
        }
        return $summary;
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private static function isSqlite(): bool {
        return (getenv('DB_DRIVER') ?: 'mysql') === 'sqlite';
    }

    private static function nowExpr(): string {
        return self::isSqlite() ? "datetime('now')" : 'NOW()';
    }
}
