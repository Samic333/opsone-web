<?php
/**
 * Create Revision form
 * Variables: $periods, $crewList, $dutyTypes
 */
$dutyTypes = $dutyTypes ?? RosterModel::dutyTypes();
?>
<style>
.rc-form{max-width:860px;}
.rc-section{background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:20px;margin-bottom:16px;}
.rc-section-title{font-size:13px;font-weight:700;margin-bottom:14px;padding-bottom:8px;border-bottom:1px solid var(--border);}
.rc-items{display:flex;flex-direction:column;gap:10px;}
.rc-item-row{display:grid;grid-template-columns:1fr 110px 1fr 110px 1fr auto;gap:8px;align-items:end;}
.rc-item-row label{font-size:11px;color:var(--text-muted);font-weight:600;display:block;margin-bottom:3px;}
.rc-item-row input,.rc-item-row select{width:100%;}
.rc-add-btn{font-size:12px;padding:6px 14px;cursor:pointer;border:1px dashed var(--border);
 background:none;border-radius:6px;color:var(--text-muted);margin-top:6px;width:100%;text-align:center;}
.rc-add-btn:hover{background:var(--bg-secondary);}
.rc-remove-btn{background:none;border:none;cursor:pointer;color:#ef4444;font-size:16px;padding:4px;line-height:1;}
</style>

<div class="rc-form">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;">
        <a href="/roster/revisions" class="btn btn-ghost">← Back to Revision Center</a>
    </div>

    <form method="POST" action="/roster/revisions/store" id="revForm">
        <?= csrfField() ?>

        <div class="rc-section">
            <div class="rc-section-title">Revision Details</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div>
                    <label class="form-label">Roster Period <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
                    <select name="roster_period_id" class="form-control">
                        <option value="">— No specific period —</option>
                        <?php foreach ($periods as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= e($p['name']) ?> (<?= ucfirst($p['status']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Change Source</label>
                    <select name="change_source" class="form-control">
                        <option value="scheduler">Scheduler</option>
                        <option value="manager_request">Manager Request</option>
                        <option value="crew_request">Crew Request</option>
                        <option value="operational">Operational Requirement</option>
                        <option value="system">System / Administrative</option>
                    </select>
                </div>
                <div style="grid-column:1/-1;">
                    <label class="form-label">Reason for Revision <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="reason" class="form-control"
                           placeholder="e.g. Crew swap due to medical standdown — FLT-001 replaced by FLT-009" required>
                </div>
                <div style="grid-column:1/-1;">
                    <label class="form-label">Additional Notes <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
                    <input type="text" name="notes" class="form-control" placeholder="Internal notes for this revision…">
                </div>
            </div>
        </div>

        <div class="rc-section">
            <div class="rc-section-title">Duty Changes</div>
            <div class="rc-items" id="revItems">
                <!-- Item template row (initially one row) -->
                <div class="rc-item-row">
                    <div>
                        <label>Crew Member</label>
                        <select name="item_user_id[]" class="form-control" required>
                            <option value="">Select crew…</option>
                            <?php foreach ($crewList as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= e($c['user_name']) ?> (<?= e($c['employee_id'] ?? $c['role_slug']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Date</label>
                        <input type="date" name="item_date[]" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div>
                        <label>New Duty Type</label>
                        <select name="item_new_duty[]" class="form-control" required>
                            <?php foreach ($dutyTypes as $key => $dt): ?>
                                <option value="<?= $key ?>"><?= $dt['code'] ?> — <?= $dt['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Duty Code <span style="color:var(--text-muted);font-weight:400;">(opt)</span></label>
                        <input type="text" name="item_new_code[]" class="form-control" placeholder="e.g. NBO-ADD" maxlength="30">
                    </div>
                    <div>
                        <label>Change Note <span style="color:var(--text-muted);font-weight:400;">(opt)</span></label>
                        <input type="text" name="item_note[]" class="form-control" placeholder="Brief reason for this item">
                    </div>
                    <div style="padding-bottom:2px;">
                        <label>&nbsp;</label>
                        <button type="button" class="rc-remove-btn" onclick="removeItem(this)" title="Remove">✕</button>
                    </div>
                </div>
            </div>
            <button type="button" class="rc-add-btn" onclick="addItem()">＋ Add another duty change</button>
        </div>

        <div style="display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary">Create &amp; Issue Revision</button>
            <a href="/roster/revisions" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>

<script>
const crewOptions = `<?php foreach ($crewList as $c): ?><option value="<?= $c['id'] ?>"><?= e(addslashes($c['user_name'])) ?> (<?= e(addslashes($c['employee_id'] ?? $c['role_slug'])) ?>)</option><?php endforeach; ?>`;
const dutyOptions = `<?php foreach ($dutyTypes as $key => $dt): ?><option value="<?= $key ?>"><?= $dt['code'] ?> — <?= $dt['label'] ?></option><?php endforeach; ?>`;

function addItem() {
    const container = document.getElementById('revItems');
    const row = document.createElement('div');
    row.className = 'rc-item-row';
    row.innerHTML = `
        <div><label>Crew Member</label><select name="item_user_id[]" class="form-control" required><option value="">Select crew…</option>${crewOptions}</select></div>
        <div><label>Date</label><input type="date" name="item_date[]" class="form-control" required value="<?= date('Y-m-d') ?>"></div>
        <div><label>New Duty Type</label><select name="item_new_duty[]" class="form-control" required>${dutyOptions}</select></div>
        <div><label>Duty Code</label><input type="text" name="item_new_code[]" class="form-control" placeholder="e.g. NBO-ADD" maxlength="30"></div>
        <div><label>Change Note</label><input type="text" name="item_note[]" class="form-control" placeholder="Brief reason"></div>
        <div style="padding-bottom:2px;"><label>&nbsp;</label><button type="button" class="rc-remove-btn" onclick="removeItem(this)">✕</button></div>`;
    container.appendChild(row);
}

function removeItem(btn) {
    const rows = document.querySelectorAll('#revItems .rc-item-row');
    if (rows.length > 1) btn.closest('.rc-item-row').remove();
}
</script>
