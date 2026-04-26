<?php
/**
 * FlightFolderApiController — Phase 5 (V2).
 *
 * Seven per-flight documents (journey log, risk assessment, crew briefing,
 * navlog, post-arrival, verification, after-mission) with a uniform
 * draft → submitted → accepted/rejected/returned flow.
 *
 * Routes (registered in config/routes.php):
 *   GET  /api/flights/{id}/folder
 *   GET  /api/flights/{id}/folder/{doc_type}
 *   PUT  /api/flights/{id}/folder/{doc_type}              — save-or-update draft
 *   POST /api/flights/{id}/folder/{doc_type}/submit       — submit for review
 */
class FlightFolderApiController {

    /** Table name per doc_type. */
    private const TABLES = [
        'journey_log'         => 'flight_journey_logs',
        'risk_assessment'     => 'flight_risk_assessments',
        'crew_briefing'       => 'crew_briefing_sheets',
        'navlog'              => 'flight_navlogs',
        'post_arrival'        => 'post_arrival_reports',
        'verification'        => 'flight_verification_forms',
        'after_mission_pilot' => 'after_mission_reports',
        'after_mission_cabin' => 'after_mission_reports',
    ];

    private const LABELS = [
        'journey_log'         => 'Journey Log',
        'risk_assessment'     => 'Flight Risk Assessment',
        'crew_briefing'       => 'Crew Briefing',
        'navlog'              => 'Navigation Log',
        'post_arrival'        => 'Post-Arrival Report',
        'verification'        => 'Verification Form',
        'after_mission_pilot' => 'After-Mission (Pilot)',
        'after_mission_cabin' => 'After-Mission (Cabin)',
    ];

    /** GET /api/flights/{id}/folder — returns all 7 docs' current state. */
    public function index(int $flightId): void {
        $tenantId = apiTenantId();
        $flight = $this->requireFlight($tenantId, $flightId);

        $out = [
            'flight'    => [
                'id'            => (int)$flight['id'],
                'flight_number' => $flight['flight_number'] ?? '',
                'departure'     => $flight['departure']     ?? '',
                'arrival'       => $flight['arrival']       ?? '',
                'flight_date'   => $flight['flight_date']   ?? '',
                'status'        => $flight['status']        ?? '',
            ],
            'documents' => [],
        ];
        foreach (self::TABLES as $docType => $table) {
            $row = $this->loadDoc($tenantId, $flightId, $docType, $table);
            $out['documents'][$docType] = [
                'doc_type'     => $docType,
                'status'       => $row['status']       ?? 'not_started',
                'submitted_at' => $row['submitted_at'] ?? null,
                'reviewed_at'  => $row['reviewed_at']  ?? null,
                'updated_at'   => $row['updated_at']   ?? null,
                'has_draft'    => $row ? true : false,
            ];
        }
        jsonResponse($out);
    }

    /** GET /api/flights/{id}/folder/{doc_type} */
    public function show(int $flightId, string $docType): void {
        $tenantId = apiTenantId();
        $table = $this->requireDocType($docType);
        $this->requireFlight($tenantId, $flightId);

        $row = $this->loadDoc($tenantId, $flightId, $docType, $table);
        if (!$row) {
            jsonResponse(['doc' => null, 'doc_type' => $docType]);
        }
        $row['payload'] = $this->decodeJson($row['payload'] ?? null);
        jsonResponse(['doc' => $row, 'doc_type' => $docType]);
    }

    /** PUT /api/flights/{id}/folder/{doc_type} */
    public function save(int $flightId, string $docType): void {
        $tenantId = apiTenantId();
        $table = $this->requireDocType($docType);
        $this->requireFlight($tenantId, $flightId);
        $userId = (int) apiUser()['user_id'];

        $body        = json_decode(file_get_contents('php://input'), true) ?? [];
        $payloadJson = json_encode($body['payload'] ?? $body);
        $existing    = $this->loadDoc($tenantId, $flightId, $docType, $table);

        if ($existing) {
            Database::query(
                "UPDATE `$table`
                    SET payload = ?,
                        submitted_by_user_id = ?
                  WHERE id = ?",
                [$payloadJson, $userId, (int)$existing['id']]
            );
            $id = (int)$existing['id'];
        } else {
            $cols = ['tenant_id', 'flight_id', 'submitted_by_user_id', 'status', 'payload'];
            $vals = [$tenantId, $flightId, $userId, 'draft', $payloadJson];
            if ($table === 'after_mission_reports') {
                $cols[] = 'role_type';
                $vals[] = $docType === 'after_mission_pilot' ? 'pilot' : 'cabin_crew';
            }
            $placeholders = implode(',', array_fill(0, count($cols), '?'));
            $id = Database::insert(
                "INSERT INTO `$table` (" . implode(',', $cols) . ") VALUES ($placeholders)",
                $vals
            );
        }

        AuditLog::log('flight_folder_draft_saved', $docType, (int)$id, "flight $flightId");
        jsonResponse(['success' => true, 'id' => (int)$id, 'status' => 'draft']);
    }

