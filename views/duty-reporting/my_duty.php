<?php
/** OpsOne — Crew self-service duty reporting */
$isOnDuty        = !empty($current) && in_array($current['state'], ['checked_in','on_duty','exception_pending_review'], true);
$pendingReason   = $_SESSION['duty_exception_pending'] ?? null;
$needsReasonForm = !empty($pendingReason);

// Derive a live "on duty for …" string for the big card
$liveDuration = '';
if ($isOnDuty && !empty($current['check_in_at_utc'])) {
    $start = strtotime($current['check_in_at_utc']);
    if ($start) {
        $mins = max(0, (int) floor((time() - $start) / 60));
        $liveDuration = sprintf('%dh %dm', intdiv($mins, 60), $mins % 60);
    }
}

$stateLabel = isset($current['state'])
    ? ucfirst(str_replace('_', ' ', (string) $current['state']))
    : 'Not Reported';

$stateColor = match ($current['state'] ?? null) {
    'checked_in', 'on_duty', 'exception_approved' => '#10b981',
    'exception_pending_review'                    => '#f59e0b',
    'missed_report', 'exception_rejected'         => '#ef4444',
    default                                       => '#6b7280',
};

$agg = $aggregates ?? null;
$monthPctOfCap = ($agg && $agg['monthly_cap_hours'] > 0)
    ? min(100, round(($agg['duty_hours_month'] / $agg['monthly_cap_hours']) * 100))
    : 0;
$ytdPctOfCap = ($agg && $agg['yearly_cap_hours'] > 0)
    ? min(100, round(($agg['duty_hours_ytd'] / $agg['yearly_cap_hours']) * 100))
    : 0;
$bandFor = static function (int $pct): array {
    if ($pct >= 90) return ['red',    '#ef4444', 'Approaching cap'];
    if ($pct >= 70) return ['amber',  '#f59e0b', 'Heads-up'];
    return                ['green',   '#10b981', 'Within limits'];
};
[$mClass, $mColor, $mLabel] = $bandFor($monthPctOfCap);
[$yClass, $yColor, $yLabel] = $bandFor($ytdPctOfCap);
?>

<?php if ($agg): ?>
<!-- ─── Duty aggregates ─────────────────────────────────────────────── -->
<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-label">This Month</div>
        <div class="stat-value"><?= e((string)$agg['duty_hours_month']) ?>h</div>
    </div>
    <div class="stat-card cyan">
        <div class="stat-label">Previous Month</div>
        <div class="stat-value"><?= e((string)$agg['duty_hours_prev']) ?>h</div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Year to Date</div>
        <div class="stat-value"><?= e((string)$agg['duty_hours_ytd']) ?>h</div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-label">Flight Days (YTD)</div>
        <div class="stat-value"><?= (int)$agg['flight_days_ytd'] ?></div>
    </div>
</div>

<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <div class="card-title">Threshold Status</div>
        <span class="text-xs text-muted">Reference caps (illustrative)</span>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;padding:8px 0;">
        <div>
            <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:6px;">
                <span style="font-size:13px;color:var(--text-secondary);">Monthly duty</span>
                <span style="font-size:13px;font-weight:700;color:<?= $mColor ?>;">
                    <?= e((string)$agg['duty_hours_month']) ?>h / <?= (int)$agg['monthly_cap_hours'] ?>h · <?= $mLabel ?>
                </span>
            </div>
            <div style="height:8px;background:var(--bg-secondary);border-radius:4px;overflow:hidden;">
                <div style="height:100%;width:<?= $monthPctOfCap ?>%;background:<?= $mColor ?>;transition:width .25s;"></div>
            </div>
        </div>
        <div>
            <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:6px;">
                <span style="font-size:13px;color:var(--text-secondary);">Yearly duty</span>
                <span style="font-size:13px;font-weight:700;color:<?= $yColor ?>;">
                    <?= e((string)$agg['duty_hours_ytd']) ?>h / <?= (int)$agg['yearly_cap_hours'] ?>h · <?= $yLabel ?>
                </span>
            </div>
            <div style="height:8px;background:var(--bg-secondary);border-radius:4px;overflow:hidden;">
                <div style="height:100%;width:<?= $ytdPctOfCap ?>%;background:<?= $yColor ?>;transition:width .25s;"></div>
            </div>
        </div>
    </div>
    <p class="text-xs text-muted" style="margin:14px 0 0;">
        Defaults shown above are conservative reference values for monitoring only. Final FTL
        compliance is governed by your operations manual and regulatory authority.
    </p>
