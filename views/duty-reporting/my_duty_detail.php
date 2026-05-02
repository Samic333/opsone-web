<?php
/** OpsOne — Crew duty record detail (own record only). */
$r = $report;

$dur = isset($r['duration_minutes']) && $r['duration_minutes'] !== null
    ? sprintf('%dh %dm', intdiv((int)$r['duration_minutes'], 60), (int)$r['duration_minutes'] % 60)
    : '—';

$stateColor = match ($r['state']) {
    'checked_in', 'on_duty', 'exception_approved' => '#10b981',
    'checked_out'                                 => '#6366f1',
    'exception_pending_review'                    => '#f59e0b',
    'missed_report', 'exception_rejected'         => '#ef4444',
    default                                       => '#7484a8',
};
$stateLabel = ucfirst(str_replace('_', ' ', (string) $r['state']));

$dutyTypeLabel = static function (?string $t): string {
    return match ($t) {
        'flight'   => 'Flight',
        'standby'  => 'Standby',
        'reserve'  => 'Reserve',
        'rest'     => 'Rest',
        'sim'      => 'Simulator',
        'training' => 'Training',
        'leave'    => 'Leave',
        'maint'    => 'Maintenance',
        'off'      => 'Day Off',
        default    => $t ? ucfirst((string)$t) : '—',
    };
};

$crewRoleLabel = static function (?string $role): string {
    return match ($role) {
        'captain'       => 'Captain',
        'first_officer' => 'First Officer',
        'cabin_crew'    => 'Cabin Crew',
        'purser'        => 'Purser',
        'scc'           => 'Senior Cabin Crew',
        'engineer'      => 'Engineer',
        'observer'      => 'Observer',
        'jumpseat'      => 'Jumpseat',
        default         => $role ? ucfirst(str_replace('_', ' ', (string) $role)) : '—',
    };
};
?>

<!-- ═══ Back link + header ═══════════════════════════════════════════════ -->
<div style="margin-bottom:18px;">
    <a href="/my-duty" class="btn btn-sm btn-outline">← Back to Duty Time</a>
</div>

<div class="card" style="padding:22px 24px; margin-bottom:20px;">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
        <div>
            <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em;">Duty Record</div>
            <div style="font-size:24px; font-weight:700; color:var(--text-primary); margin-top:4px;">
                <?= e(substr((string)($r['check_in_at_utc'] ?? ''), 0, 10) ?: 'No date') ?>
                <?php if ($roster && !empty($roster['duty_code'])): ?>
                    <span style="color:var(--text-secondary); font-weight:500;"> · <?= e($roster['duty_code']) ?></span>
                <?php endif; ?>
            </div>
            <div style="margin-top:6px; display:flex; align-items:center; gap:10px;">
                <span style="display:inline-flex; align-items:center; gap:6px; padding:4px 12px; border-radius:999px; background:<?= $stateColor ?>22; color:<?= $stateColor ?>; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.04em;">
                    <span style="width:6px; height:6px; border-radius:50%; background:<?= $stateColor ?>;"></span>
                    <?= e($stateLabel) ?>
                </span>
                <?php if ($roster && !empty($roster['duty_type'])): ?>
                    <span class="text-xs text-muted"><?= e($dutyTypeLabel($roster['duty_type'])) ?> · roster</span>
                <?php endif; ?>
            </div>
        </div>
        <div style="text-align:right;">
            <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em;">Duration</div>
            <div style="font-size:28px; font-weight:700; color:var(--text-primary); font-variant-numeric:tabular-nums;"><?= e($dur) ?></div>
        </div>
    </div>
</div>

