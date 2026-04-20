<?php
/**
 * AuditLog Model — legacy compatibility wrapper
 *
 * @deprecated All new code must use AuditService directly.
 *             This shim exists solely for backward-compat. It will be removed
 *             once all callers have been migrated (tracked via error_log below).
 *
 * Migration path:
 *   AuditLog::log(...)      → AuditService::log(...)
 *   AuditLog::apiLog(...)   → AuditService::logApi(...)
 *   AuditLog::logLogin(...) → AuditService::logLogin(...)
 *   AuditLog::recent(...)   → AuditService::recent(...)
 *   AuditLog::all(...)      → AuditService::all(...)
 */
class AuditLog {

    /**
     * @deprecated Use AuditService::log() directly.
     */
    public static function log(string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null): void {
        error_log('[DEPRECATED] AuditLog::log() called from ' . self::callerHint() . ' — migrate to AuditService::log()');
        AuditService::log($action, $entityType, $entityId, $details);
    }

    /**
     * @deprecated Use AuditService::logApi() directly.
     */
    public static function apiLog(string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null): void {
        error_log('[DEPRECATED] AuditLog::apiLog() called from ' . self::callerHint() . ' — migrate to AuditService::logApi()');
        AuditService::logApi($action, $entityType, $entityId, $details);
    }

    /**
     * @deprecated Use AuditService::logLogin() directly.
     */
    public static function logLogin(?int $userId, ?int $tenantId, string $email, bool $success, string $source = 'web'): void {
        error_log('[DEPRECATED] AuditLog::logLogin() called from ' . self::callerHint() . ' — migrate to AuditService::logLogin()');
        AuditService::logLogin($userId, $tenantId, $email, $success, $source);
    }

    /**
     * @deprecated Use AuditService::recent() directly.
     */
    public static function recent(int $tenantId, int $limit = 20): array {
        error_log('[DEPRECATED] AuditLog::recent() called from ' . self::callerHint() . ' — migrate to AuditService::recent()');
        return AuditService::recent($tenantId, $limit);
    }

    /**
     * @deprecated Use AuditService::all() directly.
     */
    public static function all(?int $tenantId = null, int $limit = 50): array {
        error_log('[DEPRECATED] AuditLog::all() called from ' . self::callerHint() . ' — migrate to AuditService::all()');
        return AuditService::all($tenantId, $limit);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Returns a concise "file:line" string for the immediate external caller so
     * the error_log entries are actionable without a full stack trace.
     */
    private static function callerHint(): string {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        // [0] = callerHint, [1] = the deprecated method itself, [2] = real caller
        $frame = $trace[2] ?? $trace[1] ?? [];
        $file  = isset($frame['file']) ? basename($frame['file']) : 'unknown';
        $line  = $frame['line'] ?? '?';
        return "{$file}:{$line}";
    }
}
