<?php
/** OpsVelo — Crew self-service duty reporting (redesigned). */
$isOnDuty        = !empty($current) && in_array($current['state'], ['checked_in','on_duty','exception_pending_review'], true);
$pendingReason   = $_SESSION['duty_exception_pending'] ?? null;
$needsReasonForm = !empty($pendingReason);

$agg = $aggregates ?? null;

// ─── Helpers ───────────────────────────────────────────────────────────────
$fmtHM = static function (?int $mins): string {
    if ($mins === null) return '—';
    return sprintf('%dh %dm', intdiv(max(0, $mins), 60), max(0, $mins) % 60);
};
$bandFor = static function (string $zone): array {
    // [class, color, label]
    return match ($zone) {
        'exceeded'    => ['red',   'var(--status-critical, #ef4444)', 'Exceeded'],
        'approaching' => ['amber', 'var(--status-advisory, #f59e0b)', 'Approaching'],
        default       => ['green', 'var(--status-cleared, #10b981)',  'Normal'],
    };
};

$liveDuration = $fmtHM($agg['active_duty_minutes'] ?? null);
$restText     = $fmtHM($agg['rest_minutes']        ?? null);

$stateLabel = isset($current['state'])
    ? ucfirst(str_replace('_', ' ', (string) $current['state']))
    : 'Off duty';
$stateColor = match ($current['state'] ?? null) {
    'checked_in', 'on_duty', 'exception_approved' => 'var(--status-cleared, #10b981)',
    'exception_pending_review'                    => 'var(--status-advisory, #f59e0b)',
    'missed_report', 'exception_rejected'         => 'var(--status-critical, #ef4444)',
    default                                       => 'var(--text-tertiary, #7484a8)',
};

[$mClass, $mColor, $mLabel] = $bandFor($agg['month_threshold'] ?? 'normal');
[$yClass, $yColor, $yLabel] = $bandFor($agg['ytd_threshold']   ?? 'normal');

$monthPctOfCap = ($agg && $agg['monthly_cap_hours'] > 0)
    ? min(100, round(($agg['duty_hours_month'] / $agg['monthly_cap_hours']) * 100))
    : 0;
$ytdPctOfCap = ($agg && $agg['yearly_cap_hours'] > 0)
    ? min(100, round(($agg['duty_hours_ytd'] / $agg['yearly_cap_hours']) * 100))
    : 0;

// Pill renderer used in the cards + breakdown table.
$pillHtml = static function (string $zone, ?string $labelOverride = null): string {
    $map = [
        'exceeded'    => ['#ef4444', 'Exceeded'],
        'approaching' => ['#f59e0b', 'Approaching'],
        'normal'      => ['#10b981', 'Normal'],
    ];
    [$color, $label] = $map[$zone] ?? $map['normal'];
    if ($labelOverride !== null) $label = $labelOverride;
    return '<span style="display:inline-flex; align-items:center; gap:6px; padding:3px 10px;'
         . 'border-radius:999px; background:' . $color . '22; color:' . $color
         . '; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.04em;">'
         . '<span style="width:6px; height:6px; border-radius:50%; background:' . $color . ';"></span>'
         . htmlspecialchars($label) . '</span>';
};

// Rest-period classifier used for the Rest Period KPI card.
$restClass = static function (?int $mins, bool $onDuty): array {
    if ($onDuty)        return ['On duty',    'var(--accent-blue, #3b82f6)'];
    if ($mins === null) return ['No record',  'var(--text-tertiary, #7484a8)'];
    if ($mins >= 600)   return ['Adequate',   'var(--status-cleared, #10b981)'];   // ≥10h
    if ($mins >= 480)   return ['Minimum',    'var(--status-advisory, #f59e0b)'];  // 8–10h
    return                       ['Below min', 'var(--status-critical, #ef4444)']; // <8h
};
[$restLabel, $restColor] = $restClass($agg['rest_minutes'] ?? null, $isOnDuty);
?>

<!-- ═══ Live Duty Status Banner ═══════════════════════════════════════════ -->
<?php
$bannerBg = $isOnDuty
    ? ($current['state'] === 'exception_pending_review'
        ? 'linear-gradient(135deg,#f59e0b 0%,#d97706 100%)'
        : 'linear-gradient(135deg,#10b981 0%,#059669 100%)')
    : 'linear-gradient(135deg,#1e3a8a 0%,#1e40af 100%)';
