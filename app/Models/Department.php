<?php
/**
 * Department Model — per-tenant department management
 */
class Department {

    public static function allForTenant(int $tenantId): array {
        return Database::fetchAll(
            "SELECT * FROM departments WHERE tenant_id = ? ORDER BY name ASC",
            [$tenantId]
        );
    }

    public static function find(int $id): ?array {
        return Database::fetch("SELECT * FROM departments WHERE id = ?", [$id]);
    }

    public static function create(int $tenantId, string $name, ?string $code): int {
        return Database::insert(
            "INSERT INTO departments (tenant_id, name, code) VALUES (?, ?, ?)",
            [$tenantId, $name, $code ?: null]
        );
    }

    public static function update(int $id, string $name, ?string $code): void {
        Database::execute(
            "UPDATE departments SET name = ?, code = ? WHERE id = ?",
            [$name, $code ?: null, $id]
        );
    }

    public static function delete(int $id): void {
        Database::execute("DELETE FROM departments WHERE id = ?", [$id]);
    }

    public static function countUsers(int $departmentId): int {
        $row = Database::fetch(
            "SELECT COUNT(*) AS c FROM users WHERE department_id = ?",
            [$departmentId]
        );
        return (int) ($row['c'] ?? 0);
    }
}
