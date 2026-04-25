<?php
/**
 * FdmApiController — Phase 10 FDM Refinement (pilot-facing mobile).
 *
 * Only /my-fdm is exposed to the iPad — analyst tools stay on web.
 *
 * Routes:
 *   GET  /api/fdm/mine              — pilot's FDM event inbox
 *   POST /api/fdm/event/{id}/ack    — acknowledge event
 */
class FdmApiController {

    /** GET /api/fdm/mine */
    public function mine(): void {
        $tenantId = apiTenantId();
        $userId   = (int) apiUser()['user_id'];

        $rows = Database::fetchAll(
            "SELECT e.*, u.original_name AS upload_name
               FROM fdm_events e
               LEFT JOIN fdm_uploads u ON e.fdm_upload_id = u.id
              WHERE e.tenant_id = ? AND e.pilot_user_id = ?
              ORDER BY e.flight_date DESC, e.id DESC",
            [$tenantId, $userId]
        );

        $events = array_map(fn($r) => [
            'id'             => (int)$r['id'],
            'event_type'     => (string)($r['event_type']    ?? 'other'),
            'severity'       => (string)($r['severity']      ?? 'medium'),
            'flight_date'    => $r['flight_date']            ?? null,
            'aircraft_reg'   => $r['aircraft_reg']           ?? null,
            'flight_number'  => $r['flight_number']          ?? null,
            'flight_phase'   => $r['flight_phase']           ?? null,
            'parameter'      => $r['parameter']              ?? null,
            'value_recorded' => $r['value_recorded']         ?? null,
            'threshold'      => $r['threshold']              ?? null,
            'notes'          => $r['notes']                  ?? null,
            'upload_name'    => $r['upload_name']            ?? null,
            'pilot_ack_at'   => $r['pilot_ack_at']           ?? null,
        ], $rows);

        jsonResponse(['events' => $events]);
    }

    /** POST /api/fdm/event/{id}/ack */
    public function ack(int $id): void {
        $tenantId = apiTenantId();
        $userId   = (int) apiUser()['user_id'];

        $row = Database::fetch(
            "SELECT id FROM fdm_events WHERE id = ? AND tenant_id = ? AND pilot_user_id = ?",
            [$id, $tenantId, $userId]
        );
        if (!$row) jsonResponse(['error' => 'Event not found'], 404);

        Database::execute(
            "UPDATE fdm_events
                SET pilot_ack_at = CURRENT_TIMESTAMP
              WHERE id = ? AND pilot_user_id = ? AND pilot_ack_at IS NULL",
            [$id, $userId]
        );
        AuditLog::log('fdm_event_acked', 'fdm_event', $id, 'Pilot acknowledged via API');
        jsonResponse(['success' => true]);
    }

    /**
     * POST /api/fdm/event/{id}/comment
     *
     * Body: { "comment": string }
     *
     * Records the pilot's explanation/response.  Implicitly also acknowledges
     * the event if not already acked.  Notifies the FDM analyst team for the
     * tenant so they can review the response.
     */
    public function comment(int $id): void {
        $tenantId = apiTenantId();
        $userId   = (int) apiUser()['user_id'];

        $body    = json_decode(file_get_contents('php://input'), true) ?? [];
        $comment = trim((string)($body['comment'] ?? ''));
        if ($comment === '') {
            jsonResponse(['error' => 'comment is required'], 422);
        }

        $row = Database::fetch(
            "SELECT id, event_type, severity FROM fdm_events
              WHERE id = ? AND tenant_id = ? AND pilot_user_id = ?",
            [$id, $tenantId, $userId]
        );
        if (!$row) jsonResponse(['error' => 'Event not found'], 404);

        Database::execute(
            "UPDATE fdm_events
                SET pilot_comment = ?,
                    pilot_comment_at = CURRENT_TIMESTAMP,
                    pilot_ack_at = COALESCE(pilot_ack_at, CURRENT_TIMESTAMP)
              WHERE id = ?",
            [$comment, $id]
        );

        AuditLog::log('fdm_event_commented', 'fdm_event', $id, 'Pilot comment via API');

        // Notify FDM analysts / safety officers so they see the response.
        try {
            $analysts = Database::fetchAll(
                "SELECT DISTINCT u.id FROM users u
                   JOIN user_roles ur ON ur.user_id = u.id
                   JOIN roles r       ON r.id       = ur.role_id
                  WHERE u.tenant_id = ?
                    AND u.status = 'active'
                    AND r.slug IN ('fdm_analyst','safety_officer','airline_admin')",
                [$tenantId]
            );
            foreach ($analysts as $a) {
                NotificationService::notifyUser(
                    (int)$a['id'],
                    'FDM event response',
                    'Pilot response to event #' . $id . ' (' . $row['event_type'] . ' / ' . $row['severity'] . ')',
                    "/fdm/event/$id",
                    'fdm_pilot_comment',
                    'normal',
                    false
                );
            }
        } catch (\Throwable $e) {
            error_log('[FdmApiController] notify error: ' . $e->getMessage());
        }

        jsonResponse(['success' => true]);
    }
}
