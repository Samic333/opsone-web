<?php
/**
 * InstallLog Model — tracks install page access and downloads
 */
class InstallLog {
    public static function log(string $action, ?int $userId = null, ?int $tenantId = null, ?int $buildId = null): void {
        try {
            Database::insert(
                "INSERT INTO install_logs (user_id, tenant_id, action, build_id, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $userId, $tenantId, $action,
                    $buildId,
                    substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45) ?: null,
                    substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255) ?: null,
                ]
            );
        } catch (\Exception $e) {
            // Silently fail logging rather than breaking the user page
        }
    }

    public static function recent(int $limit = 50): array {
        return Database::fetchAll(
            "SELECT il.*, u.name as user_name, t.name as tenant_name, ab.version as build_version
             FROM install_logs il
             LEFT JOIN users u ON il.user_id = u.id
             LEFT JOIN tenants t ON il.tenant_id = t.id
             LEFT JOIN app_builds ab ON il.build_id = ab.id
             ORDER BY il.created_at DESC LIMIT ?",
            [$limit]
        );
    }

    public static function countByAction(string $action, ?int $days = 30): int {
        $since = dbDatePlusDays(-(int)$days);
        return (int) Database::fetch(
            "SELECT COUNT(*) as c FROM install_logs WHERE action = ? AND created_at > $since",
            [$action]
        )['c'];
    }
}
