<?php
/**
 * DutyReportingService — business logic for the Duty Reporting module.
 *
 * Responsibilities:
 *   • evaluate a check-in request against tenant settings + geofence
 *   • decide whether to enter the exception flow
 *   • orchestrate DutyReport + DutyException writes
 *   • compute derived values (distance to base, duty duration)
 *
 * Audit + notification dispatch stays in the controller (see
 * SafetyApiController for the established pattern). This service returns
 * structured result arrays; it does NOT call AuditService or
 * NotificationService itself.
 */
class DutyReportingService {

    // ─── Geofence evaluation ──────────────────────────────────────────────────

    /**
     * Haversine distance in metres between two lat/lng points.
     * Accepts null/empty inputs and returns null in that case.
     */
    public static function haversineMetres(
        ?float $lat1, ?float $lng1,
        ?float $lat2, ?float $lng2
    ): ?float {
        if ($lat1 === null || $lng1 === null || $lat2 === null || $lng2 === null) {
            return null;
        }
        $earth = 6_371_000.0;
        $φ1 = deg2rad($lat1);
        $φ2 = deg2rad($lat2);
        $dφ = deg2rad($lat2 - $lat1);
        $dλ = deg2rad($lng2 - $lng1);
        $a  = sin($dφ / 2) ** 2 + cos($φ1) * cos($φ2) * sin($dλ / 2) ** 2;
        $c  = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earth * $c;
    }

    /**
     * Attempt to match (lat,lng) to a configured base for this tenant.
     * Returns the matching base row + distance, or null if no match.
     * "Match" means the point is within the base's geofence_radius_m.
     */
    public static function resolveBase(int $tenantId, ?float $lat, ?float $lng): ?array {
        if ($lat === null || $lng === null) return null;

        $bases = Database::fetchAll(
            "SELECT * FROM bases
              WHERE tenant_id = ?
                AND latitude  IS NOT NULL
                AND longitude IS NOT NULL",
            [$tenantId]
        );
        if (!$bases) return null;

        $best = null;
        $bestDist = PHP_FLOAT_MAX;
        foreach ($bases as $b) {
            $d = self::haversineMetres(
                $lat, $lng,
                (float) $b['latitude'], (float) $b['longitude']
            );
            if ($d === null) continue;
            $radius = (int) ($b['geofence_radius_m'] ?? 0);
            if ($radius > 0 && $d <= $radius && $d < $bestDist) {
                $best = array_merge($b, ['distance_m' => (int) round($d)]);
                $bestDist = $d;
            }
        }
        return $best;
    }

    // ─── Duty state evaluation ────────────────────────────────────────────────

