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
