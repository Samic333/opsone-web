<?php
/**
 * AuditLog Model — records privileged actions
 */
class AuditLog {
    public static function log(string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null): void {
        $user = currentUser();
        Database::insert(
            "INSERT INTO audit_logs (tenant_id, user_id, user_name, action, entity_type, entity_id, details, ip_address)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                currentTenantId(),
                $user['id'] ?? null,
                $user['name'] ?? 'System',
                $action,
                $entityType,
                $entityId,
                $details,
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]
        );
    }

    public static function apiLog(string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null): void {
        $user = apiUser();
        Database::insert(
            "INSERT INTO audit_logs (tenant_id, user_id, user_name, action, entity_type, entity_id, details, ip_address)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                apiTenantId(),
                $user['user_id'] ?? null,
                $user['name'] ?? 'API User',
                $action,
                $entityType,
                $entityId,
                $details,
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]
        );
    }

    public static function recent(int $tenantId, int $limit = 20): array {
        return Database::fetchAll(
            "SELECT * FROM audit_logs WHERE tenant_id = ? ORDER BY created_at DESC LIMIT ?",
            [$tenantId, $limit]
        );
    }

    public static function all(?int $tenantId = null, int $limit = 50): array {
        if ($tenantId) {
            return Database::fetchAll(
                "SELECT al.*, t.name as tenant_name FROM audit_logs al LEFT JOIN tenants t ON al.tenant_id = t.id WHERE al.tenant_id = ? ORDER BY al.created_at DESC LIMIT ?",
                [$tenantId, $limit]
            );
        }
        return Database::fetchAll(
            "SELECT al.*, t.name as tenant_name FROM audit_logs al LEFT JOIN tenants t ON al.tenant_id = t.id ORDER BY al.created_at DESC LIMIT ?",
            [$limit]
        );
    }

    public static function logLogin(int $userId, int $tenantId, string $email, bool $success, string $source = 'web'): void {
        Database::insert(
            "INSERT INTO login_activity (user_id, tenant_id, email, ip_address, user_agent, success, source)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $userId, $tenantId, $email,
                $_SERVER['REMOTE_ADDR'] ?? null,
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                $success ? 1 : 0,
                $source,
            ]
        );
    }
}
