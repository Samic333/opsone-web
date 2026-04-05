<?php $pageTitle = 'Edit Airline'; ob_start(); ?>
<div class="card" style="max-width: 600px;">
    <form method="POST" action="/tenants/update/<?= $tenant['id'] ?>">
        <?= csrfField() ?>
        <div class="form-group">
            <label for="name">Airline Name *</label>
            <input type="text" id="name" name="name" class="form-control" value="<?= e($tenant['name']) ?>" required>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="code">Airline Code *</label>
                <input type="text" id="code" name="code" class="form-control" value="<?= e($tenant['code']) ?>" maxlength="10" required style="text-transform:uppercase">
            </div>
            <div class="form-group">
                <label for="contact_email">Contact Email</label>
                <input type="email" id="contact_email" name="contact_email" class="form-control" value="<?= e($tenant['contact_email'] ?? '') ?>">
            </div>
        </div>
        <div class="flex gap-1">
            <button type="submit" class="btn btn-primary">Update Airline</button>
            <a href="/tenants" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/app.php'; ?>
