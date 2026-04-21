<?php
/**
 * DutyReportController — airline-side Duty Reporting admin screens.
 *
 * Routes (see config/routes.php):
 *   GET  /duty-reporting                     index  (On Duty Now + dashboard tiles)
 *   GET  /duty-reporting/history             history (filterable list)
 *   GET  /duty-reporting/exceptions          exceptions queue
 *   GET  /duty-reporting/report/{id}         detail view
 *   POST /duty-reporting/exception/{id}/approve   approveException
 *   POST /duty-reporting/exception/{id}/reject    rejectException
 *   POST /duty-reporting/report/{id}/correct      correctRecord
 *   GET  /duty-reporting/settings            settings form
 *   POST /duty-reporting/settings            saveSettings
 *
 * Role gates are applied per-method; settings requires airline_admin only.
 * Every state-changing POST is audited and notifies the affected user when
 * appropriate.
 */
class DutyReportController {

    // Airline roles that may see the management views.
    private const VIEW_ROLES = [
        'airline_admin', 'hr', 'chief_pilot', 'head_cabin_crew',
        'engineering_manager', 'base_manager', 'scheduler', 'super_admin',
    ];

    // Roles that may approve/reject exceptions or correct records.
    private const REVIEW_ROLES = [
        'airline_admin', 'chief_pilot', 'head_cabin_crew',
        'engineering_manager', 'base_manager', 'super_admin',
    ];

    private const SETTINGS_ROLES = ['airline_admin', 'super_admin'];

    // ─── GET /duty-reporting ──────────────────────────────────────────────────

