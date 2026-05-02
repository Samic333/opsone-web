<?php
// Role-aware title so cabin crew don't see "Pilot Dashboard" on their own page.
$__isCabin = function_exists('hasRole') ? hasRole('cabin_crew') : false;
$__isPilot = function_exists('hasRole') ? hasRole('pilot') : false;
if ($__isCabin && !$__isPilot) {
    $pageTitle    = 'Cabin Crew Dashboard';
    $pageSubtitle = 'Crew Operations Overview';
} else {
    $pageTitle    = 'Pilot Dashboard';
    $pageSubtitle = 'Crew Operations Overview';
}
ob_start();

// ─── Duty Reporting widget (if tenant allows this role) ───────────────────
$dutyAllowed = false;
$dutyOpen    = null;
try {
    $__tid = (int) currentTenantId();
    if ($__tid > 0 && class_exists('DutyReportingSettings')) {
        $__s = DutyReportingSettings::forTenant($__tid);
        if (!empty($__s['enabled'])
            && DutyReportingSettings::userAllowed($__tid, $_SESSION['user_roles'] ?? [])) {
            $dutyAllowed = true;
            $__uid = (int) ($_SESSION['user']['id'] ?? 0);
            if ($__uid > 0 && class_exists('DutyReport')) {
                $dutyOpen = DutyReport::findOpenForUser($__tid, $__uid);
            }
        }
    }
} catch (\Throwable $e) { /* widget is optional — never break the dashboard */ }

// Helper: human-readable duty type label.
$__dutyTypeLabel = static function (?string $t): string {
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
        default    => ucfirst((string)$t),
    };
};
?>

<?php if ($dutyAllowed): ?>
    <?php if ($dutyOpen && in_array($dutyOpen['state'], ['checked_in','on_duty','exception_pending_review'], true)):
        $onDutySince = strtotime($dutyOpen['check_in_at_utc'] ?? '') ?: 0;
        $mins        = $onDutySince ? max(0, (int) floor((time() - $onDutySince) / 60)) : 0;
        $durText     = sprintf('%dh %dm', intdiv($mins, 60), $mins % 60);
        $pending     = $dutyOpen['state'] === 'exception_pending_review';
        $bg          = $pending
            ? 'linear-gradient(135deg,#f59e0b 0%,#d97706 100%)'
            : 'linear-gradient(135deg,#10b981 0%,#059669 100%)';
    ?>
    <div style="background:<?= $bg ?>; color:#fff; border-radius:10px; padding:14px 18px; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
        <div style="display:flex; align-items:center; gap:12px;">
            <span style="display:inline-flex; color:#fff;"><?= sidebarIcon($pending ? 'clock' : 'paper-airplane', 22) ?></span>
            <div>
                <strong style="font-size:14px; display:block;">
                    <?= $pending ? 'On duty — pending manager review' : 'On duty now' ?>
                </strong>
                <span style="font-size:12px; opacity:0.9;">
                    Checked in at <?= e($dutyOpen['check_in_at_utc'] ?? '—') ?> UTC · On duty for <?= e($durText) ?>
                </span>
            </div>
        </div>
        <a href="/my-duty" style="background:#fff; color:<?= $pending ? '#d97706' : '#059669' ?>; font-weight:700; padding:8px 16px; border-radius:8px; text-decoration:none; font-size:13px; white-space:nowrap; flex-shrink:0;">
            Clock Out →
        </a>
    </div>
    <?php else: ?>
    <div style="background:linear-gradient(135deg,#2563eb 0%,#1e40af 100%); color:#fff; border-radius:10px; padding:14px 18px; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
        <div style="display:flex; align-items:center; gap:12px;">
            <span style="display:inline-flex; color:#fff;"><?= sidebarIcon('check-badge', 22) ?></span>
            <div>
                <strong style="font-size:14px; display:block;">Not currently on duty</strong>
                <span style="font-size:12px; opacity:0.9;">Report for duty to start your shift.</span>
            </div>
        </div>
        <a href="/my-duty" style="background:#fff; color:#1e40af; font-weight:700; padding:8px 16px; border-radius:8px; text-decoration:none; font-size:13px; white-space:nowrap; flex-shrink:0;">
            Report for Duty →
        </a>
    </div>
    <?php endif; ?>
<?php endif; ?>

