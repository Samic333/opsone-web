<?php
/**
 * My Roster — crew self-service personal view
 * Variables: $year, $month, $daysInMonth, $byDate, $upcoming, $summary,
 *            $dutyTypes, $activePeriod, $myChanges,
 *            $prevMonth, $prevYear, $nextMonth, $nextYear
 */
$today     = date('Y-m-d');
$dutyTypes = $dutyTypes ?? RosterModel::dutyTypes();
?>
<style>
.myr-layout{display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start;}
@media(max-width:900px){.myr-layout{grid-template-columns:1fr;}}
/* Monthly calendar */
.myr-cal-wrap{background:var(--bg-card);border:1px solid var(--border);border-radius:12px;overflow:hidden;}
.myr-cal-hdr{padding:16px 20px;background:var(--bg-secondary);border-bottom:1px solid var(--border);
 display:flex;align-items:center;gap:10px;}
.myr-cal-title{font-size:17px;font-weight:800;}
.myr-cal-nav{display:flex;align-items:center;gap:6px;margin-left:auto;}
.myr-cal-body{padding:16px;}
.myr-dow-row{display:grid;grid-template-columns:repeat(7,1fr);gap:4px;margin-bottom:6px;}
.myr-dow-cell{text-align:center;font-size:9px;font-weight:700;text-transform:uppercase;
 letter-spacing:.06em;color:var(--text-muted);padding:3px 0;}
.myr-cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:4px;}
.myr-day{border-radius:8px;min-height:60px;padding:6px;display:flex;flex-direction:column;
 border:1px solid var(--border);transition:box-shadow .12s;}
