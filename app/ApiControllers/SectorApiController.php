<?php
/**
 * SectorApiController — Phase 0 (iPad operational upgrade).
 *
 * A sector is one leg of a flight. The parent flight row is the "duty wrapper"
 * for back-compat with the rest of the platform; per-leg fields live on
 * `flight_sectors`.
 *
 * Routes (see config/routes.php):
 *   GET /api/sectors/{id}    — single sector detail (must be on parent flight)
 *   PUT /api/sectors/{id}    — partial update of crew-captured actuals
 *
 * Crew can only see / mutate sectors of flights they are assigned to (captain,
 * FO, or flight_crew_assignments). Admins / scheduler / chief pilot / base
 * manager pass via the same gate FlightApiController uses.
 */
class SectorApiController {

    /** GET /api/sectors/{id} */
    public function show(int $id): void {
        $tenantId = apiTenantId();
        $user     = apiUser();
        $userId   = (int) $user['user_id'];

        $row = self::loadOrForbid($id, $tenantId, $userId);
        jsonResponse(['sector' => FlightApiController::formatSector($row)]);
    }

    /**
     * PUT /api/sectors/{id}
     * Body (all optional): block_off_utc, takeoff_utc, landing_utc,
     * block_on_utc, fuel_uplift_kg, fuel_remaining_kg, pax_total, status, notes.
     */
    public function update(int $id): void {
        $tenantId = apiTenantId();
        $user     = apiUser();
        $userId   = (int) $user['user_id'];

        $row  = self::loadOrForbid($id, $tenantId, $userId);
        $body = json_decode(file_get_contents('php://input'), true);
        if (!is_array($body)) jsonResponse(['error' => 'Invalid JSON body'], 400);

        $allowed = [
            'block_off_utc', 'takeoff_utc', 'landing_utc', 'block_on_utc',
            'fuel_uplift_kg', 'fuel_remaining_kg', 'pax_total', 'status', 'notes',
        ];
        $statusEnum = ['planned','airborne','completed','cancelled','diverted'];

        $set = [];
        $args = [];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $body)) {
                $val = $body[$col];
                if ($col === 'status' && !in_array($val, $statusEnum, true)) {
                    jsonResponse(['error' => "Invalid status: $val"], 422);
                }
                $set[]  = "$col = ?";
                $args[] = $val;
            }
        }
        if (empty($set)) jsonResponse(['error' => 'No updatable fields supplied'], 400);

        // updated_at refresh is handled by the column default ON UPDATE on
        // MySQL; on SQLite we touch it explicitly.
        $isSqlite = env('DB_DRIVER', 'mysql') === 'sqlite';
        $set[]  = "updated_at = " . ($isSqlite ? "datetime('now')" : "CURRENT_TIMESTAMP");
        $args[] = $id;

        Database::execute(
            "UPDATE flight_sectors SET " . implode(', ', $set) . " WHERE id = ?",
            $args
        );

        $fresh = Database::fetch(
            "SELECT s.*, a.registration AS aircraft_reg
               FROM flight_sectors s
               LEFT JOIN aircraft a ON s.aircraft_id = a.id
              WHERE s.id = ?",
            [$id]
        );
        jsonResponse(['sector' => FlightApiController::formatSector($fresh)]);
    }

    // ─── Internals ──────────────────────────────────────────────

    /**
     * Load a sector and verify the caller is rostered on the parent flight.
     * On failure responds 404/403 directly and does not return.
     */
    private static function loadOrForbid(int $id, int $tenantId, int $userId): array {
        $row = Database::fetch(
            "SELECT s.*, a.registration AS aircraft_reg,
                    f.captain_id, f.fo_id, f.tenant_id AS f_tenant_id
               FROM flight_sectors s
               JOIN flights f      ON s.flight_id = f.id
               LEFT JOIN aircraft a ON s.aircraft_id = a.id
              WHERE s.id = ? AND s.tenant_id = ?",
            [$id, $tenantId]
        );
        if (!$row) jsonResponse(['error' => 'Sector not found'], 404);

        // Re-use FlightApiController's access gate by attaching the parent
        // flight context. Inline the role check to avoid making the gate
        // public on FlightApiController — keeps that controller's API tight.
        $captain = (int)($row['captain_id'] ?? 0);
        $fo      = (int)($row['fo_id']      ?? 0);
        $isCrew  = ($userId === $captain || $userId === $fo);
        if (!$isCrew) {
            $assn = Database::fetch(
                "SELECT 1 FROM flight_crew_assignments
                  WHERE flight_id = ? AND user_id = ? LIMIT 1",
                [(int)$row['flight_id'], $userId]
            );
            $isCrew = (bool) $assn;
        }
        if (!$isCrew) {
            $roles = apiUserRoles();
            $hasRole = (bool) array_intersect(
                $roles,
                ['super_admin','airline_admin','scheduler','chief_pilot','base_manager']
            );
            if (!$hasRole) jsonResponse(['error' => 'Forbidden'], 403);
        }

        return $row;
    }
}
