<?php /** Phase 11 — Submit per diem claim */ ?>
<div class="card" style="max-width:680px;">
    <form method="POST" action="/my-per-diem/submit">
        <?= csrfField() ?>
        <div class="form-row">
            <div class="form-group"><label>Period from *</label><input type="date" name="period_from" class="form-control" required></div>
            <div class="form-group"><label>Period to *</label><input type="date" name="period_to" class="form-control" required></div>
            <div class="form-group"><label>Days *</label><input type="number" step="0.5" name="days" class="form-control" required></div>
        </div>
        <div class="form-group"><label>Station</label><input type="text" name="station" class="form-control" placeholder="e.g. Nairobi"></div>
        <div class="form-group"><label>Rate (pre-configured)</label>
            <select name="rate_id" class="form-control">
                <option value="">— Use custom below —</option>
                <?php foreach ($rates as $r): ?>
                    <option value="<?= (int)$r['id'] ?>">
                        <?= e($r['country']) ?><?= $r['station'] ? ' / ' . e($r['station']) : '' ?>
                        — <?= number_format((float)$r['daily_rate'], 2) ?> <?= e($r['currency']) ?>/day
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Country (custom)</label><input type="text" name="country" class="form-control"></div>
            <div class="form-group"><label>Currency</label><input type="text" name="currency" class="form-control" value="USD"></div>
            <div class="form-group"><label>Daily rate</label><input type="number" step="0.01" name="rate" class="form-control"></div>
        </div>
        <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
        <div style="display:flex; gap:12px;">
            <button class="btn btn-primary" type="submit">Submit Claim</button>
            <a href="/my-per-diem" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
