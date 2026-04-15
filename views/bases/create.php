<div class="card" style="max-width:560px">
    <form method="POST" action="/bases/store">
        <?= csrfField() ?>
        <div class="form-group">
            <label class="form-label">Base Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required
                   placeholder="e.g. Dubai Hub" value="<?= e($_POST['name'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Station / IATA Code <span class="text-danger">*</span></label>
            <input type="text" name="code" class="form-control" required maxlength="10"
                   placeholder="e.g. DXB" value="<?= e($_POST['code'] ?? '') ?>"
                   style="text-transform:uppercase">
            <div class="form-help">IATA airport code or internal station identifier.</div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Base</button>
            <a href="/bases" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
