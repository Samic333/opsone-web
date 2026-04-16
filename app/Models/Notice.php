<?php
/**
 * Notice Model — Notices/bulletins with tenant isolation
 */
class Notice {
    public static function allForTenant(int $tenantId, bool $publishedOnly = false, ?string $category = null, ?string $priority = null): array {
        $sql = "SELECT n.*, u.name as author_name FROM notices n
                LEFT JOIN users u ON n.created_by = u.id
                WHERE n.tenant_id = ?";
        $params = [$tenantId];
        if ($publishedOnly) {
            $sql .= " AND n.published = 1 AND (n.expires_at IS NULL OR n.expires_at > " . dbNow() . ")";
        }
        if ($category) {
            $sql .= " AND n.category = ?";
            $params[] = $category;
        }
        if ($priority) {
            $sql .= " AND n.priority = ?";
            $params[] = $priority;
        }
        $sql .= " ORDER BY n.created_at DESC";
        return Database::fetchAll($sql, $params);
    }

    /**
     * Get published notices visible to a user based on their role slugs.
     * If a notice has no role restrictions it is visible to all.
     */
    public static function forUserRoles(int $tenantId, array $roleSlugs): array {
        if (empty($roleSlugs)) {
            $roleSlugs = ['__none__'];
        }
        $ph = implode(',', array_fill(0, count($roleSlugs), '?'));
        $params = array_merge([$tenantId], $roleSlugs, [$tenantId]);
        return Database::fetchAll(
            "SELECT DISTINCT n.*, u.name as author_name
             FROM notices n
             LEFT JOIN users u ON n.created_by = u.id
             LEFT JOIN notice_role_visibility nrv ON nrv.notice_id = n.id
             LEFT JOIN roles r ON nrv.role_id = r.id
             WHERE n.tenant_id = ? AND n.published = 1
               AND (n.expires_at IS NULL OR n.expires_at > " . dbNow() . ")
               AND (nrv.notice_id IS NULL OR r.slug IN ($ph))
               AND n.tenant_id = ?
             ORDER BY n.priority DESC, n.created_at DESC",
            $params
        );
    }

    public static function find(int $id): ?array {
        return Database::fetch(
            "SELECT n.*, u.name as author_name FROM notices n
             LEFT JOIN users u ON n.created_by = u.id WHERE n.id = ?",
            [$id]
        );
    }

    public static function create(array $data): int {
        return Database::insert(
            "INSERT INTO notices (tenant_id, title, body, priority, category, published, published_at, expires_at, requires_ack, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['tenant_id'], $data['title'], $data['body'],
                $data['priority'] ?? 'normal', $data['category'] ?? 'general',
                $data['published'] ?? 0,
                !empty($data['published']) ? date('Y-m-d H:i:s') : null,
                $data['expires_at'] ?? null,
                $data['requires_ack'] ?? 0,
                $data['created_by'] ?? null,
            ]
        );
    }

    public static function update(int $id, array $data): void {
        $notice = self::find($id);
        $wasPublished = $notice['published'] ?? 0;
        $publishedAt = $notice['published_at'] ?? null;

        if (!$wasPublished && !empty($data['published'])) {
            $publishedAt = date('Y-m-d H:i:s');
        }

        Database::execute(
            "UPDATE notices SET title = ?, body = ?, priority = ?, category = ?,
             published = ?, published_at = ?, expires_at = ?, requires_ack = ?,
             updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [
                $data['title'], $data['body'], $data['priority'] ?? 'normal',
                $data['category'] ?? 'general', $data['published'] ?? 0,
                $publishedAt, $data['expires_at'] ?? null,
                $data['requires_ack'] ?? 0, $id,
            ]
        );
    }

    // ─── Role Visibility ────────────────────────────────────

    public static function setRoleVisibility(int $noticeId, array $roleIds): void {
        Database::execute("DELETE FROM notice_role_visibility WHERE notice_id = ?", [$noticeId]);
        foreach ($roleIds as $roleId) {
            Database::execute(
                "INSERT INTO notice_role_visibility (notice_id, role_id) VALUES (?, ?)",
                [$noticeId, (int)$roleId]
            );
        }
    }

    public static function getRoleVisibilityIds(int $noticeId): array {
        $rows = Database::fetchAll(
            "SELECT role_id FROM notice_role_visibility WHERE notice_id = ?",
            [$noticeId]
        );
        return array_column($rows, 'role_id');
    }

    // ─── Notice Categories ───────────────────────────────────

    public static function getCategories(int $tenantId): array {
        return Database::fetchAll(
            "SELECT * FROM notice_categories WHERE tenant_id = ? ORDER BY sort_order, name",
            [$tenantId]
        );
    }

    public static function createCategory(int $tenantId, string $name, string $slug): void {
        Database::execute(
            Database::insertIgnore() . " INTO notice_categories (tenant_id, name, slug) VALUES (?, ?, ?)",
            [$tenantId, $name, $slug]
        );
    }

    public static function deleteCategory(int $id, int $tenantId): void {
        Database::execute(
            "DELETE FROM notice_categories WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );
    }

    public static function delete(int $id): void {
        Database::execute("DELETE FROM notices WHERE id = ?", [$id]);
    }

    public static function togglePublish(int $id): void {
        $notice = self::find($id);
        if (!$notice) return;
        $newPublished = $notice['published'] ? 0 : 1;
        $publishedAt = $newPublished ? date('Y-m-d H:i:s') : $notice['published_at'];
        Database::execute(
            "UPDATE notices SET published = ?, published_at = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$newPublished, $publishedAt, $id]
        );
    }

    public static function countByTenant(int $tenantId): int {
        return (int) Database::fetch("SELECT COUNT(*) as c FROM notices WHERE tenant_id = ?", [$tenantId])['c'];
    }

    public static function activeCount(int $tenantId): int {
        return (int) Database::fetch(
            "SELECT COUNT(*) as c FROM notices WHERE tenant_id = ? AND published = 1 AND (expires_at IS NULL OR expires_at > " . dbNow() . ")",
            [$tenantId]
        )['c'];
    }

    public static function recent(int $tenantId, int $limit = 5): array {
        return Database::fetchAll(
            "SELECT * FROM notices WHERE tenant_id = ? AND published = 1 
             AND (expires_at IS NULL OR expires_at > " . dbNow() . ")
             ORDER BY created_at DESC LIMIT ?",
            [$tenantId, $limit]
        );
    }
}
