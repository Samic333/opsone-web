<?php
/**
 * DutyReportingApiController — Duty Reporting API (iPad / CrewAssist)
 *
 * All endpoints:
 *   • require bearer-token auth via requireApiAuth()
 *   • enforce tenant isolation via apiTenantId()
 *   • enforce module enablement + role allowance (tenant settings)
 *   • write audit entries on state-changing calls
 *   • return JSON
 *
 * Routes (see config/routes.php):
 *   GET  /api/duty-reporting/status            — current state + settings summary
 *   POST /api/duty-reporting/check-in          — Report for Duty
 *   POST /api/duty-reporting/clock-out         — Clock Out
 *   GET  /api/duty-reporting/history           — user's own history
 *   GET  /api/duty-reporting/bases             — bases with geo (for on-device matching)
 */
class DutyReportingApiController {

    // ─── GET /api/duty-reporting/status ───────────────────────────────────────

    public function status(): void {
        // Auth already enforced by ApiAuthMiddleware before this action runs (see public/index.php).
        $user     = apiUser();
        $tenantId = apiTenantId();
        $userId   = (int) ($user['user_id'] ?? $user['id'] ?? 0);

        if (!$this->ensureModuleAndRole($tenantId, $userId)) return;

        $settings = DutyReportingSettings::forTenant($tenantId);
        $open     = DutyReport::findOpenForUser($tenantId, $userId);
        $history  = DutyReport::historyForUser($tenantId, $userId, 5);

        jsonResponse([
            'enabled'        => (bool) $settings['enabled'],
            'settings'       => self::clientSettings($settings),
            'current'        => $open ? self::formatReport($open) : null,
            'recent_history' => array_map([self::class, 'formatReport'], $history),
        ]);
    }

    // ─── POST /api/duty-reporting/check-in ────────────────────────────────────

    public function checkIn(): void {
        // Auth already enforced by ApiAuthMiddleware before this action runs (see public/index.php).
        $user     = apiUser();
        $tenantId = apiTenantId();
        $userId   = (int) ($user['user_id'] ?? $user['id'] ?? 0);

        if (!$this->ensureModuleAndRole($tenantId, $userId)) return;

        $body = $this->parseBody();

        // Pull primary role slug (for role_at_event)
        $roles     = apiUserRoles() ?: UserModel::getRoleSlugs($userId);
        $roleSlug  = $body['role_slug'] ?? ($roles[0] ?? null);

        $input = [
            'tenant_id'              => $tenantId,
            'user_id'                => $userId,
            'role_slug'              => $roleSlug,
            'lat'                    => isset($body['lat']) && $body['lat'] !== '' ? (float) $body['lat'] : null,
            'lng'                    => isset($body['lng']) && $body['lng'] !== '' ? (float) $body['lng'] : null,
            'local_time'             => trim((string) ($body['local_time']   ?? '')) ?: null,
            'method'                 => $body['method']                              ?? 'device',
            'trusted_device_id'      => $body['trusted_device_id']                   ?? null,
            'device_uuid'            => $body['device_uuid']                         ?? null,
            'notes'                  => trim((string) ($body['notes']        ?? '')) ?: null,
            'gps_unavailable'        => !empty($body['gps_unavailable']),
            'offline_queue'          => !empty($body['offline_queue']),
            'exception_reason_code'  => $body['exception_reason_code']               ?? null,
            'exception_reason_text'  => $body['exception_reason_text']               ?? null,
        ];

        $result = DutyReportingService::performCheckIn($input);

        if (!$result['ok']) {
            $http = match ($result['error']) {
                'already_on_duty'          => 409,
                'module_disabled'          => 403,
                'exception_note_required'  => 422,
                default                    => 422,
            };
            AuditService::logApi(
                'duty_reporting.check_in.blocked',
                'duty_reports',
                $result['duty_report_id'] ?? null,
                $result['error'],
                'blocked',
                $result['error']
            );
            jsonResponse([
                'success'         => false,
                'error'           => $result['error'],
                'duty_report_id'  => $result['duty_report_id'],
                'state'           => $result['state'],
                'inside_geofence' => $result['inside_geofence'],
                'matched_base'    => $result['matched_base'],
                'exception'       => $result['exception'],
            ], $http);
            return;
        }

        $reportId = (int) $result['duty_report_id'];
        $report   = DutyReport::find($tenantId, $reportId);

        AuditService::logApi(
            'duty_reporting.check_in',
            'duty_reports',
            $reportId,
            [
                'state'           => $result['state'],
                'inside_geofence' => $result['inside_geofence'],
                'method'          => $input['method'],
                'base_id'         => $result['matched_base']['id'] ?? null,
                'has_exception'   => !empty($result['exception']),
            ]
        );

        // Notify managers if exception is pending review
        if (!empty($result['exception']) && $result['state'] === DutyReport::STATE_EXCEPTION_PENDING) {
            $roleSlug = DutyException::REASONS[$result['exception']['reason_code']] ?? 'exception';
            NotificationService::notifyTenant(
                $tenantId,
                'airline_admin',
                'Duty exception pending review',
                ($user['name'] ?? 'A crew member') . ' submitted an exception: ' . $roleSlug,
                '/duty-reporting/exceptions'
            );
            NotificationService::notifyTenant(
                $tenantId,
                'chief_pilot',
                'Duty exception pending review',
                ($user['name'] ?? 'A crew member') . ' submitted an exception: ' . $roleSlug,
                '/duty-reporting/exceptions'
            );
        }

        jsonResponse([
            'success'         => true,
            'duty_report'     => self::formatReport($report),
            'inside_geofence' => $result['inside_geofence'],
            'matched_base'    => $result['matched_base'],
            'exception'       => $result['exception'],
        ]);
    }