</div>

<!-- ─── Monthly breakdown ──────────────────────────────────────────── -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <div class="card-title">Monthly Breakdown</div>
        <span class="text-xs text-muted">Last 6 months</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th class="text-right">Duty Hours</th>
                    <th class="text-right">Flight Days</th>
                    <th class="text-right">Off / Rest Days</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($agg['breakdown'] as $b):
                    $hrs = round($b['duty_min'] / 60, 1);
                ?>
                <tr>
                    <td><strong><?= e($b['label']) ?></strong></td>
                    <td class="text-right" style="font-variant-numeric:tabular-nums;"><?= e((string)$hrs) ?>h</td>
                    <td class="text-right"><?= (int)$b['flight_days'] ?></td>
                    <td class="text-right text-muted"><?= (int)$b['off_days'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ─── Current status card ────────────────────────────────────────── -->
<div class="card" style="padding:22px 24px; margin-bottom:20px;">
    <div style="display:flex; align-items:center; gap:14px; margin-bottom:<?= $isOnDuty ? '18' : '12' ?>px;">
        <span style="display:inline-block; width:14px; height:14px; border-radius:50%; background:<?= $stateColor ?>;"></span>
        <div style="flex:1;">
            <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em;">Current Status</div>
            <div style="font-size:20px; font-weight:700; color:var(--text-primary);"><?= e($stateLabel) ?></div>
        </div>
        <?php if ($isOnDuty && $liveDuration !== ''): ?>
        <div style="text-align:right;">
            <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em;">On duty for</div>
            <div style="font-size:24px; font-weight:700; font-variant-numeric:tabular-nums;"><?= e($liveDuration) ?></div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($isOnDuty): ?>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px; padding:14px 0; border-top:1px solid var(--card-border, #eee); border-bottom:1px solid var(--card-border, #eee); margin-bottom:16px;">
            <div>
                <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em;">Check-in UTC</div>
                <div class="text-sm"><?= e($current['check_in_at_utc'] ?? '—') ?></div>
            </div>
            <div>
                <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em;">Method</div>
                <div class="text-sm"><?= e(ucfirst(str_replace('_',' ', $current['check_in_method'] ?? '—'))) ?></div>
            </div>
            <?php if (!empty($current['check_in_base_id'])): ?>
                <?php $b = Database::fetch("SELECT name, code FROM bases WHERE id = ?", [$current['check_in_base_id']]); ?>
                <?php if ($b): ?>
                <div>
                    <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em;">Base</div>
                    <div class="text-sm"><?= e($b['name'] . ' (' . $b['code'] . ')') ?></div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if ($current['state'] === 'exception_pending_review'): ?>
        <div style="padding:12px 14px; background:rgba(245,158,11,0.12); border:1px solid rgba(245,158,11,0.35); border-radius:8px; margin-bottom:16px;">
            <strong style="color:#f59e0b;">Pending manager review.</strong>
            <span class="text-sm text-muted"> You can still clock out when your shift ends — the record stays in exception review until a manager approves or rejects it.</span>
        </div>
        <?php endif; ?>

        <!-- Clock Out form -->
        <form method="POST" action="/my-duty/clock-out" style="margin:0;">
            <?= csrfField() ?>
            <label class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em;">Notes (optional)</label>
            <input type="text" name="notes" class="form-control" placeholder="e.g. Handover complete" style="margin-bottom:12px;">
            <button type="submit" class="btn btn-primary" style="background:#ef4444; border-color:#ef4444; width:100%; padding:14px; font-size:15px; font-weight:700;">
                Clock Out
            </button>
        </form>

    <?php elseif ($needsReasonForm): ?>
        <!-- Exception reason required ― re-render the check-in form with a note field -->
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
        <div style="padding:12px 14px; background:rgba(245,158,11,0.12); border:1px solid rgba(245,158,11,0.35); border-radius:8px; margin-bottom:14px;">
            <strong style="color:#f59e0b;">Exception reason required: <?= e($reasonLabels[$currentReason] ?? $currentReason) ?>.</strong>
            <span class="text-sm text-muted"> Provide a brief note so your manager can review this exception.</span>
        </div>
        <form method="POST" action="/my-duty/check-in">
            <?= csrfField() ?>
            <input type="hidden" name="exception_reason_code" value="<?= e($currentReason) ?>">
            <label class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em;">Reason note (required)</label>
            <textarea name="exception_reason_text" class="form-control" rows="3" required
                      placeholder="Briefly explain the exception…"><?= e($pendingReason['notes'] ?? '') ?></textarea>
            <div style="display:flex; gap:10px; margin-top:14px;">
                <button type="submit" class="btn btn-primary" style="flex:1; padding:14px; font-size:15px; font-weight:700;">
                    Submit with Exception
                </button>
                <a href="/my-duty" class="btn btn-ghost" style="flex:0 0 auto; padding:14px 20px;">Cancel</a>
            </div>
        </form>
        <?php unset($_SESSION['duty_exception_pending']); ?>

    <?php else: ?>
        <!-- Simple Report for Duty form -->
        <form method="POST" action="/my-duty/check-in" style="margin:0;">
            <?= csrfField() ?>
            <label class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em;">Notes (optional)</label>
            <input type="text" name="notes" class="form-control" placeholder="e.g. Outstation ops, reserve call-out…" style="margin-bottom:12px;">
            <button type="submit" class="btn btn-primary" style="background:#10b981; border-color:#10b981; width:100%; padding:14px; font-size:15px; font-weight:700;">
                Report for Duty
            </button>
        </form>
    <?php endif; ?>
</div>

<!-- ─── Recent history ─────────────────────────────────────────────── -->
<div class="card" style="padding:18px 20px;">
    <h3 style="margin:0 0 12px 0; font-size:15px;">My Recent Duty</h3>

    <?php if (empty($history)): ?>
        <div class="empty-state" style="padding:24px 0;">
            <div class="icon">📋</div>
            <p>No duty records yet. Your history will appear here after your first check-in.</p>
        </div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Check-in UTC</th>
                    <th>Check-out UTC</th>
                    <th>Duration</th>
                    <th>State</th>
                    <th>Method</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($history as $r): ?>
                <?php
                $sc = match ($r['state']) {
                    'checked_in', 'on_duty', 'exception_approved' => '#10b981',
                    'checked_out'                                 => '#6366f1',
                    'exception_pending_review'                    => '#f59e0b',
                    'missed_report', 'exception_rejected'         => '#ef4444',
                    default                                       => '#6b7280',
                };
                $dur = isset($r['duration_minutes']) && $r['duration_minutes'] !== null
                    ? sprintf('%dh %dm', intdiv((int)$r['duration_minutes'], 60), (int)$r['duration_minutes'] % 60)
                    : '—';
                ?>
                <tr>
                    <td class="text-sm text-muted"><?= e($r['check_in_at_utc']  ?? '—') ?></td>
                    <td class="text-sm text-muted"><?= e($r['check_out_at_utc'] ?? '—') ?></td>
                    <td class="text-sm"><?= e($dur) ?></td>
                    <td><span class="status-badge" style="--badge-color: <?= $sc ?>"><?= e(ucfirst(str_replace('_',' ', $r['state']))) ?></span></td>
                    <td class="text-sm"><?= e(ucfirst(str_replace('_',' ', $r['check_in_method'] ?? '—'))) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