    /**
     * Process a check-in request.
     *
     * Input:
     *   tenant_id, user_id, role_slug, user_role_slugs (array),
     *   lat, lng, local_time (string|null), method, trusted_device_id, device_uuid, notes
     *
     * Result:
     *   [
     *     'ok'              => bool,
     *     'duty_report_id'  => int|null,
     *     'state'           => string,
     *     'inside_geofence' => bool|null,
     *     'matched_base'    => ?array,
     *     'exception'       => null | ['reason_code' => ..., 'requires_note' => bool, 'id' => ?int],
     *     'error'           => ?string,
     *   ]
     *
     * The caller (controller) is responsible for:
     *   • validating the bearer token + tenant
     *   • checking the user's roles against tenant allowed_roles
     *   • writing audit + notifications
     *   • returning the JSON response
     */
    public static function performCheckIn(array $in): array {
        $tenantId = (int) $in['tenant_id'];
        $userId   = (int) $in['user_id'];

        // Duplicate check-in guard
        $existing = DutyReport::findOpenForUser($tenantId, $userId);
        if ($existing) {
            return [
                'ok'              => false,
                'error'           => 'already_on_duty',
                'duty_report_id'  => (int) $existing['id'],
                'state'           => $existing['state'],
                'inside_geofence' => null,
                'matched_base'    => null,
                'exception'       => null,
            ];
        }

        $settings = DutyReportingSettings::forTenant($tenantId);
        if (!$settings['enabled']) {
            return [
                'ok'              => false,
                'error'           => 'module_disabled',
                'duty_report_id'  => null,
                'state'           => null,
                'inside_geofence' => null,
                'matched_base'    => null,
                'exception'       => null,
            ];
        }

        // Geofence evaluation
        $lat = isset($in['lat']) ? (float) $in['lat'] : null;
        $lng = isset($in['lng']) ? (float) $in['lng'] : null;
        $inside = null;
        $matchedBase = self::resolveBase($tenantId, $lat, $lng);
        if ($lat !== null && $lng !== null) {
            $inside = $matchedBase !== null;
        }

        // Decide state + optional exception
        $state     = DutyReport::STATE_CHECKED_IN;
        $exception = null;

        $geofenceRequired = $settings['geofence_required'];
        $providedReasonCode = $in['exception_reason_code'] ?? null;
        $providedReasonText = trim((string) ($in['exception_reason_text'] ?? ''));

        // GPS unavailable — only relevant when client explicitly flags it
        if (!empty($in['gps_unavailable'])) {
            $exception = ['reason_code' => 'gps_unavailable', 'requires_note' => true];
        }
        // Offline / queued submission
        elseif (!empty($in['offline_queue'])) {
            $exception = ['reason_code' => 'offline', 'requires_note' => false];
        }
        // Outside geofence when tenant policy requires geofence match
        elseif ($geofenceRequired && $inside === false) {
            $exception = ['reason_code' => 'outside_geofence', 'requires_note' => true];
        }
        // Outstation — no base match, lat/lng provided, tenant allows outstation
        elseif ($matchedBase === null && $lat !== null && $lng !== null && $settings['allow_outstation']) {
            $exception = ['reason_code' => 'outstation', 'requires_note' => true];
        }
        // Caller explicitly requested an exception path (e.g. manual reason)
        elseif ($providedReasonCode) {
            $exception = ['reason_code' => $providedReasonCode, 'requires_note' => true];
        }

        if ($exception) {
            if ($exception['requires_note'] && $providedReasonText === '') {
                return [
                    'ok'              => false,
                    'error'           => 'exception_note_required',
                    'duty_report_id'  => null,
                    'state'           => null,
                    'inside_geofence' => $inside,
                    'matched_base'    => $matchedBase,
                    'exception'       => $exception,
                ];
            }
            $state = $settings['exception_approval_required']
                   ? DutyReport::STATE_EXCEPTION_PENDING
                   : DutyReport::STATE_CHECKED_IN;
        }

        // Insert the duty report
        $method = $in['method'] ?? DutyReport::METHOD_DEVICE;
        if (!in_array($method, [
            DutyReport::METHOD_DEVICE, DutyReport::METHOD_BIOMETRIC,
            DutyReport::METHOD_MANUAL, DutyReport::METHOD_OFFLINE_QUEUE,
        ], true)) {
            $method = DutyReport::METHOD_DEVICE;
        }

        $id = DutyReport::createCheckIn([
            'tenant_id'          => $tenantId,
            'user_id'            => $userId,
            'role_at_event'      => $in['role_slug']         ?? null,
            'state'              => $state,
            'check_in_at_utc'    => gmdate('Y-m-d H:i:s'),
            'check_in_at_local'  => $in['local_time']        ?? null,
            'check_in_lat'       => $lat,
            'check_in_lng'       => $lng,
            'check_in_base_id'   => $matchedBase['id']       ?? null,
            'check_in_method'    => $method,
            'inside_geofence'    => $inside === null ? null : (int) $inside,
            'trusted_device_id'  => $in['trusted_device_id'] ?? null,
            'roster_id'          => self::findRosterAssignment($tenantId, $userId),
            'device_uuid'        => $in['device_uuid']       ?? null,
            'notes'              => $in['notes']             ?? null,
        ]);

        // If we entered the exception path, record the exception
        $exceptionId = null;
        if ($exception) {
            $exceptionId = DutyException::create(
                $tenantId,
                $id,
                $userId,
                $exception['reason_code'],
                $providedReasonText !== '' ? $providedReasonText : null
            );
            $exception['id'] = $exceptionId;
        }

        return [
            'ok'              => true,
            'duty_report_id'  => $id,
            'state'           => $state,
            'inside_geofence' => $inside,
            'matched_base'    => $matchedBase,
            'exception'       => $exception,
        ];
    }

