<?php
/**
 * FlightController — Phase 9 Flight Assignment + Flight Bag.
 * Scheduler creates flights and uploads bag files; assigned crew read their flights.
 */
class FlightController {

    private function requireScheduler(): void {
        RbacMiddleware::requireRole([
            'super_admin', 'airline_admin', 'scheduler', 'chief_pilot', 'base_manager'
        ]);
    }

    /**
     * Flight Folder aggregate — counts Flight Folder documents by status
     * across all 7 tables for the given flight. Returned keys:
     *   not_started, draft, submitted, accepted, rejected, returned, total_docs
     *
     * `total_docs` is always 7 (six per-flight tables + after_mission which
     * stores pilot + cabin crew in the same table but is counted as 2 roles
     * when rows exist for each).
     */
    public static function folderSummary(int $tenantId, int $flightId): array {
        $counts = [
            'not_started' => 0,
            'draft'       => 0,
            'submitted'   => 0,
            'accepted'    => 0,
            'rejected'    => 0,
            'returned'    => 0,
        ];

        // Tables storing a single row per flight.
        $single = [
            'flight_journey_logs',
            'flight_risk_assessments',
            'crew_briefing_sheets',
            'flight_navlogs',
            'post_arrival_reports',
            'flight_verification_forms',
        ];
        $presentSlots = 0;
        foreach ($single as $table) {
            $row = Database::fetch(
                "SELECT status FROM `$table` WHERE tenant_id = ? AND flight_id = ? LIMIT 1",
                [$tenantId, $flightId]
            );
            if ($row) {
                self::incrementBucket($counts, (string)$row['status']);
                $presentSlots++;
            }
        }

        // After-mission rows exist per role. Each flight has up to 2 potential
        // slots (pilot + cabin_crew) — count present rows plus their status.
        $amr = Database::fetchAll(
            "SELECT status FROM after_mission_reports WHERE tenant_id = ? AND flight_id = ?",
            [$tenantId, $flightId]
        );
        foreach ($amr as $r) {
            self::incrementBucket($counts, (string)$r['status']);
            $presentSlots++;
        }

        // Not-started slots: 6 fixed + 2 after-mission possibilities - present.
        $totalSlots = 6 + 2;
        $counts['not_started'] = max(0, $totalSlots - $presentSlots);
        $counts['total_docs']  = $totalSlots;

        return $counts;
    }

    private static function incrementBucket(array &$counts, string $status): void {
        switch ($status) {
            case 'submitted':         $counts['submitted']++;  break;
            case 'accepted':          $counts['accepted']++;   break;
            case 'rejected':          $counts['rejected']++;   break;
            case 'returned_for_info': $counts['returned']++;   break;
            case 'draft':
            default:                  $counts['draft']++;      break;
        }
    }

    // ─── Admin / scheduler views ────────────────────────────────

    public function index(): void {
        $this->requireScheduler();
        $tenantId = (int)currentTenantId();
        $from = $_GET['from'] ?? date('Y-m-d');
        $to   = $_GET['to']   ?? date('Y-m-d', strtotime('+14 days'));

        $flights = Database::fetchAll(
            "SELECT f.*, a.registration AS reg, a.aircraft_type,
                    uc.name AS captain_name, ufo.name AS fo_name,
                    (SELECT COUNT(*) FROM flight_bag_files fb WHERE fb.flight_id = f.id) AS bag_count
               FROM flights f
               LEFT JOIN aircraft a  ON f.aircraft_id = a.id
               LEFT JOIN users    uc ON f.captain_id  = uc.id
               LEFT JOIN users    ufo ON f.fo_id      = ufo.id
              WHERE f.tenant_id = ? AND f.flight_date BETWEEN ? AND ?
              ORDER BY f.flight_date, f.std, f.flight_number",
            [$tenantId, $from, $to]
        );

        $pageTitle    = 'Flights';
        $pageSubtitle = "Flight board " . $from . " — " . $to;

        $headerAction = '<a href="/flights/create" class="btn btn-primary">+ New Flight</a>';

        ob_start();
        require VIEWS_PATH . '/flights/index.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function showCreate(): void {
        $this->requireScheduler();
        $tenantId = (int)currentTenantId();
        $aircraft = Database::fetchAll(
            "SELECT id, registration, aircraft_type FROM aircraft WHERE tenant_id = ? AND status IN ('active','maintenance') ORDER BY registration",
            [$tenantId]
        );
        $pilots = Database::fetchAll(
            "SELECT u.id, u.name FROM users u
               JOIN user_roles ur ON ur.user_id = u.id
               JOIN roles r ON ur.role_id = r.id
              WHERE u.tenant_id = ? AND r.slug = 'pilot' AND u.status = 'active'
              GROUP BY u.id ORDER BY u.name",
            [$tenantId]
        );

        $pageTitle = 'New Flight';
        ob_start();
        require VIEWS_PATH . '/flights/create.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function store(): void {
        $this->requireScheduler();
        if (!verifyCsrf()) { flash('error','Invalid form.'); redirect('/flights/create'); }

        $tenantId = (int)currentTenantId();
        $date = $_POST['flight_date'] ?? ''; $num = trim($_POST['flight_number'] ?? '');
        if ($date === '' || $num === '') { flash('error','Date and flight # required.'); redirect('/flights/create'); }

        $id = Database::insert(
            "INSERT INTO flights
                (tenant_id, flight_date, flight_number, departure, arrival, std, sta,
                 aircraft_id, captain_id, fo_id, status, notes)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $tenantId, $date, $num,
                strtoupper(trim($_POST['departure'] ?? '')),
                strtoupper(trim($_POST['arrival']   ?? '')),
                $_POST['std'] ?: null, $_POST['sta'] ?: null,
                (int)($_POST['aircraft_id'] ?? 0) ?: null,
                (int)($_POST['captain_id']  ?? 0) ?: null,
                (int)($_POST['fo_id']       ?? 0) ?: null,
                $_POST['status'] ?? 'draft',
                trim($_POST['notes'] ?? ''),
            ]
        );