?>
<div style="background:<?= $bannerBg ?>; color:#fff; border-radius:12px; padding:18px 22px; margin-bottom:20px; box-shadow:0 4px 24px rgba(0,0,0,0.3); display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
    <div style="display:flex; align-items:center; gap:14px; flex:1; min-width:240px;">
        <span style="display:inline-block; width:14px; height:14px; border-radius:50%; background:#fff; box-shadow:0 0 0 4px rgba(255,255,255,0.25);"></span>
        <div>
            <div style="font-size:11px; text-transform:uppercase; letter-spacing:0.08em; opacity:0.85;">
                <?= $isOnDuty ? 'On duty now' : 'Off duty' ?>
            </div>
            <div style="font-size:20px; font-weight:700; margin-top:2px;">
                <?= e($stateLabel) ?>
                <?php if ($isOnDuty): ?>
                    <span style="opacity:0.85; font-weight:500;"> · for <?= e($liveDuration) ?></span>
                <?php elseif (($agg['rest_minutes'] ?? null) !== null): ?>
                    <span style="opacity:0.85; font-weight:500;"> · rested <?= e($restText) ?></span>
                <?php endif; ?>
            </div>
            <?php if ($isOnDuty && !empty($current['check_in_at_utc'])): ?>
            <div style="font-size:12px; opacity:0.85; margin-top:4px;">
                Check-in <?= e($current['check_in_at_utc']) ?> UTC
                <?php if (!empty($current['check_in_method'])): ?>
                    · <?= e(ucfirst(str_replace('_',' ', $current['check_in_method']))) ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($isOnDuty): ?>
        <form method="POST" action="/my-duty/clock-out" style="margin:0;">
            <?= csrfField() ?>
            <input type="hidden" name="notes" value="">
            <button type="submit" class="btn" style="background:#fff; color:<?= $current['state'] === 'exception_pending_review' ? '#d97706' : '#059669' ?>; font-weight:700; padding:10px 20px; border:none; border-radius:8px; cursor:pointer; font-size:13px;">
                Clock Out →
            </button>
        </form>
    <?php elseif (!$needsReasonForm): ?>
        <form method="POST" action="/my-duty/check-in" style="margin:0;">
            <?= csrfField() ?>
            <button type="submit" class="btn" style="background:#fff; color:#1e40af; font-weight:700; padding:10px 20px; border:none; border-radius:8px; cursor:pointer; font-size:13px;">
                Report for Duty →
            </button>
        </form>
    <?php endif; ?>
</div>

<?php if ($needsReasonForm): ?>
<!-- Exception reason still required -->
<?php
$reasonLabels = [
    'outside_geofence'    => 'Outside geo-fence',
    'gps_unavailable'     => 'GPS unavailable',
    'offline'             => 'No connectivity',
    'forgot_clock_out'    => 'Forgot to clock out',
    'wrong_base_detected' => 'Wrong base detected',
    'duplicate_attempt'   => 'Duplicate check-in attempt',
    'outstation'          => 'Reporting from out-station',
    'manual_correction'   => 'Manual correction',
    'other'               => 'Other',
];
$currentReason = $pendingReason['reason_code'] ?? 'other';
?>
<div class="card" style="padding:18px 22px; margin-bottom:20px; border-left:3px solid #f59e0b;">
    <div style="display:flex; align-items:flex-start; gap:12px; margin-bottom:14px;">
        <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#f59e0b; margin-top:6px;"></span>
        <div>
            <strong style="color:#f59e0b;">Exception reason required: <?= e($reasonLabels[$currentReason] ?? $currentReason) ?>.</strong>
            <div class="text-sm text-muted">Provide a brief note so your manager can review this exception.</div>
        </div>
    </div>
    <form method="POST" action="/my-duty/check-in">
        <?= csrfField() ?>
        <input type="hidden" name="exception_reason_code" value="<?= e($currentReason) ?>">
        <textarea name="exception_reason_text" class="form-control" rows="3" required
                  placeholder="Briefly explain the exception…"><?= e($pendingReason['notes'] ?? '') ?></textarea>
        <div style="display:flex; gap:10px; margin-top:14px;">
            <button type="submit" class="btn btn-primary" style="flex:1; padding:12px; font-weight:700;">Submit with Exception</button>
            <a href="/my-duty" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