    /** POST /api/flights/{id}/folder/{doc_type}/submit */
    public function submit(int $flightId, string $docType): void {
        $tenantId = apiTenantId();
        $table = $this->requireDocType($docType);
        $this->requireFlight($tenantId, $flightId);
        $userId = (int) apiUser()['user_id'];

        $existing = $this->loadDoc($tenantId, $flightId, $docType, $table);
        if (!$existing) {
            jsonResponse(['error' => 'No draft to submit — save draft first'], 422);
        }
        $now = dbNow(); // driver-agnostic — datetime('now') on SQLite, NOW() on MySQL
        Database::query(
            "UPDATE `$table`
                SET status = 'submitted',
                    submitted_at = $now,
                    submitted_by_user_id = ?
              WHERE id = ?",
            [$userId, (int)$existing['id']]
        );
        Database::insert(
            "INSERT INTO flight_folder_status_history
                (tenant_id, flight_id, doc_type, doc_id, old_status, new_status, changed_by)
             VALUES (?,?,?,?,?,?,?)",
            [$tenantId, $flightId, $docType, (int)$existing['id'],
             $existing['status'], 'submitted', $userId]
        );
        AuditLog::log('flight_folder_submitted', $docType, (int)$existing['id'], "flight $flightId");

        // Notify the assigned base manager(s) so the submission doesn't sit in
        // limbo.  Best-effort: a notification failure does not fail the submit.
        try {
            $this->notifyReviewers($tenantId, $flightId, $docType, $userId);
        } catch (\Throwable $e) {
            error_log('[FlightFolderApiController] notify error: ' . $e->getMessage());
        }

        jsonResponse(['success' => true, 'id' => (int)$existing['id'], 'status' => 'submitted']);
    }

    /**
     * Notify base managers (fallback: chief pilots) for the flight's departure
     * base that a folder document has been submitted and is awaiting review.
     */
    private function notifyReviewers(int $tenantId, int $flightId, string $docType, int $submitterId): void {
        // Pull flight context for the notification body.
        $flight = Database::fetch(
            "SELECT f.flight_number, f.departure, f.arrival, f.flight_date,
                    us.name AS submitter_name
               FROM flights f
               LEFT JOIN users us ON us.id = ?
              WHERE f.id = ? AND f.tenant_id = ?",
            [$submitterId, $flightId, $tenantId]
        );
        if (!$flight) return;

        // Resolve base_id from the flight's departure ICAO when possible.
        $baseId = null;
        $base = Database::fetch(
            "SELECT id FROM bases WHERE tenant_id = ? AND (code = ? OR name = ?) LIMIT 1",
            [$tenantId, $flight['departure'], $flight['departure']]
        );
        if ($base) $baseId = (int)$base['id'];

        // Primary target: base_manager(s) for that base.
        $targets = [];
        if ($baseId !== null) {
            $rows = Database::fetchAll(
                "SELECT DISTINCT u.id
                   FROM users u
                   JOIN user_roles ur ON ur.user_id = u.id
                   JOIN roles r       ON r.id       = ur.role_id
                  WHERE u.tenant_id = ?
                    AND u.status = 'active'
                    AND u.base_id = ?
                    AND r.slug = 'base_manager'",
                [$tenantId, $baseId]
            );
            foreach ($rows as $r) $targets[] = (int)$r['id'];
        }

        // Fallback: chief pilots + airline admins for the tenant if no base
        // manager was matched.  Avoids a dead-letter notification.
        if (empty($targets)) {
            $rows = Database::fetchAll(
                "SELECT DISTINCT u.id
                   FROM users u
                   JOIN user_roles ur ON ur.user_id = u.id
                   JOIN roles r       ON r.id       = ur.role_id
                  WHERE u.tenant_id = ?
                    AND u.status = 'active'
                    AND r.slug IN ('chief_pilot','airline_admin')",
                [$tenantId]
            );
            foreach ($rows as $r) $targets[] = (int)$r['id'];
        }
        if (empty($targets)) return;

        $docLabel = self::LABELS[$docType] ?? $docType;
        $title    = 'Flight folder submission';
        $body     = ($flight['submitter_name'] ?? 'A crew member')
                  . " submitted $docLabel for flight "
                  . ($flight['flight_number'] ?? '')
                  . ' (' . ($flight['departure'] ?? '') . ' → ' . ($flight['arrival'] ?? '') . ')';
        $link = "/flights/$flightId/folder";

        foreach ($targets as $uid) {
            if ($uid === $submitterId) continue; // don't notify self
            NotificationService::notifyUser(
                $uid, $title, $body, $link,
                'flight_folder_submitted', 'important', false
            );
        }
    }

    // ---------------------------------------------------------------- helpers

    private function requireDocType(string $docType): string {
        if (!isset(self::TABLES[$docType])) {
            jsonResponse(['error' => "Unknown doc_type '$docType'"], 422);
        }
        return self::TABLES[$docType];
    }

    private function requireFlight(int $tenantId, int $flightId): array {
        $flight = Database::fetch(
            "SELECT * FROM flights WHERE id = ? AND tenant_id = ?",
            [$flightId, $tenantId]
        );
        if (!$flight) jsonResponse(['error' => 'Flight not found'], 404);
        return $flight;
    }

    private function loadDoc(int $tenantId, int $flightId, string $docType, string $table): ?array {
        $extra = '';
        $params = [$tenantId, $flightId];
        if ($table === 'after_mission_reports') {
            $extra = " AND role_type = ? ";
            $params[] = $docType === 'after_mission_pilot' ? 'pilot' : 'cabin_crew';
        }
        return Database::fetch(
            "SELECT * FROM `$table`
              WHERE tenant_id = ? AND flight_id = ? $extra
              LIMIT 1",
            $params
        ) ?: null;
    }

    private function decodeJson($raw) {
        if (!is_string($raw)) return $raw ?? null;
        $v = json_decode($raw, true);
        return $v ?? [];
    }
}
