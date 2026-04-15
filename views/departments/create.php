<div class="card" style="max-width:560px">
    <form method="POST" action="/departments/store">
        <?= csrfField() ?>
        <div class="form-group">
            <label class="form-label">Department Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required
                   placeholder="e.g. Flight Operations" value="<?= e($_POST['name'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Code <span class="text-muted">(optional)</span></label>
            <input type="text" name="code" class="form-control" maxlength="20"
                   placeholder="e.g. FLT-OPS" value="<?= e($_POST['code'] ?? '') ?>">
            <div class="form-help">Short reference code used in reports and exports.</div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Department</button>
            <a href="/departments" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
