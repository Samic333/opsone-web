<?php
/**
 * Safety Team Queue / Inbox.
 *
 * Layout (Phase K redesign):
 *   1. KPI hero strip — 4 cards (Total / Open / Investigation / Closed) with cockpit-light tokens
 *   2. Filter toolbar (form GET → /safety) — status, type, severity, search
 *   3. Bulk-action bar (hidden until rows selected; POST → /safety/bulk-action with CSRF) — UNCHANGED
 *   4. Reports table — ref link, type chip, reporter (avatar or anonymous lock),
 *      event date, severity chip with icon, status badge, assigned-to with
 *      unassigned warning, submitted date, Open button
 *
 * Variables (controller-supplied): $reports[], $stats[], $filters[].
 * No data-shape changes; pure visual redesign + checkbox/bulk-action JS preserved.
 */
$pageTitle    = 'Safety Queue';
$pageSubtitle = 'Manage incoming reports, investigations, and closures';
$headerAction = '<a href="/safety/settings" class="btn btn-ghost btn-sm">⚙ Settings</a>';

// Cockpit-light status colour helper.
function safetyStatusColor(string $s): string {
    return match($s) {
        'submitted'          => 'var(--status-info)',
        'under_review'       => 'var(--accent-yellow)',
        'investigation'      => 'var(--status-critical)',
        'action_in_progress' => 'var(--accent-purple)',
        'closed'             => 'var(--status-cleared)',
        'reopened'           => 'var(--status-advisory)',
        default              => 'var(--text-tertiary)',
    };
}

// Cockpit-light severity colour helper.
function safetySevColor(string $s): string {
    return match(strtolower($s)) {
        'negligible' => 'var(--text-tertiary)',
        'minor'      => 'var(--status-info)',
        'moderate'   => 'var(--accent-yellow)',
        'significant'=> 'var(--accent-red)',
        'critical'   => 'var(--status-critical)',
        default      => 'var(--text-tertiary)',
    };
}

$filterStatus   = $filters['status']   ?? ($_GET['status']   ?? '');
$filterType     = $filters['type']     ?? ($_GET['type']     ?? '');
$filterSeverity = $filters['severity'] ?? ($_GET['severity'] ?? '');
$filterSearch   = $filters['q']        ?? ($_GET['q']        ?? '');

// Initials helper for reporter avatars.
$__initials = static function (?string $name): string {
    $name = trim((string) $name);
    if ($name === '') return '?';
    $parts = preg_split('/\s+/', $name);
    $i = '';
    foreach ($parts as $p) {
        if ($p !== '' && strlen($i) < 2) $i .= mb_substr($p, 0, 1);
    }
    return strtoupper($i ?: 'U');
};
?>

<!-- ─── 1. KPI hero strip ────────────────────────────────────────────── -->
<div class="safety-kpi-grid"
     style="display:grid; grid-template-columns:repeat(4, 1fr); gap:0.85rem; margin-bottom:1.25rem;">
    <?php
    $kpiCards = [
        ['label' => 'Total Reports',     'value' => $stats['total']         ?? 0, 'tone' => 'var(--text-primary)',   'href' => '/safety/queue'],
        ['label' => 'Open / Submitted',  'value' => $stats['open']          ?? 0, 'tone' => 'var(--status-info)',    'href' => '/safety/queue?status=submitted'],
        ['label' => 'Investigation',     'value' => $stats['investigation'] ?? 0, 'tone' => 'var(--status-critical)','href' => '/safety/queue?status=investigation'],
        ['label' => 'Closed',            'value' => $stats['closed']        ?? 0, 'tone' => 'var(--status-cleared)', 'href' => '/safety/queue?status=closed'],
    ];
    foreach ($kpiCards as $kpi):
    ?>
    <a href="<?= e($kpi['href']) ?>"
       style="display:flex; flex-direction:column; gap:6px;
              padding:14px 16px;
              background:var(--bg-card);
              border:1px solid var(--border-color);
              border-left:3px solid <?= $kpi['tone'] ?>;
              border-radius:var(--radius-md);
              text-decoration:none; color:inherit;
              transition:background 0.15s, transform 0.15s;"
       onmouseover="this.style.background='var(--bg-card-hover)';this.style.transform='translateY(-1px)';"
       onmouseout="this.style.background='var(--bg-card)';this.style.transform='translateY(0)';">
        <span style="font-size:11px; font-weight:700; text-transform:uppercase;
                     letter-spacing:.06em; color:var(--text-tertiary);">
            <?= e($kpi['label']) ?>
        </span>
        <span style="font-size:1.6rem; font-weight:700; color:<?= $kpi['tone'] ?>;
                     letter-spacing:-0.02em; line-height:1.1;">
            <?= (int) $kpi['value'] ?>
        </span>
    </a>
    <?php endforeach; ?>
