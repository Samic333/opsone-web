<?php
/**
 * FileModel — document/manual management with tenant isolation.
 *
 * Phase 4 additions:
 *   • Department + base targeting alongside existing role targeting.
 *   • Version chain via replaces_file_id / superseded_at.
 *   • Read receipts (file_reads) distinct from explicit acknowledgements.
 */
class FileModel {
    public static function allForTenant(int $tenantId, ?string $status = null): array {
        $sql = "SELECT f.*, fc.name as category_name, u.name as uploaded_by_name
                FROM files f
                LEFT JOIN file_categories fc ON f.category_id = fc.id
                LEFT JOIN users u ON f.uploaded_by = u.id
                WHERE f.tenant_id = ?";
        $params = [$tenantId];
        if ($status) {
            $sql .= " AND f.status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY f.created_at DESC";
        return Database::fetchAll($sql, $params);
    }

    public static function find(int $id): ?array {
        return Database::fetch(
            "SELECT f.*, fc.name as category_name, u.name as uploaded_by_name
             FROM files f
             LEFT JOIN file_categories fc ON f.category_id = fc.id
             LEFT JOIN users u ON f.uploaded_by = u.id
             WHERE f.id = ?",
            [$id]
        );
    }

    public static function create(array $data): int {
        return Database::insert(
            "INSERT INTO files
                (tenant_id, category_id, title, description, file_path, file_name, file_size,
                 mime_type, version, replaces_file_id, status, effective_date, requires_ack, uploaded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['tenant_id'], $data['category_id'] ?: null, $data['title'],
                $data['description'] ?? null, $data['file_path'], $data['file_name'],
                $data['file_size'] ?? 0, $data['mime_type'] ?? null,
                $data['version'] ?? '1.0',
                $data['replaces_file_id'] ?? null,
                $data['status'] ?? 'draft',
                $data['effective_date'] ?? null, $data['requires_ack'] ?? 0,
                $data['uploaded_by'] ?? null,
            ]
        );
    }

    public static function update(int $id, array $data): void {
        Database::execute(
            "UPDATE files SET title = ?, description = ?, category_id = ?, version = ?,
             status = ?, effective_date = ?, expires_at = ?, requires_ack = ?,
             updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [
                $data['title'], $data['description'] ?: null,
                $data['category_id'] ?: null, $data['version'] ?? '1.0',
                $data['status'] ?? 'draft', $data['effective_date'] ?: null,
                $data['expires_at'] ?: null, $data['requires_ack'] ?? 0,
                $id,
            ]
        );
    }

    public static function togglePublish(int $id): void {
        $file = self::find($id);
        if (!$file) return;
        $newStatus = $file['status'] === 'published' ? 'draft' : 'published';
        Database::execute(
            "UPDATE files SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$newStatus, $id]
        );
    }

    public static function setStatus(int $id, string $status): void {
        Database::execute(
            "UPDATE files SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$status, $id]
        );
    }

    public static function markSuperseded(int $id): void {
        Database::execute(
            "UPDATE files SET status = 'archived', superseded_at = CURRENT_TIMESTAMP,
             updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$id]
        );
    }

    public static function delete(int $id): void {
        $file = self::find($id);
        if ($file && $file['file_path']) {
            $fullPath = storagePath($file['file_path']);
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }
        Database::execute("DELETE FROM files WHERE id = ?", [$id]);
    }

    // ─── Role / Department / Base targeting ─────────────────────────────

    public static function setRoleVisibility(int $fileId, array $roleIds): void {
        Database::execute("DELETE FROM file_role_visibility WHERE file_id = ?", [$fileId]);
        foreach ($roleIds as $roleId) {
            Database::execute(
                "INSERT INTO file_role_visibility (file_id, role_id) VALUES (?, ?)",
                [$fileId, (int)$roleId]
            );
        }
    }

    public static function setDepartmentVisibility(int $fileId, array $departmentIds): void {
        Database::execute("DELETE FROM file_department_visibility WHERE file_id = ?", [$fileId]);
        foreach ($departmentIds as $deptId) {
            Database::execute(
                "INSERT INTO file_department_visibility (file_id, department_id) VALUES (?, ?)",
                [$fileId, (int)$deptId]
            );
        }
    }

    public static function setBaseVisibility(int $fileId, array $baseIds): void {
        Database::execute("DELETE FROM file_base_visibility WHERE file_id = ?", [$fileId]);
        foreach ($baseIds as $baseId) {
            Database::execute(
                "INSERT INTO file_base_visibility (file_id, base_id) VALUES (?, ?)",
                [$fileId, (int)$baseId]
            );
        }
    }

    public static function getRoleVisibilityIds(int $fileId): array {
        return array_column(Database::fetchAll(
            "SELECT role_id FROM file_role_visibility WHERE file_id = ?", [$fileId]
        ), 'role_id');
    }

    public static function getDepartmentVisibilityIds(int $fileId): array {
        return array_column(Database::fetchAll(
            "SELECT department_id FROM file_department_visibility WHERE file_id = ?", [$fileId]
        ), 'department_id');
    }

