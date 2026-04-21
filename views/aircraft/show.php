<?php /** Aircraft detail — maintenance + docs — Phase 6 */ ?>
<div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">

  <div class="card">
    <h3 style="margin-top:0;">Aircraft</h3>
    <table>
      <tr><td>Registration</td><td><strong><?= e($aircraft['registration']) ?></strong></td></tr>
      <tr><td>Type</td><td><?= e($aircraft['aircraft_type']) ?><?= $aircraft['variant'] ? ' / '.e($aircraft['variant']) : '' ?></td></tr>
      <tr><td>Manufacturer</td><td><?= e($aircraft['manufacturer'] ?? '—') ?></td></tr>
      <tr><td>MSN</td><td><?= e($aircraft['msn'] ?? '—') ?></td></tr>
      <tr><td>Year</td><td><?= e($aircraft['year_built'] ?? '—') ?></td></tr>
      <tr><td>Fleet</td><td><?= e($aircraft['fleet_name'] ?? '—') ?></td></tr>
      <tr><td>Base</td><td><?= e($aircraft['base_name'] ?? '—') ?></td></tr>
      <tr><td>Status</td><td><?= statusBadge($aircraft['status']) ?></td></tr>
      <tr><td>Total hours</td><td><?= number_format((float)$aircraft['total_hours'], 1) ?></td></tr>
      <tr><td>Total cycles</td><td><?= (int)$aircraft['total_cycles'] ?></td></tr>
    </table>
  </div>

  <div class="card">
    <h3 style="margin-top:0;">Add Maintenance Item</h3>
    <form method="POST" action="/aircraft/<?= (int)$aircraft['id'] ?>/maintenance/add">
        <?= csrfField() ?>
        <div class="form-row">
            <div class="form-group"><label>Item</label>
                <select name="item_type" class="form-control">
                    <option value="A_check">A-Check</option>
                    <option value="C_check">C-Check</option>
                    <option value="engine_oh">Engine Overhaul</option>
                    <option value="prop_oh">Prop Overhaul</option>
                    <option value="gear_insp">Gear Inspection</option>
                    <option value="airworthiness">Airworthiness renewal</option>
                    <option value="inspection">Inspection</option>
                </select>
            </div>
            <div class="form-group"><label>Due Date</label>
                <input type="date" name="due_date" class="form-control">
            </div>
            <div class="form-group"><label>Due Hours</label>
                <input type="number" step="0.1" name="due_hours" class="form-control">
            </div>
        </div>
        <div class="form-group"><label>Description</label>
            <input type="text" name="description" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Add Item</button>
    </form>
  </div>
</div>

<div class="card" style="margin-top:18px;">
    <h3 style="margin-top:0;">Maintenance Due</h3>
    <?php if (empty($maintenance)): ?>
        <p class="text-muted">No maintenance items recorded yet.</p>
    <?php else: ?>
    <table>
        <thead><tr><th>Type</th><th>Description</th><th>Due Date</th><th>Due Hours</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($maintenance as $m):
            $overdue = $m['due_date'] && $m['due_date'] < date('Y-m-d') && $m['status'] === 'active';
        ?>
            <tr<?= $overdue ? ' style="background:rgba(239,68,68,0.08);"' : '' ?>>
                <td><?= e($m['item_type']) ?></td>
                <td class="text-sm"><?= e($m['description'] ?? '—') ?></td>
                <td class="text-sm"><?= e($m['due_date'] ?? '—') ?><?= $overdue ? ' <strong style="color:#ef4444;">(overdue)</strong>' : '' ?></td>
                <td class="text-sm"><?= $m['due_hours'] ? number_format((float)$m['due_hours'],1) : '—' ?></td>
                <td><?= statusBadge($m['status']) ?></td>
                <td>
                    <?php if ($m['status'] === 'active'): ?>
                        <form method="POST" action="/aircraft/maintenance/<?= (int)$m['id'] ?>/complete" style="display:inline;">
                            <?= csrfField() ?>
                            <button type="submit" class="btn btn-xs btn-success">Mark done</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div class="card" style="margin-top:18px;">
    <h3 style="margin-top:0;">Aircraft Documents</h3>
    <form method="POST" action="/aircraft/<?= (int)$aircraft['id'] ?>/documents/add" style="margin-bottom:12px;">
        <?= csrfField() ?>
        <div class="form-row">
            <div class="form-group"><label>Type</label>
                <select name="doc_type" class="form-control">
                    <option value="airworthiness">Airworthiness</option>
                    <option value="registration">Registration</option>
                    <option value="insurance">Insurance</option>
                    <option value="noise_cert">Noise Cert</option>
                    <option value="radio">Radio Station</option>
                </select>
            </div>
            <div class="form-group"><label>Number</label><input type="text" name="doc_number" class="form-control"></div>
            <div class="form-group"><label>Issued</label><input type="date" name="issued_date" class="form-control"></div>
            <div class="form-group"><label>Expires</label><input type="date" name="expiry_date" class="form-control"></div>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Add Document</button>
    </form>

    <?php if (empty($documents)): ?>
        <p class="text-muted">No aircraft documents recorded.</p>
    <?php else: ?>
    <table>
        <thead><tr><th>Type</th><th>Number</th><th>Issued</th><th>Expires</th></tr></thead>
        <tbody>
        <?php foreach ($documents as $d):
            $exp = $d['expiry_date']; $expired = $exp && $exp < date('Y-m-d');
        ?>
            <tr<?= $expired ? ' style="background:rgba(239,68,68,0.08);"' : '' ?>>
                <td><?= e($d['doc_type']) ?></td>
                <td class="text-sm"><?= e($d['doc_number'] ?? '—') ?></td>
                <td class="text-sm"><?= e($d['issued_date'] ?? '—') ?></td>
                <td class="text-sm"><?= e($exp ?? '—') ?><?= $expired ? ' <strong style="color:#ef4444;">(expired)</strong>' : '' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div style="margin-top:16px;"><a href="/aircraft" class="btn btn-outline">← Back</a></div>
