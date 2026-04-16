<?php
/**
 * NoticeApiController — API endpoints for notices (iPad)
 *
 * Routes (see config/routes.php):
 *   GET  /api/notices              — list published notices for tenant
 *   POST /api/notices/{id}/read    — mark notice as read
 *   POST /api/notices/{id}/ack     — acknowledge notice (requires_ack = 1)
 */
class NoticeApiController {

    // ─── GET /api/notices ──────────────────────────────────────
    /**
     * Returns all published, non-expired notices for the authenticated user's tenant.
     * Includes read/ack status for the current user.
     *
     * Response: { notices: [ NoticeItem ] }
     */
    public function index(): void {
        $user     = apiUser();
        $tenantId = apiTenantId();

        if (!AuthorizationService::isModuleEnabledForTenant('notices', $tenantId)) {
            jsonResponse(['notices' => [], 'module_disabled' => true]);
        }

        // Filter by user's roles so role-targeted notices are respected
        $userRoles = UserModel::getRoles($user['id']);
        $roleSlugs = array_column($userRoles, 'slug');
        $notices   = Notice::forUserRoles($tenantId, $roleSlugs);

        // Fetch read/ack state for this user in one query
        $readMap = [];
        if (!empty($notices)) {
            $ids   = implode(',', array_map(fn($n) => (int)$n['id'], $notices));
            $reads = Database::fetchAll(
                "SELECT notice_id, read_at, acknowledged_at
                 FROM notice_reads
                 WHERE user_id = ? AND notice_id IN ($ids)",
                [$user['id']]
            );
            foreach ($reads as $r) {
                $readMap[(int)$r['notice_id']] = $r;
            }
        }

        $result = array_map(function ($n) use ($readMap) {
            $id  = (int)$n['id'];
            $r   = $readMap[$id] ?? null;
            return [
                'id'              => $id,
                'title'           => $n['title'],
                'body'            => $n['body'],
                'priority'        => $n['priority'],
                'category'        => $n['category'] ?? 'general',
                'requires_ack'    => (bool)($n['requires_ack'] ?? false),
                'author'          => $n['author_name'] ?? null,
                'published_at'    => $n['published_at'],
                'expires_at'      => $n['expires_at'],
                'is_read'         => $r !== null,
                'read_at'         => $r['read_at'] ?? null,
                'is_acknowledged' => $r !== null && $r['acknowledged_at'] !== null,
                'acknowledged_at' => $r['acknowledged_at'] ?? null,
            ];
        }, $notices);

        jsonResponse(['notices' => $result]);
    }

    // ─── POST /api/notices/{id}/read ───────────────────────────
    /**
     * Marks a notice as read for the current user. Idempotent.
     *
     * Response: { success: true, read_at: "..." }
     */
    public function markRead(int $noticeId): void {
        $user     = apiUser();
        $tenantId = apiTenantId();

        if (!AuthorizationService::isModuleEnabledForTenant('notices', $tenantId)) {
            jsonResponse(['error' => 'Module disabled'], 403);
            return;
        }

        // Verify notice belongs to tenant and is published
        $notice = Notice::find($noticeId);
        if (!$notice || (int)$notice['tenant_id'] !== $tenantId) {
            jsonResponse(['error' => 'Notice not found'], 404);
            return;
        }

        $now = dbNow();

        $existing = Database::fetch(
            "SELECT id, read_at FROM notice_reads WHERE notice_id = ? AND user_id = ?",
            [$noticeId, $user['id']]
        );

        if (!$existing) {
            Database::insert(
                "INSERT INTO notice_reads (notice_id, user_id, tenant_id, read_at)
                 VALUES (?, ?, ?, ?)",
                [$noticeId, $user['id'], $tenantId, $now]
            );
        }
        // Idempotent — if already read, do not overwrite read_at

        jsonResponse(['success' => true, 'read_at' => $existing['read_at'] ?? $now]);
    }

    // ─── POST /api/notices/{id}/ack ────────────────────────────
    /**
     * Acknowledges a notice (requires_ack must be 1).
     * Also marks as read if not already.
     *
     * Response: { success: true, acknowledged_at: "..." }
     */
    public function acknowledge(int $noticeId): void {
        $user     = apiUser();
        $tenantId = apiTenantId();

        if (!AuthorizationService::isModuleEnabledForTenant('notices', $tenantId)) {
            jsonResponse(['error' => 'Module disabled'], 403);
            return;
        }

        $notice = Notice::find($noticeId);
        if (!$notice || (int)$notice['tenant_id'] !== $tenantId) {
            jsonResponse(['error' => 'Notice not found'], 404);
            return;
        }
        if (empty($notice['requires_ack'])) {
            jsonResponse(['error' => 'This notice does not require acknowledgement'], 422);
            return;
        }

        $now = dbNow();

        $existing = Database::fetch(
            "SELECT id, acknowledged_at FROM notice_reads WHERE notice_id = ? AND user_id = ?",
            [$noticeId, $user['id']]
        );

        if (!$existing) {
            Database::insert(
                "INSERT INTO notice_reads (notice_id, user_id, tenant_id, read_at, acknowledged_at)
                 VALUES (?, ?, ?, ?, ?)",
                [$noticeId, $user['id'], $tenantId, $now, $now]
            );
        } else {
            Database::execute(
                "UPDATE notice_reads SET acknowledged_at = ? WHERE notice_id = ? AND user_id = ?",
                [$now, $noticeId, $user['id']]
            );
        }

        // Audit log
        AuditLog::apiLog(
            'notice_acknowledged',
            'notice',
            $noticeId,
            "Notice '{$notice['title']}' acknowledged via iPad"
        );

        jsonResponse(['success' => true, 'acknowledged_at' => $now]);
    }
}
