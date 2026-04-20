<?php
/**
 * OpsOne — My Submitted Safety Reports (Crew View)
 * Variables: $reports (array with has_pending_reply flag), $pendingReplies (int)
 */
$pageTitle    = 'My Safety Reports';
$pageSubtitle = 'Your submitted and closed reports';

$headerAction = '<a href="/safety/select-type" class="btn btn-primary btn-sm">＋ New Report</a>';

$filterStatus = $_GET['status'] ?? 'all';

// Status badge colour helper
$statusColor = function(string $status): string {
    return match($status) {
        'draft'              => '#6b7280',
        'submitted'          => '#3b82f6',
        'under_review'       => '#f59e0b',
        'investigation'      => '#ef4444',
        'action_in_progress' => '#8b5cf6',
        'closed'             => '#10b981',
        'reopened'           => '#f59e0b',
        default              => '#6b7280',
    };
};

// Filter
$filteredReports = $reports;
if ($filterStatus !== 'all') {
    $filteredReports = array_filter($reports, fn($r) => ($r['status'] ?? '') === $filterStatus);
}
$pendingReplies = $pendingReplies ?? count(array_filter($reports ?? [], fn($r) => !empty($r['has_pending_reply'])));
?>

<?php if ($pendingReplies > 0): ?>
<!-- Banner: replies waiting -->
<div style="
    display:flex; align-items:center; gap:12px;
    background:rgba(245,158,11,0.08); border:1px solid rgba(245,158,11,0.35);
    border-radius:var(--radius-md); padding:12px 18px; margin-bottom:18px;">
    <span style="font-size:18px; flex-shrink:0;">💬</span>
    <p class="text-sm" style="margin:0; color:var(--text-primary); line-height:1.4; font-weight:600;">
        The safety team has replied to <?= $pendingReplies ?> of your report<?= $pendingReplies !== 1 ? 's' : '' ?>.
        <span style="font-weight:400; color:var(--text-muted);">Look for the <strong style="color:#f59e0b;">💬 Reply needed</strong> badge below and click the report to respond.</span>
    </p>
</div>
<?php endif; ?>

<!-- Filter Tabs -->
<div class="nav-bar" style="margin-bottom:20px;">
    <?php
    $tabs = [
        'all'          => 'All',
        'submitted'    => 'Submitted',
        'under_review' => 'Under Review',
        'investigation'=> 'Investigation',
        'closed'       => 'Closed',
    ];
    foreach ($tabs as $slug => $label):
    ?>
    <a href="/safety/my-reports?status=<?= $slug ?>"
       class="btn btn-sm <?= $filterStatus === $slug ? 'btn-primary' : 'btn-ghost' ?>">
        <?= $label ?>
    </a>
    <?php endforeach; ?>
</div>

<?php if (empty($filteredReports)): ?>
    <div class="card">
        <div class="empty-state" style="padding:48px 0;">
            <div class="icon">📋</div>
            <h3>No Reports Found</h3>
            <p>You haven't submitted any safety reports yet, or none match this filter.</p>
            <a href="/safety/select-type" class="btn btn-primary btn-sm">Submit a Report</a>
        </div>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Ref No.</th>
                    <th>Type</th>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th style="text-align:right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filteredReports as $r):
                    $hasPending = !empty($r['has_pending_reply']);
                    $rowBg      = $hasPending ? 'background:rgba(245,158,11,0.04);' : '';
                ?>
                <tr style="<?= $rowBg ?>">
                    <td style="font-family:monospace; font-weight:700; font-size:13px; color:var(--accent-blue);">
                        <?= e($r['reference_no'] ?? '—') ?>
                    </td>
                    <td style="font-size:12px; font-weight:600; color:var(--text-secondary);">
                        <?= e(ucwords(str_replace('_', ' ', $r['report_type'] ?? '—'))) ?>
                    </td>
                    <td style="font-weight:500;">
                        <?= e($r['title'] ?? '—') ?>
                        <?php if (!empty($r['is_anonymous'])): ?>
                            <span class="text-xs text-muted" title="Filed Anonymously"> 🕵️</span>
                        <?php endif; ?>
                        <?php if ($hasPending): ?>
                            <span style="
                                display:inline-flex; align-items:center; gap:4px;
                                margin-left:8px; padding:2px 8px; border-radius:20px;
                                background:rgba(245,158,11,0.12); border:1px solid rgba(245,158,11,0.35);
                                font-size:10px; font-weight:700; color:#b45309; white-space:nowrap;">
                                💬 Reply needed
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php $sc = $statusColor($r['status'] ?? ''); ?>
                        <span class="status-badge" style="--badge-color:<?= $sc ?>;">
                            <?= ucfirst(str_replace('_', ' ', $r['status'] ?? 'unknown')) ?>
                        </span>
                    </td>
                    <td class="text-sm text-muted">
                        <?= !empty($r['created_at']) ? date('d M Y', strtotime($r['created_at'])) : '—' ?>
                    </td>
                    <td style="text-align:right;">
                        <?php
                        // Link directly to Discussion tab if there's a pending reply
                        $detailUrl = '/safety/report/' . (int)$r['id'] . ($hasPending ? '?tab=discussion' : '');
                        ?>
                        <a href="<?= $detailUrl ?>" class="btn <?= $hasPending ? 'btn-primary' : 'btn-outline' ?> btn-xs">
                            <?= $hasPending ? '💬 Reply' : 'View' ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
