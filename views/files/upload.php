<?php
/**
 * OpsOne — Upload Document
 *
 * Phase 4: role + department + base targeting, optional replaces-previous mode
 */
$pageTitle = isset($replacesFile) && $replacesFile
    ? 'Upload New Version'
    : 'Upload Document';
ob_start();
?>
<div class="card" style="max-width: 760px;">

    <?php if (!empty($replacesFile)): ?>
        <div class="card bg-secondary" style="margin-bottom:16px; padding:12px 14px; border-left:3px solid #3b82f6;">
            <div class="text-xs text-muted">Replacing</div>
            <strong><?= e($replacesFile['title']) ?></strong>
            <span class="text-muted">v<?= e($replacesFile['version']) ?></span>
            <div class="text-xs text-muted" style="margin-top:6px;">
                The previous version will be archived automatically once this new version uploads.
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" action="/files/upload" enctype="multipart/form-data">
        <?= csrfField() ?>
        <?php if (!empty($replacesId)): ?>
            <input type="hidden" name="replaces_file_id" value="<?= (int)$replacesId ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="title">Document Title *</label>
            <input type="text" id="title" name="title" class="form-control"
                   value="<?= !empty($replacesFile) ? e($replacesFile['title']) : '' ?>"
                   placeholder="e.g. OM-A Operations Manual" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" class="form-control">
                    <option value="">— Select Category —</option>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>"
                        <?= (!empty($replacesFile) && $replacesFile['category_id'] == $c['id']) ? 'selected' : '' ?>>
                        <?= e($c['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="version">Version</label>
                <input type="text" id="version" name="version" class="form-control"
                       value="<?= !empty($replacesFile) ? '' : '1.0' ?>"
                       placeholder="e.g. 3.2" required>
            </div>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="form-control" rows="3"
                      placeholder="Brief description of the document"><?= !empty($replacesFile) ? e($replacesFile['description'] ?? '') : '' ?></textarea>
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
                    <option value="published" <?= !empty($replacesFile) ? 'selected' : '' ?>>Published</option>
                </select>
            </div>
            <div class="form-group">
                <label for="effective_date">Effective Date</label>
                <input type="date" id="effective_date" name="effective_date" class="form-control">
            </div>
        </div>

        <div class="form-group">
            <label class="form-check">
                <input type="checkbox" name="requires_ack" value="1"
                    <?= !empty($replacesFile) && $replacesFile['requires_ack'] ? 'checked' : '' ?>>
                Requires acknowledgement from recipients
            </label>
        </div>

        <!-- Targeting — role / department / base (OR semantics) -->
        <fieldset style="border:1px solid var(--border, #2d3346); border-radius:8px; padding:12px 14px; margin-top:6px;">
            <legend class="text-sm" style="padding:0 6px;">Audience <span class="text-muted">(leave all empty to publish to <strong>all staff</strong>; any selected row matches — OR)</span></legend>

            <?php if (!empty($roles)): ?>
            <div class="form-group" style="margin-top:6px;">
                <label class="text-xs text-muted">Roles</label>
                <div style="display:flex; flex-wrap:wrap; gap:8px;">
                    <?php foreach ($roles as $r): ?>
                    <label class="form-check" style="min-width:160px;">
                        <input type="checkbox" name="visible_roles[]" value="<?= $r['id'] ?>"
                            <?= in_array($r['id'], $selectedRoles ?? [], false) ? 'checked' : '' ?>>
                        <?= e($r['name']) ?>
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

        <div class="flex gap-1 mt-2">
            <button type="submit" class="btn btn-primary">
                <?= !empty($replacesFile) ? 'Upload New Version' : 'Upload Document' ?>
            </button>
            <a href="/files" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/app.php'; ?>
