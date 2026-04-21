<?php
/**
 * EligibilityController — readiness / assignment-eligibility views.
 *
 * /personnel/eligibility          tenant-wide readiness list
 * /personnel/eligibility/{id}     single staff readiness detail
 */
class EligibilityController {

    private const VIEW_ROLES = ['airline_admin', 'hr', 'chief_pilot', 'head_cabin_crew',
                                'engineering_manager', 'safety_officer', 'scheduler',
                                'training_admin', 'base_manager', 'super_admin', 'fdm_analyst'];

    public function index(): void {
        RbacMiddleware::requireRole(self::VIEW_ROLES);
        $tenantId = currentTenantId();

        $filter = $_GET['status'] ?? null;   // eligible | warning | blocked
        $bulk   = EligibilityService::bulkForTenant($tenantId);
        $summary = ['eligible' => 0, 'warning' => 0, 'blocked' => 0, 'total' => count($bulk)];
        foreach ($bulk as $row) {
            $summary[$row['status']]++;
        }

        // Attach user info for display
        $userIds = array_keys($bulk);
        $users = [];
        if (!empty($userIds)) {
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            $rows = Database::fetchAll(
                "SELECT u.id, u.name, u.email, u.employee_id, u.status,
                        d.name AS department_name, b.name AS base_name,
                        (SELECT GROUP_CONCAT(r.name, ', ')
                         FROM user_roles ur JOIN roles r ON ur.role_id = r.id
                         WHERE ur.user_id = u.id LIMIT 3) AS role_names
                 FROM users u
                 LEFT JOIN departments d ON u.department_id = d.id
                 LEFT JOIN bases       b ON u.base_id = b.id
                 WHERE u.id IN ($placeholders)
                 ORDER BY u.name ASC",
                $userIds
            );
            foreach ($rows as $u) $users[(int) $u['id']] = $u;
        }

        $pageTitle    = 'Eligibility Status';
        $pageSubtitle = 'Assignment readiness overview';

        ob_start();
        require VIEWS_PATH . '/personnel/eligibility_index.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function show(int $userId): void {
        RbacMiddleware::requireRole(self::VIEW_ROLES);
        $user = UserModel::find($userId);
        if (!$user || (int) $user['tenant_id'] !== (int) currentTenantId()) {
            flash('error', 'Staff member not found.');
            redirect('/personnel/eligibility');
        }

        $eligibility = EligibilityService::computeForUser($userId);
        $required    = RoleRequiredDocumentModel::forRoles(
            array_column(UserModel::getRoles($userId), 'slug'),
            (int) $user['tenant_id']
        );
        $documents   = CrewDocumentModel::forUser($userId);
        $licenses    = CrewProfileModel::getLicenses($userId);

        $pageTitle    = e($user['name']) . ' — Eligibility';
        $pageSubtitle = 'Assignment readiness detail';

        ob_start();
        require VIEWS_PATH . '/personnel/eligibility_show.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }
}