<?php
// ─── Compact Duty Time summary card ──────────────────────────────────────
// Always shown when the duty module is allowed for this role/tenant.
if ($dutyAllowed):
    $__monthHrs    = (float) ($data['duty_hours_month']     ?? 0);
    $__monthCap    = (int)   ($data['duty_monthly_cap']     ?? 190);
    $__zone        = (string)($data['duty_month_threshold'] ?? 'normal');
    $__remaining   = (float) ($data['duty_remaining_month'] ?? 0);
    $__restMin     = $data['duty_rest_minutes']             ?? null;
    $__lastDate    = $data['duty_last_date']                ?? null;
    $__pct         = $__monthCap > 0 ? min(100, round(($__monthHrs / $__monthCap) * 100)) : 0;
    [$__zClr, $__zLbl] = match ($__zone) {
        'exceeded'    => ['#ef4444', 'Exceeded'],
        'approaching' => ['#f59e0b', 'Approaching'],
        default       => ['#10b981', 'Normal'],
    };
    $__isOnDuty = $dutyOpen && in_array($dutyOpen['state'], ['checked_in','on_duty','exception_pending_review'], true);
    $__restLabel = 'No record';
    $__restClr   = '#7484a8';
    if ($__isOnDuty) { $__restLabel = 'On duty';   $__restClr = '#3b82f6'; }
    elseif ($__restMin !== null) {
        if ($__restMin >= 600) { $__restLabel = 'Adequate';  $__restClr = '#10b981'; }
        elseif ($__restMin >= 480) { $__restLabel = 'Minimum';   $__restClr = '#f59e0b'; }
        else { $__restLabel = 'Below min'; $__restClr = '#ef4444'; }
    }
    $__restText = ($__restMin !== null && !$__isOnDuty)
        ? sprintf('%dh %dm', intdiv((int)$__restMin, 60), (int)$__restMin % 60)
        : '—';
?>
<div class="card" style="margin-bottom:20px; padding:18px 22px;">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:14px; margin-bottom:14px; flex-wrap:wrap;">
        <div>
            <div class="card-title" style="margin:0;">Duty Time Summary</div>
            <div class="text-xs text-muted" style="margin-top:2px;"><?= e(date('F Y')) ?></div>
        </div>
        <a href="/my-duty" class="btn btn-sm btn-outline">View full Duty Time →</a>
    </div>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; align-items:center;">
        <!-- Left: month total + threshold -->
        <div>
            <div style="display:flex; align-items:baseline; gap:10px; margin-bottom:8px;">
                <span style="font-size:32px; font-weight:700; color:var(--text-primary); font-variant-numeric:tabular-nums;"><?= e((string)$__monthHrs) ?>h</span>
                <span class="text-sm text-muted">of <?= (int)$__monthCap ?>h</span>
                <span style="display:inline-flex; align-items:center; gap:6px; padding:3px 10px; border-radius:999px; background:<?= $__zClr ?>22; color:<?= $__zClr ?>; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.04em;">
                    <span style="width:6px; height:6px; border-radius:50%; background:<?= $__zClr ?>;"></span>
                    <?= e($__zLbl) ?>
                </span>
            </div>
            <div style="height:8px; background:var(--bg-secondary, rgba(255,255,255,0.05)); border-radius:4px; overflow:hidden;">
                <div style="height:100%; width:<?= (int)$__pct ?>%; background:<?= $__zClr ?>; border-radius:4px; transition:width .25s;"></div>
            </div>
            <div class="text-xs text-muted" style="margin-top:6px;">Remaining <strong style="color:var(--text-primary);"><?= e((string)$__remaining) ?>h</strong> before cap</div>
        </div>
        <!-- Right: rest period + last duty -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
            <div style="padding:12px 14px; background:var(--bg-input, rgba(255,255,255,0.03)); border-radius:8px;">
                <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.06em;">Rest Period</div>
                <div style="font-size:18px; font-weight:700; color:var(--text-primary); margin-top:4px; font-variant-numeric:tabular-nums;"><?= e($__restText) ?></div>
                <div style="display:flex; align-items:center; gap:6px; margin-top:4px;">
                    <span style="display:inline-block; width:6px; height:6px; border-radius:50%; background:<?= $__restClr ?>;"></span>
                    <span class="text-xs text-muted"><?= e($__restLabel) ?></span>
                </div>
            </div>
            <div style="padding:12px 14px; background:var(--bg-input, rgba(255,255,255,0.03)); border-radius:8px;">
                <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.06em;">Last Duty</div>
                <div style="font-size:18px; font-weight:700; color:var(--text-primary); margin-top:4px; font-variant-numeric:tabular-nums;">
                    <?= $__lastDate ? e($__lastDate) : '—' ?>
                </div>
                <div class="text-xs text-muted" style="margin-top:4px;"><?= $__lastDate ? 'Last clock-out date' : 'No history yet' ?></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (($data['pending_notice_acks'] ?? 0) > 0): ?>
<div style="background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%); color:#fff; border-radius:10px; padding:14px 18px; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
    <div style="display:flex; align-items:center; gap:12px;">
        <span style="display:inline-flex; color:#fff;"><?= sidebarIcon('pencil', 22) ?></span>
        <div>
            <strong style="font-size:14px; display:block;">
                <?= $data['pending_notice_acks'] ?> notice<?= $data['pending_notice_acks'] !== 1 ? 's' : '' ?> require<?= $data['pending_notice_acks'] === 1 ? 's' : '' ?> your acknowledgement
            </strong>
            <span style="font-size:12px; opacity:0.9;">Your sign-off is required — tap to review and acknowledge.</span>
        </div>
    </div>
    <a href="/my-notices" style="background:#fff; color:#d97706; font-weight:700; padding:8px 16px; border-radius:8px; text-decoration:none; font-size:13px; white-space:nowrap; flex-shrink:0;">
        Review Notices →
    </a>
