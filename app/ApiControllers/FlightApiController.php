<?php
/**
 * FlightApiController — Phase 9 Flight Assignment + Flight Bag (mobile).
 *
 * Crew-facing, read-only. All responses are JSON with tenant isolation.
 * Routes (see config/routes.php):
 *   GET /api/flights/mine                     — flights the caller is assigned to
 *   GET /api/flights/{id}                     — single flight detail + bag files
 *   GET /api/flights/{id}/bag                 — bag files only
 *   GET /api/flights/bag/{fileId}/download    — signed-free stream of a bag file
 */
class FlightApiController {

    /** GET /api/flights/mine */
    public function mine(): void {
        $user     = apiUser();
        $tenantId = apiTenantId();
        $userId   = (int) $user['user_id'];
        $since30  = dbDatePlusDays(-30);

        $rows = Database::fetchAll(
            "SELECT f.*, a.registration AS aircraft_reg, a.aircraft_type AS aircraft_type_name,
                    (SELECT COUNT(*) FROM flight_bag_files fb WHERE fb.flight_id = f.id) AS bag_count
               FROM flights f
               LEFT JOIN aircraft a ON f.aircraft_id = a.id
              WHERE f.tenant_id = ?
                AND (f.captain_id = ? OR f.fo_id = ?)
                AND f.status IN ('published','in_flight','completed')
                AND f.flight_date >= $since30
              ORDER BY f.flight_date DESC, f.std DESC",
            [$tenantId, $userId, $userId]
        );

        jsonResponse(['flights' => array_map([self::class, 'formatFlight'], $rows)]);
    }

    /** GET /api/flights/{id} */
    public function show(int $id): void {
        $user     = apiUser();
        $tenantId = apiTenantId();
        $userId   = (int) $user['user_id'];

        $f = Database::fetch(
            "SELECT f.*, a.registration AS aircraft_reg, a.aircraft_type AS aircraft_type_name,
                    uc.name AS captain_name, ufo.name AS fo_name
               FROM flights f
               LEFT JOIN aircraft a  ON f.aircraft_id = a.id
               LEFT JOIN users uc    ON f.captain_id  = uc.id
               LEFT JOIN users ufo   ON f.fo_id       = ufo.id
              WHERE f.id = ? AND f.tenant_id = ?",
            [$id, $tenantId]
        );
        if (!$f) jsonResponse(['error' => 'Flight not found'], 404);

        $isAssigned = in_array($userId, [(int)$f['captain_id'], (int)$f['fo_id']], true);
        $roles = apiUserRoles();
        $isScheduler = (bool) array_intersect($roles, ['super_admin','airline_admin','scheduler','chief_pilot','base_manager']);
        if (!$isAssigned && !$isScheduler) jsonResponse(['error' => 'Forbidden'], 403);

        $bag = Database::fetchAll(
            "SELECT id, flight_id, file_type, title, file_name, file_size, created_at
               FROM flight_bag_files WHERE flight_id = ? ORDER BY file_type, created_at DESC",
            [$id]
        );

        $flight = self::formatFlight($f);
        $flight['captain_name']    = $f['captain_name'] ?? null;
        $flight['fo_name']         = $f['fo_name']      ?? null;
        $flight['notes']           = $f['notes']        ?? null;
        $flight['bag']             = array_map([self::class, 'formatBag'], $bag);

        jsonResponse(['flight' => $flight]);
    }

    /** GET /api/flights/{id}/bag */
    public function bag(int $id): void {
        $tenantId = apiTenantId();
        $user     = apiUser();
        $userId   = (int) $user['user_id'];

        $f = Database::fetch("SELECT captain_id, fo_id FROM flights WHERE id = ? AND tenant_id = ?", [$id, $tenantId]);
        if (!$f) jsonResponse(['error' => 'Flight not found'], 404);

        $isAssigned = in_array($userId, [(int)$f['captain_id'], (int)$f['fo_id']], true);
        $roles = apiUserRoles();
        $isScheduler = (bool) array_intersect($roles, ['super_admin','airline_admin','scheduler','chief_pilot','base_manager']);
        if (!$isAssigned && !$isScheduler) jsonResponse(['error' => 'Forbidden'], 403);

        $bag = Database::fetchAll(
            "SELECT id, flight_id, file_type, title, file_name, file_size, created_at
               FROM flight_bag_files WHERE flight_id = ? ORDER BY file_type, created_at DESC",
            [$id]
        );
        jsonResponse(['bag' => array_map([self::class, 'formatBag'], $bag)]);
    }

    /** GET /api/flights/bag/{fileId}/download — streams the file bytes. */
    public function download(int $fileId): void {
        $tenantId = apiTenantId();
        $user     = apiUser();
        $userId   = (int) $user['user_id'];

        $row = Database::fetch(
            "SELECT fb.*, f.captain_id, f.fo_id
               FROM flight_bag_files fb
               JOIN flights f ON fb.flight_id = f.id
              WHERE fb.id = ? AND fb.tenant_id = ?",
            [$fileId, $tenantId]
        );
        if (!$row) jsonResponse(['error' => 'File not found'], 404);

        $isAssigned = in_array($userId, [(int)$row['captain_id'], (int)$row['fo_id']], true);
        $roles = apiUserRoles();
        $isScheduler = (bool) array_intersect($roles, ['super_admin','airline_admin','scheduler','chief_pilot','base_manager']);
        if (!$isAssigned && !$isScheduler) jsonResponse(['error' => 'Forbidden'], 403);

        $full = storagePath($row['file_path']);
        if (!is_file($full)) jsonResponse(['error' => 'File missing on server'], 404);

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($row['file_name']) . '"');
        header('Content-Length: ' . filesize($full));
        readfile($full);
        exit;
    }

    // ─── Formatters ─────────────────────────────────────────────

    private static function formatFlight(array $r): array {
        return [
            'id'                  => (int)  $r['id'],
            'flight_number'       => (string)($r['flight_number'] ?? ''),
            'flight_date'         => (string)($r['flight_date']   ?? ''),
            'departure'           => (string)($r['departure']     ?? ''),
            'arrival'             => (string)($r['arrival']       ?? ''),
            'std'                 => $r['std'] ?? null,
            'sta'                 => $r['sta'] ?? null,
            'status'              => (string)($r['status']        ?? 'draft'),
            'aircraft_reg'        => $r['aircraft_reg']       ?? null,
            'aircraft_type'       => $r['aircraft_type_name'] ?? null,
            'captain_id'          => isset($r['captain_id']) ? (int)$r['captain_id'] : null,
            'fo_id'               => isset($r['fo_id'])      ? (int)$r['fo_id']      : null,
            'bag_count'           => (int)($r['bag_count']   ?? 0),
        ];
    }

    private static function formatBag(array $r): array {
        return [
            'id'         => (int)$r['id'],
            'flight_id'  => (int)$r['flight_id'],
            'file_type'  => (string)($r['file_type'] ?? 'other'),
            'title'      => (string)($r['title']     ?? $r['file_name'] ?? ''),
            'file_name'  => (string)($r['file_name'] ?? ''),
            'file_size'  => (int)   ($r['file_size'] ?? 0),
            'created_at' => (string)($r['created_at']?? ''),
        ];
    }
}
