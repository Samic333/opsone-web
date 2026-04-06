<?php
/**
 * DashboardController — role-aware dashboards
 */
class DashboardController {
    public function index(): void {
        $tenantId = currentTenantId();
        
        if (hasRole('super_admin') && isMultiTenant()) {
            $this->superAdminDashboard();
        } elseif (hasAnyRole(['airline_admin', 'hr'])) {
            $this->airlineAdminDashboard($tenantId);
        } elseif (hasRole('scheduler')) {
            $this->schedulerDashboard($tenantId);
        } elseif (hasAnyRole(['pilot', 'cabin_crew'])) {
            $this->pilotDashboard($tenantId);
        } else {
            $this->airlineAdminDashboard($tenantId);
        }
    }

    private function superAdminDashboard(): void {
        $data = [
            'total_airlines' => Tenant::countAll(),
            'active_airlines' => Tenant::countActive(),
            'total_users' => (int) Database::fetch("SELECT COUNT(*) as c FROM users")['c'],
            'pending_devices' => (int) Database::fetch("SELECT COUNT(*) as c FROM devices WHERE approval_status = 'pending'")['c'],
            'recent_activity' => AuditLog::all(null, 10),
            'tenants' => Tenant::all(),
        ];
        require VIEWS_PATH . '/dashboard/super_admin.php';
    }

    private function airlineAdminDashboard(int $tenantId): void {
        $data = [
            'active_staff' => UserModel::countByTenant($tenantId, 'active'),
            'pending_users' => UserModel::countByTenant($tenantId, 'pending'),
            'pending_devices' => Device::countPending($tenantId),
            'total_files' => FileModel::countByTenant($tenantId),
            'recent_uploads' => FileModel::recentUploads($tenantId, 5),
            'recent_logins' => UserModel::recentLogins($tenantId, 8),
            'users_by_role' => UserModel::countByRole($tenantId),
            'users_by_status' => UserModel::countByStatus($tenantId),
            'device_stats' => Device::countByStatus($tenantId),
            'recent_activity' => AuditLog::recent($tenantId, 10),
        ];

        $isHr = hasRole('hr') && !hasRole('airline_admin') && !hasRole('super_admin');
        if ($isHr) {
            require VIEWS_PATH . '/dashboard/hr.php';
        } else {
            require VIEWS_PATH . '/dashboard/airline_admin.php';
        }
    }

    private function schedulerDashboard(int $tenantId): void {
        $data = [
            'active_staff' => UserModel::countByTenant($tenantId, 'active'),
        ];
        require VIEWS_PATH . '/dashboard/scheduler.php';
    }

    private function pilotDashboard(int $tenantId): void {
        $user = currentUser();
        $data = [
            'recent_notices' => NoticeModel::recent($tenantId, 5),
            'sync_status' => Device::getLatestSync($user['id']),
            'last_login' => $user['last_login'] ?? 'Never',
        ];
        require VIEWS_PATH . '/dashboard/pilot.php';
    }
}