<?php unset($_SESSION['duty_exception_pending']); ?>
<?php endif; ?>

<?php if ($agg): ?>
<!-- ═══ Summary KPI Grid ═══════════════════════════════════════════════════ -->
<div class="stats-grid" style="margin-bottom:20px;">
    <div class="stat-card blue">
        <div class="stat-label">This Month</div>
        <div class="stat-value"><?= e((string)$agg['duty_hours_month']) ?>h</div>
        <div style="margin-top:8px; display:flex; align-items:center; gap:8px; font-size:11px; color:var(--text-secondary);">
            <span>of <?= (int)$agg['monthly_cap_hours'] ?>h cap</span>
            <?= $pillHtml($agg['month_threshold'] ?? 'normal') ?>
        </div>
    </div>
    <div class="stat-card cyan">
        <div class="stat-label">Previous Month</div>
        <div class="stat-value"><?= e((string)$agg['duty_hours_prev']) ?>h</div>
        <div style="margin-top:8px; font-size:11px; color:var(--text-secondary);"><?= e(date('M Y', strtotime('-1 month'))) ?> total</div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Year to Date</div>
        <div class="stat-value"><?= e((string)$agg['duty_hours_ytd']) ?>h</div>
        <div style="margin-top:8px; display:flex; align-items:center; gap:8px; font-size:11px; color:var(--text-secondary);">
            <span>of <?= (int)$agg['yearly_cap_hours'] ?>h cap</span>
            <?= $pillHtml($agg['ytd_threshold'] ?? 'normal') ?>
        </div>
    </div>
    <div class="stat-card <?= $mClass === 'red' ? 'red' : ($mClass === 'amber' ? 'yellow' : 'purple') ?>">
        <div class="stat-label">Remaining This Month</div>
        <div class="stat-value"><?= e((string)$agg['remaining_month_hours']) ?>h</div>
        <div style="margin-top:8px; font-size:11px; color:var(--text-secondary);">
            <?= $agg['remaining_month_hours'] > 0 ? 'Available before cap' : 'Monthly cap reached' ?>
        </div>
    </div>
    <div class="stat-card <?= $isOnDuty ? 'green' : 'cyan' ?>">
        <div class="stat-label">Active Duty</div>
        <div class="stat-value" style="font-size:<?= $isOnDuty ? '32' : '24' ?>px;">
            <?= $isOnDuty ? e($liveDuration) : 'Off' ?>
        </div>
        <div style="margin-top:8px; font-size:11px; color:var(--text-secondary);">
            <?= $isOnDuty ? 'Live timer since check-in' : 'Not currently on duty' ?>
        </div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-label">Rest Period</div>
        <div class="stat-value" style="font-size:24px;">
            <?= $isOnDuty ? '—' : e($restText) ?>
        </div>
        <div style="margin-top:8px; display:flex; align-items:center; gap:8px; font-size:11px; color:var(--text-secondary);">
            <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:<?= $restColor ?>;"></span>
            <span><?= e($restLabel) ?></span>
        </div>
    </div>
</div>

