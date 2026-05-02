<?php
/**
 * Appraisals — index
 *
 * Tabs:
 *   - overview   summary stats + recent activity
 *   - pending    drafts I need to finish + (reviewer) submitted queue
 *   - completed  accepted appraisals (mine + about me)
 *   - action     same as pending but framed as "requires my action"
 *   - history    full timeline (mine + about me)
 *
 * Variables in scope (set by AppraisalController::index):
 *   $stats, $mine, $aboutMe, $reviewerPending, $myDrafts, $mySubmitted,
 *   $myCompleted, $aboutMeAccepted, $history, $actionItems,
 *   $isReviewer, $canCreate, $activeTab
 */

$tabs = [
    'overview'  => ['label' => 'Overview',         'count' => null],
    'pending'   => ['label' => 'Pending',          'count' => count($myDrafts) + count($mySubmitted) + count($reviewerPending)],
    'completed' => ['label' => 'Completed',        'count' => count($myCompleted) + count($aboutMeAccepted)],
    'action'    => ['label' => 'Action Required',  'count' => count($actionItems)],
    'history'   => ['label' => 'History',          'count' => count($history)],
];

/** Render a single appraisal as a row. */
$renderRow = function (array $a, string $context = 'mine') {
    $rating = $a['rating_overall'] ? str_repeat('★', (int)$a['rating_overall']) : '—';
    $primary = $context === 'about_me'
        ? ($a['appraiser_name'] ?? 'Appraiser')
        : ($a['subject_name'] ?? 'Subject');
    $primaryLabel = $context === 'about_me' ? 'Appraiser' : 'Subject';
    ?>
    <tr>
        <td>
            <div style="font-weight:600;"><?= e($primary) ?></div>
            <div class="text-xs text-muted"><?= e($primaryLabel) ?></div>
        </td>
        <td class="text-sm"><?= e($a['rotation_ref'] ?: '—') ?></td>
        <td class="text-sm"><?= e($a['period_from']) ?> → <?= e($a['period_to']) ?></td>
        <td><?= $rating ?></td>
        <td><?= statusBadge($a['status']) ?></td>
    </tr>
    <?php
};
?>

<div class="page-header" style="display:flex; align-items:flex-end; justify-content:space-between; gap:16px; margin-bottom:20px; flex-wrap:wrap;">
    <div>
        <h1 style="margin:0; font-size:24px; letter-spacing:-0.02em;">Appraisals</h1>
        <p class="text-muted" style="margin:6px 0 0;">
            Field Stations Staff Appraisal Form (Form No. 150, Rev 01).
            Self appraisals and peer appraisals share one record store with the iPad app.
        </p>
    </div>
    <?php if ($canCreate): ?>
        <a href="/appraisals/new" class="btn btn-primary">+ New Appraisal</a>
    <?php endif; ?>
</div>

<!-- Overview stat cards (always visible, frame the page) -->
<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-label">Written by me</div>
        <div class="stat-value"><?= (int)$stats['written_total'] ?></div>
    </div>
    <div class="stat-card cyan">
        <div class="stat-label">About me</div>
        <div class="stat-value"><?= (int)$stats['about_me_total'] ?></div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-label">Action required</div>
        <div class="stat-value"><?= (int)$stats['pending_action'] ?></div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Completed</div>
        <div class="stat-value"><?= (int)$stats['completed_total'] ?></div>
    </div>
</div>

<!-- Tab navigation -->
<div class="filter-tabs" role="tablist">
    <?php foreach ($tabs as $key => $t): ?>
        <a href="/appraisals?tab=<?= e($key) ?>"
           class="filter-tab <?= $activeTab === $key ? 'active' : '' ?>"
           role="tab"
           aria-selected="<?= $activeTab === $key ? 'true' : 'false' ?>">
            <?= e($t['label']) ?>
            <?php if (!is_null($t['count']) && $t['count'] > 0): ?>
                <span class="text-xs" style="margin-left:6px; opacity:0.85;">(<?= (int)$t['count'] ?>)</span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if ($activeTab === 'overview'): ?>

    <div class="card" style="margin-bottom:16px;">
        <h3 style="margin-top:0;">Recent appraisals about me</h3>
        <?php if (empty($aboutMe)): ?>
            <p class="text-muted">No accepted or open appraisals about you yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Appraiser</th><th>Rotation</th><th>Period</th><th>Rating</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($aboutMe, 0, 5) as $a) $renderRow($a, 'about_me'); ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="card" style="margin-bottom:16px;">
        <h3 style="margin-top:0;">Recent appraisals I've written</h3>
        <?php if (empty($mine)): ?>
            <p class="text-muted">You haven't filed any appraisals yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Subject</th><th>Rotation</th><th>Period</th><th>Rating</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($mine, 0, 5) as $a) $renderRow($a, 'mine'); ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<?php elseif ($activeTab === 'pending'): ?>

    <?php if (!empty($myDrafts)): ?>
        <div class="card" style="margin-bottom:16px;">
            <h3 style="margin-top:0;">My drafts</h3>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Subject</th><th>Rotation</th><th>Period</th><th>Rating</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($myDrafts as $a) $renderRow($a, 'mine'); ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($mySubmitted)): ?>
        <div class="card" style="margin-bottom:16px;">
            <h3 style="margin-top:0;">Submitted by me — awaiting review</h3>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Subject</th><th>Rotation</th><th>Period</th><th>Rating</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($mySubmitted as $a) $renderRow($a, 'mine'); ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($isReviewer && !empty($reviewerPending)): ?>
        <div class="card" style="margin-bottom:16px;">
            <h3 style="margin-top:0;">Reviewer queue</h3>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Subject</th><th>Appraiser</th><th>Rotation</th><th>Period</th><th>Rating</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($reviewerPending as $a): ?>
                        <tr>
                            <td><?= e($a['subject_name']) ?></td>
                            <td><?= e($a['appraiser_name']) ?></td>
                            <td class="text-sm"><?= e($a['rotation_ref'] ?: '—') ?></td>
                            <td class="text-sm"><?= e($a['period_from']) ?> → <?= e($a['period_to']) ?></td>
                            <td><?= $a['rating_overall'] ? str_repeat('★', (int)$a['rating_overall']) : '—' ?></td>
                            <td>
                                <form method="POST" action="/appraisals/<?= (int)$a['id'] ?>/accept" style="display:inline;">
                                    <?= csrfField() ?><button class="btn btn-xs btn-success" type="submit">Accept</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($myDrafts) && empty($mySubmitted) && (!$isReviewer || empty($reviewerPending))): ?>
        <div class="card empty-state">
            <h3>Nothing pending</h3>
            <p>You have no drafts in progress and no submitted appraisals waiting on review.</p>
        </div>
    <?php endif; ?>

