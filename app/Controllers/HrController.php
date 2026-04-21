<?php
/**
 * HrController — Phase 14 HR Workflow Hardening.
 * Consolidated lifecycle dashboard: onboarding, probation, contract expiries,
 * pending change requests, activation/deactivation actions.
 */
class HrController {

    private function requireHr(): void {
        RbacMiddleware::requireRole(['super_admin','airline_admin','hr']);
    }

    public function dashboard(): void {
        $this->requireHr();
        $tenantId = (int)currentTenantId();

        $onboarding = Database::fetchAll(
            "SELECT u.id, u.name, u.email, u.status, u.employment_status, u.created_at
               FROM users u
              WHERE u.tenant_id = ? AND u.employment_status IN ('onboarding','in_review')
              ORDER BY u.created_at DESC",
            [$tenantId]
        );

        $probation = Database::fetchAll(
            "SELECT u.id, u.name, u.employee_id, cp.contract_type, cp.contract_expiry, u.employment_status
               FROM users u
               LEFT JOIN crew_profiles cp ON cp.user_id = u.id
              WHERE u.tenant_id = ? AND u.employment_status = 'probation'
              ORDER BY cp.contract_expiry ASC",
            [$tenantId]
        );

        $today    = dbToday();
        $in90Days = dbDatePlusDays(90);
        $contractExpiring = Database::fetchAll(
            "SELECT u.id, u.name, u.employee_id, cp.contract_type, cp.contract_expiry
               FROM users u
               JOIN crew_profiles cp ON cp.user_id = u.id
              WHERE u.tenant_id = ? AND cp.contract_expiry IS NOT NULL
                AND cp.contract_expiry BETWEEN $today AND $in90Days
              ORDER BY cp.contract_expiry ASC",
            [$tenantId]
        );

        $pendingChangeRequests = 0;
        try {
            $pendingChangeRequests = (int)(Database::fetch(
                "SELECT COUNT(*) c FROM compliance_change_requests WHERE tenant_id = ? AND status = 'pending'",
                [$tenantId]
            )['c'] ?? 0);
        } catch (\Throwable $e) { /* table optional */ }

        $inactiveUsers = (int)(Database::fetch(
            "SELECT COUNT(*) c FROM users WHERE tenant_id = ? AND status = 'inactive'",
            [$tenantId]
        )['c'] ?? 0);

        $inactiveList = Database::fetchAll(
            "SELECT id, name, email, employee_id, employment_status FROM users
              WHERE tenant_id = ? AND status = 'inactive' ORDER BY name LIMIT 50",
            [$tenantId]
        );

        // Active users list (for deactivate action)
        $activeList = Database::fetchAll(
            "SELECT id, name, email, employee_id, employment_status FROM users
              WHERE tenant_id = ? AND status = 'active' ORDER BY name LIMIT 100",
            [$tenantId]
        );

        $pageTitle    = 'HR Workflow';
        $pageSubtitle = 'Lifecycle, contracts, and governance actions';

        ob_start();
        require VIEWS_PATH . '/hr/dashboard.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function setEmploymentStatus(int $userId): void {
        $this->requireHr();
        if (!verifyCsrf()) { flash('error','Invalid.'); redirect('/hr'); }
        $new = trim($_POST['employment_status'] ?? '');
        $allowed = ['onboarding','in_review','probation','active','terminated'];
        if (!in_array($new, $allowed, true)) {
            flash('error','Invalid employment status.'); redirect('/hr');
        }
        $tenantId = (int)currentTenantId();
        Database::execute(
            "UPDATE users SET employment_status = ? WHERE id = ? AND tenant_id = ?",
            [$new, $userId, $tenantId]
        );
        AuditLog::log('hr_employment_status', 'user', $userId, "Set to $new");
        flash('success', "Employment status updated to $new.");
        redirect('/hr');
    }

    public function deactivate(int $userId): void {
        $this->requireHr();
        if (!verifyCsrf()) { flash('error','Invalid.'); redirect('/hr'); }
        $tenantId = (int)currentTenantId();
        Database::execute(
            "UPDATE users SET status = 'inactive' WHERE id = ? AND tenant_id = ?",
            [$userId, $tenantId]
        );
        AuditLog::log('hr_user_deactivated', 'user', $userId, "User deactivated");
        flash('success', 'User deactivated.');
        redirect('/hr');
    }

    public function reactivate(int $userId): void {
        $this->requireHr();
        if (!verifyCsrf()) { flash('error','Invalid.'); redirect('/hr'); }
        $tenantId = (int)currentTenantId();
        Database::execute(
            "UPDATE users SET status = 'active' WHERE id = ? AND tenant_id = ?",
            [$userId, $tenantId]
        );
        AuditLog::log('hr_user_reactivated', 'user', $userId, "User reactivated");
        flash('success', 'User reactivated.');
        redirect('/hr');
    }
}
