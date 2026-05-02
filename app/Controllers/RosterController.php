<?php
/**
 * RosterController — crew roster grid + Phase 5 period management
 *
 * Accessible by: scheduler, airline_admin, super_admin, chief_pilot, head_cabin_crew
 */
class RosterController {

    // ─── Monthly Grid ─────────────────────────────────────────────────────────

    public function index(): void {
        // D7 (Phase 5 remediation, 2026-04-26): roster grid is for planners /
        // managers. End-user crew see their own assignments via /api/roster
        // and /flights/{id}, not this grid. Without an explicit guard any
        // logged-in user could browse the full tenant roster.
        RbacMiddleware::requireRole([
            'super_admin', 'airline_admin',
            'scheduler', 'chief_pilot', 'head_cabin_crew',
            'engineering_manager', 'base_manager',
        ]);

        $tenantId = currentTenantId();

        $year  = (int) ($_GET['year']  ?? date('Y'));
        $month = (int) ($_GET['month'] ?? date('n'));
        $baseId = !empty($_GET['base_id']) ? (int) $_GET['base_id'] : null;
        $roleSlug = !empty($_GET['role']) ? $_GET['role'] : null;

        // Clamp month
        if ($month < 1) { $month = 12; $year--; }
        if ($month > 12) { $month = 1;  $year++; }

        $daysInMonth      = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $grid             = RosterModel::getMonth($tenantId, $year, $month, $baseId, $roleSlug);
        $crewList         = RosterModel::getCrewList($tenantId, $baseId, $roleSlug);
        $dutyTypes        = RosterModel::dutyTypes();
        $complianceIssues = RosterModel::getComplianceIssues($tenantId);
        $bases            = Base::allForTenant($tenantId);
        $periods          = RosterModel::getPeriods($tenantId);
        $pendingChanges   = RosterModel::getPendingChanges($tenantId);
        $monthlySummary   = RosterModel::getMonthSummary($tenantId, $year, $month);

        // Find any period overlapping this month
        $monthStart = sprintf('%04d-%02d-01', $year, $month);
        $monthEnd   = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);
        $activePeriod = null;
        foreach ($periods as $p) {
            if ($p['start_date'] <= $monthEnd && $p['end_date'] >= $monthStart) {
                $activePeriod = $p;
                break;
            }
        }

        $prevMonth = $month - 1 < 1  ? 12 : $month - 1;
        $prevYear  = $month - 1 < 1  ? $year - 1 : $year;
        $nextMonth = $month + 1 > 12 ? 1  : $month + 1;
        $nextYear  = $month + 1 > 12 ? $year + 1 : $year;

        $pageTitle    = 'Crew Roster';
        $pageSubtitle = date('F Y', mktime(0, 0, 0, $month, 1, $year));
        $headerAction = hasAnyRole(['scheduler', 'airline_admin', 'super_admin'])
            ? '<a href="/roster/generate" class="btn btn-primary">⚡ Generate Month</a>
               <a href="/roster/assign" class="btn btn-outline" style="margin-left:8px;">＋ Single</a>
               <a href="/roster/bulk-assign" class="btn btn-outline" style="margin-left:8px;">Bulk Assign</a>
               <a href="/roster/periods" class="btn btn-outline" style="margin-left:8px;">Periods</a>'
            : '';

        ob_start();
        require VIEWS_PATH . '/roster/index.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    // ─── Single Assign ────────────────────────────────────────────────────────

    public function assignForm(): void {
        RbacMiddleware::requireRole(['scheduler', 'airline_admin', 'super_admin', 'chief_pilot', 'head_cabin_crew']);

        $tenantId  = currentTenantId();
        $crewList  = RosterModel::getCrewList($tenantId);
        $dutyTypes = RosterModel::dutyTypes();
        $periods   = RosterModel::getPeriods($tenantId);

        $pageTitle    = 'Assign Duty';
        $pageSubtitle = 'Add or update a roster entry';

        ob_start();
        require VIEWS_PATH . '/roster/assign.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function assign(): void {
        RbacMiddleware::requireRole(['scheduler', 'airline_admin', 'super_admin', 'chief_pilot', 'head_cabin_crew']);

        if (!verifyCsrf()) {
            flash('error', 'Invalid security token.');
            redirect('/roster/assign');
        }

        $tenantId = currentTenantId();
        $userId   = (int) ($_POST['user_id'] ?? 0);
        $date     = trim($_POST['roster_date'] ?? '');
        $dutyType = trim($_POST['duty_type'] ?? 'off');
        $dutyCode = trim($_POST['duty_code'] ?? '') ?: null;
        $notes    = trim($_POST['notes'] ?? '') ?: null;

        if (!$userId || !$date) {
            flash('error', 'User and date are required.');
            redirect('/roster/assign');
        }

        $user = Database::fetch("SELECT id FROM users WHERE id = ? AND tenant_id = ?", [$userId, $tenantId]);
        if (!$user) {
            flash('error', 'User not found.');
            redirect('/roster/assign');
        }

        // Phase 8 — eligibility gate. Operational duty types require valid compliance.
        $override = !empty($_POST['override_eligibility']);
        $blockers = EligibilityGate::blockersFor($userId, $date, $dutyType);

        // Phase 16 (V2) — additional operational rules: existing duty conflict,
        // rest-period, consecutive-duty cap. Returned reasons are merged into
        // the same list as the compliance blockers above.
        $opRules = RosterEligibilityService::check($tenantId, $userId, $date, $dutyType, [
            'overwrite' => $override,
        ]);
        if (!empty($opRules['reasons'])) {
            // Promote to "blockers" list when severity == block
            if (($opRules['severity'] ?? '') === 'block') {
                $blockers = array_merge($blockers, $opRules['reasons']);
            } else {
                // warn only — surface but don't auto-block
                AuditLog::log('roster_assign_warning', 'roster', $userId,
                    "Date {$date} {$dutyType}: " . implode('; ', $opRules['reasons']));
            }
        }

        if ($blockers && !$override) {
            flash('error',
                'Eligibility blockers: ' . implode(' · ', $blockers) .
                ' — resubmit with Override to proceed.'
            );
            AuditLog::log('roster_assign_blocked', 'roster', $userId,
                "Date {$date} {$dutyType}: " . implode('; ', $blockers));
            redirect('/roster/assign');
        }

        RosterModel::upsert($tenantId, $userId, $date, $dutyType, $dutyCode, $notes);

        $auditNote = "Assigned {$dutyType} on {$date}";
        if ($override && $blockers) {
            $auditNote .= ' [override: ' . implode('; ', $blockers) . ']';
        }
        AuditLog::log('roster_assigned', 'roster', $userId, $auditNote);
        $msg = $override && $blockers
            ? 'Duty assigned with eligibility override.'
            : 'Duty assigned.';
        if (!empty($opRules['reasons']) && ($opRules['severity'] ?? '') === 'warn') {
            $msg .= ' Warning: ' . implode(' · ', $opRules['reasons']);
        }
        flash('success', $msg);

        [$y, $m] = explode('-', $date);
        redirect("/roster?year={$y}&month=" . ltrim($m, '0'));
    }

