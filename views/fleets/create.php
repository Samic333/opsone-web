<div class="card" style="max-width:560px">
    <form method="POST" action="/fleets/store">
        <?= csrfField() ?>
        <div class="form-group">
            <label class="form-label">Fleet Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required
                   placeholder="e.g. Narrow-body Fleet" value="<?= e($_POST['name'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Code <span class="text-muted">(optional)</span></label>
            <input type="text" name="code" class="form-control" maxlength="20"
                   placeholder="e.g. NBF" value="<?= e($_POST['code'] ?? '') ?>"
                   style="text-transform:uppercase">
        </div>
        <div class="form-group">
            <label class="form-label">Aircraft Type <span class="text-muted">(optional)</span></label>
            <input type="text" name="aircraft_type" class="form-control"
                   placeholder="e.g. Boeing 737-800" value="<?= e($_POST['aircraft_type'] ?? '') ?>">
            <div class="form-help">Primary aircraft type associated with this fleet.</div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Fleet</button>
            <a href="/fleets" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
