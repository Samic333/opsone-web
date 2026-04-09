<?php
/**
 * Roster monthly grid view
 * Variables: $year, $month, $daysInMonth, $grid (indexed [userId][date]),
 *            $crewList, $dutyTypes, $prevMonth, $prevYear, $nextMonth, $nextYear
 */
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
</style>

<!-- Month navigation -->
<div class="nav-bar">
    <a href="/roster?year=<?= $prevYear ?>&month=<?= $prevMonth ?>" class="btn btn-sm btn-outline">← Prev</a>
    <div class="month-label"><?= date('F Y', mktime(0,0,0,$month,1,$year)) ?></div>
    <a href="/roster?year=<?= $nextYear ?>&month=<?= $nextMonth ?>" class="btn btn-sm btn-outline">Next →</a>
    <?php if (hasAnyRole(['scheduler', 'airline_admin', 'super_admin', 'chief_pilot', 'head_cabin_crew'])): ?>
    <a href="/roster/assign" class="btn btn-sm btn-primary" style="margin-left: auto;">＋ Assign Duty</a>
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
