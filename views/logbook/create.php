<?php /** Phase 7 — New logbook entry */ ?>
<div class="card" style="max-width: 820px;">
    <form method="POST" action="/my-logbook/store">
        <?= csrfField() ?>

        <div class="form-row">
            <div class="form-group"><label>Date *</label>
                <input type="date" name="flight_date" class="form-control" required value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group"><label>Flight #</label>
                <input type="text" name="flight_number" class="form-control" placeholder="e.g. MZ-224">
            </div>
            <div class="form-group"><label>Aircraft</label>
                <select name="aircraft_id" class="form-control">
                    <option value="">— Select (or fill manually below) —</option>
                    <?php foreach ($aircraft as $a): ?>
                        <option value="<?= (int)$a['id'] ?>">
                            <?= e($a['registration']) ?> — <?= e($a['aircraft_type']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group"><label>A/C Type</label><input type="text" name="aircraft_type" class="form-control"></div>
            <div class="form-group"><label>Registration</label><input type="text" name="registration" class="form-control"></div>
        </div>

        <div class="form-row">
            <div class="form-group"><label>Departure</label><input type="text" name="departure" class="form-control" placeholder="HKJK" maxlength="10"></div>
            <div class="form-group"><label>Arrival</label><input type="text" name="arrival" class="form-control" placeholder="HUEN" maxlength="10"></div>
        </div>

        <div class="form-row">
            <div class="form-group"><label>Off blocks</label><input type="time" name="off_blocks" class="form-control"></div>
            <div class="form-group"><label>Takeoff</label><input type="time" name="takeoff" class="form-control"></div>
            <div class="form-group"><label>Landing</label><input type="time" name="landing" class="form-control"></div>
            <div class="form-group"><label>On blocks</label><input type="time" name="on_blocks" class="form-control"></div>
        </div>

        <div class="form-row">
            <div class="form-group"><label>Day (min)</label><input type="number" name="day_minutes" class="form-control"></div>
            <div class="form-group"><label>Night (min)</label><input type="number" name="night_minutes" class="form-control"></div>
            <div class="form-group"><label>IFR (min)</label><input type="number" name="ifr_minutes" class="form-control"></div>
        </div>

        <div class="form-row">
            <div class="form-group"><label>PIC (min)</label><input type="number" name="pic_minutes" class="form-control"></div>
            <div class="form-group"><label>SIC (min)</label><input type="number" name="sic_minutes" class="form-control"></div>
            <div class="form-group"><label>Rules</label>
                <select name="rules" class="form-control"><option>IFR</option><option>VFR</option><option value="MIXED">Mixed</option></select>
            </div>
            <div class="form-group"><label>Role</label>
                <select name="role" class="form-control"><option>PIC</option><option>SIC</option><option>DUAL</option><option value="INSTRUCTOR">Instr.</option></select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group"><label>Landings day</label><input type="number" name="landings_day" class="form-control" value="1" min="0"></div>
            <div class="form-group"><label>Landings night</label><input type="number" name="landings_night" class="form-control" value="0" min="0"></div>
        </div>

        <div class="form-group"><label>Remarks</label>
            <textarea name="remarks" class="form-control" rows="2"></textarea>
        </div>

        <div style="display:flex; gap:12px;">
            <button type="submit" class="btn btn-primary">Save Entry</button>
            <a href="/my-logbook" class="btn btn-outline">Cancel</a>
        </div>
        <p class="text-xs text-muted" style="margin-top:8px;">Block and airborne times are computed automatically from Off/On and Takeoff/Landing.</p>
    </form>
</div>
