<?php /** OpsOne — Bulk Roster Assignment */ ?>

<div style="max-width:760px;">
    <form method="POST" action="/roster/bulk-assign">
        <?= csrfField() ?>

        <div class="card">
            <h3 style="margin:0 0 20px; font-size:15px;">Date Range &amp; Duty</h3>

            <div class="grid grid-2" style="gap:16px;">
                <div class="form-group">
                    <label>From Date <span style="color:#ef4444;">*</span></label>
                    <input type="date" name="from_date" class="form-control"
                           value="<?= e($_POST['from_date'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="form-group">
                    <label>To Date <span style="color:#ef4444;">*</span></label>
                    <input type="date" name="to_date" class="form-control"
                           value="<?= e($_POST['to_date'] ?? date('Y-m-d')) ?>" required>
                </div>
            </div>

            <div class="grid grid-2" style="gap:16px;">
                <div class="form-group">
                    <label>Duty Type <span style="color:#ef4444;">*</span></label>
                    <select name="duty_type" class="form-control" required>
                        <?php foreach ($dutyTypes as $slug => $dt): ?>
                            <option value="<?= $slug ?>" <?= ($_POST['duty_type'] ?? '') === $slug ? 'selected' : '' ?>>
                                <?= $dt['label'] ?> (<?= $dt['code'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Custom Code <span class="text-muted text-xs">(optional)</span></label>
                    <input type="text" name="duty_code" class="form-control" maxlength="20"
                           placeholder="e.g. SBY2, LVE-A"
                           value="<?= e($_POST['duty_code'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Notes <span class="text-muted text-xs">(optional)</span></label>
                <input type="text" name="notes" class="form-control" maxlength="255"
                       placeholder="e.g. Annual leave block, Route NBO-DXB"
                       value="<?= e($_POST['notes'] ?? '') ?>">
            </div>

            <div class="grid grid-2" style="gap:16px;">
                <div class="form-group">
                    <label>Link to Period <span class="text-muted text-xs">(optional)</span></label>
                    <select name="roster_period_id" class="form-control">
                        <option value="">— No period —</option>
                        <?php foreach ($periods as $p): ?>
                            <?php if (in_array($p['status'], ['draft', 'published'])): ?>
                            <option value="<?= $p['id'] ?>"
                                <?= ($_POST['roster_period_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                                <?= e($p['name']) ?> (<?= ucfirst($p['status']) ?>)
                            </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="padding-top:28px;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="checkbox" name="overwrite" value="1"
                               <?= !empty($_POST['overwrite']) ? 'checked' : '' ?>>
                        Overwrite existing entries
                    </label>
                    <p class="text-xs text-muted" style="margin-top:4px;">
                        If unchecked, existing entries for a crew member on a date are left unchanged.
                    </p>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top:16px;">
            <h3 style="margin:0 0 16px; font-size:15px;">
                Select Crew
                <span class="text-muted text-sm" style="font-weight:400;">— <?= count($crewList) ?> active crew members</span>
            </h3>

            <div style="display:flex; gap:8px; margin-bottom:12px; flex-wrap:wrap; align-items:center;">
                <button type="button" onclick="selectAll(true)" class="btn btn-ghost btn-xs">Select All Visibile</button>
                <button type="button" onclick="selectAll(false)" class="btn btn-ghost btn-xs">Deselect All Visible</button>

                <div style="margin-left:auto; display:flex; gap:8px; align-items:center;">
                    <span class="text-xs text-muted" style="font-weight:600;">Filter:</span>
                    <select id="bulkFilterBase" class="form-control" style="width:140px; padding:2px 8px; font-size:12px; min-height:26px;" onchange="applyCrewFilter()">
                        <option value="">— All Bases —</option>
                        <?php foreach ($bases as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="bulkFilterRole" class="form-control" style="width:140px; padding:2px 8px; font-size:12px; min-height:26px;" onchange="applyCrewFilter()">
                        <option value="">— All Roles —</option>
                        <option value="pilot">Pilots</option>
                        <option value="chief_pilot">Chief Pilots</option>
                        <option value="cabin_crew">Cabin Crew</option>
                        <option value="head_cabin_crew">Head Cabin Crew</option>
                        <option value="engineer">Engineers</option>
                    </select>
                </div>
            </div>

            <?php
            // Group crew by role
            $byRole = [];
            foreach ($crewList as $c) {
                $byRole[$c['role_name']][] = $c;
            }
            ?>

            <?php foreach ($byRole as $roleName => $members): ?>
            <div style="margin-bottom:16px;">
                <p class="text-xs text-muted" style="margin:0 0 8px; text-transform:uppercase; letter-spacing:.05em; font-weight:600;">
                    <?= e($roleName) ?>
                </p>
                <div class="crew-role-group" data-role-group="<?= e($roleName) ?>" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(200px,1fr)); gap:6px;">
                    <?php foreach ($members as $c): ?>
                    <label class="crew-checkbox-label" data-base-id="<?= $c['base_id'] ?>" data-role-slug="<?= $c['role_slug'] ?>" style="display:flex; align-items:center; gap:8px; padding:8px 10px; border:1px solid var(--border); border-radius:6px; cursor:pointer; font-size:13px;">
                        <input type="checkbox" name="user_ids[]" value="<?= $c['id'] ?>"
                               class="crew-check"
                               <?= in_array($c['id'], $_POST['user_ids'] ?? []) ? 'checked' : '' ?>>
                        <span>
                            <strong><?= e($c['user_name']) ?></strong><br>
                            <span class="text-muted" style="font-size:11px;"><?= e($c['employee_id'] ?? '') ?></span>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="display:flex; gap:12px; margin-top:4px;">
            <button type="submit" class="btn btn-primary">Apply Bulk Assignment</button>
            <a href="/roster" class="btn btn-ghost">Cancel</a>
        </div>

    </form>
</div>

<style>
.crew-checkbox-label:has(.crew-check:checked) {
    border-color: #3b82f6;
    background: rgba(59,130,246,.07);
}
</style>

<script>
function selectAll(state) {
    document.querySelectorAll('.crew-check').forEach(cb => {
        let parent = cb.closest('.crew-checkbox-label');
        if (parent.style.display !== 'none') {
            cb.checked = state;
        }
    });
}

function applyCrewFilter() {
    let baseId = document.getElementById('bulkFilterBase').value;
    let roleSlug = document.getElementById('bulkFilterRole').value;
    
    document.querySelectorAll('.crew-checkbox-label').forEach(lbl => {
        let matchBase = !baseId || lbl.getAttribute('data-base-id') === baseId;
        let matchRole = !roleSlug || lbl.getAttribute('data-role-slug') === roleSlug;
        if (matchBase && matchRole) {
            lbl.style.display = 'flex';
        } else {
            lbl.style.display = 'none';
        }
    });
}
</script>
