<?php
/**
 * Roster Workbench — airline-grade monthly scheduling grid
 *
 * Variables:
 *   $year, $month, $daysInMonth, $grid, $crewList, $dutyTypes
 *   $prevMonth, $prevYear, $nextMonth, $nextYear
 *   $complianceIssues, $bases, $periods, $activePeriod, $pendingChanges
 *   $monthlySummary (keyed by user_id)
 */

$today      = date('Y-m-d');
$dutyTypes  = $dutyTypes ?? RosterModel::dutyTypes();
$monthlySummary = $monthlySummary ?? [];
$filterBase = $_GET['base_id'] ?? '';
$filterRole = $_GET['role']    ?? '';
$searchQ    = trim($_GET['q']  ?? '');

$statusColors = [
    'draft'     => ['border' => '#f59e0b', 'bg' => 'rgba(245,158,11,.08)', 'text' => '#b45309', 'label' => 'DRAFT'],
    'published' => ['border' => '#10b981', 'bg' => 'rgba(16,185,129,.08)', 'text' => '#065f46', 'label' => 'PUBLISHED'],
    'frozen'    => ['border' => '#3b82f6', 'bg' => 'rgba(59,130,246,.08)', 'text' => '#1e40af', 'label' => 'FROZEN'],
    'archived'  => ['border' => '#6b7280', 'bg' => 'rgba(107,114,128,.08)','text' => '#374151', 'label' => 'ARCHIVED'],
];

// Apply search filter to crew list
$filteredCrew = array_filter($crewList ?? [], function($c) use ($searchQ) {
    if (!$searchQ) return true;
    return stripos($c['user_name'], $searchQ) !== false
        || stripos($c['employee_id'] ?? '', $searchQ) !== false;
});
?>
<style>
/* ── Workbench Layout ───────────────────────────────────────────────────── */
.wb-layout{display:flex;gap:0;min-height:calc(100vh - 180px);}
.wb-main{flex:1;min-width:0;display:flex;flex-direction:column;}
.wb-drawer{width:290px;flex-shrink:0;border-left:1px solid var(--border);
 background:var(--bg-card);overflow-y:auto;position:sticky;top:0;
 height:calc(100vh - 180px);display:none;}
.wb-drawer.open{display:block;}
/* ── Toolbar ────────────────────────────────────────────────────────────── */
.wb-toolbar{display:flex;align-items:center;gap:8px;padding:10px 0 8px;
 border-bottom:1px solid var(--border);flex-wrap:wrap;margin-bottom:8px;}
.wb-btn{display:inline-flex;align-items:center;gap:5px;padding:6px 12px;
 border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;
 border:1px solid var(--border);background:var(--bg-card);
 color:var(--text-primary);text-decoration:none;white-space:nowrap;}
