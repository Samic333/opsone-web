<?php
/**
 * ComplianceController — full crew compliance report
 *
 * Accessible by: airline_admin, hr, chief_pilot, safety_officer, super_admin
 */
class ComplianceController {

    public function index(): void {
        RbacMiddleware::requireRole(['airline_admin', 'hr', 'chief_pilot', 'safety_officer', 'super_admin']);

        $tenantId = currentTenantId();

        $summary           = CrewProfileModel::complianceSummary($tenantId);
        $expiredLicenses   = CrewProfileModel::expiredLicenses($tenantId);
        $expiringLicenses  = CrewProfileModel::expiringLicenses($tenantId, 90);
        $expiringMedicals  = CrewProfileModel::expiringMedicals($tenantId, 90);
        $expiringPassports = CrewProfileModel::expiringPassports($tenantId, 180);

        $pageTitle    = 'Crew Compliance';
        $pageSubtitle = 'Licence, Medical & Passport Status';

        ob_start();
        require VIEWS_PATH . '/compliance/index.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }
}
