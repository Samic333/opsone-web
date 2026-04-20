<?php
/**
 * OpsOne — Safety Report Form (Create / Edit Draft)
 * Variables: $reportType, $reportTypeLabel, $draft (array|null), $allowAnonymous (bool)
 */
$pageTitle    = $reportTypeLabel ?? ucwords(str_replace('_', ' ', $reportType ?? 'Safety Report'));
$pageSubtitle = 'All reports are treated confidentially';

// Report types that need each optional section
$needsAircraft  = in_array($reportType, ['flight_crew_occurrence', 'maintenance_engineering', 'tcas', 'frat']);
$needsFlightCrew = in_array($reportType, ['flight_crew_occurrence', 'tcas', 'frat']);
$needsMaint     = $reportType === 'maintenance_engineering';
$needsFrat      = $reportType === 'frat';

// Helper — pre-fill from draft
$v = function(string $key, string $default = '') use ($draft): string {
    return e($draft[$key] ?? $default);
};
?>

<div style="margin-bottom:16px;">
    <a href="/safety" class="btn btn-ghost btn-sm">← Back</a>
</div>

<form method="POST" action="/safety/report/submit" enctype="multipart/form-data" id="safetyReportForm">
    <?= csrfField() ?>
    <input type="hidden" name="report_type" value="<?= e($reportType ?? '') ?>">
    <?php if (!empty($draft['id'])): ?>
        <input type="hidden" name="draft_id" value="<?= (int)$draft['id'] ?>">
    <?php endif; ?>

    <!-- ═══════════════════════════════
         SECTION A — Base Fields (All)
         ═══════════════════════════════ -->
    <div class="form-section card" style="padding:24px; margin-bottom:20px;">
        <h4 style="margin:0 0 18px; font-size:15px; font-weight:700; color:var(--text-primary); padding-bottom:12px; border-bottom:1px solid var(--border);">
            Section A — Event Details
        </h4>

        <div class="form-group">
            <label>Report Title <span style="color:#ef4444;">*</span></label>
            <input type="text" name="title" class="form-control" required
                   value="<?= $v('title') ?>"
                   placeholder="Brief descriptive title of the event">
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
            <div class="form-group">
                <label>Date of Event <span style="color:#ef4444;">*</span></label>
                <input type="date" name="event_date" class="form-control" required value="<?= $v('event_date') ?>">
            </div>
            <div class="form-group">
                <label>UTC Time</label>
                <input type="time" name="event_time_utc" class="form-control" value="<?= $v('event_time_utc') ?>">
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
            <div class="form-group">
                <label>Local Time</label>
                <input type="time" name="event_time_local" class="form-control" value="<?= $v('event_time_local') ?>">
            </div>
            <div class="form-group">
                <label>ICAO Code</label>
                <input type="text" name="icao_code" class="form-control"
                       maxlength="4" placeholder="e.g. EGLL"
                       style="text-transform:uppercase;"
                       value="<?= $v('icao_code') ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Location / Airport</label>
            <input type="text" name="location" class="form-control"
                   placeholder="Airport name, runway, stand, taxiway, etc."
                   value="<?= $v('location') ?>">
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
            <div class="form-group">
                <label>Occurrence Type</label>
                <div style="display:flex; gap:16px; margin-top:8px;">
                    <label style="display:flex; align-items:center; gap:6px; cursor:pointer; font-weight:400;">
                        <input type="radio" name="occurrence_type" value="occurrence"
                               <?= ($draft['occurrence_type'] ?? 'occurrence') === 'occurrence' ? 'checked' : '' ?>>
                        Occurrence
                    </label>
                    <label style="display:flex; align-items:center; gap:6px; cursor:pointer; font-weight:400;">
                        <input type="radio" name="occurrence_type" value="hazard"
                               <?= ($draft['occurrence_type'] ?? '') === 'hazard' ? 'checked' : '' ?>>
                        Hazard
                    </label>
                </div>
            </div>
            <div class="form-group">
                <label>Event Type</label>
                <select name="event_type" class="form-control">
                    <option value="">— Select —</option>
                    <?php
                    $eventTypes = [
                        'Air / Airspace Incident', 'Ground Incident', 'Personnel Injury',
                        'Aircraft Damage', 'Security', 'Environmental',
                        'Technical/Mechanical', 'Wildlife Strike', 'Other'
                    ];
                    foreach ($eventTypes as $et):
                    ?>
                    <option value="<?= e($et) ?>" <?= ($draft['event_type'] ?? '') === $et ? 'selected' : '' ?>><?= e($et) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Risk Assessment 1-5 -->
        <div class="form-group">
            <label>Initial Risk Assessment</label>
            <div style="display:flex; gap:8px; margin-top:8px; flex-wrap:wrap;">
                <?php
                $riskLevels = [
                    1 => ['label' => '1 – Very Low',  'color' => '#10b981'],
                    2 => ['label' => '2 – Low',        'color' => '#3b82f6'],
                    3 => ['label' => '3 – Moderate',   'color' => '#f59e0b'],
                    4 => ['label' => '4 – High',       'color' => '#f97316'],
                    5 => ['label' => '5 – Critical',   'color' => '#ef4444'],
                ];
                $currentRisk = (int)($draft['initial_risk'] ?? 0);
                foreach ($riskLevels as $val => $info):
                ?>
                <label style="cursor:pointer;">
                    <input type="radio" name="initial_risk" value="<?= $val ?>"
                           <?= $currentRisk === $val ? 'checked' : '' ?>
                           style="display:none;" class="risk-radio" data-color="<?= $info['color'] ?>">
                    <span class="risk-pill" style="
                        display:inline-block; padding:6px 14px; border-radius:20px;
                        font-size:12px; font-weight:600; cursor:pointer;
                        border:2px solid <?= $info['color'] ?>;
                        color:<?= $currentRisk === $val ? '#fff' : $info['color'] ?>;
                        background:<?= $currentRisk === $val ? $info['color'] : 'transparent' ?>;
                        transition:all 0.15s;">
                        <?= $info['label'] ?>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-group">
            <label>Narrative / Description <span style="color:#ef4444;">*</span></label>
            <textarea name="description" class="form-control" required rows="6"
                      style="min-height:120px;"
                      placeholder="Describe the event in detail — what happened, when, where, and how. Include contributing factors."><?= $v('description') ?></textarea>
        </div>

        <div class="form-group">
            <label>Reporter Position / Job Title</label>
            <input type="text" name="reporter_position" class="form-control"
                   placeholder="e.g. First Officer, Line Maintenance Engineer"
                   value="<?= $v('reporter_position') ?>">
        </div>

        <?php if (!empty($allowAnonymous)): ?>
        <div class="form-group">
            <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-weight:400;">
                <input type="checkbox" name="is_anonymous" value="1"
                       <?= !empty($draft['is_anonymous']) ? 'checked' : '' ?>
                       style="width:16px; height:16px;">
                <span>Submit anonymously — your name will not be recorded</span>
            </label>
            <p class="text-xs text-muted" style="margin:6px 0 0 26px;">
                Anonymous reports are still treated with the same priority. You will not receive status updates.
            </p>
        </div>
        <?php endif; ?>
    </div>

    <!-- ═══════════════════════════════════════════════════
         SECTION B — Aircraft Information (conditional)
         ═══════════════════════════════════════════════════ -->
    <?php if ($needsAircraft): ?>
    <details class="card" style="padding:0; margin-bottom:20px;" open>
        <summary style="padding:18px 24px; cursor:pointer; font-size:15px; font-weight:700; color:var(--text-primary); list-style:none; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border);">
            <span>Section B — Aircraft Information</span>
            <span style="font-size:12px; color:var(--text-muted); font-weight:400;">▾</span>
        </summary>
        <div style="padding:20px 24px;">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label>Aircraft Registration</label>
                    <input type="text" name="aircraft_reg" class="form-control"
                           placeholder="e.g. G-ABCD" value="<?= $v('aircraft_reg') ?>">
                </div>
                <div class="form-group">
                    <label>Call Sign</label>
                    <input type="text" name="call_sign" class="form-control"
                           placeholder="e.g. BAW123" value="<?= $v('call_sign') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Phase of Flight</label>
                <select name="phase_of_flight" class="form-control">
                    <option value="">— Select —</option>
                    <?php foreach (['Pre-flight','Pushback','Taxi','Takeoff','Climb','Cruise','Descent','Approach','Landing','Parking','Maintenance'] as $phase): ?>
                        <option value="<?= e($phase) ?>" <?= ($draft['phase_of_flight'] ?? '') === $phase ? 'selected' : '' ?>><?= e($phase) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </details>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════
         SECTION C — Flight Crew (conditional)
         ═══════════════════════════════════════════════════ -->
    <?php if ($needsFlightCrew): ?>
    <details class="card" style="padding:0; margin-bottom:20px;">
        <summary style="padding:18px 24px; cursor:pointer; font-size:15px; font-weight:700; color:var(--text-primary); list-style:none; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border);">
            <span>Section C — Flight Crew Details</span>
            <span style="font-size:12px; color:var(--text-muted); font-weight:400;">▾ Click to expand</span>
        </summary>
        <div style="padding:20px 24px;">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label>Captain Name</label>
                    <input type="text" name="captain_name" class="form-control" value="<?= $v('captain_name') ?>">
                </div>
                <div class="form-group">
                    <label>First Officer Name</label>
                    <input type="text" name="fo_name" class="form-control" value="<?= $v('fo_name') ?>">
                </div>
                <div class="form-group">
                    <label>Captain's Role</label>
                    <input type="text" name="captain_role" class="form-control"
                           placeholder="e.g. Pilot Flying" value="<?= $v('captain_role') ?>">
                </div>
                <div class="form-group">
                    <label>First Officer's Role</label>
                    <input type="text" name="fo_role" class="form-control"
                           placeholder="e.g. Pilot Monitoring" value="<?= $v('fo_role') ?>">
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label>Cabin Manager</label>
                    <input type="text" name="cabin_manager" class="form-control" value="<?= $v('cabin_manager') ?>">
                </div>
                <div class="form-group">
                    <label>Number of Flight Attendants</label>
                    <input type="number" name="fa_count" class="form-control" min="0" value="<?= $v('fa_count') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Aircraft Engineer on Duty</label>
                <input type="text" name="aircraft_engineer" class="form-control" value="<?= $v('aircraft_engineer') ?>">
            </div>
        </div>
    </details>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════
         SECTION D — Maintenance Engineering (conditional)
         ═══════════════════════════════════════════════════ -->
    <?php if ($needsMaint): ?>
    <details class="card" style="padding:0; margin-bottom:20px;">
        <summary style="padding:18px 24px; cursor:pointer; font-size:15px; font-weight:700; color:var(--text-primary); list-style:none; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border);">
            <span>Section D — Maintenance / Engineering</span>
            <span style="font-size:12px; color:var(--text-muted); font-weight:400;">▾ Click to expand</span>
        </summary>
        <div style="padding:20px 24px;">
            <div class="form-group">
                <label>Engineering Finding</label>
                <textarea name="engineering_finding" class="form-control" rows="4"><?= $v('engineering_finding') ?></textarea>
            </div>
            <div class="form-group">
                <label>Recommendations</label>
                <textarea name="recommendations" class="form-control" rows="3"><?= $v('recommendations') ?></textarea>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label>Part Number On</label>
                    <input type="text" name="part_number_on" class="form-control" value="<?= $v('part_number_on') ?>">
                </div>
                <div class="form-group">
                    <label>Part Number Off</label>
                    <input type="text" name="part_number_off" class="form-control" value="<?= $v('part_number_off') ?>">
                </div>
                <div class="form-group">
                    <label>Serial Number On</label>
                    <input type="text" name="serial_number_on" class="form-control" value="<?= $v('serial_number_on') ?>">
                </div>
                <div class="form-group">
                    <label>Serial Number Off</label>
                    <input type="text" name="serial_number_off" class="form-control" value="<?= $v('serial_number_off') ?>">
                </div>
                <div class="form-group">
                    <label>Batch Number</label>
                    <input type="text" name="batch_number" class="form-control" value="<?= $v('batch_number') ?>">
                </div>
                <div class="form-group">
                    <label>Defect Sheet Number</label>
                    <input type="text" name="defect_sheet_number" class="form-control" value="<?= $v('defect_sheet_number') ?>">
                </div>
            </div>
        </div>
    </details>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════
         SECTION E — FRAT (conditional)
         ═══════════════════════════════════════════════════ -->
    <?php if ($needsFrat): ?>
    <details class="card" style="padding:0; margin-bottom:20px;">
        <summary style="padding:18px 24px; cursor:pointer; font-size:15px; font-weight:700; color:var(--text-primary); list-style:none; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border);">
            <span>Section E — FRAT Pre-Flight Risk Assessment</span>
            <span style="font-size:12px; color:var(--text-muted); font-weight:400;">▾ Click to expand</span>
        </summary>
        <div style="padding:20px 24px;">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label>Departure Station</label>
                    <input type="text" name="frat_dep_station" class="form-control" value="<?= $v('frat_dep_station') ?>">
                </div>
                <div class="form-group">
                    <label>Destination Station</label>
                    <input type="text" name="frat_dest_station" class="form-control" value="<?= $v('frat_dest_station') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Planned Departure Time</label>
                <input type="time" name="frat_planned_dep" class="form-control" value="<?= $v('frat_planned_dep') ?>">
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label>Crew Rest</label>
                    <select name="frat_crew_rest" class="form-control">
                        <option value="">— Select —</option>
                        <?php foreach (['Adequate','Marginal','Inadequate'] as $opt): ?>
                            <option value="<?= e($opt) ?>" <?= ($draft['frat_crew_rest'] ?? '') === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Crew Fatigue Level</label>
                    <select name="frat_fatigue" class="form-control">
                        <option value="">— Select —</option>
                        <?php foreach (['None','Low','Moderate','High'] as $opt): ?>
                            <option value="<?= e($opt) ?>" <?= ($draft['frat_fatigue'] ?? '') === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Weather Concern</label>
                <select name="frat_weather" class="form-control">
                    <option value="">— Select —</option>
                    <?php foreach (['None','Moderate','Significant','Severe'] as $opt): ?>
                        <option value="<?= e($opt) ?>" <?= ($draft['frat_weather'] ?? '') === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Additional Risk Factors</label>
                <textarea name="frat_additional_risks" class="form-control" rows="3"
                          placeholder="Any other risk factors relevant to this flight..."><?= $v('frat_additional_risks') ?></textarea>
            </div>
        </div>
    </details>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════
         SECTION F — Attachments
         ═══════════════════════════════════════════════════ -->
    <details class="card" style="padding:0; margin-bottom:80px;">
        <summary style="padding:18px 24px; cursor:pointer; font-size:15px; font-weight:700; color:var(--text-primary); list-style:none; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border);">
            <span>Section F — Attachments (Optional)</span>
            <span style="font-size:12px; color:var(--text-muted); font-weight:400;">▾ Click to expand</span>
        </summary>
        <div style="padding:20px 24px;">
            <div class="form-group">
                <label>Upload Files</label>
                <input type="file" name="attachments[]" class="form-control" multiple
                       accept="image/*,application/pdf,video/mp4,video/quicktime">
                <p class="text-xs text-muted" style="margin-top:6px;">
                    Max 25MB per file. Images, PDFs, and videos accepted.
                </p>
            </div>
        </div>
    </details>

    <!-- ═══════════════════════════════════════════════════
         Bottom Action Bar (sticky)
         ═══════════════════════════════════════════════════ -->
    <div style="
        position:fixed; bottom:0; left:0; right:0;
        background:var(--bg-card); border-top:1px solid var(--border);
        padding:14px 24px; display:flex; gap:12px; justify-content:flex-end;
        z-index:100; box-shadow:0 -2px 10px rgba(0,0,0,0.08);">
        <span class="text-sm text-muted" style="display:flex; align-items:center; margin-right:auto;">
            🔒 Securely encrypted
        </span>
        <button type="submit" form="safetyReportForm" formaction="/safety/report/draft"
                class="btn btn-outline">
            💾 Save as Draft
        </button>
        <button type="submit" form="safetyReportForm"
                class="btn btn-primary"
                onclick="return confirm('Are you sure you want to submit this report? Once submitted it will be sent to the Safety Team.')">
            Submit Report
        </button>
    </div>
</form>

<script>
// Risk pill toggle styling
document.querySelectorAll('.risk-radio').forEach(function(radio) {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.risk-radio').forEach(function(r) {
            var pill = r.nextElementSibling;
            var color = r.dataset.color;
            if (r.checked) {
                pill.style.background = color;
                pill.style.color = '#fff';
            } else {
                pill.style.background = 'transparent';
                pill.style.color = color;
            }
        });
    });
});
// ICAO uppercase
var icaoInput = document.querySelector('input[name="icao_code"]');
if (icaoInput) {
    icaoInput.addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
}
</script>
