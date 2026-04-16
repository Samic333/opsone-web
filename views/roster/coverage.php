<?php
/**
 * Coverage & Conflicts view
 * Variables: $year, $month, $daysInMonth, $coverage, $periods,
 *            $prevMonth, $prevYear, $nextMonth, $nextYear
 */
$heatmap    = $coverage['heatmap']         ?? [];
$conflicts  = $coverage['conflicts']       ?? [];
$uncovered  = $coverage['uncovered_dates'] ?? [];
$understaf  = $coverage['understaffed']    ?? [];
$resGaps    = $coverage['reserve_gaps']    ?? [];
$lvOverlap  = $coverage['leave_overlaps']  ?? [];
$byDate     = $coverage['by_date']         ?? [];

$levelStyles = [
    'ok'       => ['bg' => '#d1fae5', 'color' => '#065f46', 'label' => 'Good'],
    'warn'     => ['bg' => '#fef9c3', 'color' => '#92400e', 'label' => 'Low Coverage'],
    'critical' => ['bg' => '#fee2e2', 'color' => '#991b1b', 'label' => 'No Active Crew'],
    'empty'    => ['bg' => '#f3f4f6', 'color' => '#6b7280', 'label' => 'No Data'],
];
?>
<style>
.cov-toolbar{display:flex;align-items:center;gap:8px;margin-bottom:16px;flex-wrap:wrap;}
.cov-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:6px;margin-bottom:20px;}
.cov-day{border-radius:8px;padding:10px 8px;text-align:center;border:1px solid rgba(0,0,0,.06);cursor:default;transition:transform .12s;}
.cov-day:hover{transform:scale(1.04);}
.cov-day-num{font-size:15px;font-weight:800;line-height:1;}
.cov-day-dow{font-size:9px;text-transform:uppercase;letter-spacing:.05em;opacity:.7;margin-bottom:4px;}
.cov-day-stat{font-size:11px;font-weight:600;margin-top:2px;}
.cov-day-sub{font-size:9.5px;opacity:.75;}
.cov-day.is-today{box-shadow:0 0 0 2px #2563eb;}
.conflict-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px;margin-bottom:20px;}
.conflict-card{background:var(--bg-card);border:1px solid var(--border);border-radius:9px;padding:14px 16px;}
.conflict-card-hdr{display:flex;align-items:center;gap:8px;margin-bottom:10px;}
.conflict-card-icon{font-size:20px;}
.conflict-card-title{font-weight:700;font-size:13px;}
.conflict-card-count{font-size:18px;font-weight:800;margin-bottom:4px;}
.conflict-list{list-style:none;padding:0;margin:0;}
.conflict-list li{font-size:12px;color:var(--text-muted);padding:3px 0;border-bottom:1px solid var(--border);}
.conflict-list li:last-child{border:none;}
.conflict-list li strong{color:var(--text-primary);}
.crew-conflict-table{width:100%;border-collapse:collapse;font-size:12px;}
.crew-conflict-table th{text-align:left;padding:6px 10px;background:var(--bg-secondary);
 font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);}
