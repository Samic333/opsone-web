<?php
$pageTitle    = 'Pilot Dashboard';
$pageSubtitle = 'Crew Operations Overview';
ob_start();
?>

<?php if (($data['pending_notice_acks'] ?? 0) > 0): ?>
<div style="background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%); color:#fff; border-radius:10px; padding:14px 18px; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
    <div style="display:flex; align-items:center; gap:12px;">
        <span style="font-size:22px;">✍️</span>
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
        <div class="stat-label">Welcome</div>
        <div class="stat-value" style="font-size:20px;"><?= e($_SESSION['user']['name'] ?? 'Pilot') ?></div>
    </div>
    <div class="stat-card <?= $data['sync_status'] ? 'green' : 'yellow' ?>">
        <div class="stat-label">Last iPad Sync</div>
        <div class="stat-value" style="font-size:16px;"><?= $data['sync_status'] ? date('d M H:i', strtotime($data['sync_status'])) : 'Never' ?></div>
    </div>
    <div class="stat-card cyan">
        <div class="stat-label">Active Notices</div>
        <div class="stat-value"><?= count($data['recent_notices']) ?></div>
    </div>
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

<!-- iPad sync banner -->
<div class="card" style="background: linear-gradient(135deg, var(--accent-blue) 0%, #6366f1 100%); color: #fff; border: none;">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
        <div>
            <div style="font-weight:700; font-size:15px; margin-bottom:4px;">📲 Sync your iPad</div>
            <p style="color: rgba(255,255,255,0.85); font-size:13px; margin:0;">
                Keep your CrewAssist app up to date for the latest rosters, manuals, and notices.
                <?php if ($data['sync_status']): ?>
                Last sync: <?= date('d M Y H:i', strtotime($data['sync_status'])) ?>.
                <?php else: ?>
                No sync recorded yet.
                <?php endif; ?>
            </p>
        </div>
        <a href="/install" class="btn" style="background:#fff; color: var(--accent-blue); font-weight:700; white-space:nowrap;">Get iPad Build →</a>
    </div>
</div>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/app.php';
