<?php /** Phase 9 — Flights index (scheduler) */ ?>
<div style="display:flex; gap:8px; margin-bottom:12px;">
    <a href="/flights/create" class="btn btn-primary">+ New Flight</a>
    <form method="GET" action="/flights" style="display:flex; gap:6px; margin-left:auto;">
        <input type="date" name="from" value="<?= e($_GET['from'] ?? date('Y-m-d')) ?>" class="form-control" style="width:150px;">
        <input type="date" name="to"   value="<?= e($_GET['to']   ?? date('Y-m-d', strtotime('+14 days'))) ?>" class="form-control" style="width:150px;">
        <button class="btn btn-outline btn-sm" type="submit">Refresh</button>
    </form>
</div>

<?php if (empty($flights)): ?>
    <div class="card"><div class="empty-state" style="padding:40px 0;"><div class="icon">✈️</div><h3>No flights</h3><p>Create a flight to start assigning crew and uploading briefing packages.</p></div></div>
<?php else: ?>
<div class="table-wrap"><table>
    <thead><tr><th>Date</th><th>Flight</th><th>Route</th><th>STD/STA</th><th>A/C</th><th>Captain</th><th>FO</th><th>Status</th><th>Bag</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($flights as $f): ?>
        <tr>
            <td class="text-sm"><?= e($f['flight_date']) ?></td>
            <td><strong><?= e($f['flight_number']) ?></strong></td>
            <td class="text-sm"><?= e($f['departure']) ?> → <?= e($f['arrival']) ?></td>
            <td class="text-xs"><?= e($f['std'] ?? '—') ?> / <?= e($f['sta'] ?? '—') ?></td>
            <td class="text-sm"><?= e($f['reg'] ?? '—') ?></td>
            <td class="text-sm"><?= e($f['captain_name'] ?? '—') ?></td>
            <td class="text-sm"><?= e($f['fo_name'] ?? '—') ?></td>
            <td><?= statusBadge($f['status']) ?></td>
            <td class="text-sm"><?= (int)$f['bag_count'] ?: '—' ?></td>
            <td><a href="/flights/<?= (int)$f['id'] ?>" class="btn btn-xs btn-outline">Open</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php endif; ?>
