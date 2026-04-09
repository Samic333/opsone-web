<?php
/**
 * FDM upload form — CSV file OR manual event entry
 */
$eventTypes = FdmModel::eventTypes();
$severities = FdmModel::severities();
?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start;">

<!-- CSV Upload -->
<div class="card">
    <div class="card-header"><div class="card-title">Upload FDM CSV File</div></div>
    <form method="POST" action="/fdm/store" enctype="multipart/form-data">
        <?= csrfField() ?>

        <div class="form-group">
            <label class="form-label">FDM/FOQA Data File (CSV) <span style="color:var(--accent-red)">*</span></label>
            <input type="file" name="fdm_file" class="form-control" accept=".csv,.txt" required>
            <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">Max 10 MB. CSV columns: flight_date, aircraft_reg, flight_number, event_type, severity, flight_phase, parameter, value_recorded, threshold, notes</div>
        </div>
        <div class="form-group">
            <label class="form-label">Flight Date</label>
            <input type="date" name="flight_date" class="form-control">
        </div>
        <div class="form-group">
            <label class="form-label">Aircraft Registration</label>
            <input type="text" name="aircraft_reg" class="form-control" placeholder="e.g. 5Y-KQX" maxlength="20">
        </div>
        <div class="form-group">
            <label class="form-label">Flight Number</label>
            <input type="text" name="flight_number" class="form-control" placeholder="e.g. KQ101" maxlength="20">
        </div>
        <div class="form-group">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes about this upload"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Upload &amp; Import</button>
        <a href="/fdm" class="btn btn-outline" style="margin-left:8px;">Cancel</a>
    </form>
</div>

<!-- Manual Event Entry -->
<div class="card">
    <div class="card-header"><div class="card-title">Log Manual Event</div></div>
    <form method="POST" action="/fdm/store">
        <?= csrfField() ?>
        <!-- No file input means manual path -->

        <div class="form-group">
            <label class="form-label">Event Type <span style="color:var(--accent-red)">*</span></label>
            <select name="event_type" class="form-control" required>
                <?php foreach ($eventTypes as $key => $et): ?>
                <option value="<?= $key ?>"><?= $et['icon'] ?> <?= $et['label'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Severity <span style="color:var(--accent-red)">*</span></label>
            <select name="severity" class="form-control" required>
                <?php foreach ($severities as $key => $sv): ?>
                <option value="<?= $key ?>"><?= ucfirst($key) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Flight Date</label>
            <input type="date" name="flight_date" class="form-control" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Aircraft Registration</label>
            <input type="text" name="aircraft_reg" class="form-control" placeholder="e.g. 5Y-KQX" maxlength="20">
        </div>
        <div class="form-group">
            <label class="form-label">Flight Number</label>
            <input type="text" name="flight_number" class="form-control" placeholder="e.g. KQ101" maxlength="20">
        </div>
        <div class="form-group">
            <label class="form-label">Flight Phase</label>
            <input type="text" name="flight_phase" class="form-control" placeholder="e.g. landing, approach, climb" maxlength="50">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="form-group">
                <label class="form-label">Parameter</label>
                <input type="text" name="parameter" class="form-control" placeholder="e.g. vertical_g" maxlength="100">
            </div>
            <div class="form-group">
                <label class="form-label">Value Recorded</label>
                <input type="number" step="0.001" name="value_recorded" class="form-control" placeholder="e.g. 2.4">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Threshold</label>
            <input type="number" step="0.001" name="threshold" class="form-control" placeholder="e.g. 2.0">
        </div>
        <div class="form-group">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Additional details"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Log Event</button>
        <a href="/fdm" class="btn btn-outline" style="margin-left:8px;">Cancel</a>
    </form>
</div>

</div>

<!-- CSV format helper -->
<div class="card" style="margin-top:0;">
    <div class="card-header"><div class="card-title">CSV Format Reference</div></div>
    <div style="font-size:12px;padding:0 0 4px;">
        <p style="margin-bottom:8px;color:var(--text-muted);">Your CSV file must include a header row. All columns are optional except <code>event_type</code>.</p>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Column</th><th>Values</th><th>Example</th></tr></thead>
                <tbody>
                    <tr><td><code>flight_date</code></td><td>YYYY-MM-DD</td><td>2026-04-10</td></tr>
                    <tr><td><code>aircraft_reg</code></td><td>Free text</td><td>5Y-KQX</td></tr>
                    <tr><td><code>flight_number</code></td><td>Free text</td><td>KQ101</td></tr>
                    <tr><td><code>event_type</code></td><td>exceedance, hard_landing, unstabilised_approach, gpws, tcas, overspeed, tail_strike, windshear, other</td><td>hard_landing</td></tr>
                    <tr><td><code>severity</code></td><td>low, medium, high, critical</td><td>high</td></tr>
                    <tr><td><code>flight_phase</code></td><td>Free text</td><td>landing</td></tr>
                    <tr><td><code>parameter</code></td><td>Free text</td><td>vertical_g</td></tr>
                    <tr><td><code>value_recorded</code></td><td>Decimal</td><td>2.45</td></tr>
                    <tr><td><code>threshold</code></td><td>Decimal</td><td>2.00</td></tr>
                    <tr><td><code>notes</code></td><td>Free text</td><td>Crew reported firm touchdown</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