<!-- ═══ Threshold Status Card ════════════════════════════════════════════ -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <div class="card-title">Threshold Status</div>
        <span class="text-xs text-muted">Reference caps · <a href="/duty-reporting/settings" style="color:var(--accent-blue, #3b82f6); text-decoration:none;">configure in settings →</a></span>
    </div>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; padding:8px 0;">
        <!-- Monthly -->
        <div>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <span style="font-size:13px; color:var(--text-secondary);">Monthly duty</span>
                <?= $pillHtml($agg['month_threshold'] ?? 'normal') ?>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:6px; font-size:13px;">
                <span style="color:var(--text-primary); font-weight:700; font-variant-numeric:tabular-nums;">
                    <?= e((string)$agg['duty_hours_month']) ?>h <span style="color:var(--text-secondary); font-weight:500;">/ <?= (int)$agg['monthly_cap_hours'] ?>h</span>
                </span>
                <span style="color:var(--text-secondary); font-size:12px;"><?= (int)$monthPctOfCap ?>%</span>
            </div>
            <div style="height:10px; background:var(--bg-secondary, rgba(255,255,255,0.05)); border-radius:5px; overflow:hidden;">
                <div style="height:100%; width:<?= (int)$monthPctOfCap ?>%; background:<?= $mColor ?>; transition:width .25s; border-radius:5px;"></div>
            </div>
            <?php if ($mClass !== 'green'): ?>
            <p style="margin:8px 0 0; font-size:11px; color:<?= $mColor ?>;">
                <?= $mClass === 'red'
                    ? 'Monthly duty cap exceeded — review FTL rest requirements.'
                    : 'Approaching monthly duty cap — plan rest accordingly.' ?>
            </p>
            <?php endif; ?>
        </div>
        <!-- Yearly -->
        <div>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <span style="font-size:13px; color:var(--text-secondary);">Yearly duty</span>
                <?= $pillHtml($agg['ytd_threshold'] ?? 'normal') ?>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:6px; font-size:13px;">
                <span style="color:var(--text-primary); font-weight:700; font-variant-numeric:tabular-nums;">
                    <?= e((string)$agg['duty_hours_ytd']) ?>h <span style="color:var(--text-secondary); font-weight:500;">/ <?= (int)$agg['yearly_cap_hours'] ?>h</span>
                </span>
                <span style="color:var(--text-secondary); font-size:12px;"><?= (int)$ytdPctOfCap ?>%</span>
            </div>
            <div style="height:10px; background:var(--bg-secondary, rgba(255,255,255,0.05)); border-radius:5px; overflow:hidden;">
                <div style="height:100%; width:<?= (int)$ytdPctOfCap ?>%; background:<?= $yColor ?>; transition:width .25s; border-radius:5px;"></div>
            </div>
            <?php if ($yClass !== 'green'): ?>
            <p style="margin:8px 0 0; font-size:11px; color:<?= $yColor ?>;">
                <?= $yClass === 'red'
                    ? 'Yearly duty cap exceeded — escalate to your scheduler.'
                    : 'Approaching yearly duty cap.' ?>
            </p>
            <?php endif; ?>
        </div>
    </div>
    <p class="text-xs text-muted" style="margin:14px 0 0;">
        Threshold caps are configured per-airline. Final FTL compliance is governed by your operations manual and regulatory authority.
    </p>
</div>

