<?php /** OpsOne — Edit Document Metadata (Phase 4: role + dept + base targeting) */ ?>
<div class="card" style="max-width: 880px;">
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
                <?php if (!empty($file['superseded_at'])): ?>
                    <div><span class="text-xs text-muted">Superseded</span><br><?= e($file['superseded_at']) ?></div>
                <?php endif; ?>
            </div>
            <p class="text-xs text-muted" style="margin:8px 0 0;">
                To replace the file with a newer revision,
                <a href="/files/upload?replaces=<?= (int)$file['id'] ?>">upload a new version</a> —
                that creates a linked v2 and archives this row automatically.
            </p>
        </div>

        <!-- Audience — role + department + base -->
        <fieldset style="border:1px solid var(--border, #2d3346); border-radius:8px; padding:12px 14px; margin-top:6px;">
            <legend class="text-sm" style="padding:0 6px;">Audience <span class="text-muted">(empty = all staff; selected rows OR together)</span></legend>

            <?php if (!empty($roles)): ?>
            <div class="form-group" style="margin-top:6px;">
                <label class="text-xs text-muted">Roles</label>
                <div style="display:flex; flex-wrap:wrap; gap:8px;">
                    <?php foreach ($roles as $role): ?>
                    <label class="form-check" style="min-width:160px;">
                        <input type="checkbox" name="visible_roles[]" value="<?= $role['id'] ?>"
                               <?= in_array($role['id'], $selectedRoles ?? [], false) ? 'checked' : '' ?>>
                        <?= e($role['name']) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($departments)): ?>
            <div class="form-group">
                <label class="text-xs text-muted">Departments</label>
                <div style="display:flex; flex-wrap:wrap; gap:8px;">
                    <?php foreach ($departments as $d): ?>
                    <label class="form-check" style="min-width:160px;">
                        <input type="checkbox" name="visible_departments[]" value="<?= $d['id'] ?>"
                               <?= in_array($d['id'], $selectedDepts ?? [], false) ? 'checked' : '' ?>>
                        <?= e($d['name']) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($bases)): ?>
            <div class="form-group" style="margin-bottom:0;">
                <label class="text-xs text-muted">Bases / Stations</label>
                <div style="display:flex; flex-wrap:wrap; gap:8px;">
                    <?php foreach ($bases as $b): ?>
                    <label class="form-check" style="min-width:160px;">
                        <input type="checkbox" name="visible_bases[]" value="<?= $b['id'] ?>"
                               <?= in_array($b['id'], $selectedBases ?? [], false) ? 'checked' : '' ?>>
                        <?= e($b['name']) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </fieldset>

        <div style="display:flex; gap:12px; margin-top:24px; align-items:center;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="/files" class="btn btn-outline">Cancel</a>
            <a href="/files/history/<?= (int)$file['id'] ?>" class="btn btn-outline">Version History</a>
            <a href="/files/ack-report/<?= (int)$file['id'] ?>" class="btn btn-outline">Acknowledgement Report</a>
        </div>
    </form>
</div>
