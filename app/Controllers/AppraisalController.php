<?php
/**
 * AppraisalController — Crew Appraisal / Performance.
 *
 * The appraisals module mirrors the **Field Stations Staff Appraisal Form
 * (Form No. 150, Rev 01 — 11/11/2024)** referenced under
 * OpsVelo/Design Files/Appraisal Form/. Two flavours co-exist:
 *
 *   • Self Appraisal  — appraisee fills it out about themselves
 *                       (Section Two of the paper form, 18 questions).
 *   • Peer Appraisal  — colleague rates appraisee on five attributes
 *                       (Section Three of the paper form, 1-5 each).
 *
 * The web app reuses the same `appraisals` table the iPad CrewAssist app
 * already writes to (see `RealAppraisalService.swift`). Statuses, rating
 * scale, and confidentiality rules MUST stay in lockstep with the iPad —
 * the data flows through one shared API surface.
 */
class AppraisalController {

    // Five competency attributes from Section Three of the paper form.
    // Mirrors `PeerAppraisalForm.attributes` in
    // CrewAssist/Features/Appraisals/AppraisalsView.swift.
    private const COMPETENCY_ATTRIBUTES = [
        'communication'         => ['title' => 'Communication skills',
                                    'desc'  => 'Listens, interprets and provides relevant feedback'],
        'professionalism'       => ['title' => 'Professionalism',
                                    'desc'  => 'Ethically uses acquired skills to undertake tasks'],
        'leadership'            => ['title' => 'Leadership skills',
                                    'desc'  => 'Inspires others to achieve set goals'],
        'team_spirit'           => ['title' => 'Team spirit',
                                    'desc'  => 'Works in harmony with others'],
        'resource_management'   => ['title' => 'Resource management / Efficiency',
                                    'desc'  => 'Optimum use of resources'],
    ];

    private const RATING_LABELS = [
        1 => ['code' => 'P',  'label' => 'Poor'],
        2 => ['code' => 'F',  'label' => 'Fair'],
        3 => ['code' => 'G',  'label' => 'Good'],
        4 => ['code' => 'VG', 'label' => 'Very Good'],
        5 => ['code' => 'E',  'label' => 'Excellent'],
    ];

    // Roles that can manage / review appraisals at the airline level.
    private const REVIEWER_ROLES = [
        'super_admin','airline_admin','hr','chief_pilot','head_cabin_crew',
    ];

    // ─── Index — overview, pending, completed, action, history ─────────

