<?php
/**
 * AuditService — centralised audit logging
 *
 * All sensitive actions in the system should go through this service.
 * It captures actor, role, tenant, IP, user-agent, result, and reason.
 *
 * Usage (web context):
 *   AuditService::log('user.created', 'user', $userId, ['name' => $name]);
 *
 * Usage (API context):
 *   AuditService::logApi('device.approved', 'device', $deviceId);
 */
class AuditService {

    // ─── Core log entry ────────────────────────────────────────────────────────

    /**
     * Write an audit record from a web (session) context.
     */
    public static function log(
        string  $action,
        ?string $entityType = null,
        ?int    $entityId   = null,
        mixed   $details    = null,
        string  $result     = 'success',
        ?string $reason     = null
    ): void {
        $user     = currentUser();
        $roles    = $_SESSION['user_roles'] ?? [];
        $tenantId = currentTenantId() ?? ($user['tenant_id'] ?? null);

        self::write([
            'tenant_id'   => $tenantId,
            'user_id'     => $user['id'] ?? null,
            'user_name'   => $user['name'] ?? 'System',
            'actor_role'  => $roles[0] ?? null,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'details'     => self::encodeDetails($details),
            'result'      => $result,
            'reason'      => $reason,
            'ip_address'  => self::clientIp(),
            'user_agent'  => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        ]);
    }

    /**
     * Write an audit record from an API (bearer token) context.
     */
    public static function logApi(
        string  $action,
        ?string $entityType = null,
        ?int    $entityId   = null,
        mixed   $details    = null,
        string  $result     = 'success',
        ?string $reason     = null
    ): void {
        $user     = apiUser();
        $roles    = apiUserRoles();
        $tenantId = apiTenantId();

        self::write([
            'tenant_id'   => $tenantId,
            'user_id'     => $user['user_id'] ?? null,
            'user_name'   => $user['name'] ?? 'API User',
            'actor_role'  => $roles[0] ?? null,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'details'     => self::encodeDetails($details),
            'result'      => $result,
            'reason'      => $reason,
            'ip_address'  => self::clientIp(),
            'user_agent'  => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        ]);
    }

    /**
     * Log a login attempt (success or failure).
     */
    public static function logLogin(
        ?int   $userId,
        ?int   $tenantId,
        string $email,
        bool   $success,
        string $source = 'web',
        ?string $reason = null
    ): void {
        // login_activity table (lightweight, always kept)
        try {
            Database::insert(
                "INSERT INTO login_activity (user_id, tenant_id, email, ip_address, user_agent, success, source)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $userId, $tenantId, $email,
                    self::clientIp(),
                    substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                    $success ? 1 : 0,
                    $source,
                ]
            );
        } catch (\Throwable) {}

