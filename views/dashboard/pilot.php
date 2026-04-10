<?php
$pageTitle    = 'Pilot Dashboard';
$pageSubtitle = 'Crew Operations Overview';
ob_start();
?>

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
            <a href="/notices" class="btn btn-sm btn-outline">View All →</a>
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

    <!-- iPad Sync Card -->
    <div class="card" style="background: linear-gradient(135deg, var(--accent-blue) 0%, #6366f1 100%); color: #fff; border: none;">
        <div class="card-header" style="border-bottom: 1px solid rgba(255,255,255,0.15);">
            <div class="card-title" style="color:#fff;">📲 Sync Your iPad</div>
        </div>
        <div style="padding: 16px 0 8px;">
            <p style="color: rgba(255,255,255,0.85); margin-bottom: 16px; font-size:13px;">
                Keep your CrewAssist app up to date for the latest documents, rosters, and notices.
            </p>
            <a href="/install" class="btn" style="background:#fff; color: var(--accent-blue); font-weight:700;">Get iPad Build →</a>
        </div>
        <?php if ($data['sync_status']): ?>
        <p style="margin-top:12px; font-size:11px; color:rgba(255,255,255,0.6);">
            Last sync: <?= date('d M Y H:i', strtotime($data['sync_status'])) ?>
        </p>
        <?php else: ?>
        <p style="margin-top:12px; font-size:11px; color:rgba(255,255,255,0.6);">No sync recorded yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/app.php';
