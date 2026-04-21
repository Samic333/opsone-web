<?php /** Aircraft create/edit form — Phase 6 */ ?>
<div class="card" style="max-width: 780px;">
    <form method="POST" action="/aircraft/store">
        <?= csrfField() ?>

        <div class="form-row">
            <div class="form-group">
                <label>Registration *</label>
                <input type="text" name="registration" class="form-control" placeholder="e.g. 5X-ACZ" required>
            </div>
            <div class="form-group">
                <label>Aircraft Type *</label>
                <input type="text" name="aircraft_type" class="form-control" placeholder="e.g. DHC-8 Q400" required>
            </div>
            <div class="form-group">
                <label>Variant</label>
                <input type="text" name="variant" class="form-control" placeholder="e.g. -Q400">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Manufacturer</label>
                <input type="text" name="manufacturer" class="form-control">
            </div>
            <div class="form-group">
                <label>MSN (Serial)</label>
                <input type="text" name="msn" class="form-control">
            </div>
            <div class="form-group">
                <label>Year Built</label>
                <input type="number" name="year_built" class="form-control" min="1960" max="2030">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Fleet</label>
                <select name="fleet_id" class="form-control">
                    <option value="">— None —</option>
                    <?php foreach ($fleets as $f): ?>
                        <option value="<?= (int)$f['id'] ?>"><?= e($f['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Home Base</label>
                <select name="home_base_id" class="form-control">
                    <option value="">— None —</option>
                    <?php foreach ($bases as $b): ?>
                        <option value="<?= (int)$b['id'] ?>"><?= e($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="active">Active</option>
                    <option value="maintenance">In Maintenance</option>
                    <option value="aog">AOG</option>
                    <option value="stored">Stored</option>
                    <option value="retired">Retired</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Total Hours</label>
                <input type="number" step="0.1" name="total_hours" class="form-control" value="0">
            </div>
            <div class="form-group">
                <label>Total Cycles</label>
                <input type="number" name="total_cycles" class="form-control" value="0">
            </div>
        </div>

        <div class="form-group">
            <label>Notes</label>
            <textarea name="notes" class="form-control" rows="2"></textarea>
        </div>

        <div style="display:flex; gap:12px;">
            <button type="submit" class="btn btn-primary">Save Aircraft</button>
            <a href="/aircraft" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
