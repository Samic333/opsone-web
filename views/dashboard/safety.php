<?php
$pageTitle    = 'Safety Manager Dashboard';
$pageSubtitle = 'Safety Management System';
ob_start();
?>

<div class="stats-grid">
    <div class="stat-card red">
        <div class="stat-label">Critical/Urgent Notices</div>
        <div class="stat-value"><?= $data['critical_notices'] ?></div>
    </div>
    <div class="stat-card blue">
        <div class="stat-label">Published Notices</div>
        <div class="stat-value"><?= $data['total_notices'] ?></div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Active Staff</div>
        <div class="stat-value"><?= $data['active_staff'] ?></div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <div class="card">
        <div class="card-header">
            <div class="card-title">Safety Notices</div>
            <a href="/notices" class="btn btn-sm btn-primary">Manage →</a>
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
        <div class="card-header">
            <div class="card-title">Audit Trail</div>
        </div>
        <?php if (empty($data['recent_activity'])): ?>
            <div class="empty-state"><p>No recent activity</p></div>
        <?php else: ?>
        <ul class="activity-list">
            <?php foreach ($data['recent_activity'] as $log): ?>
            <li class="activity-item">
                <div class="activity-dot"></div>
                <div>
                    <div><strong><?= e($log['user_name'] ?? 'System') ?></strong> — <?= e($log['action']) ?></div>
                    <?php if ($log['details']): ?><div class="text-xs text-muted"><?= e($log['details']) ?></div><?php endif; ?>
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
        <div class="card-header"><div class="card-title">FDM Analysis</div></div>
        <div class="empty-state">
            <div class="icon">📊</div>
            <h3>FDM Module</h3>
            <p>Flight data monitoring upload and event review — coming in Phase 5.</p>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><div class="card-title">Safety Reports</div></div>
        <div class="empty-state">
            <div class="icon">⚠</div>
            <h3>SMS Reporting</h3>
            <p>Hazard report submission and tracking — coming in a future phase.</p>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/app.php';
