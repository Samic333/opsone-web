<?php
/**
 * SafetyController — Handles safety reports (viewing, submitting, processing)
 */
class SafetyController {

    // ─── ADMIN DASHBOARD ───────────────────────────────────────

    public function index(): void {
        RbacMiddleware::requireRole(['safety_officer', 'airline_admin', 'super_admin']);

        $tenantId = currentTenantId();
        $statusFilter = $_GET['status'] ?? 'open';

        $reports = SafetyReportModel::allForTenant($tenantId, $statusFilter);

        // Stats
        $all      = SafetyReportModel::allForTenant($tenantId, 'all');
        $openCnt  = 0;
        $investCnt= 0;
        $closedCnt= 0;
        foreach ($all as $r) {
            if ($r['status'] === SafetyReportModel::STATUS_CLOSED) {
                $closedCnt++;
            } elseif ($r['status'] === SafetyReportModel::STATUS_INVESTIG) {
                $investCnt++;
                $openCnt++;
            } else {
                $openCnt++;
            }
        }

        $pageTitle    = 'Safety Management';
        $pageSubtitle = 'Aviation Safety, Hazards, and Compliance Reports.';

        ob_start();
        require VIEWS_PATH . '/safety/index.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function view(int $id): void {
        RbacMiddleware::requireRole(['safety_officer', 'airline_admin', 'super_admin']);

        $tenantId = currentTenantId();
        $report   = SafetyReportModel::find($tenantId, $id);

        if (!$report) {
            flash('error', 'Report not found or access denied.');
            redirect('/safety');
        }

        $updates = SafetyReportModel::getUpdates($id);
        
        $crewList = UserModel::allForTenant($tenantId);

        $pageTitle = 'Safety Report: ' . $report['reference_no'];
        $pageSubtitle = "Filed on " . date('d M Y, H:i', strtotime($report['created_at']));

        ob_start();
        require VIEWS_PATH . '/safety/view.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function update(int $id): void {
        RbacMiddleware::requireRole(['safety_officer', 'airline_admin', 'super_admin']);
        verifyCsrf();

        $tenantId = currentTenantId();
        $user     = currentUser();

        $report = SafetyReportModel::find($tenantId, $id);
        if (!$report) {
            flash('error', 'Report not found.');
            redirect('/safety');
        }

        $statusChange   = ($_POST['status']   ?? '') !== $report['status']   ? ($_POST['status'] ?? null)   : null;
        $severityChange = ($_POST['severity'] ?? '') !== $report['severity'] ? ($_POST['severity'] ?? null) : null;
        $comment        = trim($_POST['comment'] ?? '');

        // Also check assignment
        $assignedTo = !empty($_POST['assigned_to']) ? (int) $_POST['assigned_to'] : null;

        if ($statusChange || $severityChange || $comment || $assignedTo !== $report['assigned_to']) {
            $updateData = [
                'status_change'   => $statusChange,
                'severity_change' => $severityChange,
                'comment'         => $comment ?: null,
                'assigned_to'     => $assignedTo,
            ];
            SafetyReportModel::addUpdate($tenantId, $id, (int) $user['id'], $updateData);
            
            AuditLog::log("Updated Safety Report {$report['reference_no']}", 'safety_reports', $id);
            flash('success', 'Report updated successfully.');
        } else {
            flash('info', 'No changes made.');
        }

        redirect("/safety/report/$id");
    }

    // ─── CREW SUBMISSION ───────────────────────────────────────

    public function myReports(): void {
        requireAuth(); // Open to any logged in user
        $user = currentUser();
        $reports = SafetyReportModel::forUser($user['tenant_id'], $user['id']);

        $pageTitle = 'My Safety Submissions';
        $pageSubtitle = 'A history of reports you have securely filed.';

        ob_start();
        require VIEWS_PATH . '/safety/my_reports.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function submitForm(): void {
        requireAuth();
        $pageTitle = 'Submit Safety Report';
        $pageSubtitle = 'Confidential safety, hazard, and incident reporting. Protected under Just Culture policy.';

        ob_start();
        require VIEWS_PATH . '/safety/submit.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function submit(): void {
        requireAuth();
        verifyCsrf();

        $user = currentUser();
        $tenantId = $user['tenant_id'];

        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $type        = trim($_POST['report_type'] ?? 'ASR');
        $isAnon      = !empty($_POST['is_anonymous']);
        $eventDate   = trim($_POST['event_date'] ?? '');

        if (!$title || !$description) {
            flash('error', 'Title and description are required.');
            redirect('/safety/submit');
        }

        $data = [
            'title'        => $title,
            'description'  => $description,
            'report_type'  => $type,
            'reporter_id'  => $user['id'],
            'is_anonymous' => $isAnon,
            'event_date'   => $eventDate,
        ];

        $reportId = SafetyReportModel::submit($tenantId, $data);
        
        $rpt = SafetyReportModel::find($tenantId, $reportId);
        AuditLog::log("Submitted Safety Report {$rpt['reference_no']}", 'safety_reports', $reportId);

        flash('success', "Report successfully submitted. Reference: {$rpt['reference_no']}");
        redirect('/safety/my-reports');
    }
}
