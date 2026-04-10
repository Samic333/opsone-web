<?php
/**
 * RosterController — monthly crew roster grid (web)
 *
 * Accessible by: scheduler, airline_admin, super_admin, chief_pilot, head_cabin_crew
 */
class RosterController {

    public function index(): void {
        $tenantId = currentTenantId();

        $year  = (int) ($_GET['year']  ?? date('Y'));
        $month = (int) ($_GET['month'] ?? date('n'));

        // Clamp month
        if ($month < 1) { $month = 12; $year--; }
        if ($month > 12) { $month = 1;  $year++; }

        $daysInMonth      = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $grid             = RosterModel::getMonth($tenantId, $year, $month);
        $crewList         = RosterModel::getCrewList($tenantId);
        $dutyTypes        = RosterModel::dutyTypes();
        $complianceIssues = RosterModel::getComplianceIssues($tenantId);

        $prevMonth = $month - 1 < 1  ? 12 : $month - 1;
        $prevYear  = $month - 1 < 1  ? $year - 1 : $year;
        $nextMonth = $month + 1 > 12 ? 1  : $month + 1;
        $nextYear  = $month + 1 > 12 ? $year + 1 : $year;

        $pageTitle    = 'Crew Roster';
        $pageSubtitle = date('F Y', mktime(0, 0, 0, $month, 1, $year));
        $headerAction = hasAnyRole(['scheduler', 'airline_admin', 'super_admin'])
            ? '<a href="/roster/assign" class="btn btn-primary">＋ Assign Duty</a>'
            : '';

        ob_start();
        require VIEWS_PATH . '/roster/index.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    /**
     * Show assign-duty form (GET)
     */
    public function assignForm(): void {
        RbacMiddleware::requireRole(['scheduler', 'airline_admin', 'super_admin', 'chief_pilot', 'head_cabin_crew']);

        $tenantId  = currentTenantId();
        $crewList  = RosterModel::getCrewList($tenantId);
        $dutyTypes = RosterModel::dutyTypes();

        $pageTitle    = 'Assign Duty';
        $pageSubtitle = 'Add or update a roster entry';

        ob_start();
        require VIEWS_PATH . '/roster/assign.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    /**
     * Save roster entry (POST /roster/assign)
     */
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

        // Ensure user belongs to this tenant
        $user = Database::fetch("SELECT id FROM users WHERE id = ? AND tenant_id = ?", [$userId, $tenantId]);
        if (!$user) {
            flash('error', 'User not found.');
            redirect('/roster/assign');
        }

        RosterModel::upsert($tenantId, $userId, $date, $dutyType, $dutyCode, $notes);

        AuditLog::log('roster_assigned', 'roster', $userId, "Assigned {$dutyType} on {$date}");
        flash('success', 'Duty assigned.');

        // Return to the month of the assigned date
        [$y, $m] = explode('-', $date);
        redirect("/roster?year={$y}&month=" . ltrim($m, '0'));
    }

    /**
     * Standby pool for today (GET /roster/standby)
     */
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

    /**
     * Suggest replacement crew (GET /roster/suggest/{id}?date=YYYY-MM-DD)
     */
    public function suggest(int $userId): void {
        RbacMiddleware::requireRole(['scheduler', 'airline_admin', 'super_admin', 'chief_pilot', 'head_cabin_crew']);

        $tenantId = currentTenantId();
        $date     = $_GET['date'] ?? date('Y-m-d');

        // Load the crew member being replaced
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

        // Their current roster entry for this date
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

    /**
     * Delete a roster entry (POST /roster/delete/{id})
     */
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
}