    /**
     * Process a clock-out for the user's open duty record.
     */
    public static function performClockOut(array $in): array {
        $tenantId = (int) $in['tenant_id'];
        $userId   = (int) $in['user_id'];

        $open = DutyReport::findOpenForUser($tenantId, $userId);
        if (!$open) {
            return [
                'ok'    => false,
                'error' => 'no_active_duty',
                'duty_report_id' => null,
            ];
        }

        $lat = isset($in['lat']) ? (float) $in['lat'] : null;
        $lng = isset($in['lng']) ? (float) $in['lng'] : null;

        // If the open record is still in exception_pending_review, clock-out
        // does NOT override that state — the manager review is still required.
        $newState = in_array($open['state'], [
            DutyReport::STATE_EXCEPTION_PENDING,
            DutyReport::STATE_EXCEPTION_APPROVED,
            DutyReport::STATE_EXCEPTION_REJECTED,
        ], true)
            ? $open['state']
            : DutyReport::STATE_CHECKED_OUT;

        DutyReport::recordCheckOut($tenantId, (int) $open['id'], [
            'check_out_at_utc'   => gmdate('Y-m-d H:i:s'),
            'check_out_at_local' => $in['local_time'] ?? null,
            'check_out_lat'      => $lat,
            'check_out_lng'      => $lng,
            'state'              => $newState,
            'notes'              => $in['notes'] ?? null,
        ]);

        $record = DutyReport::find($tenantId, (int) $open['id']);
        return [
            'ok'                 => true,
            'duty_report_id'     => (int) $open['id'],
            'state'              => $record['state']            ?? $newState,
            'duration_minutes'   => (int) ($record['duration_minutes'] ?? 0),
            'check_out_at_utc'   => $record['check_out_at_utc'] ?? null,
        ];
    }

    // ─── Roster linkage (best-effort) ────────────────────────────────────────

    /**
     * Find a roster_id for today's roster assignment if one exists for the
     * user. Schema of `rosters` is tenant-scoped; returns null on error or
     * if no row matches.
     */
    public static function findRosterAssignment(int $tenantId, int $userId): ?int {
        try {
            $row = Database::fetch(
                "SELECT id FROM rosters
                  WHERE tenant_id = ? AND user_id = ? AND roster_date = ?
                  LIMIT 1",
                [$tenantId, $userId, date('Y-m-d')]
            );
            return $row ? (int) $row['id'] : null;
        } catch (\Throwable) {
            return null;
        }
    }

    // ─── Exception review ────────────────────────────────────────────────────

    /**
     * Apply an exception review decision to a duty_report.
     * Returns the updated duty_report row.
     */
    public static function applyExceptionReview(
        int $tenantId,
        int $exceptionId,
        int $reviewerId,
        string $decision,          // 'approved'|'rejected'
        ?string $notes = null
    ): ?array {
        $ex = DutyException::find($tenantId, $exceptionId);
        if (!$ex) return null;

        DutyException::review($tenantId, $exceptionId, $reviewerId, $decision, $notes);

        $reportId = (int) $ex['duty_report_id'];
        $newState = $decision === DutyException::STATUS_APPROVED
            ? DutyReport::STATE_EXCEPTION_APPROVED
            : DutyReport::STATE_EXCEPTION_REJECTED;

        DutyReport::setState($tenantId, $reportId, $newState);

        return DutyReport::find($tenantId, $reportId);
    }

    // ─── Overdue detection helper ────────────────────────────────────────────

    /**
     * Mark records as missed_report when check-in is older than the reminder
     * window + 6h grace. Intended to be called from a scheduled task.
     * Returns the number of rows updated.
     */
    public static function markOverdue(int $tenantId): int {
        $settings = DutyReportingSettings::forTenant($tenantId);
        $threshold = $settings['clock_out_reminder_minutes'] + 360;
        try {
            $overdue = DutyReport::overdueClockOuts($tenantId, $threshold);
            foreach ($overdue as $r) {
                DutyReport::setState($tenantId, (int) $r['id'], DutyReport::STATE_MISSED_REPORT);
            }
            return count($overdue);
        } catch (\Throwable $e) {
            error_log('[DutyReportingService] markOverdue error: ' . $e->getMessage());
            return 0;
        }
    }
}
