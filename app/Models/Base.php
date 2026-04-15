<?php
/**
 * Base Model — per-tenant base/location management
 */
class Base {

    public static function allForTenant(int $tenantId): array {
        return Database::fetchAll(
            "SELECT * FROM bases WHERE tenant_id = ? ORDER BY name ASC",
            [$tenantId]
        );
    }

    public static function find(int $id): ?array {
        return Database::fetch("SELECT * FROM bases WHERE id = ?", [$id]);
    }

    public static function create(int $tenantId, string $name, string $code): int {
        return Database::insert(
            "INSERT INTO bases (tenant_id, name, code) VALUES (?, ?, ?)",
            [$tenantId, $name, $code]
        );
    }

    public static function update(int $id, string $name, string $code): void {
        Database::execute(
            "UPDATE bases SET name = ?, code = ? WHERE id = ?",
            [$name, $code, $id]
        );
    }

    public static function delete(int $id): void {
        Database::execute("DELETE FROM bases WHERE id = ?", [$id]);
    }

    public static function countUsers(int $baseId): int {
        $row = Database::fetch(
            "SELECT COUNT(*) AS c FROM users WHERE base_id = ?",
            [$baseId]
        );
        return (int) ($row['c'] ?? 0);
    }
}
