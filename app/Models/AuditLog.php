<?php
/**
 * AuditLog Model — legacy compatibility wrapper
 *
 * All new code should use AuditService directly.
 * This class delegates to AuditService so existing controller
 * calls continue to work without modification.
 */
class AuditLog {

    /**
     * @deprecated Use AuditService::log() directly
     */
    public static function log(string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null): void {
        AuditService::log($action, $entityType, $entityId, $details);
    }

    /**
     * @deprecated Use AuditService::logApi() directly
     */
    public static function apiLog(string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null): void {
        AuditService::logApi($action, $entityType, $entityId, $details);
    }

    public static function logLogin(?int $userId, ?int $tenantId, string $email, bool $success, string $source = 'web'): void {
        AuditService::logLogin($userId, $tenantId, $email, $success, $source);
    }

    public static function recent(int $tenantId, int $limit = 20): array {
        return AuditService::recent($tenantId, $limit);
    }

    public static function all(?int $tenantId = null, int $limit = 50): array {
        return AuditService::all($tenantId, $limit);
    }
}