        AuditLog::log('flight_created', 'flight', $id, "$date $num");

        // Notify assigned crew when immediately published
        if (($_POST['status'] ?? 'draft') === 'published') {
            $this->notifyCrew($id, 'Flight assigned');
        }

        flash('success', 'Flight created.');
        redirect("/flights/$id");
    }

    public function show(int $id): void {
        requireAuth();
        $tenantId = (int)currentTenantId();
        $flight = Database::fetch(
            "SELECT f.*, a.registration AS reg, a.aircraft_type,
                    uc.name AS captain_name, ufo.name AS fo_name
               FROM flights f
               LEFT JOIN aircraft a  ON f.aircraft_id = a.id
               LEFT JOIN users    uc ON f.captain_id  = uc.id
               LEFT JOIN users    ufo ON f.fo_id      = ufo.id
              WHERE f.id = ? AND f.tenant_id = ?",
            [$id, $tenantId]
        );
        if (!$flight) { flash('error','Flight not found.'); redirect('/flights'); }

        // Crew can only see flights they're on; schedulers see everything.
        $user = currentUser();
        $isAssigned = in_array((int)$user['id'], [(int)$flight['captain_id'], (int)$flight['fo_id']], true);
        if (!$isAssigned && !hasAnyRole(['super_admin','airline_admin','scheduler','chief_pilot','base_manager'])) {
            flash('error','Not authorised for this flight.'); redirect('/dashboard');
        }

        $bag = Database::fetchAll(
            "SELECT * FROM flight_bag_files WHERE flight_id = ? ORDER BY file_type, created_at DESC",
            [$id]
        );

        $canUpload = hasAnyRole(['super_admin','airline_admin','scheduler','chief_pilot','base_manager']) || $isAssigned;

        // Flight Folder aggregate: count submissions across the 7 doc tables so
        // the show view can render a status line + "Review Flight Folder" CTA
        // without hitting each table separately from the view.
        $folderSummary = self::folderSummary($tenantId, $id);

        $pageTitle    = "Flight " . $flight['flight_number'];
        $pageSubtitle = $flight['flight_date'] . ' · ' . ($flight['departure'] ?? '???') . ' → ' . ($flight['arrival'] ?? '???');

        ob_start();
        require VIEWS_PATH . '/flights/show.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function publish(int $id): void {
        $this->requireScheduler();
        if (!verifyCsrf()) { flash('error','Invalid form.'); redirect("/flights/$id"); }
        Database::execute("UPDATE flights SET status = 'published', updated_at = CURRENT_TIMESTAMP WHERE id = ?", [$id]);
        AuditLog::log('flight_published', 'flight', $id, 'Published');
        $this->notifyCrew($id, 'Flight assigned (published)');
        flash('success','Flight published and crew notified.');
        redirect("/flights/$id");
    }

    public function uploadBagFile(int $id): void {
        requireAuth();
        if (!verifyCsrf()) { flash('error','Invalid form.'); redirect("/flights/$id"); }

        $tenantId = (int)currentTenantId();
        $flight = Database::fetch("SELECT * FROM flights WHERE id = ? AND tenant_id = ?", [$id, $tenantId]);
        if (!$flight) { flash('error','Flight not found.'); redirect('/flights'); }

        $user = currentUser();
        $isAssigned = in_array((int)$user['id'], [(int)$flight['captain_id'], (int)$flight['fo_id']], true);
        if (!$isAssigned && !hasAnyRole(['super_admin','airline_admin','scheduler','chief_pilot','base_manager'])) {
            flash('error','Not authorised to upload.'); redirect("/flights/$id");
        }

        if (empty($_FILES['file']['name']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            flash('error','Select a file.'); redirect("/flights/$id");
        }

        $f = $_FILES['file'];
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf','jpg','jpeg','png','txt','csv'], true)) {
            flash('error',"File type .$ext not allowed."); redirect("/flights/$id");
        }

        $dir = storagePath("uploads/tenant_$tenantId/flight_$id");
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $safe = sanitizeFilename(pathinfo($f['name'], PATHINFO_FILENAME));
        $unique = $safe . '_' . uniqid() . '.' . $ext;
        $rel = "uploads/tenant_$tenantId/flight_$id/$unique";
        if (!move_uploaded_file($f['tmp_name'], storagePath($rel))) {
            flash('error','Failed to save.'); redirect("/flights/$id");
        }

        Database::insert(
            "INSERT INTO flight_bag_files (flight_id, tenant_id, file_type, title, file_path, file_name, file_size, uploaded_by)
             VALUES (?,?,?,?,?,?,?,?)",
            [
                $id, $tenantId, $_POST['file_type'] ?? 'other',
                trim($_POST['title'] ?? $f['name']),
                $rel, $f['name'], $f['size'], (int)$user['id']
            ]
        );

        AuditLog::log('flight_bag_added', 'flight', $id, "Added {$_POST['file_type']}: {$f['name']}");
        flash('success','File added to flight bag.');
        redirect("/flights/$id");
    }

    public function download(int $id): void {
        requireAuth();
        $tenantId = (int)currentTenantId();
        $row = Database::fetch("SELECT * FROM flight_bag_files WHERE id = ? AND tenant_id = ?", [$id, $tenantId]);
        if (!$row) { http_response_code(404); echo "Not found"; exit; }

        $flight = Database::fetch("SELECT captain_id, fo_id FROM flights WHERE id = ?", [$row['flight_id']]);
        $user = currentUser();
        $isAssigned = $flight && in_array((int)$user['id'], [(int)$flight['captain_id'], (int)$flight['fo_id']], true);
        if (!$isAssigned && !hasAnyRole(['super_admin','airline_admin','scheduler','chief_pilot','base_manager'])) {
            http_response_code(403); echo "Forbidden"; exit;
        }

        $full = storagePath($row['file_path']);
        if (!file_exists($full)) { http_response_code(404); echo "File missing"; exit; }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $row['file_name'] . '"');
        header('Content-Length: ' . filesize($full));
        readfile($full); exit;
    }

    // ─── Crew view ──────────────────────────────────────────────

    public function myFlights(): void {
        requireAuth();
        $tenantId = (int)currentTenantId();
        $userId   = (int)currentUser()['id'];

        // dbDatePlusDays() emits driver-correct syntax (CURDATE()/DATE('now',…))
        $cutoff = dbDatePlusDays(-30);
        $flights = Database::fetchAll(
            "SELECT f.*, a.registration AS reg, a.aircraft_type
               FROM flights f
               LEFT JOIN aircraft a ON f.aircraft_id = a.id
              WHERE f.tenant_id = ? AND f.status IN ('published','in_flight','completed')
                AND (f.captain_id = ? OR f.fo_id = ?)
                AND f.flight_date >= $cutoff
              ORDER BY f.flight_date DESC, f.std DESC",
            [$tenantId, $userId, $userId]
        );

        $pageTitle    = 'My Flights';
        $pageSubtitle = 'Assigned flights and their briefing packages';

        ob_start();
        require VIEWS_PATH . '/flights/my_flights.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    // ─── Helpers ───────────────────────────────────────────────

    private function notifyCrew(int $flightId, string $title): void {
        $f = Database::fetch("SELECT * FROM flights WHERE id = ?", [$flightId]);
        if (!$f) return;
        $body = "Flight {$f['flight_number']} on {$f['flight_date']} "
              . ($f['departure'] ?? '') . " → " . ($f['arrival'] ?? '');
        foreach (array_filter([$f['captain_id'], $f['fo_id']]) as $uid) {
            NotificationService::notifyUser(
                (int)$uid, $title, $body, "/flights/$flightId",
                'flight_assigned', 'important', false
            );
        }
    }
}