<!-- ═══ Two-column: Duty info | Roster + Flight info ═════════════════════ -->
<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">

    <!-- Duty info -->
    <div class="card">
        <div class="card-header"><div class="card-title">Duty Info</div></div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px 20px;">
            <div>
                <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em;">Check-in (UTC)</div>
                <div class="text-sm" style="font-variant-numeric:tabular-nums;"><?= e((string)($r['check_in_at_utc'] ?? '—')) ?></div>
            </div>
            <div>
                <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em;">Check-out (UTC)</div>
                <div class="text-sm" style="font-variant-numeric:tabular-nums;"><?= e((string)($r['check_out_at_utc'] ?? '—')) ?></div>
            </div>
            <div>
                <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em;">Method</div>
                <div class="text-sm"><?= e(ucfirst(str_replace('_',' ', (string)($r['check_in_method'] ?? '—')))) ?></div>
            </div>
            <div>
                <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em;">Station</div>
                <div class="text-sm">
                    <?php if ($base): ?><strong><?= e($base['name']) ?></strong> (<?= e($base['code']) ?>)<?php else: ?>—<?php endif; ?>
                </div>
            </div>
            <?php if (!empty($r['inside_geofence']) || $r['inside_geofence'] === 0): ?>
            <div>
                <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em;">Geo-fence</div>
                <div class="text-sm"><?= $r['inside_geofence'] ? '✅ Inside' : '⚠️ Outside' ?></div>
            </div>
            <?php endif; ?>
            <div>
                <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em;">Record ID</div>
                <div class="text-sm" style="font-variant-numeric:tabular-nums;">#<?= (int) $r['id'] ?></div>
            </div>
        </div>
        <?php if (!empty($r['notes'])): ?>
        <div style="margin-top:18px; padding-top:14px; border-top:1px solid var(--border-light, rgba(255,255,255,0.05));">
            <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em; margin-bottom:6px;">Remarks</div>
            <div class="text-sm" style="white-space:pre-wrap;"><?= e((string) $r['notes']) ?></div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Roster + Flight info -->
    <div class="card">
        <div class="card-header"><div class="card-title">Roster &amp; Flight</div></div>
        <?php if ($roster): ?>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px 20px;">
            <div>
                <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em;">Duty Type</div>
                <div class="text-sm"><strong><?= e($dutyTypeLabel($roster['duty_type'] ?? null)) ?></strong></div>
            </div>
            <div>
                <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em;">Duty Code</div>
                <div class="text-sm"><?= e((string)($roster['duty_code'] ?? '—')) ?></div>
            </div>
            <?php if (!empty($roster['fleet_name'])): ?>
            <div>
                <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em;">Fleet</div>
                <div class="text-sm"><?= e($roster['fleet_name']) ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($roster['base_code'])): ?>
            <div>
                <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em;">Roster Base</div>
                <div class="text-sm"><?= e($roster['base_name'] ?? '') ?> (<?= e($roster['base_code']) ?>)</div>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <p class="text-sm text-muted" style="margin:0 0 16px;">No roster entry matched this duty record.</p>
        <?php endif; ?>

        <?php if ($flight): ?>
        <div style="margin-top:<?= $roster ? 18 : 0 ?>px; padding-top:<?= $roster ? 14 : 0 ?>px; <?= $roster ? 'border-top:1px solid var(--border-light, rgba(255,255,255,0.05));' : '' ?>">
            <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em; margin-bottom:8px;">Flight Assignment</div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px 20px;">
                <div>
                    <div class="text-xs text-muted">Flight</div>
                    <div class="text-sm"><strong><?= e((string) $flight['flight_number']) ?></strong></div>
                </div>
                <div>
                    <div class="text-xs text-muted">Route</div>
                    <div class="text-sm">
                        <?php if (!empty($flight['departure']) && !empty($flight['arrival'])): ?>
                            <strong><?= e($flight['departure']) ?> → <?= e($flight['arrival']) ?></strong>
                        <?php else: ?>—<?php endif; ?>
                    </div>
                </div>
                <div>
                    <div class="text-xs text-muted">STD / STA</div>
                    <div class="text-sm" style="font-variant-numeric:tabular-nums;">
                        <?= e((string)($flight['std'] ?? '—')) ?> / <?= e((string)($flight['sta'] ?? '—')) ?>
                    </div>
                </div>
                <div>
                    <div class="text-xs text-muted">Status</div>
                    <div class="text-sm"><?= e(ucfirst(str_replace('_',' ', (string)($flight['status'] ?? '—')))) ?></div>
                </div>
            </div>
        </div>
        <?php elseif (!$roster): ?>
        <div class="empty-state" style="padding:20px 0;">
            <p class="text-sm text-muted">No flight or roster information for this date.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ═══ Crew on the same flight ═══════════════════════════════════════════ -->
