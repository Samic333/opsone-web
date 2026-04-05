<?php
$pageTitle = 'Airline Dashboard';
$pageSubtitle = 'Administration Overview';
ob_start();
?>

<div class="stats-grid">
    <div class="stat-card green">
        <div class="stat-label">Active Staff</div>
        <div class="stat-value"><?= $data['active_staff'] ?></div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-label">Pending Users</div>
        <div class="stat-value"><?= $data['pending_users'] ?></div>
    </div>
    <div class="stat-card red">
        <div class="stat-label">Pending Devices</div>
        <div class="stat-value"><?= $data['pending_devices'] ?></div>
    </div>
    <div class="stat-card blue">
        <div class="stat-label">Documents</div>
        <div class="stat-value"><?= $data['total_files'] ?></div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <!-- Staff by Role -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Staff by Role</div>
            <a href="/users" class="btn btn-sm btn-outline">View All →</a>
        </div>
        <?php if (empty($data['users_by_role'])): ?>
            <div class="empty-state"><p>No staff assigned yet</p></div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Role</th><th>Count</th></tr></thead>
                <tbody>
                <?php foreach ($data['users_by_role'] as $r): ?>
                <tr>
                    <td><?= e($r['name']) ?></td>
                    <td><strong><?= $r['count'] ?></strong></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Recent Logins -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Recent Logins</div>
        </div>
        <?php if (empty($data['recent_logins'])): ?>
            <div class="empty-state"><p>No recent login activity</p></div>
        <?php else: ?>
        <ul class="activity-list">
            <?php foreach ($data['recent_logins'] as $login): ?>
            <li class="activity-item">
                <div class="activity-dot" style="background: <?= $login['success'] ? 'var(--accent-green)' : 'var(--accent-red)' ?>"></div>
                <div>
                    <div><strong><?= e($login['name'] ?? $login['email']) ?></strong> — <?= $login['success'] ? 'Login' : 'Failed attempt' ?></div>
                    <div class="activity-time"><?= formatDateTime($login['created_at']) ?> · <?= e($login['source']) ?> · <?= e($login['ip_address'] ?? '') ?></div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>

    <!-- Recent Uploads -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Recent Uploads</div>
            <a href="/files" class="btn btn-sm btn-outline">Manage →</a>
        </div>
        <?php if (empty($data['recent_uploads'])): ?>
            <div class="empty-state"><p>No uploads yet</p></div>
        <?php else: ?>
        <ul class="activity-list">
            <?php foreach ($data['recent_uploads'] as $f): ?>
            <li class="activity-item">
                <div class="activity-dot" style="background: var(--accent-purple)"></div>
                <div>
                    <div><strong><?= e($f['title']) ?></strong></div>
                    <div class="text-xs text-muted">by <?= e($f['uploaded_by_name'] ?? 'Unknown') ?> · <?= statusBadge($f['status']) ?></div>
                    <div class="activity-time"><?= formatDateTime($f['created_at']) ?></div>
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

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/app.php';
