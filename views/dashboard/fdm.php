<?php
$pageTitle    = 'FDM Analyst Dashboard';
$pageSubtitle = 'Flight Data Monitoring';
ob_start();
?>

<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-label">Active Staff</div>
        <div class="stat-value"><?= $data['active_staff'] ?></div>
    </div>
    <div class="stat-card cyan">
        <div class="stat-label">Published Documents</div>
        <div class="stat-value"><?= $data['total_files'] ?></div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <div class="card">
        <div class="card-header"><div class="card-title">FDM Data Upload</div></div>
        <div class="empty-state">
            <div class="icon">📊</div>
            <h3>Flight Data Upload</h3>
            <p>Upload and process FDM/FOQA data files from the flight recorder. This module is under development.</p>
            <span style="display:inline-block;margin-top:8px;padding:3px 10px;background:var(--accent-amber,#f59e0b);color:#000;font-size:11px;font-weight:700;border-radius:4px;">COMING IN PHASE 5</span>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><div class="card-title">FDM Event Review</div></div>
        <div class="empty-state">
            <div class="icon">✈</div>
            <h3>Event Analysis</h3>
            <p>Review exceedances, hard landings, unstabilised approach events, and crew feedback.</p>
            <span style="display:inline-block;margin-top:8px;padding:3px 10px;background:var(--accent-amber,#f59e0b);color:#000;font-size:11px;font-weight:700;border-radius:4px;">COMING IN PHASE 5</span>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><div class="card-title">Platform Audit Trail</div></div>
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

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/app.php';
