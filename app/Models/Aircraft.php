<?php
/**
 * Aircraft model — registry, documents, and maintenance due.
 * Phase 6 (V2).
 */
class Aircraft {

    public static function allForTenant(int $tenantId, ?string $status = null): array {
        $sql = "SELECT a.*, f.name AS fleet_name, b.name AS base_name,
                       (SELECT COUNT(*) FROM aircraft_maintenance m
                         WHERE m.aircraft_id = a.id AND m.status = 'active'
                           AND m.due_date IS NOT NULL AND m.due_date < DATE('now')) AS overdue_items,
                       (SELECT COUNT(*) FROM aircraft_maintenance m
                         WHERE m.aircraft_id = a.id AND m.status = 'active'
                           AND m.due_date IS NOT NULL
                           AND m.due_date BETWEEN DATE('now') AND DATE('now','+30 days')) AS due_30d,
                       (SELECT COUNT(*) FROM aircraft_documents d
                         WHERE d.aircraft_id = a.id
                           AND d.expiry_date IS NOT NULL AND d.expiry_date < DATE('now')) AS expired_docs
                  FROM aircraft a
                  LEFT JOIN fleets f ON a.fleet_id = f.id
                  LEFT JOIN bases  b ON a.home_base_id = b.id
                 WHERE a.tenant_id = ?";
        $params = [$tenantId];
        if ($status) { $sql .= " AND a.status = ?"; $params[] = $status; }
        $sql .= " ORDER BY a.registration ASC";
        return Database::fetchAll($sql, $params);
    }

    public static function find(int $id): ?array {
        return Database::fetch(
            "SELECT a.*, f.name AS fleet_name, b.name AS base_name
               FROM aircraft a
               LEFT JOIN fleets f ON a.fleet_id = f.id
               LEFT JOIN bases  b ON a.home_base_id = b.id
              WHERE a.id = ?",
            [$id]
        );
    }

