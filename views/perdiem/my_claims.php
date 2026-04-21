<?php /** Phase 11 — Crew per diem claims */ ?>
<div style="margin-bottom:12px;"><a href="/my-per-diem/new" class="btn btn-primary">+ Submit Claim</a></div>

<?php if (empty($claims)): ?>
    <div class="card"><div class="empty-state" style="padding:40px 0;"><div class="icon">💼</div><h3>No claims yet</h3><p>Submit your first per diem claim when returning from an outstation.</p></div></div>
<?php else: ?>
<div class="card">
    <table class="table">
        <thead><tr><th>Period</th><th>Country/Station</th><th>Days</th><th>Rate</th><th>Total</th><th>Status</th><th>Paid</th></tr></thead>
        <tbody>
        <?php foreach ($claims as $c): ?>
            <tr>
                <td class="text-sm"><?= e($c['period_from']) ?> → <?= e($c['period_to']) ?></td>
                <td class="text-sm"><?= e($c['country']) ?><?= $c['station'] ? ' / ' . e($c['station']) : '' ?></td>
                <td class="text-sm"><?= number_format((float)$c['days'], 2) ?></td>
                <td class="text-sm"><?= number_format((float)$c['rate'], 2) ?> <?= e($c['currency']) ?></td>
                <td><strong><?= number_format((float)$c['amount'], 2) ?> <?= e($c['currency']) ?></strong></td>
                <td><?= statusBadge($c['status']) ?></td>
                <td class="text-xs text-muted"><?= e($c['paid_at'] ?? '—') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
