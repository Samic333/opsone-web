<?php
/**
 * HelpController — Phase 15 Help Hub.
 * Role-aware static content; trimmed to what each role actually uses.
 */
class HelpController {

    public function index(): void {
        requireAuth();
        $roles = $_SESSION['user_roles'] ?? [];

        // Topics relevant to every role:
        $topics = [
            ['Getting started',   '/help/topic?t=getting-started', '🏁'],
            ['Your profile',      '/help/topic?t=profile',         '👤'],
            ['Notifications',     '/help/topic?t=notifications',   '🔔'],
        ];

        // Role-specific additions
        if (array_intersect($roles, ['pilot','cabin_crew','engineer'])) {
            $topics[] = ['My roster',            '/help/topic?t=my-roster',    '🗓'];
            $topics[] = ['My flights & bag',     '/help/topic?t=my-flights',   '🛫'];
            $topics[] = ['Electronic logbook',   '/help/topic?t=logbook',      '📒'];
            $topics[] = ['Safety reporting',     '/help/topic?t=safety',       '🛡'];
            $topics[] = ['Duty reporting',       '/help/topic?t=duty',         '⏱'];
            $topics[] = ['Documents & ack',      '/help/topic?t=documents',    '📄'];
        }
        if (array_intersect($roles, ['scheduler','chief_pilot','head_cabin_crew','base_manager'])) {
            $topics[] = ['Scheduler workbench',  '/help/topic?t=scheduler',    '📆'];
            $topics[] = ['Flight assignment',    '/help/topic?t=flights',      '✈️'];
        }
        if (array_intersect($roles, ['document_control','airline_admin','super_admin'])) {
            $topics[] = ['Document controller',  '/help/topic?t=document-controller', '🗂'];
        }
        if (array_intersect($roles, ['hr','airline_admin','super_admin','training_admin'])) {
            $topics[] = ['HR workflow',          '/help/topic?t=hr',           '🧑‍💼'];
            $topics[] = ['Training management',  '/help/topic?t=training',     '🎓'];
        }
        if (array_intersect($roles, ['safety_officer','safety_manager','safety_staff'])) {
            $topics[] = ['Safety team dashboard','/help/topic?t=safety-team',  '🚨'];
        }
        if (array_intersect($roles, ['fdm_analyst','airline_admin','super_admin'])) {
            $topics[] = ['FDM uploads & events', '/help/topic?t=fdm',          '📈'];
        }

        $pageTitle    = 'Help & Guides';
        $pageSubtitle = 'Contextual help tailored to your role';

        ob_start();
        require VIEWS_PATH . '/help/index.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function topic(): void {
        requireAuth();
        $slug = preg_replace('/[^a-z0-9\-]/', '', $_GET['t'] ?? '');
        if ($slug === '') { redirect('/help'); }
        $file = VIEWS_PATH . "/help/topics/$slug.php";
        $title = ucwords(str_replace('-', ' ', $slug));

        if (!file_exists($file)) {
            flash('error', 'Help topic not found.');
            redirect('/help');
        }

        $pageTitle    = "Help — $title";
        $pageSubtitle = '';

        ob_start();
        require $file;
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }
}
