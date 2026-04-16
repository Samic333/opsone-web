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

<!-- Compliance widget -->
<?php if (!empty($data['expiring_licenses']) || !empty($data['expiring_medicals']) || !empty($data['expiring_qualifications'])): ?>
<div class="card" style="border-left: 3px solid var(--accent-amber, #f59e0b); margin-bottom: 24px;">
    <div class="card-header">
        <div class="card-title">⚠ Crew Compliance Alerts (next 90 days)</div>
        <a href="/users" class="btn btn-sm btn-outline">View Staff →</a>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
        <div>
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);letter-spacing:.06em;margin-bottom:6px;">Licences Expiring</div>
            <?php if (empty($data['expiring_licenses'])): ?>
                <p style="font-size:13px;color:var(--text-muted);">None in next 90 days</p>
            <?php else: ?>
            <ul class="activity-list">
            <?php foreach (array_slice($data['expiring_licenses'], 0, 5) as $l):
                $d = (int) ceil((strtotime($l['expiry_date']) - time()) / 86400);
            ?>
                <li class="activity-item">
                    <div class="activity-dot" style="background: <?= $d <= 30 ? 'var(--accent-red)' : 'var(--accent-amber,#f59e0b)' ?>"></div>
                    <div>
                        <div><strong><?= e($l['user_name']) ?></strong> — <?= e($l['license_type']) ?></div>
                        <div class="activity-time" style="color: <?= $d <= 30 ? 'var(--accent-red)' : 'var(--accent-amber,#f59e0b)' ?>;">Expires <?= e($l['expiry_date']) ?> (<?= $d ?>d)</div>
                    </div>
                </li>
            <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
        <div>
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);letter-spacing:.06em;margin-bottom:6px;">Medicals Expiring</div>
            <?php if (empty($data['expiring_medicals'])): ?>
                <p style="font-size:13px;color:var(--text-muted);">None in next 90 days</p>
            <?php else: ?>
            <ul class="activity-list">
            <?php foreach (array_slice($data['expiring_medicals'], 0, 5) as $m):
                $d = (int) ceil((strtotime($m['medical_expiry']) - time()) / 86400);
            ?>
                <li class="activity-item">
                    <div class="activity-dot" style="background: <?= $d <= 30 ? 'var(--accent-red)' : 'var(--accent-amber,#f59e0b)' ?>"></div>
                    <div>
                        <div><strong><?= e($m['user_name']) ?></strong> — <?= e($m['medical_class'] ?? 'Medical') ?></div>
                        <div class="activity-time" style="color: <?= $d <= 30 ? 'var(--accent-red)' : 'var(--accent-amber,#f59e0b)' ?>;">Expires <?= e($m['medical_expiry']) ?> (<?= $d ?>d)</div>
                    </div>
                </li>
            <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
        <div>
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);letter-spacing:.06em;margin-bottom:6px;">Qualifications / Endorsements</div>
            <?php if (empty($data['expiring_qualifications'])): ?>
                <p style="font-size:13px;color:var(--text-muted);">None in next 90 days</p>
            <?php else: ?>
            <ul class="activity-list">
            <?php foreach (array_slice($data['expiring_qualifications'], 0, 5) as $q):
                $d = (int) ceil((strtotime($q['expiry_date']) - time()) / 86400);
            ?>
                <li class="activity-item">
                    <div class="activity-dot" style="background: <?= $d <= 30 ? 'var(--accent-red)' : 'var(--accent-amber,#f59e0b)' ?>"></div>
                    <div>
                        <div><strong><?= e($q['user_name']) ?></strong> — <?= e($q['qual_name']) ?></div>
                        <div class="activity-time" style="color: <?= $d <= 30 ? 'var(--accent-red)' : 'var(--accent-amber,#f59e0b)' ?>;">Expires <?= e($q['expiry_date']) ?> (<?= $d ?>d)</div>
                    </div>
                </li>
            <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

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