        // Full audit record
        self::write([
            'tenant_id'   => $tenantId,
            'user_id'     => $userId,
            'user_name'   => $email,
            'actor_role'  => null,
            'action'      => $success ? 'auth.login.success' : 'auth.login.failure',
            'entity_type' => 'user',
            'entity_id'   => $userId,
            'details'     => "Email: $email | Source: $source",
            'result'      => $success ? 'success' : 'failure',
            'reason'      => $reason,
            'ip_address'  => self::clientIp(),
            'user_agent'  => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        ]);
    }

    /**
     * Log a platform admin accessing a tenant area.
     */
    public static function logPlatformAccess(
        int    $tenantId,
        string $moduleArea,
        string $reason,
        ?string $ticketRef = null
    ): int {
        $user = currentUser();
        $logId = Database::insert(
            "INSERT INTO platform_access_log
                (platform_user_id, tenant_id, module_area, reason, ticket_ref, ip_address, user_agent, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'active')",
            [
                $user['id'] ?? 0,
                $tenantId,
                $moduleArea,
                $reason,
                $ticketRef,
                self::clientIp(),
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            ]
        );

        // Also write to main audit log
        self::log(
            'platform.tenant_access',
            'tenant',
            $tenantId,
            "Area: $moduleArea | Ticket: " . ($ticketRef ?? 'none') . " | Reason: $reason"
        );

        return $logId;
    }

    // ─── Convenience event helpers ─────────────────────────────────────────────

    public static function logLogout(?int $userId, ?int $tenantId): void {
        self::write([
            'tenant_id'   => $tenantId,
            'user_id'     => $userId,
            'user_name'   => currentUser()['name'] ?? 'Unknown',
            'actor_role'  => ($_SESSION['user_roles'] ?? [])[0] ?? null,
            'action'      => 'auth.logout',
            'entity_type' => 'user',
            'entity_id'   => $userId,
            'details'     => null,
            'result'      => 'success',
            'reason'      => null,
            'ip_address'  => self::clientIp(),
            'user_agent'  => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        ]);
    }

    public static function logRoleChange(int $targetUserId, string $targetName, array $newRoles): void {
        self::log(
            'user.role_changed',
            'user',
            $targetUserId,
            "User: $targetName | New roles: " . implode(', ', $newRoles)
        );
    }

    public static function logModuleToggle(int $tenantId, string $tenantName, string $moduleCode, bool $enabled): void {
        self::log(
            $enabled ? 'module.enabled' : 'module.disabled',
            'tenant',
            $tenantId,
            "Tenant: $tenantName | Module: $moduleCode | Status: " . ($enabled ? 'enabled' : 'disabled')
        );
    }

    public static function logTenantCreated(int $tenantId, string $tenantName): void {
        self::log('tenant.created', 'tenant', $tenantId, "Created: $tenantName");
    }

    public static function logTenantUpdated(int $tenantId, string $tenantName): void {
        self::log('tenant.updated', 'tenant', $tenantId, "Updated: $tenantName");
    }

    public static function logTenantStatusChange(int $tenantId, string $tenantName, string $newStatus): void {
        self::log(
            'tenant.status_changed',
            'tenant',
            $tenantId,
            "Tenant: $tenantName | New status: $newStatus"
        );
    }

    public static function logUserCreated(int $userId, string $name, int $tenantId): void {
        self::log('user.created', 'user', $userId, "Created user: $name in tenant #$tenantId");
    }

    public static function logUserUpdated(int $userId, string $name): void {
        self::log('user.updated', 'user', $userId, "Updated: $name");
    }

    public static function logUserStatusChange(int $userId, string $name, string $newStatus): void {
        self::log('user.status_changed', 'user', $userId, "User: $name | Status: $newStatus");
    }

    public static function logPasswordChange(int $userId, string $name): void {
        self::log('user.password_changed', 'user', $userId, "Password changed for: $name");
    }

    public static function logDeviceApproval(int $deviceId, string $action, int $tenantId): void {
        self::log("device.$action", 'device', $deviceId, "Device $deviceId | Action: $action | Tenant: $tenantId");
    }

    public static function logCapabilityChange(int $userId, string $name, string $capability, bool $granted): void {
        self::log(
            $granted ? 'capability.granted' : 'capability.revoked',
            'user',
            $userId,
            "User: $name | Capability: $capability"
        );
    }

    public static function logUnauthorizedAccess(string $uri, array $requiredRoles): void {
        $user   = currentUser();
        $roles  = $_SESSION['user_roles'] ?? [];
        self::log(
            'auth.access_denied',
            'security',
            null,
            "URI: $uri | Required: " . implode(', ', $requiredRoles) . " | Has: " . implode(', ', $roles),
            'blocked'
        );
    }

    // ─── Query helpers ─────────────────────────────────────────────────────────

    public static function recent(?int $tenantId, int $limit = 20): array {
        if ($tenantId) {
            return Database::fetchAll(
                "SELECT * FROM audit_logs WHERE tenant_id = ? ORDER BY created_at DESC LIMIT ?",
                [$tenantId, $limit]
            );
        }
        return Database::fetchAll(
            "SELECT al.*, t.name as tenant_name
             FROM audit_logs al
             LEFT JOIN tenants t ON al.tenant_id = t.id
             ORDER BY al.created_at DESC LIMIT ?",
            [$limit]
        );
    }

    public static function all(?int $tenantId = null, int $limit = 50, int $offset = 0): array {
        if ($tenantId) {
            return Database::fetchAll(
                "SELECT al.*, t.name as tenant_name
                 FROM audit_logs al
                 LEFT JOIN tenants t ON al.tenant_id = t.id
                 WHERE al.tenant_id = ?
                 ORDER BY al.created_at DESC LIMIT ? OFFSET ?",
                [$tenantId, $limit, $offset]
            );
        }
        return Database::fetchAll(
            "SELECT al.*, t.name as tenant_name
             FROM audit_logs al
             LEFT JOIN tenants t ON al.tenant_id = t.id
             ORDER BY al.created_at DESC LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }

    public static function platformAccessLogs(int $limit = 50): array {
        return Database::fetchAll(
            "SELECT pal.*, u.name as platform_user_name, t.name as tenant_name
             FROM platform_access_log pal
             LEFT JOIN users u ON u.id = pal.platform_user_id
             LEFT JOIN tenants t ON t.id = pal.tenant_id
             ORDER BY pal.access_started_at DESC LIMIT ?",
            [$limit]
        );
    }

    // ─── Internal ─────────────────────────────────────────────────────────────

    private static function write(array $row): void {
        try {
            Database::insert(
                "INSERT INTO audit_logs
                    (tenant_id, user_id, user_name, actor_role, action,
                     entity_type, entity_id, details, result, reason, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $row['tenant_id'],
                    $row['user_id'],
                    $row['user_name'],
                    $row['actor_role'],
                    $row['action'],
                    $row['entity_type'],
                    $row['entity_id'],
                    $row['details'],
                    $row['result'],
                    $row['reason'],
                    $row['ip_address'],
                    $row['user_agent'],
                ]
            );
        } catch (\Throwable $e) {
            // Never let audit failures break the application
            appLog("AuditService write failed: " . $e->getMessage(), 'error');
        }
    }

    private static function encodeDetails(mixed $details): ?string {
        if ($details === null) return null;
        if (is_string($details)) return $details;
        return json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private static function clientIp(): ?string {
        foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'] as $k) {
            if (!empty($_SERVER[$k])) {
                $ip = trim(explode(',', $_SERVER[$k])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
            }
        }
        return null;
    }
}
