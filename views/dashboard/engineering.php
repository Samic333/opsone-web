<?php
$pageTitle    = 'Engineering Manager Dashboard';
$pageSubtitle = 'Engineering & Maintenance Oversight';
ob_start();
?>

<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-label">Active Engineers</div>
        <div class="stat-value"><?= $data['eng_count'] ?></div>
    </div>
    <div class="stat-card cyan">
        <div class="stat-label">Published Documents</div>
        <div class="stat-value"><?= $data['total_files'] ?></div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-label">Active Notices</div>
        <div class="stat-value"><?= count($data['recent_notices']) ?></div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <div class="card">
        <div class="card-header">
            <div class="card-title">Engineering Notices</div>
            <a href="/notices" class="btn btn-sm btn-outline">View All →</a>
        </div>
        <?php if (empty($data['recent_notices'])): ?>
            <div class="empty-state"><p>No active notices</p></div>
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

    <div class="card">
        <div class="card-header"><div class="card-title">Recent Activity</div></div>
        <?php if (empty($data['recent_activity'])): ?>
            <div class="empty-state"><p>No recent activity</p></div>
        <?php else: ?>
        <ul class="activity-list">
            <?php foreach ($data['recent_activity'] as $log): ?>
            <li class="activity-item">
                <div class="activity-dot"></div>
                <div>
                    <div><strong><?= e($log['user_name'] ?? 'System') ?></strong> — <?= e($log['action']) ?></div>
                    <div class="activity-time"><?= formatDateTime($log['created_at']) ?></div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <div class="card">
        <div class="card-header">
            <div class="card-title">Engineering Orders &amp; Manuals</div>
            <a href="/files" class="btn btn-sm btn-primary">View Documents →</a>
        </div>
        <div class="empty-state">
            <div class="icon">🔧</div>
            <p><?= $data['total_files'] ?> document<?= $data['total_files'] !== 1 ? 's' : '' ?> published.<br>Engineering orders visible via Documents.</p>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><div class="card-title">Maintenance Tracking</div></div>
        <div class="empty-state">
            <div class="icon">✈</div>
            <h3>Maintenance Module</h3>
            <p>Work order tracking and MEL management — coming in a future phase.</p>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/app.php';
