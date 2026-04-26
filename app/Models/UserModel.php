<?php
/**
 * User Model — CRUD with tenant isolation
 */
class UserModel {
    public static function findByEmail(string $email, ?int $tenantId = null): ?array {
        if ($tenantId) {
            return Database::fetch(
                "SELECT * FROM users WHERE email = ? AND tenant_id = ?",
                [$email, $tenantId]
            );
        }
        // Multi-tenant fallback: users.email is UNIQUE only per (email, tenant_id),
        // so the same email can exist in multiple airlines. Returning the first row
        // would silently log the user into the wrong tenant. Succeed only when the
        // email is unambiguous across the whole instance.
        $rows = Database::fetchAll(
            "SELECT * FROM users WHERE email = ? LIMIT 2",
            [$email]
        );
        return (count($rows) === 1) ? $rows[0] : null;
    }

    public static function find(int $id): ?array {
        return Database::fetch(
            "SELECT u.*, d.name AS department_name, b.code AS base_code, f.name AS fleet_name
             FROM users u
             LEFT JOIN departments d ON u.department_id = d.id
             LEFT JOIN bases b ON u.base_id = b.id
             LEFT JOIN fleets f ON u.fleet_id = f.id
             WHERE u.id = ?",
            [$id]
        );
    }

    public static function allForTenant(int $tenantId, ?string $status = null): array {
        $sql = "SELECT u.*, d.name AS department_name, b.code AS base_code, f.name AS fleet_name,
                group_concat(r.name, ', ') AS role_names
                FROM users u
                LEFT JOIN departments d ON u.department_id = d.id
                LEFT JOIN bases b ON u.base_id = b.id
                LEFT JOIN fleets f ON u.fleet_id = f.id
                LEFT JOIN user_roles ur ON ur.user_id = u.id
                LEFT JOIN roles r ON ur.role_id = r.id
                WHERE u.tenant_id = ?";
        $params = [$tenantId];
        if ($status) {
            $sql .= " AND u.status = ?";
            $params[] = $status;
        }
        $sql .= " GROUP BY u.id ORDER BY u.name ASC";
        return Database::fetchAll($sql, $params);
    }

    public static function create(array $data): int {
        return Database::insert(
            "INSERT INTO users
                (tenant_id, name, email, password_hash, employee_id,
                 department_id, base_id, fleet_id, employment_status,
                 status, mobile_access, web_access)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['tenant_id'],
                $data['name'],
                $data['email'],
                password_hash($data['password'], PASSWORD_BCRYPT),
                $data['employee_id'] ?? null,
                $data['department_id'] ?: null,
                $data['base_id'] ?: null,
                $data['fleet_id'] ?: null,
                $data['employment_status'] ?? null,
                $data['status'] ?? 'active',
                $data['mobile_access'] ?? 1,
                $data['web_access'] ?? 1,
            ]
        );
    }

    public static function update(int $id, array $data): void {
        Database::execute(
            "UPDATE users SET
                name = ?, email = ?, employee_id = ?,
                department_id = ?, base_id = ?, fleet_id = ?,
                employment_status = ?, status = ?,
                mobile_access = ?, web_access = ?,
                updated_at = CURRENT_TIMESTAMP
             WHERE id = ?",
            [
                $data['name'], $data['email'], $data['employee_id'] ?? null,
                $data['department_id'] ?: null, $data['base_id'] ?: null,
                $data['fleet_id'] ?: null, $data['employment_status'] ?? null,
                $data['status'], $data['mobile_access'] ?? 1,
                $data['web_access'] ?? 1, $id,
            ]
        );
        if (!empty($data['password'])) {
            Database::execute(
                "UPDATE users SET password_hash = ? WHERE id = ?",
                [password_hash($data['password'], PASSWORD_BCRYPT), $id]
            );
        }
    }

    public static function toggleStatus(int $id): void {
        $user = self::find($id);
        if (!$user) return;
        $newStatus = $user['status'] === 'active' ? 'suspended' : 'active';
        Database::execute("UPDATE users SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", [$newStatus, $id]);
    }

    public static function getRoles(int $userId): array {
        return Database::fetchAll(
            "SELECT r.* FROM roles r JOIN user_roles ur ON ur.role_id = r.id WHERE ur.user_id = ?",
            [$userId]
        );
    }

    public static function getRoleSlugs(int $userId): array {
        $roles = Database::fetchAll(
            "SELECT r.slug FROM roles r JOIN user_roles ur ON ur.role_id = r.id WHERE ur.user_id = ?",
            [$userId]
        );
        return array_column($roles, 'slug');
    }

    public static function assignRole(int $userId, int $roleId, int $tenantId): void {
        Database::execute(
            Database::insertIgnore() . " INTO user_roles (user_id, role_id, tenant_id) VALUES (?, ?, ?)",
            [$userId, $roleId, $tenantId]
        );
    }

    public static function clearRoles(int $userId): void {
        Database::execute("DELETE FROM user_roles WHERE user_id = ?", [$userId]);
    }

    public static function countByTenant(int $tenantId, ?string $status = null): int {
        $sql = "SELECT COUNT(*) as c FROM users WHERE tenant_id = ?";
        $params = [$tenantId];
        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        return (int) Database::fetch($sql, $params)['c'];
    }

    public static function countByRole(int $tenantId): array {
        return Database::fetchAll(
            "SELECT r.name, r.slug, COUNT(ur.user_id) as count
             FROM roles r
             LEFT JOIN user_roles ur ON ur.role_id = r.id
             WHERE r.tenant_id = ?
             GROUP BY r.id ORDER BY count DESC",
            [$tenantId]
        );
    }

    public static function countByStatus(int $tenantId): array {
        return Database::fetchAll(
            "SELECT status, COUNT(*) as count FROM users WHERE tenant_id = ? GROUP BY status",
            [$tenantId]
        );
    }

    public static function recentLogins(int $tenantId, int $limit = 10): array {
        return Database::fetchAll(
            "SELECT la.*, u.name FROM login_activity la
             LEFT JOIN users u ON la.user_id = u.id
             WHERE la.tenant_id = ? ORDER BY la.created_at DESC LIMIT ?",
            [$tenantId, $limit]
        );
    }

    public static function updateLastLogin(int $id): void {
        Database::execute("UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = ?", [$id]);
    }
}
