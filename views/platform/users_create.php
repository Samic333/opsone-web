<?php
$pageTitle = $pageTitle ?? 'Add Platform Staff';
ob_start();
?>

<div style="max-width:560px;">
<div class="card">
    <form method="POST" action="/platform/users/store">
        <?= csrfField() ?>

        <div class="form-group">
            <label>Full Name *</label>
            <input type="text" name="name" class="form-control" required
                   value="<?= e($_POST['name'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Email Address *</label>
            <input type="email" name="email" class="form-control" required
                   value="<?= e($_POST['email'] ?? '') ?>">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Employee / Staff ID</label>
                <input type="text" name="employee_id" class="form-control"
                       value="<?= e($_POST['employee_id'] ?? '') ?>" placeholder="PLT-XXX">
            </div>
            <div class="form-group">
                <label>Platform Role *</label>
                <select name="role" class="form-control" required>
                    <option value="">— select role —</option>
                    <?php foreach ($platformRoles as $slug => $label): ?>
                    <option value="<?= e($slug) ?>" <?= ($_POST['role'] ?? '') === $slug ? 'selected' : '' ?>>
                        <?= e($label) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Temporary Password *</label>
            <input type="password" name="password" class="form-control" required
                   autocomplete="new-password" minlength="10">
            <small style="color:var(--text-muted); font-size:11px;">Min 10 characters. User should change on first login.</small>
        </div>

        <div class="flex gap-1" style="margin-top:1.5rem;">
            <button type="submit" class="btn btn-primary">Create Account</button>
            <a href="/platform/users" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/app.php'; ?>
