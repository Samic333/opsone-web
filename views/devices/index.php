<?php
$pageTitle = 'Device Approval';
$pageSubtitle = 'Manage mobile device access';
$statusFilter = $_GET['status'] ?? null;
ob_start();
?>

<div class="stats-grid" style="margin-bottom: 24px;">
    <div class="stat-card yellow"><div class="stat-label">Pending</div><div class="stat-value"><?= $statsMap['pending'] ?? 0 ?></div></div>
    <div class="stat-card green"><div class="stat-label">Approved</div><div class="stat-value"><?= $statsMap['approved'] ?? 0 ?></div></div>
    <div class="stat-card red"><div class="stat-label">Rejected</div><div class="stat-value"><?= $statsMap['rejected'] ?? 0 ?></div></div>
    <div class="stat-card purple"><div class="stat-label">Revoked</div><div class="stat-value"><?= $statsMap['revoked'] ?? 0 ?></div></div>
</div>

<div class="filter-tabs">
    <a href="/devices" class="filter-tab <?= !$statusFilter ? 'active' : '' ?>">All</a>
    <a href="/devices?status=pending" class="filter-tab <?= $statusFilter === 'pending' ? 'active' : '' ?>">Pending</a>
    <a href="/devices?status=approved" class="filter-tab <?= $statusFilter === 'approved' ? 'active' : '' ?>">Approved</a>
    <a href="/devices?status=rejected" class="filter-tab <?= $statusFilter === 'rejected' ? 'active' : '' ?>">Rejected</a>
    <a href="/devices?status=revoked" class="filter-tab <?= $statusFilter === 'revoked' ? 'active' : '' ?>">Revoked</a>
</div>

<?php if (empty($devices)): ?>
<div class="card">
    <div class="empty-state">
        <div class="icon">📱</div>
        <h3>No Devices Found</h3>
        <p>Devices will appear here when users log in from the mobile app.</p>
    </div>
</div>
<?php else: ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr><th>User</th><th>Device UUID</th><th>Platform</th><th>Model</th><th>Status</th><th>Registered</th><th>Last Sync</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($devices as $d): ?>
        <tr>
            <td>
                <strong><?= e($d['user_name']) ?></strong>
                <div class="text-xs text-muted"><?= e($d['user_email']) ?></div>
            </td>
            <td><code style="font-size:11px"><?= e(substr($d['device_uuid'], 0, 24)) ?></code></td>
            <td><?= e($d['platform'] ?? '—') ?></td>
            <td class="text-sm"><?= e($d['model'] ?? '—') ?></td>
            <td><?= statusBadge($d['approval_status']) ?></td>
            <td class="text-sm"><?= formatDateTime($d['first_login_at']) ?></td>
            <td class="text-sm"><?= formatDateTime($d['last_sync_at']) ?></td>
            <td>
                <div class="btn-group">
                    <?php if ($d['approval_status'] === 'pending'): ?>
                        <form method="POST" action="/devices/approve/<?= $d['id'] ?>" style="display:inline">
                            <?= csrfField() ?>
                            <button type="submit" class="btn btn-xs btn-success">Approve</button>
                        </form>
                        <form method="POST" action="/devices/reject/<?= $d['id'] ?>" style="display:inline">
                            <?= csrfField() ?>
                            <button type="submit" class="btn btn-xs btn-danger">Reject</button>
                        </form>
                    <?php elseif ($d['approval_status'] === 'approved'): ?>
                        <form method="POST" action="/devices/revoke/<?= $d['id'] ?>" style="display:inline">
                            <?= csrfField() ?>
                            <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Revoke device access? This will invalidate API tokens.')">Revoke</button>
                        </form>
                    <?php else: ?>
                        <span class="text-xs text-muted">No actions</span>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/app.php'; ?>