    // ─── Bulk Assign ──────────────────────────────────────────────────────────

    public function bulkAssignForm(): void {
        RbacMiddleware::requireRole(['scheduler', 'airline_admin', 'super_admin']);

        $tenantId  = currentTenantId();
        $crewList  = RosterModel::getCrewList($tenantId);
        $dutyTypes = RosterModel::dutyTypes();
        $periods   = RosterModel::getPeriods($tenantId);
        $bases     = Base::allForTenant($tenantId);

        $pageTitle    = 'Bulk Assign';
        $pageSubtitle = 'Assign duty across a date range for multiple crew';

        ob_start();
        require VIEWS_PATH . '/roster/bulk_assign.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function bulkAssign(): void {
        RbacMiddleware::requireRole(['scheduler', 'airline_admin', 'super_admin']);

        if (!verifyCsrf()) {
            flash('error', 'Invalid security token.');
            redirect('/roster/bulk-assign');
        }

        $tenantId  = currentTenantId();
        $userIds   = array_map('intval', $_POST['user_ids'] ?? []);
        $fromDate  = trim($_POST['from_date'] ?? '');
        $toDate    = trim($_POST['to_date'] ?? '');
        $dutyType  = trim($_POST['duty_type'] ?? 'off');
        $dutyCode  = trim($_POST['duty_code'] ?? '') ?: null;
        $notes     = trim($_POST['notes'] ?? '') ?: null;
        $overwrite = !empty($_POST['overwrite']);
        $periodId  = (int)($_POST['roster_period_id'] ?? 0) ?: null;

        if (empty($userIds)) {
            flash('error', 'Select at least one crew member.');
            redirect('/roster/bulk-assign');
        }
        if (!$fromDate || !$toDate || $fromDate > $toDate) {
            flash('error', 'Valid date range required.');
            redirect('/roster/bulk-assign');
        }
        if ((new \DateTime($fromDate))->diff(new \DateTime($toDate))->days > 365) {
            flash('error', 'Date range cannot exceed 365 days.');
            redirect('/roster/bulk-assign');
        }

        // Verify all users belong to this tenant
        foreach ($userIds as $uid) {
            $u = Database::fetch("SELECT id FROM users WHERE id = ? AND tenant_id = ?", [$uid, $tenantId]);
            if (!$u) { flash('error', 'Invalid user selection.'); redirect('/roster/bulk-assign'); }
        }

        $count = RosterModel::bulkAssign($tenantId, $userIds, $fromDate, $toDate, $dutyType, $dutyCode, $notes, $overwrite, $periodId);

        AuditLog::log('roster_bulk_assign', 'roster', 0,
            "Bulk assigned {$dutyType} from {$fromDate} to {$toDate} for " . count($userIds) . " crew ({$count} entries)");

        flash('success', "{$count} roster entries written.");
        [$y, $m] = explode('-', $fromDate);
        redirect("/roster?year={$y}&month=" . ltrim($m, '0'));
    }

    // ─── Roster Periods ───────────────────────────────────────────────────────

    public function periods(): void {
        RbacMiddleware::requireRole(['scheduler', 'airline_admin', 'super_admin']);

        $tenantId = currentTenantId();
        $periods  = RosterModel::getPeriods($tenantId);
        $pending  = RosterModel::getPendingChanges($tenantId);

        $pageTitle    = 'Roster Periods';
        $pageSubtitle = 'Manage scheduling cycles and publish roster';

        ob_start();
        require VIEWS_PATH . '/roster/periods.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function createPeriodForm(): void {
        RbacMiddleware::requireRole(['scheduler', 'airline_admin', 'super_admin']);

        $pageTitle    = 'Create Period';
        $pageSubtitle = 'Define a new scheduling period';

        ob_start();
        require VIEWS_PATH . '/roster/period_create.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function storePeriod(): void {
        RbacMiddleware::requireRole(['scheduler', 'airline_admin', 'super_admin']);

        if (!verifyCsrf()) {
            flash('error', 'Invalid security token.');
            redirect('/roster/periods/create');
        }

        $tenantId  = currentTenantId();
        $userId    = (int)(currentUser()['id'] ?? 0);
        $name      = trim($_POST['name'] ?? '');
        $startDate = trim($_POST['start_date'] ?? '');
        $endDate   = trim($_POST['end_date'] ?? '');
        $notes     = trim($_POST['notes'] ?? '') ?: null;

        if (!$name || !$startDate || !$endDate) {
            flash('error', 'Name, start date, and end date are required.');
            redirect('/roster/periods/create');
        }
        if ($startDate > $endDate) {
            flash('error', 'Start date must be before end date.');
            redirect('/roster/periods/create');
        }

        $id = RosterModel::createPeriod($tenantId, $name, $startDate, $endDate, $notes, $userId);
        AuditLog::log('roster_period_created', 'roster_period', $id, "Created period: {$name}");
        flash('success', "Period '{$name}' created.");
        redirect('/roster/periods');
    }

