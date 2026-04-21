<?php /** Phase 13 — New appraisal */ ?>
<div class="card" style="max-width:760px;">
    <form method="POST" action="/appraisals/store">
        <?= csrfField() ?>
        <div class="form-row">
            <div class="form-group"><label>Subject *</label>
                <select name="subject_id" class="form-control" required>
                    <option value="">— Select crew member —</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Rotation / roster ref</label>
                <input type="text" name="rotation_ref" class="form-control" placeholder="e.g. P-2026-04">
            </div>
            <div class="form-group"><label>Overall rating (1-5)</label>
                <select name="rating_overall" class="form-control">
                    <option value="">—</option>
                    <?php for ($i=1; $i<=5; $i++): ?><option value="<?= $i ?>"><?= str_repeat('★', $i) ?></option><?php endfor; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Period from *</label><input type="date" name="period_from" class="form-control" required></div>
            <div class="form-group"><label>Period to *</label><input type="date" name="period_to" class="form-control" required></div>
        </div>
        <div class="form-group"><label>Strengths</label><textarea name="strengths" class="form-control" rows="2"></textarea></div>
        <div class="form-group"><label>Areas to improve</label><textarea name="improvements" class="form-control" rows="2"></textarea></div>
        <div class="form-group"><label>General comments</label><textarea name="comments" class="form-control" rows="3"></textarea></div>
        <div class="form-group">
            <label class="form-check"><input type="checkbox" name="confidential" value="1" checked> Confidential (visible to HR only until accepted)</label>
        </div>
        <div class="form-group"><label>Status</label>
            <select name="status" class="form-control"><option>draft</option><option selected>submitted</option></select>
        </div>
        <div style="display:flex; gap:12px;">
            <button class="btn btn-primary" type="submit">Save</button>
            <a href="/appraisals" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
