<?php /** OpsOne — Edit Document Metadata */ ?>
<div class="card" style="max-width: 860px;">
    <form method="POST" action="/files/update/<?= $file['id'] ?>">
        <?= csrfField() ?>

        <div class="form-group">
            <label>Title <span style="color:#ef4444;">*</span></label>
            <input type="text" name="title" class="form-control" value="<?= e($file['title']) ?>" required>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="3"><?= e($file['description'] ?? '') ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Category</label>
                <select name="category_id" class="form-control">
                    <option value="">— No Category —</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $file['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Version</label>
                <input type="text" name="version" class="form-control" value="<?= e($file['version'] ?? '1.0') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="draft"     <?= ($file['status'] ?? '') === 'draft'     ? 'selected' : '' ?>>Draft</option>
                    <option value="published" <?= ($file['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="archived"  <?= ($file['status'] ?? '') === 'archived'  ? 'selected' : '' ?>>Archived</option>
                </select>
            </div>
            <div class="form-group">
                <label>Effective Date</label>
                <input type="date" name="effective_date" class="form-control"
                       value="<?= e($file['effective_date'] ?? '') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Expires At (optional)</label>
                <input type="date" name="expires_at" class="form-control"
                       value="<?= e($file['expires_at'] ?? '') ?>">
            </div>
            <div class="form-group" style="display:flex; align-items:flex-end; padding-bottom:4px;">
                <label class="form-check">
                    <input type="checkbox" name="requires_ack" value="1" <?= $file['requires_ack'] ? 'checked' : '' ?>>
                    Requires acknowledgement
                </label>
            </div>
        </div>

        <!-- File info (read-only) -->
        <div class="card bg-secondary" style="margin:12px 0 20px; padding:14px 16px;">
            <div style="display:flex; gap:24px; flex-wrap:wrap;">
                <div><span class="text-xs text-muted">File</span><br><strong><?= e($file['file_name']) ?></strong></div>
                <div><span class="text-xs text-muted">Size</span><br><?= number_format(($file['file_size'] ?? 0) / 1024, 1) ?> KB</div>
                <div><span class="text-xs text-muted">Type</span><br><?= e($file['mime_type'] ?? '—') ?></div>
            </div>
            <p class="text-xs text-muted" style="margin:8px 0 0;">To replace the file, delete this document and re-upload.</p>
        </div>

        <!-- Role Visibility -->
        <?php if (!empty($roles)): ?>
        <div class="form-group">
            <label>Visible To (leave empty = all roles)</label>
            <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:6px;">
                <?php foreach ($roles as $role): ?>
                <label class="form-check" style="min-width:160px;">
                    <input type="checkbox" name="visible_roles[]" value="<?= $role['id'] ?>"
                           <?= in_array($role['id'], $selectedRoles ?? []) ? 'checked' : '' ?>>
                    <?= e($role['name']) ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div style="display:flex; gap:12px; margin-top:24px; align-items:center;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="/files" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
