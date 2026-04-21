<?php
/** Phase 7 — Pilot logbook */
$fmt = function(?int $mins): string {
    if (!$mins) return '—';
    $h = intdiv($mins, 60); $m = $mins % 60;
    return sprintf('%d:%02d', $h, $m);
};
?>
<div style="display:flex; gap:12px; margin-bottom:18px; flex-wrap:wrap;">
  <div class="card" style="flex:1; min-width:140px; padding:12px;">
      <div class="text-xs text-muted">Flights</div>
      <div style="font-size:22px; font-weight:700;"><?= (int)$totals['flights'] ?></div>
  </div>
  <div class="card" style="flex:1; min-width:140px; padding:12px;">
      <div class="text-xs text-muted">Block time</div>
      <div style="font-size:22px; font-weight:700;"><?= $fmt($totals['block_minutes']) ?></div>
  </div>
  <div class="card" style="flex:1; min-width:140px; padding:12px;">
      <div class="text-xs text-muted">Airborne</div>
      <div style="font-size:22px; font-weight:700;"><?= $fmt($totals['air_minutes']) ?></div>
  </div>
  <div class="card" style="flex:1; min-width:140px; padding:12px;">
      <div class="text-xs text-muted">Night time</div>
      <div style="font-size:22px; font-weight:700;"><?= $fmt($totals['night_minutes']) ?></div>
  </div>
  <div class="card" style="flex:1; min-width:140px; padding:12px;">
      <div class="text-xs text-muted">Landings</div>
      <div style="font-size:22px; font-weight:700;">
          <?= (int)$totals['landings_day'] ?>D /
          <?= (int)$totals['landings_night'] ?>N
      </div>
  </div>
</div>

<div style="display:flex; gap:8px; margin-bottom:12px;">
    <a href="/my-logbook/new" class="btn btn-primary">+ New Entry</a>
    <a href="/my-logbook/export" class="btn btn-outline">Export CSV</a>
</div>

<?php if (empty($logs)): ?>
    <div class="card"><div class="empty-state" style="padding:40px 0;"><div class="icon">📘</div><h3>No entries yet</h3><p>Tap "New Entry" to record your first flight.</p></div></div>
<?php else: ?>
<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Date</th><th>Flight</th><th>A/C</th><th>Reg</th>
                <th>Dep → Arr</th><th>Off/On</th><th>Block</th><th>Air</th>
                <th>Role</th><th>Rules</th><th>Ldg</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($logs as $l): ?>
            <tr>
                <td class="text-sm"><?= e($l['flight_date']) ?></td>
                <td class="text-sm"><?= e($l['flight_number'] ?? '—') ?></td>
                <td class="text-sm"><?= e($l['a_type'] ?? $l['aircraft_type'] ?? '—') ?></td>
                <td class="text-sm"><?= e($l['a_reg'] ?? $l['registration'] ?? '—') ?></td>
                <td class="text-sm"><?= e($l['departure'] ?? '') ?> → <?= e($l['arrival'] ?? '') ?></td>
                <td class="text-xs"><?= e($l['off_blocks'] ?? '—') ?> / <?= e($l['on_blocks'] ?? '—') ?></td>
                <td class="text-sm"><strong><?= $fmt((int)$l['block_minutes']) ?></strong></td>
                <td class="text-sm"><?= $fmt((int)$l['air_minutes']) ?></td>
                <td class="text-xs"><?= e($l['role']) ?></td>
                <td class="text-xs"><?= e($l['rules']) ?></td>
                <td class="text-xs"><?= (int)$l['landings_day'] ?>D/<?= (int)$l['landings_night'] ?>N</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
