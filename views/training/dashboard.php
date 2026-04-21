<?php /** Phase 12 — Training Dashboard */ ?>
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px,1fr)); gap:12px; margin-bottom:18px;">
    <div class="card" style="padding:12px;"><div class="text-xs text-muted">Total records</div><div style="font-size:22px; font-weight:700;"><?= (int)$summary['total_records'] ?></div></div>
    <div class="card" style="padding:12px;"><div class="text-xs text-muted">Expiring (30d)</div><div style="font-size:22px; font-weight:700; color:#f59e0b;"><?= (int)$summary['expiring_30d'] ?></div></div>
    <div class="card" style="padding:12px;"><div class="text-xs text-muted">Expired</div><div style="font-size:22px; font-weight:700; color:#ef4444;"><?= (int)$summary['expired'] ?></div></div>
    <div class="card" style="padding:12px;"><div class="text-xs text-muted">In progress</div><div style="font-size:22px; font-weight:700;"><?= (int)$summary['in_progress'] ?></div></div>
</div>

<div class="card" style="margin-bottom:16px;">
    <h3 style="margin-top:0;">Upcoming / recent expiries</h3>
    <?php if (empty($expiring)): ?>
        <p class="text-muted">No expirations inside the –14 / +60 day window.</p>
    <?php else: ?>
    <table class="table">
        <thead><tr><th>Staff</th><th>Emp #</th><th>Training</th><th>Completed</th><th>Expires</th><th>Result</th></tr></thead>
        <tbody>
        <?php foreach ($expiring as $r):
            $exp = $r['expires_date']; $past = $exp && $exp < date('Y-m-d');
        ?>
            <tr<?= $past ? ' style="background:rgba(239,68,68,0.08);"' : '' ?>>
                <td><?= e($r['user_name']) ?></td>
                <td class="text-xs text-muted"><?= e($r['employee_id'] ?? '') ?></td>
                <td><?= e($r['type_name'] ?? $r['type_code'] ?? '—') ?></td>
                <td class="text-sm"><?= e($r['completed_date']) ?></td>
                <td class="text-sm<?= $past ? ' ' : '' ?>"><?= e($exp ?? '—') ?><?= $past ? ' <strong style="color:#ef4444;">(expired)</strong>' : '' ?></td>
                <td class="text-sm"><?= e($r['result']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
  <div class="card">
    <h3 style="margin-top:0;">Add Training Type</h3>
    <form method="POST" action="/training/types/add">
        <?= csrfField() ?>
        <div class="form-row">
            <div class="form-group"><label>Code *</label><input type="text" name="code" class="form-control" required placeholder="recurrent_sim"></div>
            <div class="form-group"><label>Name *</label><input type="text" name="name" class="form-control" required placeholder="6-monthly Sim"></div>
            <div class="form-group"><label>Validity (months)</label><input type="number" name="validity_months" class="form-control" placeholder="6"></div>
        </div>
        <div class="form-group"><label>Applicable roles</label><input type="text" name="applicable_roles" class="form-control" placeholder="pilot,chief_pilot"></div>
        <button class="btn btn-primary btn-sm" type="submit">Add Type</button>
    </form>
  </div>
  <div class="card">
    <h3 style="margin-top:0;">Add Training Record</h3>
    <form method="POST" action="/training/records/add">
        <?= csrfField() ?>
        <div class="form-row">
            <div class="form-group"><label>User ID *</label><input type="number" name="user_id" class="form-control" required></div>
            <div class="form-group"><label>Type code</label><input type="text" name="type_code" class="form-control" placeholder="recurrent_sim"></div>
            <div class="form-group"><label>Result</label>
                <select name="result" class="form-control"><option>pass</option><option>fail</option><option>in_progress</option><option>scheduled</option></select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Completed *</label><input type="date" name="completed_date" class="form-control" required value="<?= date('Y-m-d') ?>"></div>
            <div class="form-group"><label>Expires</label><input type="date" name="expires_date" class="form-control"></div>
            <div class="form-group"><label>Provider</label><input type="text" name="provider" class="form-control"></div>
        </div>
        <button class="btn btn-primary btn-sm" type="submit">Save Record</button>
    </form>
  </div>
</div>