    public function index(): void {
        RbacMiddleware::requireRole(self::VIEW_ROLES);
        AuthorizationService::requireModuleAccess('duty_reporting', 'view');

        $tenantId = (int) currentTenantId();
        $settings = DutyReportingSettings::forTenant($tenantId);
        $counters = DutyReport::counters($tenantId, $settings['clock_out_reminder_minutes'] + 360);
        $onDuty   = DutyReport::onDutyNow($tenantId);
        $pendingX = DutyException::pending($tenantId, 10);

        $pageTitle    = 'Duty Reporting';
        $pageSubtitle = 'On Duty Now, exceptions, and duty history';
        $headerAction = '<a href="/duty-reporting/history" class="btn btn-outline btn-sm">Full History</a>';

        ob_start();
        require VIEWS_PATH . '/duty-reporting/index.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    // ─── GET /duty-reporting/history ──────────────────────────────────────────

    public function history(): void {
        RbacMiddleware::requireRole(self::VIEW_ROLES);
        AuthorizationService::requireModuleAccess('duty_reporting', 'view');

        $tenantId = (int) currentTenantId();
        $fromDate = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
        $toDate   = $_GET['to']   ?? date('Y-m-d');
        $roleF    = $_GET['role'] ?? null;
        $userF    = !empty($_GET['user_id']) ? (int) $_GET['user_id'] : null;

        $records = DutyReport::history($tenantId, $fromDate, $toDate, $roleF, $userF, 500);

        $pageTitle    = 'Duty History';
        $pageSubtitle = 'All duty check-ins and clock-outs';
        $headerAction = '<a href="/duty-reporting" class="btn btn-outline btn-sm">Back to Overview</a>';

        ob_start();
        require VIEWS_PATH . '/duty-reporting/history.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    // ─── GET /duty-reporting/exceptions ───────────────────────────────────────

    public function exceptions(): void {
        RbacMiddleware::requireRole(self::VIEW_ROLES);
        AuthorizationService::requireModuleAccess('duty_reporting', 'view');

        $tenantId = (int) currentTenantId();
        $status   = $_GET['status'] ?? 'pending';
        $rows = $status === 'all'
            ? DutyException::history($tenantId, null, 300)
            : DutyException::history($tenantId, $status, 300);

        $pageTitle    = 'Duty Exceptions';
        $pageSubtitle = 'Review and approve or reject exception submissions';

        ob_start();
        require VIEWS_PATH . '/duty-reporting/exceptions.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    // ─── GET /duty-reporting/report/{id} ──────────────────────────────────────

    public function detail(int $id): void {
        RbacMiddleware::requireRole(self::VIEW_ROLES);
        AuthorizationService::requireModuleAccess('duty_reporting', 'view');

        $tenantId = (int) currentTenantId();
        $report = DutyReport::find($tenantId, $id);
        if (!$report) {
            flash('error', 'Duty record not found.');
            redirect('/duty-reporting');
        }

        // Enrich with user + base + exceptions
        $user = Database::fetch("SELECT id, name, email FROM users WHERE id = ?", [$report['user_id']]);
        $base = !empty($report['check_in_base_id'])
            ? Database::fetch("SELECT id, name, code FROM bases WHERE id = ?", [$report['check_in_base_id']])
            : null;
        $exceptions = DutyException::forReport($tenantId, $id);

        $pageTitle    = 'Duty Record #' . $id;
        $pageSubtitle = $user['name'] ?? 'Unknown user';

        $canReview  = hasAnyRole(self::REVIEW_ROLES);

        ob_start();
        require VIEWS_PATH . '/duty-reporting/detail.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    // ─── POST /duty-reporting/exception/{id}/approve ──────────────────────────

    public function approveException(int $id): void {
        $this->reviewException($id, DutyException::STATUS_APPROVED);
    }

    // ─── POST /duty-reporting/exception/{id}/reject ───────────────────────────

    public function rejectException(int $id): void {
        $this->reviewException($id, DutyException::STATUS_REJECTED);
    }

    private function reviewException(int $id, string $decision): void {
        RbacMiddleware::requireRole(self::REVIEW_ROLES);
        AuthorizationService::requireModuleAccess('duty_reporting', 'view');
        if (!verifyCsrf()) {
            flash('error', 'Invalid security token. Please try again.');
            redirect('/duty-reporting/exceptions');
        }

        $tenantId = (int) currentTenantId();
        $user     = currentUser();
        $notes    = trim((string) ($_POST['review_notes'] ?? ''));

        $report = DutyReportingService::applyExceptionReview(
            $tenantId,
            $id,
            (int) ($user['id'] ?? 0),
            $decision,
            $notes !== '' ? $notes : null
        );

        if (!$report) {
            flash('error', 'Exception not found.');
            redirect('/duty-reporting/exceptions');
        }

        AuditService::log(
            'duty_reporting.exception.' . $decision,
            'duty_exceptions',
            $id,
            ['duty_report_id' => $report['id'], 'notes' => $notes]
        );

        // Notify the affected crew member
        NotificationService::notifyUser(
            (int) $report['user_id'],
            'Duty exception ' . $decision,
            'Your duty exception has been ' . $decision
                . (isset($report['id']) ? ' (record #' . $report['id'] . ')' : ''),
            '/duty-reporting/report/' . $report['id']
        );

        flash('success', 'Exception ' . $decision . '.');
        redirect('/duty-reporting/exceptions');
    }

    // ─── POST /duty-reporting/report/{id}/correct ─────────────────────────────

    public function correctRecord(int $id): void {
        RbacMiddleware::requireRole(self::REVIEW_ROLES);
        AuthorizationService::requireModuleAccess('duty_reporting', 'view');
        if (!verifyCsrf()) {
            flash('error', 'Invalid security token. Please try again.');
            redirect('/duty-reporting/report/' . $id);
        }

        $tenantId = (int) currentTenantId();
        $note = trim((string) ($_POST['correction_note'] ?? ''));
        if ($note === '') {
            flash('error', 'A correction note is required.');
            redirect('/duty-reporting/report/' . $id);
        }

        $allowed = ['check_in_at_utc','check_out_at_utc','state'];
        $fields  = [];
        foreach ($allowed as $k) {
            if (!empty($_POST[$k])) $fields[$k] = $_POST[$k];
        }

        DutyReport::adminCorrect($tenantId, $id, $fields, $note);

        AuditService::log(
            'duty_reporting.record.corrected',
            'duty_reports',
            $id,
            ['fields' => array_keys($fields), 'note' => $note]
        );

        flash('success', 'Record corrected.');
        redirect('/duty-reporting/report/' . $id);
    }

    // ─── GET /duty-reporting/settings ─────────────────────────────────────────

    public function settings(): void {
        RbacMiddleware::requireRole(self::SETTINGS_ROLES);
        AuthorizationService::requireModuleAccess('duty_reporting', 'view');

        $tenantId = (int) currentTenantId();
        $settings = DutyReportingSettings::forTenant($tenantId);

        // Available role slugs for the multi-select — limited to operational crew + engineering
        $availableRoles = [
            'pilot' => 'Pilot', 'cabin_crew' => 'Cabin Crew', 'engineer' => 'Engineer',
            'chief_pilot' => 'Chief Pilot', 'head_cabin_crew' => 'Head of Cabin Crew',
            'engineering_manager' => 'Engineering Manager',
            'base_manager' => 'Base Manager', 'scheduler' => 'Scheduler',
        ];

        $pageTitle    = 'Duty Reporting Settings';
        $pageSubtitle = 'Configure geo-fence, exceptions, and reminders for this tenant';

        ob_start();
        require VIEWS_PATH . '/duty-reporting/settings.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    // ─── POST /duty-reporting/settings ────────────────────────────────────────

    public function saveSettings(): void {
        RbacMiddleware::requireRole(self::SETTINGS_ROLES);
        AuthorizationService::requireModuleAccess('duty_reporting', 'view');
        if (!verifyCsrf()) {
            flash('error', 'Invalid security token. Please try again.');
            redirect('/duty-reporting/settings');
        }

        $tenantId = (int) currentTenantId();
        $user     = currentUser();

        $fields = [
            'enabled'                     => !empty($_POST['enabled']),
            'allowed_roles'               => $_POST['allowed_roles'] ?? [],
            'geofence_required'           => !empty($_POST['geofence_required']),
            'default_radius_m'            => (int) ($_POST['default_radius_m'] ?? 500),
            'allow_outstation'            => !empty($_POST['allow_outstation']),
            'exception_approval_required' => !empty($_POST['exception_approval_required']),
            'clock_out_reminder_minutes'  => (int) ($_POST['clock_out_reminder_minutes'] ?? 840),
            'trusted_device_required'     => !empty($_POST['trusted_device_required']),
            'biometric_required'          => !empty($_POST['biometric_required']),
            'retention_days'              => (int) ($_POST['retention_days'] ?? 180),
        ];

        DutyReportingSettings::save($tenantId, $fields, (int) ($user['id'] ?? 0));

        AuditService::log(
            'duty_reporting.settings.updated',
            'duty_reporting_settings',
            $tenantId,
            ['fields' => array_keys($fields)]
        );

        flash('success', 'Duty Reporting settings saved.');
        redirect('/duty-reporting/settings');
    }
}
