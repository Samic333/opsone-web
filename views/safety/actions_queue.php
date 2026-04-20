<?php
/**
 * OpsOne — Safety Corrective Actions Queue
 * Variables: $actions (array), $stats (array), $statusFilter (string)
 */
$pageTitle    = 'Corrective Actions';
$pageSubtitle = 'All safety corrective actions';

$statusFilter = $statusFilter ?? ($_GET['status'] ?? '');
?>

<!-- ═══════════════════════════════
     STATS ROW
     ═══════════════════════════════ -->
<div style="display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:20px;">

    <div class="card" style="padding:16px 18px; text-align:center;">
        <div style="font-size:11px; text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted); font-weight:600; margin-bottom:6px;">Open</div>
        <div style="font-size:30px; font-weight:800; color:#3b82f6;"><?= (int)($stats['open'] ?? 0) ?></div>
    </div>

    <div class="card" style="padding:16px 18px; text-align:center;">
        <div style="font-size:11px; text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted); font-weight:600; margin-bottom:6px;">In Progress</div>
        <div style="font-size:30px; font-weight:800; color:#f59e0b;"><?= (int)($stats['in_progress'] ?? 0) ?></div>
    </div>

    <div class="card" style="padding:16px 18px; text-align:center;">
        <div style="font-size:11px; text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted); font-weight:600; margin-bottom:6px;">Overdue</div>
        <div style="font-size:30px; font-weight:800; color:#ef4444;"><?= (int)($stats['overdue'] ?? 0) ?></div>
    </div>

    <div class="card" style="padding:16px 18px; text-align:center;">
        <div style="font-size:11px; text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted); font-weight:600; margin-bottom:6px;">Completed</div>
        <div style="font-size:30px; font-weight:800; color:#10b981;"><?= (int)($stats['completed'] ?? 0) ?></div>
    </div>

</div>

<!-- ═══════════════════════════════
     FILTER BAR (tab-style)
     ═══════════════════════════════ -->
<div style="display:flex; gap:0; margin-bottom:20px; border-bottom:1px solid var(--border);">
    <?php
    $filterTabs = [
        ''            => 'All',
        'open'        => 'Open',
        'in_progress' => 'In Progress',
        'overdue'     => 'Overdue',
        'completed'   => 'Completed',
    ];
    foreach ($filterTabs as $val => $lbl):
    $isActive = $statusFilter === $val;
    $activeStyle = $isActive
        ? 'background:var(--accent-blue,#3b82f6); color:#fff; border-color:var(--accent-blue,#3b82f6);'
        : 'background:transparent; color:var(--text-secondary); border-color:transparent;';
    ?>
    <a href="?status=<?= e($val) ?>" style="
        display:inline-block; padding:9px 18px;
        font-size:13px; font-weight:600; text-decoration:none;
        border-bottom:2px solid transparent;
        margin-bottom:-1px;
        <?= $isActive
            ? 'color:var(--accent-blue,#3b82f6); border-bottom-color:var(--accent-blue,#3b82f6);'
            : 'color:var(--text-secondary); border-bottom-color:transparent;' ?>
        transition:color 0.15s, border-color 0.15s;">
        <?= e($lbl) ?>
        <?php if ($val === 'overdue' && !empty($stats['overdue']) && (int)$stats['overdue'] > 0): ?>
            <span style="background:#ef4444; color:#fff; border-radius:10px; padding:1px 6px; font-size:10px; font-weight:700; margin-left:4px;"><?= (int)$stats['overdue'] ?></span>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- ═══════════════════════════════
     ACTIONS TABLE
     ═══════════════════════════════ -->
<?php if (empty($actions)): ?>
    <div class="card">
        <div class="empty-state" style="padding:48px 0;">
            <div class="icon">📋</div>
            <h3>No actions found</h3>
            <p>No actions found for this filter.</p>
        </div>
    </div>
<?php else: ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Action Title</th>
                <th>Report</th>
                <th>Assigned To</th>
                <th>Due Date</th>
                <th>Status</th>
                <th style="text-align:right; min-width:200px;">Update</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($actions as $act):
            $actStatus = $act['status'] ?? 'open';
            $actStatusColor = match($actStatus) {
                'open'        => '#3b82f6',
                'in_progress' => '#f59e0b',
                'completed'   => '#10b981',
                'overdue'     => '#ef4444',
                default       => '#6b7280',
            };
            $isOverdue = !empty($act['due_date'])
                         && strtotime($act['due_date']) < time()
                         && $actStatus !== 'completed';
            ?>
            <tr>
                <td>
                    <div style="font-size:13px; font-weight:600; max-width:220px;"><?= e($act['title'] ?? '—') ?></div>
                    <?php if (!empty($act['description'])): ?>
                        <div class="text-xs text-muted" style="margin-top:3px; max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= e($act['description']) ?></div>
                    <?php endif; ?>
                </td>
                <td style="font-family:monospace; font-size:12px; white-space:nowrap;">
                    <?php if (!empty($act['report_id'])): ?>
                        <a href="/safety/team/report/<?= (int)$act['report_id'] ?>" style="color:var(--accent-blue); text-decoration:none; font-weight:700;"><?= e($act['reference_no'] ?? '#' . $act['report_id']) ?></a>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td class="text-sm">
                    <?php if (!empty($act['assigned_to_name'])): ?>
                        <?= e($act['assigned_to_name']) ?>
                    <?php elseif (!empty($act['assign_by_role'])): ?>
                        <span style="font-size:11px; color:var(--text-muted);">Role: <?= e($act['assign_by_role']) ?></span>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td style="white-space:nowrap; <?= $isOverdue ? 'color:#ef4444; font-weight:700;' : '' ?> font-size:13px;">
                    <?= !empty($act['due_date']) ? date('d M Y', strtotime($act['due_date'])) : '—' ?>
                    <?php if ($isOverdue): ?>
                        <span style="display:block; font-size:10px; margin-top:1px;">⏰ OVERDUE</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="status-badge" style="--badge-color:<?= $actStatusColor ?>;">
                        <?= ucfirst(str_replace('_', ' ', $actStatus)) ?>
                    </span>
                </td>
                <td style="text-align:right;">
                    <form method="POST" action="/safety/team/action/<?= (int)$act['id'] ?>/update"
                          style="display:inline-flex; align-items:center; gap:6px; justify-content:flex-end;">
                        <?= csrfField() ?>
                        <select name="status" class="form-control" style="width:auto; font-size:12px; padding:5px 8px;">
                            <option value="open"        <?= $actStatus === 'open'        ? 'selected' : '' ?>>Open</option>
                            <option value="in_progress" <?= $actStatus === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="completed"   <?= $actStatus === 'completed'   ? 'selected' : '' ?>>Completed</option>
                        </select>
                        <button type="submit" class="btn btn-outline btn-xs">Update</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
