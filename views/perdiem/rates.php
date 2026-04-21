<?php /** Phase 11 — Per Diem Rates */ ?>
<div class="card" style="margin-bottom:16px;">
    <h3 style="margin-top:0;">Add Rate</h3>
    <form method="POST" action="/per-diem/rates/add">
        <?= csrfField() ?>
        <div class="form-row">
            <div class="form-group"><label>Country *</label><input type="text" name="country" class="form-control" required></div>
            <div class="form-group"><label>Station</label><input type="text" name="station" class="form-control" placeholder="optional"></div>
            <div class="form-group"><label>Currency</label><input type="text" name="currency" class="form-control" value="USD"></div>
            <div class="form-group"><label>Daily rate</label><input type="number" step="0.01" name="daily_rate" class="form-control" required></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Effective from</label><input type="date" name="effective_from" class="form-control" value="<?= date('Y-m-d') ?>"></div>
            <div class="form-group"><label>Effective to</label><input type="date" name="effective_to" class="form-control"></div>
        </div>
        <button class="btn btn-primary btn-sm" type="submit">Add Rate</button>
    </form>
</div>

<?php if (empty($rates)): ?>
    <div class="card"><div class="empty-state" style="padding:40px 0;"><div class="icon">💱</div><h3>No rates yet</h3></div></div>
<?php else: ?>
<div class="card">
    <table class="table">
        <thead><tr><th>Country</th><th>Station</th><th>Currency</th><th>Daily rate</th><th>From</th><th>To</th></tr></thead>
        <tbody>
        <?php foreach ($rates as $r): ?>
            <tr>
                <td><?= e($r['country']) ?></td>
                <td class="text-sm"><?= e($r['station'] ?? '—') ?></td>
                <td class="text-sm"><?= e($r['currency']) ?></td>
                <td><strong><?= number_format((float)$r['daily_rate'], 2) ?></strong></td>
                <td class="text-xs"><?= e($r['effective_from']) ?></td>
                <td class="text-xs"><?= e($r['effective_to'] ?? '—') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
