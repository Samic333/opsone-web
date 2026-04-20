<?php
/**
 * OpsOne — Safety Team Dashboard
 * Variables: $stats (array), $recentReports (array, 5 most recent submitted),
 *            $overdueActions (array), $pendingActions (array)
 */
$pageTitle    = 'Safety Dashboard';
$pageSubtitle = 'Overview of all safety activity for your airline';

// Status colour helper
function dashStatusColor(string $s): string {
    return match($s) {
        'submitted'          => '#3b82f6',
        'under_review'       => '#f59e0b',
        'investigation'      => '#ef4444',
        'action_in_progress' => '#8b5cf6',
        'closed'             => '#10b981',
        'reopened'           => '#f59e0b',
        default              => '#6b7280',
    };
}
?>

<!-- ═══════════════════════════════
     ROW 1 — Status Counter Cards (4 across)
     ═══════════════════════════════ -->
<div style="display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:20px;">

    <!-- Open Reports -->
    <div class="card" style="padding:20px 18px; border-bottom:3px solid #3b82f6; display:flex; flex-direction:column; gap:8px;">
        <div style="font-size:26px; line-height:1;">📋</div>
        <div style="font-size:36px; font-weight:800; color:#3b82f6; line-height:1;"><?= (int)($stats['open'] ?? 0) ?></div>
        <div style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted);">Open</div>
    </div>

    <!-- Under Review -->
    <div class="card" style="padding:20px 18px; border-bottom:3px solid #f59e0b; display:flex; flex-direction:column; gap:8px;">
        <div style="font-size:26px; line-height:1;">🔍</div>
        <div style="font-size:36px; font-weight:800; color:#f59e0b; line-height:1;"><?= (int)($stats['under_review'] ?? 0) ?></div>
        <div style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted);">Under Review</div>
    </div>

    <!-- Investigation -->
    <div class="card" style="padding:20px 18px; border-bottom:3px solid #ef4444; display:flex; flex-direction:column; gap:8px;">
        <div style="font-size:26px; line-height:1;">🔬</div>
        <div style="font-size:36px; font-weight:800; color:#ef4444; line-height:1;"><?= (int)($stats['investigation'] ?? 0) ?></div>
        <div style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted);">Investigation</div>
    </div>

    <!-- Action In Progress -->
    <div class="card" style="padding:20px 18px; border-bottom:3px solid #8b5cf6; display:flex; flex-direction:column; gap:8px;">
        <div style="font-size:26px; line-height:1;">⚙️</div>
        <div style="font-size:36px; font-weight:800; color:#8b5cf6; line-height:1;"><?= (int)($stats['action_in_progress'] ?? 0) ?></div>
        <div style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted);">Action In Progress</div>
    </div>

</div>

<!-- ═══════════════════════════════
     ROW 2 — Action Cards (3 across)
     ═══════════════════════════════ -->
<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:20px;">

    <!-- Overdue Actions -->
    <div class="card" style="padding:20px 18px; border-bottom:3px solid #ef4444; display:flex; flex-direction:column; gap:8px;">
        <div style="font-size:26px; line-height:1;">⏰</div>
        <div style="font-size:36px; font-weight:800; color:#ef4444; line-height:1;"><?= (int)($stats['overdue_actions'] ?? 0) ?></div>
        <div style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted);">Overdue Actions</div>
        <a href="/safety/team/actions?status=overdue" class="text-xs" style="color:#ef4444; text-decoration:none; font-weight:600;">View →</a>
    </div>

    <!-- Open Actions -->
    <div class="card" style="padding:20px 18px; border-bottom:3px solid #f97316; display:flex; flex-direction:column; gap:8px;">
        <div style="font-size:26px; line-height:1;">📌</div>
        <div style="font-size:36px; font-weight:800; color:#f97316; line-height:1;"><?= (int)($stats['open_actions'] ?? 0) ?></div>
        <div style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted);">Open Actions</div>
        <a href="/safety/team/actions?status=open" class="text-xs" style="color:#f97316; text-decoration:none; font-weight:600;">View →</a>
    </div>

    <!-- Closed This Month -->
    <div class="card" style="padding:20px 18px; border-bottom:3px solid #10b981; display:flex; flex-direction:column; gap:8px;">
        <div style="font-size:26px; line-height:1;">✅</div>
        <div style="font-size:36px; font-weight:800; color:#10b981; line-height:1;"><?= (int)($stats['closed_this_month'] ?? 0) ?></div>
        <div style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted);">Closed This Month</div>
    </div>

</div>

<!-- ═══════════════════════════════
     ROW 3 — Severity Breakdown
     ═══════════════════════════════ -->
<?php if (!empty($stats['by_severity'])): ?>
<div class="card" style="padding:20px 24px; margin-bottom:20px;">
    <h4 style="margin:0 0 16px; font-size:15px; font-weight:700; color:var(--text-primary);">Severity Breakdown</h4>
    <?php
    $sevOrder = [
        'negligible'  => ['label' => '1 — Negligible',   'color' => '#10b981'],
        'minor'       => ['label' => '2 — Minor',        'color' => '#65a30d'],
        'moderate'    => ['label' => '3 — Major',        'color' => '#d97706'],
        'significant' => ['label' => '4 — Hazardous',    'color' => '#ea580c'],
        'critical'    => ['label' => '5 — Catastrophic', 'color' => '#dc2626'],
    ];
    $sevData  = $stats['by_severity'];
    $maxCount = max(1, max(array_values($sevData)));
    foreach ($sevOrder as $key => $cfg):
        $count = (int)($sevData[$key] ?? 0);
        $pct   = round(($count / $maxCount) * 100);
    ?>
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:10px;">
        <div style="min-width:140px; font-size:12px; font-weight:600; color:var(--text-secondary);"><?= $cfg['label'] ?></div>
        <div style="flex:1; background:var(--bg-secondary,#f3f4f6); border-radius:4px; height:10px; overflow:hidden;">
            <div style="width:<?= $pct ?>%; height:100%; background:<?= $cfg['color'] ?>; border-radius:4px; transition:width 0.3s;"></div>
        </div>
        <div style="min-width:28px; text-align:right; font-size:13px; font-weight:700; color:<?= $cfg['color'] ?>;"><?= $count ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════
     ROW 4 — Two column layout
     ═══════════════════════════════ -->