    public function index(): void {
        requireAuth();
        $tenantId = (int)currentTenantId();
        $userId   = (int)currentUser()['id'];
        $myRoles  = array_column(UserModel::getRoles($userId), 'slug');
        $isReviewer = (bool)array_intersect($myRoles, self::REVIEWER_ROLES);
        $canCreate  = self::canAppraiseAnyone($myRoles, $tenantId, $userId);

        // Appraisals I've written
        $mine = Database::fetchAll(
            "SELECT a.*, us.name AS subject_name, ua.name AS appraiser_name
               FROM appraisals a
               JOIN users us ON a.subject_id = us.id
               JOIN users ua ON a.appraiser_id = ua.id
              WHERE a.tenant_id = ? AND a.appraiser_id = ?
              ORDER BY a.period_to DESC, a.id DESC",
            [$tenantId, $userId]
        );

        // Appraisals about me (per confidentiality rule — only accepted, or
        // non-confidential. Mirrors AppraisalApiController.)
        $aboutMe = Database::fetchAll(
            "SELECT a.*, us.name AS subject_name, ua.name AS appraiser_name
               FROM appraisals a
               JOIN users ua ON a.appraiser_id = ua.id
               JOIN users us ON a.subject_id = us.id
              WHERE a.tenant_id = ? AND a.subject_id = ?
                AND (a.confidential = 0 OR a.status = 'accepted')
              ORDER BY a.period_to DESC, a.id DESC",
            [$tenantId, $userId]
        );

        // Reviewer queue: all submitted appraisals airline-wide awaiting review.
        $reviewerPending = [];
        if ($isReviewer) {
            $reviewerPending = Database::fetchAll(
                "SELECT a.*, us.name AS subject_name, ua.name AS appraiser_name
                   FROM appraisals a
                   JOIN users us ON a.subject_id = us.id
                   JOIN users ua ON a.appraiser_id = ua.id
                  WHERE a.tenant_id = ? AND a.status = 'submitted'
                  ORDER BY a.submitted_at DESC, a.id DESC",
                [$tenantId]
            );
        }

        // Buckets for the new tabbed UI.
        $myDrafts          = array_values(array_filter($mine, fn($a) => $a['status'] === 'draft'));
        $mySubmitted       = array_values(array_filter($mine, fn($a) => $a['status'] === 'submitted'));
        $myCompleted       = array_values(array_filter($mine, fn($a) => in_array($a['status'], ['accepted','reviewed'], true)));
        $aboutMeAccepted   = array_values(array_filter($aboutMe, fn($a) => $a['status'] === 'accepted'));

        // History = full timeline (mine + about me) sorted by period_to desc
        $history = array_merge(
            array_map(fn($a) => $a + ['_role' => 'appraiser'], $mine),
            array_map(fn($a) => $a + ['_role' => 'subject'],   $aboutMe)
        );
        usort($history, fn($a, $b) => strcmp($b['period_to'] ?? '', $a['period_to'] ?? ''));

        // Action queue: drafts I need to finish + (reviewer) submitted ones.
        $actionItems = array_merge(
            array_map(fn($a) => $a + ['_action' => 'finish_draft'], $myDrafts),
            array_map(fn($a) => $a + ['_action' => 'review'],       $reviewerPending)
        );

        // Stats for the overview cards.
        $stats = [
            'written_total'   => count($mine),
            'about_me_total'  => count($aboutMe),
            'pending_action'  => count($actionItems),
            'completed_total' => count($myCompleted) + count($aboutMeAccepted),
        ];

        $pageTitle    = 'Appraisals';
        $pageSubtitle = 'Performance records — Field Stations Staff Appraisal Form (Form No. 150, Rev 01)';

        // Tab to highlight (?tab=overview|pending|completed|action|history)
        $activeTab = $_GET['tab'] ?? 'overview';
        $allowedTabs = ['overview','pending','completed','action','history'];
        if (!in_array($activeTab, $allowedTabs, true)) $activeTab = 'overview';

        ob_start();
        require VIEWS_PATH . '/appraisals/index.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    // ─── New appraisal — chooser + form ─────────────────────────────────

    public function showCreate(): void {
        requireAuth();
        $tenantId = (int)currentTenantId();
        $me       = (int)currentUser()['id'];
        $myRoles  = array_column(UserModel::getRoles($me), 'slug');

        // Determine which form kinds the user is allowed to file:
        //  - self appraisal: any active crew member
        //  - peer appraisal: only if canAppraise() permits at least one other user
        $candidates = Database::fetchAll(
            "SELECT id, name FROM users
              WHERE tenant_id = ? AND id != ? AND status = 'active'
              ORDER BY name",
            [$tenantId, $me]
        );
        $subjects = [];
        foreach ($candidates as $c) {
            if (self::canAppraise($myRoles, (int)$c['id'])) {
                $subjects[] = $c;
            }
        }

        $kind = $_GET['kind'] ?? '';
        if (!in_array($kind, ['self','peer'], true)) {
            // Show the chooser only when the user actually has both options.
            $kind = '';
        }
        if ($kind === 'peer' && empty($subjects)) {
            flash('error', 'You are not authorised to write a peer appraisal in this airline.');
            redirect('/appraisals');
        }

        $attributes  = self::COMPETENCY_ATTRIBUTES;
        $ratingScale = self::RATING_LABELS;

        $pageTitle    = 'New Appraisal';
        $pageSubtitle = 'Field Stations Staff Appraisal Form (Form No. 150, Rev 01)';

        ob_start();
        require VIEWS_PATH . '/appraisals/create.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    // ─── Store ────────────────────────────────────────────────────────────

    public function store(): void {
        requireAuth();
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/appraisals/new');
        }

        $tenantId = (int)currentTenantId();
        $me       = (int)currentUser()['id'];
        $myRoles  = array_column(UserModel::getRoles($me), 'slug');
        $kind     = ($_POST['kind'] ?? 'peer') === 'self' ? 'self' : 'peer';

        // Self appraisal: subject = me. Peer appraisal: subject from form.
        $subjectId = $kind === 'self'
            ? $me
            : (int)($_POST['subject_id'] ?? 0);

        if ($subjectId <= 0) {
            flash('error', 'Select the crew member being appraised.');
            redirect('/appraisals/new?kind=' . $kind);
        }

        // Capability gate. Self appraisal is always allowed for the caller;
        // peer appraisal must clear canAppraise() against the chosen subject.
        if ($kind === 'peer') {
            if ($subjectId === $me) {
                flash('error', 'Use the Self Appraisal form to appraise yourself.');
                redirect('/appraisals/new?kind=peer');
            }
            if (!self::canAppraise($myRoles, $subjectId)) {
                flash('error', 'You are not authorised to appraise that crew member.');
                redirect('/appraisals/new?kind=peer');
            }
        }

        // Required-field validation. Both forms need a rotation reference and
        // a period; self forms additionally need at least one answer; peer
        // forms need either a rating or a written narrative.
        $rotationRef = trim((string)($_POST['rotation_ref'] ?? ''));
        $periodFrom  = trim((string)($_POST['period_from'] ?? ''));
        $periodTo    = trim((string)($_POST['period_to'] ?? ''));
        $errors = [];
        if ($rotationRef === '')                $errors[] = 'Rotation reference is required.';
        if ($periodFrom === '' || $periodTo === '') $errors[] = 'Appraisal period start and end dates are required.';
        if ($periodFrom !== '' && $periodTo !== '' && $periodFrom > $periodTo) {
            $errors[] = 'Period start date must be on or before the end date.';
        }
        if ($kind === 'self') {
            $hasAnswer = false;
            if (isset($_POST['answers']) && is_array($_POST['answers'])) {
                foreach ($_POST['answers'] as $ans) {
                    if (trim((string)$ans) !== '') { $hasAnswer = true; break; }
                }
            }
            if (!$hasAnswer) $errors[] = 'Answer at least one question before submitting.';
        } else {
            $hasRating   = isset($_POST['ratings']) && is_array($_POST['ratings'])
                           && count(array_filter($_POST['ratings'], fn($v) => (int)$v >= 1)) > 0;
            $hasNarrative = trim((string)($_POST['strengths'] ?? '')) !== ''
                         || trim((string)($_POST['improvements'] ?? '')) !== ''
                         || trim((string)($_POST['comments'] ?? '')) !== '';
            if (!$hasRating && !$hasNarrative) {
                $errors[] = 'Provide at least one competency rating or narrative comment.';
            }
        }
        if ($errors) {
            flash('error', implode(' ', $errors));
            redirect('/appraisals/new?kind=' . $kind);
        }

        // Per-attribute ratings (peer form). Coerced to ints in [1,5];
        // missing values are dropped so partial grids are still recorded.
        $ratings = [];
        $totalScore = 0;
        $countRated = 0;
        if ($kind === 'peer' && isset($_POST['ratings']) && is_array($_POST['ratings'])) {
            foreach (self::COMPETENCY_ATTRIBUTES as $key => $_) {
                $score = (int)($_POST['ratings'][$key] ?? 0);
                if ($score >= 1 && $score <= 5) {
                    $ratings[$key] = $score;
                    $totalScore   += $score;
                    $countRated++;
                }
            }
        }
        $ratingsJson = $ratings ? json_encode($ratings) : null;

        // Overall rating. Caller can pin it explicitly; otherwise we average
        // the per-attribute scores (rounded). Self appraisals don't have a
        // 1-5 scale on the paper form, so leave overall null unless given.
        $overall = isset($_POST['rating_overall']) && $_POST['rating_overall'] !== ''
            ? max(1, min(5, (int)$_POST['rating_overall']))
            : null;
        if ($overall === null && $kind === 'peer' && $countRated > 0) {
            $overall = max(1, min(5, (int)round($totalScore / $countRated)));
        }

        $status = ($_POST['status'] ?? 'submitted') === 'draft' ? 'draft' : 'submitted';

        // Self appraisals are always confidential by default — the appraisee
        // owns the record. Peer appraisals default to confidential too but
        // can be explicitly opened by the appraiser.
        $confidential = $kind === 'self' || isset($_POST['confidential']);

        // Self-form free-text answers are flattened into the comments column
        // so we don't break the existing schema. A future migration can split
        // them into a dedicated `appraisal_self_answers` table if needed.
        $extraComments = trim((string)($_POST['comments'] ?? ''));
        if ($kind === 'self' && isset($_POST['answers']) && is_array($_POST['answers'])) {
            $rendered = [];
            foreach ($_POST['answers'] as $idx => $ans) {
                $ans = trim((string)$ans);
                if ($ans !== '') {
                    $rendered[] = 'Q' . ((int)$idx + 1) . ': ' . $ans;
                }
            }
            if ($rendered) {
                $extraComments = trim($extraComments . "\n\n" . implode("\n\n", $rendered));
            }
        }

        $submittedAt = $status === 'submitted' ? date('Y-m-d H:i:s') : null;

        $newId = Database::insert(
            "INSERT INTO appraisals
                (tenant_id, subject_id, appraiser_id, rotation_ref, period_from, period_to,
                 status, rating_overall, ratings, strengths, improvements, comments,
                 confidential, submitted_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $tenantId, $subjectId, $me,
                trim((string)($_POST['rotation_ref'] ?? '')),
                $_POST['period_from'] ?: date('Y-m-d'),
                $_POST['period_to']   ?: date('Y-m-d'),
                $status,
                $overall,
                $ratingsJson,
                trim((string)($_POST['strengths'] ?? '')),
                trim((string)($_POST['improvements'] ?? '')),
                $extraComments,
                $confidential ? 1 : 0,
                $submittedAt,
            ]
        );

        AuditLog::log(
            'appraisal_created',
            'appraisal',
            (int)$newId,
            sprintf('%s appraisal — subject=%d rotation=%s status=%s',
                $kind, $subjectId, $_POST['rotation_ref'] ?? '', $status)
        );

        flash('success', $status === 'draft'
            ? 'Appraisal saved as draft.'
            : 'Appraisal submitted for review.');
        redirect('/appraisals');
    }

    // ─── Reviewer actions ─────────────────────────────────────────────────

    public function accept(int $id): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid request.');
            redirect('/appraisals');
        }
        RbacMiddleware::requireRole(self::REVIEWER_ROLES);
        Database::execute(
            "UPDATE appraisals
                SET status = 'accepted', reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP
              WHERE id = ? AND tenant_id = ?",
            [(int)currentUser()['id'], $id, (int)currentTenantId()]
        );
        AuditLog::log('appraisal_accepted', 'appraisal', $id, 'accepted');
        flash('success', 'Appraisal accepted.');
        redirect('/appraisals?tab=action');
    }

    // ─── Capability gate ──────────────────────────────────────────────────

    /**
     * Roles authorised to appraise OTHER crew members. A normal pilot /
     * cabin crew / engineer is intentionally NOT on this list — only crew
     * carrying training-captain, instructor, check-pilot or HR-style
     * capability may file a peer appraisal of someone else. Self-appraisal
     * is handled through the `kind=self` flow and bypasses this gate.
     *
     * If the airline later introduces explicit `training_captain` or
     * `check_pilot` role slugs, add them here. The matching iPad policy
     * lives in `AppraisalsView.swift::canAppraise`.
     */
    private const APPRAISER_ROLES = [
        'super_admin','airline_admin','hr','chief_pilot',
        'head_cabin_crew','training_admin','base_manager',
        'engineering_manager','director',
    ];

    /**
     * Can a user with $appraiserRoles write a *peer* appraisal about
     * $subjectId?
     *
     * Policy:
     *   - Only roles in self::APPRAISER_ROLES (leadership / training admin /
     *     check pilot / etc.) may appraise other crew.
     *   - A user can never appraise themselves through this gate — the
     *     `kind=self` flow handles self appraisals separately.
     *   - The subject must exist and live in the same tenant (the candidate
     *     pool is already tenant-scoped at the call site).
     *   - Platform-only users hold no airline roles and are blocked.
     */
    private static function canAppraise(array $appraiserRoles, int $subjectId): bool {
        if ($subjectId <= 0) return false;
        if ($subjectId === (int)currentUser()['id']) return false;
        if (!array_intersect($appraiserRoles, self::APPRAISER_ROLES)) return false;

        $subjectRoles = array_column(UserModel::getRoles($subjectId), 'slug');
        return !empty($subjectRoles);
    }

    /**
     * Does this user have anyone they're allowed to peer-appraise (for
     * surfacing the "+ New Appraisal" button on the index)? Self-only crew
     * still see the button because Self Appraisal is always available.
     */
    private static function canAppraiseAnyone(array $myRoles, int $tenantId, int $me): bool {
        // Self-appraisal is always available to active crew, so any crew
        // role enables the button.
        if (array_intersect($myRoles, ['pilot','cabin_crew','engineer'])) return true;

        if (!array_intersect($myRoles, self::APPRAISER_ROLES)) return false;

        $any = Database::fetch(
            "SELECT 1 AS one FROM users
              WHERE tenant_id = ? AND id != ? AND status = 'active' LIMIT 1",
            [$tenantId, $me]
        );
        return (bool)$any;
    }
}
