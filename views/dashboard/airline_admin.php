<?php
/**
 * Airline Super Admin Dashboard.
 *
 * Layout (Phase K redesign):
 *   1. KPI hero strip — 4 stat cards (Active Staff / Active Flights / Open Safety / iPad Devices)
 *   2. Quick actions row — 4 chips (Add Staff / Roster / Devices / Safety report)
 *   3. Crew compliance alerts (next 90 days) — 3-column tile, full width
 *   4. Main grid (2 cols) — Staff by Role + Recent Activity
 *   5. Recent Uploads strip — compact full-width list at the bottom
 *
 * Data shape comes from DashboardController::airlineAdminDashboard().
 * No data keys are added, removed, or renamed — pure visual redesign.
 */
$pageTitle    = 'Airline Dashboard';
$pageSubtitle = 'Operations Overview';
ob_start();

// Pending-device emphasis: red border if anyone is waiting for approval.
$__pendingDevices = (int) ($data['pending_devices'] ?? 0);
$__deviceCardColor = $__pendingDevices > 0 ? 'red' : 'blue';
$__deviceCardTitle = $__pendingDevices > 0
    ? "$__pendingDevices iPad device" . ($__pendingDevices === 1 ? '' : 's') . " awaiting approval"
    : 'All registered iPad devices';
?>

<!-- ─── 1. KPI Hero Strip ────────────────────────────────────────────── -->
<div class="stats-grid" style="margin-bottom:1.25rem;">
    <a href="/users" class="stat-card stat-card-link green" title="Manage all active users"
       style="text-decoration:none; color:inherit;">
        <div class="stat-label">Active Staff</div>
        <div class="stat-value"><?= (int) ($data['active_staff'] ?? 0) ?></div>
        <?php if (!empty($data['pending_users'])): ?>
            <div style="font-size:11px; color:var(--text-tertiary); margin-top:6px;">
                + <span style="color:var(--status-advisory); font-weight:600;"><?= (int) $data['pending_users'] ?></span> pending
            </div>
        <?php endif; ?>
    </a>

    <a href="/flights" class="stat-card stat-card-link blue" title="Today's flights and the current week"
       style="text-decoration:none; color:inherit;">
        <div class="stat-label">Active Flights</div>
        <div class="stat-value"><?= (int) ($data['active_flights'] ?? 0) ?></div>
        <div style="font-size:11px; color:var(--text-tertiary); margin-top:6px;">
            in progress · scheduled
        </div>
    </a>

    <a href="/safety/queue" class="stat-card stat-card-link <?= ($data['open_safety_reports'] ?? 0) > 0 ? 'yellow' : 'green' ?>"
       title="Safety reports not yet closed"
       style="text-decoration:none; color:inherit;">
        <div class="stat-label">Open Safety Reports</div>
        <div class="stat-value"><?= (int) ($data['open_safety_reports'] ?? 0) ?></div>
        <div style="font-size:11px; color:var(--text-tertiary); margin-top:6px;">
            <?= ($data['open_safety_reports'] ?? 0) > 0 ? 'awaiting triage' : 'all closed' ?>
        </div>
    </a>

    <a href="/devices" class="stat-card stat-card-link <?= $__deviceCardColor ?>"
       title="<?= e($__deviceCardTitle) ?>"
       style="text-decoration:none; color:inherit;">
        <div class="stat-label">iPad Devices</div>
        <div class="stat-value"><?= (int) ($data['device_stats']['approved'] ?? 0) ?></div>
        <?php if ($__pendingDevices > 0): ?>
            <div style="font-size:11px; color:var(--text-tertiary); margin-top:6px;">
                <span style="color:var(--status-critical); font-weight:600;"><?= $__pendingDevices ?></span> awaiting approval
            </div>
        <?php else: ?>
            <div style="font-size:11px; color:var(--text-tertiary); margin-top:6px;">
                approved & active
            </div>
        <?php endif; ?>
    </a>
</div>

