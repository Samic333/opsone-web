<?php
/**
 * LogbookApiController — Phase 7 Electronic Pilot Logbook (mobile).
 *
 * Crew-facing only. Admin cross-crew view stays on web.
 *
 * Routes:
 *   GET  /api/logbook/mine              — caller's entries + totals
 *   POST /api/logbook                    — add a new entry
 */
class LogbookApiController {

    /** GET /api/logbook/mine */
    public function mine(): void {
        $tenantId = apiTenantId();
        $userId   = (int) apiUser()['user_id'];

        $rows = Database::fetchAll(
            "SELECT fl.*, a.registration AS a_reg, a.aircraft_type AS a_type
               FROM flight_logs fl
               LEFT JOIN aircraft a ON fl.aircraft_id = a.id
              WHERE fl.user_id = ? AND fl.tenant_id = ?
              ORDER BY fl.flight_date DESC, fl.id DESC
              LIMIT 500",
            [$userId, $tenantId]
        );

        $entries = array_map(fn($r) => [
            'id'             => (int)$r['id'],
            'flight_date'    => (string)($r['flight_date']    ?? ''),
            'flight_number'  => $r['flight_number']  ?? null,
            'aircraft_type'  => $r['aircraft_type']  ?? ($r['a_type'] ?? null),
            'registration'   => $r['registration']   ?? ($r['a_reg']  ?? null),
            'departure'      => $r['departure']      ?? null,
            'arrival'        => $r['arrival']        ?? null,
            'off_blocks'     => $r['off_blocks']     ?? null,
            'takeoff'        => $r['takeoff']        ?? null,
            'landing'        => $r['landing']        ?? null,
            'on_blocks'      => $r['on_blocks']      ?? null,
            'block_minutes'  => (int)($r['block_minutes']  ?? 0),
            'air_minutes'    => (int)($r['air_minutes']    ?? 0),
            'night_minutes'  => (int)($r['night_minutes']  ?? 0),
            'ifr_minutes'    => (int)($r['ifr_minutes']    ?? 0),
            'pic_minutes'    => (int)($r['pic_minutes']    ?? 0),
            'sic_minutes'    => (int)($r['sic_minutes']    ?? 0),
            'landings_day'   => (int)($r['landings_day']   ?? 0),
            'landings_night' => (int)($r['landings_night'] ?? 0),
            'rules'          => $r['rules']   ?? 'IFR',
            'role'           => $r['role']    ?? 'PIC',
            'remarks'        => $r['remarks'] ?? null,
        ], $rows);

        $totals = [
            'flights'        => count($entries),
            'block_minutes'  => array_sum(array_column($entries, 'block_minutes')),
            'air_minutes'    => array_sum(array_column($entries, 'air_minutes')),
            'night_minutes'  => array_sum(array_column($entries, 'night_minutes')),
            'landings_day'   => array_sum(array_column($entries, 'landings_day')),
            'landings_night' => array_sum(array_column($entries, 'landings_night')),
        ];
        jsonResponse(['entries' => $entries, 'totals' => $totals]);
    }

    /** POST /api/logbook */
    public function create(): void {
        $tenantId = apiTenantId();
        $userId   = (int) apiUser()['user_id'];
        $body     = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $flightDate = trim((string)($body['flight_date'] ?? ''));
        if ($flightDate === '') jsonResponse(['error' => 'flight_date required'], 422);

        $off = $body['off_blocks'] ?? null;
        $to  = $body['takeoff']    ?? null;
        $ld  = $body['landing']    ?? null;
        $on  = $body['on_blocks']  ?? null;

        $block = self::diffMinutes($off, $on);
        $air   = self::diffMinutes($to,  $ld);

        $id = Database::insert(
            "INSERT INTO flight_logs
                (tenant_id, user_id, flight_date, aircraft_id, aircraft_type, registration, flight_number,
                 departure, arrival, off_blocks, takeoff, landing, on_blocks,
                 block_minutes, air_minutes, day_minutes, night_minutes, ifr_minutes,
                 pic_minutes, sic_minutes, landings_day, landings_night, rules, role, remarks)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $tenantId, $userId, $flightDate,
                (int)($body['aircraft_id'] ?? 0) ?: null,
                trim((string)($body['aircraft_type'] ?? '')),
                strtoupper(trim((string)($body['registration'] ?? ''))),
                trim((string)($body['flight_number'] ?? '')),
                strtoupper(trim((string)($body['departure'] ?? ''))),
                strtoupper(trim((string)($body['arrival']   ?? ''))),
                $off, $to, $ld, $on, $block, $air,
                (int)($body['day_minutes']   ?? 0) ?: null,
                (int)($body['night_minutes'] ?? 0) ?: null,
                (int)($body['ifr_minutes']   ?? 0) ?: null,
                (int)($body['pic_minutes']   ?? 0) ?: null,
                (int)($body['sic_minutes']   ?? 0) ?: null,
                (int)($body['landings_day']   ?? 0),
                (int)($body['landings_night'] ?? 0),
                (string)($body['rules'] ?? 'IFR'),
                (string)($body['role']  ?? 'PIC'),
                trim((string)($body['remarks'] ?? '')),
            ]
        );
        AuditLog::log('logbook_entry_created', 'flight_log', (int)$id,
            "$flightDate " . ($body['departure'] ?? '') . ' → ' . ($body['arrival'] ?? '') . ' (mobile)');
        jsonResponse(['success' => true, 'id' => (int)$id, 'block_minutes' => $block, 'air_minutes' => $air]);
    }

    /** Minutes between two HH:MM times (handles midnight wrap). */
    private static function diffMinutes(?string $start, ?string $end): ?int {
        if (!$start || !$end) return null;
        $s = strtotime("1970-01-01 $start");
        $e = strtotime("1970-01-01 $end");
        if ($s === false || $e === false) return null;
        if ($e < $s) $e += 86400;
        return (int) (($e - $s) / 60);
    }
}