</div>

<!-- ─── 2. Filter toolbar ────────────────────────────────────────────── -->
<div class="card" style="padding:14px 18px; margin-bottom:1.25rem;">
    <form method="GET" action="/safety/queue"
          style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
        <select name="status" class="form-control" style="width:auto; min-width:140px;">
            <option value="">All Statuses</option>
            <?php foreach ([
                'submitted' => 'Submitted', 'under_review' => 'Under Review',
                'investigation' => 'Investigation', 'action_in_progress' => 'Action In Progress',
                'closed' => 'Closed', 'reopened' => 'Reopened'
            ] as $val => $lbl): ?>
                <option value="<?= e($val) ?>" <?= $filterStatus === $val ? 'selected' : '' ?>><?= e($lbl) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="type" class="form-control" style="width:auto; min-width:170px;">
            <option value="">All Types</option>
            <?php foreach ([
                'general_hazard' => 'General Hazard', 'flight_crew_occurrence' => 'Flight Crew',
                'maintenance_engineering' => 'Maintenance', 'ground_ops' => 'Ground Ops',
                'quality' => 'Quality', 'hse' => 'HSE', 'tcas' => 'TCAS',
                'environmental' => 'Environmental', 'frat' => 'FRAT'
            ] as $val => $lbl): ?>
                <option value="<?= e($val) ?>" <?= $filterType === $val ? 'selected' : '' ?>><?= e($lbl) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="severity" class="form-control" style="width:auto; min-width:130px;">
            <option value="">All Severities</option>
            <?php foreach (['negligible','minor','moderate','significant','critical'] as $sev): ?>
                <option value="<?= e($sev) ?>" <?= $filterSeverity === $sev ? 'selected' : '' ?>><?= ucfirst($sev) ?></option>
            <?php endforeach; ?>
        </select>

        <input type="text" name="q" class="form-control"
               placeholder="Search ref, title, reporter…"
               value="<?= e($filterSearch) ?>"
               style="min-width:200px; flex:1;">

        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <?php if ($filterStatus || $filterType || $filterSeverity || $filterSearch): ?>
            <a href="/safety/queue" class="btn btn-ghost btn-sm">Clear</a>
        <?php endif; ?>
        <span style="font-size:12px; color:var(--text-tertiary); margin-left:auto;">
            <?= count($reports) ?> report<?= count($reports) !== 1 ? 's' : '' ?>
        </span>
    </form>
</div>

