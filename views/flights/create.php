<?php /** Phase 9 — New flight */ ?>
<div class="card" style="max-width:780px;">
    <form method="POST" action="/flights/store">
        <?= csrfField() ?>
        <div class="form-row">
            <div class="form-group"><label>Date *</label><input type="date" name="flight_date" class="form-control" required value="<?= date('Y-m-d') ?>"></div>
            <div class="form-group"><label>Flight # *</label><input type="text" name="flight_number" class="form-control" placeholder="MZ-224" required></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Departure</label><input type="text" name="departure" class="form-control" maxlength="10" placeholder="HKJK"></div>
            <div class="form-group"><label>Arrival</label><input type="text" name="arrival" class="form-control" maxlength="10" placeholder="HUEN"></div>
            <div class="form-group"><label>STD</label><input type="time" name="std" class="form-control"></div>
            <div class="form-group"><label>STA</label><input type="time" name="sta" class="form-control"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Aircraft</label>
                <select name="aircraft_id" class="form-control">
                    <option value="">— None —</option>
                    <?php foreach ($aircraft as $a): ?>
                        <option value="<?= (int)$a['id'] ?>"><?= e($a['registration']) ?> · <?= e($a['aircraft_type']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Captain</label>
                <select name="captain_id" class="form-control">
                    <option value="">— None —</option>
                    <?php foreach ($pilots as $p): ?>
                        <option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>First Officer</label>
                <select name="fo_id" class="form-control">
                    <option value="">— None —</option>
                    <?php foreach ($pilots as $p): ?>
                        <option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Status</label>
                <select name="status" class="form-control"><option>draft</option><option>published</option></select>
            </div>
        </div>
        <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>

        <div style="display:flex; gap:12px;">
            <button class="btn btn-primary" type="submit">Save Flight</button>
            <a href="/flights" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
