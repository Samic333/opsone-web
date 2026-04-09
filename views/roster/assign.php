<?php
/**
 * Roster assign-duty form
 * Variables: $crewList, $dutyTypes
 */
?>
<div class="card" style="max-width:560px;">
    <div class="card-header">
        <div class="card-title">Assign Duty</div>
    </div>
    <form method="POST" action="/roster/assign" style="padding:0 0 4px;">
        <?= csrfField() ?>

        <div class="form-group">
            <label class="form-label">Crew Member <span style="color:var(--accent-red)">*</span></label>
            <select name="user_id" class="form-control" required>
                <option value="">— Select crew member —</option>
                <?php foreach ($crewList as $c): ?>
                <option value="<?= $c['id'] ?>"><?= e($c['user_name']) ?><?= $c['employee_id'] ? ' (' . e($c['employee_id']) . ')' : '' ?> — <?= e($c['role_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Date <span style="color:var(--accent-red)">*</span></label>
            <input type="date" name="roster_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label">Duty Type <span style="color:var(--accent-red)">*</span></label>
            <select name="duty_type" class="form-control" required>
                <?php foreach ($dutyTypes as $key => $dt): ?>
                <option value="<?= $key ?>"><?= $dt['code'] ?> — <?= $dt['label'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Custom Code <span style="font-size:11px;color:var(--text-muted);">(optional — overrides default e.g. SBY2, LVE-A)</span></label>
            <input type="text" name="duty_code" class="form-control" maxlength="20" placeholder="e.g. SBY2">
        </div>

        <div class="form-group">
            <label class="form-label">Notes <span style="font-size:11px;color:var(--text-muted);">(optional)</span></label>
            <input type="text" name="notes" class="form-control" maxlength="255" placeholder="e.g. Route LHR-DXB">
        </div>

        <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn btn-primary">Save Duty</button>
            <a href="/roster" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
