<?php
$pageTitle = 'Users';
$pageSubtitle = 'Manage staff accounts';
$headerAction = '<a href="/users/create" class="btn btn-primary">+ New User</a>';
$statusFilter = $_GET['status'] ?? null;
ob_start();
?>

<div class="filter-tabs">
    <a href="/users" class="filter-tab <?= !$statusFilter ? 'active' : '' ?>">All</a>
    <a href="/users?status=active" class="filter-tab <?= $statusFilter === 'active' ? 'active' : '' ?>">Active</a>
    <a href="/users?status=pending" class="filter-tab <?= $statusFilter === 'pending' ? 'active' : '' ?>">Pending</a>
    <a href="/users?status=suspended" class="filter-tab <?= $statusFilter === 'suspended' ? 'active' : '' ?>">Suspended</a>
    <a href="/users?status=inactive" class="filter-tab <?= $statusFilter === 'inactive' ? 'active' : '' ?>">Inactive</a>
</div>

<?php if (empty($users)): ?>
<div class="card">
    <div class="empty-state">
        <div class="icon">👥</div>
        <h3>No Users Found</h3>
        <p>Create your first staff user to get started.</p>
        <a href="/users/create" class="btn btn-primary mt-2">+ Create User</a>
    </div>
</div>
<?php else: ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr><th>Name</th><th>Email</th><th>Employee ID</th><th>Roles</th><th>Department</th><th>Base</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><strong><?= e($u['name']) ?></strong></td>
            <td class="text-sm"><?= e($u['email']) ?></td>
            <td><code><?= e($u['employee_id'] ?? '—') ?></code></td>
            <td class="text-sm"><?= e($u['role_names'] ?? '—') ?></td>
            <td class="text-sm"><?= e($u['department_name'] ?? '—') ?></td>
            <td><?= e($u['base_code'] ?? '—') ?></td>
            <td><?= statusBadge($u['status']) ?></td>
            <td>
                <div class="btn-group">
                    <a href="/users/edit/<?= $u['id'] ?>" class="btn btn-xs btn-outline">Edit</a>
                    <form method="POST" action="/users/toggle/<?= $u['id'] ?>" style="display:inline">
                        <?= csrfField() ?>
                        <button type="submit" class="btn btn-xs <?= $u['status'] === 'active' ? 'btn-warning' : 'btn-success' ?>">
                            <?= $u['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                        </button>
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
