<?php
/**
 * SafetyController — Phase 1 Safety Reporting
 *
 * Crew-facing routes:
 *   GET  /safety                        home()
 *   GET  /safety/select-type            selectType()
 *   GET  /safety/report/new/{type}      reportForm($type)
 *   POST /safety/report/draft           saveDraft()
 *   POST /safety/report/submit          submitReport()
 *   GET  /safety/drafts                 myDrafts()
 *   GET  /safety/my-reports             myReports()
 *   GET  /safety/report/{id}            reportDetail($id)
 *   POST /safety/report/{id}/reply      addReply($id)
 *   POST /safety/report/{id}/upload     uploadAttachment($id)
 *
 * Safety-team routes (safety_manager / safety_staff / airline_admin / super_admin):
 *   GET  /safety/queue                          index()
 *   GET  /safety/team/report/{id}               teamDetail($id)
 *   POST /safety/team/report/{id}/status        updateStatus($id)
 *   POST /safety/team/report/{id}/assign        assignReport($id)
 *   POST /safety/team/report/{id}/internal-note addInternalNote($id)
 *   POST /safety/team/report/{id}/reply         addTeamReply($id)
 *   GET  /safety/publications                   publications()
 *   GET  /safety/publications/new               newPublication()
 *   POST /safety/publications/save              savePublication()
 *   POST /safety/publications/{id}/publish      publishPublication($id)
 *   GET  /safety/publication/{id}               publicationDetail($id)
 *   GET  /safety/settings                       settings()
 *   POST /safety/settings                       saveSettings()
 */
class SafetyController {

    /** Roles that have safety-team (queue management) access.
     *  Includes 'safety_officer' which is the actual DB slug used in demo/prod data.
     *  'safety_manager' and 'safety_staff' are kept for forward-compatibility.
     */
    private const TEAM_ROLES = [
        'safety_manager',   // future / new tenants
        'safety_staff',     // future / new tenants
        'safety_officer',   // current DB slug (demo + prod seed)
        'airline_admin',
        'super_admin',
    ];

    /** Accepted upload MIME types */
    private const ALLOWED_MIMES = [
        'image/jpeg', 'image/png', 'image/heic',
        'application/pdf',
        'video/mp4', 'video/quicktime',
    ];

    /** Max upload size: 25 MB */
    private const MAX_UPLOAD_BYTES = 26_214_400;

    // =========================================================================
    // CREW-FACING ROUTES
    // =========================================================================

