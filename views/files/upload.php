<?php $pageTitle = 'Upload Document'; ob_start(); ?>
<div class="card" style="max-width: 700px;">
    <form method="POST" action="/files/upload" enctype="multipart/form-data">
        <?= csrfField() ?>
        <div class="form-group">
            <label for="title">Document Title *</label>
            <input type="text" id="title" name="title" class="form-control" placeholder="e.g. OM-A Operations Manual" required>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" class="form-control">
                    <option value="">— Select Category —</option>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="version">Version</label>
                <input type="text" id="version" name="version" class="form-control" value="1.0" placeholder="e.g. 3.2">
            </div>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="form-control" rows="3" placeholder="Brief description of the document"></textarea>
        </div>
        <div class="form-group">
            <label for="file">File *</label>
            <input type="file" id="file" name="file" class="form-control" required
                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png">
            <small class="text-xs text-muted">Max 50MB. Allowed: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, PNG</small>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="status">Publish Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                </select>
            </div>
            <div class="form-group">
                <label for="effective_date">Effective Date</label>
                <input type="date" id="effective_date" name="effective_date" class="form-control">
            </div>
        </div>
        <div class="form-group">
            <label class="form-check">
                <input type="checkbox" name="requires_ack"> Requires acknowledgement from recipients
            </label>
        </div>
        <div class="form-group">
            <label>Visible to Roles <small class="text-muted">(leave empty for all roles)</small></label>
            <div class="checkbox-grid">
                <?php foreach ($roles as $r): ?>
                <label class="form-check">
                    <input type="checkbox" name="visible_roles[]" value="<?= $r['id'] ?>"> <?= e($r['name']) ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="flex gap-1 mt-2">
            <button type="submit" class="btn btn-primary">Upload Document</button>
            <a href="/files" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/app.php'; ?>
