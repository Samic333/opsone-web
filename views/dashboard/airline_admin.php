<?php
$pageTitle = 'Airline Dashboard';
$pageSubtitle = 'Administration Overview';
ob_start();
?>

<?php
// Operational stat cards — four metrics that matter on a daily ops glance.
// Colours follow the cockpit-light system from the brand-direction doc:
//   green   = nominal     · blue = info     · amber = advisory    · red = critical
$__pendingDevices = (int) ($data['pending_devices'] ?? 0);
$__deviceCardColor = $__pendingDevices > 0 ? 'red' : 'blue';
$__deviceCardTitle = $__pendingDevices > 0
    ? "$__pendingDevices iPad device" . ($__pendingDevices === 1 ? '' : 's') . " awaiting approval"
    : 'All registered iPad devices';
?>
<div class="stats-grid">
    <a href="/users" class="stat-card stat-card-link green" title="Manage all active users">
        <div class="stat-label">Active Staff</div>
        <div class="stat-value"><?= (int) ($data['active_staff'] ?? 0) ?></div>
    </a>
    <a href="/flights" class="stat-card stat-card-link blue" title="Today's flights and the current week">
        <div class="stat-label">Active Flights</div>
        <div class="stat-value"><?= (int) ($data['active_flights'] ?? 0) ?></div>
    </a>
    <a href="/safety/queue" class="stat-card stat-card-link yellow" title="Safety reports not yet closed">
        <div class="stat-label">Open Safety Reports</div>
        <div class="stat-value"><?= (int) ($data['open_safety_reports'] ?? 0) ?></div>
    </a>
    <a href="/devices" class="stat-card stat-card-link <?= $__deviceCardColor ?>" title="<?= e($__deviceCardTitle) ?>">
        <div class="stat-label">iPad Devices<?= $__pendingDevices > 0 ? ' · '.$__pendingDevices.' pending' : '' ?></div>
        <div class="stat-value"><?= (int) ($data['device_stats']['approved'] ?? 0) ?></div>
    </a>
</div>

<!-- Compliance widget -->
<?php if (!empty($data['expiring_licenses']) || !empty($data['expiring_medicals']) || !empty($data['expiring_qualifications'])): ?>
<div class="card" style="border-left: 3px solid var(--accent-amber, #f59e0b); margin-bottom: 24px;">
    <div class="card-header">
        <div class="card-title">Crew Compliance Alerts (next 90 days)</div>
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
    <!-- Staff by Role — compact: top 4 + remaining count + view all link -->
    <?php
    $__roles      = $data['users_by_role'] ?? [];
    $__rolesTotal = array_sum(array_column($__roles, 'count'));
    $__topRoles   = array_slice($__roles, 0, 4);
    $__moreRoles  = max(0, count($__roles) - count($__topRoles));
    $__moreCount  = $__rolesTotal - array_sum(array_column($__topRoles, 'count'));
    ?>
    <div class="card">
        <div class="card-header">
            <div class="card-title">Staff by Role</div>
            <a href="/roles" class="btn btn-sm btn-outline">View all roles →</a>
        </div>
        <?php if (empty($__roles)): ?>
            <div class="empty-state"><p>No staff assigned yet</p></div>
        <?php else: ?>
            <div style="display:flex;align-items:baseline;gap:8px;margin-bottom:14px;">
                <div style="font-size:28px;font-weight:800;letter-spacing:-0.02em;color:var(--text-primary);
                            font-variant-numeric:tabular-nums;">
                    <?= (int) $__rolesTotal ?>
                </div>
                <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;
                            color:var(--text-tertiary);">
                    total assignments across <?= count($__roles) ?> role<?= count($__roles) === 1 ? '' : 's' ?>
                </div>
            </div>
            <ul style="list-style:none;padding:0;margin:0;">
                <?php foreach ($__topRoles as $r): ?>
                    <?php
                    $__pct = $__rolesTotal > 0 ? round(((int) $r['count'] / $__rolesTotal) * 100) : 0;
                    ?>
                    <li style="display:flex;align-items:center;gap:12px;padding:8px 0;
                               border-bottom:1px solid var(--border-color);">
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:13px;font-weight:600;color:var(--text-primary);
                                        overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                <?= e($r['name']) ?>
                            </div>
                            <div style="height:4px;background:var(--bg-input);border-radius:2px;margin-top:4px;overflow:hidden;">
                                <div style="height:100%;width:<?= max(2,$__pct) ?>%;
                                            background:linear-gradient(90deg,var(--accent-blue),var(--accent-cyan));
                                            border-radius:2px;"></div>
                            </div>
                        </div>
                        <div style="font-size:13px;font-weight:700;color:var(--text-primary);
                                    font-variant-numeric:tabular-nums;min-width:28px;text-align:right;">
                            <?= (int) $r['count'] ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if ($__moreRoles > 0): ?>
                <div style="margin-top:10px;">
                    <a href="/roles" style="font-size:12px;color:var(--text-secondary);
                                            border-bottom:1px solid transparent;
                                            transition:border-color 0.2s;"
                       onmouseover="this.style.borderBottomColor='var(--accent-blue)';"
                       onmouseout="this.style.borderBottomColor='transparent';">
                        + <?= (int) $__moreRoles ?> more role<?= $__moreRoles === 1 ? '' : 's' ?>
                        (<?= (int) $__moreCount ?> staff)
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Recent Logins -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Recent Logins</div>
            <a href="/audit-log" class="btn btn-sm btn-outline">View All →</a>
        </div>
        <?php if (empty($data['recent_logins'])): ?>
            <div class="empty-state"><p>No recent login activity</p></div>
        <?php else: ?>
        <ul class="activity-list">
            <?php foreach (array_slice($data['recent_logins'], 0, 5) as $login): ?>
            <li class="activity-item">
                <div class="activity-dot" style="background: <?= $login['success'] ? 'var(--accent-green)' : 'var(--accent-red)' ?>"></div>
                <div>
                    <div><strong><?= e($login['name'] ?? $login['email']) ?></strong> — <?= $login['success'] ? 'Login' : 'Failed attempt' ?></div>
                    <div class="activity-time"><?= formatDateTime($login['created_at']) ?> · <?= e($login['source']) ?></div>
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
            <?php foreach (array_slice($data['recent_uploads'], 0, 5) as $f): ?>
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
            <a href="/audit-log" class="btn btn-sm btn-outline">Full Log →</a>
        </div>
        <?php if (empty($data['recent_activity'])): ?>
            <div class="empty-state"><p>No recent activity</p></div>
        <?php else: ?>
        <ul class="activity-list">
            <?php foreach (array_slice($data['recent_activity'], 0, 5) as $log): ?>
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
