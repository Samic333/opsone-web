<?php /** OpsOne — Duty Reporting (Admin Overview: On Duty Now + tiles) */ ?>

<!-- ─── Dashboard tiles ───────────────────────────────────────────── -->
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); gap:14px; margin-bottom:20px;">
    <?php
    $tile = function(string $label, int $value, string $color, string $href = '') {
        $inner = '<div style="font-size:11px; font-weight:700; letter-spacing:0.08em; color:var(--text-muted); text-transform:uppercase;">'
               . e($label) . '</div>'
               . '<div style="font-size:32px; font-weight:700; color:' . $color . '; margin-top:4px;">' . (int)$value . '</div>';
        $card = '<div class="card" style="padding:16px 18px;">' . $inner . '</div>';
        return $href !== '' ? '<a href="' . e($href) . '" style="text-decoration:none; color:inherit;">' . $card . '</a>' : $card;
    };
    echo $tile('On Duty Now',        $counters['on_duty_now'],        '#10b981');
    echo $tile('Checked In Today',   $counters['checked_in_today'],   '#3b82f6', '/duty-reporting/history?from=' . date('Y-m-d'));
    echo $tile('Checked Out Today',  $counters['checked_out_today'],  '#6366f1', '/duty-reporting/history?from=' . date('Y-m-d'));
    echo $tile('Overdue Clock-Out',  $counters['overdue_clock_out'],  $counters['overdue_clock_out'] > 0 ? '#ef4444' : '#6b7280');
    echo $tile('Exceptions Pending', $counters['exceptions_pending'], $counters['exceptions_pending'] > 0 ? '#f59e0b' : '#6b7280', '/duty-reporting/exceptions');
    ?>
</div>

<!-- ─── On Duty Now table ──────────────────────────────────────────── -->
<div class="card" style="padding:18px 20px; margin-bottom:20px;">
    <div style="display:flex; align-items:center; margin-bottom:12px;">
        <h3 style="margin:0; font-size:15px;">On Duty Now</h3>
        <span class="text-xs text-muted" style="margin-left:10px;"><?= count($onDuty) ?> crew</span>
        <a href="/duty-reporting/history" class="btn btn-ghost btn-sm" style="margin-left:auto;">Full History</a>
    </div>

    <?php if (empty($onDuty)): ?>
        <div class="empty-state">
            <div class="icon">🟢</div>
            <h3>No crew currently on duty</h3>
            <p>Active check-ins will appear here as they happen.</p>
        </div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Crew</th>
                    <th>Role</th>
                    <th>Base</th>
                    <th>Check-in (UTC)</th>
                    <th>Method</th>
                    <th>Geofence</th>
                    <th>State</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($onDuty as $dr): ?>
                <?php
                $stateColor = match ($dr['state']) {
                    'checked_in', 'on_duty'              => '#10b981',
                    'exception_pending_review'           => '#f59e0b',
                    'missed_report'                      => '#ef4444',
                    default                              => '#6b7280',
                };
                $geoLabel = $dr['inside_geofence'] === null ? '—' :
                    ((int)$dr['inside_geofence'] === 1 ? 'Inside' : 'Outside');
                $geoColor = $dr['inside_geofence'] === null ? '#6b7280' :
                    ((int)$dr['inside_geofence'] === 1 ? '#10b981' : '#ef4444');
                ?>
                <tr>
                    <td>
                        <strong><?= e($dr['user_name'] ?? '—') ?></strong>
                        <br><span class="text-xs text-muted"><?= e($dr['user_email'] ?? '') ?></span>
                    </td>
                    <td class="text-sm"><?= e(ucfirst(str_replace('_',' ',$dr['role_at_event'] ?? '—'))) ?></td>
                    <td class="text-sm">
                        <?= e($dr['base_name'] ?? '—') ?>
                        <?php if (!empty($dr['base_code'])): ?><span class="text-xs text-muted">(<?= e($dr['base_code']) ?>)</span><?php endif; ?>
                    </td>
                    <td class="text-sm text-muted"><?= e($dr['check_in_at_utc'] ?? '—') ?></td>
                    <td class="text-sm"><?= e(ucfirst(str_replace('_',' ', $dr['check_in_method'] ?? '—'))) ?></td>
                    <td class="text-sm" style="color:<?= $geoColor ?>;"><?= e($geoLabel) ?></td>
                    <td><span class="status-badge" style="--badge-color: <?= $stateColor ?>"><?= e(ucfirst(str_replace('_',' ', $dr['state']))) ?></span></td>
                    <td><a href="/duty-reporting/report/<?= (int)$dr['id'] ?>" class="btn btn-ghost btn-sm">View</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- ─── Recent pending exceptions ──────────────────────────────────── -->
<div class="card" style="padding:18px 20px;">
    <div style="display:flex; align-items:center; margin-bottom:12px;">
        <h3 style="margin:0; font-size:15px;">Recent Pending Exceptions</h3>
        <span class="text-xs text-muted" style="margin-left:10px;"><?= count($pendingX) ?> pending</span>
        <a href="/duty-reporting/exceptions" class="btn btn-ghost btn-sm" style="margin-left:auto;">View All</a>
    </div>

    <?php if (empty($pendingX)): ?>
        <div class="empty-state"><p>No exceptions awaiting review.</p></div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Submitted</th>
                    <th>Crew</th>
                    <th>Reason</th>
                    <th>Note</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pendingX as $ex): ?>
                <tr>
                    <td class="text-sm text-muted"><?= e($ex['submitted_at']) ?></td>
                    <td><?= e($ex['user_name'] ?? '—') ?></td>
                    <td class="text-sm"><?= e(DutyException::REASONS[$ex['reason_code']] ?? $ex['reason_code']) ?></td>
                    <td class="text-sm"><?= e(mb_substr((string)($ex['reason_text'] ?? ''), 0, 80)) ?></td>
                    <td><a href="/duty-reporting/report/<?= (int)$ex['duty_report_id'] ?>" class="btn btn-ghost btn-sm">Review</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
