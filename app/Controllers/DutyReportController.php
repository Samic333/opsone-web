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

    // =========================================================================
    // CREW SELF-SERVICE (web) — mirror of the iPad check-in / clock-out surface
    // Routes: GET /my-duty, POST /my-duty/check-in, POST /my-duty/clock-out
    //
    // Gated by the tenant's duty_reporting_settings.allowed_roles list, not
    // by hardcoded role slugs — so an airline can include chief_pilot /
    // head_cabin_crew and allow managers who fly lines to self-check-in.
    // =========================================================================

    public function myDuty(): void {
        $tenantId = $this->requireCrewAccess('/dashboard');
        if ($tenantId === null) return;

        $user     = currentUser();
        $userId   = (int) ($user['id'] ?? 0);
        $settings = DutyReportingSettings::forTenant($tenantId);
        $current  = DutyReport::findOpenForUser($tenantId, $userId);
        $history  = DutyReport::historyForUser($tenantId, $userId, 10);

        $pageTitle    = 'My Duty';
        $pageSubtitle = $current
            ? 'You are on duty — remember to clock out at the end of your shift.'
            : 'Report for duty to start a new duty event.';

        ob_start();
        require VIEWS_PATH . '/duty-reporting/my_duty.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function myDutyCheckIn(): void {
        $tenantId = $this->requireCrewAccess('/my-duty');
        if ($tenantId === null) return;
        if (!verifyCsrf()) {
            flash('error', 'Invalid security token. Please try again.');
            redirect('/my-duty');
        }

        $user     = currentUser();
        $userId   = (int) ($user['id'] ?? 0);
        $roles    = UserModel::getRoleSlugs($userId);
        $primary  = $roles[0] ?? null;
        $reasonTx = trim((string) ($_POST['exception_reason_text'] ?? ''));
        $reasonCd = trim((string) ($_POST['exception_reason_code'] ?? ''));

        $payload = [
            'tenant_id'             => $tenantId,
            'user_id'               => $userId,
            'role_slug'             => $primary,
            'lat'                   => null,   // web has no GPS; server still processes fine
            'lng'                   => null,
            'local_time'            => date('Y-m-d H:i:s'),
            'method'                => 'manual',
            'notes'                 => trim((string) ($_POST['notes'] ?? '')) ?: null,
            'exception_reason_code' => $reasonCd !== '' ? $reasonCd : null,
            'exception_reason_text' => $reasonTx !== '' ? $reasonTx : null,
        ];

        $result = DutyReportingService::performCheckIn($payload);

        if (!$result['ok']) {
            // exception_note_required is expected when tenant policy forces
            // a reason — stash state in flash so the form can re-render.
            if ($result['error'] === 'exception_note_required') {
                $_SESSION['duty_exception_pending'] = [
                    'reason_code' => $result['exception']['reason_code'] ?? 'other',
                    'notes'       => $payload['notes'] ?? '',
                ];
                flash('warning', 'This check-in needs a reason. Please fill the form below.');
            } elseif ($result['error'] === 'already_on_duty') {
                flash('warning', 'You are already on duty.');
            } elseif ($result['error'] === 'module_disabled') {
                flash('error', 'Duty Reporting is not enabled for your airline.');
            } else {
                flash('error', 'Check-in blocked: ' . str_replace('_', ' ', (string)$result['error']));
            }
            AuditService::log(
                'duty_reporting.check_in.blocked',
                'duty_reports',
                $result['duty_report_id'] ?? null,
                ['via' => 'web', 'error' => $result['error']],
                'blocked',
                $result['error']
            );
            redirect('/my-duty');
        }

        unset($_SESSION['duty_exception_pending']);

        AuditService::log(
            'duty_reporting.check_in',
            'duty_reports',
            (int) $result['duty_report_id'],
            [
                'via'             => 'web',
                'state'           => $result['state'],
                'has_exception'   => !empty($result['exception']),
            ]
        );

        if (!empty($result['exception']) && $result['state'] === DutyReport::STATE_EXCEPTION_PENDING) {
            NotificationService::notifyTenant(
                $tenantId, 'airline_admin',
                'Duty exception pending review',
                ($user['name'] ?? 'A crew member') . ' submitted an exception via web.',
                '/duty-reporting/exceptions'
            );
            NotificationService::notifyTenant(
                $tenantId, 'chief_pilot',
                'Duty exception pending review',
                ($user['name'] ?? 'A crew member') . ' submitted an exception via web.',
                '/duty-reporting/exceptions'
            );
            flash('warning', 'Check-in submitted — pending manager review.');
        } else {
            flash('success', 'Reported for duty.');
        }
        redirect('/my-duty');
    }

    public function myDutyClockOut(): void {
        $tenantId = $this->requireCrewAccess('/my-duty');
        if ($tenantId === null) return;
        if (!verifyCsrf()) {
            flash('error', 'Invalid security token. Please try again.');
            redirect('/my-duty');
        }

        $user     = currentUser();
        $userId   = (int) ($user['id'] ?? 0);

        $result = DutyReportingService::performClockOut([
            'tenant_id'  => $tenantId,
            'user_id'    => $userId,
            'lat'        => null,
            'lng'        => null,
            'local_time' => date('Y-m-d H:i:s'),
            'notes'      => trim((string) ($_POST['notes'] ?? '')) ?: null,
        ]);

        if (!$result['ok']) {
            AuditService::log(
                'duty_reporting.clock_out.blocked',
                'duty_reports',
                null,
                ['via' => 'web', 'error' => $result['error']],
                'blocked',
                $result['error']
            );
            if ($result['error'] === 'no_active_duty') {
                flash('warning', 'You have no active duty record to clock out.');
            } else {
                flash('error', 'Clock-out blocked: ' . str_replace('_', ' ', (string)$result['error']));
            }
            redirect('/my-duty');
        }

        AuditService::log(
            'duty_reporting.clock_out',
            'duty_reports',
            (int) $result['duty_report_id'],
            [
                'via'              => 'web',
                'state'            => $result['state'],
                'duration_minutes' => $result['duration_minutes'] ?? null,
            ]
        );

        flash('success', 'Clocked out. Duration: ' . ($result['duration_minutes'] ?? 0) . ' minutes.');
        redirect('/my-duty');
    }

    /**
     * Gate for all /my-duty/* crew endpoints. Returns the resolved tenant id,
     * or null (after redirecting) if the user isn't allowed.
     *
     * Allowed when: module enabled for tenant, tenant settings.enabled = 1,
     * AND any of the user's role slugs appears in settings.allowed_roles.
     * No hardcoded role list — the tenant's admin decides who can use it.
     */
    private function requireCrewAccess(string $redirectOnDenyTo): ?int {
        $tenantId = (int) currentTenantId();
        if ($tenantId <= 0) {
            flash('error', 'Please log in first.');
            redirect('/login');
            return null;
        }

        if (!AuthorizationService::isModuleEnabledForTenant('duty_reporting', $tenantId)) {
            flash('warning', 'Duty Reporting is not enabled for your airline.');
            redirect($redirectOnDenyTo);
            return null;
        }

        $settings = DutyReportingSettings::forTenant($tenantId);
        if (!$settings['enabled']) {
            flash('warning', 'Duty Reporting is temporarily disabled by your airline admin.');
            redirect($redirectOnDenyTo);
            return null;
        }

        $user     = currentUser();
        $userId   = (int) ($user['id'] ?? 0);
        $roles    = UserModel::getRoleSlugs($userId);
        if (!DutyReportingSettings::userAllowed($tenantId, $roles)) {
            flash('error', 'Your role is not permitted to use Duty Reporting. Ask your airline admin.');
            redirect($redirectOnDenyTo);
            return null;
        }

        return $tenantId;
    }

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
