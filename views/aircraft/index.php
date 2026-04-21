<?php /** Aircraft Registry — Phase 6 */ ?>
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(150px,1fr)); gap:12px; margin-bottom:18px;">
    <div class="card" style="padding:12px;"><div class="text-xs text-muted">Active / In MX</div><div style="font-size:22px;font-weight:700;"><?= (int)$summary['active'] ?></div></div>
    <div class="card" style="padding:12px;"><div class="text-xs text-muted">AOG</div><div style="font-size:22px;font-weight:700;color:#ef4444;"><?= (int)$summary['aog'] ?></div></div>
    <div class="card" style="padding:12px;"><div class="text-xs text-muted">Overdue MX</div><div style="font-size:22px;font-weight:700;color:#ef4444;"><?= (int)$summary['overdueMx'] ?></div></div>
    <div class="card" style="padding:12px;"><div class="text-xs text-muted">Due in 30d</div><div style="font-size:22px;font-weight:700;color:#f59e0b;"><?= (int)$summary['dueMx30'] ?></div></div>
    <div class="card" style="padding:12px;"><div class="text-xs text-muted">Expired docs</div><div style="font-size:22px;font-weight:700;color:#ef4444;"><?= (int)$summary['expiredDocs'] ?></div></div>
</div>

<div style="margin-bottom:12px;"><a href="/aircraft/create" class="btn btn-primary">+ Register Aircraft</a></div>

<?php if (empty($aircraft)): ?>
    <div class="card"><div class="empty-state" style="padding:40px 0;"><div class="icon">✈</div><h3>No aircraft registered</h3><p>Click "Register Aircraft" to add the first aircraft.</p></div></div>
<?php else: ?>
<div class="table-wrap"><table>
    <thead><tr><th>Registration</th><th>Type</th><th>Fleet</th><th>Base</th><th>Status</th><th>Hours</th><th>Cycles</th><th>Overdue</th><th>Due 30d</th><th>Doc</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($aircraft as $a): ?>
        <tr>
            <td><strong><?= e($a['registration']) ?></strong></td>
            <td><?= e($a['aircraft_type']) ?><?= $a['variant'] ? ' / ' . e($a['variant']) : '' ?></td>
            <td class="text-sm"><?= e($a['fleet_name'] ?? '—') ?></td>
            <td class="text-sm"><?= e($a['base_name']  ?? '—') ?></td>
            <td><?= statusBadge($a['status']) ?></td>
            <td class="text-sm"><?= number_format((float)$a['total_hours'], 1) ?></td>
            <td class="text-sm"><?= (int)$a['total_cycles'] ?></td>
            <td class="text-sm"><?= (int)$a['overdue_items'] > 0 ? '<strong style="color:#ef4444;">'.(int)$a['overdue_items'].'</strong>' : '—' ?></td>
            <td class="text-sm"><?= (int)$a['due_30d'] > 0 ? '<span style="color:#f59e0b;">'.(int)$a['due_30d'].'</span>' : '—' ?></td>
            <td class="text-sm"><?= (int)$a['expired_docs'] > 0 ? '<strong style="color:#ef4444;">'.(int)$a['expired_docs'].'</strong>' : '—' ?></td>
            <td><a href="/aircraft/<?= (int)$a['id'] ?>" class="btn btn-xs btn-outline">Open</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php endif; ?>
