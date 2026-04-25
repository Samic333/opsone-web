<?php
/**
 * NotificationApiController — Phase 5 Notification Refinement (mobile inbox).
 *
 * Exposes the unified notifications table (separate from "notices"/manuals).
 * Notices are still served via /api/notices; this controller drives the
 * bell/inbox for events like flight_assigned, fdm_event_added, document_ack_required, etc.
 *
 * Routes:
 *   GET  /api/notifications                  — inbox (?filter=all|unread|unack, default all)
 *   GET  /api/notifications/counts           — {unread, unack, total}
 *   POST /api/notifications/{id}/read        — mark read
 *   POST /api/notifications/{id}/ack         — acknowledge (for ack_required items)
 *   POST /api/notifications/read-all         — mark all read
 */
class NotificationApiController {

    /** GET /api/notifications */
    public function index(): void {
        $tenantId = apiTenantId();
        $userId   = (int) apiUser()['user_id'];

        $filter = $_GET['filter'] ?? 'all';
        $where  = "user_id = ? AND tenant_id = ?";
        $params = [$userId, $tenantId];

        if ($filter === 'unread')     { $where .= " AND is_read = 0"; }
        elseif ($filter === 'unack')  { $where .= " AND ack_required = 1 AND acknowledged_at IS NULL"; }

        $rows = Database::fetchAll(
            "SELECT id, title, body, link, event, priority, ack_required, acknowledged_at,
                    is_read, read_at, created_at
               FROM notifications
              WHERE $where
              ORDER BY created_at DESC LIMIT 200",
            $params
        );

        $items = array_map(fn($r) => [
            'id'              => (int)$r['id'],
            'title'           => (string)($r['title'] ?? ''),
            'body'            => (string)($r['body']  ?? ''),
            'link'            => $r['link']            ?? null,
            'event'           => $r['event']           ?? null,
            'priority'        => (string)($r['priority'] ?? 'normal'),
            'ack_required'    => !empty($r['ack_required']),
            'acknowledged_at' => $r['acknowledged_at'] ?? null,
            'is_read'         => !empty($r['is_read']),
            'read_at'         => $r['read_at']         ?? null,
            'created_at'      => $r['created_at']      ?? null,
        ], $rows);

        jsonResponse(['notifications' => $items]);
    }

    /** GET /api/notifications/counts */
    public function counts(): void {
        $tenantId = apiTenantId();
        $userId   = (int) apiUser()['user_id'];

        $total  = (int)(Database::fetch(
            "SELECT COUNT(*) c FROM notifications WHERE user_id=? AND tenant_id=?",
            [$userId, $tenantId])['c'] ?? 0);
        $unread = (int)(Database::fetch(
            "SELECT COUNT(*) c FROM notifications WHERE user_id=? AND tenant_id=? AND is_read=0",
            [$userId, $tenantId])['c'] ?? 0);
        $unack  = (int)(Database::fetch(
            "SELECT COUNT(*) c FROM notifications WHERE user_id=? AND tenant_id=? AND ack_required=1 AND acknowledged_at IS NULL",
            [$userId, $tenantId])['c'] ?? 0);

        jsonResponse(['total' => $total, 'unread' => $unread, 'unack' => $unack]);
    }

    /** POST /api/notifications/{id}/read */
    public function markRead(int $id): void {
        $tenantId = apiTenantId();
        $userId   = (int) apiUser()['user_id'];
        Database::execute(
            "UPDATE notifications
                SET is_read = 1, read_at = CURRENT_TIMESTAMP
              WHERE id = ? AND user_id = ? AND tenant_id = ?",
            [$id, $userId, $tenantId]
        );
        jsonResponse(['success' => true]);
    }

    /** POST /api/notifications/{id}/ack */
    public function acknowledge(int $id): void {
        $tenantId = apiTenantId();
        $userId   = (int) apiUser()['user_id'];
        Database::execute(
            "UPDATE notifications
                SET acknowledged_at = CURRENT_TIMESTAMP,
                    is_read = 1,
                    read_at = COALESCE(read_at, CURRENT_TIMESTAMP)
              WHERE id = ? AND user_id = ? AND tenant_id = ? AND ack_required = 1",
            [$id, $userId, $tenantId]
        );
        jsonResponse(['success' => true]);
    }

    /** POST /api/notifications/read-all */
    public function markAllRead(): void {
        $tenantId = apiTenantId();
        $userId   = (int) apiUser()['user_id'];
        Database::execute(
            "UPDATE notifications
                SET is_read = 1, read_at = CURRENT_TIMESTAMP
              WHERE user_id = ? AND tenant_id = ? AND is_read = 0",
            [$userId, $tenantId]
        );
        jsonResponse(['success' => true]);
    }
}