<!-- ─── 2. Quick Actions ─────────────────────────────────────────────── -->
<?php
$quickActions = [
    [
        'href'  => '/users/create',
        'icon'  => 'user',
        'title' => 'Add Staff',
        'sub'   => 'Invite a new airline user',
        'tone'  => 'var(--accent-blue)',
    ],
    [
        'href'  => '/roster',
        'icon'  => 'calendar',
        'title' => 'Roster Workbench',
        'sub'   => 'Build & publish rosters',
        'tone'  => 'var(--accent-cyan)',
    ],
    [
        'href'  => '/devices' . ($__pendingDevices > 0 ? '?status=pending' : ''),
        'icon'  => 'device-tablet',
        'title' => $__pendingDevices > 0 ? 'Approve Devices' : 'iPad Devices',
        'sub'   => $__pendingDevices > 0
                    ? $__pendingDevices . ' awaiting your approval'
                    : 'Manage device fleet',
        'tone'  => $__pendingDevices > 0 ? 'var(--status-critical)' : 'var(--accent-purple)',
    ],
    [
        'href'  => '/safety/queue',
        'icon'  => 'shield-exclamation',
        'title' => 'Safety Queue',
        'sub'   => 'Triage incoming reports',
        'tone'  => 'var(--accent-yellow)',
    ],
];
?>
<div class="dash-quick-grid" style="display:grid; grid-template-columns:repeat(4,1fr); gap:0.85rem; margin-bottom:1.5rem;">
    <?php foreach ($quickActions as $qa): ?>
    <a href="<?= e($qa['href']) ?>"
       style="display:flex; align-items:center; gap:12px;
              padding:14px 16px;
              background:var(--bg-card);
              border:1px solid var(--border-color);
              border-left:3px solid <?= $qa['tone'] ?>;
              border-radius:var(--radius-md);
              text-decoration:none; color:inherit;
              transition:background 0.15s, transform 0.15s;"
       onmouseover="this.style.background='var(--bg-card-hover)';this.style.transform='translateY(-1px)';"
       onmouseout="this.style.background='var(--bg-card)';this.style.transform='translateY(0)';">
        <span style="display:inline-flex;align-items:center;justify-content:center;
                     width:36px;height:36px;border-radius:8px;
                     background:rgba(255,255,255,0.04);color:<?= $qa['tone'] ?>;">
            <?= sidebarIcon($qa['icon'], 18) ?>
        </span>
        <span style="display:flex; flex-direction:column; min-width:0;">
            <span style="font-size:13px; font-weight:600; color:var(--text-primary); line-height:1.2;">
                <?= e($qa['title']) ?>
            </span>
            <span style="font-size:11px; color:var(--text-tertiary); line-height:1.3; margin-top:2px;
                         overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                <?= e($qa['sub']) ?>
            </span>
        </span>
    </a>
    <?php endforeach; ?>
</div>

