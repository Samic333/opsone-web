<?php /** Phase 9 — Crew "my flights" */ ?>
<?php if (empty($flights)): ?>
    <div class="card"><div class="empty-state" style="padding:40px 0;"><div class="icon">🛫</div><h3>No assigned flights</h3><p>You'll see upcoming flights and their briefing packages here.</p></div></div>
<?php else: ?>
<div class="card">
    <table class="table">
        <thead><tr><th>Date</th><th>Flight</th><th>Route</th><th>STD/STA</th><th>Aircraft</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($flights as $f): ?>
            <tr>
                <td class="text-sm"><?= e($f['flight_date']) ?></td>
                <td><strong><?= e($f['flight_number']) ?></strong></td>
                <td class="text-sm"><?= e($f['departure']) ?> → <?= e($f['arrival']) ?></td>
                <td class="text-xs"><?= e($f['std'] ?? '—') ?> / <?= e($f['sta'] ?? '—') ?></td>
                <td class="text-sm"><?= e($f['reg'] ?? '—') ?></td>
                <td><?= statusBadge($f['status']) ?></td>
                <td><a href="/flights/<?= (int)$f['id'] ?>" class="btn btn-xs btn-primary">Open bag</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
