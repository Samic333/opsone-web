<?php
/**
 * NotificationController — web-facing notification center.
 * Phase 5 — unified inbox with priority classification and ack support.
 */
class NotificationController {

    public function index(): void {
        requireAuth();
        $user      = currentUser();
        $userId    = (int)$user['id'];
        $tenantId  = (int)currentTenantId();

        $filter = $_GET['filter'] ?? 'all';  // all | unread | unack
        $where  = ["user_id = ?", "tenant_id = ?"];
        $params = [$userId, $tenantId];

        if ($filter === 'unread')  { $where[] = "is_read = 0"; }
        if ($filter === 'unack')   { $where[] = "ack_required = 1 AND acknowledged_at IS NULL"; }

        $notifications = Database::fetchAll(
            "SELECT * FROM notifications WHERE " . implode(' AND ', $where) .
            " ORDER BY created_at DESC LIMIT 200",
            $params
        );

        $counts = [
            'total'  => (int)(Database::fetch("SELECT COUNT(*) c FROM notifications WHERE user_id=? AND tenant_id=?", [$userId, $tenantId])['c'] ?? 0),
            'unread' => (int)(Database::fetch("SELECT COUNT(*) c FROM notifications WHERE user_id=? AND tenant_id=? AND is_read=0", [$userId, $tenantId])['c'] ?? 0),
            'unack'  => (int)(Database::fetch("SELECT COUNT(*) c FROM notifications WHERE user_id=? AND tenant_id=? AND ack_required=1 AND acknowledged_at IS NULL", [$userId, $tenantId])['c'] ?? 0),
        ];

        $pageTitle    = 'Notifications';
        $pageSubtitle = 'Your notification inbox';

        ob_start();
        require VIEWS_PATH . '/notifications/index.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    /**
     * Opening a notification: mark read, then redirect to its link.
     * Dead-link guard: if the link is empty, stay on the inbox.
     */
    public function open(int $id): void {
        requireAuth();
        $userId   = (int) currentUser()['id'];
        $tenantId = (int) currentTenantId();

        $row = Database::fetch(
            "SELECT * FROM notifications WHERE id = ? AND user_id = ? AND tenant_id = ?",
            [$id, $userId, $tenantId]
        );
        if (!$row) redirect('/notifications');

        Database::execute(
            "UPDATE notifications SET is_read = 1, read_at = CURRENT_TIMESTAMP
              WHERE id = ? AND is_read = 0",
            [$id]
        );

        $link = $row['link'] ?: '/notifications';
        redirect($link);
    }

    public function markRead(int $id): void {
        requireAuth();
        if (!verifyCsrf()) { flash('error', 'Invalid request.'); redirect('/notifications'); }
        $userId   = (int) currentUser()['id'];
        $tenantId = (int) currentTenantId();
        Database::execute(
            "UPDATE notifications SET is_read = 1, read_at = CURRENT_TIMESTAMP
              WHERE id = ? AND user_id = ? AND tenant_id = ?",
            [$id, $userId, $tenantId]
        );
        redirect('/notifications');
    }

    public function markAllRead(): void {
        requireAuth();
        if (!verifyCsrf()) { flash('error', 'Invalid request.'); redirect('/notifications'); }
        $userId   = (int) currentUser()['id'];
        $tenantId = (int) currentTenantId();
        Database::execute(
            "UPDATE notifications SET is_read = 1, read_at = CURRENT_TIMESTAMP
              WHERE user_id = ? AND tenant_id = ? AND is_read = 0",
            [$userId, $tenantId]
        );
        flash('success', 'All notifications marked as read.');
        redirect('/notifications');
    }

    public function acknowledge(int $id): void {
        requireAuth();
        if (!verifyCsrf()) { flash('error', 'Invalid request.'); redirect('/notifications'); }
        $userId   = (int) currentUser()['id'];
        $tenantId = (int) currentTenantId();
        Database::execute(
            "UPDATE notifications
                SET is_read = 1, read_at = COALESCE(read_at, CURRENT_TIMESTAMP),
                    acknowledged_at = CURRENT_TIMESTAMP
              WHERE id = ? AND user_id = ? AND tenant_id = ? AND ack_required = 1",
            [$id, $userId, $tenantId]
        );
        redirect('/notifications');
    }

    /** JSON endpoint — bell badge count. */
    public function unreadCount(): void {
        requireAuth();
        $userId   = (int) currentUser()['id'];
        $tenantId = (int) currentTenantId();
        $row = Database::fetch(
            "SELECT
                 SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END)                                            AS unread,
                 SUM(CASE WHEN ack_required = 1 AND acknowledged_at IS NULL THEN 1 ELSE 0 END)           AS unack,
                 SUM(CASE WHEN priority IN ('important','critical') AND is_read = 0 THEN 1 ELSE 0 END)   AS loud
               FROM notifications
              WHERE user_id = ? AND tenant_id = ?",
            [$userId, $tenantId]
        );
        jsonResponse([
            'unread' => (int)($row['unread'] ?? 0),
            'unack'  => (int)($row['unack']  ?? 0),
            'loud'   => (int)($row['loud']   ?? 0),
        ]);
    }
}
