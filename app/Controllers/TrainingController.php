<?php
/**
 * TrainingController — Phase 12 Training Management + Compliance Dashboard.
 */
class TrainingController {

    private function requireAdmin(): void {
        RbacMiddleware::requireRole(['super_admin','airline_admin','hr','training_admin','chief_pilot','head_cabin_crew']);
    }

    // ─── Admin dashboard ──────────────────────────────────────

    public function dashboard(): void {
        $this->requireAdmin();
        $tenantId = (int)currentTenantId();

        $today     = dbToday();
        $in30Days  = dbDatePlusDays(30);
        $in60Days  = dbDatePlusDays(60);
        $minus14   = dbDatePlusDays(-14);

        $summary = [
            'total_records' => (int)(Database::fetch("SELECT COUNT(*) c FROM training_records WHERE tenant_id = ?", [$tenantId])['c'] ?? 0),
            'expiring_30d'  => (int)(Database::fetch(
                "SELECT COUNT(*) c FROM training_records
                  WHERE tenant_id = ? AND expires_date BETWEEN $today AND $in30Days", [$tenantId])['c'] ?? 0),
            'expired'       => (int)(Database::fetch(
                "SELECT COUNT(*) c FROM training_records
                  WHERE tenant_id = ? AND expires_date < $today", [$tenantId])['c'] ?? 0),
            'in_progress'   => (int)(Database::fetch(
                "SELECT COUNT(*) c FROM training_records WHERE tenant_id = ? AND result = 'in_progress'", [$tenantId])['c'] ?? 0),
        ];

        $expiring = Database::fetchAll(
            "SELECT tr.*, u.name AS user_name, u.employee_id, tt.name AS type_name
               FROM training_records tr
               JOIN users u ON tr.user_id = u.id
               LEFT JOIN training_types tt ON tr.training_type_id = tt.id
              WHERE tr.tenant_id = ?
                AND tr.expires_date BETWEEN $minus14 AND $in60Days
              ORDER BY tr.expires_date ASC",
            [$tenantId]
        );

        $pageTitle    = 'Training Dashboard';
        $pageSubtitle = 'Compliance readiness and upcoming expiries';

        ob_start();
        require VIEWS_PATH . '/training/dashboard.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function addRecord(): void {
        $this->requireAdmin();
        if (!verifyCsrf()) { flash('error','Invalid.'); redirect('/training'); }
        $tenantId = (int)currentTenantId();
        $typeId = (int)($_POST['training_type_id'] ?? 0) ?: null;
        $completed = $_POST['completed_date'] ?? date('Y-m-d');
        $expires = $_POST['expires_date'] ?? null;

        // If type has validity_months and no explicit expiry provided, compute it.
        if (!$expires && $typeId) {
            $t = Database::fetch("SELECT validity_months FROM training_types WHERE id = ?", [$typeId]);
            if ($t && !empty($t['validity_months'])) {
                $expires = date('Y-m-d', strtotime($completed . ' +' . (int)$t['validity_months'] . ' months'));
            }
        }

        Database::insert(
            "INSERT INTO training_records
                (tenant_id, user_id, training_type_id, type_code, completed_date, expires_date,
                 provider, result, notes)
             VALUES (?,?,?,?,?,?,?,?,?)",
            [
                $tenantId, (int)$_POST['user_id'],
                $typeId, trim($_POST['type_code'] ?? ''),
                $completed, $expires ?: null,
                trim($_POST['provider'] ?? ''),
                $_POST['result'] ?? 'pass',
                trim($_POST['notes'] ?? ''),
            ]
        );
        AuditLog::log('training_record_added', 'training_record', 0,
            "User {$_POST['user_id']} — " . ($_POST['type_code'] ?? 'training'));
        flash('success', 'Training record added.');
        redirect('/training');
    }

    public function addType(): void {
        $this->requireAdmin();
        if (!verifyCsrf()) { flash('error','Invalid.'); redirect('/training'); }
        $tenantId = (int)currentTenantId();
        Database::insert(
            "INSERT INTO training_types (tenant_id, code, name, validity_months, applicable_roles)
             VALUES (?,?,?,?,?)",
            [
                $tenantId,
                strtolower(trim($_POST['code'] ?? '')),
                trim($_POST['name'] ?? ''),
                (int)($_POST['validity_months'] ?? 0) ?: null,
                trim($_POST['applicable_roles'] ?? ''),
            ]
        );
        flash('success','Training type added.');
        redirect('/training');
    }

    // ─── Crew self-service ────────────────────────────────────

    public function myTraining(): void {
        requireAuth();
        $tenantId = (int)currentTenantId();
        $userId   = (int)currentUser()['id'];

        $records = Database::fetchAll(
            "SELECT tr.*, tt.name AS type_name
               FROM training_records tr
               LEFT JOIN training_types tt ON tr.training_type_id = tt.id
              WHERE tr.tenant_id = ? AND tr.user_id = ?
              ORDER BY COALESCE(tr.expires_date, '9999-12-31') ASC, tr.completed_date DESC",
            [$tenantId, $userId]
        );

        $pageTitle    = 'My Training';
        $pageSubtitle = 'Records, expiries, and upcoming due items';

        ob_start();
        require VIEWS_PATH . '/training/my_training.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }
}
