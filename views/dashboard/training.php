<?php
$pageTitle    = 'Training Admin Dashboard';
$pageSubtitle = 'Training Programme Management';
ob_start();
?>

<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-label">Active Staff</div>
        <div class="stat-value"><?= $data['active_staff'] ?></div>
    </div>
    <div class="stat-card cyan">
        <div class="stat-label">Active Notices</div>
        <div class="stat-value"><?= count($data['recent_notices']) ?></div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <div class="card">
        <div class="card-header"><div class="card-title">Training Records</div></div>
        <div class="empty-state">
            <div class="icon">🎓</div>
            <p>Crew training records are managed via the staff profiles in <a href="/users">Users</a>.</p>
            <a href="/compliance" class="btn btn-sm btn-outline" style="margin-top:8px;">View Compliance Report →</a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">Recent Notices</div>
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
                    <div class="activity-time"><?= formatDateTime($n['published_at'] ?? $n['created_at']) ?></div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <div class="card">
        <div class="card-header"><div class="card-title">Licence Expiry Tracking</div></div>
        <div class="empty-state">
            <div class="icon">📋</div>
            <p>Licence, medical, and rating expiry alerts are visible in the Compliance report.</p>
            <a href="/compliance" class="btn btn-sm btn-outline" style="margin-top:8px;">Compliance Report →</a>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <div class="card-title">Training Documents</div>
            <a href="/files" class="btn btn-sm btn-outline">View →</a>
        </div>
        <div class="empty-state">
            <div class="icon">📄</div>
            <p>Training manuals and syllabuses available in Documents.</p>
            <a href="/files" class="btn btn-sm btn-primary" style="margin-top: 8px;">Open Documents →</a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/app.php';
