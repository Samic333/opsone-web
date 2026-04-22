<?php
$pageTitle    = 'Engineer Dashboard';
$pageSubtitle = 'Engineering Operations';
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
} catch (\Throwable $e) {}
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
            <span style="font-size:22px;"><?= $pending ? '⏳' : '🔧' ?></span>
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
            <span style="font-size:22px;">🟢</span>
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

<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-label">Welcome</div>
        <div class="stat-value" style="font-size:20px;"><?= e($_SESSION['user']['name'] ?? 'Engineer') ?></div>
    </div>
    <div class="stat-card <?= $data['sync_status'] ? 'green' : 'yellow' ?>">
        <div class="stat-label">Last iPad Sync</div>
        <div class="stat-value" style="font-size:16px;"><?= $data['sync_status'] ? date('d M H:i', strtotime($data['sync_status'])) : 'Never' ?></div>
    </div>
    <div class="stat-card cyan">
        <div class="stat-label">Published Manuals</div>
        <div class="stat-value"><?= $data['total_files'] ?></div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <!-- Notices -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Engineering Notices</div>
            <a href="/notices" class="btn btn-sm btn-outline">View All →</a>
        </div>
        <?php if (empty($data['recent_notices'])): ?>
            <div class="empty-state"><p>No active notices.</p></div>
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

<!-- iPad sync status -->
<div class="card" style="background: linear-gradient(135deg, var(--accent-blue) 0%, #6366f1 100%); color: #fff; border: none;">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
        <div>
            <div style="font-weight:700; font-size:15px; margin-bottom:4px;">📲 iPad sync</div>
            <p style="color: rgba(255,255,255,0.85); font-size:13px; margin:0;">
                Keep your CrewAssist app up to date for the latest engineering orders and manuals.
                <?php if ($data['sync_status']): ?>
                Last sync: <?= date('d M Y H:i', strtotime($data['sync_status'])) ?>.
                <?php else: ?>
                No sync recorded yet. Your admin will email the iPad setup instructions.
                <?php endif; ?>
            </p>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/app.php';
