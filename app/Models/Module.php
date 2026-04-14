<?php
/**
 * Module Model — module catalog management
 */
class Module {

    public static function all(bool $visibleOnly = false): array {
        $where = $visibleOnly ? "WHERE m.visibility = 'visible'" : '';
        return Database::fetchAll(
            "SELECT m.*, COUNT(mc.id) as capability_count
             FROM modules m
             LEFT JOIN module_capabilities mc ON mc.module_id = m.id
             $where
             GROUP BY m.id
             ORDER BY m.sort_order ASC, m.name ASC"
        );
    }

    public static function find(int $id): ?array {
        return Database::fetch("SELECT * FROM modules WHERE id = ?", [$id]);
    }

    public static function findByCode(string $code): ?array {
        return Database::fetch("SELECT * FROM modules WHERE code = ?", [$code]);
    }

    public static function getCapabilities(int $moduleId): array {
        return Database::fetchAll(
            "SELECT * FROM module_capabilities WHERE module_id = ? ORDER BY capability ASC",
            [$moduleId]
        );
    }

    public static function allWithTenantStatus(int $tenantId): array {
        return Database::fetchAll(
            "SELECT m.*, mc_count.cnt as capability_count,
                    COALESCE(tm.is_enabled, 0) as tenant_enabled,
                    tm.id as tenant_module_id
             FROM modules m
             LEFT JOIN (
                 SELECT module_id, COUNT(*) as cnt FROM module_capabilities GROUP BY module_id
             ) mc_count ON mc_count.module_id = m.id
             LEFT JOIN tenant_modules tm ON tm.module_id = m.id AND tm.tenant_id = ?
             ORDER BY m.sort_order ASC, m.name ASC",
            [$tenantId]
        );
    }

    public static function enabledForTenant(int $tenantId): array {
        return Database::fetchAll(
            "SELECT m.*
             FROM modules m
             JOIN tenant_modules tm ON tm.module_id = m.id
             WHERE tm.tenant_id = ? AND tm.is_enabled = 1
             ORDER BY m.sort_order ASC, m.name ASC",
            [$tenantId]
        );
    }

    public static function countEnabledForTenant(int $tenantId): int {
        $row = Database::fetch(
            "SELECT COUNT(*) as c FROM tenant_modules WHERE tenant_id = ? AND is_enabled = 1",
            [$tenantId]
        );
        return (int)($row['c'] ?? 0);
    }
}
