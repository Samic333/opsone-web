<?php
/**
 * TenantModule Model — per-tenant module enablement
 */
class TenantModule {

    public static function enable(int $tenantId, int $moduleId, ?int $enabledBy = null, ?string $notes = null): void {
        $existing = Database::fetch(
            "SELECT id FROM tenant_modules WHERE tenant_id = ? AND module_id = ?",
            [$tenantId, $moduleId]
        );

        if ($existing) {
            Database::execute(
                "UPDATE tenant_modules SET is_enabled = 1, notes = ? WHERE id = ?",
                [$notes, $existing['id']]
            );
        } else {
            Database::insert(
                "INSERT INTO tenant_modules (tenant_id, module_id, is_enabled, enabled_by, notes) VALUES (?, ?, 1, ?, ?)",
                [$tenantId, $moduleId, $enabledBy, $notes]
            );
        }
    }

    public static function disable(int $tenantId, int $moduleId): void {
        Database::execute(
            "UPDATE tenant_modules SET is_enabled = 0 WHERE tenant_id = ? AND module_id = ?",
            [$tenantId, $moduleId]
        );
    }

    public static function toggle(int $tenantId, int $moduleId): bool {
        $current = Database::fetch(
            "SELECT is_enabled FROM tenant_modules WHERE tenant_id = ? AND module_id = ?",
            [$tenantId, $moduleId]
        );

        if (!$current) {
            // Enable for first time
            Database::insert(
                "INSERT INTO tenant_modules (tenant_id, module_id, is_enabled) VALUES (?, ?, 1)",
                [$tenantId, $moduleId]
            );
            return true;
        }

        $newState = $current['is_enabled'] ? 0 : 1;
        Database::execute(
            "UPDATE tenant_modules SET is_enabled = ? WHERE tenant_id = ? AND module_id = ?",
            [$newState, $tenantId, $moduleId]
        );
        return (bool)$newState;
    }

    public static function bulkEnable(int $tenantId, array $moduleCodes, ?int $enabledBy = null): void {
        if (empty($moduleCodes)) return;
        $modules = Database::fetchAll(
            "SELECT id, code FROM modules WHERE code IN (" .
            implode(',', array_fill(0, count($moduleCodes), '?')) . ")",
            $moduleCodes
        );
        foreach ($modules as $mod) {
            self::enable($tenantId, $mod['id'], $enabledBy);
        }
    }

    public static function isEnabled(int $tenantId, string $moduleCode): bool {
        $row = Database::fetch(
            "SELECT tm.is_enabled FROM tenant_modules tm
             JOIN modules m ON m.id = tm.module_id
             WHERE tm.tenant_id = ? AND m.code = ?",
            [$tenantId, $moduleCode]
        );
        return $row ? (bool)$row['is_enabled'] : false;
    }
}
