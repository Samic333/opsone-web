<?php
/**
 * AppraisalController — Phase 13 Crew Appraisal / Performance.
 */
class AppraisalController {

    /** Appraiser/scheduler/hr dashboard: list my appraisals + mine to do. */
    public function index(): void {
        requireAuth();
        $tenantId = (int)currentTenantId();
        $userId   = (int)currentUser()['id'];

        // Appraisals I've written
        $mine = Database::fetchAll(
            "SELECT a.*, us.name AS subject_name
               FROM appraisals a JOIN users us ON a.subject_id = us.id
              WHERE a.tenant_id = ? AND a.appraiser_id = ?
              ORDER BY a.period_to DESC",
            [$tenantId, $userId]
        );

        // Appraisals about me (visible to HR and subject when accepted)
        $aboutMe = Database::fetchAll(
            "SELECT a.*, ua.name AS appraiser_name
               FROM appraisals a JOIN users ua ON a.appraiser_id = ua.id
              WHERE a.tenant_id = ? AND a.subject_id = ?
                AND (a.status = 'accepted' OR a.confidential = 0)
              ORDER BY a.period_to DESC",
            [$tenantId, $userId]
        );

        // If HR / admin, show pending for review
        $pending = [];
        if (hasAnyRole(['super_admin','airline_admin','hr','chief_pilot','head_cabin_crew'])) {
            $pending = Database::fetchAll(
                "SELECT a.*, us.name AS subject_name, ua.name AS appraiser_name
                   FROM appraisals a
                   JOIN users us ON a.subject_id = us.id
                   JOIN users ua ON a.appraiser_id = ua.id
                  WHERE a.tenant_id = ? AND a.status = 'submitted'
                  ORDER BY a.submitted_at DESC",
                [$tenantId]
            );
        }

        $pageTitle    = 'Appraisals';
        $pageSubtitle = 'Post-rotation performance records';

        ob_start();
        require VIEWS_PATH . '/appraisals/index.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function showCreate(): void {
        requireAuth();
        $tenantId = (int)currentTenantId();
        $me = (int)currentUser()['id'];

        // Appraisable subjects: for now any crew in same tenant, minus self
        $subjects = Database::fetchAll(
            "SELECT id, name FROM users WHERE tenant_id = ? AND id != ? AND status = 'active' ORDER BY name",
            [$tenantId, $me]
        );

        $pageTitle = 'New Appraisal';
        ob_start();
        require VIEWS_PATH . '/appraisals/create.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function store(): void {
        requireAuth();
        if (!verifyCsrf()) { flash('error','Invalid.'); redirect('/appraisals/new'); }

        $tenantId = (int)currentTenantId();
        $me       = (int)currentUser()['id'];
        $subject  = (int)($_POST['subject_id'] ?? 0);
        if ($subject === 0 || $subject === $me) { flash('error','Select a different crew member.'); redirect('/appraisals/new'); }

        Database::insert(
            "INSERT INTO appraisals
                (tenant_id, subject_id, appraiser_id, rotation_ref, period_from, period_to,
                 status, rating_overall, strengths, improvements, comments, confidential)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $tenantId, $subject, $me,
                trim($_POST['rotation_ref'] ?? ''),
                $_POST['period_from'] ?: date('Y-m-d'),
                $_POST['period_to']   ?: date('Y-m-d'),
                $_POST['status'] ?? 'submitted',
                (int)($_POST['rating_overall'] ?? 0) ?: null,
                trim($_POST['strengths'] ?? ''),
                trim($_POST['improvements'] ?? ''),
                trim($_POST['comments'] ?? ''),
                isset($_POST['confidential']) ? 1 : 0,
            ]
        );
        AuditLog::log('appraisal_created', 'appraisal', 0, "Subject $subject rotation " . ($_POST['rotation_ref'] ?? ''));
        flash('success','Appraisal saved.');
        redirect('/appraisals');
    }

    public function accept(int $id): void {
        if (!verifyCsrf()) { flash('error','Invalid.'); redirect('/appraisals'); }
        RbacMiddleware::requireRole(['super_admin','airline_admin','hr','chief_pilot','head_cabin_crew']);
        Database::execute(
            "UPDATE appraisals SET status = 'accepted', reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP WHERE id = ?",
            [(int)currentUser()['id'], $id]
        );
        AuditLog::log('appraisal_accepted', 'appraisal', $id, 'accepted');
        flash('success','Appraisal accepted.');
        redirect('/appraisals');
    }
}