    public function publishPeriod(int $id): void {
        RbacMiddleware::requireRole(['scheduler', 'airline_admin', 'super_admin']);

        if (!verifyCsrf()) { flash('error', 'Invalid token.'); redirect('/roster/periods'); }

        $tenantId = currentTenantId();
        $period   = RosterModel::getPeriod($id, $tenantId);
        if (!$period) { flash('error', 'Period not found.'); redirect('/roster/periods'); }

        $newStatus = $period['status'] === 'published' ? 'draft' : 'published';
        RosterModel::updatePeriodStatus($id, $tenantId, $newStatus);
        AuditLog::log('roster_period_status', 'roster_period', $id, "Period {$period['name']} → {$newStatus}");
        flash('success', "Period " . ($newStatus === 'published' ? 'published' : 'moved back to draft') . ".");
        redirect('/roster/periods');
    }

    public function freezePeriod(int $id): void {
        RbacMiddleware::requireRole(['airline_admin', 'super_admin']);

        if (!verifyCsrf()) { flash('error', 'Invalid token.'); redirect('/roster/periods'); }

        $tenantId = currentTenantId();
        $period   = RosterModel::getPeriod($id, $tenantId);
        if (!$period || $period['status'] !== 'published') {
            flash('error', 'Only published periods can be frozen.');
            redirect('/roster/periods');
        }

        RosterModel::updatePeriodStatus($id, $tenantId, 'frozen');
        AuditLog::log('roster_period_frozen', 'roster_period', $id, "Frozen period: {$period['name']}");
        flash('success', "Period '{$period['name']}' frozen — no further changes allowed.");
        redirect('/roster/periods');
    }

    public function deletePeriod(int $id): void {
        RbacMiddleware::requireRole(['scheduler', 'airline_admin', 'super_admin']);

        if (!verifyCsrf()) { flash('error', 'Invalid token.'); redirect('/roster/periods'); }

        $tenantId = currentTenantId();
        $period   = RosterModel::getPeriod($id, $tenantId);
        if (!$period) { flash('error', 'Period not found.'); redirect('/roster/periods'); }
        if ($period['status'] !== 'draft') {
            flash('error', 'Only draft periods can be deleted.');
            redirect('/roster/periods');
        }

        RosterModel::deletePeriod($id, $tenantId);
        AuditLog::log('roster_period_deleted', 'roster_period', $id, "Deleted draft period: {$period['name']}");
        flash('success', 'Draft period deleted.');
        redirect('/roster/periods');
    }

    // ─── Change Requests / Comments ───────────────────────────────────────────

    public function changes(): void {
        RbacMiddleware::requireRole(['scheduler', 'airline_admin', 'super_admin', 'chief_pilot', 'head_cabin_crew']);

        $tenantId   = currentTenantId();
        $statusFilter = $_GET['status'] ?? 'pending';   // pending | approved | rejected | noted | all

        // Pending always shown via the primary model helper; other statuses use a raw query
        if ($statusFilter === 'pending') {
            $changes = RosterModel::getPendingChanges($tenantId);
        } else {
            $whereStatus = $statusFilter !== 'all' ? "AND rc.status = '$statusFilter'" : '';
            $changes = Database::fetchAll(
                "SELECT rc.*, u.name AS user_name, u.employee_id,
                        p.name AS period_name,
                        rb.name AS responded_by_name
                 FROM roster_changes rc
                 JOIN users u ON u.id = rc.user_id
                 LEFT JOIN roster_periods p ON p.id = rc.roster_period_id
                 LEFT JOIN users rb ON rb.id = rc.responded_by
                 WHERE rc.tenant_id = ? $whereStatus
                 ORDER BY rc.created_at DESC",
                [$tenantId]
            );
        }

        // Counts for tab badges
        $counts = Database::fetchAll(
            "SELECT status, COUNT(*) as c FROM roster_changes WHERE tenant_id = ? GROUP BY status",
            [$tenantId]
        );
        $statusCounts = [];
        foreach ($counts as $row) { $statusCounts[$row['status']] = (int)$row['c']; }
        $totalCount   = array_sum($statusCounts);
        $pendingCount = $statusCounts['pending'] ?? 0;

        $pageTitle    = 'Roster Change Requests';
        $pageSubtitle = 'Review and respond to crew requests';

        ob_start();
        require VIEWS_PATH . '/roster/changes.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function requestChange(): void {
        requireAuth();

        if (!verifyCsrf()) {
            flash('error', 'Invalid security token.');
            redirect('/roster');
        }

        $tenantId   = currentTenantId();
        $userId     = (int)(currentUser()['id'] ?? 0);
        $changeType = trim($_POST['change_type'] ?? 'comment');
        $message    = trim($_POST['message'] ?? '');
        $periodId   = (int)($_POST['roster_period_id'] ?? 0) ?: null;
        $rosterId   = (int)($_POST['roster_id'] ?? 0) ?: null;

        if (!$message) {
            flash('error', 'Message is required.');
            redirect('/roster');
        }

        $validTypes = ['comment', 'leave_request', 'swap_request', 'correction', 'training_request'];
        if (!in_array($changeType, $validTypes)) $changeType = 'comment';

        RosterModel::createChangeRequest($tenantId, $userId, $userId, $changeType, $message, $periodId, $rosterId);
        AuditLog::log('roster_change_requested', 'roster', 0, "Change request ({$changeType}) submitted");
        flash('success', 'Your request has been submitted.');
        redirect($_POST['redirect'] ?? '/roster');
    }

