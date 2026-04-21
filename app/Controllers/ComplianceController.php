<?php
/**
 * ComplianceController — full crew compliance report
 *
 * Accessible by: airline_admin, hr, chief_pilot, safety_officer, super_admin
 */
class ComplianceController {

    public function index(): void {
        RbacMiddleware::requireRole(['airline_admin', 'hr', 'chief_pilot', 'safety_officer', 'super_admin', 'fdm_analyst']);

        $tenantId = currentTenantId();

        $summary           = CrewProfileModel::complianceSummary($tenantId);
        $expiredLicenses   = CrewProfileModel::expiredLicenses($tenantId);
        $expiringLicenses  = CrewProfileModel::expiringLicenses($tenantId, 90);
        $expiringMedicals  = CrewProfileModel::expiringMedicals($tenantId, 90);
        $expiringPassports = CrewProfileModel::expiringPassports($tenantId, 180);

        // Documents pending acknowledgement by at least one active crew member
        $pendingAcks = Database::fetchAll(
            "SELECT f.id AS file_id, f.title, f.version, f.category_id,
                    fc.name AS category_name,
                    COUNT(DISTINCT u.id) AS total_required,
                    COUNT(DISTINCT fa.user_id) AS total_acked,
                    COUNT(DISTINCT u.id) - COUNT(DISTINCT fa.user_id) AS pending_count
             FROM files f
             LEFT JOIN file_categories fc ON fc.id = f.category_id
             JOIN users u ON u.tenant_id = f.tenant_id AND u.status = 'active' AND u.mobile_access = 1
             LEFT JOIN file_acknowledgements fa ON fa.file_id = f.id AND fa.user_id = u.id
             WHERE f.tenant_id = ? AND f.requires_ack = 1 AND f.status = 'published'
             GROUP BY f.id
             HAVING pending_count > 0
             ORDER BY pending_count DESC, f.title",
            [$tenantId]
        );

        // Notices requiring acknowledgement with outstanding crew sign-offs
        $now = dbNow();   // driver-agnostic (NOW() on MySQL, datetime('now') on SQLite)
        $pendingNoticeAcks = Database::fetchAll(
            "SELECT n.id, n.title, n.priority, n.category, n.published_at, n.created_at,
                    COUNT(DISTINCT u.id) AS total_required,
                    COUNT(DISTINCT CASE WHEN nr.acknowledged_at IS NOT NULL THEN nr.user_id END) AS total_acked,
                    COUNT(DISTINCT u.id) - COUNT(DISTINCT CASE WHEN nr.acknowledged_at IS NOT NULL THEN nr.user_id END) AS pending_count
             FROM notices n
             JOIN users u ON u.tenant_id = n.tenant_id AND u.status = 'active' AND u.mobile_access = 1
             LEFT JOIN notice_reads nr ON nr.notice_id = n.id AND nr.user_id = u.id
             WHERE n.tenant_id = ? AND n.requires_ack = 1 AND n.published = 1
               AND (n.expires_at IS NULL OR n.expires_at > $now)
             GROUP BY n.id
             HAVING pending_count > 0
             ORDER BY n.priority DESC, pending_count DESC",
            [$tenantId]
        );

        // ─── Phase 6: compliance extension widgets ─────────────────────────
        $pendingChangeRequests = ChangeRequestModel::pendingCount($tenantId);
        $pendingDocuments      = CrewDocumentModel::pendingApprovalCount($tenantId);
        $eligibilitySummary    = EligibilityService::tenantSummary($tenantId);
        $expiringDocuments     = CrewDocumentModel::expiringForTenant($tenantId, 60);
        $expiredDocuments      = CrewDocumentModel::expiredForTenant($tenantId, 50);
        $openAlerts            = ExpiryAlertModel::openForTenant($tenantId, 50);

        $pageTitle    = 'Crew Compliance';
        $pageSubtitle = 'Licence, Medical, Passport & Document Status';

        ob_start();
        require VIEWS_PATH . '/compliance/index.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    // ─── Phase 6: dedicated drill-downs ─────────────────────────────────────

    public function expiring(): void {
        RbacMiddleware::requireRole(['airline_admin', 'hr', 'chief_pilot', 'head_cabin_crew',
                                     'engineering_manager', 'safety_officer', 'training_admin',
                                     'base_manager', 'super_admin', 'fdm_analyst']);
        $tenantId = currentTenantId();

        $expiringLicenses  = CrewProfileModel::expiringLicenses($tenantId, 90);
        $expiringMedicals  = CrewProfileModel::expiringMedicals($tenantId, 90);
        $expiringPassports = CrewProfileModel::expiringPassports($tenantId, 180);
        $expiringDocuments = CrewDocumentModel::expiringForTenant($tenantId, 90);
        $expiringQuals     = QualificationModel::expiringForTenant($tenantId, 90);

        $pageTitle    = 'Expiring Compliance';
        $pageSubtitle = 'Items expiring within the next 90/180 days';

        ob_start();
        require VIEWS_PATH . '/compliance/expiring.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function missing(): void {
        RbacMiddleware::requireRole(['airline_admin', 'hr', 'chief_pilot', 'head_cabin_crew',
                                     'engineering_manager', 'training_admin', 'super_admin']);
        $tenantId = currentTenantId();

        // For each active staff, check role-required docs against current holdings
        $users = Database::fetchAll(
            "SELECT u.id, u.name, u.email, u.employee_id, u.status,
                    d.name AS department_name, b.name AS base_name,
                    (SELECT GROUP_CONCAT(r.slug, ',')
                     FROM user_roles ur JOIN roles r ON ur.role_id = r.id
                     WHERE ur.user_id = u.id) AS role_slugs,
                    (SELECT GROUP_CONCAT(r.name, ', ')
                     FROM user_roles ur JOIN roles r ON ur.role_id = r.id
                     WHERE ur.user_id = u.id) AS role_names
             FROM users u
             LEFT JOIN departments d ON u.department_id = d.id
             LEFT JOIN bases       b ON u.base_id = b.id
             WHERE u.tenant_id = ? AND u.status = 'active'
             ORDER BY u.name",
            [$tenantId]
        );

        $report = [];
        foreach ($users as $u) {
            $e = EligibilityService::computeForUser((int) $u['id']);
            $missing = $e['details']['missing_required'];
            if (!empty($missing)) {
                $u['missing']     = $missing;
                $u['eligibility'] = $e['status'];
                $report[] = $u;
            }
        }

        $pageTitle    = 'Missing Required Documents';
        $pageSubtitle = 'Staff without the documents required for their role(s)';

        ob_start();
        require VIEWS_PATH . '/compliance/missing.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function runAlertScan(): void {
        RbacMiddleware::requireRole(['airline_admin', 'hr', 'super_admin']);
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/compliance');
        }
        $counts = ExpiryAlertService::scanTenant(currentTenantId());
        AuditService::log('compliance.expiry_scan', null, null, $counts);
        flash('success', sprintf(
            'Expiry scan complete — %d warning, %d critical, %d expired items logged.',
            $counts['warnings'], $counts['critical'], $counts['expired']
        ));
        redirect('/compliance');
    }
}
