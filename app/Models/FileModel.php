<?php
/**
 * FileModel — document/manual management with tenant isolation
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
            "INSERT INTO files (tenant_id, category_id, title, description, file_path, file_name, file_size, mime_type, version, status, effective_date, requires_ack, uploaded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['tenant_id'], $data['category_id'] ?: null, $data['title'],
                $data['description'] ?? null, $data['file_path'], $data['file_name'],
                $data['file_size'] ?? 0, $data['mime_type'] ?? null,
                $data['version'] ?? '1.0', $data['status'] ?? 'draft',
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

    public static function getRoleVisibilityIds(int $fileId): array {
        $rows = Database::fetchAll(
            "SELECT role_id FROM file_role_visibility WHERE file_id = ?",
            [$fileId]
        );
        return array_column($rows, 'role_id');
    }

    public static function togglePublish(int $id): void {
        $file = self::find($id);
        if (!$file) return;
        $newStatus = $file['status'] === 'published' ? 'draft' : 'published';
        Database::execute("UPDATE files SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", [$newStatus, $id]);
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

    public static function setRoleVisibility(int $fileId, array $roleIds): void {
        Database::execute("DELETE FROM file_role_visibility WHERE file_id = ?", [$fileId]);
        foreach ($roleIds as $roleId) {
            Database::execute(
                "INSERT INTO file_role_visibility (file_id, role_id) VALUES (?, ?)",
                [$fileId, $roleId]
            );
        }
    }

    public static function getVisibleRoles(int $fileId): array {
        return Database::fetchAll(
            "SELECT r.* FROM roles r JOIN file_role_visibility frv ON frv.role_id = r.id WHERE frv.file_id = ?",
            [$fileId]
        );
    }

    /**
     * Get files visible to a specific user based on their roles
     */
    public static function forUserRoles(int $tenantId, array $roleSlugs): array {
        // Get role IDs for the user's roles
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
             AND (frv.file_id IS NULL OR r.slug IN ($placeholders))
             AND f.tenant_id = ?
             ORDER BY f.created_at DESC",
            $params
        );
    }

    public static function getCategories(int $tenantId): array {
        return Database::fetchAll("SELECT * FROM file_categories WHERE tenant_id = ?", [$tenantId]);
    }

    public static function countByTenant(int $tenantId): int {
        return (int) Database::fetch("SELECT COUNT(*) as c FROM files WHERE tenant_id = ?", [$tenantId])['c'];
    }

    public static function recentUploads(int $tenantId, int $limit = 5): array {
        return Database::fetchAll(
            "SELECT f.*, u.name as uploaded_by_name FROM files f LEFT JOIN users u ON f.uploaded_by = u.id
             WHERE f.tenant_id = ? ORDER BY f.created_at DESC LIMIT ?",
            [$tenantId, $limit]
        );
    }
}