<?php if (!empty($flightCrew)): ?>
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <div class="card-title">Flight Crew</div>
        <span class="text-xs text-muted"><?= count($flightCrew) ?> crew assigned</span>
    </div>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:12px;">
        <?php foreach ($flightCrew as $cr): ?>
        <div style="display:flex; align-items:center; gap:12px; padding:10px 12px; background:var(--bg-input, rgba(255,255,255,0.03)); border-radius:8px;">
            <div style="width:36px; height:36px; border-radius:50%; background:linear-gradient(135deg,#3b82f6,#06b6d4); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:13px; flex-shrink:0;">
                <?= e(strtoupper(substr((string)($cr['name'] ?? '?'), 0, 1))) ?>
            </div>
            <div style="flex:1; min-width:0;">
                <div class="text-sm" style="font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= e((string)($cr['name'] ?? 'Unknown')) ?></div>
                <div class="text-xs text-muted"><?= e($crewRoleLabel($cr['role_on_flight'] ?? null)) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ═══ Exceptions / Remarks ═══════════════════════════════════════════ -->
<?php if (!empty($exceptions)): ?>
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <div class="card-title">Exceptions &amp; Reviews</div>
        <span class="text-xs text-muted"><?= count($exceptions) ?> entr<?= count($exceptions) === 1 ? 'y' : 'ies' ?></span>
    </div>
    <ul class="activity-list" style="list-style:none; margin:0; padding:0;">
        <?php foreach ($exceptions as $ex):
            $exColor = match ($ex['status']) {
                'approved' => '#10b981',
                'rejected' => '#ef4444',
                default    => '#f59e0b',
            };
            $reasonLabel = DutyException::REASONS[$ex['reason_code']] ?? ucfirst(str_replace('_',' ', $ex['reason_code']));
        ?>
        <li class="activity-item" style="display:flex; gap:12px; padding:12px 0; border-bottom:1px solid var(--border-light, rgba(255,255,255,0.05));">
            <div style="width:8px; height:8px; border-radius:50%; background:<?= $exColor ?>; margin-top:7px; flex-shrink:0;"></div>
            <div style="flex:1;">
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <strong><?= e($reasonLabel) ?></strong>
                    <span style="display:inline-flex; padding:2px 8px; border-radius:999px; background:<?= $exColor ?>22; color:<?= $exColor ?>; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.04em;">
                        <?= e(ucfirst($ex['status'])) ?>
                    </span>
                </div>
                <?php if (!empty($ex['reason_text'])): ?>
                <div class="text-sm" style="margin-top:4px; white-space:pre-wrap;"><?= e($ex['reason_text']) ?></div>
                <?php endif; ?>
                <?php if (!empty($ex['review_notes'])): ?>
                <div class="text-sm text-muted" style="margin-top:6px; font-style:italic;">Reviewer: <?= e($ex['review_notes']) ?></div>
                <?php endif; ?>
                <div class="activity-time text-xs text-muted" style="margin-top:6px;">
                    Submitted <?= e((string) $ex['submitted_at']) ?>
                    <?php if (!empty($ex['reviewed_at'])): ?>
                        · Reviewed <?= e((string) $ex['reviewed_at']) ?>
                    <?php endif; ?>
                </div>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<!-- ═══ Request Correction ═══════════════════════════════════════════ -->
<div class="card">
    <div class="card-header">
        <div class="card-title">Request Correction</div>
        <span class="text-xs text-muted">Sends to your scheduler / chief pilot for review</span>
    </div>
    <?php if ($hasOpenCorrection): ?>
        <div style="padding:12px 14px; background:rgba(245,158,11,0.12); border:1px solid rgba(245,158,11,0.35); border-radius:8px;">
            <strong style="color:#f59e0b;">Correction request pending review.</strong>
            <span class="text-sm text-muted"> A manager will respond — see the Exceptions section above for status.</span>
        </div>
    <?php else: ?>
        <p class="text-sm text-muted" style="margin:0 0 14px;">
            Spotted an error in this record (wrong times, base, or notes)? Submit a correction request and a manager will review it.
        </p>
        <form method="POST" action="/my-duty/<?= (int) $r['id'] ?>/request-correction">
            <?= csrfField() ?>
            <label class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em; display:block; margin-bottom:6px;">
                What needs to be corrected? <span style="color:#ef4444;">*</span>
            </label>
            <textarea name="correction_note" class="form-control" rows="4" required maxlength="1000"
                      placeholder="e.g. Check-in time should be 06:30 UTC — I checked in at flight planning, not at the aircraft."></textarea>
            <div style="display:flex; gap:10px; margin-top:14px;">
                <button type="submit" class="btn btn-primary">Submit Correction Request</button>
                <a href="/my-duty" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</div>