    // ─── POST /api/duty-reporting/clock-out ───────────────────────────────────

    public function clockOut(): void {
        // Auth already enforced by ApiAuthMiddleware before this action runs (see public/index.php).
        $user     = apiUser();
        $tenantId = apiTenantId();
        $userId   = (int) ($user['user_id'] ?? $user['id'] ?? 0);

        if (!$this->ensureModuleAndRole($tenantId, $userId)) return;

        $body = $this->parseBody();

        $result = DutyReportingService::performClockOut([
            'tenant_id'  => $tenantId,
            'user_id'    => $userId,
            'lat'        => isset($body['lat']) && $body['lat'] !== '' ? (float) $body['lat'] : null,
            'lng'        => isset($body['lng']) && $body['lng'] !== '' ? (float) $body['lng'] : null,
            'local_time' => trim((string) ($body['local_time'] ?? '')) ?: null,
            'notes'      => trim((string) ($body['notes']      ?? '')) ?: null,
        ]);

        if (!$result['ok']) {
            AuditService::logApi(
                'duty_reporting.clock_out.blocked',
                'duty_reports',
                null,
                $result['error'],
                'blocked',
                $result['error']
            );
            $http = $result['error'] === 'no_active_duty' ? 404 : 422;
            jsonResponse(['success' => false, 'error' => $result['error']], $http);
            return;
        }

        AuditService::logApi(
            'duty_reporting.clock_out',
            'duty_reports',
            $result['duty_report_id'],
            [
                'state'            => $result['state'],
                'duration_minutes' => $result['duration_minutes'],
            ]
        );

        $report = DutyReport::find($tenantId, (int) $result['duty_report_id']);
        jsonResponse([
            'success'     => true,
            'duty_report' => self::formatReport($report),
            'summary'     => [
                'duration_minutes' => $result['duration_minutes'],
                'checked_out_at'   => $result['check_out_at_utc'],
            ],
        ]);
    }

    // ─── GET /api/duty-reporting/history ──────────────────────────────────────

    public function history(): void {
        // Auth already enforced by ApiAuthMiddleware before this action runs (see public/index.php).
        $user     = apiUser();
        $tenantId = apiTenantId();
        $userId   = (int) ($user['user_id'] ?? $user['id'] ?? 0);

        if (!$this->ensureModuleAndRole($tenantId, $userId)) return;

        $limit = max(1, min(200, (int) ($_GET['limit'] ?? 30)));
        $rows  = DutyReport::historyForUser($tenantId, $userId, $limit);

        jsonResponse([
            'history' => array_map([self::class, 'formatReport'], $rows),
        ]);
    }

    // ─── GET /api/duty-reporting/bases ────────────────────────────────────────

