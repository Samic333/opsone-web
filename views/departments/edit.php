<div class="card" style="max-width:560px">
    <form method="POST" action="/departments/update/<?= $department['id'] ?>">
        <?= csrfField() ?>
        <div class="form-group">
            <label class="form-label">Department Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required
                   value="<?= e($_POST['name'] ?? $department['name']) ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Code <span class="text-muted">(optional)</span></label>
            <input type="text" name="code" class="form-control" maxlength="20"
                   value="<?= e($_POST['code'] ?? $department['code'] ?? '') ?>">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="/departments" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
