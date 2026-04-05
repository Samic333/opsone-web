<?php /** OpsOne — Edit Notice */ ?>
<div class="card" style="max-width: 800px;">
    <form method="POST" action="/notices/update/<?= $notice['id'] ?>">
        <?= csrfField() ?>
        <div class="form-group">
            <label>Title</label>
            <input type="text" name="title" class="form-control" value="<?= e($notice['title']) ?>" required>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Priority</label>
                <select name="priority" class="form-control">
                    <option value="normal" <?= $notice['priority'] === 'normal' ? 'selected' : '' ?>>Normal</option>
                    <option value="urgent" <?= $notice['priority'] === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                    <option value="critical" <?= $notice['priority'] === 'critical' ? 'selected' : '' ?>>Critical</option>
                </select>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category" class="form-control">
                    <option value="general" <?= $notice['category'] === 'general' ? 'selected' : '' ?>>General</option>
                    <option value="operational" <?= $notice['category'] === 'operational' ? 'selected' : '' ?>>Operational</option>
                    <option value="safety" <?= $notice['category'] === 'safety' ? 'selected' : '' ?>>Safety</option>
                    <option value="training" <?= $notice['category'] === 'training' ? 'selected' : '' ?>>Training</option>
                    <option value="policy" <?= $notice['category'] === 'policy' ? 'selected' : '' ?>>Policy</option>
                    <option value="schedule" <?= $notice['category'] === 'schedule' ? 'selected' : '' ?>>Schedule</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Body</label>
            <textarea name="body" class="form-control" rows="8" required><?= e($notice['body']) ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Expires At (optional)</label>
                <input type="datetime-local" name="expires_at" class="form-control" value="<?= $notice['expires_at'] ? date('Y-m-d\TH:i', strtotime($notice['expires_at'])) : '' ?>">
            </div>
            <div class="form-group" style="display: flex; align-items: end; padding-bottom: 4px;">
                <label class="form-check">
                    <input type="checkbox" name="published" value="1" <?= $notice['published'] ? 'checked' : '' ?>> Published
                </label>
            </div>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 24px;">
            <button type="submit" class="btn btn-primary">Update Notice</button>
            <a href="/notices" class="btn btn-outline">Cancel</a>
            <form method="POST" action="/notices/delete/<?= $notice['id'] ?>" style="margin-left: auto;" onsubmit="return confirm('Delete this notice?')">
                <?= csrfField() ?>
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
            </form>
        </div>
    </form>
</div>
