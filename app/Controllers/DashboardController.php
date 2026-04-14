<?php
/**
 * DashboardController — role-aware dashboard routing
 *
 * Routing priority (first match wins):
 *   super_admin (multi-tenant)         → Platform overview
 *   platform_support/security/monitor  → Platform overview (read-only feel)
 *   airline_admin, hr                  → Airline admin / HR dashboard
 *   chief_pilot                        → Chief Pilot dashboard
 *   head_cabin_crew                    → Head of Cabin Crew dashboard
 *   engineering_manager                → Engineering Manager dashboard
 *   safety_officer                     → Safety Manager dashboard
 *   fdm_analyst                        → FDM Analyst dashboard
 *   document_control                   → Document Control dashboard
 *   base_manager                       → Base Manager dashboard
 *   scheduler                          → Scheduler dashboard
 *   pilot, cabin_crew                  → Crew (pilot) dashboard
 *   engineer                           → Engineer dashboard
 *   training_admin                     → Training Admin dashboard
 *   (fallback)                         → Airline admin dashboard
 */
class DashboardController {
    public function index(): void {
        $sessionUser = currentUser();
        $tenantId    = ($sessionUser['tenant_id'] ?? null) ?? currentTenantId();

        if (hasRole('super_admin') && isMultiTenant()) {
            $this->superAdminDashboard();

        } elseif (hasAnyRole(['platform_support', 'platform_security', 'system_monitoring']) && isMultiTenant()) {
            $this->platformSupportDashboard();

        } elseif (hasAnyRole(['airline_admin', 'hr'])) {
            $this->airlineAdminDashboard($tenantId);

        } elseif (hasRole('chief_pilot')) {
            $this->chiefPilotDashboard($tenantId);

        } elseif (hasRole('head_cabin_crew')) {
            $this->headCabinCrewDashboard($tenantId);

        } elseif (hasRole('engineering_manager')) {
            $this->engineeringDashboard($tenantId);

        } elseif (hasRole('safety_officer')) {
            $this->safetyDashboard($tenantId);

        } elseif (hasRole('fdm_analyst')) {
            $this->fdmDashboard($tenantId);

        } elseif (hasRole('document_control')) {
            $this->documentControlDashboard($tenantId);

        } elseif (hasRole('base_manager')) {
            $this->baseManagerDashboard($tenantId);

        } elseif (hasRole('scheduler')) {
            $this->schedulerDashboard($tenantId);

        } elseif (hasAnyRole(['pilot', 'cabin_crew'])) {
            $this->pilotDashboard($tenantId);

        } elseif (hasRole('engineer')) {
            $this->engineerDashboard($tenantId);

        } elseif (hasRole('training_admin')) {
            $this->trainingDashboard($tenantId);

        } else {
            $this->airlineAdminDashboard($tenantId);
        }
    }

    // ─── Platform dashboards ──────────────────────────────

    private function superAdminDashboard(): void {
        $data = [
            'total_airlines'   => Tenant::countAll(),
            'active_airlines'  => Tenant::countActive(),
            'total_users'      => (int) Database::fetch("SELECT COUNT(*) as c FROM users")['c'],
            'pending_devices'  => (int) Database::fetch("SELECT COUNT(*) as c FROM devices WHERE approval_status = 'pending'")['c'],
            'recent_activity'  => AuditLog::all(null, 10),
            'tenants'          => Tenant::all(),
        ];
        require VIEWS_PATH . '/dashboard/super_admin.php';
    }

    private function platformSupportDashboard(): void {
        // Read-only platform view — same data, different context label
        $data = [
            'total_airlines'   => Tenant::countAll(),
            'active_airlines'  => Tenant::countActive(),
            'total_users'      => (int) Database::fetch("SELECT COUNT(*) as c FROM users")['c'],
            'pending_devices'  => (int) Database::fetch("SELECT COUNT(*) as c FROM devices WHERE approval_status = 'pending'")['c'],
            'recent_activity'  => AuditLog::all(null, 10),
            'tenants'          => Tenant::all(),
        ];
        require VIEWS_PATH . '/dashboard/platform_support.php';
    }

