<?php
/**
 * LogbookController — Phase 7 Electronic Pilot Logbook.
 * Crew-facing self-service; admins get an unfiltered view across a tenant.
 */
class LogbookController {

    public function myLogbook(): void {
        requireAuth();
        $user     = currentUser();
        $userId   = (int)$user['id'];
        $tenantId = (int)currentTenantId();

        $logs = Database::fetchAll(
            "SELECT fl.*, a.registration AS a_reg, a.aircraft_type AS a_type
               FROM flight_logs fl
               LEFT JOIN aircraft a ON fl.aircraft_id = a.id
              WHERE fl.user_id = ? AND fl.tenant_id = ?
              ORDER BY fl.flight_date DESC, fl.id DESC",
            [$userId, $tenantId]
        );

        $totals = [
            'flights'        => count($logs),
            'block_minutes'  => array_sum(array_map(fn($l) => (int)($l['block_minutes'] ?? 0), $logs)),
            'air_minutes'    => array_sum(array_map(fn($l) => (int)($l['air_minutes']   ?? 0), $logs)),
            'night_minutes'  => array_sum(array_map(fn($l) => (int)($l['night_minutes'] ?? 0), $logs)),
            'landings_day'   => array_sum(array_map(fn($l) => (int)($l['landings_day']   ?? 0), $logs)),
            'landings_night' => array_sum(array_map(fn($l) => (int)($l['landings_night'] ?? 0), $logs)),
        ];

        $pageTitle    = 'My Logbook';
        $pageSubtitle = 'Electronic pilot logbook';

        ob_start();
        require VIEWS_PATH . '/logbook/my_logbook.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function showCreate(): void {
        requireAuth();
        $tenantId = (int)currentTenantId();
        $aircraft = Database::fetchAll(
            "SELECT id, registration, aircraft_type FROM aircraft
              WHERE tenant_id = ? AND status IN ('active','maintenance')
              ORDER BY registration",
            [$tenantId]
        );

        $pageTitle    = 'New Logbook Entry';
        $pageSubtitle = 'Record a flight';

        ob_start();
        require VIEWS_PATH . '/logbook/create.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function store(): void {
        requireAuth();
        if (!verifyCsrf()) { flash('error','Invalid form.'); redirect('/my-logbook/new'); }

        $user     = currentUser();
        $userId   = (int)$user['id'];
        $tenantId = (int)currentTenantId();

        $flightDate = $_POST['flight_date'] ?? '';
        if ($flightDate === '') { flash('error','Date required.'); redirect('/my-logbook/new'); }

        $off = $_POST['off_blocks'] ?? null;
        $to  = $_POST['takeoff']    ?? null;
        $ld  = $_POST['landing']    ?? null;
        $on  = $_POST['on_blocks']  ?? null;

        $block = self::diffMinutes($off, $on);
        $air   = self::diffMinutes($to,  $ld);

        Database::insert(
            "INSERT INTO flight_logs
                (tenant_id, user_id, flight_date, aircraft_id, aircraft_type, registration, flight_number,
                 departure, arrival, off_blocks, takeoff, landing, on_blocks,
                 block_minutes, air_minutes, day_minutes, night_minutes, ifr_minutes,
                 pic_minutes, sic_minutes, landings_day, landings_night, rules, role, remarks)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $tenantId, $userId, $flightDate,
                (int)($_POST['aircraft_id'] ?? 0) ?: null,
                trim($_POST['aircraft_type'] ?? ''),
                strtoupper(trim($_POST['registration'] ?? '')),
                trim($_POST['flight_number'] ?? ''),
                strtoupper(trim($_POST['departure'] ?? '')),
                strtoupper(trim($_POST['arrival']   ?? '')),
                $off, $to, $ld, $on,
                $block, $air,
                (int)($_POST['day_minutes']   ?? 0) ?: null,
                (int)($_POST['night_minutes'] ?? 0) ?: null,
                (int)($_POST['ifr_minutes']   ?? 0) ?: null,
                (int)($_POST['pic_minutes']   ?? 0) ?: null,
                (int)($_POST['sic_minutes']   ?? 0) ?: null,
                (int)($_POST['landings_day']   ?? 0),
                (int)($_POST['landings_night'] ?? 0),
                $_POST['rules'] ?? 'IFR',
                $_POST['role']  ?? 'PIC',
                trim($_POST['remarks'] ?? ''),
            ]
        );

