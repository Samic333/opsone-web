<?php /** OpsOne — Edit Notice */ ?>
<div class="card" style="max-width: 860px;">
    <form method="POST" action="/notices/update/<?= $notice['id'] ?>">
        <?= csrfField() ?>

        <div class="form-group">
            <label>Title <span style="color:#ef4444;">*</span></label>
            <input type="text" name="title" class="form-control" value="<?= e($notice['title']) ?>" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Priority</label>
                <select name="priority" class="form-control">
                    <option value="normal"   <?= $notice['priority'] === 'normal'   ? 'selected' : '' ?>>Normal</option>
                    <option value="urgent"   <?= $notice['priority'] === 'urgent'   ? 'selected' : '' ?>>Urgent</option>
                    <option value="critical" <?= $notice['priority'] === 'critical' ? 'selected' : '' ?>>Critical</option>
                </select>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category" class="form-control">
                    <option value="general" <?= ($notice['category'] ?? '') === 'general' ? 'selected' : '' ?>>General</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat['slug']) ?>" <?= ($notice['category'] ?? '') === $cat['slug'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Body <span style="color:#ef4444;">*</span></label>
            <textarea name="body" class="form-control" rows="9" required><?= e($notice['body']) ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Expires At (optional)</label>
                <input type="datetime-local" name="expires_at" class="form-control"
                       value="<?= $notice['expires_at'] ? date('Y-m-d\TH:i', strtotime($notice['expires_at'])) : '' ?>">
            </div>
            <div class="form-group" style="display:flex; flex-direction:column; justify-content:flex-end; gap:10px; padding-bottom:4px;">
                <label class="form-check">
                    <input type="checkbox" name="published" value="1" <?= $notice['published'] ? 'checked' : '' ?>> Published
                </label>
                <label class="form-check">
                    <input type="checkbox" name="requires_ack" value="1" <?= $notice['requires_ack'] ? 'checked' : '' ?>> Requires acknowledgement
                </label>
            </div>
        </div>

        <!-- Role Targeting -->
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
            <p class="text-xs text-muted" style="margin-top:6px;">If no roles selected, notice is visible to all crew.</p>
        </div>
        <?php endif; ?>

        <div style="display:flex; gap:12px; margin-top:24px; align-items:center;">
            <button type="submit" class="btn btn-primary">Update Notice</button>
            <a href="/notices" class="btn btn-outline">Cancel</a>
            <form method="POST" action="/notices/delete/<?= $notice['id'] ?>" style="margin-left:auto;"
                  onsubmit="return confirm('Delete this notice? This cannot be undone.')">
                <?= csrfField() ?>
                <button type="submit" class="btn btn-danger btn-sm">Delete Notice</button>
            </form>
        </div>
    </form>
</div>
