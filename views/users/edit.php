<?php $pageTitle = 'Edit User'; ob_start(); ?>
<div class="card" style="max-width: 700px;">
    <form method="POST" action="/users/update/<?= $user['id'] ?>">
        <?= csrfField() ?>
        <div class="form-row">
            <div class="form-group">
                <label for="name">Full Name *</label>
                <input type="text" id="name" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="password">New Password <small class="text-muted">(leave blank to keep current)</small></label>
                <input type="password" id="password" name="password" class="form-control" minlength="6">
            </div>
            <div class="form-group">
                <label for="employee_id">Employee ID</label>
                <input type="text" id="employee_id" name="employee_id" class="form-control" value="<?= e($user['employee_id'] ?? '') ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="department_id">Department</label>
                <select id="department_id" name="department_id" class="form-control">
                    <option value="">— Select —</option>
                    <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= ($user['department_id'] ?? '') == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="base_id">Base</label>
                <select id="base_id" name="base_id" class="form-control">
                    <option value="">— Select —</option>
                    <?php foreach ($bases as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= ($user['base_id'] ?? '') == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?> (<?= e($b['code']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" class="form-control">
                    <?php foreach (['pending','active','suspended','inactive'] as $s): ?>
                    <option value="<?= $s ?>" <?= $user['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="display:flex;flex-direction:column;justify-content:flex-end;padding-bottom:22px;gap:8px;">
                <label class="form-check">
                    <input type="checkbox" name="web_access" <?= !empty($user['web_access']) ? 'checked' : '' ?>> Allow web portal access
                </label>
                <label class="form-check">
                    <input type="checkbox" name="mobile_access" <?= !empty($user['mobile_access']) ? 'checked' : '' ?>> Allow mobile app access
                </label>
            </div>
        </div>
        <div class="form-group">
            <label>Assign Roles</label>
            <div class="checkbox-grid">
                <?php foreach ($roles as $r): ?>
                <label class="form-check">
                    <input type="checkbox" name="roles[]" value="<?= $r['id'] ?>" <?= in_array($r['id'], $userRoleIds) ? 'checked' : '' ?>> <?= e($r['name']) ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="flex gap-1 mt-2">
            <button type="submit" class="btn btn-primary">Update User</button>
            <a href="/users" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<?php if (!empty($devices)): ?>
<div class="card mt-3" style="max-width: 700px;">
    <div class="card-header"><div class="card-title">📱 Registered Devices</div></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Device</th><th>Platform</th><th>Status</th><th>First Login</th><th>Last Sync</th></tr></thead>
            <tbody>
            <?php foreach ($devices as $dev): ?>
            <tr>
                <td><code><?= e(substr($dev['device_uuid'], 0, 20)) ?>...</code></td>
                <td><?= e($dev['platform'] ?? '—') ?> <?= e($dev['model'] ?? '') ?></td>
                <td><?= statusBadge($dev['approval_status']) ?></td>
                <td class="text-sm"><?= formatDateTime($dev['first_login_at']) ?></td>
                <td class="text-sm"><?= formatDateTime($dev['last_sync_at']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/app.php'; ?>
