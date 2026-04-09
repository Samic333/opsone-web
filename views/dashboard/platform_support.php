<?php
$pageTitle    = 'Platform Overview';
$pageSubtitle = ucwords(str_replace('_', ' ', $_SESSION['user_roles'][0] ?? 'Support')) . ' View';
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

<div style="padding: 10px 0 6px; display: flex; align-items: center; gap: 8px;">
    <span style="background: var(--accent-amber, #f59e0b); color: #000; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 4px; letter-spacing: 0.05em;">READ-ONLY VIEW</span>
    <span style="font-size: 12px; color: var(--text-muted);">Your role has visibility access only — changes must be made by a Super Admin.</span>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 8px;">
    <div class="card">
        <div class="card-header">
            <div class="card-title">Registered Airlines</div>
        </div>
        <?php if (empty($data['tenants'])): ?>
            <div class="empty-state"><p>No airlines registered yet</p></div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Airline</th><th>Code</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach (array_slice($data['tenants'], 0, 8) as $t): ?>
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

    <div class="card">
        <div class="card-header">
            <div class="card-title">Recent Platform Activity</div>
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