    // ─── Airline-level dashboards ─────────────────────────

    private function airlineAdminDashboard(?int $tenantId): void {
        if (!$tenantId) { redirect('/dashboard'); }
        $compliance = CrewProfileModel::complianceSummary($tenantId);
        $data = [
            'active_staff'       => UserModel::countByTenant($tenantId, 'active'),
            'pending_users'      => UserModel::countByTenant($tenantId, 'pending'),
            'pending_devices'    => Device::countPending($tenantId),
            'total_files'        => FileModel::countByTenant($tenantId),
            'recent_uploads'     => FileModel::recentUploads($tenantId, 5),
            'recent_logins'      => UserModel::recentLogins($tenantId, 8),
            'users_by_role'      => UserModel::countByRole($tenantId),
            'users_by_status'    => UserModel::countByStatus($tenantId),
            'device_stats'       => Device::countByStatus($tenantId),
            'recent_activity'    => AuditLog::recent($tenantId, 10),
            'compliance'         => $compliance,
            'expiring_licenses'  => CrewProfileModel::expiringLicenses($tenantId, 90),
            'expiring_medicals'  => CrewProfileModel::expiringMedicals($tenantId, 90),
        ];

        $isHr = hasRole('hr') && !hasRole('airline_admin') && !hasRole('super_admin');
        require VIEWS_PATH . '/dashboard/' . ($isHr ? 'hr' : 'airline_admin') . '.php';
    }

    // ─── Management dashboards ────────────────────────────

    private function chiefPilotDashboard(?int $tenantId): void {
        if (!$tenantId) { redirect('/dashboard'); }
        $data = [
            'pilot_count'       => (int) Database::fetch(
                "SELECT COUNT(DISTINCT u.id) as c FROM users u
                 JOIN user_roles ur ON ur.user_id = u.id
                 JOIN roles r ON r.id = ur.role_id
                 WHERE u.tenant_id = ? AND r.slug = 'pilot' AND u.status = 'active'",
                [$tenantId]
            )['c'],
            'active_staff'      => UserModel::countByTenant($tenantId, 'active'),
            'total_files'       => FileModel::countByTenant($tenantId),
            'recent_notices'    => Notice::recent($tenantId, 5),
            'recent_activity'   => AuditLog::recent($tenantId, 6),
            'expiring_licenses' => CrewProfileModel::expiringLicenses($tenantId, 90),
            'expiring_medicals' => CrewProfileModel::expiringMedicals($tenantId, 90),
        ];
        require VIEWS_PATH . '/dashboard/chief_pilot.php';
    }

    private function headCabinCrewDashboard(?int $tenantId): void {
        if (!$tenantId) { redirect('/dashboard'); }
        $data = [
            'cabin_count'     => (int) Database::fetch(
                "SELECT COUNT(DISTINCT u.id) as c FROM users u
                 JOIN user_roles ur ON ur.user_id = u.id
                 JOIN roles r ON r.id = ur.role_id
                 WHERE u.tenant_id = ? AND r.slug = 'cabin_crew' AND u.status = 'active'",
                [$tenantId]
            )['c'],
            'active_staff'    => UserModel::countByTenant($tenantId, 'active'),
            'recent_notices'  => Notice::recent($tenantId, 5),
            'recent_activity' => AuditLog::recent($tenantId, 6),
        ];
        require VIEWS_PATH . '/dashboard/cabin_crew_mgmt.php';
    }

