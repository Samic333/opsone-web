<?php
/**
 * OpsOne — Safety Team Queue / Inbox
 * Variables: $reports (array), $stats (array), $filters (array)
 */
$pageTitle    = 'Safety Queue';
$pageSubtitle = 'Manage incoming reports, investigations, and closures';

$headerAction = '<a href="/safety/settings" class="btn btn-ghost btn-sm">⚙ Settings</a>';

// Status colour helper
function safetyStatusColor(string $s): string {
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

// Severity colour helper
function safetySevColor(string $s): string {
    return match(strtolower($s)) {
        'negligible' => '#6b7280',
        'minor'      => '#3b82f6',
        'moderate'   => '#f59e0b',
        'significant'=> '#f97316',
        'critical'   => '#ef4444',
        default      => '#6b7280',
    };
}

$filterStatus   = $filters['status']   ?? ($_GET['status']   ?? '');
$filterType     = $filters['type']     ?? ($_GET['type']     ?? '');
$filterSeverity = $filters['severity'] ?? ($_GET['severity'] ?? '');
$filterSearch   = $filters['q']        ?? ($_GET['q']        ?? '');
?>

<!-- Stats Row -->
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:16px; margin-bottom:24px;">
    <?php
    $statCards = [
        ['label' => 'Total Reports', 'value' => $stats['total']       ?? 0, 'color' => 'var(--text-primary)'],
        ['label' => 'Open / Submitted', 'value' => $stats['open']     ?? 0, 'color' => '#3b82f6'],
        ['label' => 'Investigation',    'value' => $stats['investigation'] ?? 0, 'color' => '#ef4444'],
        ['label' => 'Closed',           'value' => $stats['closed']   ?? 0, 'color' => '#10b981'],
    ];
    foreach ($statCards as $sc):
    ?>
    <div class="card" style="padding:20px 16px; text-align:center;">
        <div style="font-size:11px; text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted); font-weight:600; margin-bottom:6px;"><?= e($sc['label']) ?></div>
        <div style="font-size:32px; font-weight:700; color:<?= $sc['color'] ?>;"><?= (int)$sc['value'] ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filter Bar -->
<div class="card" style="padding:16px 20px; margin-bottom:20px;">
    <form method="GET" action="/safety" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
        <select name="status" class="form-control" style="width:auto; min-width:150px;">
            <option value="">All Statuses</option>
            <?php foreach ([
                'submitted' => 'Submitted', 'under_review' => 'Under Review',
                'investigation' => 'Investigation', 'action_in_progress' => 'Action In Progress',
                'closed' => 'Closed', 'reopened' => 'Reopened'
            ] as $val => $lbl): ?>
            <option value="<?= $val ?>" <?= $filterStatus === $val ? 'selected' : '' ?>><?= $lbl ?></option>
            <?php endforeach; ?>
        </select>

        <select name="type" class="form-control" style="width:auto; min-width:190px;">
            <option value="">All Types</option>
            <?php foreach ([
                'general_hazard' => 'General Hazard', 'flight_crew_occurrence' => 'Flight Crew',
                'maintenance_engineering' => 'Maintenance', 'ground_ops' => 'Ground Ops',
                'quality' => 'Quality', 'hse' => 'HSE', 'tcas' => 'TCAS',
                'environmental' => 'Environmental', 'frat' => 'FRAT'
            ] as $val => $lbl): ?>
            <option value="<?= $val ?>" <?= $filterType === $val ? 'selected' : '' ?>><?= $lbl ?></option>
            <?php endforeach; ?>
        </select>

        <select name="severity" class="form-control" style="width:auto; min-width:140px;">
            <option value="">All Severities</option>
            <?php foreach (['negligible','minor','moderate','significant','critical'] as $sev): ?>
            <option value="<?= $sev ?>" <?= $filterSeverity === $sev ? 'selected' : '' ?>><?= ucfirst($sev) ?></option>
            <?php endforeach; ?>
        </select>

        <input type="text" name="q" class="form-control" style="min-width:180px;"
               placeholder="Search ref, title, reporter…" value="<?= e($filterSearch) ?>">

        <button type="submit" class="btn btn-outline btn-sm">Filter</button>
        <?php if ($filterStatus || $filterType || $filterSeverity || $filterSearch): ?>
            <a href="/safety" class="btn btn-ghost btn-sm">Clear</a>
        <?php endif; ?>
        <span class="text-sm text-muted" style="margin-left:auto;"><?= count($reports) ?> report<?= count($reports) !== 1 ? 's' : '' ?></span>
    </form>
</div>

<!-- Bulk Action Form -->
<form method="POST" action="/safety/bulk-action" id="bulkForm">
    <?= csrfField() ?>

    <!-- Bulk Actions Bar (hidden until selection) -->
    <div id="bulkBar" style="display:none; background:rgba(59,130,246,0.08); border:1px solid rgba(59,130,246,0.25); border-radius:var(--radius-md); padding:10px 16px; margin-bottom:12px; align-items:center; gap:12px;">
        <span class="text-sm" id="bulkCount">0 selected</span>
        <select name="bulk_action" class="form-control" style="width:auto; min-width:180px;">
            <option value="">— Choose Action —</option>
            <option value="assign_me">Assign to Me</option>
            <option value="status_under_review">→ Under Review</option>
            <option value="status_investigation">→ Investigation</option>
            <option value="status_closed">→ Closed</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Apply</button>
        <button type="button" class="btn btn-ghost btn-sm" onclick="clearSelection()">Cancel</button>
    </div>

    <?php if (empty($reports)): ?>
        <div class="card">
            <div class="empty-state" style="padding:48px 0;">
                <div class="icon">🔍</div>
                <h3>No Reports Found</h3>
                <p>No safety reports match the current filters.</p>
            </div>
        </div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:36px;">
                        <input type="checkbox" id="selectAll" title="Select all" onchange="toggleAll(this)">
                    </th>
                    <th>Ref No.</th>
                    <th>Type</th>
                    <th>Reporter</th>
                    <th>Event Date</th>
                    <th>Severity</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Submitted</th>
                    <th style="text-align:right;"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $r): ?>
                <?php
                $sColor = safetyStatusColor($r['status'] ?? '');
                $sevColor = safetySevColor($r['final_severity'] ?? $r['severity'] ?? '');
                $unassigned = empty($r['assigned_to_name']) && in_array($r['status'] ?? '', ['submitted','under_review']);
                ?>
                <tr>
                    <td>
                        <input type="checkbox" name="report_ids[]" value="<?= (int)$r['id'] ?>" class="row-check" onchange="updateBulkBar()">
                    </td>
                    <td style="font-family:monospace; font-weight:600; font-size:13px;">
                        <a href="/safety/team/report/<?= (int)$r['id'] ?>" style="color:var(--accent-blue); text-decoration:none;"><?= e($r['reference_no'] ?? '—') ?></a>
                    </td>
                    <td style="font-size:12px; font-weight:600; color:var(--text-secondary);">
                        <?= e(ucwords(str_replace('_', ' ', $r['report_type'] ?? '—'))) ?>
                    </td>
                    <td class="text-sm">
                        <?php if (!empty($r['is_anonymous'])): ?>
                            <span style="color:var(--text-muted); font-style:italic;">🔒 Anonymous</span>
                        <?php else: ?>
                            <?= e($r['reporter_name'] ?? '—') ?>
                        <?php endif; ?>
                    </td>
                    <td class="text-sm text-muted">
                        <?= !empty($r['event_date']) ? date('d M Y', strtotime($r['event_date'])) : '—' ?>
                    </td>
                    <td>
                        <?php if (!empty($r['final_severity']) || !empty($r['severity'])): ?>
                        <span class="status-badge" style="--badge-color:<?= $sevColor ?>;">
                            <?= ucfirst($r['final_severity'] ?? $r['severity'] ?? '') ?>
                        </span>
                        <?php else: ?>
                            <span class="text-muted text-xs">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-badge" style="--badge-color:<?= $sColor ?>;">
                            <?= ucfirst(str_replace('_', ' ', $r['status'] ?? '')) ?>
                        </span>
                    </td>
                    <td class="text-sm">
                        <?php if ($unassigned): ?>
                            <span style="color:#ef4444; font-weight:600; font-size:12px;">⚠ Unassigned</span>
                        <?php else: ?>
                            <?= e($r['assigned_to_name'] ?? '—') ?>
                        <?php endif; ?>
                    </td>
                    <td class="text-sm text-muted">
                        <?= !empty($r['created_at']) ? date('d M Y', strtotime($r['created_at'])) : '—' ?>
                    </td>
                    <td style="text-align:right;">
                        <a href="/safety/team/report/<?= (int)$r['id'] ?>" class="btn btn-outline btn-xs">Open</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</form>

<script>
function toggleAll(master) {
    document.querySelectorAll('.row-check').forEach(function(cb) { cb.checked = master.checked; });
    updateBulkBar();
}
function clearSelection() {
    document.querySelectorAll('.row-check, #selectAll').forEach(function(cb) { cb.checked = false; });
    updateBulkBar();
}
function updateBulkBar() {
    var selected = document.querySelectorAll('.row-check:checked').length;
    var bar = document.getElementById('bulkBar');
    var cnt = document.getElementById('bulkCount');
    bar.style.display = selected > 0 ? 'flex' : 'none';
    cnt.textContent = selected + ' selected';
}
</script>
