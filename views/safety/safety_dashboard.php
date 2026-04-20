<?php
/**
 * OpsOne — Safety Team Dashboard
 * Variables: $stats (array), $recentReports (array), $overdueActions (array), $pendingActions (array)
 *
 * $stats keys:
 *   total, open, closed, by_status (map), by_type (map), overdue_actions, open_actions, by_severity (map)
 *
 * Helpers for accessing individual statuses:
 *   $stats['by_status']['submitted']          — submitted count
 *   $stats['by_status']['under_review']       — under review count
 *   $stats['by_status']['investigation']      — investigation count
 *   $stats['by_status']['action_in_progress'] — action in progress count
 */

// Shorthand helpers
$byStatus   = $stats['by_status'] ?? [];
$submitted  = (int)($byStatus['submitted']          ?? 0);
$underRev   = (int)($byStatus['under_review']       ?? 0);
$invest     = (int)($byStatus['investigation']      ?? 0);
$actionProg = (int)($byStatus['action_in_progress'] ?? 0);
$closedN    = (int)($stats['closed']                ?? 0);
$overdueA   = (int)($stats['overdue_actions']       ?? 0);
$openA      = (int)($stats['open_actions']          ?? 0);

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

// Quick-action short cuts for the header
$queueFilters = [
    ['url' => '/safety/queue',                        'label' => 'All Reports',  'style' => 'outline'],
    ['url' => '/safety/queue?status=submitted',       'label' => '+ Submitted',  'style' => $submitted  ? 'primary' : 'outline'],
    ['url' => '/safety/queue?status=under_review',    'label' => 'Under Review', 'style' => $underRev   ? 'warning' : 'outline'],
    ['url' => '/safety/queue?status=investigation',   'label' => 'Investigation','style' => $invest     ? 'danger'  : 'outline'],
];
?>

<!-- ═══════════════════════════════════════════
     PAGE HEADER with quick-filter chips
     ═══════════════════════════════════════════ -->
<div style="display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:20px;">
    <div>
        <h2 style="margin:0 0 2px; font-size:20px; font-weight:800; color:var(--text-primary);">Safety Dashboard</h2>
        <p class="text-sm text-muted" style="margin:0;">Overview of all safety activity for your airline.</p>
    </div>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <?php foreach ($queueFilters as $f):
            $btnStyle = match($f['style']) {
                'primary' => 'background:#3b82f6; color:#fff; border-color:#3b82f6;',
                'warning' => 'background:#f59e0b; color:#fff; border-color:#f59e0b;',
                'danger'  => 'background:#ef4444; color:#fff; border-color:#ef4444;',
                default   => '',
            };
        ?>
        <a href="<?= e($f['url']) ?>" class="btn btn-outline btn-sm" style="<?= $btnStyle ?>">
            <?= e($f['label']) ?>
        </a>
        <?php endforeach; ?>
        <a href="/safety/select-type" class="btn btn-primary btn-sm">+ Submit Report</a>
    </div>
</div>

<!-- ═══════════════════════════════════════════
     ROW 1 — Compact 6-stat counter strip
     ═══════════════════════════════════════════ -->
<div style="display:grid; grid-template-columns:repeat(6,1fr); gap:10px; margin-bottom:20px;">

    <?php
    $counters = [
        ['label' => 'Submitted',       'val' => $submitted,  'color' => '#3b82f6', 'url' => '/safety/queue?status=submitted'],
        ['label' => 'Under Review',    'val' => $underRev,   'color' => '#f59e0b', 'url' => '/safety/queue?status=under_review'],
        ['label' => 'Investigation',   'val' => $invest,     'color' => '#ef4444', 'url' => '/safety/queue?status=investigation'],
        ['label' => 'Action In Prog.', 'val' => $actionProg, 'color' => '#8b5cf6', 'url' => '/safety/queue?status=action_in_progress'],
        ['label' => 'Overdue Actions', 'val' => $overdueA,   'color' => '#dc2626', 'url' => '/safety/team/actions?status=overdue'],
        ['label' => 'Closed',          'val' => $closedN,    'color' => '#10b981', 'url' => '/safety/queue?status=closed'],
    ];
    foreach ($counters as $c):
    ?>
    <a href="<?= e($c['url']) ?>" style="text-decoration:none;">
        <div class="card" style="
            padding:12px 14px;
            border-top:3px solid <?= $c['color'] ?>;
            display:flex; flex-direction:column; gap:4px;
            transition:box-shadow 0.15s, transform 0.15s;"
            onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.1)'; this.style.transform='translateY(-1px)'"
            onmouseout="this.style.boxShadow=''; this.style.transform=''">
            <div style="font-size:26px; font-weight:800; color:<?= $c['color'] ?>; line-height:1.1;"><?= $c['val'] ?></div>
            <div style="font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted); line-height:1.2;"><?= e($c['label']) ?></div>
        </div>
    </a>
    <?php endforeach; ?>