<!-- ═══ Monthly Breakdown ═══════════════════════════════════════════════ -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <div class="card-title">Monthly Breakdown</div>
        <span class="text-xs text-muted">Last 6 months</span>
    </div>

    <?php
    // Mini bar chart: tallest bar = largest hour total in the window.
    $maxHrs = 0;
    foreach ($agg['breakdown'] as $b) { $maxHrs = max($maxHrs, (float)$b['duty_hours']); }
    $maxHrs = max($maxHrs, (float)$agg['monthly_cap_hours']);
    ?>
    <div style="display:grid; grid-template-columns:repeat(<?= count($agg['breakdown']) ?>, 1fr); gap:10px; align-items:end; height:120px; margin:6px 0 18px; padding:0 4px;">
        <?php foreach ($agg['breakdown'] as $b):
            $hrs = (float) $b['duty_hours'];
            $pct = $maxHrs > 0 ? max(2, min(100, round(($hrs / $maxHrs) * 100))) : 2;
            [, $bColor, ] = $bandFor($b['threshold_status']);
        ?>
        <div title="<?= e($b['label']) ?> · <?= e((string)$hrs) ?>h" style="display:flex; flex-direction:column; align-items:center; justify-content:flex-end; height:100%;">
            <div style="font-size:10px; color:var(--text-secondary); margin-bottom:4px; font-variant-numeric:tabular-nums;"><?= e((string)$hrs) ?>h</div>
            <div style="width:100%; height:<?= (int)$pct ?>%; background:<?= $bColor ?>; border-radius:4px 4px 0 0; min-height:3px; transition:height .25s;"></div>
            <div style="font-size:10px; color:var(--text-secondary); margin-top:6px; text-transform:uppercase; letter-spacing:0.04em;"><?= e(substr($b['label'], 0, 3)) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th class="text-right">Duty Hours</th>
                    <th class="text-right">Flight Hours</th>
                    <th class="text-right">Duty Periods</th>
                    <th class="text-right">Flights</th>
                    <th class="text-right">Off / Rest</th>
                    <th>Threshold</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($agg['breakdown'] as $b): ?>
                <tr>
                    <td><strong><?= e($b['label']) ?></strong></td>
                    <td class="text-right" style="font-variant-numeric:tabular-nums;"><?= e((string)$b['duty_hours']) ?>h</td>
                    <td class="text-right text-muted">—</td>
                    <td class="text-right"><?= (int)$b['duty_periods'] ?></td>
                    <td class="text-right"><?= (int)$b['flights'] ?></td>
                    <td class="text-right text-muted"><?= (int)$b['off_days'] ?></td>
                    <td><?= $pillHtml($b['threshold_status']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="text-xs text-muted" style="margin:10px 0 0;">
        Flight hours are tracked separately in your e-logbook — column shown for future expansion.
    </p>
</div>
<?php endif; ?>

<!-- ═══ Duty History ═══════════════════════════════════════════════════ -->
<div class="card">
    <div class="card-header">
        <div class="card-title">Duty History</div>
        <span class="text-xs text-muted">Last <?= count($history) ?> records · click a row for details</span>
    </div>

    <?php if (empty($history)): ?>
        <div class="empty-state" style="padding:40px 20px;">
            <div style="font-size:32px; margin-bottom:8px;">📋</div>
            <p style="margin:0;">No duty records yet. Your history will appear here after your first check-in.</p>
        </div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Duty Start (UTC)</th>
                    <th>Duty End (UTC)</th>
                    <th class="text-right">Duration</th>
                    <th>Route / Code</th>
                    <th>Status</th>
                    <th>Notes</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($history as $r):
                $sc = match ($r['state']) {
                    'checked_in', 'on_duty', 'exception_approved' => '#10b981',
                    'checked_out'                                 => '#6366f1',
                    'exception_pending_review'                    => '#f59e0b',
                    'missed_report', 'exception_rejected'         => '#ef4444',
                    default                                       => '#7484a8',
                };
                $dur = isset($r['duration_minutes']) && $r['duration_minutes'] !== null
                    ? sprintf('%dh %dm', intdiv((int)$r['duration_minutes'], 60), (int)$r['duration_minutes'] % 60)
                    : '—';
                $date = !empty($r['check_in_at_utc']) ? substr((string) $r['check_in_at_utc'], 0, 10) : '—';
                $startTime = !empty($r['check_in_at_utc']) ? substr((string) $r['check_in_at_utc'], 11, 5) : '—';
                $endTime   = !empty($r['check_out_at_utc']) ? substr((string) $r['check_out_at_utc'], 11, 5) : '—';
                $route = $r['route'] ?? null;
                $code  = $r['duty_code'] ?? null;
                $notesShort = !empty($r['notes']) ? (mb_strlen((string) $r['notes']) > 40 ? mb_substr((string) $r['notes'], 0, 40) . '…' : $r['notes']) : '';
                $href = '/my-duty/' . (int) $r['id'];
            ?>
                <tr style="cursor:pointer;" onclick="window.location='<?= $href ?>'">
                    <td><strong><?= e($date) ?></strong></td>
                    <td class="text-sm" style="font-variant-numeric:tabular-nums;"><?= e($startTime) ?></td>
                    <td class="text-sm text-muted" style="font-variant-numeric:tabular-nums;"><?= e($endTime) ?></td>
                    <td class="text-right text-sm" style="font-variant-numeric:tabular-nums;"><?= e($dur) ?></td>
                    <td class="text-sm">
                        <?php if ($route): ?>
                            <strong><?= e($route) ?></strong>
                            <?php if ($code): ?><span class="text-muted"> · <?= e($code) ?></span><?php endif; ?>
                        <?php elseif ($code): ?>
                            <strong><?= e($code) ?></strong>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="status-badge" style="--badge-color:<?= $sc ?>; background:<?= $sc ?>22; color:<?= $sc ?>; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.04em;"><?= e(ucfirst(str_replace('_',' ', $r['state']))) ?></span></td>
                    <td class="text-sm text-muted" title="<?= e((string)($r['notes'] ?? '')) ?>"><?= e($notesShort) ?></td>
                    <td class="text-right"><a href="<?= $href ?>" class="btn btn-xs btn-outline" onclick="event.stopPropagation()">Detail →</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
