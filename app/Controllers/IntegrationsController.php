<?php
/**
 * IntegrationsController — Phase 16 registry.
 * Only surfaces enable/disable/status — connectors themselves are future work.
 */
class IntegrationsController {

    private function requireAdmin(): void {
        RbacMiddleware::requireRole(['super_admin','airline_admin']);
    }

    public function index(): void {
        $this->requireAdmin();
        $tenantId = (int)currentTenantId();
        $integrations = Database::fetchAll(
            "SELECT * FROM integrations WHERE tenant_id = ? ORDER BY display_name",
            [$tenantId]
        );

        $pageTitle    = 'Integrations';
        $pageSubtitle = 'External service connectors (advanced)';

        ob_start();
        require VIEWS_PATH . '/integrations/index.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function setStatus(int $id): void {
        $this->requireAdmin();
        if (!verifyCsrf()) { flash('error','Invalid.'); redirect('/integrations'); }
        $tenantId = (int)currentTenantId();
        $new = $_POST['status'] ?? 'disabled';
        if (!in_array($new, ['disabled','pending','live','error'], true)) {
            flash('error','Invalid status.'); redirect('/integrations');
        }
        Database::execute(
            "UPDATE integrations SET status = ?, updated_at = CURRENT_TIMESTAMP
              WHERE id = ? AND tenant_id = ?",
            [$new, $id, $tenantId]
        );
        AuditLog::log('integration_status', 'integration', $id, "Status → $new");
        flash('success',"Integration status updated to $new.");
        redirect('/integrations');
    }
}
