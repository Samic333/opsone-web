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
        $history  = DutyReport::historyForUser($tenantId, $userId, 30);

        // Threshold caps — airline-configurable, with sensible defaults.
        $monthlyDutyCap = max(1, (int) ($settings['monthly_duty_cap_hours'] ?? 190));
        $yearlyDutyCap  = max(1, (int) ($settings['yearly_duty_cap_hours']  ?? 2000));

        // ─── Aggregates: month / previous month / YTD / monthly breakdown ──
        $monthStart = date('Y-m-01');
        $monthEnd   = date('Y-m-01', strtotime('+1 month'));
        $prevStart  = date('Y-m-01', strtotime('-1 month'));
        $prevEnd    = $monthStart;
        $yearStart  = date('Y-01-01');
        $yearEnd    = date('Y-01-01', strtotime('+1 year'));

        $sumMin = static function (int $tid, int $uid, string $from, string $to): int {
            return (int)(Database::fetch(
                "SELECT COALESCE(SUM(duration_minutes),0) AS m
                   FROM duty_reports
                  WHERE tenant_id = ? AND user_id = ?
                    AND duration_minutes IS NOT NULL
                    AND check_in_at_utc >= ? AND check_in_at_utc < ?",
                [$tid, $uid, $from . ' 00:00:00', $to . ' 00:00:00']
            )['m'] ?? 0);
        };

        $countDays = static function (int $tid, int $uid, string $type, string $from, string $to): int {
            // Match calendar visibility: published/frozen periods only (and
            // unscoped rows). Keeps Duty Time aggregates aligned with the
            // /my-roster grid the pilot actually sees.
            return (int)(Database::fetch(
                "SELECT COUNT(DISTINCT r.roster_date) AS c
                   FROM rosters r
              LEFT JOIN roster_periods p ON p.id = r.roster_period_id
                  WHERE r.tenant_id = ? AND r.user_id = ?
                    AND r.duty_type = ?
                    AND r.roster_date >= ? AND r.roster_date < ?
                    AND (r.roster_period_id IS NULL OR p.status IN ('published','frozen'))",
                [$tid, $uid, $type, $from, $to]
            )['c'] ?? 0);
        };

        $countDutyPeriods = static function (int $tid, int $uid, string $from, string $to): int {
            return (int)(Database::fetch(
                "SELECT COUNT(*) AS c FROM duty_reports
                  WHERE tenant_id = ? AND user_id = ?
                    AND check_in_at_utc >= ? AND check_in_at_utc < ?
                    AND check_out_at_utc IS NOT NULL",
                [$tid, $uid, $from . ' 00:00:00', $to . ' 00:00:00']
            )['c'] ?? 0);
        };

        $countFlights = static function (int $tid, int $uid, string $from, string $to): int {
            // Counts flight pairings where the user appears as Captain, FO,
            // or any flight_crew_assignments role. flights table may not
            // exist on all installs — guard by table-presence check.
            try {
                return (int)(Database::fetch(
                    "SELECT COUNT(DISTINCT f.id) AS c
                       FROM flights f
                  LEFT JOIN flight_crew_assignments fca ON fca.flight_id = f.id
                      WHERE f.tenant_id = ?
                        AND f.flight_date >= ? AND f.flight_date < ?
                        AND (f.captain_id = ? OR f.fo_id = ? OR fca.user_id = ?)",
                    [$tid, $from, $to, $uid, $uid, $uid]
                )['c'] ?? 0);
            } catch (\Throwable $e) {
                return 0;
            }
        };

        $dutyMonthMin = $sumMin($tenantId, $userId, $monthStart, $monthEnd);
        $dutyPrevMin  = $sumMin($tenantId, $userId, $prevStart,  $prevEnd);
        $dutyYearMin  = $sumMin($tenantId, $userId, $yearStart,  $yearEnd);

        $flightDaysMonth = $countDays($tenantId, $userId, 'flight', $monthStart, $monthEnd);
        $flightDaysYTD   = $countDays($tenantId, $userId, 'flight', $yearStart,  $yearEnd);
        $offDaysMonth    = $countDays($tenantId, $userId, 'off',    $monthStart, $monthEnd)
                         + $countDays($tenantId, $userId, 'rest',   $monthStart, $monthEnd);

        // Threshold zone classifier — matches the green/amber/red bands in the view.
        $zoneFor = static function (float $hours, int $cap): string {
            if ($cap <= 0) return 'normal';
            $pct = ($hours / $cap) * 100;
            if ($pct >= 100) return 'exceeded';
            if ($pct >= 85)  return 'approaching';
            return 'normal';
        };

        // Monthly breakdown — last 6 months, enriched with duty period count,
        // flight count, and a per-row threshold zone.
        $breakdown = [];
        for ($i = 5; $i >= 0; $i--) {
            $mFrom = date('Y-m-01', strtotime("-$i month"));
            $mTo   = date('Y-m-01', strtotime("-" . ($i - 1) . " month"));
            $mMin  = $sumMin($tenantId, $userId, $mFrom, $mTo);
            $mHrs  = round($mMin / 60, 1);
            $breakdown[] = [
                'label'            => date('M Y', strtotime($mFrom)),
                'duty_min'         => $mMin,
                'duty_hours'       => $mHrs,
                'flight_days'      => $countDays($tenantId, $userId, 'flight', $mFrom, $mTo),
                'off_days'         => $countDays($tenantId, $userId, 'off',  $mFrom, $mTo)
                                    + $countDays($tenantId, $userId, 'rest', $mFrom, $mTo),
                'duty_periods'     => $countDutyPeriods($tenantId, $userId, $mFrom, $mTo),
                'flights'          => $countFlights($tenantId, $userId, $mFrom, $mTo),
                'threshold_status' => $zoneFor($mHrs, $monthlyDutyCap),
            ];
        }

        $monthHours = round($dutyMonthMin / 60, 1);
        $ytdHours   = round($dutyYearMin  / 60, 1);

        // Active duty + rest period.
        // - If currently on duty: rest_minutes is null, show on-duty timer.
        // - If off duty: minutes since most recent check_out_at_utc.
        $activeDutyMinutes = null;
        if ($current && !empty($current['check_in_at_utc'])) {
            $start = strtotime($current['check_in_at_utc']);
            if ($start) $activeDutyMinutes = max(0, (int) floor((time() - $start) / 60));
        }

        $restMinutes = null;
        if (!$current) {
            $lastOut = Database::fetch(
                "SELECT check_out_at_utc FROM duty_reports
                  WHERE tenant_id = ? AND user_id = ?
                    AND check_out_at_utc IS NOT NULL
               ORDER BY check_out_at_utc DESC LIMIT 1",
                [$tenantId, $userId]
            );
            if ($lastOut && !empty($lastOut['check_out_at_utc'])) {
                $t = strtotime($lastOut['check_out_at_utc']);
                if ($t) $restMinutes = max(0, (int) floor((time() - $t) / 60));
            }
        }

        // Enrich history rows with route + duty_code derived from flights/rosters
        // for the matching date — keeps the table single-query in the view.
        $history = $this->enrichHistoryRoutes($tenantId, $userId, $history);

        $remainingMonthHours = max(0, round($monthlyDutyCap - $monthHours, 1));
        $remainingYearHours  = max(0, round($yearlyDutyCap  - $ytdHours,   1));

        $aggregates = [
            'duty_hours_month'      => $monthHours,
            'duty_hours_prev'       => round($dutyPrevMin / 60, 1),
            'duty_hours_ytd'        => $ytdHours,
            'flight_days_month'    => $flightDaysMonth,
            'flight_days_ytd'      => $flightDaysYTD,
            'off_days_month'       => $offDaysMonth,
            'monthly_cap_hours'    => $monthlyDutyCap,
            'yearly_cap_hours'     => $yearlyDutyCap,
            'remaining_month_hours' => $remainingMonthHours,
            'remaining_year_hours'  => $remainingYearHours,
            'month_threshold'      => $zoneFor($monthHours, $monthlyDutyCap),
            'ytd_threshold'        => $zoneFor($ytdHours,   $yearlyDutyCap),
            'active_duty_minutes'  => $activeDutyMinutes,
            'rest_minutes'         => $restMinutes,
            'breakdown'            => $breakdown,
        ];

        $pageTitle    = 'Duty Time';
        $pageSubtitle = $current
            ? 'You are on duty — remember to clock out at the end of your shift.'
            : 'Report for duty to start a new duty event.';

        ob_start();
        require VIEWS_PATH . '/duty-reporting/my_duty.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    /**
     * Add `route` (e.g. "NBO → MGQ") and `duty_code` to each history row by
     * looking up the matching flight or roster entry for the same date. Failure
     * is non-fatal — rows just keep an empty route.
     */
    private function enrichHistoryRoutes(int $tenantId, int $userId, array $rows): array {
        if (!$rows) return $rows;
        foreach ($rows as $i => $r) {
            $rows[$i]['route']     = null;
            $rows[$i]['duty_code'] = null;
            if (empty($r['check_in_at_utc'])) continue;
            $date = substr((string) $r['check_in_at_utc'], 0, 10);
            try {
                $flt = Database::fetch(
                    "SELECT departure, arrival, flight_number FROM flights
                      WHERE tenant_id = ? AND flight_date = ?
                        AND (captain_id = ? OR fo_id = ?
                             OR id IN (SELECT flight_id FROM flight_crew_assignments WHERE user_id = ?))
                   ORDER BY std ASC LIMIT 1",
                    [$tenantId, $date, $userId, $userId, $userId]
                );
                if ($flt && !empty($flt['departure']) && !empty($flt['arrival'])) {
                    $rows[$i]['route'] = $flt['departure'] . ' → ' . $flt['arrival'];
                    if (!empty($flt['flight_number'])) {
                        $rows[$i]['duty_code'] = $flt['flight_number'];
                    }
                }
            } catch (\Throwable $e) { /* flights table may not exist on slim installs */ }

            if (empty($rows[$i]['duty_code']) || empty($rows[$i]['route'])) {
                try {
                    $ros = Database::fetch(
                        "SELECT duty_type, duty_code FROM rosters
                          WHERE tenant_id = ? AND user_id = ? AND roster_date = ?
                       ORDER BY id DESC LIMIT 1",
                        [$tenantId, $userId, $date]
                    );
                    if ($ros) {
                        if (empty($rows[$i]['duty_code']) && !empty($ros['duty_code'])) {
                            $rows[$i]['duty_code'] = $ros['duty_code'];
                        }
                    }
                } catch (\Throwable $e) { /* ignore */ }
            }
        }
        return $rows;
    }

    /**
     * GET /my-duty/{id} — pilot-side view of a single duty record.
     * Owner-only; admin detail at /duty-reporting/report/{id} stays separate.
     */
    public function myDutyDetail(int $id): void {
        $tenantId = $this->requireCrewAccess('/my-duty');
        if ($tenantId === null) return;

        $user   = currentUser();
        $userId = (int) ($user['id'] ?? 0);

        $report = DutyReport::find($tenantId, $id);
        if (!$report || (int) $report['user_id'] !== $userId) {
            flash('error', 'Duty record not found.');
            redirect('/my-duty');
        }

        // Enrichment: base, exceptions, matching roster + flight + flight crew
        $base = !empty($report['check_in_base_id'])
            ? Database::fetch("SELECT id, name, code FROM bases WHERE id = ?", [$report['check_in_base_id']])
            : null;
        $exceptions = DutyException::forReport($tenantId, $id);

        $date = !empty($report['check_in_at_utc'])
            ? substr((string) $report['check_in_at_utc'], 0, 10)
            : null;

        $roster = null;
        $flight = null;
        $flightCrew = [];
        if ($date) {
            try {
                $roster = Database::fetch(
                    "SELECT r.duty_type, r.duty_code, r.notes,
                            f.name AS fleet_name,
                            b.code AS base_code, b.name AS base_name
                       FROM rosters r
                  LEFT JOIN fleets f ON f.id = r.fleet_id
                  LEFT JOIN bases  b ON b.id = r.base_id
                      WHERE r.tenant_id = ? AND r.user_id = ? AND r.roster_date = ?
                   ORDER BY r.id DESC LIMIT 1",
                    [$tenantId, $userId, $date]
                );
            } catch (\Throwable $e) { /* schema may differ */ }

            try {
                $flight = Database::fetch(
                    "SELECT id, flight_number, departure, arrival, std, sta,
                            captain_id, fo_id, aircraft_id, status
                       FROM flights
                      WHERE tenant_id = ? AND flight_date = ?
                        AND (captain_id = ? OR fo_id = ?
                             OR id IN (SELECT flight_id FROM flight_crew_assignments WHERE user_id = ?))
                   ORDER BY std ASC LIMIT 1",
                    [$tenantId, $date, $userId, $userId, $userId]
                );
            } catch (\Throwable $e) { /* flights table may not exist */ }

            if ($flight) {
                try {
                    $flightCrew = Database::fetchAll(
                        "SELECT u.id, u.name, 'captain' AS role_on_flight
                           FROM users u WHERE u.id = ?
                          UNION ALL
                         SELECT u.id, u.name, 'first_officer' AS role_on_flight
                           FROM users u WHERE u.id = ?
                          UNION ALL
                         SELECT u.id, u.name, fca.role_on_flight
                           FROM flight_crew_assignments fca
                           JOIN users u ON u.id = fca.user_id
                          WHERE fca.flight_id = ?",
                        [(int) $flight['captain_id'], (int) $flight['fo_id'], (int) $flight['id']]
                    );
                    // De-duplicate on user_id, keep first occurrence.
                    $seen = [];
                    $flightCrew = array_values(array_filter($flightCrew, static function ($r) use (&$seen) {
                        if (empty($r['id']) || isset($seen[$r['id']])) return false;
                        $seen[$r['id']] = true;
                        return true;
                    }));
                } catch (\Throwable $e) { /* ignore */ }
            }
        }

        // Has the pilot already submitted a correction request for this record?
        $hasOpenCorrection = false;
        foreach ($exceptions as $ex) {
            if ($ex['reason_code'] === 'manual_correction'
                && in_array($ex['status'], ['pending'], true)) {
                $hasOpenCorrection = true;
                break;
            }
        }

        $pageTitle    = 'Duty Record #' . $id;
        $pageSubtitle = $date ? ('Duty on ' . $date) : 'Duty record details';

        ob_start();
        require VIEWS_PATH . '/duty-reporting/my_duty_detail.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    /**
     * POST /my-duty/{id}/request-correction — pilot submits a correction
     * request against their own duty record. Reuses the duty_exceptions
     * table with reason_code='manual_correction' so admins triage it from
     * the existing /duty-reporting/exceptions queue.
     */
    public function myDutyRequestCorrection(int $id): void {
        $tenantId = $this->requireCrewAccess('/my-duty');
        if ($tenantId === null) return;
        if (!verifyCsrf()) {
            flash('error', 'Invalid security token. Please try again.');
            redirect('/my-duty/' . $id);
        }

        $user   = currentUser();
        $userId = (int) ($user['id'] ?? 0);

        $report = DutyReport::find($tenantId, $id);
        if (!$report || (int) $report['user_id'] !== $userId) {
            flash('error', 'Duty record not found.');
            redirect('/my-duty');
        }

        $note = trim((string) ($_POST['correction_note'] ?? ''));
        if ($note === '') {
            flash('error', 'Please describe what needs to be corrected.');
            redirect('/my-duty/' . $id);
        }
        if (mb_strlen($note) > 1000) {
            $note = mb_substr($note, 0, 1000);
        }

        $exceptionId = DutyException::create(
            $tenantId,
            $id,
            $userId,
            'manual_correction',
            $note
        );

        AuditService::log(
            'duty_reporting.correction.requested',
            'duty_exceptions',
            (int) $exceptionId,
            ['duty_report_id' => $id, 'via' => 'web']
        );

        // Notify reviewers via the same channels used for exceptions.
        foreach (['airline_admin', 'chief_pilot', 'head_cabin_crew'] as $role) {
            NotificationService::notifyTenant(
                $tenantId, $role,
                'Duty correction request',
                ($user['name'] ?? 'A crew member') . ' requested a correction on duty record #' . $id . '.',
                '/duty-reporting/exceptions'
            );
        }

        flash('success', 'Correction request submitted. A manager will review it.');
        redirect('/my-duty/' . $id);
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
        AuthorizationService::requireModuleEnabled('duty_reporting');

        $tenantId = (int) currentTenantId();
        $settings = DutyReportingSettings::forTenant($tenantId);

        // Promote any records past the reminder + grace window to missed_report.
        // Replaces a scheduled task on shared hosting where cron isn't available.
        DutyReportingService::markOverdue($tenantId);

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
        AuthorizationService::requireModuleEnabled('duty_reporting');

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
        AuthorizationService::requireModuleEnabled('duty_reporting');

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
        AuthorizationService::requireModuleEnabled('duty_reporting');

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
        AuthorizationService::requireModuleEnabled('duty_reporting');
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
        AuthorizationService::requireModuleEnabled('duty_reporting');
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
        AuthorizationService::requireModuleEnabled('duty_reporting');

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
        AuthorizationService::requireModuleEnabled('duty_reporting');
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
            'monthly_duty_cap_hours'      => (int) ($_POST['monthly_duty_cap_hours'] ?? 190),
            'yearly_duty_cap_hours'       => (int) ($_POST['yearly_duty_cap_hours']  ?? 2000),
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
