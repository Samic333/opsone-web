<?php /** OpsOne — Create Notice */ ?>
<div class="card" style="max-width: 860px;">
    <form method="POST" action="/notices/store">
        <?= csrfField() ?>

        <div class="form-group">
            <label>Title <span style="color:#ef4444;">*</span></label>
            <input type="text" name="title" class="form-control" placeholder="Notice title" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Priority</label>
                <select name="priority" class="form-control">
                    <option value="normal">Normal</option>
                    <option value="urgent">Urgent</option>
                    <option value="critical">Critical</option>
                </select>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category" class="form-control">
                    <option value="general">General</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat['slug']) ?>"><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Body <span style="color:#ef4444;">*</span></label>
            <textarea name="body" class="form-control" rows="9" placeholder="Notice content..." required></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Expires At (optional)</label>
                <input type="datetime-local" name="expires_at" class="form-control">
            </div>
            <div class="form-group" style="display:flex; flex-direction:column; justify-content:flex-end; gap:10px; padding-bottom:4px;">
                <label class="form-check">
                    <input type="checkbox" name="published" value="1"> Publish immediately
                </label>
                <label class="form-check">
                    <input type="checkbox" name="requires_ack" value="1"> Requires acknowledgement
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
                    <input type="checkbox" name="visible_roles[]" value="<?= $role['id'] ?>">
                    <?= e($role['name']) ?>
                </label>
                <?php endforeach; ?>
            </div>
            <p class="text-xs text-muted" style="margin-top:6px;">If no roles selected, notice is visible to all crew.</p>
        </div>
        <?php endif; ?>

        <div style="display:flex; gap:12px; margin-top:24px;">
            <button type="submit" class="btn btn-primary">Create Notice</button>
            <a href="/notices" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
