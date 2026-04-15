<?php
/**
 * Fleet Model — per-tenant fleet/aircraft-type management
 */
class Fleet {

    public static function allForTenant(int $tenantId): array {
        return Database::fetchAll(
            "SELECT * FROM fleets WHERE tenant_id = ? ORDER BY name ASC",
            [$tenantId]
        );
    }

    public static function find(int $id): ?array {
        return Database::fetch("SELECT * FROM fleets WHERE id = ?", [$id]);
    }

    public static function create(int $tenantId, string $name, ?string $code, ?string $aircraftType): int {
        return Database::insert(
            "INSERT INTO fleets (tenant_id, name, code, aircraft_type) VALUES (?, ?, ?, ?)",
            [$tenantId, $name, $code ?: null, $aircraftType ?: null]
        );
    }

    public static function update(int $id, string $name, ?string $code, ?string $aircraftType): void {
        Database::execute(
            "UPDATE fleets SET name = ?, code = ?, aircraft_type = ? WHERE id = ?",
            [$name, $code ?: null, $aircraftType ?: null, $id]
        );
    }

    public static function delete(int $id): void {
        Database::execute("DELETE FROM fleets WHERE id = ?", [$id]);
    }

    public static function countUsers(int $fleetId): int {
        $row = Database::fetch(
            "SELECT COUNT(*) AS c FROM users WHERE fleet_id = ?",
            [$fleetId]
        );
        return (int) ($row['c'] ?? 0);
    }
}