<!-- ─── 3. Crew Compliance Alerts ────────────────────────────────────── -->
<?php
$__hasCompliance = !empty($data['expiring_licenses']) || !empty($data['expiring_medicals']) || !empty($data['expiring_qualifications']);
?>
<?php if ($__hasCompliance): ?>
<div class="card" style="border-left:3px solid var(--status-advisory); margin-bottom:1.25rem;">
    <div class="card-header">
        <div class="card-title" style="display:flex;align-items:center;gap:8px;">
            <span style="display:inline-flex;color:var(--status-advisory);"><?= sidebarIcon('shield-check', 16) ?></span>
            Crew Compliance Alerts <span style="font-size:11px;font-weight:500;color:var(--text-tertiary);">(next 90 days)</span>
        </div>
        <a href="/users" class="btn btn-sm btn-outline">View Staff →</a>
    </div>

    <div class="dash-compliance-grid"
         style="display:grid; grid-template-columns:repeat(3,1fr); gap:1.25rem; margin-top:0.5rem;">

        <!-- Licenses -->
        <div>
            <div style="display:flex; align-items:center; gap:6px; margin-bottom:8px;">
                <span style="display:inline-flex;color:var(--accent-blue);"><?= sidebarIcon('identification', 14) ?></span>
                <span style="font-size:11px; font-weight:700; text-transform:uppercase;
                             letter-spacing:.06em; color:var(--text-tertiary);">Licences Expiring</span>
            </div>
            <?php if (empty($data['expiring_licenses'])): ?>
                <p style="font-size:13px;color:var(--text-tertiary);margin:0;">None in next 90 days</p>
            <?php else: ?>
                <ul class="activity-list" style="margin:0; padding:0; list-style:none;">
                <?php foreach (array_slice($data['expiring_licenses'], 0, 5) as $l):
                    $d = (int) ceil((strtotime($l['expiry_date']) - time()) / 86400);
                    $color = $d <= 30 ? 'var(--status-critical)' : 'var(--status-advisory)';
                ?>
                    <li class="activity-item" style="padding:6px 0; border:0;">
                        <div class="activity-dot" style="background:<?= $color ?>;"></div>
                        <div style="min-width:0;">
                            <div style="font-size:12px; color:var(--text-primary);">
                                <strong><?= e($l['user_name']) ?></strong>
                                <span style="color:var(--text-tertiary);">— <?= e($l['license_type']) ?></span>
                            </div>
                            <div style="font-size:11px; color:<?= $color ?>; margin-top:2px;">
                                Expires <?= e($l['expiry_date']) ?> · <?= $d ?>d
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- Medicals -->
        <div>
            <div style="display:flex; align-items:center; gap:6px; margin-bottom:8px;">
                <span style="display:inline-flex;color:var(--accent-cyan);"><?= sidebarIcon('shield-check', 14) ?></span>
                <span style="font-size:11px; font-weight:700; text-transform:uppercase;
                             letter-spacing:.06em; color:var(--text-tertiary);">Medicals Expiring</span>
            </div>
            <?php if (empty($data['expiring_medicals'])): ?>
                <p style="font-size:13px;color:var(--text-tertiary);margin:0;">None in next 90 days</p>
            <?php else: ?>
                <ul class="activity-list" style="margin:0; padding:0; list-style:none;">
                <?php foreach (array_slice($data['expiring_medicals'], 0, 5) as $m):
                    $d = (int) ceil((strtotime($m['medical_expiry']) - time()) / 86400);
                    $color = $d <= 30 ? 'var(--status-critical)' : 'var(--status-advisory)';
                ?>
                    <li class="activity-item" style="padding:6px 0; border:0;">
                        <div class="activity-dot" style="background:<?= $color ?>;"></div>
                        <div style="min-width:0;">
                            <div style="font-size:12px; color:var(--text-primary);">
                                <strong><?= e($m['user_name']) ?></strong>
                                <span style="color:var(--text-tertiary);">— <?= e($m['medical_class'] ?? 'Medical') ?></span>
                            </div>
                            <div style="font-size:11px; color:<?= $color ?>; margin-top:2px;">
                                Expires <?= e($m['medical_expiry']) ?> · <?= $d ?>d
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- Qualifications / Endorsements -->
        <div>
            <div style="display:flex; align-items:center; gap:6px; margin-bottom:8px;">
                <span style="display:inline-flex;color:var(--accent-purple);"><?= sidebarIcon('check-badge', 14) ?></span>
                <span style="font-size:11px; font-weight:700; text-transform:uppercase;
                             letter-spacing:.06em; color:var(--text-tertiary);">Qualifications</span>
            </div>
            <?php if (empty($data['expiring_qualifications'])): ?>
                <p style="font-size:13px;color:var(--text-tertiary);margin:0;">None in next 90 days</p>
            <?php else: ?>
                <ul class="activity-list" style="margin:0; padding:0; list-style:none;">
                <?php foreach (array_slice($data['expiring_qualifications'], 0, 5) as $q):
                    $d = (int) ceil((strtotime($q['expiry_date']) - time()) / 86400);
                    $color = $d <= 30 ? 'var(--status-critical)' : 'var(--status-advisory)';
                ?>
                    <li class="activity-item" style="padding:6px 0; border:0;">
                        <div class="activity-dot" style="background:<?= $color ?>;"></div>
                        <div style="min-width:0;">
                            <div style="font-size:12px; color:var(--text-primary);">
                                <strong><?= e($q['user_name']) ?></strong>
                                <span style="color:var(--text-tertiary);">— <?= e($q['qual_name']) ?></span>
                            </div>
                            <div style="font-size:11px; color:<?= $color ?>; margin-top:2px;">
                                Expires <?= e($q['expiry_date']) ?> · <?= $d ?>d
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ─── 4. Main grid: Staff by Role (left) + Recent Activity (right) ─── -->
<div class="dash-main-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem;">

    <!-- Staff by Role -->
    <?php
    $__roles      = $data['users_by_role'] ?? [];
    $__rolesTotal = array_sum(array_column($__roles, 'count'));
    $__topRoles   = array_slice($__roles, 0, 4);
    $__moreRoles  = max(0, count($__roles) - count($__topRoles));
    $__moreCount  = $__rolesTotal - array_sum(array_column($__topRoles, 'count'));
    ?>
    <div class="card">
        <div class="card-header">
            <div class="card-title" style="display:flex; align-items:center; gap:8px;">
                <span style="display:inline-flex;color:var(--accent-cyan);"><?= sidebarIcon('users', 16) ?></span>
                Staff by Role
            </div>
            <a href="/roles" class="btn btn-sm btn-outline">View all roles →</a>
        </div>

        <?php if (empty($__roles)): ?>
            <div class="empty-state"><p>No staff assigned yet</p></div>
        <?php else: ?>
            <div style="display:flex; align-items:baseline; gap:8px; margin-bottom:14px;">
                <div style="font-size:28px; font-weight:800; letter-spacing:-0.02em;
                            color:var(--text-primary); font-variant-numeric:tabular-nums;">
                    <?= (int) $__rolesTotal ?>
                </div>
                <div style="font-size:11px; font-weight:600; text-transform:uppercase;
                            letter-spacing:0.06em; color:var(--text-tertiary);">
                    total assignments across <?= count($__roles) ?> role<?= count($__roles) === 1 ? '' : 's' ?>
                </div>
            </div>

            <ul style="list-style:none; padding:0; margin:0;">
                <?php foreach ($__topRoles as $r): ?>
                    <?php $__pct = $__rolesTotal > 0 ? round(((int) $r['count'] / $__rolesTotal) * 100) : 0; ?>
                    <li style="display:flex; align-items:center; gap:12px; padding:8px 0;
                               border-bottom:1px solid var(--border-light);">
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:13px; font-weight:600; color:var(--text-primary);
                                        overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                <?= e($r['name']) ?>
                            </div>
                            <div style="height:4px; background:var(--bg-input); border-radius:2px;
                                        margin-top:6px; overflow:hidden;">
                                <div style="height:100%; width:<?= max(2, $__pct) ?>%;
                                            background:linear-gradient(90deg, var(--accent-blue), var(--accent-cyan));
                                            border-radius:2px;"></div>
                            </div>
                        </div>
                        <div style="font-size:13px; font-weight:700; color:var(--text-primary);
                                    font-variant-numeric:tabular-nums; min-width:28px; text-align:right;">
                            <?= (int) $r['count'] ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if ($__moreRoles > 0): ?>
                <div style="margin-top:10px;">
                    <a href="/roles"
                       style="font-size:12px; color:var(--accent-blue); text-decoration:none;">
                        + <?= (int) $__moreRoles ?> more role<?= $__moreRoles === 1 ? '' : 's' ?>
                        (<?= (int) $__moreCount ?> staff) →
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Recent Activity (combined: logins + audit) -->
    <div class="card">
        <div class="card-header">
            <div class="card-title" style="display:flex; align-items:center; gap:8px;">
                <span style="display:inline-flex;color:var(--accent-green);"><?= sidebarIcon('chart-bar', 16) ?></span>
                Recent Activity
            </div>
            <a href="/audit-log" class="btn btn-sm btn-outline">Full log →</a>
        </div>

        <?php if (empty($data['recent_activity'])): ?>
            <div class="empty-state"><p>No recent activity</p></div>
        <?php else: ?>
            <ul class="activity-list" style="margin:0; padding:0; list-style:none;">
                <?php foreach (array_slice($data['recent_activity'], 0, 8) as $log): ?>
                    <li class="activity-item"
                        style="display:flex; gap:12px; padding:10px 0;
                               border-bottom:1px solid var(--border-light);">
                        <span class="activity-dot"
                              style="width:8px; height:8px; border-radius:50%;
                                     background:var(--accent-purple); margin-top:6px; flex-shrink:0;"></span>
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:13px; color:var(--text-primary);">
                                <strong><?= e($log['user_name'] ?? 'System') ?></strong>
                                <span style="margin-left:6px; font-size:11px; font-family:ui-monospace,monospace;
                                             color:var(--accent-blue); background:rgba(59,130,246,0.08);
                                             padding:1px 7px; border-radius:4px;">
                                    <?= e($log['action']) ?>
                                </span>
                            </div>
                            <?php if (!empty($log['details'])): ?>
                                <div style="font-size:11px; color:var(--text-tertiary); margin-top:3px;
                                            white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?= e(is_array($log['details']) ? json_encode($log['details']) : $log['details']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:11px; color:var(--text-tertiary); white-space:nowrap; align-self:center;">
                            <?= formatDateTime($log['created_at']) ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<!-- ─── 5. Recent Uploads + Recent Logins (compact bottom row) ──────── -->
<div class="dash-bottom-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem; margin-top:1.25rem;">

    <!-- Recent Logins -->
    <div class="card">
        <div class="card-header">
            <div class="card-title" style="display:flex; align-items:center; gap:8px;">
                <span style="display:inline-flex;color:var(--accent-blue);"><?= sidebarIcon('key', 16) ?></span>
                Recent Logins
            </div>
            <a href="/audit-log/logins" class="btn btn-sm btn-outline">View all →</a>
        </div>
        <?php if (empty($data['recent_logins'])): ?>
            <div class="empty-state"><p>No recent login activity</p></div>
        <?php else: ?>
            <ul class="activity-list" style="margin:0; padding:0; list-style:none;">
                <?php foreach (array_slice($data['recent_logins'], 0, 5) as $login): ?>
                    <li class="activity-item"
                        style="display:flex; gap:12px; padding:8px 0;
                               border-bottom:1px solid var(--border-light);">
                        <span class="activity-dot"
                              style="width:8px; height:8px; border-radius:50%;
                                     background:<?= !empty($login['success']) ? 'var(--status-cleared)' : 'var(--status-critical)' ?>;
                                     margin-top:6px; flex-shrink:0;"></span>
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:13px; color:var(--text-primary);
                                        overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                <strong><?= e($login['name'] ?? $login['email']) ?></strong>
                                <span style="font-size:11px; color:var(--text-tertiary);">
                                    <?= !empty($login['success']) ? '· Login' : '· Failed' ?>
                                </span>
                            </div>
                            <div style="font-size:11px; color:var(--text-tertiary); margin-top:2px;">
                                <?= formatDateTime($login['created_at']) ?>
                                <?php if (!empty($login['source'])): ?>
                                    · <?= e($login['source']) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Recent Uploads -->
    <div class="card">
        <div class="card-header">
            <div class="card-title" style="display:flex; align-items:center; gap:8px;">
                <span style="display:inline-flex;color:var(--accent-purple);"><?= sidebarIcon('folder-open', 16) ?></span>
                Recent Uploads
            </div>
            <a href="/files" class="btn btn-sm btn-outline">Manage →</a>
        </div>
        <?php if (empty($data['recent_uploads'])): ?>
            <div class="empty-state"><p>No uploads yet</p></div>
        <?php else: ?>
            <ul class="activity-list" style="margin:0; padding:0; list-style:none;">
                <?php foreach (array_slice($data['recent_uploads'], 0, 5) as $f): ?>
                    <li class="activity-item"
                        style="display:flex; gap:12px; padding:8px 0;
                               border-bottom:1px solid var(--border-light);">
                        <span class="activity-dot"
                              style="width:8px; height:8px; border-radius:50%;
                                     background:var(--accent-purple); margin-top:6px; flex-shrink:0;"></span>
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:13px; color:var(--text-primary);
                                        overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                <strong><?= e($f['title']) ?></strong>
                            </div>
                            <div style="font-size:11px; color:var(--text-tertiary); margin-top:2px;">
                                by <?= e($f['uploaded_by_name'] ?? 'Unknown') ?>
                                · <?= statusBadge($f['status']) ?>
                                · <?= formatDateTime($f['created_at']) ?>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<!-- Responsive collapse for narrower windows. -->
<style>
@media (max-width: 1100px) {
    .dash-quick-grid       { grid-template-columns: repeat(2, 1fr) !important; }
    .dash-compliance-grid  { grid-template-columns: 1fr !important; }
    .dash-main-grid        { grid-template-columns: 1fr !important; }
    .dash-bottom-grid      { grid-template-columns: 1fr !important; }
}
</style>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/app.php';
