<?php
/**
 * HelpApiController — Phase 15 Help Hub (mobile).
 *
 * Returns role-aware topic list and topic bodies. Topic bodies come from the
 * same static help files used by the web controller (views/help/topics/*).
 *
 * Routes:
 *   GET /api/help/topics            — role-aware topic list
 *   GET /api/help/topic?slug=...    — single topic body (markdown/html)
 */
class HelpApiController {

    /** GET /api/help/topics */
    public function topics(): void {
        $roles = apiUserRoles();

        $topics = [
            ['slug' => 'getting-started', 'title' => 'Getting started',    'icon' => '🏁'],
            ['slug' => 'profile',         'title' => 'Your profile',      'icon' => '👤'],
            ['slug' => 'notifications',   'title' => 'Notifications',     'icon' => '🔔'],
        ];

        if (array_intersect($roles, ['pilot','cabin_crew','engineer'])) {
            $topics[] = ['slug' => 'my-roster',   'title' => 'My roster',          'icon' => '🗓'];
            $topics[] = ['slug' => 'my-flights',  'title' => 'My flights & bag',   'icon' => '🛫'];
            $topics[] = ['slug' => 'logbook',     'title' => 'Electronic logbook', 'icon' => '📒'];
            $topics[] = ['slug' => 'safety',      'title' => 'Safety reporting',   'icon' => '🛡'];
            $topics[] = ['slug' => 'duty',        'title' => 'Duty reporting',     'icon' => '⏱'];
            $topics[] = ['slug' => 'documents',   'title' => 'Documents & ack',    'icon' => '📄'];
            $topics[] = ['slug' => 'per-diem',    'title' => 'Per diem claims',    'icon' => '💰'];
            $topics[] = ['slug' => 'training',    'title' => 'Training records',   'icon' => '🎓'];
            $topics[] = ['slug' => 'fdm',         'title' => 'FDM events',         'icon' => '📊'];
        }
        if (array_intersect($roles, ['scheduler','chief_pilot','head_cabin_crew','base_manager'])) {
            $topics[] = ['slug' => 'scheduler', 'title' => 'Scheduler workbench', 'icon' => '📆'];
            $topics[] = ['slug' => 'flights',   'title' => 'Flight assignment',   'icon' => '✈️'];
        }
        if (array_intersect($roles, ['hr','airline_admin','super_admin','training_admin'])) {
            $topics[] = ['slug' => 'hr',       'title' => 'HR workflow',          'icon' => '🧑‍💼'];
            $topics[] = ['slug' => 'training', 'title' => 'Training management',  'icon' => '🎓'];
        }
        if (array_intersect($roles, ['safety_officer','safety_manager','safety_staff'])) {
            $topics[] = ['slug' => 'safety-team', 'title' => 'Safety team dashboard', 'icon' => '🚨'];
        }

        // Deduplicate while preserving order.
        $seen = [];
        $unique = [];
        foreach ($topics as $t) {
            if (isset($seen[$t['slug']])) continue;
            $seen[$t['slug']] = true;
            $unique[] = $t;
        }

        jsonResponse(['topics' => $unique]);
    }

    /** GET /api/help/topic?slug=... */
    public function topic(): void {
        $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower((string)($_GET['slug'] ?? '')));
        if ($slug === '') jsonResponse(['error' => 'slug required'], 422);

        $candidates = [
            VIEWS_PATH . "/help/topics/$slug.md",
            VIEWS_PATH . "/help/topics/$slug.html",
            VIEWS_PATH . "/help/topics/$slug.php",
        ];
        $file = null;
        foreach ($candidates as $c) { if (is_file($c)) { $file = $c; break; } }

        if (!$file) {
            jsonResponse([
                'slug'    => $slug,
                'title'   => ucfirst(str_replace('-', ' ', $slug)),
                'format'  => 'text',
                'body'    => 'This help topic is coming soon. Please check the full help center on the web portal for the latest guidance.',
            ]);
        }

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if ($ext === 'php') {
            ob_start();
            include $file;
            $body = (string) ob_get_clean();
            $format = 'html';
        } else {
            $body = (string) file_get_contents($file);
            $format = $ext === 'md' ? 'markdown' : 'html';
        }

        // Derive a title from the first markdown H1 or HTML <h1>.
        $title = ucfirst(str_replace('-', ' ', $slug));
        if ($format === 'markdown' && preg_match('/^#\s+(.+)$/m', $body, $m)) {
            $title = trim($m[1]);
        } elseif (preg_match('/<h1[^>]*>(.+?)<\/h1>/is', $body, $m)) {
            $title = trim(strip_tags($m[1]));
        }

        jsonResponse([
            'slug'   => $slug,
            'title'  => $title,
            'format' => $format,
            'body'   => $body,
        ]);
    }

    /**
     * POST /api/help/support-request
     *
     * Body: { "subject": string, "message": string, "category": string? }
     *
     * Files an in-app notification to every user in the tenant who can act
     * on it (airline_admin / chief_pilot / safety_officer).  Falls back to
     * super_admin if the tenant has none of those.
     */
    public function supportRequest(): void {
        $tenantId = apiTenantId();
        $me       = apiUser();
        $userId   = (int) $me['user_id'];

        $body    = json_decode(file_get_contents('php://input'), true) ?? [];
        $subject  = trim((string)($body['subject']  ?? ''));
        $message  = trim((string)($body['message']  ?? ''));
        $category = trim((string)($body['category'] ?? 'general'));
        if ($subject === '' || $message === '') {
            jsonResponse(['error' => 'subject and message are required'], 422);
        }

        // Resolve the requester's name for the notification body.
        $requester = Database::fetch(
            "SELECT name FROM users WHERE id = ? LIMIT 1",
            [$userId]
        );
        $requesterName = $requester['name'] ?? 'A crew member';

        // Find admin targets in this tenant.
        $admins = Database::fetchAll(
            "SELECT DISTINCT u.id
               FROM users u
               JOIN user_roles ur ON ur.user_id = u.id
               JOIN roles r       ON r.id       = ur.role_id
              WHERE u.tenant_id = ?
                AND u.status = 'active'
                AND r.slug IN ('airline_admin','chief_pilot','safety_officer')",
            [$tenantId]
        );
        if (empty($admins)) {
            $admins = Database::fetchAll(
                "SELECT DISTINCT u.id
                   FROM users u
                   JOIN user_roles ur ON ur.user_id = u.id
                   JOIN roles r       ON r.id       = ur.role_id
                  WHERE u.status = 'active' AND r.slug = 'super_admin'"
            );
        }

        $notifTitle = '[Support] ' . $subject;
        $notifBody  = $requesterName . ' ('. $category . "):\n\n" . $message;
        $sentTo = 0;
        foreach ($admins as $a) {
            $aid = (int)$a['id'];
            if ($aid === $userId) continue;
            try {
                NotificationService::notifyUser(
                    $aid, $notifTitle, $notifBody,
                    '/notifications', 'help_support_request',
                    'important', false
                );
                $sentTo++;
            } catch (\Throwable $e) {
                error_log('[HelpApiController] support notify error: ' . $e->getMessage());
            }
        }

        AuditLog::log('help_support_request', 'help', $userId, "$category — $subject");

        jsonResponse([
            'success' => true,
            'sent_to' => $sentTo,
            'message' => 'Support request received',
        ]);
    }
}
