<?php
/**
 * OpsOne — My Submitted Safety Reports (Crew View)
 * Variables: $reports (array)
 */
$pageTitle    = 'My Safety Reports';
$pageSubtitle = 'Your submitted and closed reports';

$headerAction = '<a href="/safety" class="btn btn-primary btn-sm">＋ New Report</a>';

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
?>

<!-- Filter Tabs -->
<div class="nav-bar" style="margin-bottom:20px;">
    <?php
    $tabs = [
        'all'          => 'All',
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

<?php
// Filter the fetched reports by status tab
if ($filterStatus !== 'all') {
    $reports = array_filter($reports, fn($r) => ($r['status'] ?? '') === $filterStatus);
}
?>
<?php if (empty($reports)): ?>
    <div class="card">
        <div class="empty-state" style="padding:48px 0;">
            <div class="icon">📋</div>
            <h3>No Reports Found</h3>
            <p>You haven't submitted any safety reports yet, or none match this filter.</p>
            <a href="/safety" class="btn btn-primary btn-sm">Submit a Report</a>
        </div>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Ref No.</th>
                    <th>Report Type</th>
                    <th>Event Date</th>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th style="text-align:right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $r): ?>
                <tr>
                    <td style="font-family:monospace; font-weight:600; font-size:13px; color:var(--accent-blue);">
                        <?= e($r['reference_no'] ?? '—') ?>
                    </td>
                    <td style="font-size:12px; font-weight:600; color:var(--text-secondary);">
                        <?= e(ucwords(str_replace('_', ' ', $r['report_type'] ?? '—'))) ?>
                    </td>
                    <td class="text-sm text-muted">
                        <?= !empty($r['event_date']) ? date('d M Y', strtotime($r['event_date'])) : '—' ?>
                    </td>
                    <td style="font-weight:500;">
                        <?= e($r['title'] ?? '—') ?>
                        <?php if (!empty($r['is_anonymous'])): ?>
                            <span class="text-xs text-muted" title="Filed Anonymously"> 🕵️</span>
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
                        <a href="/safety/report/<?= (int)$r['id'] ?>" class="btn btn-outline btn-xs">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
