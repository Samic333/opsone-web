<?php
/**
 * PlatformAccessLog Model — audited controlled platform→tenant access
 */
class PlatformAccessLog {

    public static function all(int $limit = 50): array {
        return Database::fetchAll(
            "SELECT pal.*, u.name as platform_user_name, t.name as tenant_name
             FROM platform_access_log pal
             LEFT JOIN users u ON u.id = pal.platform_user_id
             LEFT JOIN tenants t ON t.id = pal.tenant_id
             ORDER BY pal.access_started_at DESC LIMIT ?",
            [$limit]
        );
    }

    public static function forTenant(int $tenantId, int $limit = 20): array {
        return Database::fetchAll(
            "SELECT pal.*, u.name as platform_user_name
             FROM platform_access_log pal
             LEFT JOIN users u ON u.id = pal.platform_user_id
             WHERE pal.tenant_id = ?
             ORDER BY pal.access_started_at DESC LIMIT ?",
            [$tenantId, $limit]
        );
    }

    public static function endAccess(int $logId): void {
        Database::execute(
            "UPDATE platform_access_log SET access_ended_at = NOW(), status = 'ended' WHERE id = ?",
            [$logId]
        );
    }

    public static function activeSessions(): array {
        return Database::fetchAll(
            "SELECT pal.*, u.name as platform_user_name, t.name as tenant_name
             FROM platform_access_log pal
             LEFT JOIN users u ON u.id = pal.platform_user_id
             LEFT JOIN tenants t ON t.id = pal.tenant_id
             WHERE pal.status = 'active'"
        );
    }
}
