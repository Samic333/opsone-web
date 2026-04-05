<?php
$pageTitle = 'Airlines';
$pageSubtitle = 'Manage airline tenants';
$headerAction = '<a href="/tenants/create" class="btn btn-primary">+ New Airline</a>';
ob_start();
?>

<?php if (empty($tenants)): ?>
<div class="card">
    <div class="empty-state">
        <div class="icon">🏢</div>
        <h3>No Airlines Yet</h3>
        <p>Create your first airline tenant to get started.</p>
        <a href="/tenants/create" class="btn btn-primary mt-2">+ Create Airline</a>
    </div>
</div>
<?php else: ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr><th>Airline</th><th>Code</th><th>Contact</th><th>Users</th><th>Pending Devices</th><th>Status</th><th>Created</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($tenants as $t): ?>
        <tr>
            <td><strong><?= e($t['name']) ?></strong></td>
            <td><code><?= e($t['code']) ?></code></td>
            <td><?= e($t['contact_email'] ?? '—') ?></td>
            <td><?= $t['user_count'] ?? 0 ?></td>
            <td><?= $t['pending_devices'] > 0 ? '<span style="color:var(--accent-yellow);font-weight:600">'.$t['pending_devices'].'</span>' : '0' ?></td>
            <td><?= statusBadge($t['is_active'] ? 'active' : 'inactive') ?></td>
            <td class="text-sm text-muted"><?= formatDate($t['created_at']) ?></td>
            <td>
                <div class="btn-group">
                    <a href="/tenants/edit/<?= $t['id'] ?>" class="btn btn-xs btn-outline">Edit</a>
                    <form method="POST" action="/tenants/toggle/<?= $t['id'] ?>" style="display:inline">
                        <?= csrfField() ?>
                        <button type="submit" class="btn btn-xs <?= $t['is_active'] ? 'btn-warning' : 'btn-success' ?>"><?= $t['is_active'] ? 'Deactivate' : 'Activate' ?></button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/app.php'; ?>
