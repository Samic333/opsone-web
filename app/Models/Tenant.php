<?php
/**
 * Tenant Model — CRUD operations for airline tenants
 */
class Tenant {
    public static function all(): array {
        return Database::fetchAll("SELECT * FROM tenants ORDER BY name ASC");
    }

    public static function find(int $id): ?array {
        return Database::fetch("SELECT * FROM tenants WHERE id = ?", [$id]);
    }

    public static function create(array $data): int {
        return Database::insert(
            "INSERT INTO tenants (name, code, contact_email, is_active) VALUES (?, ?, ?, ?)",
            [$data['name'], $data['code'], $data['contact_email'] ?? null, $data['is_active'] ?? 1]
        );
    }

    public static function update(int $id, array $data): void {
        Database::execute(
            "UPDATE tenants SET name = ?, code = ?, contact_email = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$data['name'], $data['code'], $data['contact_email'] ?? null, $id]
        );
    }

    public static function toggleActive(int $id): void {
        Database::execute("UPDATE tenants SET is_active = NOT is_active, updated_at = CURRENT_TIMESTAMP WHERE id = ?", [$id]);
    }

    public static function countActive(): int {
        return (int) Database::fetch("SELECT COUNT(*) as c FROM tenants WHERE is_active = 1")['c'];
    }

    public static function countAll(): int {
        return (int) Database::fetch("SELECT COUNT(*) as c FROM tenants")['c'];
    }

    public static function stats(int $tenantId): array {
        $users = Database::fetch("SELECT COUNT(*) as c FROM users WHERE tenant_id = ?", [$tenantId]);
        $devices = Database::fetch("SELECT COUNT(*) as c FROM devices WHERE tenant_id = ? AND approval_status = 'pending'", [$tenantId]);
        return [
            'user_count' => (int) $users['c'],
            'pending_devices' => (int) $devices['c'],
        ];
    }
}