.wb-btn:hover{background:var(--bg-secondary);}
.wb-btn-primary{background:#2563eb;color:#fff;border-color:#2563eb;}
.wb-btn-primary:hover{background:#1d4ed8;}
.wb-btn-success{background:#10b981;color:#fff;border-color:#10b981;}
.wb-btn-warning{background:#f59e0b;color:#fff;border-color:#f59e0b;}
.wb-btn-sm{padding:4px 10px;font-size:11px;}
/* ── Filters ────────────────────────────────────────────────────────────── */
.wb-filters{display:flex;align-items:center;gap:8px;padding:6px 0;flex-wrap:wrap;margin-bottom:6px;}
.wb-filter-select{height:30px;padding:0 8px;border-radius:6px;font-size:12px;
 border:1px solid var(--border);background:var(--bg-card);color:var(--text-primary);}
.wb-search{height:30px;padding:0 10px;border-radius:6px;font-size:12px;
 border:1px solid var(--border);background:var(--bg-card);color:var(--text-primary);width:190px;}
.view-toggle{display:flex;border:1px solid var(--border);border-radius:6px;overflow:hidden;}
.view-toggle a{padding:5px 12px;font-size:11px;font-weight:600;text-decoration:none;color:var(--text-muted);background:var(--bg-card);}
.view-toggle a.active{background:#2563eb;color:#fff;}
/* ── Legend ──────────────────────────────────────────────────────────────── */
.duty-legend{display:flex;flex-wrap:wrap;gap:6px;padding:5px 0 8px;}
.legend-item{display:flex;align-items:center;gap:4px;font-size:11px;}
/* ── Grid ────────────────────────────────────────────────────────────────── */
.grid-wrap{overflow-x:auto;flex:1;}
.roster-grid{border-collapse:collapse;font-size:11.5px;min-width:900px;width:100%;}
/* Header */
.roster-grid thead th{background:var(--bg-secondary);border-bottom:2px solid var(--border);
 padding:0;position:sticky;top:0;z-index:10;}
.day-hdr{display:flex;flex-direction:column;align-items:center;justify-content:center;
 padding:5px 2px;min-width:31px;}
.day-num{font-size:13px;font-weight:700;line-height:1;}
.day-dow{font-size:8px;text-transform:uppercase;color:var(--text-muted);letter-spacing:.04em;}
/* Crew column */
.crew-col{position:sticky;left:0;background:var(--bg-card);z-index:5;
 border-right:2px solid var(--border);min-width:185px;padding:0!important;}
.crew-col-hdr{position:sticky;left:0;background:var(--bg-secondary);z-index:11;
 border-right:2px solid var(--border);min-width:185px;
 padding:5px 10px!important;font-size:11px;font-weight:700;
 color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;}
.crew-cell{padding:4px 8px 4px 10px;display:flex;flex-direction:column;gap:1px;cursor:pointer;}
.crew-cell:hover{background:var(--bg-secondary);}
.crew-name{font-weight:700;font-size:12px;color:var(--text-primary);
 white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:160px;}
.crew-meta{display:flex;align-items:center;gap:4px;}
.crew-empid{font-size:9.5px;color:var(--text-muted);font-family:monospace;}
.crew-role-chip{font-size:8.5px;font-weight:700;padding:1px 5px;border-radius:3px;text-transform:uppercase;letter-spacing:.04em;}
.role-pilot{background:#dbeafe;color:#1e40af;}
.role-cabin{background:#fce7f3;color:#9d174d;}
.role-engineer{background:#fef9c3;color:#92400e;}
.role-base{background:#d1fae5;color:#065f46;}
/* Duty cells */
.roster-grid td{border-right:1px solid var(--border);border-bottom:1px solid var(--border);
 padding:3px 2px;text-align:center;vertical-align:middle;}
.roster-grid tr:hover td:not(.crew-col){background:rgba(37,99,235,.03);}
.duty-chip{display:inline-flex;align-items:center;justify-content:center;
 border-radius:4px;padding:2px 4px;font-size:9.5px;font-weight:800;
 letter-spacing:.04em;min-width:26px;cursor:default;}
.duty-chip.revised{outline:2px solid #f59e0b;outline-offset:1px;}
.empty-day{font-size:9px;color:var(--border);}
.conflict-dot{width:4px;height:4px;border-radius:50%;background:#ef4444;
 display:inline-block;margin-left:1px;vertical-align:top;}
.warn-dot{background:#f59e0b;}
/* Summary column */
.summary-col{position:sticky;right:0;background:var(--bg-secondary);z-index:5;
 border-left:2px solid var(--border);min-width:88px;padding:0!important;}
.summary-hdr{position:sticky;right:0;background:var(--bg-secondary);z-index:11;
 border-left:2px solid var(--border);min-width:88px;
 padding:5px 6px!important;font-size:10px;font-weight:700;
 color:var(--text-muted);text-transform:uppercase;}
.summary-cell{padding:3px 6px;}
.sum-row{display:flex;justify-content:space-between;font-size:9.5px;line-height:1.6;}
.sum-label{color:var(--text-muted);}
.sum-val{font-weight:700;font-variant-numeric:tabular-nums;}
/* Compliance badges */
.badge-crit{display:inline-flex;align-items:center;gap:2px;font-size:8px;font-weight:800;
 padding:1px 4px;border-radius:3px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;cursor:help;}
.badge-warn{background:#fffbeb;color:#d97706;border:1px solid #fed7aa;}
/* Weekend/today */
.is-weekend{background:rgba(239,68,68,.03);}
.is-today{background:rgba(37,99,235,.07)!important;}
/* Drawer */
.wb-drawer-hdr{padding:14px 16px;border-bottom:1px solid var(--border);
 display:flex;align-items:center;justify-content:space-between;}
.wb-drawer-body{padding:16px;}
.ds-title{font-weight:700;font-size:14px;}
.ds-close{cursor:pointer;color:var(--text-muted);font-size:18px;background:none;border:none;padding:0;}
.ds-section{margin-bottom:14px;}
.ds-section-label{font-size:10px;font-weight:800;text-transform:uppercase;
 letter-spacing:.06em;color:var(--text-muted);margin-bottom:6px;}
.ds-stat{display:flex;justify-content:space-between;font-size:12px;
 padding:4px 0;border-bottom:1px solid var(--border);}
.ds-stat:last-child{border:none;}
/* Alert banners */
.alert-banner{display:flex;align-items:center;gap:10px;padding:9px 14px;
 border-radius:8px;border:1px solid;margin-bottom:8px;font-size:13px;flex-wrap:wrap;}
/* Group divider */
.group-divider td{background:var(--bg-secondary);padding:4px 12px;font-size:10px;
 font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);
 border-bottom:1px solid var(--border);}
</style>

<?php if ($activePeriod):
    $sc = $statusColors[$activePeriod['status']] ?? $statusColors['draft'];
?>
<div class="alert-banner" style="border-color:<?= $sc['border'] ?>;background:<?= $sc['bg'] ?>;color:<?= $sc['text'] ?>;">
    <span style="background:<?= $sc['border'] ?>;color:#fff;font-size:10px;font-weight:800;
     padding:2px 7px;border-radius:4px;letter-spacing:.06em;"><?= $sc['label'] ?></span>
    <strong><?= e($activePeriod['name']) ?></strong>
    <span style="font-size:12px;opacity:.8;"><?= date('d M', strtotime($activePeriod['start_date'])) ?> – <?= date('d M Y', strtotime($activePeriod['end_date'])) ?></span>
    <?php if ($activePeriod['status'] === 'draft'): ?>
        <span style="font-size:12px;">⚠ Draft — not visible to crew</span>
    <?php elseif ($activePeriod['status'] === 'published'): ?>
        <span style="font-size:12px;">✓ Published — crew can view their roster</span>
    <?php elseif ($activePeriod['status'] === 'frozen'): ?>
        <span style="font-size:12px;">🔒 Frozen — no further changes</span>
    <?php endif; ?>
    <?php if (hasAnyRole(['scheduler','airline_admin','super_admin'])): ?>
        <a href="/roster/periods" class="wb-btn wb-btn-sm" style="margin-left:auto;">Manage Periods</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!empty($pendingChanges) && hasAnyRole(['scheduler','airline_admin','super_admin','chief_pilot','head_cabin_crew'])): ?>
<div class="alert-banner" style="border-color:#f59e0b;background:rgba(245,158,11,.08);color:#b45309;">
    ⚠ <strong><?= count($pendingChanges) ?> pending change request<?= count($pendingChanges) !== 1 ? 's' : '' ?></strong>
    <a href="/roster/changes" style="color:#b45309;">Review now →</a>
</div>
<?php endif; ?>

<!-- Toolbar -->
<div class="wb-toolbar">
    <a href="/roster?year=<?= $prevYear ?>&month=<?= $prevMonth ?><?= $filterBase ? '&base_id='.$filterBase : '' ?><?= $filterRole ? '&role='.$filterRole : '' ?>"
       class="wb-btn">← <?= date('M', mktime(0,0,0,$prevMonth,1,$prevYear)) ?></a>
    <span style="font-size:17px;font-weight:800;min-width:148px;text-align:center;">
        <?= date('F Y', mktime(0,0,0,$month,1,$year)) ?>
    </span>
    <a href="/roster?year=<?= $nextYear ?>&month=<?= $nextMonth ?><?= $filterBase ? '&base_id='.$filterBase : '' ?><?= $filterRole ? '&role='.$filterRole : '' ?>"
       class="wb-btn"><?= date('M', mktime(0,0,0,$nextMonth,1,$nextYear)) ?> →</a>

    <span style="width:1px;height:24px;background:var(--border);margin:0 4px;"></span>

    <?php if (hasAnyRole(['scheduler','airline_admin','super_admin'])): ?>
        <a href="/roster/assign" class="wb-btn wb-btn-primary">＋ Assign</a>
        <a href="/roster/bulk-assign" class="wb-btn">Bulk Assign</a>
        <?php if ($activePeriod && $activePeriod['status'] === 'published'): ?>
            <a href="/roster/revisions/create" class="wb-btn wb-btn-warning">✎ Revision</a>
        <?php endif; ?>
    <?php endif; ?>

    <span style="margin-left:auto;display:flex;gap:6px;align-items:center;">
        <a href="/roster/coverage?year=<?= $year ?>&month=<?= $month ?>" class="wb-btn wb-btn-sm">📊 Coverage</a>
        <button class="wb-btn wb-btn-sm" onclick="toggleDrawer()">☰ Inspector</button>
    </span>
</div>

<!-- Filters -->
<form method="GET" action="/roster" id="filterForm">
    <input type="hidden" name="year" value="<?= $year ?>">
    <input type="hidden" name="month" value="<?= $month ?>">
    <div class="wb-filters">
        <select name="base_id" class="wb-filter-select" onchange="document.getElementById('filterForm').submit()">
            <option value="">All Bases</option>
            <?php foreach ($bases as $b): ?>
                <option value="<?= $b['id'] ?>" <?= ($filterBase == $b['id']) ? 'selected' : '' ?>><?= e($b['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="role" class="wb-filter-select" onchange="document.getElementById('filterForm').submit()">
            <option value="">All Crew Groups</option>
            <option value="chief_pilot"    <?= $filterRole === 'chief_pilot'     ? 'selected' : '' ?>>Captains</option>
            <option value="pilot"          <?= $filterRole === 'pilot'           ? 'selected' : '' ?>>First Officers</option>
            <option value="cabin_crew"     <?= $filterRole === 'cabin_crew'      ? 'selected' : '' ?>>Cabin Crew</option>
            <option value="head_cabin_crew"<?= $filterRole === 'head_cabin_crew' ? 'selected' : '' ?>>Head of Cabin</option>
            <option value="engineer"       <?= $filterRole === 'engineer'        ? 'selected' : '' ?>>Engineers</option>
        </select>

        <input type="search" name="q" class="wb-search" placeholder="Name or staff ID…"
               value="<?= e($searchQ) ?>" oninput="debounceFilter(this)">

        <?php if ($filterBase || $filterRole || $searchQ): ?>
            <a href="/roster?year=<?= $year ?>&month=<?= $month ?>" class="wb-btn wb-btn-sm" style="color:var(--text-muted);">✕ Clear</a>
        <?php endif; ?>

        <span style="margin-left:auto;display:flex;align-items:center;gap:6px;">
            <a href="/roster/standby?date=<?= $today ?>" class="wb-btn wb-btn-sm">Reserve Pool</a>
        </span>
    </div>
</form>

<!-- Legend -->
<div class="duty-legend">
    <?php foreach ($dutyTypes as $key => $dt): ?>
    <div class="legend-item">
        <span class="duty-chip" style="background:<?= $dt['bg'] ?>;color:<?= $dt['color'] ?>;"><?= $dt['code'] ?></span>
        <span style="color:var(--text-muted);font-size:10.5px;"><?= $dt['label'] ?></span>
    </div>
    <?php endforeach; ?>
    <div class="legend-item">
        <span class="duty-chip revised" style="background:#dbeafe;color:#2563eb;">FLT</span>
        <span style="color:var(--text-muted);font-size:10.5px;">Revised</span>
    </div>
</div>

<!-- Main workbench -->
<div class="wb-layout">
<div class="wb-main">
<div class="grid-wrap">

<?php if (empty($filteredCrew)): ?>
<div style="text-align:center;padding:60px 24px;color:var(--text-muted);">
    <div style="font-size:32px;margin-bottom:12px;">📋</div>
    <div style="font-size:16px;font-weight:700;color:var(--text-primary);margin-bottom:6px;">No crew to display</div>
    <div style="font-size:13px;"><?= $searchQ ? 'No crew matched your search.' : 'No active crew found for selected filters.' ?></div>
    <?php if ($filterBase || $filterRole || $searchQ): ?>
        <a href="/roster?year=<?= $year ?>&month=<?= $month ?>" class="wb-btn wb-btn-sm" style="margin-top:12px;display:inline-flex;">Clear filters</a>
    <?php endif; ?>
</div>
<?php else: ?>

<table class="roster-grid" id="rosterGrid">
<thead>
<tr>
    <th class="crew-col-hdr">
        <div style="padding:5px 10px;">Crew (<?= count($filteredCrew) ?>)</div>
    </th>
    <?php for ($d = 1; $d <= $daysInMonth; $d++):
        $dt     = sprintf('%04d-%02d-%02d', $year, $month, $d);
        $dow    = (int)date('N', strtotime($dt));
        $isWknd = $dow >= 6;
        $isTdy  = $dt === $today;
    ?>
    <th style="<?= $isWknd ? 'background:rgba(239,68,68,.04);' : '' ?><?= $isTdy ? 'background:rgba(37,99,235,.1);' : '' ?>">
        <div class="day-hdr">
            <span class="day-num" style="<?= $isTdy ? 'color:#2563eb;' : '' ?>"><?= $d ?></span>
            <span class="day-dow"><?= date('D', strtotime($dt)) ?></span>
        </div>
    </th>
    <?php endfor; ?>
    <th class="summary-hdr">Month</th>
</tr>
</thead>
<tbody>
<?php
$prevRoleGroup = null;
foreach ($filteredCrew as $crew):
    $uid  = $crew['id'];
    $slug = $crew['role_slug'] ?? '';
    $comp = $complianceIssues[$uid] ?? null;
    $sum  = $monthlySummary[$uid]   ?? ['flight'=>0,'standby'=>0,'reserve'=>0,'training'=>0,'leave'=>0,'off'=>0,'rest'=>0];

    $roleGroupLabel = match($slug) {
        'chief_pilot'     => 'Captains',
        'pilot'           => 'First Officers',
        'head_cabin_crew' => 'Head of Cabin Crew',
        'cabin_crew'      => 'Cabin Crew',
        'engineer'        => 'Engineers',
        'base_manager'    => 'Base Managers',
        default           => ucwords(str_replace('_', ' ', $slug)),
    };
    if ($slug !== $prevRoleGroup):
        $prevRoleGroup = $slug;
?>
<tr class="group-divider">
    <td colspan="<?= $daysInMonth + 2 ?>"><?= $roleGroupLabel ?></td>
</tr>
<?php endif; ?>
<tr class="crew-row" data-uid="<?= $uid ?>" data-name="<?= e($crew['user_name']) ?>">
    <td class="crew-col">
        <div class="crew-cell" onclick="openDrawer(<?= $uid ?>,'<?= e(addslashes($crew['user_name'])) ?>','<?= e(addslashes($crew['employee_id'] ?? '')) ?>','<?= $slug ?>')">
            <div class="crew-name">
                <?= e($crew['user_name']) ?>
                <?php if ($comp): ?>
                    <span class="badge-<?= $comp['severity'] === 'critical' ? 'crit' : 'warn' ?>"
                          title="<?= e(implode(' | ', $comp['issues'])) ?>">
                        <?= $comp['severity'] === 'critical' ? '⚠ EXP' : '⚡ DUE' ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="crew-meta">
                <span class="crew-empid"><?= e($crew['employee_id'] ?? '—') ?></span>
                <?php
                $rc = match($slug) {
                    'chief_pilot'                    => ['role-pilot','CAPT'],
                    'pilot'                          => ['role-pilot','FO'],
                    'cabin_crew','head_cabin_crew'   => ['role-cabin','CC'],
                    'engineer'                       => ['role-engineer','ENG'],
                    'base_manager'                   => ['role-base','BM'],
                    default                          => ['role-pilot',strtoupper(substr($slug,0,2))],
                };
                ?>
                <span class="crew-role-chip <?= $rc[0] ?>"><?= $rc[1] ?></span>
            </div>
        </div>
    </td>

    <?php for ($d = 1; $d <= $daysInMonth; $d++):
        $dt     = sprintf('%04d-%02d-%02d', $year, $month, $d);
        $entry  = $grid[$uid][$dt] ?? null;
        $dow    = (int)date('N', strtotime($dt));
        $isWknd = $dow >= 6;
        $isTdy  = $dt === $today;
        $cs     = '';
        if ($isTdy)        $cs = 'background:rgba(37,99,235,.07);';
        elseif ($isWknd)   $cs = 'background:rgba(239,68,68,.03);';
    ?>
    <td style="<?= $cs ?>">
        <?php if ($entry):
            $dtype  = $entry['duty_type'] ?? 'off';
            $dtMeta = $dutyTypes[$dtype] ?? ['color'=>'#6b7280','bg'=>'#f3f4f6','code'=>strtoupper(substr($dtype,0,3)),'label'=>$dtype];
            $isRev  = !empty($entry['is_revision']);
            $ttip   = $dtMeta['label'];
            if ($entry['duty_code']) $ttip .= ' — '.$entry['duty_code'];
            if ($entry['notes'])     $ttip .= ': '.substr($entry['notes'],0,60);
        ?>
            <span class="duty-chip <?= $isRev ? 'revised' : '' ?>"
                  style="background:<?= $dtMeta['bg'] ?>;color:<?= $dtMeta['color'] ?>;"
                  title="<?= e($ttip) ?>">
                <?= e($entry['duty_code'] ?: $dtMeta['code']) ?>
                <?php if ($comp && in_array($dtype, ['flight','standby','reserve','pos','deadhead'])): ?>
                    <span class="conflict-dot <?= $comp['severity'] !== 'critical' ? 'warn-dot' : '' ?>"></span>
                <?php endif; ?>
            </span>
        <?php else: ?>
            <span class="empty-day">–</span>
        <?php endif; ?>
    </td>
    <?php endfor; ?>

    <td class="summary-col">
        <div class="summary-cell">
            <div class="sum-row"><span class="sum-label">FLT</span><span class="sum-val" style="color:#2563eb;"><?= $sum['flight'] ?></span></div>
            <div class="sum-row"><span class="sum-label">SBY</span><span class="sum-val" style="color:#d97706;"><?= ($sum['standby'] ?? 0) + ($sum['reserve'] ?? 0) ?></span></div>
            <div class="sum-row"><span class="sum-label">TRN</span><span class="sum-val" style="color:#7c3aed;"><?= $sum['training'] ?></span></div>
            <div class="sum-row"><span class="sum-label">LVE</span><span class="sum-val" style="color:#059669;"><?= $sum['leave'] ?></span></div>
        </div>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

</div><!-- /grid-wrap -->
</div><!-- /wb-main -->

<!-- Inspector Drawer -->
<div class="wb-drawer" id="inspectorDrawer">
    <div class="wb-drawer-hdr">
        <span class="ds-title" id="drawerCrewName">Inspector</span>
        <button class="ds-close" onclick="toggleDrawer()">✕</button>
    </div>
    <div class="wb-drawer-body" id="drawerBody">
        <p style="color:var(--text-muted);font-size:13px;">Click any crew member row to inspect.</p>
    </div>
</div>
</div><!-- /wb-layout -->

<?php if (hasAnyRole(['pilot','cabin_crew','engineer','chief_pilot','head_cabin_crew'])
    && !hasAnyRole(['scheduler','airline_admin','super_admin'])): ?>
<div style="margin-top:20px;padding:16px;background:var(--bg-card);border:1px solid var(--border);border-radius:8px;">
    <h4 style="margin:0 0 12px;font-size:14px;">Submit a Change Request</h4>
    <form method="POST" action="/roster/changes/request">
        <?= csrfField() ?>
        <input type="hidden" name="redirect" value="/roster?year=<?= $year ?>&month=<?= $month ?>">
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
            <select name="change_type" class="form-control" style="width:160px;">
                <option value="leave_request">Leave Request</option>
                <option value="swap_request">Swap Request</option>
                <option value="correction">Correction</option>
                <option value="comment">Comment</option>
            </select>
            <input type="text" name="message" class="form-control" style="flex:1;min-width:220px;" placeholder="Describe your request…" required>
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
    </form>
</div>
<?php endif; ?>

<script>
function toggleDrawer(){document.getElementById('inspectorDrawer').classList.toggle('open');}

function openDrawer(uid,name,empId,roleSlug){
    const rLabels={pilot:'First Officer',chief_pilot:'Captain',cabin_crew:'Cabin Crew',
        head_cabin_crew:'Head of Cabin',engineer:'Engineer',base_manager:'Base Manager'};
    const row=document.querySelector('[data-uid="'+uid+'"]');
    let flt=0,sby=0,trn=0,lve=0,off=0;
    if(row) row.querySelectorAll('.duty-chip').forEach(c=>{
        const t=c.textContent.trim();
        if(['FLT','POS','DH'].includes(t))flt++;
        else if(['SBY','RES'].includes(t))sby++;
        else if(['TRN','SIM','CHK'].includes(t))trn++;
        else if(['LVE','SCK'].includes(t))lve++;
        else off++;
    });
    document.getElementById('drawerCrewName').textContent=name;
    document.getElementById('drawerBody').innerHTML=`
<div class="ds-section">
 <div class="ds-section-label">Profile</div>
 <div class="ds-stat"><span style="color:var(--text-muted)">Role</span><strong>${rLabels[roleSlug]||roleSlug}</strong></div>
 <div class="ds-stat"><span style="color:var(--text-muted)">Staff ID</span><span style="font-family:monospace">${empId||'—'}</span></div>
</div>
<div class="ds-section">
 <div class="ds-section-label">This Month</div>
 <div class="ds-stat"><span style="color:var(--text-muted)">Flight days</span><strong style="color:#2563eb">${flt}</strong></div>
 <div class="ds-stat"><span style="color:var(--text-muted)">Standby / Reserve</span><strong style="color:#d97706">${sby}</strong></div>
 <div class="ds-stat"><span style="color:var(--text-muted)">Training</span><strong style="color:#7c3aed">${trn}</strong></div>
 <div class="ds-stat"><span style="color:var(--text-muted)">Leave</span><strong style="color:#059669">${lve}</strong></div>
 <div class="ds-stat"><span style="color:var(--text-muted)">Off / Rest</span><strong style="color:var(--text-muted)">${off}</strong></div>
</div>
<div class="ds-section" style="display:flex;flex-direction:column;gap:6px;">
 <a href="/roster/assign?user_id=${uid}" class="wb-btn wb-btn-primary" style="justify-content:center;">＋ Assign Duty</a>
 <a href="/roster/suggest/${uid}?date=<?= $today ?>" class="wb-btn" style="justify-content:center;">Suggest Replacement</a>
</div>`;
    document.getElementById('inspectorDrawer').classList.add('open');
}

let searchTimer;
function debounceFilter(el){
    clearTimeout(searchTimer);
    searchTimer=setTimeout(()=>document.getElementById('filterForm').submit(),380);
}
</script>
