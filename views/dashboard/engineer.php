<?php
$pageTitle    = 'Engineer Dashboard';
$pageSubtitle = 'Engineering Operations';
ob_start();
?>

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
            <div class="card-title">Documents</div>
            <a href="/files" class="btn btn-sm btn-outline">Browse →</a>
        </div>
        <div class="empty-state" style="padding: 16px 0;">
            <div class="icon">📄</div>
            <p><?= $data['total_files'] ?> published manual<?= $data['total_files'] !== 1 ? 's' : '' ?> available.</p>
            <a href="/files" class="btn btn-sm btn-primary" style="margin-top:8px;">Open Documents →</a>
        </div>
    </div>
</div>

<!-- iPad sync banner -->
<div class="card" style="background: linear-gradient(135deg, var(--accent-blue) 0%, #6366f1 100%); color: #fff; border: none;">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
        <div>
            <div style="font-weight:700; font-size:15px; margin-bottom:4px;">📲 Sync your iPad</div>
            <p style="color: rgba(255,255,255,0.85); font-size:13px; margin:0;">
                Keep your CrewAssist app up to date for the latest engineering orders and manuals.
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