.crew-conflict-table td{padding:8px 10px;border-bottom:1px solid var(--border);}
.crew-conflict-table tr:last-child td{border:none;}
.sev-crit{color:#dc2626;font-weight:700;}
.sev-warn{color:#d97706;font-weight:700;}
.legend-bar{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:14px;}
.legend-bar-item{display:flex;align-items:center;gap:6px;font-size:12px;}
.legend-swatch{width:14px;height:14px;border-radius:3px;}
</style>

<!-- Toolbar -->
<div class="cov-toolbar">
    <a href="/roster/coverage?year=<?= $prevYear ?>&month=<?= $prevMonth ?>" class="btn btn-outline btn-sm">← <?= date('M', mktime(0,0,0,$prevMonth,1,$prevYear)) ?></a>
    <span style="font-size:17px;font-weight:800;min-width:148px;text-align:center;">
        <?= date('F Y', mktime(0,0,0,$month,1,$year)) ?>
    </span>
    <a href="/roster/coverage?year=<?= $nextYear ?>&month=<?= $nextMonth ?>" class="btn btn-outline btn-sm"><?= date('M', mktime(0,0,0,$nextMonth,1,$nextYear)) ?> →</a>
    <a href="/roster?year=<?= $year ?>&month=<?= $month ?>" class="btn btn-ghost btn-sm" style="margin-left:auto;">← Workbench</a>
</div>

<!-- Summary conflict cards -->
<div class="conflict-cards">
    <div class="conflict-card">
        <div class="conflict-card-hdr">
            <span class="conflict-card-icon">⚠</span>
            <span class="conflict-card-title">Crew Compliance Issues</span>
        </div>
        <div class="conflict-card-count" style="color:#dc2626;"><?= count($conflicts) ?></div>
        <div style="font-size:11px;color:var(--text-muted);">crew with expired or expiring documents</div>
    </div>
    <div class="conflict-card">
        <div class="conflict-card-hdr">
            <span class="conflict-card-icon">📅</span>
            <span class="conflict-card-title">Uncovered Days</span>
        </div>
        <div class="conflict-card-count" style="color:#7c3aed;"><?= count($uncovered) ?></div>
        <div style="font-size:11px;color:var(--text-muted);">days with no rostered crew data</div>
    </div>
    <div class="conflict-card">
        <div class="conflict-card-hdr">
            <span class="conflict-card-icon">👥</span>
            <span class="conflict-card-title">Low Coverage Days</span>
        </div>
        <div class="conflict-card-count" style="color:#d97706;"><?= count($understaf) ?></div>
        <div style="font-size:11px;color:var(--text-muted);">days with fewer than 2 flight crew</div>
    </div>
    <div class="conflict-card">
        <div class="conflict-card-hdr">
            <span class="conflict-card-icon">🛡</span>
            <span class="conflict-card-title">Reserve Gaps</span>
        </div>
        <div class="conflict-card-count" style="color:#b45309;"><?= count($resGaps) ?></div>
        <div style="font-size:11px;color:var(--text-muted);">days with no standby / reserve crew</div>
    </div>
    <div class="conflict-card">
        <div class="conflict-card-hdr">
            <span class="conflict-card-icon">🏖</span>
            <span class="conflict-card-title">Leave Concentration</span>
        </div>
        <div class="conflict-card-count" style="color:#059669;"><?= count($lvOverlap) ?></div>
        <div style="font-size:11px;color:var(--text-muted);">days with 3+ crew on leave simultaneously</div>
    </div>
</div>

<!-- Heatmap legend -->
<div class="legend-bar">
    <?php foreach ($levelStyles as $k => $s): ?>
    <div class="legend-bar-item">
        <div class="legend-swatch" style="background:<?= $s['bg'] ?>;border:1px solid rgba(0,0,0,.08);"></div>
        <span><?= $s['label'] ?></span>
    </div>
    <?php endforeach; ?>
    <div class="legend-bar-item">
        <div class="legend-swatch" style="background:#dbeafe;border:2px solid #2563eb;"></div>
        <span>Today</span>
    </div>
</div>

<!-- Calendar heatmap grid -->
<div class="card" style="padding:16px;margin-bottom:20px;">
    <h3 style="margin:0 0 14px;font-size:14px;font-weight:700;">Monthly Coverage Heatmap</h3>

    <!-- Day-of-week headers -->
    <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:6px;margin-bottom:6px;">
        <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $dow): ?>
            <div style="text-align:center;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);"><?= $dow ?></div>
        <?php endforeach; ?>
    </div>

    <?php
    // Build calendar grid with leading empty cells
    $firstDay   = date('N', mktime(0,0,0,$month,1,$year)); // 1=Mon .. 7=Sun
    $today      = date('Y-m-d');
    $cellCount  = 0;
    echo '<div class="cov-grid">';

    // Leading empty cells
    for ($e = 1; $e < $firstDay; $e++) {
        echo '<div></div>';
        $cellCount++;
    }

    for ($d = 1; $d <= $daysInMonth; $d++):
        $dt      = sprintf('%04d-%02d-%02d', $year, $month, $d);
        $hmDay   = $heatmap[$dt] ?? ['count'=>0,'flight'=>0,'standby'=>0,'leave'=>0,'level'=>'empty'];
        $level   = $hmDay['level'];
        $ls      = $levelStyles[$level] ?? $levelStyles['empty'];
        $isTdy   = $dt === $today;
        $dow     = date('D', strtotime($dt));
        $conflicts_this_day = 0;
        // Count crew with compliance issues scheduled to fly this day
        foreach (($byDate[$dt]['users'] ?? []) as $du) {
            if (isset($conflicts[$du['id']]) && in_array($du['duty'], ['flight','pos','deadhead'])) $conflicts_this_day++;
        }
        $extraBorder = $isTdy ? 'box-shadow:0 0 0 2px #2563eb;' : '';
        $bg = $isTdy ? '#dbeafe' : $ls['bg'];
        $color = $isTdy ? '#1e40af' : $ls['color'];
    ?>
        <div class="cov-day" style="background:<?= $bg ?>;<?= $extraBorder ?>"
             title="<?= $dt ?> — <?= $hmDay['flight'] ?> flight, <?= $hmDay['standby'] ?> standby, <?= $hmDay['leave'] ?> leave">
            <div class="cov-day-dow"><?= $dow ?></div>
            <div class="cov-day-num" style="color:<?= $color ?>;"><?= $d ?></div>
            <?php if ($hmDay['count'] > 0): ?>
                <div class="cov-day-stat" style="color:<?= $color ?>;"><?= $hmDay['flight'] ?> FLT</div>
                <div class="cov-day-sub" style="color:<?= $color ?>;"><?= $hmDay['standby'] ?> SBY</div>
            <?php else: ?>
                <div class="cov-day-sub" style="color:<?= $ls['color'] ?>;">No data</div>
            <?php endif; ?>
            <?php if ($conflicts_this_day > 0): ?>
                <div style="font-size:8.5px;color:#dc2626;font-weight:700;margin-top:2px;">⚠ <?= $conflicts_this_day ?> issue<?= $conflicts_this_day>1?'s':'' ?></div>
            <?php endif; ?>
        </div>
    <?php
    $cellCount++;
    endfor;
    // Trailing empty cells
    $remainder = $cellCount % 7;
    if ($remainder > 0) for ($e = 0; $e < (7 - $remainder); $e++) echo '<div></div>';
    echo '</div>';
    ?>
</div>

<!-- Crew compliance issues table -->
<?php if (!empty($conflicts)): ?>
<div class="card" style="padding:0;overflow:hidden;margin-bottom:16px;">
    <div style="padding:14px 16px;border-bottom:1px solid var(--border);background:var(--bg-secondary);">
        <h3 style="margin:0;font-size:14px;font-weight:700;">Crew Compliance Issues</h3>
        <p style="margin:4px 0 0;font-size:12px;color:var(--text-muted);">Crew with expired or expiring documents — review before scheduling for flying duties</p>
    </div>
    <table class="crew-conflict-table">
        <thead>
            <tr>
                <th>Crew Member</th>
                <th>Severity</th>
                <th>Issues</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // We need user names — load them
            if (!empty($conflicts)):
                $uids = implode(',', array_map('intval', array_keys($conflicts)));
                $users = Database::fetchAll(
                    "SELECT u.id, u.name, u.employee_id, r.name AS role_name
                     FROM users u
                     LEFT JOIN user_roles ur ON ur.user_id = u.id
                     LEFT JOIN roles r ON r.id = ur.role_id
                     WHERE u.id IN ($uids)"
                );
                $userMap = [];
                foreach ($users as $u) { if (!isset($userMap[$u['id']])) $userMap[$u['id']] = $u; }

                foreach ($conflicts as $uid => $cf):
                    $u = $userMap[$uid] ?? null;
                    if (!$u) continue;
            ?>
            <tr>
                <td>
                    <strong><?= e($u['name']) ?></strong><br>
                    <span style="font-size:10px;color:var(--text-muted);font-family:monospace;"><?= e($u['employee_id'] ?? '') ?></span>
                </td>
                <td>
                    <span class="sev-<?= $cf['severity'] === 'critical' ? 'crit' : 'warn' ?>">
                        <?= $cf['severity'] === 'critical' ? '⛔ CRITICAL' : '⚡ WARNING' ?>
                    </span>
                </td>
                <td>
                    <ul style="margin:0;padding:0 0 0 16px;font-size:11px;color:var(--text-muted);">
                        <?php foreach ($cf['issues'] as $issue): ?>
                            <li><?= e($issue) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </td>
                <td>
                    <a href="/roster/assign?user_id=<?= $uid ?>" class="btn btn-ghost btn-xs" style="font-size:11px;">Reassign</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Coverage by day list (worst days) -->
<?php
$critDays = array_filter($heatmap, fn($h) => in_array($h['level'], ['critical','warn']));
arsort($critDays);
if (!empty($critDays)):
?>
<div class="card" style="padding:16px;margin-bottom:16px;">
    <h3 style="margin:0 0 12px;font-size:14px;font-weight:700;">Days Requiring Attention</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px;">
        <?php foreach ($critDays as $dt => $hm):
            $ls = $levelStyles[$hm['level']];
            $bdate = $byDate[$dt] ?? [];
        ?>
        <div style="background:<?= $ls['bg'] ?>;border-radius:7px;padding:10px 12px;border:1px solid rgba(0,0,0,.06);">
            <div style="font-weight:700;font-size:13px;color:<?= $ls['color'] ?>;"><?= date('D d M', strtotime($dt)) ?></div>
            <div style="font-size:11px;color:<?= $ls['color'] ?>;margin-top:3px;"><?= $ls['label'] ?></div>
            <div style="font-size:11px;margin-top:4px;color:var(--text-muted);">
                <?= $hm['flight'] ?> flight · <?= $hm['standby'] ?> standby · <?= $hm['leave'] ?> leave
            </div>
            <a href="/roster?year=<?= $year ?>&month=<?= $month ?>" style="font-size:11px;color:#2563eb;text-decoration:none;font-weight:600;">Fix →</a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