    /**
     * Safety home: shows report type tiles filtered by user role + tenant settings.
     * GET /safety
     *
     * Safety-team users are redirected to the Safety Dashboard — they should use
     * the team queue, not the crew submission landing page.
     */
    public function home(): void {
        requireAuth();
        $user      = currentUser();
        $userRoles = UserModel::getRoles((int) $user['id']);
        $roleSlugs = array_column($userRoles, 'slug');

        // Team users land on the dashboard, not the submission home
        if (array_intersect(self::TEAM_ROLES, $roleSlugs)) {
            redirect('/safety/dashboard');
        }

        $tenantId = (int) $user['tenant_id'];

        $settings     = SafetyReportModel::getSettings($tenantId);
        $enabledTypes = $settings['enabled_types'] ?? array_keys(SafetyReportModel::TYPES);

        $reportTypes = self::filterTypesByRole($enabledTypes, $roleSlugs);

        $draftCount     = count(SafetyReportModel::draftsForUser($tenantId, (int) $user['id']));
        $submittedCount = count(SafetyReportModel::forUser($tenantId, (int) $user['id']));
        $followUpCount  = count(SafetyReportModel::followUpsForUser($tenantId, (int) $user['id']));

        $isTeamUser = false; // non-team path (team users redirected above)

        $pageTitle    = 'Safety Reporting';
        $pageSubtitle = 'Confidential safety, hazard, and incident reporting. Protected under Just Culture policy.';

        ob_start();
        require VIEWS_PATH . '/safety/home.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    /**
     * GET /safety/select-type
     * Shows type selection if the user hasn't chosen one yet.
     */
    public function selectType(): void {
        requireAuth();
        $user     = currentUser();
        $tenantId = (int) $user['tenant_id'];

        try { $settings = SafetyReportModel::getSettings($tenantId); } catch (\Throwable $e) { $settings = []; }
        $enabledTypes = !empty($settings['enabled_types']) ? $settings['enabled_types'] : array_keys(SafetyReportModel::TYPES);
        $userRoles    = UserModel::getRoles((int) $user['id']);
        $roleSlugs    = array_column($userRoles, 'slug');

        $reportTypes = self::filterTypesByRole($enabledTypes, $roleSlugs);

        $pageTitle    = 'Select Report Type';
        $pageSubtitle = 'Choose the type of safety report you want to submit.';

        ob_start();
        require VIEWS_PATH . '/safety/select_type.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    /**
     * GET /safety/report/new/{type}
     * Shows the dynamic submission form for a given type.
     */
    public function reportForm(string $type): void {
        requireAuth();
        $user     = currentUser();
        $tenantId = (int) $user['tenant_id'];

        if (!self::userCanUseType($type, $tenantId, (int) $user['id'])) {
            flash('error', 'You do not have access to that report type.');
            redirect('/safety');
        }

        $settings        = SafetyReportModel::getSettings($tenantId);
        $typeName        = SafetyReportModel::TYPES[$type] ?? $type;
        $reportType      = $type;
        $reportTypeLabel = $typeName;
        $draft           = null; // populated when editing an existing draft
        $prefill         = $this->buildPrefill($user);
        $pageTitle       = 'New ' . htmlspecialchars($typeName);
        $pageSubtitle    = 'Complete the form below. Your submission is confidential.';

        ob_start();
        require VIEWS_PATH . '/safety/report_form.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    /**
     * GET /safety/quick-report/{type}
     * Minimal quick-capture form for fast field entry.
     */
    public function quickReportForm(string $type): void {
        requireAuth();
        $user     = currentUser();
        $tenantId = (int) $user['tenant_id'];

        if (!self::userCanUseType($type, $tenantId, (int) $user['id'])) {
            flash('error', 'You do not have access to that report type.');
            redirect('/safety');
        }

        $prefill      = $this->buildPrefill($user);
        $typeName     = SafetyReportModel::TYPES[$type] ?? $type;
        $pageTitle    = 'Quick Report: ' . htmlspecialchars($typeName);
        $pageSubtitle = 'Fast capture — add detail later.';

        ob_start();
        require VIEWS_PATH . '/safety/quick_report.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    /**
     * POST /safety/quick-report
     * Submit a quick report (can also save as draft).
     * Only validates: type, title, description. All other fields are optional.
     */
    public function submitQuickReport(): void {
        requireAuth();
        verifyCsrf();

        $user     = currentUser();
        $tenantId = (int) $user['tenant_id'];
        $type     = trim($_POST['report_type'] ?? 'general_hazard');

        if (!self::userCanUseType($type, $tenantId, (int) $user['id'])) {
            flash('error', 'You do not have access to that report type.');
            redirect('/safety');
        }

        $title       = trim($_POST['title']       ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!$title || !$description) {
            flash('error', 'Title and description are required.');
            redirect('/safety/quick-report/' . urlencode($type));
        }

        $data = $this->collectFormData($user);

        // Determine submit vs draft
        $asDraft = !empty($_POST['save_draft']);

        if ($asDraft) {
            $id = SafetyReportModel::saveDraft($tenantId, $data);
            AuditService::log('safety.draft_saved', 'safety_reports', $id);
            flash('success', 'Draft saved.');
            redirect('/safety/drafts');
            return;
        }

        $id     = SafetyReportModel::submit($tenantId, $data);
        $report = SafetyReportModel::find($tenantId, $id);

        AuditService::log('safety.report_submitted', 'safety_reports', $id, [
            'reference_no' => $report['reference_no'],
            'type'         => $type,
            'quick'        => true,
        ]);

        self::notifySafetyTeam(
            $tenantId,
            'New Safety Report: ' . $report['reference_no'],
            "{$report['reference_no']} — {$title}",
            '/safety/team/report/' . $id
        );

        flash('success', "Report successfully submitted. Reference: {$report['reference_no']}");
        redirect('/safety/report/' . $id);
    }

    /**
     * POST /safety/report/draft
     * Save a draft. Returns JSON {success, id, reference_no} if AJAX,
     * otherwise redirects.
     */
    public function saveDraft(): void {
        requireAuth();
        verifyCsrf();

        $user     = currentUser();
        $tenantId = (int) $user['tenant_id'];
        $data     = $this->collectFormData($user);

        // If updating existing draft
        $draftId = !empty($_POST['draft_id']) ? (int) $_POST['draft_id'] : null;

        if ($draftId) {
            $ok = SafetyReportModel::updateDraft($tenantId, $draftId, (int) $user['id'], $data);
            if (!$ok) {
                $this->jsonOrFlash('error', 'Draft not found or already submitted.', '/safety');
                return;
            }
            $id = $draftId;
        } else {
            $id = SafetyReportModel::saveDraft($tenantId, $data);
            AuditService::log('safety.draft_saved', 'safety_reports', $id);
        }

        $report = SafetyReportModel::find($tenantId, $id);

        if ($this->isAjax()) {
            jsonResponse(['success' => true, 'id' => $id, 'reference_no' => $report['reference_no'] ?? '']);
        } else {
            flash('success', 'Draft saved.');
            redirect('/safety/drafts');
        }
    }

    /**
     * POST /safety/report/submit
     * Validate, insert, notify safety managers, redirect to confirmation.
     */
    public function submitReport(): void {
        requireAuth();
        verifyCsrf();

        $user     = currentUser();
        $tenantId = (int) $user['tenant_id'];
        $type     = trim($_POST['report_type'] ?? 'general_hazard');

        if (!self::userCanUseType($type, $tenantId, (int) $user['id'])) {
            flash('error', 'You do not have access to that report type.');
            redirect('/safety');
        }

        $title       = trim($_POST['title']       ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!$title || !$description) {
            flash('error', 'Title and description are required.');
            redirect('/safety/report/new/' . urlencode($type));
        }

        $data   = $this->collectFormData($user);
        $id     = SafetyReportModel::submit($tenantId, $data);
        $report = SafetyReportModel::find($tenantId, $id);

        AuditService::log('safety.report_submitted', 'safety_reports', $id, [
            'reference_no' => $report['reference_no'],
            'type'         => $type,
        ]);

        // Notify all safety team users in the tenant
        self::notifySafetyTeam(
            $tenantId,
            'New Safety Report: ' . $report['reference_no'],
            "{$report['reference_no']} — {$title}",
            '/safety/team/report/' . $id
        );

        flash('success', "Report successfully submitted. Reference: {$report['reference_no']}");
        redirect('/safety/my-reports');
    }

    /**
     * GET /safety/report/edit/{id}
     * Continue editing an existing draft.
     */
    public function editDraft(int $id): void {
        requireAuth();
        $user     = currentUser();
        $tenantId = (int) $user['tenant_id'];

        $draft = SafetyReportModel::find($tenantId, $id);

        if (!$draft || !$draft['is_draft'] || (int) $draft['reporter_id'] !== (int) $user['id']) {
            flash('error', 'Draft not found or access denied.');
            redirect('/safety/drafts');
        }

        $type            = $draft['report_type'];
        $settings        = SafetyReportModel::getSettings($tenantId);
        $reportType      = $type;
        $reportTypeLabel = SafetyReportModel::TYPES[$type] ?? $type;
        $prefill         = $this->buildPrefill($user);

        $pageTitle    = 'Continue Draft';
        $pageSubtitle = 'Finish and submit your saved report.';

        ob_start();
        require VIEWS_PATH . '/safety/report_form.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    /**
     * POST /safety/report/delete/{id}
     * Delete a draft (own reports only).
     */
    public function deleteDraft(int $id): void {
        requireAuth();
        verifyCsrf();
        $user     = currentUser();
        $tenantId = (int) $user['tenant_id'];

        $draft = SafetyReportModel::find($tenantId, $id);

        if (!$draft || !$draft['is_draft'] || (int) $draft['reporter_id'] !== (int) $user['id']) {
            flash('error', 'Draft not found or access denied.');
            redirect('/safety/drafts');
        }

        Database::execute(
            "DELETE FROM safety_reports WHERE id = ? AND tenant_id = ? AND is_draft = 1",
            [$id, $tenantId]
        );

        AuditService::log('safety.draft_deleted', 'safety_reports', $id);
        flash('success', 'Draft deleted.');
        redirect('/safety/drafts');
    }

    /**
     * GET /safety/drafts
     */
    public function myDrafts(): void {
        requireAuth();
        $user     = currentUser();
        $tenantId = (int) $user['tenant_id'];

        $drafts = SafetyReportModel::draftsForUser($tenantId, (int) $user['id']);

        $pageTitle    = 'My Drafts';
        $pageSubtitle = 'Saved reports that have not yet been submitted.';

        ob_start();
        require VIEWS_PATH . '/safety/my_drafts.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    /**
     * GET /safety/my-reports
     */
    public function myReports(): void {
        requireAuth();
        $user     = currentUser();
        $tenantId = (int) $user['tenant_id'];
        $userId   = (int) $user['id'];

        $reports       = SafetyReportModel::forUser($tenantId, $userId);
        // Count reports where safety team has replied and reporter hasn't replied yet
        $pendingReplies = count(array_filter($reports, fn($r) => !empty($r['has_pending_reply'])));

        $pageTitle    = 'My Safety Submissions';
        $pageSubtitle = 'A history of reports you have securely filed.';

        ob_start();
        require VIEWS_PATH . '/safety/my_reports.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    /**
     * GET /safety/report/{id}
     * Reporter view: shows report + reporter-visible thread only (no internal notes).
     */
    public function reportDetail(int $id): void {
        requireAuth();
        $user     = currentUser();
        $tenantId = (int) $user['tenant_id'];

        $report = SafetyReportModel::find($tenantId, $id);

        // Must exist and belong to current reporter (or be a safety team member)
        if (!$report || (
            (int) $report['reporter_id'] !== (int) $user['id'] &&
            !RbacMiddleware::userHasAnyRole(self::TEAM_ROLES)
        )) {
            flash('error', 'Report not found or access denied.');
            redirect('/safety/my-reports');
        }

        $threads     = SafetyReportModel::getThreads($id, false); // public only
        $attachments = SafetyReportModel::getAttachments($id);

        $pageTitle    = 'Report ' . $report['reference_no'];
        $pageSubtitle = 'Filed ' . date('d M Y', strtotime($report['created_at']));

        ob_start();
        require VIEWS_PATH . '/safety/report_detail.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    /**
     * POST /safety/report/{id}/reply
     * Reporter adds a public thread reply (is_internal always false).
     */
    public function addReply(int $id): void {
        requireAuth();
        verifyCsrf();

        $user     = currentUser();
        $tenantId = (int) $user['tenant_id'];
        $report   = SafetyReportModel::find($tenantId, $id);

        if (!$report || (int) $report['reporter_id'] !== (int) $user['id']) {
            flash('error', 'Report not found or access denied.');
            redirect('/safety/my-reports');
        }

        $body = trim($_POST['body'] ?? '');
        if (!$body) {
            flash('error', 'Reply cannot be empty.');
            redirect("/safety/report/$id");
        }

        SafetyReportModel::addThread($id, (int) $user['id'], $body, false);
        AuditService::log('safety.reply_added', 'safety_reports', $id);

        // Notify assigned_to or all safety_managers
        if ($report['assigned_to']) {
            NotificationService::notifyUser(
                (int) $report['assigned_to'],
                'New Reply: ' . $report['reference_no'],
                'The reporter has added a reply to ' . $report['reference_no'],
                '/safety/team/report/' . $id
            );
        } else {
            self::notifySafetyTeam(
                $tenantId,
                'New Reply: ' . $report['reference_no'],
                'The reporter has added a reply to ' . $report['reference_no'],
                '/safety/team/report/' . $id
            );
        }

        flash('success', 'Reply added.');
        redirect("/safety/report/$id");
    }

    /**
     * POST /safety/report/{id}/upload
     * Handle file upload for a report or a thread reply.
     */
    public function uploadAttachment(int $id): void {
        requireAuth();
        verifyCsrf();

        $user     = currentUser();
        $tenantId = (int) $user['tenant_id'];
        $report   = SafetyReportModel::find($tenantId, $id);

        if (!$report) {
            flash('error', 'Report not found.');
            redirect('/safety/my-reports');
        }

        // Reporter can only upload to their own reports; team members can always upload
        if ((int) $report['reporter_id'] !== (int) $user['id'] &&
            !RbacMiddleware::userHasAnyRole(self::TEAM_ROLES)
        ) {
            flash('error', 'Access denied.');
            redirect('/safety/my-reports');
        }

        if (empty($_FILES['attachment']) || $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'No file uploaded or upload error.');
            redirect("/safety/report/$id");
        }

        $file = $_FILES['attachment'];

        // Validate size
        if ($file['size'] > self::MAX_UPLOAD_BYTES) {
            flash('error', 'File exceeds 25 MB limit.');
            redirect("/safety/report/$id");
        }

        // Validate MIME via finfo (not extension)
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        if (!in_array($mimeType, self::ALLOWED_MIMES, true)) {
            flash('error', 'File type not allowed. Accepted: JPG, PNG, PDF, MP4, MOV, HEIC.');
            redirect("/safety/report/$id");
        }

        // Build destination path
        $uploadDir = rtrim(STORAGE_PATH ?? BASE_PATH . '/uploads', '/') .
                     "/safety/{$tenantId}/{$id}/";

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            flash('error', 'Could not create upload directory.');
            redirect("/safety/report/$id");
        }

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safeName = uniqid('att_', true) . '.' . strtolower($ext);
        $destPath = $uploadDir . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            flash('error', 'Failed to save file.');
            redirect("/safety/report/$id");
        }

        $threadId = !empty($_POST['thread_id']) ? (int) $_POST['thread_id'] : null;

        SafetyReportModel::addAttachment($id, (int) $user['id'], [
            'file_name' => $file['name'],
            'file_path' => "safety/{$tenantId}/{$id}/{$safeName}",
            'file_type' => $mimeType,
            'file_size' => $file['size'],
        ], $threadId);

        AuditService::log('safety.attachment_uploaded', 'safety_reports', $id, $file['name']);

        flash('success', 'Attachment uploaded.');
        $backUrl = $report['is_draft'] ? "/safety/report/$id" : "/safety/report/$id";
        redirect($backUrl);
    }

    /**
     * GET /safety/follow-ups
     * Reports where the safety team has replied and the reporter hasn't responded yet.
     */
    public function myFollowUps(): void {
        requireAuth();
        $user     = currentUser();
        $tenantId = (int) $user['tenant_id'];
        $userId   = (int) $user['id'];

        $followUps = SafetyReportModel::followUpsForUser($tenantId, $userId);

        $pageTitle    = 'Follow-Ups';
        $pageSubtitle = 'Reports where the safety team has responded and is awaiting your reply.';

        ob_start();
        require VIEWS_PATH . '/safety/my_followups.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    // =========================================================================
    // SAFETY-TEAM ROUTES
    // =========================================================================

    /**
     * GET /safety/dashboard
     * Safety team dashboard with counters and recent activity.
     */
    public function safetyDashboard(): void {
        RbacMiddleware::requireRole(self::TEAM_ROLES);

        $tenantId = (int) currentUser()['tenant_id'];

        // Mark past-due actions as overdue (replaces MySQL EVENT on shared hosting)
        SafetyReportModel::markOverdueActions($tenantId);

        $stats = SafetyReportModel::stats($tenantId);

        $recentReports  = array_slice(SafetyReportModel::allForTenant($tenantId, 'all', []), 0, 8);
        $overdueActions = SafetyReportModel::tenantActions($tenantId, 'overdue');
        $pendingActions = SafetyReportModel::tenantActions($tenantId, 'open');

        $pageTitle    = 'Safety Dashboard';
        $pageSubtitle = 'Overview of all safety activity for your airline.';

        ob_start();
        require VIEWS_PATH . '/safety/safety_dashboard.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    /**
     * GET /safety/queue
     * Safety team queue with filters and stats.
     */
    public function index(): void {
        RbacMiddleware::requireRole(self::TEAM_ROLES);
        $tenantId     = currentTenantId();
        $statusFilter = $_GET['status']      ?? 'all';
        $filters      = [
            'type'        => $_GET['type']        ?? '',
            'assigned_to' => $_GET['assigned_to'] ?? '',
            'severity'    => $_GET['severity']    ?? '',
            'date_from'   => $_GET['date_from']   ?? '',
            'date_to'     => $_GET['date_to']     ?? '',
        ];

        $reports  = SafetyReportModel::allForTenant($tenantId, $statusFilter, array_filter($filters));
        $stats    = SafetyReportModel::stats($tenantId);
        $crewList = UserModel::allForTenant($tenantId);

        $pageTitle    = 'Safety Management';
        $pageSubtitle = 'Aviation Safety, Hazards, and Compliance Reports.';

        ob_start();
        require VIEWS_PATH . '/safety/queue.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    /**
     * GET /safety/team/report/{id}
     * Full detail: internal notes + public thread + status/assignment controls.
     */
    public function teamDetail(int $id): void {
        RbacMiddleware::requireRole(self::TEAM_ROLES);
        $tenantId = currentTenantId();
        $report   = SafetyReportModel::find($tenantId, $id);

        if (!$report) {
            flash('error', 'Report not found.');
            redirect('/safety/queue');
        }

        $allThreads    = SafetyReportModel::getThreads($id, true); // include internal
        // Split for view: Discussion tab uses $publicThreads, Internal Notes uses $internalNotes
        $publicThreads = array_values(array_filter($allThreads, fn($t) => !(bool)($t['is_internal'] ?? false)));
        $internalNotes = array_values(array_filter($allThreads, fn($t) =>  (bool)($t['is_internal'] ?? false)));
        $threads       = $allThreads; // kept for backward-compat in any view code referencing $threads
        $attachments   = SafetyReportModel::getAttachments($id);
        $statusHistory = SafetyReportModel::getStatusHistory($id);
        $actions       = SafetyReportModel::getActions($id, $tenantId);
        $crewList      = UserModel::allForTenant($tenantId);

        // Safety-team users for assignment dropdown
        // Covers all safety role slugs including the actual DB slug 'safety_officer'
        $safetyUsers = array_filter($crewList, function($u) {
            $roles = array_column(UserModel::getRoles((int)$u['id']), 'slug');
            return (bool) array_intersect(self::TEAM_ROLES, $roles);
        });
        $safetyUsers = array_values($safetyUsers);

        // Fallback: if no safety-team users found, show all crew
        if (empty($safetyUsers)) {
            $safetyUsers = $crewList;
        }

        $pageTitle    = 'Safety Report: ' . $report['reference_no'];
        $pageSubtitle = 'Filed ' . date('d M Y, H:i', strtotime($report['created_at']));

        ob_start();
        require VIEWS_PATH . '/safety/team_detail.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    /**
     * POST /safety/team/report/{id}/status
     * Changes report status, logs history, notifies reporter.
     */
    public function updateStatus(int $id): void {
        RbacMiddleware::requireRole(self::TEAM_ROLES);
        verifyCsrf();

        $tenantId  = currentTenantId();
        $user      = currentUser();
        $newStatus = trim($_POST['status']  ?? '');
        $comment   = trim($_POST['comment'] ?? '') ?: null;

        if (!in_array($newStatus, SafetyReportModel::STATUSES, true)) {
            flash('error', 'Invalid status.');
            redirect("/safety/team/report/$id");
        }

        $report = SafetyReportModel::find($tenantId, $id);
        if (!$report) {
            flash('error', 'Report not found.');
            redirect('/safety/queue');
        }

        $ok = SafetyReportModel::updateStatus($tenantId, $id, (int) $user['id'], $newStatus, $comment);
        if (!$ok) {
            flash('error', 'Could not update status.');
            redirect("/safety/team/report/$id");
        }

        AuditService::log('safety.status_changed', 'safety_reports', $id, [
            'from' => $report['status'],
            'to'   => $newStatus,
        ]);

        // Notify reporter if not anonymous
        if (!$report['is_anonymous'] && $report['reporter_id']) {
            NotificationService::notifyUser(
                (int) $report['reporter_id'],
                'Safety Report Update: ' . $report['reference_no'],
                "Your report {$report['reference_no']} status changed to: " . str_replace('_', ' ', $newStatus),
                '/safety/report/' . $id
            );
        }

        flash('success', 'Status updated to ' . str_replace('_', ' ', $newStatus) . '.');
        redirect("/safety/team/report/$id");
    }

    /**
     * GET /safety/notifications/count
     * Returns JSON {count, for_team} — used by the header bell for live polling.
     * No role restriction: every authenticated user gets a count relevant to them.
     */
    public function notificationCount(): void {
        requireAuth();
        $user      = currentUser();
        $tenantId  = (int) $user['tenant_id'];
        $userId    = (int) $user['id'];
        $userRoles = UserModel::getRoles($userId);
        $roleSlugs = array_column($userRoles, 'slug');
        $isTeam    = (bool) array_intersect(self::TEAM_ROLES, $roleSlugs);

        $count = 0;
        try {
            if ($isTeam) {
                // Safety team: count reports where the reporter last replied
                // (pilot is waiting for safety team to respond)
                $row = Database::fetch(
                    "SELECT COUNT(DISTINCT sr.id) AS cnt
                       FROM safety_reports sr
                       JOIN safety_report_threads lt
                         ON lt.report_id   = sr.id
                        AND lt.is_internal = 0
                        AND lt.created_at  = (
                            SELECT MAX(t2.created_at)
                              FROM safety_report_threads t2
                             WHERE t2.report_id   = sr.id
                               AND t2.is_internal = 0
                        )
                      WHERE sr.tenant_id = ?
                        AND sr.is_draft  = 0
                        AND sr.status NOT IN ('closed','draft')
                        AND lt.author_id = sr.reporter_id",
                    [$tenantId]
                );
                $count = (int)($row['cnt'] ?? 0);
            } else {
                // Pilot/crew: count their reports where safety team last replied
                // (safety team is waiting for reporter to respond)
                $row = Database::fetch(
                    "SELECT COUNT(DISTINCT sr.id) AS cnt
                       FROM safety_reports sr
                       JOIN safety_report_threads lt
                         ON lt.report_id   = sr.id
                        AND lt.is_internal = 0
                        AND lt.created_at  = (
                            SELECT MAX(t2.created_at)
                              FROM safety_report_threads t2
                             WHERE t2.report_id   = sr.id
                               AND t2.is_internal = 0
                        )
                      WHERE sr.tenant_id  = ?
                        AND sr.reporter_id = ?
                        AND sr.is_draft    = 0
                        AND sr.status NOT IN ('closed','draft')
                        AND lt.author_id  != sr.reporter_id",
                    [$tenantId, $userId]
                );
                $count = (int)($row['cnt'] ?? 0);
            }
        } catch (\Throwable $e) {
            $count = 0; // threads table may not exist yet
        }

        header('Content-Type: application/json');
        echo json_encode(['count' => $count, 'for_team' => $isTeam]);
        exit;
    }

    /**
     * POST /safety/team/report/{id}/severity
     * Set the safety team's final severity classification.
     */
    public function updateSeverity(int $id): void {
        RbacMiddleware::requireRole(self::TEAM_ROLES);
        verifyCsrf();

        $tenantId = currentTenantId();
        $user     = currentUser();
        $severity = trim($_POST['final_severity'] ?? '');

        $report = SafetyReportModel::find($tenantId, $id);
        if (!$report) {
            flash('error', 'Report not found.');
            redirect('/safety/queue');
        }

        SafetyReportModel::setFinalSeverity($tenantId, $id, $severity ?: null);
        AuditService::log('safety.severity_classified', 'safety_reports', $id, [
            'final_severity' => $severity ?: 'unclassified',
        ]);

        flash('success', $severity ? 'Severity classified as ' . ucfirst($severity) . '.' : 'Severity classification cleared.');
        redirect("/safety/team/report/$id");
    }

    /**
     * POST /safety/team/report/{id}/assign
     */
    public function assignReport(int $id): void {
        RbacMiddleware::requireRole(self::TEAM_ROLES);
        verifyCsrf();

        $tenantId   = currentTenantId();
        $user       = currentUser();
        $assignedTo = !empty($_POST['assigned_to']) ? (int) $_POST['assigned_to'] : null;

        $report = SafetyReportModel::find($tenantId, $id);
        if (!$report) {
            flash('error', 'Report not found.');
            redirect('/safety/queue');
        }

        SafetyReportModel::assign($tenantId, $id, (int) $user['id'], $assignedTo);
        AuditService::log('safety.assigned', 'safety_reports', $id, ['assigned_to' => $assignedTo]);

        flash('success', $assignedTo ? 'Report assigned.' : 'Report unassigned.');
        redirect("/safety/team/report/$id");
    }

    /**
     * POST /safety/team/report/{id}/internal-note
     * Add internal-only note (hidden from reporter).
     */
    public function addInternalNote(int $id): void {
        RbacMiddleware::requireRole(self::TEAM_ROLES);
        verifyCsrf();

        $tenantId = currentTenantId();
        $user     = currentUser();

        $report = SafetyReportModel::find($tenantId, $id);
        if (!$report) {
            flash('error', 'Report not found.');
            redirect('/safety/queue');
        }

        $body = trim($_POST['body'] ?? '');
        if (!$body) {
            flash('error', 'Note body cannot be empty.');
            redirect("/safety/team/report/$id");
        }

        SafetyReportModel::addThread($id, (int) $user['id'], $body, true);
        AuditService::log('safety.internal_note_added', 'safety_reports', $id);

        flash('success', 'Internal note added.');
        redirect("/safety/team/report/$id");
    }

    /**
     * POST /safety/team/report/{id}/reply
     * Public reply from safety team — visible to reporter. Fires notification.
     */
    public function addTeamReply(int $id): void {
        RbacMiddleware::requireRole(self::TEAM_ROLES);
        verifyCsrf();

        $tenantId = currentTenantId();
        $user     = currentUser();

        $report = SafetyReportModel::find($tenantId, $id);
        if (!$report) {
            flash('error', 'Report not found.');
            redirect('/safety/queue');
        }

        $body = trim($_POST['body'] ?? '');
        if (!$body) {
            flash('error', 'Reply cannot be empty.');
            redirect("/safety/team/report/$id");
        }

        SafetyReportModel::addThread($id, (int) $user['id'], $body, false);
        AuditService::log('safety.team_reply_added', 'safety_reports', $id);

        // Notify reporter if not anonymous
        if (!$report['is_anonymous'] && $report['reporter_id']) {
            NotificationService::notifyUser(
                (int) $report['reporter_id'],
                'Update on ' . $report['reference_no'],
                'The safety team has replied to your report ' . $report['reference_no'],
                '/safety/report/' . $id
            );
        }

        flash('success', 'Reply sent.');
        redirect("/safety/team/report/$id");
    }

    // ─── Corrective Actions ───────────────────────────────────────────────────

    /**
     * POST /safety/team/report/{id}/action
     * Create a corrective action for a report (safety team only).
     */
    public function addAction(int $id): void {
        RbacMiddleware::requireRole(self::TEAM_ROLES);
        verifyCsrf();
        $user     = currentUser();
        $tenantId = (int) $user['tenant_id'];

        $report = SafetyReportModel::find($tenantId, $id);
        if (!$report) {
            flash('error', 'Report not found.');
            redirect('/safety/queue');
        }

        $data = [
            'title'         => trim($_POST['title']         ?? ''),
            'description'   => trim($_POST['description']   ?? ''),
            'assigned_to'   => !empty($_POST['assigned_to'])   ? (int) $_POST['assigned_to']   : null,
            'assigned_role' => trim($_POST['assigned_role'] ?? ''),
            'due_date'      => trim($_POST['due_date']       ?? '') ?: null,
        ];

        if (!$data['title']) {
            flash('error', 'Action title is required.');
            redirect("/safety/team/report/$id");
        }

        $actionId = SafetyReportModel::addAction($tenantId, $id, (int) $user['id'], $data);
        AuditService::log('safety_action.created', 'safety_actions', $actionId, ['report_id' => $id, 'title' => $data['title']]);

        // Notify assigned user
        if ($data['assigned_to']) {
            NotificationService::notifyUser(
                $data['assigned_to'],
                'Safety Action Assigned',
                "You have been assigned a corrective action for report {$report['reference_no']}: {$data['title']}",
                "/safety/team/report/$id"
            );
        }

        flash('success', 'Action created successfully.');
        redirect("/safety/team/report/$id#actions");
    }

    /**
     * POST /safety/team/action/{id}/update
     * Update action status/details.
     */
    public function updateAction(int $id): void {
        RbacMiddleware::requireRole(self::TEAM_ROLES);
        verifyCsrf();
        $user     = currentUser();
        $tenantId = (int) $user['tenant_id'];

        $data = [
            'status'      => $_POST['status']      ?? null,
            'title'       => !empty($_POST['title'])      ? trim($_POST['title'])      : null,
            'due_date'    => !empty($_POST['due_date'])   ? trim($_POST['due_date'])   : null,
            'assigned_to' => !empty($_POST['assigned_to']) ? (int) $_POST['assigned_to'] : null,
        ];
        // Remove null values so we don't overwrite existing data with nulls
        $data = array_filter($data, fn($v) => $v !== null);

        SafetyReportModel::updateAction($id, $tenantId, $data);
        AuditService::log('safety_action.updated', 'safety_actions', $id, $data);

        flash('success', 'Action updated.');
        $ref = $_SERVER['HTTP_REFERER'] ?? '/safety/queue';
        redirect($ref);
    }

    /**
     * GET /safety/team/actions
     * All actions across all reports for the tenant (safety team view).
     */
    public function actionsQueue(): void {
        RbacMiddleware::requireRole(self::TEAM_ROLES);
        $user     = currentUser();
        $tenantId = (int) $user['tenant_id'];

        $statusFilter = $_GET['status'] ?? 'all';
        $actions      = SafetyReportModel::tenantActions($tenantId, $statusFilter);
        $stats        = SafetyReportModel::stats($tenantId);

        $pageTitle    = 'Corrective Actions';
        $pageSubtitle = 'Track and manage safety corrective actions across all reports.';

        ob_start();
        require VIEWS_PATH . '/safety/actions_queue.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    // ─── Publications ─────────────────────────────────────────────────────────

    /**
     * GET /safety/publications
     */
    public function publications(): void {
        RbacMiddleware::requireRole(self::TEAM_ROLES);
        $tenantId    = currentTenantId();
        $statusParam = $_GET['status'] ?? 'all';
        $publications = SafetyReportModel::getPublications($tenantId, $statusParam);

        $pageTitle    = 'Safety Publications';
        $pageSubtitle = 'Bulletins and lessons learned for your organisation.';

        ob_start();
        require VIEWS_PATH . '/safety/publications.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    /**
     * GET /safety/publication/{id}
     */
    public function publicationDetail(int $id): void {
        requireAuth();
        $tenantId   = currentTenantId();
        $publication = SafetyReportModel::getPublication($tenantId, $id);

        if (!$publication || $publication['status'] !== 'published') {
            // Non-team users cannot see non-published publications
            if (!RbacMiddleware::userHasAnyRole(self::TEAM_ROLES)) {
                flash('error', 'Publication not found.');
                redirect('/safety');
            }
        }

        if (!$publication) {
            flash('error', 'Publication not found.');
            redirect('/safety/publications');
        }

        $pageTitle    = htmlspecialchars($publication['title']);
        $pageSubtitle = 'Safety Publication';

        ob_start();
        require VIEWS_PATH . '/safety/publication_detail.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    /**
     * GET /safety/publications/new
     */
    public function newPublication(): void {
        RbacMiddleware::requireRole(self::TEAM_ROLES);
        $tenantId = currentTenantId();
        $reports  = SafetyReportModel::allForTenant($tenantId, 'all');

        $pageTitle    = 'New Safety Publication';
        $pageSubtitle = 'Draft a safety bulletin or lessons-learned document.';

        ob_start();
        require VIEWS_PATH . '/safety/publication_form.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    /**
     * POST /safety/publications/save
     */
    public function savePublication(): void {
        RbacMiddleware::requireRole(self::TEAM_ROLES);
        verifyCsrf();

        $tenantId = currentTenantId();
        $user     = currentUser();

        $title   = trim($_POST['title']   ?? '');
        $content = trim($_POST['content'] ?? '');

        if (!$title || !$content) {
            flash('error', 'Title and content are required.');
            redirect('/safety/publications/new');
        }

        $data = [
            'title'             => $title,
            'summary'           => trim($_POST['summary']           ?? '') ?: null,
            'content'           => $content,
            'related_report_id' => !empty($_POST['related_report_id']) ? (int) $_POST['related_report_id'] : null,
            'status'            => 'draft',
        ];

        $id = SafetyReportModel::savePublication($tenantId, (int) $user['id'], $data);
        AuditService::log('safety.publication_saved', 'safety_publications', $id);

        flash('success', 'Publication saved as draft.');
        redirect('/safety/publications');
    }

    /**
     * POST /safety/publications/{id}/publish
     */
    public function publishPublication(int $id): void {
        RbacMiddleware::requireRole(self::TEAM_ROLES);
        verifyCsrf();

        $tenantId = currentTenantId();
        $ok       = SafetyReportModel::publishPublication($id, $tenantId);

        if (!$ok) {
            flash('error', 'Publication not found or already published.');
            redirect('/safety/publications');
        }

        AuditService::log('safety.publication_published', 'safety_publications', $id);
        flash('success', 'Publication published.');
        redirect('/safety/publications');
    }

    // ─── Settings ─────────────────────────────────────────────────────────────

    /**
     * GET /safety/settings
     */
    public function settings(): void {
        RbacMiddleware::requireRole(['safety_manager', 'safety_officer', 'airline_admin', 'super_admin']);
        $tenantId = currentTenantId();
        $settings = SafetyReportModel::getSettings($tenantId);

        $pageTitle    = 'Safety Module Settings';
        $pageSubtitle = 'Configure safety reporting options for your organisation.';

        ob_start();
        require VIEWS_PATH . '/safety/settings.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    /**
     * POST /safety/settings
     */
    public function saveSettings(): void {
        RbacMiddleware::requireRole(['safety_manager', 'safety_officer', 'airline_admin', 'super_admin']);
        verifyCsrf();

        $tenantId = currentTenantId();

        $enabledTypes = array_filter(
            array_keys(SafetyReportModel::TYPES),
            fn ($slug) => !empty($_POST['type_' . $slug])
        );

        $data = [
            'enabled_types'        => array_values($enabledTypes),
            'allow_anonymous'      => !empty($_POST['allow_anonymous'])      ? 1 : 0,
            'require_aircraft_reg' => !empty($_POST['require_aircraft_reg']) ? 1 : 0,
            'risk_matrix_enabled'  => !empty($_POST['risk_matrix_enabled'])  ? 1 : 0,
            'retention_days'       => max(90, (int) ($_POST['retention_days'] ?? 2555)),
        ];

        SafetyReportModel::updateSettings($tenantId, $data);
        AuditService::log('safety.settings_updated', 'tenant', $tenantId);

        flash('success', 'Safety settings saved.');
        redirect('/safety/settings');
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Build pre-fill context for the report form from the current user's profile,
     * roles, crew profile, and today's roster entry.
     */
    private function buildPrefill(array $user): array {
        $tenantId = (int) $user['tenant_id'];
        $userId   = (int) $user['id'];

        // 1. Reporter identity from user record
        $prefill = [
            'reporter_name'        => $user['name']            ?? '',
            'reporter_employee_id' => $user['employee_id']     ?? '',
            'reporter_department'  => $user['department_name'] ?? '',
            'reporter_base'        => $user['base_code']       ?? '',
            'reporter_fleet'       => $user['fleet_name']      ?? '',
            'event_date'           => date('Y-m-d'),
            'event_utc_time'       => gmdate('H:i'),
            'event_local_time'     => date('H:i'),
            'location_name'        => $user['base_code']       ?? '',
        ];

        // 2. Role/position from user roles
        $roles       = UserModel::getRoles($userId);
        $primaryRole = !empty($roles) ? ($roles[0]['name'] ?? '') : '';
        $prefill['reporter_position'] = $primaryRole;

        // 3. Crew profile for additional position info
        if (class_exists('CrewProfileModel')) {
            $profile = CrewProfileModel::findByUser($userId);
            if ($profile && !empty($profile['contract_type'])) {
                $prefill['reporter_contract'] = $profile['contract_type'];
            }
        }

        // 4. Today's roster entry for flight/duty context
        $today       = date('Y-m-d');
        $todayRoster = Database::fetch(
            "SELECT r.*, r.duty_type, r.notes
             FROM rosters r
             WHERE r.tenant_id = ? AND r.user_id = ? AND r.roster_date = ?
             LIMIT 1",
            [$tenantId, $userId, $today]
        );

        if ($todayRoster) {
            $prefill['roster_duty_type'] = $todayRoster['duty_type'] ?? '';
            $prefill['roster_notes']     = $todayRoster['notes']     ?? '';
            // For flight duties, expose that flight context exists
            if (in_array($todayRoster['duty_type'], ['flight', 'pos', 'deadhead'])) {
                $prefill['has_flight_context'] = true;
            }
        }

        return $prefill;
    }

    /**
     * Collect and sanitise form fields from $_POST into a data array
     * suitable for SafetyReportModel::submit() / saveDraft().
     */
    private function collectFormData(array $user): array {
        // Merge risk matrix values into extra_fields (no dedicated DB columns exist)
        $extraFields = [];
        if (!empty($_POST['extra_fields'])) {
            $posted      = $_POST['extra_fields'];
            $extraFields = is_array($posted) ? $posted : (json_decode($posted, true) ?? []);
        }
        if (!empty($_POST['risk_likelihood']))   $extraFields['risk_likelihood']   = trim($_POST['risk_likelihood']);
        if (!empty($_POST['risk_severity']))     $extraFields['risk_severity']     = trim($_POST['risk_severity']);
        if (!empty($_POST['initial_risk_code'])) $extraFields['initial_risk_code'] = trim($_POST['initial_risk_code']);

        return [
            'report_type'          => trim($_POST['report_type']          ?? 'general_hazard'),
            'reporter_id'          => (int) $user['id'],
            'is_anonymous'         => !empty($_POST['is_anonymous']),
            'event_date'           => trim($_POST['event_date']           ?? '') ?: null,
            'event_utc_time'       => trim($_POST['event_utc_time']       ?? '') ?: null,
            'event_local_time'     => trim($_POST['event_local_time']     ?? '') ?: null,
            'location_name'        => trim($_POST['location_name']        ?? '') ?: null,
            'icao_code'            => strtoupper(trim($_POST['icao_code'] ?? '')) ?: null,
            'occurrence_type'      => in_array(($_POST['occurrence_type'] ?? ''), ['occurrence','hazard'])
                                       ? $_POST['occurrence_type'] : 'occurrence',
            'event_type'           => trim($_POST['event_type']           ?? '') ?: null,
            'initial_risk_score'   => isset($_POST['initial_risk_score']) && $_POST['initial_risk_score'] !== ''
                                       ? max(1, min(5, (int) $_POST['initial_risk_score'])) : null,
            'aircraft_registration'=> strtoupper(trim($_POST['aircraft_registration'] ?? '')) ?: null,
            'call_sign'            => strtoupper(trim($_POST['call_sign']  ?? '')) ?: null,
            'title'                => trim($_POST['title']                ?? ''),
            'description'          => trim($_POST['description']          ?? ''),
            'severity'             => trim($_POST['severity']             ?? 'unassigned'),
            'extra_fields'         => !empty($extraFields) ? $extraFields : null,
            'reporter_position'    => trim($_POST['reporter_position']    ?? '') ?: null,
            'template_version'     => max(1, (int) ($_POST['template_version'] ?? 1)),
        ];
    }

    /**
     * Filter enabled type slugs to those accessible by the user's roles.
     */
    private static function filterTypesByRole(array $enabledTypes, array $userRoleSlugs): array {
        // Safety team members can submit any enabled report type
        if (array_intersect(self::TEAM_ROLES, $userRoleSlugs)) {
            $result = [];
            foreach ($enabledTypes as $slug) {
                if (isset(SafetyReportModel::TYPES[$slug])) {
                    $result[$slug] = SafetyReportModel::TYPES[$slug];
                }
            }
            return $result;
        }

        $result = [];
        foreach ($enabledTypes as $slug) {
            if (!isset(SafetyReportModel::TYPES[$slug])) continue;
            $allowed = SafetyReportModel::TYPE_ROLES[$slug] ?? ['all'];
            if (in_array('all', $allowed, true) ||
                array_intersect($allowed, $userRoleSlugs)
            ) {
                $result[$slug] = SafetyReportModel::TYPES[$slug];
            }
        }
        return $result;
    }

    /**
     * Return true if the user's role allows submitting the given type,
     * and the type is enabled for the tenant.
     */
    private static function userCanUseType(string $type, int $tenantId, int $userId): bool {
        if (!isset(SafetyReportModel::TYPES[$type])) return false;

        try { $settings = SafetyReportModel::getSettings($tenantId); } catch (\Throwable $e) { $settings = []; }
        $enabledTypes = !empty($settings['enabled_types']) ? $settings['enabled_types'] : array_keys(SafetyReportModel::TYPES);
        if (!in_array($type, $enabledTypes, true)) return false;

        $allowed = SafetyReportModel::TYPE_ROLES[$type] ?? ['all'];
        if (in_array('all', $allowed, true)) return true;

        $userRoles = UserModel::getRoles($userId);
        $roleSlugs = array_column($userRoles, 'slug');
        if (array_intersect(self::TEAM_ROLES, $roleSlugs)) return true;

        return (bool) array_intersect($allowed, $roleSlugs);
    }

    /**
     * Detect whether the current request is an AJAX / JSON request.
     */
    private function isAjax(): bool {
        return (
            ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' ||
            str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
        );
    }

    /**
     * Notify all safety-team roles for a tenant.
     * Covers 'safety_manager', 'safety_staff', and 'safety_officer' (actual DB slug).
     * Each role gets one notification; duplicate users (multiple roles) may get extras —
     * acceptable for safety-critical comms.
     */
    private static function notifySafetyTeam(
        int    $tenantId,
        string $title,
        string $body,
        string $link = ''
    ): void {
        $notifyRoles = ['safety_manager', 'safety_staff', 'safety_officer'];
        foreach ($notifyRoles as $role) {
            NotificationService::notifyTenant($tenantId, $role, $title, $body, $link);
        }
    }

    /**
     * Either respond with JSON error or flash + redirect, depending on request type.
     */
    private function jsonOrFlash(string $type, string $message, string $redirect): void {
        if ($this->isAjax()) {
            jsonResponse([$type => $message], $type === 'error' ? 422 : 200);
        } else {
            flash($type, $message);
            redirect($redirect);
        }
    }
}
