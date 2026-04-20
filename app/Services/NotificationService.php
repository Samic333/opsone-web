<?php
/**
 * NotificationService — hook-based notification dispatcher
 *
 * Supported channels: 'in_app', 'push', 'email'
 *
 * Database dependency (in_app channel):
 *   Table `notifications` is created by migration
 *   019_phase0_safety_reports_mysql.sql Section B.
 *
 *   Schema reference:
 *     CREATE TABLE notifications (
 *         id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 *         tenant_id   INT UNSIGNED NOT NULL,
 *         user_id     INT UNSIGNED NOT NULL,
 *         title       VARCHAR(255) NOT NULL,
 *         body        TEXT NOT NULL,
 *         link        VARCHAR(500) DEFAULT NULL,
 *         is_read     TINYINT(1) NOT NULL DEFAULT 0,
 *         read_at     TIMESTAMP NULL DEFAULT NULL,
 *         created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 *         INDEX idx_notif_tenant_user (tenant_id, user_id),
 *         INDEX idx_notif_unread      (user_id, is_read),
 *         FK tenant_id → tenants(id) ON DELETE CASCADE,
 *         FK user_id   → users(id)   ON DELETE CASCADE
 *     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 */
class NotificationService {

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Dispatch a notification on one or more channels.
     *
     * @param string $channel  'in_app' | 'push' | 'email'
     * @param string $event    Arbitrary event name, e.g. 'safety_report_submitted'
     * @param array  $context  Must contain at minimum:
     *                           'tenant_id' int
     *                           'user_id'   int
     *                           'title'     string
     *                           'body'      string
     *                         Optional:
     *                           'link'      string
     */
    public static function dispatch(string $channel, string $event, array $context): void {
        switch ($channel) {
            case 'in_app':
                self::dispatchInApp($event, $context);
                break;

            case 'push':
                self::dispatchPush($event, $context);
                break;

            case 'email':
                self::dispatchEmail($event, $context);
                break;

            default:
                error_log("[NotificationService] Unknown channel '{$channel}' for event '{$event}'");
        }
    }

    /**
     * Send a notification to a single user across all appropriate channels.
     *
     * @param int    $userId
     * @param string $title
     * @param string $body
     * @param string $link   Optional deep-link URL or relative path
     */
    public static function notifyUser(int $userId, string $title, string $body, string $link = ''): void {
        $tenantId = self::resolveTenantId($userId);
        if ($tenantId === null) {
            error_log("[NotificationService] notifyUser(): could not resolve tenant for user_id={$userId}");
            return;
        }

        $context = [
            'tenant_id' => $tenantId,
            'user_id'   => $userId,
            'title'     => $title,
            'body'      => $body,
            'link'      => $link,
        ];

        self::dispatch('in_app', 'notify_user', $context);
        // Extend here: self::dispatch('push',  'notify_user', $context);
        // Extend here: self::dispatch('email', 'notify_user', $context);
    }

    /**
     * Broadcast a notification to all users of a tenant who hold a given role.
     *
     * @param int    $tenantId
     * @param string $role      Role slug, e.g. 'airline_admin', 'crew'
     * @param string $title
     * @param string $body
     * @param string $link
     */
    public static function notifyTenant(int $tenantId, string $role, string $title, string $body, string $link = ''): void {
        $userIds = self::getUsersForRole($tenantId, $role);

        if (empty($userIds)) {
            error_log("[NotificationService] notifyTenant(): no users found for tenant_id={$tenantId} role={$role}");
            return;
        }

        foreach ($userIds as $userId) {
            $context = [
                'tenant_id' => $tenantId,
                'user_id'   => (int) $userId,
                'title'     => $title,
                'body'      => $body,
                'link'      => $link,
            ];
            self::dispatch('in_app', 'notify_tenant_role', $context);
        }
    }

    // -------------------------------------------------------------------------
    // Channel handlers
    // -------------------------------------------------------------------------

    /**
     * Insert a row into the `notifications` table (in-app inbox).
     */
    private static function dispatchInApp(string $event, array $context): void {
        $tenantId = (int) ($context['tenant_id'] ?? 0);
        $userId   = (int) ($context['user_id']   ?? 0);
        $title    = $context['title'] ?? '';
        $body     = $context['body']  ?? '';
        $link     = $context['link']  ?? null;

        if ($tenantId === 0 || $userId === 0 || $title === '') {
            error_log("[NotificationService] dispatchInApp(): missing required context for event '{$event}'");
            return;
        }

        try {
            $db   = self::db();
            $stmt = $db->prepare(
                'INSERT INTO notifications (tenant_id, user_id, title, body, link)
                 VALUES (:tenant_id, :user_id, :title, :body, :link)'
            );
            $stmt->execute([
                ':tenant_id' => $tenantId,
                ':user_id'   => $userId,
                ':title'     => $title,
                ':body'      => $body,
                ':link'      => ($link !== '' ? $link : null),
            ]);
        } catch (\Throwable $e) {
            error_log("[NotificationService] dispatchInApp() DB error for event '{$event}': " . $e->getMessage());
        }
    }

    /**
     * Push notification stub.
     *
     * @todo Integrate APNS (Apple Push Notification Service) for iOS devices.
     */
    private static function dispatchPush(string $event, array $context): void {
        // TODO: integrate APNS / SES
        $userId = $context['user_id'] ?? 'unknown';
        error_log("[NotificationService][STUB] push event='{$event}' user_id={$userId} title=" . ($context['title'] ?? ''));
    }

    /**
     * Email notification stub.
     *
     * @todo Integrate AWS SES (Simple Email Service) or similar SMTP provider.
     */
    private static function dispatchEmail(string $event, array $context): void {
        // TODO: integrate APNS / SES
        $userId = $context['user_id'] ?? 'unknown';
        error_log("[NotificationService][STUB] email event='{$event}' user_id={$userId} title=" . ($context['title'] ?? ''));
    }

    // -------------------------------------------------------------------------
    // Tenant-aware helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve the tenant_id for a given user from the `users` table.
     * Returns null if the user does not exist or has no tenant.
     */
    private static function resolveTenantId(int $userId): ?int {
        try {
            $stmt = self::db()->prepare('SELECT tenant_id FROM users WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $userId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return ($row && $row['tenant_id']) ? (int) $row['tenant_id'] : null;
        } catch (\Throwable $e) {
            error_log('[NotificationService] resolveTenantId() error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Return an array of user IDs in `$tenantId` that hold `$roleSlug`.
     */
    private static function getUsersForRole(int $tenantId, string $roleSlug): array {
        try {
            $stmt = self::db()->prepare(
                'SELECT u.id
                   FROM users u
                   JOIN user_roles ur ON ur.user_id = u.id
                   JOIN roles r       ON r.id = ur.role_id
                  WHERE u.tenant_id = :tenant_id
                    AND r.slug      = :role_slug
                    AND u.status    = \'active\''
            );
            $stmt->execute([':tenant_id' => $tenantId, ':role_slug' => $roleSlug]);
            return $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
        } catch (\Throwable $e) {
            error_log('[NotificationService] getUsersForRole() error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Returns the shared PDO instance from whatever bootstrap/container this
     * application uses. Adjust if the app wires the DB differently.
     */
    private static function db(): \PDO {
        // Convention used throughout this codebase: global $pdo
        global $pdo;
        if (!$pdo instanceof \PDO) {
            throw new \RuntimeException('NotificationService: PDO instance not available in $pdo');
        }
        return $pdo;
    }
}
