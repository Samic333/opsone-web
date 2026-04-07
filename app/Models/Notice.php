<?php
/**
 * Notice Model — Notices/bulletins with tenant isolation
 */
class Notice {
    public static function allForTenant(int $tenantId, bool $publishedOnly = false): array {
        $sql = "SELECT n.*, u.name as author_name FROM notices n
                LEFT JOIN users u ON n.created_by = u.id
                WHERE n.tenant_id = ?";
        $params = [$tenantId];
        if ($publishedOnly) {
            $sql .= " AND n.published = 1 AND (n.expires_at IS NULL OR n.expires_at > " . dbNow() . ")";
        }
        $sql .= " ORDER BY n.created_at DESC";
        return Database::fetchAll($sql, $params);
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
            "INSERT INTO notices (tenant_id, title, body, priority, category, published, published_at, expires_at, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['tenant_id'], $data['title'], $data['body'],
                $data['priority'] ?? 'normal', $data['category'] ?? 'general',
                $data['published'] ?? 0,
                !empty($data['published']) ? date('Y-m-d H:i:s') : null,
                $data['expires_at'] ?? null, $data['created_by'] ?? null,
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
             published = ?, published_at = ?, expires_at = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [
                $data['title'], $data['body'], $data['priority'] ?? 'normal',
                $data['category'] ?? 'general', $data['published'] ?? 0,
                $publishedAt, $data['expires_at'] ?? null, $id,
            ]
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