    private function engineeringDashboard(?int $tenantId): void {
        if (!$tenantId) { redirect('/dashboard'); }
        $data = [
            'eng_count'       => (int) Database::fetch(
                "SELECT COUNT(DISTINCT u.id) as c FROM users u
                 JOIN user_roles ur ON ur.user_id = u.id
                 JOIN roles r ON r.id = ur.role_id
                 WHERE u.tenant_id = ? AND r.slug = 'engineer' AND u.status = 'active'",
                [$tenantId]
            )['c'],
            'total_files'     => FileModel::countByTenant($tenantId),
            'recent_notices'  => Notice::recent($tenantId, 5),
            'recent_activity' => AuditLog::recent($tenantId, 6),
        ];
        require VIEWS_PATH . '/dashboard/engineering.php';
    }

    private function safetyDashboard(?int $tenantId): void {
        if (!$tenantId) { redirect('/dashboard'); }
        $fdmSummary = FdmModel::summary($tenantId);
        $compliance = CrewProfileModel::complianceSummary($tenantId);
        $data = [
            'active_staff'     => UserModel::countByTenant($tenantId, 'active'),
            'critical_notices' => (int) Database::fetch(
                "SELECT COUNT(*) as c FROM notices WHERE tenant_id = ? AND published = 1 AND priority IN ('critical','urgent')",
                [$tenantId]
            )['c'],
            'total_notices'    => (int) Database::fetch(
                "SELECT COUNT(*) as c FROM notices WHERE tenant_id = ? AND published = 1",
                [$tenantId]
            )['c'],
            'recent_notices'   => Notice::recent($tenantId, 5),
            'recent_activity'  => AuditLog::recent($tenantId, 8),
            'fdm_summary'      => $fdmSummary,
            'compliance'       => $compliance,
        ];
        require VIEWS_PATH . '/dashboard/safety.php';
    }

    private function fdmDashboard(?int $tenantId): void {
        if (!$tenantId) { redirect('/dashboard'); }
        $summary = FdmModel::summary($tenantId);
        $data = [
            'active_staff'    => UserModel::countByTenant($tenantId, 'active'),
            'recent_activity' => AuditLog::recent($tenantId, 8),
            'total_files'     => FileModel::countByTenant($tenantId),
            'fdm_summary'     => $summary,
            'recent_events'   => FdmModel::recentEvents($tenantId, 8),
        ];
        require VIEWS_PATH . '/dashboard/fdm.php';
    }

    private function documentControlDashboard(?int $tenantId): void {
        if (!$tenantId) { redirect('/dashboard'); }
        $data = [
            'total_files'     => FileModel::countByTenant($tenantId),
            'draft_files'     => (int) Database::fetch(
                "SELECT COUNT(*) as c FROM files WHERE tenant_id = ? AND status = 'draft'",
                [$tenantId]
            )['c'],
            'total_notices'   => (int) Database::fetch(
                "SELECT COUNT(*) as c FROM notices WHERE tenant_id = ? AND published = 1",
                [$tenantId]
            )['c'],
            'recent_uploads'  => FileModel::recentUploads($tenantId, 8),
        ];
        require VIEWS_PATH . '/dashboard/document_control.php';
    }

    private function baseManagerDashboard(?int $tenantId): void {
        if (!$tenantId) { redirect('/dashboard'); }
        $user = currentUser();
        $data = [
            'active_staff'    => UserModel::countByTenant($tenantId, 'active'),
            'pending_devices' => Device::countPending($tenantId),
            'recent_notices'  => Notice::recent($tenantId, 5),
            'recent_activity' => AuditLog::recent($tenantId, 6),
        ];
        require VIEWS_PATH . '/dashboard/base_manager.php';
    }