    public static function create(array $d): int {
        return Database::insert(
            "INSERT INTO aircraft
              (tenant_id, fleet_id, registration, aircraft_type, variant, manufacturer,
               msn, year_built, home_base_id, status, total_hours, total_cycles, notes)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $d['tenant_id'], $d['fleet_id'] ?: null, $d['registration'],
                $d['aircraft_type'], $d['variant'] ?? null, $d['manufacturer'] ?? null,
                $d['msn'] ?? null, $d['year_built'] ?: null, $d['home_base_id'] ?: null,
                $d['status'] ?? 'active', $d['total_hours'] ?? 0, $d['total_cycles'] ?? 0,
                $d['notes'] ?? null,
            ]
        );
    }

    public static function update(int $id, array $d): void {
        Database::execute(
            "UPDATE aircraft SET
                fleet_id = ?, registration = ?, aircraft_type = ?, variant = ?,
                manufacturer = ?, msn = ?, year_built = ?, home_base_id = ?,
                status = ?, total_hours = ?, total_cycles = ?, notes = ?,
                updated_at = CURRENT_TIMESTAMP
              WHERE id = ?",
            [
                $d['fleet_id'] ?: null, $d['registration'], $d['aircraft_type'],
                $d['variant'] ?? null, $d['manufacturer'] ?? null, $d['msn'] ?? null,
                $d['year_built'] ?: null, $d['home_base_id'] ?: null,
                $d['status'] ?? 'active', $d['total_hours'] ?? 0,
                $d['total_cycles'] ?? 0, $d['notes'] ?? null, $id,
            ]
        );
    }

    public static function delete(int $id): void {
        Database::execute("DELETE FROM aircraft WHERE id = ?", [$id]);
    }

    // ─── Maintenance ─────────────────────────────────────────────

    public static function maintenanceFor(int $aircraftId): array {
        // COALESCE keeps nullable due_date at the end on both MySQL and SQLite.
        return Database::fetchAll(
            "SELECT * FROM aircraft_maintenance WHERE aircraft_id = ?
              ORDER BY COALESCE(due_date, '9999-12-31') ASC",
            [$aircraftId]
        );
    }

    public static function addMaintenance(array $d): int {
        return Database::insert(
            "INSERT INTO aircraft_maintenance
                (aircraft_id, tenant_id, item_type, description, due_date, due_hours,
                 due_cycles, last_done_date, last_done_hours, interval_days, interval_hours, status, notes)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $d['aircraft_id'], $d['tenant_id'], $d['item_type'],
                $d['description'] ?? null, $d['due_date'] ?: null,
                $d['due_hours'] ?: null, $d['due_cycles'] ?: null,
                $d['last_done_date'] ?: null, $d['last_done_hours'] ?: null,
                $d['interval_days'] ?: null, $d['interval_hours'] ?: null,
                $d['status'] ?? 'active', $d['notes'] ?? null,
            ]
        );
    }

    public static function completeMaintenance(int $id, ?string $doneDate, ?float $doneHours): void {
        Database::execute(
            "UPDATE aircraft_maintenance
                SET status = 'completed', last_done_date = ?, last_done_hours = ?,
                    updated_at = CURRENT_TIMESTAMP
              WHERE id = ?",
            [$doneDate ?: date('Y-m-d'), $doneHours, $id]
        );
    }

    // ─── Documents ──────────────────────────────────────────────

    public static function documentsFor(int $aircraftId): array {
        return Database::fetchAll(
            "SELECT * FROM aircraft_documents WHERE aircraft_id = ? ORDER BY expiry_date ASC",
            [$aircraftId]
        );
    }

    public static function addDocument(array $d): int {
        return Database::insert(
            "INSERT INTO aircraft_documents
                (aircraft_id, tenant_id, doc_type, doc_number, issued_date, expiry_date,
                 file_path, notes, uploaded_by)
             VALUES (?,?,?,?,?,?,?,?,?)",
            [
                $d['aircraft_id'], $d['tenant_id'], $d['doc_type'],
                $d['doc_number'] ?? null, $d['issued_date'] ?: null,
                $d['expiry_date'] ?: null, $d['file_path'] ?? null,
                $d['notes'] ?? null, $d['uploaded_by'] ?? null,
            ]
        );
    }

    // ─── Compliance summary ─────────────────────────────────────

    public static function complianceSummary(int $tenantId): array {
        $active = (int)(Database::fetch(
            "SELECT COUNT(*) c FROM aircraft WHERE tenant_id = ? AND status IN ('active','maintenance')",
            [$tenantId]
        )['c'] ?? 0);
        $aog = (int)(Database::fetch(
            "SELECT COUNT(*) c FROM aircraft WHERE tenant_id = ? AND status = 'aog'",
            [$tenantId]
        )['c'] ?? 0);
        $overdueMx = (int)(Database::fetch(
            "SELECT COUNT(*) c FROM aircraft_maintenance m
               JOIN aircraft a ON m.aircraft_id = a.id
              WHERE a.tenant_id = ? AND m.status = 'active'
                AND m.due_date IS NOT NULL AND m.due_date < DATE('now')",
            [$tenantId]
        )['c'] ?? 0);
        $dueMx30 = (int)(Database::fetch(
            "SELECT COUNT(*) c FROM aircraft_maintenance m
               JOIN aircraft a ON m.aircraft_id = a.id
              WHERE a.tenant_id = ? AND m.status = 'active'
                AND m.due_date BETWEEN DATE('now') AND DATE('now','+30 days')",
            [$tenantId]
        )['c'] ?? 0);
        $expiredDocs = (int)(Database::fetch(
            "SELECT COUNT(*) c FROM aircraft_documents d
               JOIN aircraft a ON d.aircraft_id = a.id
              WHERE a.tenant_id = ? AND d.expiry_date IS NOT NULL AND d.expiry_date < DATE('now')",
            [$tenantId]
        )['c'] ?? 0);
        return compact('active','aog','overdueMx','dueMx30','expiredDocs');
    }
}
