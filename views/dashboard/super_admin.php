<?php
$pageTitle = 'Platform Overview';
$pageSubtitle = 'Super Admin Dashboard';
ob_start();
?>

<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-label">Total Airlines</div>
        <div class="stat-value"><?= $data['total_airlines'] ?></div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Active Airlines</div>
        <div class="stat-value"><?= $data['active_airlines'] ?></div>
    </div>
    <div class="stat-card cyan">
        <div class="stat-label">Total Users</div>
        <div class="stat-value"><?= $data['total_users'] ?></div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-label">Pending Devices</div>
        <div class="stat-value"><?= $data['pending_devices'] ?></div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <!-- Airlines -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Airlines</div>
            <a href="/tenants" class="btn btn-sm btn-outline">Manage →</a>
        </div>
        <?php if (empty($data['tenants'])): ?>
            <div class="empty-state"><p>No airlines registered yet</p></div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Airline</th><th>Code</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach (array_slice($data['tenants'], 0, 5) as $t): ?>
                <tr>
                    <td><?= e($t['name']) ?></td>
                    <td><code><?= e($t['code']) ?></code></td>
                    <td><?= statusBadge($t['is_active'] ? 'active' : 'inactive') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
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
