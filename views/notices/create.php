<?php /** OpsOne — Create Notice */ ?>
<div class="card" style="max-width: 800px;">
    <form method="POST" action="/notices/store">
        <?= csrfField() ?>
        <div class="form-group">
            <label>Title</label>
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
                    <option value="operational">Operational</option>
                    <option value="safety">Safety</option>
                    <option value="training">Training</option>
                    <option value="policy">Policy</option>
                    <option value="schedule">Schedule</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Body</label>
            <textarea name="body" class="form-control" rows="8" placeholder="Notice content..." required></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Expires At (optional)</label>
                <input type="datetime-local" name="expires_at" class="form-control">
            </div>
            <div class="form-group" style="display: flex; align-items: end; padding-bottom: 4px;">
                <label class="form-check">
                    <input type="checkbox" name="published" value="1"> Publish immediately
                </label>
            </div>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 24px;">
            <button type="submit" class="btn btn-primary">Create Notice</button>
            <a href="/notices" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