.myr-day:hover{box-shadow:0 1px 6px rgba(0,0,0,.1);}
.myr-day-num{font-size:13px;font-weight:700;line-height:1;margin-bottom:3px;}
.myr-day.is-today{border-color:#2563eb;box-shadow:0 0 0 1.5px #2563eb;}
.myr-day.is-today .myr-day-num{color:#2563eb;}
.myr-day.is-wknd{background:rgba(239,68,68,.025);}
.myr-duty-chip{display:inline-flex;align-items:center;justify-content:center;
 border-radius:4px;padding:2px 6px;font-size:10px;font-weight:800;letter-spacing:.04em;width:100%;margin-top:1px;}
.myr-day-code{font-size:9px;color:var(--text-muted);margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.myr-empty-day{color:var(--border);font-size:18px;text-align:center;margin-top:8px;}
/* Summary panel */
.myr-summary-card{background:var(--bg-card);border:1px solid var(--border);border-radius:12px;overflow:hidden;}
.myr-summary-hdr{padding:14px 16px;background:var(--bg-secondary);border-bottom:1px solid var(--border);}
.myr-summary-body{padding:16px;}
.myr-stat-row{display:flex;justify-content:space-between;align-items:center;
 padding:9px 0;border-bottom:1px solid var(--border);}
.myr-stat-row:last-child{border:none;}
.myr-stat-label{font-size:13px;color:var(--text-muted);}
.myr-stat-val{font-size:20px;font-weight:800;}
/* Upcoming duties */
.upcoming-card{background:var(--bg-card);border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-top:14px;}
.upcoming-item{display:flex;align-items:center;gap:12px;padding:10px 16px;border-bottom:1px solid var(--border);}
.upcoming-item:last-child{border:none;}
.upcoming-date{width:44px;text-align:center;flex-shrink:0;}
.upcoming-date-day{font-size:20px;font-weight:800;line-height:1;color:var(--text-primary);}
.upcoming-date-dow{font-size:10px;text-transform:uppercase;color:var(--text-muted);letter-spacing:.05em;}
.upcoming-chip{flex-shrink:0;}
.upcoming-detail{flex:1;min-width:0;}
.upcoming-duty-name{font-size:13px;font-weight:600;}
.upcoming-duty-note{font-size:11px;color:var(--text-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
/* Request form */
.req-card{background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:16px;margin-top:14px;}
/* Change requests history */
.cr-item{display:flex;align-items:flex-start;gap:10px;padding:10px 0;border-bottom:1px solid var(--border);}
.cr-item:last-child{border:none;}
.cr-type-chip{font-size:9.5px;font-weight:700;padding:2px 6px;border-radius:3px;white-space:nowrap;}
.cr-status-pending{background:#fef9c3;color:#92400e;}
.cr-status-approved{background:#d1fae5;color:#065f46;}
.cr-status-rejected{background:#fee2e2;color:#991b1b;}
.cr-status-noted{background:#f3f4f6;color:#374151;}
</style>

<!-- Period status banner -->
<?php if ($activePeriod): ?>
<div style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;border:1px solid;margin-bottom:14px;font-size:13px;
     <?= $activePeriod['status'] === 'published' ? 'border-color:#10b981;background:rgba(16,185,129,.08);color:#065f46;' : 'border-color:#f59e0b;background:rgba(245,158,11,.08);color:#b45309;' ?>">
    <?php if ($activePeriod['status'] === 'published'): ?>
        ✓ Roster published for <strong><?= e($activePeriod['name']) ?></strong>
        (<?= date('d M', strtotime($activePeriod['start_date'])) ?> – <?= date('d M Y', strtotime($activePeriod['end_date'])) ?>)
    <?php elseif ($activePeriod['status'] === 'frozen'): ?>
        🔒 Roster frozen for <strong><?= e($activePeriod['name']) ?></strong>
    <?php else: ?>
        ⚠ Roster for <strong><?= e($activePeriod['name']) ?></strong> is still in draft — check back soon
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="myr-layout">
    <!-- Monthly Calendar -->
    <div>
        <div class="myr-cal-wrap">
            <div class="myr-cal-hdr">
                <div class="myr-cal-title"><?= date('F Y', mktime(0,0,0,$month,1,$year)) ?></div>
                <div class="myr-cal-nav">
                    <a href="/my-roster?year=<?= $prevYear ?>&month=<?= $prevMonth ?>" class="btn btn-ghost btn-xs">← <?= date('M', mktime(0,0,0,$prevMonth,1,$prevYear)) ?></a>
                    <a href="/my-roster?year=<?= $nextYear ?>&month=<?= $nextMonth ?>" class="btn btn-ghost btn-xs"><?= date('M', mktime(0,0,0,$nextMonth,1,$nextYear)) ?> →</a>
                </div>
            </div>
            <div class="myr-cal-body">
                <div class="myr-dow-row">
                    <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $dow): ?>
                        <div class="myr-dow-cell"><?= $dow ?></div>
                    <?php endforeach; ?>
                </div>

                <?php
                $firstDay = date('N', mktime(0,0,0,$month,1,$year));
                echo '<div class="myr-cal-grid">';
                for ($e = 1; $e < $firstDay; $e++) echo '<div></div>';

                for ($d = 1; $d <= $daysInMonth; $d++):
                    $dt     = sprintf('%04d-%02d-%02d', $year, $month, $d);
                    $entry  = $byDate[$dt] ?? null;
                    $dow    = (int)date('N', strtotime($dt));
                    $isWknd = $dow >= 6;
                    $isTdy  = $dt === $today;
                    $cls    = 'myr-day';
                    if ($isTdy)  $cls .= ' is-today';
                    if ($isWknd) $cls .= ' is-wknd';
                    $dtype  = $entry['duty_type'] ?? null;
                    $dtMeta = $dtype ? ($dutyTypes[$dtype] ?? null) : null;
                ?>
                <div class="<?= $cls ?>">
                    <div class="myr-day-num"><?= $d ?></div>
                    <?php if ($entry && $dtMeta): ?>
                        <div class="myr-duty-chip"
                             style="background:<?= $dtMeta['bg'] ?>;color:<?= $dtMeta['color'] ?>;">
                            <?= e($entry['duty_code'] ?: $dtMeta['code']) ?>
                        </div>
                        <?php if ($entry['notes']): ?>
                            <div class="myr-day-code" title="<?= e($entry['notes']) ?>"><?= e(substr($entry['notes'],0,20)) ?></div>
                        <?php endif; ?>
                    <?php elseif ($entry && !$dtMeta): ?>
                        <div class="myr-day-code" style="color:var(--text-muted);font-size:10px;"><?= e(strtoupper($entry['duty_type'])) ?></div>
                    <?php else: ?>
                        <div class="myr-empty-day">·</div>
                    <?php endif; ?>
                </div>
                <?php endfor;
                echo '</div>'; ?>
            </div>
        </div>

        <!-- Upcoming 14 days -->
        <?php if (!empty($upcoming)): ?>
        <div class="upcoming-card">
            <div style="padding:12px 16px;border-bottom:1px solid var(--border);background:var(--bg-secondary);">
                <strong style="font-size:13px;">Next 14 Days</strong>
            </div>
            <?php foreach ($upcoming as $e):
                $dtMeta = $dutyTypes[$e['duty_type']] ?? ['bg'=>'#f3f4f6','color'=>'#6b7280','code'=>strtoupper(substr($e['duty_type'],0,3)),'label'=>$e['duty_type']];
                $isUpTdy = $e['roster_date'] === $today;
            ?>
            <div class="upcoming-item" style="<?= $isUpTdy ? 'background:rgba(37,99,235,.05);' : '' ?>">
                <div class="upcoming-date">
                    <div class="upcoming-date-day"><?= date('j', strtotime($e['roster_date'])) ?></div>
                    <div class="upcoming-date-dow"><?= date('D', strtotime($e['roster_date'])) ?></div>
                </div>
                <div class="upcoming-chip">
                    <span class="myr-duty-chip" style="background:<?= $dtMeta['bg'] ?>;color:<?= $dtMeta['color'] ?>;width:auto;padding:3px 8px;font-size:11px;">
                        <?= e($e['duty_code'] ?: $dtMeta['code']) ?>
                    </span>
                </div>
                <div class="upcoming-detail">
                    <div class="upcoming-duty-name"><?= $dtMeta['label'] ?></div>
                    <?php if ($e['notes']): ?>
                        <div class="upcoming-duty-note"><?= e($e['notes']) ?></div>
                    <?php endif; ?>
                </div>
                <?php if ($isUpTdy): ?>
                    <span style="font-size:10px;font-weight:700;color:#2563eb;padding:2px 6px;background:#dbeafe;border-radius:4px;">TODAY</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right sidebar -->
    <div>
        <!-- Monthly summary -->
        <div class="myr-summary-card">
            <div class="myr-summary-hdr">
                <strong style="font-size:13px;">Month Summary</strong>
                <div style="font-size:11px;color:var(--text-muted);margin-top:2px;"><?= date('F Y', mktime(0,0,0,$month,1,$year)) ?></div>
            </div>
            <div class="myr-summary-body">
                <div class="myr-stat-row">
                    <span class="myr-stat-label">Flight days</span>
                    <span class="myr-stat-val" style="color:#2563eb;"><?= $summary['flight'] ?></span>
                </div>
                <div class="myr-stat-row">
                    <span class="myr-stat-label">Standby / Reserve</span>
                    <span class="myr-stat-val" style="color:#d97706;"><?= ($summary['standby'] ?? 0) + ($summary['reserve'] ?? 0) ?></span>
                </div>
                <div class="myr-stat-row">
                    <span class="myr-stat-label">Training</span>
                    <span class="myr-stat-val" style="color:#7c3aed;"><?= $summary['training'] ?></span>
                </div>
                <div class="myr-stat-row">
                    <span class="myr-stat-label">Leave</span>
                    <span class="myr-stat-val" style="color:#059669;"><?= $summary['leave'] ?></span>
                </div>
                <div class="myr-stat-row">
                    <span class="myr-stat-label">Days off / Rest</span>
                    <span class="myr-stat-val" style="color:var(--text-muted);"><?= ($summary['off'] ?? 0) + ($summary['rest'] ?? 0) ?></span>
                </div>
                <div class="myr-stat-row" style="border-top:1px solid var(--border);margin-top:4px;padding-top:10px;">
                    <span class="myr-stat-label" style="font-weight:600;">Total rostered days</span>
                    <span class="myr-stat-val"><?= $summary['total'] ?></span>
                </div>
            </div>
        </div>

        <!-- Submit a request -->
        <div class="req-card">
            <strong style="font-size:13px;display:block;margin-bottom:10px;">Submit Request</strong>
            <form method="POST" action="/roster/changes/request">
                <?= csrfField() ?>
                <input type="hidden" name="redirect" value="/my-roster?year=<?= $year ?>&month=<?= $month ?>">
                <div style="margin-bottom:8px;">
                    <select name="change_type" class="form-control" style="font-size:12px;">
                        <option value="leave_request">Leave Request</option>
                        <option value="swap_request">Swap Request</option>
                        <option value="correction">Correction</option>
                        <option value="comment">Comment / Query</option>
                    </select>
                </div>
                <div style="margin-bottom:8px;">
                    <textarea name="message" class="form-control" rows="2" style="font-size:12px;"
                              placeholder="Describe your request…" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;font-size:12px;">Submit</button>
            </form>
        </div>

        <!-- My recent requests -->
        <?php if (!empty($myChanges)): ?>
        <div style="margin-top:14px;background:var(--bg-card);border:1px solid var(--border);border-radius:12px;overflow:hidden;">
            <div style="padding:12px 16px;border-bottom:1px solid var(--border);background:var(--bg-secondary);">
                <strong style="font-size:13px;">My Recent Requests</strong>
            </div>
            <div style="padding:8px 16px;">
                <?php
                $crTypeLabels = ['leave_request'=>'Leave','swap_request'=>'Swap','correction'=>'Correction','comment'=>'Comment','training_request'=>'Training'];
                foreach ($myChanges as $cr):
                ?>
                <div class="cr-item">
                    <div>
                        <div style="display:flex;align-items:center;gap:6px;margin-bottom:3px;">
                            <span class="cr-type-chip"><?= $crTypeLabels[$cr['change_type']] ?? ucfirst($cr['change_type']) ?></span>
                            <span class="cr-type-chip cr-status-<?= $cr['status'] ?>"><?= ucfirst($cr['status']) ?></span>
                        </div>
                        <div style="font-size:12px;color:var(--text-primary);"><?= e(substr($cr['message'],0,60)) ?><?= strlen($cr['message'])>60?'…':'' ?></div>
                        <div style="font-size:10px;color:var(--text-muted);margin-top:2px;"><?= date('d M Y', strtotime($cr['created_at'])) ?></div>
                        <?php if ($cr['response']): ?>
                            <div style="font-size:11px;color:var(--text-muted);font-style:italic;margin-top:3px;">Response: <?= e(substr($cr['response'],0,60)) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
