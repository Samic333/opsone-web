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
        $pendingNoticeAcks = Database::fetchAll(
            "SELECT n.id, n.title, n.priority, n.category, n.published_at, n.created_at,
                    COUNT(DISTINCT u.id) AS total_required,
                    COUNT(DISTINCT CASE WHEN nr.acknowledged_at IS NOT NULL THEN nr.user_id END) AS total_acked,
                    COUNT(DISTINCT u.id) - COUNT(DISTINCT CASE WHEN nr.acknowledged_at IS NOT NULL THEN nr.user_id END) AS pending_count
             FROM notices n
             JOIN users u ON u.tenant_id = n.tenant_id AND u.status = 'active' AND u.mobile_access = 1
             LEFT JOIN notice_reads nr ON nr.notice_id = n.id AND nr.user_id = u.id
             WHERE n.tenant_id = ? AND n.requires_ack = 1 AND n.published = 1
               AND (n.expires_at IS NULL OR n.expires_at > NOW())
             GROUP BY n.id
             HAVING pending_count > 0
             ORDER BY n.priority DESC, pending_count DESC",
            [$tenantId]
        );

        $pageTitle    = 'Crew Compliance';
        $pageSubtitle = 'Licence, Medical, Passport & Document Status';

        ob_start();
        require VIEWS_PATH . '/compliance/index.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }
}
