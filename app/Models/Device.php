<?php
/**
 * Device Model — mobile device registration and approval
 */
class Device {
    public static function findByUuid(string $uuid, int $userId): ?array {
        return Database::fetch(
            "SELECT * FROM devices WHERE device_uuid = ? AND user_id = ?",
            [$uuid, $userId]
        );
    }

    public static function find(int $id): ?array {
        return Database::fetch(
            "SELECT d.*, u.name as user_name, u.email as user_email,
                    ab.name as approved_by_name, rb.name as revoked_by_name
             FROM devices d
             JOIN users u ON d.user_id = u.id
             LEFT JOIN users ab ON d.approved_by = ab.id
             LEFT JOIN users rb ON d.revoked_by = rb.id
             WHERE d.id = ?",
            [$id]
        );
    }

    public static function allForTenant(int $tenantId, ?string $status = null): array {
        $sql = "SELECT d.*, u.name as user_name, u.email as user_email, u.employee_id,
                       ab.name as approved_by_name
                FROM devices d
                JOIN users u ON d.user_id = u.id
                LEFT JOIN users ab ON d.approved_by = ab.id
                WHERE d.tenant_id = ?";
        $params = [$tenantId];
        if ($status) {
            $sql .= " AND d.approval_status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY d.created_at DESC";
        return Database::fetchAll($sql, $params);
    }

    /**
     * Platform-wide device list (super admin / support). Joins tenant name.
     */
    public static function allForPlatform(?string $status = null): array {
        $sql = "SELECT d.*, u.name as user_name, u.email as user_email, u.employee_id,
                       ab.name as approved_by_name, t.name as tenant_name, t.code as tenant_code
                FROM devices d
                JOIN users u ON d.user_id = u.id
                LEFT JOIN users ab ON d.approved_by = ab.id
                LEFT JOIN tenants t ON t.id = d.tenant_id";
        $params = [];
        if ($status) {
            $sql .= " WHERE d.approval_status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY d.created_at DESC";
        return Database::fetchAll($sql, $params);
    }

    public static function register(array $data): int {
        $id = Database::insert(
            "INSERT INTO devices (tenant_id, user_id, device_uuid, platform, model, os_version, app_version, approval_status, first_login_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', CURRENT_TIMESTAMP)",
            [
                $data['tenant_id'], $data['user_id'], $data['device_uuid'],
                $data['platform'] ?? null, $data['model'] ?? null,
                $data['os_version'] ?? null, $data['app_version'] ?? null,
            ]
        );
        // Log registration
        self::log($id, $data['tenant_id'], 'registered', null, 'Device registered via mobile app');
        return $id;
    }

    public static function approve(int $id, int $approvedBy): void {
        $device = self::find($id);
        if (!$device) return;
        Database::execute(
            "UPDATE devices SET approval_status = 'approved', approved_by = ?, approved_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$approvedBy, $id]
        );
        self::log($id, $device['tenant_id'], 'approved', $approvedBy);
    }

    public static function reject(int $id, int $rejectedBy): void {
        $device = self::find($id);
        if (!$device) return;
        Database::execute(
            "UPDATE devices SET approval_status = 'rejected', updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$id]
        );
        self::log($id, $device['tenant_id'], 'rejected', $rejectedBy);
    }

    public static function revoke(int $id, int $revokedBy): void {
        $device = self::find($id);
        if (!$device) return;
        Database::execute(
            "UPDATE devices SET approval_status = 'revoked', revoked_by = ?, revoked_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$revokedBy, $id]
        );
        self::log($id, $device['tenant_id'], 'revoked', $revokedBy);
        // Also revoke any API tokens for this device
        Database::execute("UPDATE api_tokens SET revoked = 1 WHERE device_id = ?", [$id]);
    }

    public static function updateLastSync(int $id): void {
        Database::execute("UPDATE devices SET last_sync_at = CURRENT_TIMESTAMP WHERE id = ?", [$id]);
    }

    public static function countByStatus(?int $tenantId): array {
        if ($tenantId === null) {
            // Platform-wide aggregation
            return Database::fetchAll(
                "SELECT approval_status, COUNT(*) as count FROM devices GROUP BY approval_status"
            );
        }
        return Database::fetchAll(
            "SELECT approval_status, COUNT(*) as count FROM devices WHERE tenant_id = ? GROUP BY approval_status",
            [$tenantId]
        );
    }

    public static function countPending(int $tenantId): int {
        return (int) Database::fetch(
            "SELECT COUNT(*) as c FROM devices WHERE tenant_id = ? AND approval_status = 'pending'",
            [$tenantId]
        )['c'];
    }

    public static function forUser(int $userId): array {
        return Database::fetchAll("SELECT * FROM devices WHERE user_id = ? ORDER BY created_at DESC", [$userId]);
    }

    public static function getApprovedForUser(int $userId, string $deviceUuid): ?array {
        return Database::fetch(
            "SELECT * FROM devices WHERE user_id = ? AND device_uuid = ? AND approval_status = 'approved'",
            [$userId, $deviceUuid]
        );
    }

    private static function log(int $deviceId, int $tenantId, string $action, ?int $performedBy, ?string $notes = null): void {
        Database::insert(
            "INSERT INTO device_approval_logs (device_id, tenant_id, action, performed_by, notes) VALUES (?, ?, ?, ?, ?)",
            [$deviceId, $tenantId, $action, $performedBy, $notes]
        );
    }

    public static function getLogs(int $deviceId): array {
        return Database::fetchAll(
            "SELECT dal.*, u.name as performed_by_name
             FROM device_approval_logs dal
             LEFT JOIN users u ON dal.performed_by = u.id
             WHERE dal.device_id = ? ORDER BY dal.created_at DESC",
            [$deviceId]
        );
    }

    public static function getLatestSync(int $userId): ?string {
        $result = Database::fetch(
            "SELECT last_sync_at FROM devices WHERE user_id = ? AND approval_status = 'approved' ORDER BY last_sync_at DESC LIMIT 1",
            [$userId]
        );
        return $result['last_sync_at'] ?? null;
    }
}
