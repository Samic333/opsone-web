<?php
/** Phase 7 — Admin cross-crew logbook totals */
$fmt = function(?int $m): string { if (!$m) return '—'; return sprintf('%d:%02d', intdiv($m,60), $m%60); };
?>
<form method="GET" action="/logbook" style="display:flex; gap:8px; margin-bottom:12px;">
    <input type="date" name="from" value="<?= e($_GET['from'] ?? date('Y-m-d', strtotime('-90 days'))) ?>" class="form-control" style="width:160px;">
    <input type="date" name="to"   value="<?= e($_GET['to']   ?? date('Y-m-d')) ?>" class="form-control" style="width:160px;">
    <button type="submit" class="btn btn-sm btn-outline">Refresh</button>
</form>

<?php if (empty($rows)): ?>
    <div class="card"><p class="text-muted" style="padding:20px;">No flight entries in this date range.</p></div>
<?php else: ?>
<div class="card">
    <table class="table">
        <thead><tr><th>Pilot</th><th>Emp #</th><th>Flights</th><th>Block</th><th>Airborne</th><th>Night</th><th>Landings</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><strong><?= e($r['name']) ?></strong></td>
                <td class="text-xs text-muted"><?= e($r['employee_id'] ?? '') ?></td>
                <td class="text-sm"><?= (int)$r['flights'] ?></td>
                <td><strong><?= $fmt((int)$r['block_min']) ?></strong></td>
                <td class="text-sm"><?= $fmt((int)$r['air_min']) ?></td>
                <td class="text-sm"><?= $fmt((int)$r['night_min']) ?></td>
                <td class="text-sm"><?= (int)$r['ldg_day'] ?>D / <?= (int)$r['ldg_night'] ?>N</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