<?php elseif ($activeTab === 'completed'): ?>

    <div class="card" style="margin-bottom:16px;">
        <h3 style="margin-top:0;">Accepted appraisals about me</h3>
        <?php if (empty($aboutMeAccepted)): ?>
            <p class="text-muted">No accepted appraisals about you yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Appraiser</th><th>Rotation</th><th>Period</th><th>Rating</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($aboutMeAccepted as $a) $renderRow($a, 'about_me'); ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Accepted / reviewed appraisals I've written</h3>
        <?php if (empty($myCompleted)): ?>
            <p class="text-muted">None of your appraisals have been accepted yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Subject</th><th>Rotation</th><th>Period</th><th>Rating</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($myCompleted as $a) $renderRow($a, 'mine'); ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<?php elseif ($activeTab === 'action'): ?>

    <?php if (empty($actionItems)): ?>
        <div class="card empty-state">
            <h3>You're all caught up</h3>
            <p>No appraisals are waiting on action from you.</p>
        </div>
    <?php else: ?>
        <div class="card">
            <h3 style="margin-top:0;">Requires your action</h3>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>What</th><th>Subject</th><th>Rotation</th><th>Period</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($actionItems as $a):
                        $isReview = ($a['_action'] ?? '') === 'review'; ?>
                        <tr>
                            <td>
                                <span class="status-badge" style="--badge-color: <?= $isReview ? '#3b82f6' : '#f59e0b' ?>">
                                    <?= $isReview ? 'Review' : 'Finish draft' ?>
                                </span>
                            </td>
                            <td><?= e($a['subject_name']) ?></td>
                            <td class="text-sm"><?= e($a['rotation_ref'] ?: '—') ?></td>
                            <td class="text-sm"><?= e($a['period_from']) ?> → <?= e($a['period_to']) ?></td>
                            <td><?= statusBadge($a['status']) ?></td>
                            <td>
                                <?php if ($isReview): ?>
                                    <form method="POST" action="/appraisals/<?= (int)$a['id'] ?>/accept" style="display:inline;">
                                        <?= csrfField() ?><button class="btn btn-xs btn-success" type="submit">Accept</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-xs text-muted">Draft #<?= (int)$a['id'] ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

<?php elseif ($activeTab === 'history'): ?>

    <?php if (empty($history)): ?>
        <div class="card empty-state">
            <h3>No appraisal history yet</h3>
            <p>Your timeline will populate once you write or receive an appraisal.</p>
        </div>
    <?php else: ?>
        <div class="card">
            <h3 style="margin-top:0;">Full timeline</h3>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Role</th><th>Counterparty</th><th>Rotation</th><th>Period</th><th>Rating</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($history as $a):
                        $asAppraiser = ($a['_role'] ?? '') === 'appraiser';
                        $counter = $asAppraiser ? ($a['subject_name'] ?? '') : ($a['appraiser_name'] ?? ''); ?>
                        <tr>
                            <td>
                                <span class="status-badge" style="--badge-color: <?= $asAppraiser ? '#7c3aed' : '#0ea5e9' ?>">
                                    <?= $asAppraiser ? 'Wrote' : 'Received' ?>
                                </span>
                            </td>
                            <td><?= e($counter) ?></td>
                            <td class="text-sm"><?= e($a['rotation_ref'] ?: '—') ?></td>
                            <td class="text-sm"><?= e($a['period_from']) ?> → <?= e($a['period_to']) ?></td>
                            <td><?= $a['rating_overall'] ? str_repeat('★', (int)$a['rating_overall']) : '—' ?></td>
                            <td><?= statusBadge($a['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

<?php endif; ?>