</div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-label">Duty Hours This Month</div>
        <div class="stat-value"><?= e((string)($data['duty_hours_month'] ?? 0)) ?></div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Days Flown This Month</div>
        <div class="stat-value"><?= (int)($data['days_flown_month'] ?? 0) ?></div>
    </div>
    <div class="stat-card <?= ($data['pending_notice_acks'] ?? 0) > 0 ? 'yellow' : 'cyan' ?>">
        <div class="stat-label">Pending Acknowledgements</div>
        <div class="stat-value"><?= (int)($data['pending_notice_acks'] ?? 0) ?></div>
    </div>
    <div class="stat-card cyan">
        <div class="stat-label">Next Duty</div>
        <?php if (!empty($data['next_duty'])): ?>
            <div class="stat-value" style="font-size:18px;"><?= e($__dutyTypeLabel($data['next_duty']['duty_type'])) ?></div>
            <div class="stat-label" style="margin-top:4px;">
                <?= e(formatDate($data['next_duty']['roster_date'])) ?>
                <?php if (!empty($data['next_duty']['duty_code'])): ?>
                    · <?= e($data['next_duty']['duty_code']) ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="stat-value" style="font-size:18px;">—</div>
            <div class="stat-label" style="margin-top:4px;">No upcoming duties</div>
        <?php endif; ?>
    </div>
</div>

<!-- Today's duty -->
<div class="card">
    <div class="card-header">
        <div class="card-title">Today's Duty</div>
        <a href="/my-roster" class="btn btn-sm btn-outline">Open Roster →</a>
    </div>
    <?php if (!empty($data['today_duty'])):
        $td = $data['today_duty'];
        $tdType = $__dutyTypeLabel($td['duty_type']);
        $tdColor = match ($td['duty_type']) {
            'flight'   => 'var(--accent-blue)',
            'standby', 'reserve' => 'var(--accent-amber, #f59e0b)',
            'leave', 'off', 'rest' => 'var(--text-muted, #94a3b8)',
            'sim', 'training' => 'var(--accent-cyan, #06b6d4)',
            default    => 'var(--accent-blue)',
        };
    ?>
        <div style="display:flex; align-items:center; gap:16px; padding:8px 0;">
            <div style="width:8px; height:48px; background:<?= $tdColor ?>; border-radius:4px;"></div>
            <div style="flex:1;">
                <div style="font-size:18px; font-weight:700; color:var(--text-primary);"><?= e($tdType) ?></div>
                <div style="font-size:13px; color:var(--text-secondary);">
                    <?= e(formatDate($td['roster_date'])) ?>
                    <?php if (!empty($td['duty_code'])): ?>
                        · <strong><?= e($td['duty_code']) ?></strong>
                    <?php endif; ?>
                    <?php if (!empty($td['notes'])): ?>
                        · <?= e($td['notes']) ?>
                    <?php endif; ?>
                </div>
            </div>
            <a href="/my-roster" class="btn btn-sm btn-primary">View Details →</a>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <p>No duty assigned for today.</p>
        </div>
    <?php endif; ?>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <!-- Recent Notices -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Company Notices</div>
            <a href="/my-notices" class="btn btn-sm btn-outline">View All →</a>
        </div>
        <?php if (empty($data['recent_notices'])): ?>
            <div class="empty-state"><p>No active operational bulletins.</p></div>
        <?php else: ?>
        <ul class="activity-list">
            <?php foreach ($data['recent_notices'] as $n): ?>
            <li class="activity-item">
                <div class="activity-dot" style="background: <?= $n['priority'] === 'critical' ? 'var(--accent-red)' : ($n['priority'] === 'urgent' ? 'var(--accent-amber, #f59e0b)' : 'var(--accent-blue)') ?>"></div>
                <div>
                    <div><strong><?= e($n['title']) ?></strong></div>
                    <div class="activity-time"><?= statusBadge($n['priority']) ?> · <?= formatDateTime($n['published_at'] ?? $n['created_at']) ?></div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>

    <!-- Documents -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Assigned Documents</div>
            <a href="/my-files" class="btn btn-sm btn-outline">Browse All →</a>
        </div>
        <?php if (empty($data['recent_files'])): ?>
            <div class="empty-state"><p>No assigned documents.</p></div>
        <?php else: ?>
        <ul class="activity-list">
            <?php foreach ($data['recent_files'] as $f): ?>
            <li class="activity-item">
                <div class="activity-dot" style="background: var(--accent-blue);"></div>
                <div>
                    <div><strong><?= e($f['title']) ?></strong></div>
                    <div class="activity-time">v<?= e($f['version']) ?> · <?= formatDateTime($f['created_at']) ?></div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/app.php';
