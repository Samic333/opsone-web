<?php
/**
 * FlightFolderController — Phase 5 (V2) — web-side review of Flight Folder
 * documents submitted from the iPad/iPhone app.
 *
 * Access: station/base manager, chief pilot, super admin, plus any crew who
 * are on the flight.  Submitters view their own via /my-flights; managers see
 * every crew's submissions.
 *
 * Routes (config/routes.php):
 *   GET /flights/{id}/folder
 *   POST /flights/{id}/folder/{doc_type}/review
 */
class FlightFolderController {

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

    private const REVIEWER_ROLES = [
        'super_admin', 'airline_admin', 'chief_pilot',
        'base_manager', 'scheduler', 'safety_officer'
    ];

    /** GET /flights/{id}/folder */
    public function index(int $id): void {
        requireAuth();
        $tenantId = (int) currentTenantId();
        $flight = Database::fetch(
            "SELECT f.*, a.registration AS aircraft_reg,
                    uc.name AS captain_name, ufo.name AS fo_name
               FROM flights f
               LEFT JOIN aircraft a ON f.aircraft_id = a.id
               LEFT JOIN users uc   ON f.captain_id  = uc.id
               LEFT JOIN users ufo  ON f.fo_id       = ufo.id
              WHERE f.id = ? AND f.tenant_id = ?",
            [$id, $tenantId]
        );
        if (!$flight) { http_response_code(404); echo 'Flight not found'; return; }

        $docs = [];
        foreach (self::TABLES as $docType => $table) {
            $extra = '';
            $params = [$tenantId, $id];
            if ($table === 'after_mission_reports') {
                $extra = ' AND role_type = ? ';
                $params[] = $docType === 'after_mission_pilot' ? 'pilot' : 'cabin_crew';
            }
            $row = Database::fetch(
                "SELECT d.*, us.name AS submitter_name
                   FROM `$table` d
                   LEFT JOIN users us ON d.submitted_by_user_id = us.id
                  WHERE d.tenant_id = ? AND d.flight_id = ? $extra
                  LIMIT 1",
                $params
            );
            $docs[$docType] = $row ?: null;
        }

        $history = Database::fetchAll(
            "SELECT h.*, u.name AS changed_by_name
               FROM flight_folder_status_history h
               LEFT JOIN users u ON h.changed_by = u.id
              WHERE h.tenant_id = ? AND h.flight_id = ?
              ORDER BY h.changed_at DESC
              LIMIT 50",
            [$tenantId, $id]
        );

        // Roles live in $_SESSION['user_roles'] (see hasAnyRole helper) — not on
        // the $user array directly.  Reusing the canonical helper avoids drift.
        $canReview = hasAnyRole(self::REVIEWER_ROLES);

        $docLabels = self::labels();

        $pageTitle = "Flight Folder — {$flight['flight_number']} {$flight['departure']} → {$flight['arrival']}";
        require VIEWS_PATH . '/flights/folder/index.php';
    }

    /** @return array<string,string> User-facing labels for each doc_type. */
    private static function labels(): array {
        return [
            'journey_log'         => 'Journey Log',
            'risk_assessment'     => 'Flight Risk Assessment',
            'crew_briefing'       => 'Crew Briefing',
            'navlog'              => 'Navigation Log',
            'post_arrival'        => 'Post-Arrival Report',
            'verification'        => 'Verification Form',
            'after_mission_pilot' => 'After-Mission (Pilot)',
            'after_mission_cabin' => 'After-Mission (Cabin Crew)',
        ];
    }

    /** POST /flights/{id}/folder/{doc_type}/review */
    public function review(int $id, string $docType): void {
        requireAuth();
        if (!verifyCsrf()) { http_response_code(419); echo 'CSRF'; return; }

        RbacMiddleware::requireRole(self::REVIEWER_ROLES);

        $tenantId = (int) currentTenantId();
        if (!isset(self::TABLES[$docType])) { http_response_code(422); echo 'Bad doc_type'; return; }
        $table = self::TABLES[$docType];

        $decision = $_POST['decision'] ?? '';
        $notes    = trim((string)($_POST['notes'] ?? ''));
        $map = [
            'accept' => 'accepted',
            'reject' => 'rejected',
            'info'   => 'returned_for_info',
        ];
        if (!isset($map[$decision])) { http_response_code(422); echo 'Bad decision'; return; }
        $newStatus = $map[$decision];

        $extra = '';
        $params = [$tenantId, $id];
        if ($table === 'after_mission_reports') {
            $extra = ' AND role_type = ? ';
            $params[] = $docType === 'after_mission_pilot' ? 'pilot' : 'cabin_crew';
        }
        $existing = Database::fetch(
            "SELECT * FROM `$table` WHERE tenant_id = ? AND flight_id = ? $extra LIMIT 1",
            $params
        );
        if (!$existing) { http_response_code(404); echo 'Document not found'; return; }

        $me = currentUser();
        $reviewer = (int)($me['id'] ?? 0);
        $now = dbNow(); // driver-agnostic
        Database::query(
            "UPDATE `$table`
                SET status = ?, reviewed_by_user_id = ?, reviewed_at = $now
              WHERE id = ?",
            [$newStatus, $reviewer, (int)$existing['id']]
        );
        Database::insert(
            "INSERT INTO flight_folder_status_history
                (tenant_id, flight_id, doc_type, doc_id, old_status, new_status, changed_by, notes)
             VALUES (?,?,?,?,?,?,?,?)",
            [$tenantId, $id, $docType, (int)$existing['id'],
             $existing['status'], $newStatus, $reviewer, $notes ?: null]
        );

        AuditLog::log('flight_folder_reviewed', $docType, (int)$existing['id'],
            "flight $id → $newStatus");

        redirect("/flights/$id/folder");
    }
}