<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">

    <!-- Left: Recent Reports -->
    <div class="card" style="padding:0; overflow:hidden;">
        <div style="padding:16px 20px 12px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between;">
            <h4 style="margin:0; font-size:15px; font-weight:700;">Recent Reports</h4>
            <a href="/safety/queue" class="text-xs" style="color:var(--accent-blue); text-decoration:none; font-weight:600;">View All →</a>
        </div>
        <?php if (empty($recentReports)): ?>
            <div style="padding:32px 20px; text-align:center; color:var(--text-muted);">
                <div style="font-size:28px; margin-bottom:8px;">📋</div>
                <p class="text-sm">No reports yet.</p>
            </div>
        <?php else: ?>
        <div class="table-wrap" style="border-radius:0;">
            <table style="border-radius:0;">
                <thead>
                    <tr>
                        <th style="font-size:11px;">Ref No.</th>
                        <th style="font-size:11px;">Type</th>
                        <th style="font-size:11px;">Reporter</th>
                        <th style="font-size:11px;">Status</th>
                        <th style="font-size:11px;">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($recentReports, 0, 5) as $r): ?>
                    <?php $sc = dashStatusColor($r['status'] ?? ''); ?>
                    <tr>
                        <td style="font-family:monospace; font-size:12px; font-weight:700;">
                            <a href="/safety/team/report/<?= (int)$r['id'] ?>" style="color:var(--accent-blue); text-decoration:none;"><?= e($r['reference_no'] ?? '—') ?></a>
                        </td>
                        <td style="font-size:11px; color:var(--text-secondary);"><?= e(ucwords(str_replace('_', ' ', $r['report_type'] ?? ''))) ?></td>
                        <td class="text-sm">
                            <?php if (!empty($r['is_anonymous'])): ?>
                                <span style="color:var(--text-muted); font-style:italic; font-size:11px;">Anonymous</span>
                            <?php else: ?>
                                <?= e($r['reporter_name'] ?? '—') ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge" style="--badge-color:<?= $sc ?>; font-size:10px;">
                                <?= ucfirst(str_replace('_', ' ', $r['status'] ?? '')) ?>
                            </span>
                        </td>
                        <td class="text-xs text-muted" style="white-space:nowrap;">
                            <?= !empty($r['created_at']) ? date('d M Y', strtotime($r['created_at'])) : '—' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right: Overdue Actions -->
    <div class="card" style="padding:0; overflow:hidden;">
        <div style="padding:16px 20px 12px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between;">
            <h4 style="margin:0; font-size:15px; font-weight:700;">⏰ Overdue Actions</h4>
            <a href="/safety/team/actions?status=overdue" class="text-xs" style="color:#ef4444; text-decoration:none; font-weight:600;">View All →</a>
        </div>
        <?php if (empty($overdueActions)): ?>
            <div style="padding:32px 20px; text-align:center; color:var(--text-muted);">
                <div style="font-size:28px; margin-bottom:8px;">✅</div>
                <p class="text-sm">No overdue actions.</p>
            </div>
        <?php else: ?>
        <div class="table-wrap" style="border-radius:0;">
            <table style="border-radius:0;">
                <thead>
                    <tr>
                        <th style="font-size:11px;">Action</th>
                        <th style="font-size:11px;">Report</th>
                        <th style="font-size:11px;">Assignee</th>
                        <th style="font-size:11px;">Due</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($overdueActions, 0, 5) as $a): ?>
                    <tr>
                        <td style="font-size:13px; font-weight:600; max-width:160px;">
                            <div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= e($a['title'] ?? '—') ?></div>
                        </td>
                        <td style="font-family:monospace; font-size:12px;">
                            <?php if (!empty($a['report_id'])): ?>
                                <a href="/safety/team/report/<?= (int)$a['report_id'] ?>" style="color:var(--accent-blue); text-decoration:none;"><?= e($a['reference_no'] ?? '—') ?></a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-sm">
                            <?php if (!empty($a['assigned_to_name'])): ?>
                                <?= e($a['assigned_to_name']) ?>
                            <?php elseif (!empty($a['assign_by_role'])): ?>
                                <span style="color:var(--text-muted); font-size:11px;">Role: <?= e($a['assign_by_role']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="color:#ef4444; font-weight:700; font-size:12px; white-space:nowrap;">
                            <?= !empty($a['due_date']) ? date('d M Y', strtotime($a['due_date'])) : '—' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>

<style>
@media (max-width: 900px) {
    /* Stack 4-col row to 2x2 */
    .dash-row1 { grid-template-columns: repeat(2,1fr) !important; }
    /* Stack 2-col row to 1 */
    .dash-row4 { grid-template-columns: 1fr !important; }
}
@media (max-width: 600px) {
    .dash-row1 { grid-template-columns: 1fr 1fr !important; }
    .dash-row2 { grid-template-columns: 1fr !important; }
}
</style>
