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
