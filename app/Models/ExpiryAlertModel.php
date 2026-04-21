<?php
/**
 * ExpiryAlertModel — notification ledger for expiry events.
 *
 * Prevents duplicate sends and tracks which recipients (user / HR / line
 * manager) have been notified for each (user, entity, level) combination.
 * Scanning logic lives in ExpiryAlertService.
 */
class ExpiryAlertModel {

    public const LEVEL_WARNING  = 'warning';
    public const LEVEL_CRITICAL = 'critical';
    public const LEVEL_EXPIRED  = 'expired';

    public static function find(int $id): ?array {
        return Database::fetch("SELECT * FROM expiry_alerts WHERE id = ?", [$id]);
    }

    public static function findExisting(int $tenantId, int $userId, string $entity, int $entityId, string $level): ?array {
        return Database::fetch(
            "SELECT * FROM expiry_alerts
             WHERE tenant_id = ? AND user_id = ? AND entity_type = ? AND entity_id = ? AND alert_level = ?",
            [$tenantId, $userId, $entity, $entityId, $level]
        );
    }

    /**
     * Upsert an alert. If it already exists we update the expiry date (in case
     * the record was extended) and clear cleared_at if it's still outstanding.
     */
    public static function record(int $tenantId, int $userId, string $entity, int $entityId,
                                  string $level, string $expiryDate): int {
        $existing = self::findExisting($tenantId, $userId, $entity, $entityId, $level);
        if ($existing) {
            Database::execute(
                "UPDATE expiry_alerts SET expiry_date = ?, cleared_at = NULL WHERE id = ?",
                [$expiryDate, (int) $existing['id']]
            );
            return (int) $existing['id'];
        }
        return Database::insert(
            "INSERT INTO expiry_alerts
             (tenant_id, user_id, entity_type, entity_id, expiry_date, alert_level)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$tenantId, $userId, $entity, $entityId, $expiryDate, $level]
        );
    }

    public static function markSent(int $id, bool $toUser, bool $toHr, bool $toManager): void {
        $parts = [];
        $params = [];
        if ($toUser)    { $parts[] = 'sent_to_user = 1'; }
        if ($toHr)      { $parts[] = 'sent_to_hr = 1'; }
        if ($toManager) { $parts[] = 'sent_to_manager = 1'; }
        if (empty($parts)) return;
        $parts[] = 'last_sent_at = CURRENT_TIMESTAMP';
        Database::execute(
            "UPDATE expiry_alerts SET " . implode(', ', $parts) . " WHERE id = ?",
            [$id]
        );
    }

    public static function clear(int $id): void {
        Database::execute(
            "UPDATE expiry_alerts SET cleared_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$id]
        );
    }

    public static function openForTenant(int $tenantId, int $limit = 200): array {
        return Database::fetchAll(
            "SELECT ea.*, u.name AS user_name, u.employee_id
             FROM expiry_alerts ea
             JOIN users u ON ea.user_id = u.id
             WHERE ea.tenant_id = ? AND ea.cleared_at IS NULL
             ORDER BY CASE ea.alert_level WHEN 'expired' THEN 1 WHEN 'critical' THEN 2 ELSE 3 END,
                      ea.expiry_date ASC
             LIMIT ?",
            [$tenantId, $limit]
        );
    }

    public static function openForUser(int $userId, int $limit = 100): array {
        return Database::fetchAll(
            "SELECT * FROM expiry_alerts
             WHERE user_id = ? AND cleared_at IS NULL
             ORDER BY expiry_date ASC LIMIT ?",
            [$userId, $limit]
        );
    }
}
