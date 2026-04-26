<?php
/**
 * PerDiemController — Phase 11 Per Diem Management.
 * Rates managed by finance/admin. Claims submitted by crew, approved by finance.
 */
class PerDiemController {

    private function requireFinance(): void {
        RbacMiddleware::requireRole(['super_admin','airline_admin','hr']);
    }

    // ─── Rate table (admin) ────────────────────────────────────

    public function rates(): void {
        $this->requireFinance();
        $tenantId = (int)currentTenantId();
        $rates = Database::fetchAll(
            "SELECT * FROM per_diem_rates WHERE tenant_id = ? ORDER BY country, COALESCE(station,''), effective_from DESC",
            [$tenantId]
        );

        $pageTitle    = 'Per Diem Rates';
        $pageSubtitle = 'Country / station daily rates';

        ob_start();
        require VIEWS_PATH . '/perdiem/rates.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function addRate(): void {
        $this->requireFinance();
        if (!verifyCsrf()) { flash('error','Invalid.'); redirect('/per-diem/rates'); }
        $tenantId = (int)currentTenantId();
        Database::insert(
            "INSERT INTO per_diem_rates (tenant_id, country, station, currency, daily_rate, effective_from, effective_to, notes)
             VALUES (?,?,?,?,?,?,?,?)",
            [
                $tenantId, trim($_POST['country'] ?? ''),
                trim($_POST['station'] ?? '') ?: null,
                strtoupper(trim($_POST['currency'] ?? 'USD')),
                (float)($_POST['daily_rate'] ?? 0),
                $_POST['effective_from'] ?: date('Y-m-d'),
                $_POST['effective_to'] ?: null,
                trim($_POST['notes'] ?? ''),
            ]
        );
        AuditLog::log('perdiem_rate_added', 'per_diem_rate', 0, "{$_POST['country']} {$_POST['currency']} {$_POST['daily_rate']}");
        flash('success','Rate added.');
        redirect('/per-diem/rates');
    }

    // ─── Claims (admin review) ─────────────────────────────────

    public function claimsIndex(): void {
        $this->requireFinance();
        $tenantId = (int)currentTenantId();
        $status = $_GET['status'] ?? 'submitted';

        $where = "pc.tenant_id = ?"; $params = [$tenantId];
        if ($status !== 'all') { $where .= " AND pc.status = ?"; $params[] = $status; }

        $claims = Database::fetchAll(
            "SELECT pc.*, u.name AS user_name, u.employee_id
               FROM per_diem_claims pc
               JOIN users u ON pc.user_id = u.id
              WHERE $where
              ORDER BY pc.created_at DESC",
            $params
        );

        $pageTitle    = 'Per Diem Claims';
        $pageSubtitle = 'Review, approve, reject, pay';

        ob_start();
        require VIEWS_PATH . '/perdiem/claims_admin.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    // Defence-in-depth: explicit guard at the route entry point so a future
    // refactor of reviewClaim() cannot silently drop the role check. The
    // delegated $this->requireFinance() inside reviewClaim() remains the
    // canonical guard; this is belt-and-braces.
    public function approveClaim(int $id): void { $this->requireFinance(); $this->reviewClaim($id, 'approved'); }
    public function rejectClaim(int $id): void  { $this->requireFinance(); $this->reviewClaim($id, 'rejected'); }
    public function payClaim(int $id): void     { $this->requireFinance(); $this->reviewClaim($id, 'paid'); }

    private function reviewClaim(int $id, string $decision): void {
        $this->requireFinance();
        if (!verifyCsrf()) { flash('error','Invalid.'); redirect('/per-diem/claims'); }
        if (!in_array($decision, ['approved','rejected','paid'], true)) {
            flash('error','Bad decision.'); redirect('/per-diem/claims');
        }
        $paidClause = $decision === 'paid' ? ', paid_at = CURRENT_TIMESTAMP' : '';
        Database::execute(
            "UPDATE per_diem_claims SET status = ?, reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP $paidClause
              WHERE id = ?",
            [$decision, (int)currentUser()['id'], $id]
        );
        AuditLog::log('perdiem_claim_'.$decision, 'per_diem_claim', $id, $decision);
        flash('success', "Claim #$id marked $decision.");
        redirect('/per-diem/claims');
    }

    // ─── Crew self-service ─────────────────────────────────────

    public function myClaims(): void {
        requireAuth();
        $tenantId = (int)currentTenantId();
        $userId   = (int)currentUser()['id'];
        $claims = Database::fetchAll(
            "SELECT * FROM per_diem_claims WHERE tenant_id = ? AND user_id = ? ORDER BY period_from DESC",
            [$tenantId, $userId]
        );

        $pageTitle    = 'My Per Diem';
        $pageSubtitle = 'Claims and payment status';

        ob_start();
        require VIEWS_PATH . '/perdiem/my_claims.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function showSubmit(): void {
        requireAuth();
        $tenantId = (int)currentTenantId();
        $today = dbToday();
        $rates = Database::fetchAll(
            "SELECT * FROM per_diem_rates WHERE tenant_id = ?
               AND (effective_to IS NULL OR effective_to >= $today)
              ORDER BY country, station",
            [$tenantId]
        );

        $pageTitle    = 'Submit Per Diem';
        $pageSubtitle = 'New claim';

        ob_start();
        require VIEWS_PATH . '/perdiem/submit.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function submitClaim(): void {
        requireAuth();
        if (!verifyCsrf()) { flash('error','Invalid.'); redirect('/my-per-diem/new'); }

        $tenantId = (int)currentTenantId();
        $userId   = (int)currentUser()['id'];
        $rateId   = (int)($_POST['rate_id'] ?? 0);
        $rate     = $rateId ? Database::fetch("SELECT * FROM per_diem_rates WHERE id = ?", [$rateId]) : null;

        $days    = (float)($_POST['days'] ?? 0);
        $dayRate = $rate ? (float)$rate['daily_rate'] : (float)($_POST['rate'] ?? 0);
        $curr    = $rate ? $rate['currency']    : strtoupper($_POST['currency'] ?? 'USD');
        $amount  = round($days * $dayRate, 2);

        Database::insert(
            "INSERT INTO per_diem_claims
                (tenant_id, user_id, period_from, period_to, station, country, days, rate_id, rate, currency, amount, status, notes)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $tenantId, $userId,
                $_POST['period_from'] ?? date('Y-m-d'),
                $_POST['period_to']   ?? date('Y-m-d'),
                trim($_POST['station'] ?? '') ?: null,
                $rate ? $rate['country'] : trim($_POST['country'] ?? 'Unknown'),
                $days, $rateId ?: null, $dayRate, $curr, $amount,
                'submitted',
                trim($_POST['notes'] ?? ''),
            ]
        );
        AuditLog::log('perdiem_claim_submitted', 'per_diem_claim', 0, "$days days @ $dayRate $curr = $amount");
        flash('success','Claim submitted for review.');
        redirect('/my-per-diem');
    }
}
