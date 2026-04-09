<?php
$pageTitle = 'HR Dashboard';
$pageSubtitle = 'Human Resources Overview';
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
        <div class="stat-label">Devices Awaiting Approval</div>
        <div class="stat-value"><?= $data['pending_devices'] ?></div>
    </div>
    <div class="stat-card blue">
        <div class="stat-label">Total Documents</div>
        <div class="stat-value"><?= $data['total_files'] ?></div>
    </div>
</div>

<?php $c = $data['compliance']; $hasCompliance = ($c['expiring_licenses'] + $c['expired_licenses'] + $c['expiring_medicals'] + $c['expiring_passports']) > 0; ?>
<?php if ($hasCompliance): ?>
<div class="card" style="border-left: 3px solid var(--accent-amber, #f59e0b);">
    <div class="card-header">
        <div class="card-title">⚠ Compliance Alerts (next 90 days)</div>
        <a href="/users" class="btn btn-sm btn-outline">View Users →</a>
    </div>
    <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); gap: 12px; padding: 0 0 4px;">
        <div class="stat-card <?= $c['expired_licenses']  > 0 ? 'red'    : 'blue' ?>" style="padding: 10px 14px;">
            <div class="stat-label">Expired Licences</div>
            <div class="stat-value" style="font-size: 1.6rem;"><?= $c['expired_licenses'] ?></div>
        </div>
        <div class="stat-card <?= $c['expiring_licenses'] > 0 ? 'yellow' : 'blue' ?>" style="padding: 10px 14px;">
            <div class="stat-label">Licences Expiring Soon</div>
            <div class="stat-value" style="font-size: 1.6rem;"><?= $c['expiring_licenses'] ?></div>
        </div>
        <div class="stat-card <?= $c['expiring_medicals'] > 0 ? 'yellow' : 'blue' ?>" style="padding: 10px 14px;">
            <div class="stat-label">Medicals Expiring Soon</div>
            <div class="stat-value" style="font-size: 1.6rem;"><?= $c['expiring_medicals'] ?></div>
        </div>
        <div class="stat-card <?= $c['expiring_passports']> 0 ? 'yellow' : 'blue' ?>" style="padding: 10px 14px;">
            <div class="stat-label">Passports Expiring (180d)</div>
            <div class="stat-value" style="font-size: 1.6rem;"><?= $c['expiring_passports'] ?></div>
        </div>
    </div>
    <?php if (!empty($data['expiring_licenses'])): ?>
    <div style="margin-top: 12px; font-size: 12px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: .06em;">Licences expiring in 90 days</div>
    <div class="table-wrap" style="margin-top: 6px;">
        <table>
            <thead><tr><th>Staff</th><th>Licence Type</th><th>Number</th><th>Expires</th></tr></thead>
            <tbody>
            <?php foreach (array_slice($data['expiring_licenses'], 0, 6) as $l):
                $daysLeft = (int) ceil((strtotime($l['expiry_date']) - time()) / 86400);
            ?>
            <tr>
                <td><?= e($l['user_name']) ?> <?php if ($l['employee_id']): ?><span class="text-muted text-xs">(<?= e($l['employee_id']) ?>)</span><?php endif; ?></td>
                <td><?= e($l['license_type']) ?></td>
                <td><code><?= e($l['license_number'] ?? '—') ?></code></td>
                <td style="color: <?= $daysLeft <= 30 ? 'var(--accent-red)' : 'var(--accent-amber,#f59e0b)' ?>; font-weight: 600;"><?= e($l['expiry_date']) ?> <span style="font-size:10px;">(<?= $daysLeft ?>d)</span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <div class="card">
        <div class="card-header">
            <div class="card-title">Users by Role</div>
            <a href="/users" class="btn btn-sm btn-primary">Manage Users →</a>
        </div>
        <?php if (!empty($data['users_by_role'])): ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Role</th><th>Count</th></tr></thead>
                <tbody>
                <?php foreach ($data['users_by_role'] as $r): ?>
                <tr><td><?= e($r['name']) ?></td><td><strong><?= $r['count'] ?></strong></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="empty-state"><p>No roles assigned</p></div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">Users by Status</div>
            <a href="/devices" class="btn btn-sm btn-outline">Device Approvals →</a>
        </div>
        <?php if (!empty($data['users_by_status'])): ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Status</th><th>Count</th></tr></thead>
                <tbody>
                <?php foreach ($data['users_by_status'] as $s): ?>
                <tr><td><?= statusBadge($s['status']) ?></td><td><strong><?= $s['count'] ?></strong></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="empty-state"><p>No users yet</p></div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/app.php';
