<?php
/**
 * InstallAccessMiddleware — Protects install page access
 * Only authenticated users with active status can access install resources.
 */
class InstallAccessMiddleware {
    public function handle(): void {
        if (!isset($_SESSION['user'])) {
            flash('error', 'You must be logged in to access installation resources.');
            redirect('/login');
        }

        $user = $_SESSION['user'];
        if ($user['status'] !== 'active') {
            flash('error', 'Your account must be active to access installation resources.');
            redirect('/');
        }

        // Verify user has mobile_access enabled (unless Super Admin)
        $isSuperAdmin = in_array('super_admin', $_SESSION['user_roles'] ?? []);
        if (empty($user['mobile_access']) && !$isSuperAdmin) {
            flash('error', 'Your account does not have mobile app access enabled. Contact your administrator.');
            redirect('/');
        }
    }
}