    public static function getBaseVisibilityIds(int $fileId): array {
        return array_column(Database::fetchAll(
            "SELECT base_id FROM file_base_visibility WHERE file_id = ?", [$fileId]
        ), 'base_id');
    }

    public static function getVisibleRoles(int $fileId): array {
        return Database::fetchAll(
            "SELECT r.* FROM roles r
             JOIN file_role_visibility frv ON frv.role_id = r.id
             WHERE frv.file_id = ?",
            [$fileId]
        );
    }

    /**
     * Targeting summary — human-readable audience description for admin list.
     */
    public static function audienceSummary(int $fileId): string {
        $roles = Database::fetchAll(
            "SELECT r.name FROM roles r JOIN file_role_visibility frv ON frv.role_id = r.id
             WHERE frv.file_id = ? ORDER BY r.name", [$fileId]
        );
        $depts = Database::fetchAll(
            "SELECT d.name FROM departments d JOIN file_department_visibility fdv ON fdv.department_id = d.id
             WHERE fdv.file_id = ? ORDER BY d.name", [$fileId]
        );
        $bases = Database::fetchAll(
            "SELECT b.name FROM bases b JOIN file_base_visibility fbv ON fbv.base_id = b.id
             WHERE fbv.file_id = ? ORDER BY b.name", [$fileId]
        );
        if (!$roles && !$depts && !$bases) return 'All staff';
        $parts = [];
        if ($roles) $parts[] = count($roles) . ' role' . (count($roles) === 1 ? '' : 's');
        if ($depts) $parts[] = count($depts) . ' dept' . (count($depts) === 1 ? '' : 's');
        if ($bases) $parts[] = count($bases) . ' base' . (count($bases) === 1 ? '' : 's');
        return implode(' · ', $parts);
    }

    // ─── Visibility query (OR semantics across role/dept/base) ──────────

    /**
     * Files visible to a specific user, based on their roles, department, and base.
     *
     * Visibility rule (OR semantics — admin composes targets):
     *   A published file is visible to a user if ANY of:
     *     1. No targeting rows exist at all (= all staff)
     *     2. User holds a role that's in file_role_visibility
     *     3. User's department is in file_department_visibility
     *     4. User's base is in file_base_visibility
     *
     * Superseded files (superseded_at NOT NULL) are excluded unless $includeSuperseded.
     */
    public static function forUser(
        int $tenantId,
        int $userId,
        array $roleSlugs,
        ?int $departmentId,
        ?int $baseId,
        bool $includeSuperseded = false
    ): array {
        if (empty($roleSlugs)) $roleSlugs = ['__none__']; // guard empty IN()
        $rolePlaceholders = implode(',', array_fill(0, count($roleSlugs), '?'));

        $sql = "
            SELECT DISTINCT f.*, fc.name as category_name,
                   fr.read_at         AS user_read_at,
                   fa.acknowledged_at AS user_acknowledged_at,
                   fa.version         AS user_acknowledged_version
              FROM files f
              LEFT JOIN file_categories fc ON f.category_id = fc.id
              LEFT JOIN file_reads             fr ON fr.file_id = f.id AND fr.user_id = ?
              LEFT JOIN file_acknowledgements  fa ON fa.file_id = f.id AND fa.user_id = ?
             WHERE f.tenant_id = ?
               AND f.status    = 'published'
               " . ($includeSuperseded ? "" : "AND f.superseded_at IS NULL") . "
               AND (
                    -- (1) untargeted = all staff
                    NOT EXISTS (SELECT 1 FROM file_role_visibility        WHERE file_id = f.id)
                AND NOT EXISTS (SELECT 1 FROM file_department_visibility  WHERE file_id = f.id)
                AND NOT EXISTS (SELECT 1 FROM file_base_visibility        WHERE file_id = f.id)
                 OR -- (2) role match
                    EXISTS (
                        SELECT 1 FROM file_role_visibility frv
                          JOIN roles r ON r.id = frv.role_id
                         WHERE frv.file_id = f.id AND r.slug IN ($rolePlaceholders)
                    )
                 OR -- (3) department match
                    EXISTS (
                        SELECT 1 FROM file_department_visibility fdv
                         WHERE fdv.file_id = f.id AND fdv.department_id = ?
                    )
                 OR -- (4) base match
                    EXISTS (
                        SELECT 1 FROM file_base_visibility fbv
                         WHERE fbv.file_id = f.id AND fbv.base_id = ?
                    )
               )
             ORDER BY f.created_at DESC
        ";

        $params = array_merge(
            [$userId, $userId, $tenantId],
            $roleSlugs,
            [$departmentId ?: 0, $baseId ?: 0]
        );

        return Database::fetchAll($sql, $params);
    }

