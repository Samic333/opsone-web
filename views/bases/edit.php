<div class="card" style="max-width:560px">
    <form method="POST" action="/bases/update/<?= $base['id'] ?>">
        <?= csrfField() ?>
        <div class="form-group">
            <label class="form-label">Base Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required
                   value="<?= e($_POST['name'] ?? $base['name']) ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Station / IATA Code <span class="text-danger">*</span></label>
            <input type="text" name="code" class="form-control" required maxlength="10"
                   value="<?= e($_POST['code'] ?? $base['code']) ?>"
                   style="text-transform:uppercase">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="/bases" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
