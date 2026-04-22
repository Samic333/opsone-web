<?php
/**
 * Roster Generator wizard.
 * Variables: $crewByRole, $bases, $periods, $patterns,
 *            $srcYear, $srcMonth, $tgtYear, $tgtMonth, $defaultFrom, $defaultTo
 */
$totalCrew = 0;
foreach ($crewByRole as $g) $totalCrew += count($g);

$blocked = $_SESSION['_roster_generator_blocked'] ?? null;
unset($_SESSION['_roster_generator_blocked']);
?>
<style>
.gen-card     { background: var(--bg-card); border:1px solid var(--border); border-radius:10px; padding:20px; margin-bottom:18px; }
.gen-h        { font-size:14px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted); margin-bottom:12px;}
.gen-grid2    { display:grid; grid-template-columns: 1fr 1fr; gap:14px; }
.gen-grid3    { display:grid; grid-template-columns: repeat(3, 1fr); gap:14px; }
.gen-mode-tabs{ display:flex; gap:8px; margin-bottom:18px; }
.gen-mode-tab { flex:1; padding:14px; border:1px solid var(--border); border-radius:10px; cursor:pointer; background:var(--bg-card); }
.gen-mode-tab.active{ border-color: var(--accent-blue, #3b82f6); background: rgba(59,130,246,.1); }
.gen-mode-tab h4{ margin:0 0 4px; font-size:14px;}
.gen-mode-tab p { margin:0; font-size:12px; color:var(--text-muted); }
.crew-group   { margin-bottom:14px; }
.crew-group h5{ margin:0 0 6px; font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted);}
.crew-row     { display:flex; align-items:center; gap:8px; padding:4px 6px; border-radius:6px; font-size:13px; }
.crew-row:hover { background: rgba(59,130,246,.05);}
.crew-pick    { display:grid; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); gap:6px; }
.preset-chip  { display:inline-block; padding:4px 10px; border:1px solid var(--border); border-radius:999px; font-size:12px; cursor:pointer; }
.preset-chip:hover { background: rgba(59,130,246,.05);}
.blocked-row  { padding:6px; border-bottom:1px solid var(--border); font-size:12px; }
.warn-banner  { background:#fef3c7; color:#92400e; border:1px solid #f59e0b; padding:10px 14px; border-radius:8px; font-size:13px; margin-bottom:14px; }
</style>

<?php if ($blocked && is_array($blocked)): ?>
<div class="warn-banner">
    <strong>⚠ Last run blocked <?= count($blocked) ?> entries</strong> — review the eligibility issues below
    and either fix the source data, override the rule, or skip those crew.
    <details style="margin-top:8px;">
        <summary style="cursor:pointer;">Show blocked entries</summary>
        <div style="margin-top:8px; max-height:240px; overflow:auto; background:#fff; border-radius:6px;">
            <?php foreach (array_slice($blocked, 0, 200) as $b): ?>
                <div class="blocked-row">
                    user #<?= e($b['user_id']) ?> · <?= e($b['date']) ?> · <?= e($b['duty_type']) ?>
                    — <?= e(implode(' · ', $b['reasons'])) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </details>
</div>
<?php endif; ?>

<form method="POST" action="/roster/generate" id="gen-form">
    <?= csrfField() ?>

    <!-- Mode picker -->
    <div class="gen-mode-tabs">
        <label class="gen-mode-tab active" data-mode="copy_from_month" onclick="genPickMode(this,'copy_from_month')">
            <input type="radio" name="mode" value="copy_from_month" checked style="display:none;">
            <h4>Clone a month</h4>
            <p>Copy a previous month's pattern onto a new target month — same crew, same shapes, new dates.</p>
        </label>
        <label class="gen-mode-tab" data-mode="pattern" onclick="genPickMode(this,'pattern')">
            <input type="radio" name="mode" value="pattern" style="display:none;">
            <h4>Apply a rotation</h4>
            <p>Pick a preset (e.g. 5-on / 2-off, 4-on / 3-off) and apply it across a date range.</p>
        </label>
    </div>

    <!-- Mode A: Copy from month -->
    <div class="gen-card" id="gen-mode-copy">
        <div class="gen-h">Copy from / to</div>
        <div class="gen-grid2">
            <div>
                <label class="form-label">Source month</label>
                <div class="gen-grid2">
                    <select name="src_year" class="form-control">
                        <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 2; $y--): ?>
                            <option value="<?= $y ?>" <?= $y === $srcYear ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                    <select name="src_month" class="form-control">
                        <?php foreach ([1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'] as $mn=>$ml): ?>
                            <option value="<?= $mn ?>" <?= $mn === $srcMonth ? 'selected' : '' ?>><?= $ml ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div>
                <label class="form-label">Target month</label>
                <div class="gen-grid2">
                    <select name="tgt_year" class="form-control">
                        <?php for ($y = (int)date('Y'); $y <= (int)date('Y') + 2; $y++): ?>
                            <option value="<?= $y ?>" <?= $y === $tgtYear ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                    <select name="tgt_month" class="form-control">
                        <?php foreach ([1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'] as $mn=>$ml): ?>
                            <option value="<?= $mn ?>" <?= $mn === $tgtMonth ? 'selected' : '' ?>><?= $ml ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        <p style="margin-top:8px;font-size:12px;color:var(--text-muted);">
            For each chosen crew member: their day-of-month duties from the source month are duplicated onto the target month.
            Empty days in the source remain empty. Existing entries on the target are kept unless <em>overwrite</em> is ticked below.
        </p>
    </div>

    <!-- Mode B: Pattern -->
    <div class="gen-card" id="gen-mode-pattern" style="display:none;">
        <div class="gen-h">Apply a rotation pattern</div>
        <div class="gen-grid3">
            <div>
                <label class="form-label">Pattern</label>
                <select name="pattern_key" class="form-control">
                    <?php foreach ($patterns as $p): ?>
                        <option value="<?= e($p['key']) ?>"><?= e($p['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label">From</label>
                <input type="date" name="from_date" class="form-control" value="<?= e($defaultFrom) ?>">
            </div>
            <div>
                <label class="form-label">To</label>
                <input type="date" name="to_date" class="form-control" value="<?= e($defaultTo) ?>">
            </div>
        </div>
        <div style="margin-top:10px;">
            <label style="font-size:13px;display:flex;gap:6px;align-items:center;">
                <input type="checkbox" name="offset_mode" value="stagger" checked>
                Stagger users across the cycle (so the whole airline isn't off on the same day)
            </label>
        </div>
    </div>

    <!-- Crew picker -->
    <div class="gen-card">
        <div class="gen-h" style="display:flex;justify-content:space-between;align-items:center;">
            <span>Crew to roster (<?= $totalCrew ?> available)</span>
            <span>
                <span class="preset-chip" onclick="genSelectAll(true)">Select all</span>
                <span class="preset-chip" onclick="genSelectAll(false)">Clear</span>
                <span class="preset-chip" onclick="genSelectByRole('Pilot')">Pilots</span>
                <span class="preset-chip" onclick="genSelectByRole('Cabin Crew')">Cabin</span>
                <span class="preset-chip" onclick="genSelectByRole('Engineer')">Engineers</span>
                <span class="preset-chip" onclick="genSelectByRole('Base Manager')">Base Mgrs</span>
            </span>
        </div>
        <div class="crew-pick">
            <?php foreach ($crewByRole as $roleName => $members): ?>
                <div class="crew-group" data-role="<?= e($roleName) ?>">
                    <h5>■ <?= e($roleName) ?> <span style="color:var(--text-muted);">(<?= count($members) ?>)</span></h5>
                    <?php foreach ($members as $m): ?>
                        <label class="crew-row">
                            <input type="checkbox" name="user_ids[]" value="<?= (int)$m['id'] ?>"
                                   class="gen-crew" data-role="<?= e($roleName) ?>">
                            <span><?= e($m['user_name']) ?></span>
                            <span style="color:var(--text-muted);font-size:11px;">
                                <?= e($m['employee_id'] ?? '') ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <p id="gen-pick-summary" style="margin-top:8px;font-size:12px;color:var(--text-muted);">
            0 crew selected
        </p>
    </div>

    <!-- Options -->
    <div class="gen-card">
        <div class="gen-h">Options</div>
        <div class="gen-grid2">
            <div>
                <label class="form-label">Save into period (optional)</label>
                <select name="roster_period_id" class="form-control">
                    <option value="">— No period (loose entries) —</option>
                    <?php foreach ($periods as $p): ?>
                        <option value="<?= (int)$p['id'] ?>">
                            <?= e($p['name']) ?> · <?= e($p['start_date']) ?> → <?= e($p['end_date']) ?> · <?= e($p['status']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex;flex-direction:column;gap:8px;justify-content:flex-end;">
                <label style="font-size:13px;display:flex;gap:6px;align-items:center;">
                    <input type="checkbox" name="overwrite" value="1">
                    Overwrite existing entries on the target dates
                </label>
                <label style="font-size:13px;display:flex;gap:6px;align-items:center;">
                    <input type="checkbox" name="ignore_compliance" value="1">
                    Ignore compliance blocks (expired licence / medical) — use sparingly
                </label>
            </div>
        </div>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;">
        <a href="/roster" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary">Generate Roster</button>
    </div>
</form>

<script>
function genPickMode(el, mode) {
    document.querySelectorAll('.gen-mode-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    el.querySelector('input[type=radio]').checked = true;
    document.getElementById('gen-mode-copy').style.display    = (mode === 'copy_from_month') ? '' : 'none';
    document.getElementById('gen-mode-pattern').style.display = (mode === 'pattern') ? '' : 'none';
}
function genSelectAll(state) {
    document.querySelectorAll('.gen-crew').forEach(cb => cb.checked = !!state);
    genUpdateSummary();
}
function genSelectByRole(role) {
    document.querySelectorAll('.gen-crew').forEach(cb => {
        if (cb.dataset.role === role) cb.checked = true;
    });
    genUpdateSummary();
}
function genUpdateSummary() {
    const n = document.querySelectorAll('.gen-crew:checked').length;
    document.getElementById('gen-pick-summary').textContent = n + ' crew selected';
}
document.querySelectorAll('.gen-crew').forEach(cb => cb.addEventListener('change', genUpdateSummary));
</script>
