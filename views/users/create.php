<?php $pageTitle = 'Create User'; ob_start(); ?>
<div class="card" style="max-width: 700px;">
    <form method="POST" action="/users/store">
        <?= csrfField() ?>
        <div class="form-row">
            <div class="form-group">
                <label for="name">Full Name *</label>
                <input type="text" id="name" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" class="form-control" required minlength="6">
            </div>
            <div class="form-group">
                <label for="employee_id">Employee ID</label>
                <input type="text" id="employee_id" name="employee_id" class="form-control" placeholder="e.g. FLT-042">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="department_id">Department</label>
                <select id="department_id" name="department_id" class="form-control">
                    <option value="">— Select —</option>
                    <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="base_id">Base</label>
                <select id="base_id" name="base_id" class="form-control">
                    <option value="">— Select —</option>
                    <?php foreach ($bases as $b): ?>
                    <option value="<?= $b['id'] ?>"><?= e($b['name']) ?> (<?= e($b['code']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="active">Active</option>
                    <option value="pending">Pending</option>
                </select>
            </div>
            <div class="form-group" style="display:flex;flex-direction:column;justify-content:flex-end;padding-bottom:22px;gap:8px;">
                <label class="form-check">
                    <input type="checkbox" name="web_access" checked> Allow web portal access
                </label>
                <label class="form-check">
                    <input type="checkbox" name="mobile_access" checked> Allow mobile app access
                </label>
            </div>
        </div>
        <div class="form-group">
            <label>Assign Roles</label>
            <div class="checkbox-grid">
                <?php foreach ($roles as $r): ?>
                <label class="form-check">
                    <input type="checkbox" name="roles[]" value="<?= $r['id'] ?>"> <?= e($r['name']) ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="flex gap-1 mt-2">
            <button type="submit" class="btn btn-primary">Create User</button>
            <a href="/users" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/app.php'; ?>