        AuditLog::log('logbook_entry_created', 'flight_log', 0,
            "$flightDate {$_POST['departure']} → {$_POST['arrival']}");
        flash('success','Logbook entry saved.');
        redirect('/my-logbook');
    }

    // ─── Admin / chief pilot cross-crew view ────────────────────

    public function adminIndex(): void {
        requireAuth();
        RbacMiddleware::requireRole(['super_admin','airline_admin','chief_pilot','hr','training_admin']);

        $tenantId = (int)currentTenantId();
        $from = $_GET['from'] ?? date('Y-m-d', strtotime('-90 days'));
        $to   = $_GET['to']   ?? date('Y-m-d');

        $rows = Database::fetchAll(
            "SELECT u.id AS user_id, u.name, u.employee_id,
                    COUNT(fl.id) AS flights,
                    COALESCE(SUM(fl.block_minutes),0) AS block_min,
                    COALESCE(SUM(fl.air_minutes),0)   AS air_min,
                    COALESCE(SUM(fl.night_minutes),0) AS night_min,
                    COALESCE(SUM(fl.landings_day),0)   AS ldg_day,
                    COALESCE(SUM(fl.landings_night),0) AS ldg_night
               FROM users u
               LEFT JOIN flight_logs fl ON fl.user_id = u.id
                                       AND fl.flight_date BETWEEN ? AND ?
                                       AND fl.tenant_id = ?
              WHERE u.tenant_id = ? AND u.status = 'active'
              GROUP BY u.id, u.name, u.employee_id
             HAVING flights > 0
              ORDER BY block_min DESC",
            [$from, $to, $tenantId, $tenantId]
        );

        $pageTitle    = 'Logbook — all crew';
        $pageSubtitle = "Per-pilot totals $from → $to";

        ob_start();
        require VIEWS_PATH . '/logbook/admin_index.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function exportCsv(): void {
        requireAuth();
        $user     = currentUser();
        $userId   = (int)$user['id'];
        $tenantId = (int)currentTenantId();

        $rows = Database::fetchAll(
            "SELECT * FROM flight_logs WHERE user_id = ? AND tenant_id = ?
              ORDER BY flight_date DESC, id DESC",
            [$userId, $tenantId]
        );

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="logbook_'.date('Y-m-d').'.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'date','flight_no','aircraft_type','registration','dep','arr',
            'off','to','land','on','block_min','air_min',
            'day','night','ifr','pic','sic','ldg_day','ldg_night','rules','role','remarks'
        ]);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['flight_date'], $r['flight_number'], $r['aircraft_type'], $r['registration'],
                $r['departure'], $r['arrival'],
                $r['off_blocks'], $r['takeoff'], $r['landing'], $r['on_blocks'],
                $r['block_minutes'], $r['air_minutes'],
                $r['day_minutes'], $r['night_minutes'], $r['ifr_minutes'],
                $r['pic_minutes'], $r['sic_minutes'],
                $r['landings_day'], $r['landings_night'], $r['rules'], $r['role'], $r['remarks'],
            ]);
        }
        fclose($out);
        exit;
    }

    /** Minutes between two HH:MM times (handles wrap-over midnight). */
    private static function diffMinutes(?string $start, ?string $end): ?int {
        if (!$start || !$end) return null;
        $s = strtotime("1970-01-01 $start");
        $e = strtotime("1970-01-01 $end");
        if ($s === false || $e === false) return null;
        if ($e < $s) $e += 86400; // wrap midnight
        return (int) (($e - $s) / 60);
    }
}
