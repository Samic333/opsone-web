<div class="card" style="max-width:560px">
    <form method="POST" action="/fleets/update/<?= $fleet['id'] ?>">
        <?= csrfField() ?>
        <div class="form-group">
            <label class="form-label">Fleet Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required
                   value="<?= e($_POST['name'] ?? $fleet['name']) ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Code <span class="text-muted">(optional)</span></label>
            <input type="text" name="code" class="form-control" maxlength="20"
                   value="<?= e($_POST['code'] ?? $fleet['code'] ?? '') ?>"
                   style="text-transform:uppercase">
        </div>
        <div class="form-group">
            <label class="form-label">Aircraft Type <span class="text-muted">(optional)</span></label>
            <input type="text" name="aircraft_type" class="form-control"
                   value="<?= e($_POST['aircraft_type'] ?? $fleet['aircraft_type'] ?? '') ?>">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="/fleets" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
