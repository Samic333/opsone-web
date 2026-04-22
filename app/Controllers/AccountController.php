<?php
/**
 * AccountController — lightweight self-service for every logged-in user.
 *
 * Covers the bits of "profile settings" that aren't tied to the crew record:
 *   - display name + email
 *   - preferred language / timezone (best-effort, only if columns exist)
 *   - optional avatar URL
 *
 * Deep crew data (licences, medical, passport, etc.) still lives in
 * /my-profile → CrewProfileController. This page is intentionally minimal so
 * platform staff who have no crew record still have somewhere to land.
 */
class AccountController {

    public function settings(): void {
        requireAuth();
        $me      = currentUser();
        $userId  = (int) $me['id'];
        $row     = UserModel::find($userId) ?? $me;

        // Column discovery (SQLite vs MySQL dev parity) — tolerate missing cols.
        $cols = self::userColumns();

        $data = [
            'user'   => $row,
            'has'    => $cols,
            'roles'  => UserModel::getRoles($userId) ?? [],
        ];

        $pageTitle    = 'Profile Settings';
        $pageSubtitle = 'Your account preferences';

        ob_start();
        require VIEWS_PATH . '/account/settings.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function update(): void {
        requireAuth();
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/account/settings');
        }

        $userId = (int) currentUser()['id'];
        $cols   = self::userColumns();

        // Always-safe: name + email
        $update = [
            'name'  => trim($_POST['name']  ?? ''),
            'email' => trim($_POST['email'] ?? ''),
        ];
        if ($update['name'] === '' || $update['email'] === '') {
            flash('error', 'Name and email are required.');
            redirect('/account/settings');
        }

        // Optional columns — only include them if the schema has them.
        $opt = ['phone', 'timezone', 'locale', 'avatar_url'];
        foreach ($opt as $c) {
            if (in_array($c, $cols, true) && array_key_exists($c, $_POST)) {
                $update[$c] = trim($_POST[$c]) ?: null;
            }
        }

        $set = [];
        $args = [];
        foreach ($update as $k => $v) {
            $set[]  = "$k = ?";
            $args[] = $v;
        }
        $args[] = $userId;

        try {
            Database::execute(
                'UPDATE users SET ' . implode(', ', $set) . ' WHERE id = ?',
                $args
            );
            // Keep session in sync so the header dropdown reflects the change.
            $_SESSION['user']['name']  = $update['name'];
            $_SESSION['user']['email'] = $update['email'];
            flash('success', 'Your settings have been saved.');
        } catch (\Throwable $e) {
            appLog('account.update_failed: ' . $e->getMessage(), 'error');
            flash('error', 'Could not save changes. Please try again.');
        }

        redirect('/account/settings');
    }

    /**
     * Return the column names of the `users` table, lowercased.
     */
    private static function userColumns(): array {
        static $cached = null;
        if ($cached !== null) return $cached;

        $driver = env('DB_DRIVER', 'mysql');
        try {
            if ($driver === 'sqlite') {
                $rows = Database::fetchAll("PRAGMA table_info('users')");
                $cached = array_map(fn($r) => strtolower($r['name']), $rows);
            } else {
                $rows = Database::fetchAll(
                    "SELECT COLUMN_NAME AS name FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'"
                );
                $cached = array_map(fn($r) => strtolower($r['name']), $rows);
            }
        } catch (\Throwable $e) {
            $cached = ['name','email'];
        }
        return $cached;
    }
}
