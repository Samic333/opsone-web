<?php
$pageTitle = $pageTitle ?? 'Platform Staff';
ob_start();
?>

<div class="card">
    <?php if (empty($users)): ?>
    <p style="color:var(--text-muted); font-size:13px;">No platform staff accounts found.</p>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Emp ID</th>
                <th>Status</th>
                <th>Last Login</th>
                <th style="width:80px;"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
        <?php
            $slugs = array_filter(explode(',', $u['role_slugs'] ?? ''));
            $names = array_filter(explode(',', $u['role_names'] ?? ''));
            $roleBadge = '';
            foreach ($names as $rn) {
                $roleBadge .= '<span style="display:inline-block;padding:2px 8px;background:var(--surface);border:1px solid var(--border);border-radius:4px;font-size:11px;margin-right:3px;">' . e($rn) . '</span>';
            }
        ?>
        <tr>
            <td style="font-weight:500;"><?= e($u['name']) ?></td>
            <td style="font-size:13px; color:var(--text-muted);"><?= e($u['email']) ?></td>
            <td><?= $roleBadge ?></td>
            <td style="font-size:12px; color:var(--text-muted);"><?= e($u['employee_id'] ?? '—') ?></td>
            <td><?= statusBadge($u['status']) ?></td>
            <td style="font-size:12px; color:var(--text-muted);"><?= formatDateTime($u['last_login_at']) ?></td>
            <td>
                <form method="POST" action="/platform/users/toggle/<?= $u['id'] ?>">
                    <?= csrfField() ?>
                    <button type="submit" class="btn btn-outline"
                            style="font-size:11px; padding:3px 10px;"
                            onclick="return confirm('Toggle status for <?= e(addslashes($u['name'])) ?>?')">
                        <?= $u['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                    </button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/app.php'; ?>