    public function bases(): void {
        // Auth already enforced by ApiAuthMiddleware before this action runs (see public/index.php).
        $user     = apiUser();
        $tenantId = apiTenantId();

        if (!$this->ensureModuleAndRole($tenantId, (int) ($user['user_id'] ?? $user['id'] ?? 0))) return;

        $rows = Database::fetchAll(
            "SELECT id, name, code, latitude, longitude, geofence_radius_m, timezone
               FROM bases
              WHERE tenant_id = ?
           ORDER BY name ASC",
            [$tenantId]
        );
        $fmt = array_map(function(array $b): array {
            return [
                'id'                => (int) $b['id'],
                'name'              => $b['name'],
                'code'              => $b['code'],
                'latitude'          => isset($b['latitude'])  ? (float) $b['latitude']  : null,
                'longitude'         => isset($b['longitude']) ? (float) $b['longitude'] : null,
                'geofence_radius_m' => isset($b['geofence_radius_m']) ? (int) $b['geofence_radius_m'] : null,
                'timezone'          => $b['timezone'] ?? null,
            ];
        }, $rows);

        jsonResponse(['bases' => $fmt]);
    }

    // =========================================================================
    // PRIVATE
    // =========================================================================

    /**
     * Ensure module is enabled for the tenant AND user's roles are permitted
     * by tenant settings. On failure emits a JSON error + returns false.
     */
    private function ensureModuleAndRole(int $tenantId, int $userId): bool {
        if (!AuthorizationService::isModuleEnabledForTenant('duty_reporting', $tenantId)) {
            jsonResponse(['error' => 'Duty Reporting is not enabled for this tenant'], 403);
            return false;
        }

        $settings = DutyReportingSettings::forTenant($tenantId);
        if (!$settings['enabled']) {
            jsonResponse(['error' => 'Duty Reporting is disabled in tenant settings'], 403);
            return false;
        }

        $roles = apiUserRoles() ?: UserModel::getRoleSlugs($userId);
        if (!DutyReportingSettings::userAllowed($tenantId, $roles)) {
            jsonResponse(['error' => 'Your role is not permitted to use Duty Reporting'], 403);
            return false;
        }

        return true;
    }

    private function parseBody(): array {
        $raw = file_get_contents('php://input');
        if ($raw) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) return $decoded;
        }
        return $_POST;
    }

    // ─── Formatters ───────────────────────────────────────────────────────────

    private static function clientSettings(array $s): array {
        // Only expose the subset the client actually needs.
        return [
            'geofence_required'           => $s['geofence_required'],
            'default_radius_m'            => $s['default_radius_m'],
            'allow_outstation'            => $s['allow_outstation'],
            'exception_approval_required' => $s['exception_approval_required'],
            'clock_out_reminder_minutes'  => $s['clock_out_reminder_minutes'],
            'trusted_device_required'     => $s['trusted_device_required'],
            'biometric_required'          => $s['biometric_required'],
            'allowed_roles'               => explode(',', $s['allowed_roles']),
        ];
    }

    private static function formatReport(array $r): array {
        return [
            'id'                 => (int) $r['id'],
            'state'              => $r['state'],
            'role_at_event'      => $r['role_at_event']      ?? null,
            'check_in_at_utc'    => $r['check_in_at_utc']    ?? null,
            'check_in_at_local'  => $r['check_in_at_local']  ?? null,
            'check_in_base_id'   => isset($r['check_in_base_id']) ? (int) $r['check_in_base_id'] : null,
            'check_in_method'    => $r['check_in_method']    ?? null,
            'inside_geofence'    => isset($r['inside_geofence']) && $r['inside_geofence'] !== null
                                        ? (bool) $r['inside_geofence'] : null,
            'check_out_at_utc'   => $r['check_out_at_utc']   ?? null,
            'check_out_at_local' => $r['check_out_at_local'] ?? null,
            'duration_minutes'   => isset($r['duration_minutes']) ? (int) $r['duration_minutes'] : null,
            'roster_id'          => isset($r['roster_id']) ? (int) $r['roster_id'] : null,
            'notes'              => $r['notes']              ?? null,
            'created_at'         => $r['created_at']         ?? null,
        ];
    }
}