    public function respondToChange(int $id): void {
        RbacMiddleware::requireRole(['scheduler', 'airline_admin', 'super_admin', 'chief_pilot', 'head_cabin_crew']);

        if (!verifyCsrf()) {
            flash('error', 'Invalid security token.');
            redirect('/roster/changes');
        }

        $tenantId   = currentTenantId();
        $responderId = (int)(currentUser()['id'] ?? 0);
        $status     = trim($_POST['status'] ?? 'noted');
        $response   = trim($_POST['response'] ?? '');

        $validStatuses = ['approved', 'rejected', 'noted'];
        if (!in_array($status, $validStatuses)) $status = 'noted';

        $change = RosterModel::getChangeRequest($id, $tenantId);
        if (!$change) { flash('error', 'Request not found.'); redirect('/roster/changes'); }

        RosterModel::respondToChange($id, $tenantId, $status, $response, $responderId);
        AuditLog::log('roster_change_responded', 'roster', $id, "Change #{$id} marked {$status}");
        flash('success', "Request marked as {$status}.");
        redirect('/roster/changes');
    }

    // ─── Standby Pool + Suggestions ───────────────────────────────────────────

    public function standbyPool(): void {
        RbacMiddleware::requireRole(['scheduler', 'airline_admin', 'super_admin', 'chief_pilot', 'head_cabin_crew']);

        $tenantId = currentTenantId();
        $date     = $_GET['date'] ?? date('Y-m-d');
        $pool     = RosterModel::getStandbyPool($tenantId, $date);

        $pageTitle    = 'Standby Pool';
        $pageSubtitle = date('D, d M Y', strtotime($date));

        ob_start();
        require VIEWS_PATH . '/roster/standby.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function suggest(int $userId): void {
        RbacMiddleware::requireRole(['scheduler', 'airline_admin', 'super_admin', 'chief_pilot', 'head_cabin_crew']);

        $tenantId = currentTenantId();
        $date     = $_GET['date'] ?? date('Y-m-d');

        $crewMember = Database::fetch(
            "SELECT u.id, u.name AS user_name, u.employee_id, ro.name AS role_name, ro.slug AS role_slug
             FROM users u
             JOIN user_roles ur ON ur.user_id = u.id
             JOIN roles ro ON ro.id = ur.role_id
             WHERE u.id = ? AND u.tenant_id = ?
               AND ro.slug IN ('pilot','cabin_crew','engineer','chief_pilot','head_cabin_crew')",
            [$userId, $tenantId]
        );

        if (!$crewMember) {
            flash('error', 'Crew member not found.');
            redirect('/roster');
        }

        $currentDuty = Database::fetch(
            "SELECT * FROM rosters WHERE user_id = ? AND roster_date = ? AND tenant_id = ?",
            [$userId, $date, $tenantId]
        );

        $complianceIssues = RosterModel::getComplianceIssues($tenantId);
        $crewCompliance   = $complianceIssues[$userId] ?? null;
        $suggestions      = RosterModel::suggestReplacements($tenantId, $date, $userId);
        $dutyTypes        = RosterModel::dutyTypes();

        $pageTitle    = 'Replacement Suggestions';
        $pageSubtitle = 'For ' . htmlspecialchars($crewMember['user_name']) . ' on ' . date('D, d M Y', strtotime($date));

        ob_start();
        require VIEWS_PATH . '/roster/suggest.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    // ─── Delete entry ─────────────────────────────────────────────────────────

    public function delete(int $id): void {
        RbacMiddleware::requireRole(['scheduler', 'airline_admin', 'super_admin']);

        if (!verifyCsrf()) {
            flash('error', 'Invalid security token.');
            redirect('/roster');
        }

        $tenantId = currentTenantId();
        RosterModel::delete($id, $tenantId);
        AuditLog::log('roster_deleted', 'roster', $id, "Deleted roster entry #{$id}");
        flash('success', 'Roster entry removed.');

        $ref = $_SERVER['HTTP_REFERER'] ?? '/roster';
        redirect($ref);
    }

    // ─── Revision Center ──────────────────────────────────────────────────────

    public function revisions(): void {
        RbacMiddleware::requireRole(['scheduler', 'airline_admin', 'super_admin', 'chief_pilot', 'head_cabin_crew']);

        $tenantId  = currentTenantId();
        $periodId  = !empty($_GET['period_id']) ? (int)$_GET['period_id'] : null;
        $revisions = RosterModel::getRevisions($tenantId, $periodId);
        $periods   = RosterModel::getPeriods($tenantId);

        $pageTitle    = 'Revision Center';
        $pageSubtitle = 'Post-publication roster change tracking';

        ob_start();
        require VIEWS_PATH . '/roster/revisions.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function createRevisionForm(): void {
        RbacMiddleware::requireRole(['scheduler', 'airline_admin', 'super_admin']);

        $tenantId  = currentTenantId();
        $periods   = RosterModel::getPeriods($tenantId);
        $crewList  = RosterModel::getCrewList($tenantId);
        $dutyTypes = RosterModel::dutyTypes();

        $pageTitle    = 'Create Revision';
        $pageSubtitle = 'Record a post-publication duty change';

        ob_start();
        require VIEWS_PATH . '/roster/revision_create.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function storeRevision(): void {
        RbacMiddleware::requireRole(['scheduler', 'airline_admin', 'super_admin']);

        if (!verifyCsrf()) {
            flash('error', 'Invalid security token.');
            redirect('/roster/revisions/create');
        }

        $tenantId = currentTenantId();
        $userId   = (int)(currentUser()['id'] ?? 0);
        $periodId = (int)($_POST['roster_period_id'] ?? 0) ?: null;
        $reason   = trim($_POST['reason'] ?? '');
        $source   = trim($_POST['change_source'] ?? 'scheduler');
        $notes    = trim($_POST['notes'] ?? '') ?: null;

        $itemUserIds = $_POST['item_user_id']  ?? [];
        $itemDates   = $_POST['item_date']     ?? [];
        $itemNewDuty = $_POST['item_new_duty'] ?? [];
        $itemNewCode = $_POST['item_new_code'] ?? [];
        $itemNote    = $_POST['item_note']     ?? [];

        if (!$reason) {
            flash('error', 'Reason is required.');
            redirect('/roster/revisions/create');
        }
        if (empty($itemUserIds)) {
            flash('error', 'At least one change item is required.');
            redirect('/roster/revisions/create');
        }

        $validSources = ['scheduler','manager_request','crew_request','operational','system'];
        if (!in_array($source, $validSources)) $source = 'scheduler';

        $ref   = RosterModel::nextRevisionRef($tenantId, $periodId);
        $revId = RosterModel::createRevision($tenantId, $periodId, $ref, $reason, $source, $userId, $notes);

        $dbNow = (getenv('DB_DRIVER') === 'sqlite') ? "datetime('now')" : 'NOW()';
        foreach ($itemUserIds as $i => $itemUserId) {
            $itemUserId = (int)$itemUserId;
            $date       = trim($itemDates[$i]   ?? '');
            $newDuty    = trim($itemNewDuty[$i] ?? '');
            $newCode    = trim($itemNewCode[$i] ?? '') ?: null;
            $note       = trim($itemNote[$i]    ?? '') ?: null;
            if (!$itemUserId || !$date || !$newDuty) continue;

            $existing = Database::fetch(
                "SELECT duty_type, duty_code FROM rosters WHERE user_id = ? AND roster_date = ? AND tenant_id = ?",
                [$itemUserId, $date, $tenantId]
            );

            RosterModel::addRevisionItem(
                $revId, $tenantId, $itemUserId, $date,
                $existing['duty_type'] ?? null, $existing['duty_code'] ?? null,
                $newDuty, $newCode, $note
            );

            if ($existing) {
                Database::execute(
                    "UPDATE rosters SET duty_type = ?, duty_code = ?, revision_id = ?, is_revision = 1, updated_at = $dbNow
                     WHERE user_id = ? AND roster_date = ? AND tenant_id = ?",
                    [$newDuty, $newCode, $revId, $itemUserId, $date, $tenantId]
                );
            } else {
                Database::execute(
                    "INSERT INTO rosters (tenant_id, user_id, roster_date, duty_type, duty_code, revision_id, is_revision)
                     VALUES (?, ?, ?, ?, ?, ?, 1)",
                    [$tenantId, $itemUserId, $date, $newDuty, $newCode, $revId]
                );
            }
        }

        RosterModel::issueRevision($revId, $tenantId, $userId);
        AuditLog::log('roster_revision_created', 'roster_revision', $revId, "Created revision {$ref}: {$reason}");
        flash('success', "Revision {$ref} created and issued.");
        redirect('/roster/revisions');
    }

    // ─── Coverage & Conflicts ─────────────────────────────────────────────────

    public function coverage(): void {
        RbacMiddleware::requireRole(['scheduler', 'airline_admin', 'super_admin', 'chief_pilot', 'head_cabin_crew']);

        $tenantId = currentTenantId();
        $year  = (int) ($_GET['year']  ?? date('Y'));
        $month = (int) ($_GET['month'] ?? date('n'));
        if ($month < 1) { $month = 12; $year--; }
        if ($month > 12) { $month = 1;  $year++; }

        $coverage    = RosterModel::getCoverage($tenantId, $year, $month);
        $periods     = RosterModel::getPeriods($tenantId);
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        $prevMonth = $month - 1 < 1  ? 12 : $month - 1;
        $prevYear  = $month - 1 < 1  ? $year - 1 : $year;
        $nextMonth = $month + 1 > 12 ? 1  : $month + 1;
        $nextYear  = $month + 1 > 12 ? $year + 1 : $year;

        $pageTitle    = 'Coverage & Conflicts';
        $pageSubtitle = date('F Y', mktime(0, 0, 0, $month, 1, $year));

        ob_start();
        require VIEWS_PATH . '/roster/coverage.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    // ─── Personal Roster (crew self-service) ──────────────────────────────────

    public function myRoster(): void {
        requireAuth();

        $tenantId = currentTenantId();
        $userId   = (int)(currentUser()['id'] ?? 0);
        $year     = (int) ($_GET['year']  ?? date('Y'));
        $month    = (int) ($_GET['month'] ?? date('n'));
        if ($month < 1) { $month = 12; $year--; }
        if ($month > 12) { $month = 1;  $year++; }

        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $entries     = RosterModel::getPersonalMonth($userId, $tenantId, $year, $month);
        $upcoming    = RosterModel::getPersonalUpcoming($userId, $tenantId, 14);
        $summary     = RosterModel::getPersonalMonthlySummary($userId, $tenantId, $year, $month);
        $dutyTypes   = RosterModel::dutyTypes();

        $byDate = [];
        foreach ($entries as $e) {
            $byDate[$e['roster_date']] = $e;
        }

        $prevMonth = $month - 1 < 1  ? 12 : $month - 1;
        $prevYear  = $month - 1 < 1  ? $year - 1 : $year;
        $nextMonth = $month + 1 > 12 ? 1  : $month + 1;
        $nextYear  = $month + 1 > 12 ? $year + 1 : $year;

        $activePeriod = RosterModel::getActivePeriod($tenantId);
        $myChanges    = Database::fetchAll(
            "SELECT rc.*, p.name AS period_name
             FROM roster_changes rc
             LEFT JOIN roster_periods p ON p.id = rc.roster_period_id
             WHERE rc.user_id = ? AND rc.tenant_id = ?
             ORDER BY rc.created_at DESC LIMIT 5",
            [$userId, $tenantId]
        );

        $pageTitle    = 'My Roster';
        $pageSubtitle = date('F Y', mktime(0, 0, 0, $month, 1, $year));

        ob_start();
        require VIEWS_PATH . '/roster/my_roster.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    // ─── Personal Leave Requests / Roster Corrections (dedicated pages) ───────
    //
    // Both reuse the existing /roster/changes/request POST endpoint with a
    // pre-selected change_type so backend wiring stays unchanged.

    public function myLeaveRequests(): void {
        requireAuth();
        $this->renderPersonalChangeFeed(
            'leave_request',
            'Leave Requests',
            'Submit and track time-off requests.'
        );
    }

    public function myRosterCorrections(): void {
        requireAuth();
        $this->renderPersonalChangeFeed(
            'correction',
            'Roster Corrections',
            'Flag incorrect roster entries and track scheduling responses.'
        );
    }

    private function renderPersonalChangeFeed(string $type, string $title, string $subtitle): void {
        $tenantId = (int) currentTenantId();
        $userId   = (int) (currentUser()['id'] ?? 0);

        $rows = Database::fetchAll(
            "SELECT rc.*, p.name AS period_name
               FROM roster_changes rc
          LEFT JOIN roster_periods p ON p.id = rc.roster_period_id
              WHERE rc.user_id = ? AND rc.tenant_id = ?
                AND rc.change_type = ?
              ORDER BY rc.created_at DESC LIMIT 50",
            [$userId, $tenantId, $type]
        );

        $open    = array_values(array_filter($rows, fn($r) => $r['status'] === 'pending'));
        $closed  = array_values(array_filter($rows, fn($r) => $r['status'] !== 'pending'));

        $pageTitle    = $title;
        $pageSubtitle = $subtitle;
        $changeType   = $type;

        ob_start();
        require VIEWS_PATH . '/roster/personal_changes.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    // ─── Personal Roster Duty Detail (JSON, drawer-fed) ───────────────────────
    //
    // GET /my-roster/duty/{id} — returns the full duty detail for one of the
    // logged-in user's own roster cells, scoped to their tenant. Powers the
    // click-to-detail drawer on /my-roster. Tenant + ownership isolation are
    // enforced server-side; the {id} alone is not enough to read another
    // crew member's duty.
    public function myDutyDetailJson(string $rosterId): void {
        requireAuth();
        $tenantId = (int) currentTenantId();
        $userId   = (int) (currentUser()['id'] ?? 0);
        $rid      = (int) $rosterId;

        if ($rid <= 0 || $tenantId <= 0 || $userId <= 0) {
            jsonResponse(['error' => 'Not found'], 404);
        }

        $row = Database::fetch(
            "SELECT r.id, r.tenant_id, r.user_id, r.roster_date, r.duty_type, r.duty_code,
                    r.notes, r.acknowledged_at, r.base_id, r.fleet_id, r.reserve_type,
                    b.name AS base_name, b.code AS base_code,
                    fl.name AS fleet_name
               FROM rosters r
          LEFT JOIN bases   b  ON b.id  = r.base_id
          LEFT JOIN fleets  fl ON fl.id = r.fleet_id
              WHERE r.id = ? AND r.tenant_id = ? AND r.user_id = ?
              LIMIT 1",
            [$rid, $tenantId, $userId]
        );
        if (!$row) {
            jsonResponse(['error' => 'Not found'], 404);
        }

        $dutyTypes  = RosterModel::dutyTypes();
        $meta       = $dutyTypes[$row['duty_type']] ?? null;

        // For flight duties, find any flights this user is on for that date.
        // The user might be captain, FO, or in flight_crew_assignments.
        $sectors = [];
        $crewSet = [];   // user_id => row, deduped across sectors
        if (in_array($row['duty_type'], ['flight','pos','deadhead'], true)) {
            $flights = Database::fetchAll(
                "SELECT f.id, f.flight_number, f.flight_date, f.departure, f.arrival,
                        f.std, f.sta, f.status, f.notes,
                        a.registration AS aircraft_reg, a.aircraft_type,
                        f.captain_id, f.fo_id,
                        cap.name AS captain_name,
                        fo.name  AS fo_name
                   FROM flights f
              LEFT JOIN aircraft a ON a.id = f.aircraft_id
              LEFT JOIN users    cap ON cap.id = f.captain_id
              LEFT JOIN users    fo  ON fo.id  = f.fo_id
                  WHERE f.tenant_id = ? AND f.flight_date = ?
                    AND (
                          f.captain_id = ?
                       OR f.fo_id      = ?
                       OR f.id IN (
                            SELECT flight_id FROM flight_crew_assignments
                             WHERE user_id = ?
                          )
                    )
               ORDER BY f.std ASC, f.flight_number ASC",
                [$tenantId, $row['roster_date'], $userId, $userId, $userId]
            );

            foreach ($flights as $f) {
                // Sector-level crew (cabin / engineer / observer / jumpseat).
                // Profile photo column varies between sqlite (avatar_path) and
                // MySQL (profile_photo_path), so we omit it here — the front-end
                // already falls back to initials. We can re-add a unified photo
                // column once the schemas converge.
                $sectorCrew = Database::fetchAll(
                    "SELECT u.id, u.name,
                            fca.role_on_flight, fca.is_lead
                       FROM flight_crew_assignments fca
                       JOIN users u ON u.id = fca.user_id
                      WHERE fca.flight_id = ?
                      ORDER BY fca.is_lead DESC, u.name ASC",
                    [$f['id']]
                );

                // Roll up cockpit + cabin into a single deduped crew set for the duty
                if (!empty($f['captain_id']) && empty($crewSet[$f['captain_id']])) {
                    $crewSet[$f['captain_id']] = [
                        'id'    => (int) $f['captain_id'],
                        'name'  => $f['captain_name'],
                        'role'  => 'captain',
                        'role_label' => 'Captain',
                        'photo' => null,
                        'is_self' => ((int)$f['captain_id'] === $userId),
                    ];
                }
                if (!empty($f['fo_id']) && empty($crewSet[$f['fo_id']])) {
                    $crewSet[$f['fo_id']] = [
                        'id'    => (int) $f['fo_id'],
                        'name'  => $f['fo_name'],
                        'role'  => 'first_officer',
                        'role_label' => 'First Officer',
                        'photo' => null,
                        'is_self' => ((int)$f['fo_id'] === $userId),
                    ];
                }
                foreach ($sectorCrew as $c) {
                    $cid = (int) $c['id'];
                    if ($cid <= 0 || isset($crewSet[$cid])) continue;
                    $roleSlug  = strtolower((string) ($c['role_on_flight'] ?? ''));
                    $roleLabel = match ($roleSlug) {
                        'cabin_crew'       => $c['is_lead'] ? 'Senior Cabin Crew' : 'Cabin Crew',
                        'purser', 'scc'    => 'Purser',
                        'engineer'         => 'Flight Engineer',
                        'observer'         => 'Observer',
                        'jumpseat'         => 'Jumpseat',
                        default            => ucfirst(str_replace('_', ' ', $roleSlug ?: 'Crew')),
                    };
                    $crewSet[$cid] = [
                        'id'    => $cid,
                        'name'  => $c['name'],
                        'role'  => $roleSlug,
                        'role_label' => $roleLabel,
                        'photo' => null,
                        'is_self' => ($cid === $userId),
                    ];
                }

                $sectors[] = [
                    'id'             => (int) $f['id'],
                    'flight_number'  => $f['flight_number'],
                    'departure'      => $f['departure'],
                    'arrival'        => $f['arrival'],
                    'std'            => $f['std'],
                    'sta'            => $f['sta'],
                    'etd'            => null,   // reserved — populated when ops adds revised times
                    'eta'            => null,
                    'aircraft_reg'   => $f['aircraft_reg'],
                    'aircraft_type'  => $f['aircraft_type'],
                    'captain_name'   => $f['captain_name'],
                    'fo_name'        => $f['fo_name'],
                    'status'         => $f['status'],
                    'notes'          => $f['notes'],
                    'briefing_url'   => '/flights/' . (int) $f['id'],
                ];
            }
        }

        // ── Crew, ordered: self last (or by rank), captain first, then FO,
        //    then SCC/Purser, then cabin crew, then engineer/observer.
        $rankOrder = [
            'captain' => 0, 'first_officer' => 1, 'purser' => 2, 'scc' => 2,
            'cabin_crew' => 3, 'engineer' => 4, 'observer' => 5, 'jumpseat' => 6,
        ];
        $crew = array_values($crewSet);
        usort($crew, function ($a, $b) use ($rankOrder) {
            $ra = $rankOrder[$a['role']] ?? 9;
            $rb = $rankOrder[$b['role']] ?? 9;
            if ($ra !== $rb) return $ra <=> $rb;
            return strcmp($a['name'] ?? '', $b['name'] ?? '');
        });

        // ── Briefing documents (flight bag) — pulled across all sectors.
        $briefingDocs = [];
        if (!empty($sectors)) {
            $flightIds = array_map(fn($s) => $s['id'], $sectors);
            $placeholders = rtrim(str_repeat('?,', count($flightIds)), ',');
            try {
                $briefingDocs = Database::fetchAll(
                    "SELECT id, flight_id, file_type, title, file_name, file_size, created_at
                       FROM flight_bag_files
                      WHERE tenant_id = ? AND flight_id IN ($placeholders)
                      ORDER BY created_at DESC",
                    array_merge([$tenantId], $flightIds)
                );
            } catch (\Throwable $e) {
                $briefingDocs = [];
            }
        }

        // ── Action permission flags driving drawer footer buttons.
        $isAcknowledged = !empty($row['acknowledged_at']);
        $today          = date('Y-m-d');
        // Acknowledge applies to assigned duties from today forward — pilots
        // shouldn't need to ack a day off in the past, and shouldn't be
        // re-acking a sealed past duty either.
        $canAcknowledge = !$isAcknowledged
                       && $row['roster_date'] >= $today
                       && !in_array($row['duty_type'], ['off','rest'], true);
        $canRequestLeave = !in_array($row['duty_type'], ['leave','sick'], true)
                        && $row['roster_date'] >= $today;
        $canRequestCorrection = true;

        // ── Estimated duty duration: when sectors are present, span from
        //    earliest STD to latest STA. Otherwise null (no FDP rules at this
        //    layer — that's the FDM controller's job).
        $estDutyMins = null;
        if (!empty($sectors)) {
            $first = $sectors[0]['std']  ?? null;
            $last  = end($sectors)['sta'] ?? null;
            reset($sectors);
            if ($first && $last) {
                try {
                    $a = strtotime($row['roster_date'] . ' ' . $first);
                    $b = strtotime($row['roster_date'] . ' ' . $last);
                    if ($b < $a) { $b += 86400; } // crosses midnight
                    $estDutyMins = (int) round(($b - $a) / 60);
                } catch (\Throwable $e) { $estDutyMins = null; }
            }
        }

        // Routing string e.g. "NBO → MGQ → NBO"
        $routing = null;
        if (!empty($sectors)) {
            $stops = [];
            foreach ($sectors as $s) {
                if (empty($stops)) $stops[] = $s['departure'];
                $stops[] = $s['arrival'];
            }
            $stops = array_filter($stops);
            if (count($stops) >= 2) $routing = implode(' → ', $stops);
        }

        jsonResponse([
            'id'              => (int) $row['id'],
            'date'            => $row['roster_date'],
            'duty_type'       => $row['duty_type'],
            'duty_type_label' => $meta['label'] ?? ucfirst((string)$row['duty_type']),
            'duty_type_color' => $meta['color'] ?? null,
            'duty_type_bg'    => $meta['bg'] ?? null,
            'duty_type_code'  => $meta['code'] ?? strtoupper(substr((string)$row['duty_type'], 0, 3)),
            'duty_code'       => $row['duty_code'] ?? '',
            'notes'           => $row['notes'] ?? '',
            'reserve_type'    => $row['reserve_type'] ?? null,
            'station'         => $row['base_code'] ?: $row['base_name'],
            'base_name'       => $row['base_name'],
            'base_code'       => $row['base_code'],
            'fleet_name'      => $row['fleet_name'],
            'acknowledged_at' => $row['acknowledged_at'],
            'is_acknowledged' => $isAcknowledged,
            'can_acknowledge' => $canAcknowledge,
            'can_request_leave'      => $canRequestLeave,
            'can_request_correction' => $canRequestCorrection,
            'routing'         => $routing,
            'est_duty_minutes' => $estDutyMins,
            'sectors'         => $sectors,
            'crew'            => $crew,
            'briefing_docs'   => $briefingDocs,
        ]);
    }

    // ─── Acknowledge a roster entry ───────────────────────────────────────────
    //
    // POST /my-roster/acknowledge — the logged-in pilot stamps acknowledged_at
    // on one of THEIR own roster rows. Idempotent: a second ack is a no-op.
    public function acknowledgeDuty(): void {
        requireAuth();

        if (!verifyCsrf()) {
            jsonResponse(['error' => 'Invalid security token.'], 403);
        }

        $tenantId = (int) currentTenantId();
        $userId   = (int) (currentUser()['id'] ?? 0);
        $rid      = (int) ($_POST['roster_id'] ?? 0);

        if ($rid <= 0 || $tenantId <= 0 || $userId <= 0) {
            jsonResponse(['error' => 'Invalid request.'], 400);
        }

        $row = Database::fetch(
            "SELECT id, roster_date, duty_type, acknowledged_at
               FROM rosters
              WHERE id = ? AND tenant_id = ? AND user_id = ?
              LIMIT 1",
            [$rid, $tenantId, $userId]
        );
        if (!$row) {
            jsonResponse(['error' => 'Roster entry not found.'], 404);
        }
        if (!empty($row['acknowledged_at'])) {
            jsonResponse([
                'ok' => true,
                'already_acknowledged' => true,
                'acknowledged_at'      => $row['acknowledged_at'],
            ]);
        }

        $dbNow = (getenv('DB_DRIVER') === 'sqlite') ? "datetime('now')" : 'NOW()';
        Database::execute(
            "UPDATE rosters
                SET acknowledged_at = $dbNow
              WHERE id = ? AND tenant_id = ? AND user_id = ? AND acknowledged_at IS NULL",
            [$rid, $tenantId, $userId]
        );

        AuditLog::log('roster_acknowledged', 'roster', $rid,
            "Acknowledged {$row['duty_type']} on {$row['roster_date']}");

        $fresh = Database::fetch(
            "SELECT acknowledged_at FROM rosters WHERE id = ? AND tenant_id = ? AND user_id = ?",
            [$rid, $tenantId, $userId]
        );
        jsonResponse([
            'ok' => true,
            'already_acknowledged' => false,
            'acknowledged_at'      => $fresh['acknowledged_at'] ?? null,
        ]);
    }

    // ─── Personal "All Roster Requests" — unified history ─────────────────────
    //
    // GET /my-roster/requests — single page that lists every roster_changes row
    // for the logged-in pilot, with type tabs (All / Leave / Correction / Swap
    // / Comment) and status filters. Companion to the dedicated leave-requests
    // and roster-corrections pages; this is the umbrella history view.
    public function myAllRosterRequests(): void {
        requireAuth();
        $tenantId   = (int) currentTenantId();
        $userId     = (int) (currentUser()['id'] ?? 0);
        $typeFilter = $_GET['type']   ?? 'all';
        $stsFilter  = $_GET['status'] ?? 'all';

        $validTypes  = ['all','leave_request','correction','swap_request','comment'];
        $validStatus = ['all','pending','approved','rejected','noted'];
        if (!in_array($typeFilter, $validTypes,  true)) $typeFilter = 'all';
        if (!in_array($stsFilter,  $validStatus, true)) $stsFilter  = 'all';

        $where  = "rc.user_id = ? AND rc.tenant_id = ?";
        $params = [$userId, $tenantId];
        if ($typeFilter !== 'all') { $where .= " AND rc.change_type = ?"; $params[] = $typeFilter; }
        if ($stsFilter  !== 'all') { $where .= " AND rc.status = ?";      $params[] = $stsFilter;  }

        $rows = Database::fetchAll(
            "SELECT rc.*, p.name AS period_name,
                    rb.name AS responded_by_name
               FROM roster_changes rc
          LEFT JOIN roster_periods p  ON p.id  = rc.roster_period_id
          LEFT JOIN users          rb ON rb.id = rc.responded_by
              WHERE $where
              ORDER BY rc.created_at DESC LIMIT 200",
            $params
        );

        // Counts for tab badges (always all types/statuses for the user).
        $allRows = Database::fetchAll(
            "SELECT change_type, status FROM roster_changes
              WHERE user_id = ? AND tenant_id = ?",
            [$userId, $tenantId]
        );
        $typeCounts = ['all' => 0, 'leave_request' => 0, 'correction' => 0,
                       'swap_request' => 0, 'comment' => 0];
        $statusCounts = ['all' => 0, 'pending' => 0, 'approved' => 0,
                         'rejected' => 0, 'noted' => 0];
        foreach ($allRows as $r) {
            $typeCounts['all']++;
            $statusCounts['all']++;
            if (isset($typeCounts[$r['change_type']])) $typeCounts[$r['change_type']]++;
            if (isset($statusCounts[$r['status']]))    $statusCounts[$r['status']]++;
        }

        $pageTitle    = 'My Roster Requests';
        $pageSubtitle = 'Leave, corrections, swaps and comments — all in one feed.';

        ob_start();
        require VIEWS_PATH . '/roster/all_requests.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }
}