</div>

<?php
// Alert bar: show if any overdue actions exist
if ($overdueA > 0):
?>
<!-- ═══════════════════════════════════════════
     ALERT — Overdue actions banner
     ═══════════════════════════════════════════ -->
<div style="
    display:flex; align-items:center; gap:12px;
    background:rgba(220,38,38,0.07); border:1px solid rgba(220,38,38,0.25);
    border-radius:var(--radius-md); padding:12px 18px; margin-bottom:20px;">
    <span style="font-size:18px; flex-shrink:0;">⏰</span>
    <p class="text-sm" style="margin:0; color:#b91c1c; font-weight:600; line-height:1.4;">
        <?= $overdueA ?> corrective action<?= $overdueA !== 1 ? 's are' : ' is' ?> overdue.
        <a href="/safety/team/actions?status=overdue" style="color:#dc2626; text-decoration:underline; margin-left:4px;">Review now →</a>
    </p>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════
     ROW 2 — Recent Reports | Overdue Actions  (primary operational section)
     ═══════════════════════════════════════════ -->
<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">

    <!-- LEFT: Recent Reports -->
    <div class="card" style="padding:0; overflow:hidden;">
        <div style="padding:14px 18px 10px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between;">
            <h4 style="margin:0; font-size:14px; font-weight:700;">📋 Recent Reports</h4>
            <a href="/safety/queue" class="text-xs" style="color:var(--accent-blue); text-decoration:none; font-weight:600;">View All →</a>
        </div>
        <?php if (empty($recentReports)): ?>
            <div style="padding:28px 18px; text-align:center; color:var(--text-muted);">
                <div style="font-size:26px; margin-bottom:6px;">📋</div>
                <p class="text-sm">No reports yet.</p>
            </div>
        <?php else: ?>
        <div class="table-wrap" style="border-radius:0;">
            <table style="border-radius:0;">
                <thead>
                    <tr>
                        <th style="font-size:10px;">Ref</th>
                        <th style="font-size:10px;">Type</th>
                        <th style="font-size:10px;">Reporter</th>
                        <th style="font-size:10px;">Status</th>
                        <th style="font-size:10px;">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($recentReports, 0, 8) as $r): ?>
                    <?php $sc = dashStatusColor($r['status'] ?? ''); ?>
                    <tr>
                        <td style="font-family:monospace; font-size:11px; font-weight:700;">
                            <a href="/safety/team/report/<?= (int)$r['id'] ?>" style="color:var(--accent-blue); text-decoration:none;"><?= e($r['reference_no'] ?? '—') ?></a>
                        </td>
                        <td style="font-size:10px; color:var(--text-secondary);"><?= e(ucwords(str_replace('_', ' ', $r['report_type'] ?? ''))) ?></td>
                        <td class="text-xs">
                            <?php if (!empty($r['is_anonymous'])): ?>
                                <span style="color:var(--text-muted); font-style:italic;">Anon.</span>
                            <?php else: ?>
                                <?= e($r['reporter_name'] ?? '—') ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge" style="--badge-color:<?= $sc ?>; font-size:9px; padding:2px 7px;">
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

    <!-- RIGHT: Overdue Actions -->
    <div class="card" style="padding:0; overflow:hidden;">
        <div style="padding:14px 18px 10px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between;">
            <h4 style="margin:0; font-size:14px; font-weight:700; color:<?= $overdueA > 0 ? '#dc2626' : 'var(--text-primary)' ?>;">⏰ Overdue Actions</h4>
            <a href="/safety/team/actions?status=overdue" class="text-xs" style="color:#ef4444; text-decoration:none; font-weight:600;">View All →</a>
        </div>
        <?php if (empty($overdueActions)): ?>
            <div style="padding:28px 18px; text-align:center; color:var(--text-muted);">
                <div style="font-size:26px; margin-bottom:6px;">✅</div>
                <p class="text-sm">No overdue actions.</p>
            </div>
        <?php else: ?>
        <div class="table-wrap" style="border-radius:0;">
            <table style="border-radius:0;">
                <thead>
                    <tr>
                        <th style="font-size:10px;">Action</th>
                        <th style="font-size:10px;">Report</th>
                        <th style="font-size:10px;">Assignee</th>
                        <th style="font-size:10px;">Due</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($overdueActions, 0, 8) as $a): ?>
                    <tr>
                        <td style="font-size:12px; font-weight:600; max-width:140px;">
                            <div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= e($a['title'] ?? '—') ?></div>
                        </td>
                        <td style="font-family:monospace; font-size:11px;">
                            <?php if (!empty($a['report_id'])): ?>
                                <a href="/safety/team/report/<?= (int)$a['report_id'] ?>" style="color:var(--accent-blue); text-decoration:none;"><?= e($a['reference_no'] ?? '—') ?></a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-xs">
                            <?php if (!empty($a['assigned_to_name'])): ?>
                                <?= e($a['assigned_to_name']) ?>
                            <?php elseif (!empty($a['assign_by_role'])): ?>
                                <span style="color:var(--text-muted); font-size:10px;">Role: <?= e($a['assign_by_role']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="color:#dc2626; font-weight:700; font-size:11px; white-space:nowrap;">
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

<!-- ═══════════════════════════════════════════
     ROW 3 — Open Pending Actions strip
     ═══════════════════════════════════════════ -->
<?php if (!empty($pendingActions)): ?>
<div class="card" style="padding:0; overflow:hidden; margin-bottom:20px;">
    <div style="padding:12px 18px 10px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between;">
        <h4 style="margin:0; font-size:14px; font-weight:700;">📌 Open Corrective Actions (<?= count($pendingActions) ?>)</h4>
        <a href="/safety/team/actions?status=open" class="text-xs" style="color:#f97316; text-decoration:none; font-weight:600;">View All →</a>
    </div>
    <div class="table-wrap" style="border-radius:0;">
        <table style="border-radius:0;">
            <thead>
                <tr>
                    <th style="font-size:10px;">Action</th>
                    <th style="font-size:10px;">Report</th>
                    <th style="font-size:10px;">Assignee</th>
                    <th style="font-size:10px;">Due</th>
                    <th style="font-size:10px;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($pendingActions, 0, 5) as $a):
                    $actStatus = $a['status'] ?? 'open';
                    $actColor  = $actStatus === 'in_progress' ? '#f59e0b' : '#3b82f6';
                ?>
                <tr>
                    <td style="font-size:12px; font-weight:600; max-width:160px;">
                        <div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= e($a['title'] ?? '—') ?></div>
                    </td>
                    <td style="font-family:monospace; font-size:11px;">
                        <?php if (!empty($a['report_id'])): ?>
                            <a href="/safety/team/report/<?= (int)$a['report_id'] ?>" style="color:var(--accent-blue); text-decoration:none;"><?= e($a['reference_no'] ?? '—') ?></a>
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                    <td class="text-xs"><?= e($a['assigned_to_name'] ?? $a['assign_by_role'] ?? '—') ?></td>
                    <td style="font-size:11px; white-space:nowrap; color:var(--text-secondary);">
                        <?= !empty($a['due_date']) ? date('d M Y', strtotime($a['due_date'])) : '—' ?>
                    </td>
                    <td>
                        <span class="status-badge" style="--badge-color:<?= $actColor ?>; font-size:9px; padding:2px 7px;">
                            <?= ucfirst(str_replace('_', ' ', $actStatus)) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════
     ROW 4 — Severity Breakdown (lower priority, condensed)
     ═══════════════════════════════════════════ -->
<?php if (!empty($stats['by_severity'])): ?>
<div class="card" style="padding:16px 20px;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
        <h4 style="margin:0; font-size:13px; font-weight:700; color:var(--text-primary);">Severity Breakdown</h4>
        <span class="text-xs text-muted">Reporter self-assessment</span>
    </div>
    <?php
    $sevOrder = [
        'critical'    => ['label' => 'Catastrophic (5)', 'color' => '#dc2626'],
        'significant' => ['label' => 'Hazardous (4)',    'color' => '#ea580c'],
        'moderate'    => ['label' => 'Major (3)',         'color' => '#d97706'],
        'minor'       => ['label' => 'Minor (2)',         'color' => '#65a30d'],
        'negligible'  => ['label' => 'Negligible (1)',   'color' => '#10b981'],
    ];
    $sevData  = $stats['by_severity'];
    $maxCount = max(1, max(array_values($sevData) ?: [1]));
    foreach ($sevOrder as $key => $cfg):
        $count = (int)($sevData[$key] ?? 0);
        $pct   = round(($count / $maxCount) * 100);
    ?>
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:7px;">
        <div style="min-width:120px; font-size:11px; font-weight:600; color:var(--text-secondary);"><?= $cfg['label'] ?></div>
        <div style="flex:1; background:var(--bg-secondary,#f3f4f6); border-radius:3px; height:8px; overflow:hidden;">
            <div style="width:<?= $pct ?>%; height:100%; background:<?= $cfg['color'] ?>; border-radius:3px;"></div>
        </div>
        <div style="min-width:24px; text-align:right; font-size:12px; font-weight:700; color:<?= $cfg['color'] ?>;"><?= $count ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<style>
@media (max-width: 1100px) {
    /* 6-col strip → 3×2 */
    .dash-counters { grid-template-columns: repeat(3,1fr) !important; }
}
@media (max-width: 800px) {
    /* Main panels stack */
    .dash-main-row { grid-template-columns: 1fr !important; }
    /* 6-col → 2×3 */
    .dash-counters { grid-template-columns: repeat(2,1fr) !important; }
}
@media (max-width: 480px) {
    .dash-counters { grid-template-columns: repeat(2,1fr) !important; }
}
</style>