    /**
     * Legacy alias — kept so nothing breaks if anything else calls it.
     * @deprecated Use forUser() instead.
     */
    public static function forUserRoles(int $tenantId, array $roleSlugs): array {
        if (empty($roleSlugs)) return [];
        $placeholders = implode(',', array_fill(0, count($roleSlugs), '?'));
        $params = array_merge([$tenantId], $roleSlugs, [$tenantId]);
        return Database::fetchAll(
            "SELECT DISTINCT f.*, fc.name as category_name
               FROM files f
               LEFT JOIN file_categories fc ON f.category_id = fc.id
               LEFT JOIN file_role_visibility frv ON frv.file_id = f.id
               LEFT JOIN roles r ON frv.role_id = r.id
              WHERE f.tenant_id = ? AND f.status = 'published'
                AND f.superseded_at IS NULL
                AND (frv.file_id IS NULL OR r.slug IN ($placeholders))
                AND f.tenant_id = ?
              ORDER BY f.created_at DESC",
            $params
        );
    }

    // ─── Version chain ──────────────────────────────────────────────────

    /** Walk backwards through replaces_file_id to build full version history. */
    public static function versionHistory(int $fileId): array {
        $history = [];
        $current = self::find($fileId);
        if (!$current) return [];

        // Walk backwards from current → originals
        $cursor = $current;
        $guard  = 50;
        while ($cursor && $guard-- > 0) {
            $history[] = $cursor;
            if (empty($cursor['replaces_file_id'])) break;
            $cursor = self::find((int)$cursor['replaces_file_id']);
        }

        // Walk forward for anything that replaces $fileId (newer versions)
        $newer  = Database::fetchAll(
            "SELECT * FROM files WHERE replaces_file_id = ? ORDER BY created_at DESC",
            [$fileId]
        );
        return ['chain' => $history, 'newer' => $newer];
    }

    // ─── Read receipts ──────────────────────────────────────────────────

    /** Record that a user opened/downloaded a file. Idempotent upsert on first view. */
    public static function markRead(int $fileId, int $userId, int $tenantId, ?string $version = null): void {
        $existing = Database::fetch(
            "SELECT id FROM file_reads WHERE file_id = ? AND user_id = ?",
            [$fileId, $userId]
        );
        if ($existing) return; // first-read sticks; don't churn timestamps on every view
        Database::insert(
            "INSERT INTO file_reads (file_id, user_id, tenant_id, version) VALUES (?, ?, ?, ?)",
            [$fileId, $userId, $tenantId, $version]
        );
    }

    /** Per-file recipient roll-up used by admin acknowledgement report. */
    public static function recipientReport(int $fileId, int $tenantId): array {
        $file = self::find($fileId);
        if (!$file) return [];

        // Build the set of users this file targets using the same OR semantics as forUser().
        $roles = self::getRoleVisibilityIds($fileId);
        $depts = self::getDepartmentVisibilityIds($fileId);
        $bases = self::getBaseVisibilityIds($fileId);

        $where  = ["u.tenant_id = ?", "u.status = 'active'"];
        $params = [$tenantId];

        if (empty($roles) && empty($depts) && empty($bases)) {
            // Untargeted = all active users
        } else {
            $clauses = [];
            if ($roles) {
                $ph = implode(',', array_fill(0, count($roles), '?'));
                $clauses[] = "u.id IN (SELECT ur.user_id FROM user_roles ur WHERE ur.role_id IN ($ph))";
                $params = array_merge($params, $roles);
            }
            if ($depts) {
                $ph = implode(',', array_fill(0, count($depts), '?'));
                $clauses[] = "u.department_id IN ($ph)";
                $params = array_merge($params, $depts);
            }
            if ($bases) {
                $ph = implode(',', array_fill(0, count($bases), '?'));
                $clauses[] = "u.base_id IN ($ph)";
                $params = array_merge($params, $bases);
            }
            $where[] = '(' . implode(' OR ', $clauses) . ')';
        }

        $sql = "SELECT u.id, u.name, u.email, u.employee_id,
                       fr.read_at,
                       fa.acknowledged_at, fa.version as acked_version
                  FROM users u
                  LEFT JOIN file_reads            fr ON fr.file_id = ? AND fr.user_id = u.id
                  LEFT JOIN file_acknowledgements fa ON fa.file_id = ? AND fa.user_id = u.id
                 WHERE " . implode(' AND ', $where) . "
                 ORDER BY u.name ASC";

        return Database::fetchAll($sql, array_merge([$fileId, $fileId], $params));
    }

    public static function getCategories(int $tenantId): array {
        return Database::fetchAll(
            "SELECT * FROM file_categories WHERE tenant_id = ? ORDER BY name",
            [$tenantId]
        );
    }

    public static function countByTenant(int $tenantId): int {
        return (int) Database::fetch(
            "SELECT COUNT(*) as c FROM files WHERE tenant_id = ?", [$tenantId]
        )['c'];
    }

    public static function recentUploads(int $tenantId, int $limit = 5): array {
        return Database::fetchAll(
            "SELECT f.*, u.name as uploaded_by_name
               FROM files f LEFT JOIN users u ON f.uploaded_by = u.id
              WHERE f.tenant_id = ?
              ORDER BY f.created_at DESC LIMIT ?",
            [$tenantId, $limit]
        );
    }
}
