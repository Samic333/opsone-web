<?php
/**
 * Roster monthly grid view
 * Variables: $year, $month, $daysInMonth, $grid (indexed [userId][date]),
 *            $crewList, $dutyTypes, $prevMonth, $prevYear, $nextMonth, $nextYear,
 *            $complianceIssues (keyed by user_id)
 */
$today = date('Y-m-d');
?>
<style>
.roster-wrap { overflow-x: auto; }
.roster-table { border-collapse: collapse; width: 100%; min-width: 900px; font-size: 12px; }
.roster-table th, .roster-table td { border: 1px solid var(--border); padding: 4px 3px; text-align: center; white-space: nowrap; }
.roster-table th { background: var(--bg-secondary); font-weight: 600; font-size: 11px; }
.roster-table td.crew-name { text-align: left; padding: 4px 8px; font-weight: 600; font-size: 12px; min-width: 150px; position: sticky; left: 0; background: var(--bg-card); z-index: 1; }
.roster-cell { display: inline-block; border-radius: 4px; padding: 2px 5px; font-size: 10px; font-weight: 700; color: #fff; cursor: default; letter-spacing: .03em; }
.roster-table tr:hover td { background: rgba(59,130,246,.06); }
.roster-table td.crew-name:hover { background: var(--bg-secondary); }
.day-hdr-num { font-size: 13px; font-weight: 700; }
.day-hdr-dow { font-size: 9px; color: var(--text-muted); text-transform: uppercase; }
.day-weekend { background: rgba(249,115,22,.06); }
.day-today   { background: rgba(59,130,246,.1); }
.legend { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
.legend-item { display: flex; align-items: center; gap: 5px; font-size: 12px; }
.legend-dot { width: 16px; height: 16px; border-radius: 3px; }
.nav-bar { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
.nav-bar .month-label { font-size: 18px; font-weight: 700; min-width: 160px; text-align: center; }
.compliance-crit { display:inline-block; background:#ef4444; color:#fff; border-radius:3px; padding:0 4px; font-size:9px; font-weight:700; margin-left:4px; cursor:help; }
.compliance-warn { display:inline-block; background:#f59e0b; color:#fff; border-radius:3px; padding:0 4px; font-size:9px; font-weight:700; margin-left:4px; cursor:help; }
.replace-btn { display:inline-block; font-size:9px; color: var(--accent-blue, #3b82f6); text-decoration:none; margin-left:4px; }
.replace-btn:hover { text-decoration:underline; }
</style>

<!-- Active period banner -->
<?php if ($activePeriod): ?>
<?php
    $periodStatusColor = ['draft' => '#f59e0b', 'published' => '#10b981', 'frozen' => '#3b82f6', 'archived' => '#6b7280'];
    $pc = $periodStatusColor[$activePeriod['status']] ?? '#6b7280';
?>
<div style="background:var(--bg-secondary); border:1px solid <?= $pc ?>; border-left:4px solid <?= $pc ?>; border-radius:8px; padding:10px 16px; margin-bottom:14px; display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
    <span style="font-size:13px; font-weight:600; color:var(--text-primary);">📅 <?= e($activePeriod['name']) ?></span>
    <span class="status-badge" style="--badge-color:<?= $pc ?>;"><?= ucfirst($activePeriod['status']) ?></span>
    <span class="text-xs text-muted"><?= date('d M', strtotime($activePeriod['start_date'])) ?> → <?= date('d M Y', strtotime($activePeriod['end_date'])) ?></span>
    <?php if ($activePeriod['status'] === 'draft'): ?>
        <span class="text-xs" style="color:#f59e0b;">Draft — not visible to crew yet</span>
    <?php elseif ($activePeriod['status'] === 'published'): ?>
        <span class="text-xs" style="color:#10b981;">Published — crew can view this period</span>
    <?php elseif ($activePeriod['status'] === 'frozen'): ?>
        <span class="text-xs" style="color:#3b82f6;">Frozen — roster is locked</span>
    <?php endif; ?>
    <?php if (hasAnyRole(['scheduler', 'airline_admin', 'super_admin'])): ?>
        <a href="/roster/periods" class="btn btn-ghost btn-xs" style="margin-left:auto;">Manage Periods</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Pending change requests badge -->
<?php if (!empty($pendingChanges) && hasAnyRole(['scheduler', 'airline_admin', 'super_admin', 'chief_pilot', 'head_cabin_crew'])): ?>
<div style="margin-bottom:12px;">
    <a href="/roster/changes" style="display:inline-flex; align-items:center; gap:8px; background:rgba(245,158,11,.12); border:1px solid #f59e0b; border-radius:6px; padding:8px 14px; text-decoration:none; font-size:13px; color:#f59e0b; font-weight:600;">
        ⚠ <?= count($pendingChanges) ?> pending change request<?= count($pendingChanges) !== 1 ? 's' : '' ?> — review now
    </a>
</div>
<?php endif; ?>

<!-- Month navigation -->
<form method="GET" action="/roster" style="display:flex; gap:8px; margin-bottom:16px; align-items:center;">
    <input type="hidden" name="year" value="<?= $year ?>">
    <input type="hidden" name="month" value="<?= $month ?>">
    <span class="text-sm text-muted" style="font-weight:600; margin-right:4px;">Filter Crew:</span>
    <select name="base_id" class="form-control" style="width:180px; padding:4px 10px;" onchange="this.form.submit()">
        <option value="">— All Bases —</option>
        <?php foreach ($bases as $b): ?>
            <option value="<?= $b['id'] ?>" <?= (($_GET['base_id']??'') == $b['id']) ? 'selected' : '' ?>><?= e($b['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="role" class="form-control" style="width:180px; padding:4px 10px;" onchange="this.form.submit()">
        <option value="">— All Roles —</option>
        <option value="pilot" <?= (($_GET['role']??'') == 'pilot') ? 'selected' : '' ?>>Pilots</option>
        <option value="chief_pilot" <?= (($_GET['role']??'') == 'chief_pilot') ? 'selected' : '' ?>>Chief Pilots</option>
        <option value="cabin_crew" <?= (($_GET['role']??'') == 'cabin_crew') ? 'selected' : '' ?>>Cabin Crew</option>
        <option value="head_cabin_crew" <?= (($_GET['role']??'') == 'head_cabin_crew') ? 'selected' : '' ?>>Head Cabin Crew</option>
        <option value="engineer" <?= (($_GET['role']??'') == 'engineer') ? 'selected' : '' ?>>Engineers</option>
    </select>
    <?php if (!empty($_GET['base_id']) || !empty($_GET['role'])): ?>
        <a href="/roster?year=<?= $year ?>&month=<?= $month ?>" class="btn btn-sm btn-ghost" style="color:var(--text-muted); font-weight:normal;">Clear Filters</a>
    <?php endif; ?>
</form>

<div class="nav-bar">
    <a href="/roster?year=<?= $prevYear ?>&month=<?= $prevMonth ?>" class="btn btn-sm btn-outline">← Prev</a>
    <div class="month-label"><?= date('F Y', mktime(0,0,0,$month,1,$year)) ?></div>
    <a href="/roster?year=<?= $nextYear ?>&month=<?= $nextMonth ?>" class="btn btn-sm btn-outline">Next →</a>
    <?php if (hasAnyRole(['scheduler', 'airline_admin', 'super_admin', 'chief_pilot', 'head_cabin_crew'])): ?>
    <a href="/roster/assign" class="btn btn-sm btn-primary" style="margin-left: auto;">＋ Assign</a>
    <?php endif; ?>
    <?php if (hasAnyRole(['scheduler', 'airline_admin', 'super_admin'])): ?>
    <a href="/roster/bulk-assign" class="btn btn-sm btn-outline">Bulk Assign</a>
    <a href="/roster/periods" class="btn btn-sm btn-outline">Periods</a>
    <?php endif; ?>
</div>

<!-- Legend -->
<div class="legend">
    <?php foreach ($dutyTypes as $key => $dt): ?>
    <div class="legend-item">
        <div class="legend-dot" style="background:<?= $dt['color'] ?>"></div>
        <span><?= $dt['code'] ?> — <?= $dt['label'] ?></span>
    </div>
    <?php endforeach; ?>
</div>

<div class="card" style="padding:0;">
    <div class="roster-wrap">
        <table class="roster-table">
            <thead>
            <tr>
                <th style="text-align:left;padding:6px 8px;min-width:150px;">Crew Member</th>
                <?php for ($d = 1; $d <= $daysInMonth; $d++):
                    $ts  = mktime(0,0,0,$month,$d,$year);
                    $dow = date('N', $ts); // 1=Mon, 7=Sun
                    $isWeekend = $dow >= 6;
                    $isToday   = date('Y-m-d', $ts) === date('Y-m-d');
                    $cls = $isToday ? 'day-today' : ($isWeekend ? 'day-weekend' : '');
                ?>
                <th class="<?= $cls ?>" style="min-width:36px;">
                    <div class="day-hdr-num"><?= $d ?></div>
                    <div class="day-hdr-dow"><?= date('D', $ts) ?></div>
                </th>
                <?php endfor; ?>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($crewList)): ?>
            <tr><td colspan="<?= $daysInMonth + 1 ?>" style="text-align:center;color:var(--text-muted);padding:24px;">No active crew found.</td></tr>
            <?php else: ?>
            <?php foreach ($crewList as $crew):
                $userId = $crew['id'];
            ?>
            <tr>
                <td class="crew-name">
                    <?= e($crew['user_name']) ?>
                    <?php if ($crew['employee_id']): ?>
                        <span style="font-size:10px;color:var(--text-muted);font-weight:400;"> (<?= e($crew['employee_id']) ?>)</span>
                    <?php endif; ?>
                    <?php
                    $ci = $complianceIssues[$userId] ?? null;
                    if ($ci):
                        $issueTitle = implode('; ', $ci['issues']);
                        $badgeClass = $ci['severity'] === 'critical' ? 'compliance-crit' : 'compliance-warn';
                        $badgeText  = $ci['severity'] === 'critical' ? '✕ COMPLIANCE' : '⚠ EXPIRY';
                    ?>
                        <span class="<?= $badgeClass ?>" title="<?= e($issueTitle) ?>"><?= $badgeText ?></span>
                        <?php if (hasAnyRole(['scheduler', 'airline_admin', 'super_admin', 'chief_pilot', 'head_cabin_crew'])): ?>
                        <a href="/roster/suggest/<?= $userId ?>?date=<?= urlencode($today) ?>" class="replace-btn" title="Find replacement crew">Replace</a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <div style="font-size:10px;color:var(--text-muted);font-weight:400;"><?= e($crew['role_name']) ?></div>
                </td>
                <?php for ($d = 1; $d <= $daysInMonth; $d++):
                    $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
                    $ts      = mktime(0,0,0,$month,$d,$year);
                    $dow     = date('N', $ts);
                    $isWeekend = $dow >= 6;
                    $isToday   = $dateStr === date('Y-m-d');
                    $cls = $isToday ? 'day-today' : ($isWeekend ? 'day-weekend' : '');
                    $entry = $grid[$userId][$dateStr] ?? null;
                    $dt    = $entry ? ($dutyTypes[$entry['duty_type']] ?? null) : null;
                ?>
                <td class="<?= $cls ?>">
                    <?php if ($entry && $dt): ?>
                        <span class="roster-cell" style="background:<?= $dt['color'] ?>;"
                              title="<?= e($dt['label']) ?><?= $entry['notes'] ? ' — ' . e($entry['notes']) : '' ?>">
                            <?= e($entry['duty_code'] ?? $dt['code']) ?>
                        </span>
                        <?php if (hasAnyRole(['scheduler', 'airline_admin', 'super_admin'])): ?>
                        <form method="POST" action="/roster/delete/<?= $entry['id'] ?>" style="display:inline;" onsubmit="return confirm('Remove this entry?');">
                            <?= csrfField() ?>
                            <button type="submit" style="background:none;border:none;color:var(--text-muted);font-size:9px;cursor:pointer;padding:0;line-height:1;">✕</button>
                        </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="color:var(--text-muted);font-size:10px;">—</span>
                    <?php endif; ?>
                </td>
                <?php endfor; ?>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
