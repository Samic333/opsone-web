<?php
$pageTitle    = 'Chief Pilot Dashboard';
$pageSubtitle = 'Flight Operations Oversight';
ob_start();
?>

<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-label">Active Pilots</div>
        <div class="stat-value"><?= $data['pilot_count'] ?></div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Total Staff (Airline)</div>
        <div class="stat-value"><?= $data['active_staff'] ?></div>
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
    <!-- Active Notices -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Flight Operations Notices</div>
            <a href="/notices" class="btn btn-sm btn-outline">Manage →</a>
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

    <!-- Recent Activity -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Recent Activity</div>
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

<!-- Module placeholders -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 0;">
    <div class="card">
        <div class="card-header"><div class="card-title">Crew Roster</div></div>
        <div class="empty-state">
            <div class="icon">📅</div>
            <h3>Roster Module</h3>
            <p>Monthly crew roster integration — coming in Phase 4.</p>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <div class="card-title">Documents &amp; Manuals</div>
            <a href="/files" class="btn btn-sm btn-outline">View All →</a>
        </div>
        <div class="empty-state">
            <div class="icon">📄</div>
            <p><?= $data['total_files'] ?> published document<?= $data['total_files'] !== 1 ? 's' : '' ?> available to crew.</p>
            <a href="/files" class="btn btn-sm btn-primary" style="margin-top: 8px;">Open Documents →</a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/app.php';