    private function schedulerDashboard(?int $tenantId): void {
        if (!$tenantId) { redirect('/dashboard'); }
        $today = date('Y-m-d');
        $year  = (int) date('Y');
        $month = (int) date('n');

        $todayDuties = Database::fetchAll(
            "SELECT r.*, u.name AS user_name, u.employee_id
             FROM rosters r
             JOIN users u ON u.id = r.user_id
             WHERE r.tenant_id = ? AND r.roster_date = ?
             ORDER BY u.name",
            [$tenantId, $today]
        );

        $complianceIssues  = RosterModel::getComplianceIssues($tenantId);
        $standbyToday      = RosterModel::getStandbyPool($tenantId, $today);
        $complianceFlagged = array_filter($complianceIssues, fn($c) => $c['severity'] === 'critical');

        // Enrich compliance-flagged entries with user info for dashboard display
        $flaggedCrew = [];
        if (!empty($complianceFlagged)) {
            $flaggedIds = array_keys($complianceFlagged);
            $placeholders = implode(',', array_fill(0, count($flaggedIds), '?'));
            $crewRows = Database::fetchAll(
                "SELECT u.id, u.name AS user_name, u.employee_id, ro.name AS role_name
                 FROM users u
                 JOIN user_roles ur ON ur.user_id = u.id
                 JOIN roles ro ON ro.id = ur.role_id
                 WHERE u.id IN ($placeholders) AND u.tenant_id = ? AND u.status = 'active'
                   AND ro.slug IN ('pilot','cabin_crew','engineer','chief_pilot','head_cabin_crew')
                 ORDER BY u.name",
                [...$flaggedIds, $tenantId]
            );
            foreach ($crewRows as $cr) {
                $cr['compliance'] = $complianceIssues[$cr['id']];
                $flaggedCrew[] = $cr;
            }
        }

        $data = [
            'active_staff'       => UserModel::countByTenant($tenantId, 'active'),
            'roster_count_month' => (int) Database::fetch(
                "SELECT COUNT(*) as c FROM rosters
                 WHERE tenant_id = ? AND roster_date BETWEEN ? AND ?",
                [$tenantId, "{$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01",
                            date('Y-m-t', mktime(0,0,0,$month,1,$year))]
            )['c'],
            'on_duty_today' => (int) Database::fetch(
                "SELECT COUNT(*) as c FROM rosters WHERE tenant_id = ? AND roster_date = ? AND duty_type NOT IN ('off','leave','rest')",
                [$tenantId, $today]
            )['c'],
            'on_leave_today' => (int) Database::fetch(
                "SELECT COUNT(*) as c FROM rosters WHERE tenant_id = ? AND roster_date = ? AND duty_type = 'leave'",
                [$tenantId, $today]
            )['c'],
            'on_standby_today'   => count($standbyToday),
            'today_duties'       => $todayDuties,
            'standby_today'      => $standbyToday,
            'flagged_crew'       => $flaggedCrew,
        ];
        require VIEWS_PATH . '/dashboard/scheduler.php';
    }

    // ─── Operational dashboards ───────────────────────────

    private function pilotDashboard(?int $tenantId): void {
        if (!$tenantId) { redirect('/dashboard'); }
        $user = currentUser();
        $data = [
            'recent_notices' => Notice::recent($tenantId, 5),
            'sync_status'    => Device::getLatestSync($user['id']),
            'last_login'     => $user['last_login'] ?? 'Never',
        ];
        require VIEWS_PATH . '/dashboard/pilot.php';
    }

    private function engineerDashboard(?int $tenantId): void {
        if (!$tenantId) { redirect('/dashboard'); }
        $user = currentUser();
        $data = [
            'recent_notices' => Notice::recent($tenantId, 5),
            'sync_status'    => Device::getLatestSync($user['id']),
            'total_files'    => FileModel::countByTenant($tenantId),
        ];
        require VIEWS_PATH . '/dashboard/engineer.php';
    }

    private function trainingDashboard(?int $tenantId): void {
        if (!$tenantId) { redirect('/dashboard'); }
        $data = [
            'active_staff'   => UserModel::countByTenant($tenantId, 'active'),
            'recent_notices' => Notice::recent($tenantId, 5),
        ];
        require VIEWS_PATH . '/dashboard/training.php';
    }
}
