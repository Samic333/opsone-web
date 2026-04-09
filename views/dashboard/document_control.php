<?php
$pageTitle    = 'Document Control Dashboard';
$pageSubtitle = 'Document & Manual Management';
ob_start();
?>

<div class="stats-grid">
    <div class="stat-card green">
        <div class="stat-label">Published Documents</div>
        <div class="stat-value"><?= $data['total_files'] ?></div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-label">Drafts Pending Review</div>
        <div class="stat-value"><?= $data['draft_files'] ?></div>
    </div>
    <div class="stat-card blue">
        <div class="stat-label">Active Notices</div>
        <div class="stat-value"><?= $data['total_notices'] ?></div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <div class="card">
        <div class="card-header">
            <div class="card-title">Recently Uploaded Documents</div>
            <a href="/files/upload" class="btn btn-sm btn-primary">Upload →</a>
        </div>
        <?php if (empty($data['recent_uploads'])): ?>
            <div class="empty-state"><p>No documents yet</p></div>
        <?php else: ?>
        <ul class="activity-list">
            <?php foreach ($data['recent_uploads'] as $f): ?>
            <li class="activity-item">
                <div class="activity-dot" style="background: var(--accent-purple)"></div>
                <div>
                    <div><strong><?= e($f['title']) ?></strong></div>
                    <div class="text-xs text-muted">by <?= e($f['uploaded_by_name'] ?? 'Unknown') ?> · v<?= e($f['version']) ?> · <?= statusBadge($f['status']) ?></div>
                    <div class="activity-time"><?= formatDateTime($f['created_at']) ?></div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">Notices</div>
            <a href="/notices/create" class="btn btn-sm btn-primary">New Notice →</a>
        </div>
        <div class="empty-state">
            <div class="icon">📢</div>
            <p><?= $data['total_notices'] ?> active notice<?= $data['total_notices'] !== 1 ? 's' : '' ?> published.</p>
            <a href="/notices" class="btn btn-sm btn-outline" style="margin-top: 8px;">Manage Notices →</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><div class="card-title">Acknowledgement Tracking</div></div>
    <div class="empty-state">
        <div class="icon">✅</div>
        <h3>Ack Tracking</h3>
        <p>View who has read and acknowledged documents across all crew — coming in Phase 5.</p>
    </div>
</div>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/app.php';
