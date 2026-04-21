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
    public static function notifyUser(
        int $userId,
        string $title,
        string $body,
        string $link = '',
        string $event = 'notify_user',
        string $priority = 'normal',
        bool $ackRequired = false
    ): void {
        $tenantId = self::resolveTenantId($userId);
        if ($tenantId === null) {
            error_log("[NotificationService] notifyUser(): could not resolve tenant for user_id={$userId}");
            return;
        }

        $context = [
            'tenant_id'    => $tenantId,
            'user_id'      => $userId,
            'title'        => $title,
            'body'         => $body,
            'link'         => $link,
            'priority'     => $priority,
            'ack_required' => $ackRequired,
        ];

        self::dispatch('in_app', $event, $context);

        // Route to push for important/critical; silent channel goes push-only.
        if (in_array($priority, ['important', 'critical', 'silent'], true)) {
            self::dispatch('push', $event, $context);
        }
        // Email reserved for critical operational items.
        if ($priority === 'critical') {
            self::dispatch('email', $event, $context);
        }
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
        $tenantId    = (int) ($context['tenant_id'] ?? 0);
        $userId      = (int) ($context['user_id']   ?? 0);
        $title       = $context['title']    ?? '';
        $body        = $context['body']     ?? '';
        $link        = $context['link']     ?? null;
        $priority    = self::normalizePriority($context['priority'] ?? 'normal');
        $ackRequired = !empty($context['ack_required']) ? 1 : 0;

        if ($tenantId === 0 || $userId === 0 || $title === '') {
            error_log("[NotificationService] dispatchInApp(): missing required context for event '{$event}'");
            return;
        }

        try {
            $db   = self::db();
            $stmt = $db->prepare(
                'INSERT INTO notifications
                    (tenant_id, user_id, title, body, link, priority, event, ack_required)
                 VALUES
                    (:tenant_id, :user_id, :title, :body, :link, :priority, :event, :ack_required)'
            );
            $stmt->execute([
                ':tenant_id'    => $tenantId,
                ':user_id'      => $userId,
                ':title'        => $title,
                ':body'         => $body,
                ':link'         => ($link !== '' ? $link : null),
                ':priority'     => $priority,
                ':event'        => $event,
                ':ack_required' => $ackRequired,
            ]);
        } catch (\Throwable $e) {
            error_log("[NotificationService] dispatchInApp() DB error for event '{$event}': " . $e->getMessage());
        }
    }

    /** Coerce any incoming priority to the allowed set. */
    private static function normalizePriority(string $p): string {
        $p = strtolower($p);
        return in_array($p, ['critical', 'important', 'normal', 'silent'], true) ? $p : 'normal';
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
     * Returns the shared PDO instance.
     *
     * Prefers the canonical Database::getInstance() (used throughout the
     * codebase). Falls back to the legacy `global $pdo` for backwards
     * compatibility with earlier scripts that wire it that way.
     */
    private static function db(): \PDO {
        if (class_exists('Database')) {
            return Database::getInstance();
        }
        global $pdo;
        if (!$pdo instanceof \PDO) {
            throw new \RuntimeException('NotificationService: PDO instance not available');
        }
        return $pdo;
    }
}