<!-- ─── 3. Bulk-action form (preserved verbatim) ─────────────────────── -->
<form method="POST" action="/safety/bulk-action" id="bulkForm">
    <?= csrfField() ?>

    <!-- Bulk Actions Bar (hidden until selection) -->
    <div id="bulkBar"
         style="display:none; background:rgba(59,130,246,0.08);
                border:1px solid rgba(59,130,246,0.25);
                border-radius:var(--radius-md);
                padding:10px 16px; margin-bottom:12px;
                align-items:center; gap:12px;">
        <span style="font-size:13px; color:var(--text-primary);" id="bulkCount">0 selected</span>
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

    <!-- ─── 4. Reports table ─────────────────────────────────────────── -->
    <?php if (empty($reports)): ?>
        <div class="card">
            <div class="empty-state" style="padding:48px 0;">
                <div class="icon"><?= sidebarIcon('shield-exclamation', 32) ?></div>
                <h3>No Reports Found</h3>
                <p>No safety reports match the current filters.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="card" style="padding:0; overflow:hidden;">
            <div class="table-wrap" style="margin:0;">
                <table style="margin:0;">
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
                            <th style="text-align:center;">Status</th>
                            <th>Assigned To</th>
                            <th>Submitted</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($reports as $r):
                        $sColor   = safetyStatusColor($r['status'] ?? '');
                        $sevColor = safetySevColor($r['final_severity'] ?? $r['severity'] ?? '');
                        $unassigned = empty($r['assigned_to_name'])
                                      && in_array($r['status'] ?? '', ['submitted','under_review'], true);
                        $reporterName = $r['reporter_name'] ?? '';
                    ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="report_ids[]"
                                       value="<?= (int) $r['id'] ?>"
                                       class="row-check" onchange="updateBulkBar()">
                            </td>
                            <td style="white-space:nowrap;">
                                <a href="/safety/team/report/<?= (int) $r['id'] ?>"
                                   style="color:var(--accent-blue); text-decoration:none;
                                          font-family:ui-monospace,monospace; font-weight:600; font-size:12px;">
                                    <?= e($r['reference_no'] ?? '—') ?>
                                </a>
                            </td>
                            <td>
                                <span style="display:inline-block; font-size:11px; font-weight:600;
                                             padding:2px 8px; border-radius:10px;
                                             background:rgba(255,255,255,0.04);
                                             color:var(--text-secondary);">
                                    <?= e(ucwords(str_replace('_', ' ', $r['report_type'] ?? '—'))) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($r['is_anonymous'])): ?>
                                    <span style="display:inline-flex; align-items:center; gap:6px;
                                                 font-size:12px; color:var(--text-tertiary); font-style:italic;">
                                        <?= sidebarIcon('lock-closed', 12) ?>
                                        Anonymous
                                    </span>
                                <?php else: ?>
                                    <div style="display:flex; align-items:center; gap:8px; min-width:0;">
                                        <span style="width:24px; height:24px; border-radius:50%;
                                                     display:inline-flex; align-items:center; justify-content:center;
                                                     background:var(--accent-blue); color:#fff;
                                                     font-size:9px; font-weight:700; flex-shrink:0;">
                                            <?= e($__initials($reporterName)) ?>
                                        </span>
                                        <span style="font-size:12px; color:var(--text-primary);
                                                     overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
                                                     max-width:140px;">
                                            <?= e($reporterName ?: '—') ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:12px; color:var(--text-tertiary); white-space:nowrap;">
                                <?= !empty($r['event_date']) ? date('d M Y', strtotime($r['event_date'])) : '—' ?>
                            </td>
                            <td>
                                <?php $sev = $r['final_severity'] ?? $r['severity'] ?? ''; ?>
                                <?php if ($sev): ?>
                                    <span style="display:inline-flex; align-items:center; gap:5px;
                                                 font-size:11px; font-weight:700;
                                                 padding:3px 9px; border-radius:10px;
                                                 background:<?= $sevColor ?>22;
                                                 color:<?= $sevColor ?>;
                                                 text-transform:capitalize;">
                                        <span style="width:6px; height:6px; border-radius:50%;
                                                     background:<?= $sevColor ?>;"></span>
                                        <?= e($sev) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color:var(--text-tertiary); font-size:12px;">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;">
                                <span style="display:inline-flex; align-items:center; gap:5px;
                                             font-size:11px; font-weight:700;
                                             padding:3px 9px; border-radius:10px;
                                             background:<?= $sColor ?>22;
                                             color:<?= $sColor ?>;
                                             text-transform:capitalize; white-space:nowrap;">
                                    <span style="width:6px; height:6px; border-radius:50%;
                                                 background:<?= $sColor ?>;"></span>
                                    <?= e(str_replace('_', ' ', $r['status'] ?? '')) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($unassigned): ?>
                                    <span style="display:inline-flex; align-items:center; gap:5px;
                                                 font-size:11px; font-weight:600;
                                                 padding:3px 9px; border-radius:10px;
                                                 background:rgba(239,68,68,0.10);
                                                 color:var(--status-critical);">
                                        <?= sidebarIcon('exclamation', 11) ?>
                                        Unassigned
                                    </span>
                                <?php else: ?>
                                    <span style="font-size:12px; color:var(--text-secondary);">
                                        <?= e($r['assigned_to_name'] ?? '—') ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:12px; color:var(--text-tertiary); white-space:nowrap;">
                                <?= !empty($r['created_at']) ? date('d M Y', strtotime($r['created_at'])) : '—' ?>
                            </td>
                            <td style="text-align:right;">
                                <a href="/safety/team/report/<?= (int) $r['id'] ?>" class="btn btn-outline btn-xs">
                                    Open
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</form>

<script>
function toggleAll(master) {
    document.querySelectorAll('.row-check').forEach(function (cb) { cb.checked = master.checked; });
    updateBulkBar();
}
function clearSelection() {
    document.querySelectorAll('.row-check, #selectAll').forEach(function (cb) { cb.checked = false; });
    updateBulkBar();
}
function updateBulkBar() {
    var selected = document.querySelectorAll('.row-check:checked').length;
    var bar = document.getElementById('bulkBar');
    var cnt = document.getElementById('bulkCount');
    if (!bar || !cnt) return;
    bar.style.display = selected > 0 ? 'flex' : 'none';
    cnt.textContent = selected + ' selected';
}
</script>

<style>
@media (max-width: 1100px) {
    .safety-kpi-grid { grid-template-columns: repeat(2, 1fr) !important; }
}
</style>
