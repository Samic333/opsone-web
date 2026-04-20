<?php
/**
 * OpsOne — Safety Report Form (Create / Edit Draft)
 * Variables: $reportType (slug), $reportTypeLabel, $settings (array),
 *            $draft (array|null), $prefill (array), $user (array)
 */

// Conditional section flags
$needsAircraft   = in_array($reportType, ['flight_crew_occurrence', 'maintenance_engineering', 'tcas', 'frat']);
$needsFlightCrew = in_array($reportType, ['flight_crew_occurrence', 'tcas', 'frat']);
$needsMaint      = ($reportType === 'maintenance_engineering');
$needsFrat       = ($reportType === 'frat');

$typeIcons = [
    'general_hazard'          => '⚠️',
    'flight_crew_occurrence'  => '✈️',
    'maintenance_engineering' => '🔧',
    'ground_ops'              => '🚧',
    'quality'                 => '✅',
    'hse'                     => '🦺',
    'tcas'                    => '📡',
    'environmental'           => '🌿',
    'frat'                    => '📋',
];
$typeIcon = $typeIcons[$reportType ?? ''] ?? '📄';

// Value helpers — draft takes priority over prefill
$dv = function(string $key, string $default = '') use ($draft, $prefill): string {
    if (isset($draft[$key]) && $draft[$key] !== '') return e($draft[$key]);
    if (isset($prefill[$key]) && $prefill[$key] !== '') return e($prefill[$key]);
    return $default;
};
$pv = function(string $key) use ($prefill): string {
    return e($prefill[$key] ?? '');
};

$hasDraft          = !empty($draft['id']);
$allowAnonymous    = !empty($settings['allow_anonymous']);
$hasFlightContext  = !empty($prefill['has_flight_context']);

// Risk matrix data
$likelihoods = ['A' => 'Frequent', 'B' => 'Occasional', 'C' => 'Remote', 'D' => 'Improbable', 'E' => 'Ext. Improbable'];
$severities  = [5 => 'Catastrophic', 4 => 'Hazardous', 3 => 'Major', 2 => 'Minor', 1 => 'Negligible'];
// Risk level colour by combined index (severity * 10 + likelihood_index)
// Intolerable: high severity + high/med likelihood → red
// Tolerable: medium → amber  Acceptable: low → green
$riskColor = function(int $sev, string $lik): string {
    $likIdx = array_search($lik, array_keys(['A'=>0,'B'=>1,'C'=>2,'D'=>3,'E'=>4]));
    $likIdx = ['A'=>0,'B'=>1,'C'=>2,'D'=>3,'E'=>4][$lik];
    if ($sev >= 4 && $likIdx <= 1) return '#dc2626'; // intolerable
    if ($sev >= 4 && $likIdx === 2) return '#dc2626';
    if ($sev === 3 && $likIdx <= 1) return '#dc2626';
    if ($sev >= 3 && $likIdx <= 2) return '#f59e0b'; // tolerable
    if ($sev === 2 && $likIdx <= 1) return '#f59e0b';
    return '#10b981'; // acceptable
};
$riskLabel = function(int $sev, string $lik): string {
    $likIdx = ['A'=>0,'B'=>1,'C'=>2,'D'=>3,'E'=>4][$lik];
    if ($sev >= 4 && $likIdx <= 2) return 'Intolerable';
    if ($sev >= 3 && $likIdx <= 2) return 'Tolerable';
    if ($sev === 2 && $likIdx <= 1) return 'Tolerable';
    return 'Acceptable';
};

$selectedLik = $draft['risk_likelihood'] ?? '';
$selectedSev = $draft['risk_severity']   ?? '';
$selectedCode = $draft['initial_risk_code'] ?? '';
?>

<!-- ═══════════════════════════════
     STICKY HEADER BAR
     ═══════════════════════════════ -->
<div style="
    position:sticky; top:0; z-index:90;
    background:var(--bg-card); border-bottom:1px solid var(--border);
    padding:12px 0 12px; margin:-24px -24px 24px;
    display:flex; align-items:center; gap:14px;
    padding-left:24px; padding-right:24px;
    box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <a href="/safety" style="color:var(--text-muted); text-decoration:none; font-size:20px; line-height:1; flex-shrink:0;">←</a>
    <span style="font-size:20px; line-height:1;"><?= $typeIcon ?></span>
    <span style="font-size:15px; font-weight:700; color:var(--text-primary); flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
        <?= e($reportTypeLabel ?? ucwords(str_replace('_', ' ', $reportType ?? ''))) ?>
    </span>
    <?php if ($hasDraft): ?>
    <span style="display:flex; align-items:center; gap:5px; font-size:12px; color:var(--text-muted); flex-shrink:0;">
        <span style="width:7px; height:7px; background:#f59e0b; border-radius:50%; display:inline-block;"></span>
        Draft — <?= e($draft['reference_no'] ?? 'unsaved') ?>
    </span>
    <?php endif; ?>
</div>

<form method="POST" action="/safety/report/submit" enctype="multipart/form-data" id="safetyReportForm">
    <?= csrfField() ?>
    <input type="hidden" name="report_type" value="<?= e($reportType ?? '') ?>">
    <?php if ($hasDraft): ?>
        <input type="hidden" name="draft_id" value="<?= (int)$draft['id'] ?>">
    <?php endif; ?>
    <input type="hidden" name="risk_likelihood"   id="hiddenLikelihood" value="<?= e($selectedLik) ?>">
    <input type="hidden" name="risk_severity"     id="hiddenSeverity"   value="<?= e($selectedSev) ?>">
    <input type="hidden" name="initial_risk_code" id="hiddenRiskCode"   value="<?= e($selectedCode) ?>">

    <!-- ═══════════════════════════════════════════════════
         SECTION 1 — Reporter Context (auto-filled, closed)
         ═══════════════════════════════════════════════════ -->
    <details class="card" style="padding:0; margin-bottom:16px;">
        <summary style="
            padding:16px 20px; cursor:pointer; list-style:none;
            display:flex; align-items:center; gap:10px;
            font-size:14px; font-weight:700; color:var(--text-primary);">
            <span>👤</span>
            <span style="flex:1;">Reported by:
                <span style="font-weight:400; color:var(--text-muted);">
                    <?= e($prefill['reporter_name'] ?? $user['name'] ?? '') ?>
                    <?php if (!empty($prefill['reporter_position'])): ?>
                        &nbsp;·&nbsp;<?= e($prefill['reporter_position']) ?>
                    <?php endif; ?>
                    <?php if (!empty($prefill['reporter_base'])): ?>
                        &nbsp;·&nbsp;<?= e($prefill['reporter_base']) ?>
                    <?php endif; ?>
                </span>
            </span>
            <span style="font-size:11px; color:var(--text-muted); font-weight:400;">Auto-filled ▾</span>
        </summary>
        <div style="padding:20px; border-top:1px solid var(--border);">

            <!-- Reporter Name — read-only -->
            <div class="form-group" style="margin-bottom:14px;">
                <label style="font-size:12px; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:.04em;">Reporter Name</label>
                <div style="
                    padding:9px 12px; background:var(--bg-secondary,#f9fafb);
                    border:1px solid var(--border); border-radius:var(--radius-sm);
                    font-size:14px; color:var(--text-primary); display:flex; align-items:center; justify-content:space-between;">
                    <span><?= e($prefill['reporter_name'] ?? $user['name'] ?? '—') ?></span>
                    <span style="font-size:11px; color:var(--text-muted);">Not you? Contact admin</span>
                </div>
                <input type="hidden" name="reporter_name" value="<?= e($prefill['reporter_name'] ?? $user['name'] ?? '') ?>">
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
                <!-- Employee ID — read-only -->
                <div class="form-group">
                    <label style="font-size:12px; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:.04em;">Employee ID</label>
                    <div style="padding:9px 12px; background:var(--bg-secondary,#f9fafb); border:1px solid var(--border); border-radius:var(--radius-sm); font-size:14px; color:var(--text-primary);">
                        <?= e($prefill['reporter_employee_id'] ?? '—') ?>
                    </div>
                    <input type="hidden" name="reporter_employee_id" value="<?= e($prefill['reporter_employee_id'] ?? '') ?>">
                </div>
                <!-- Department — read-only -->
                <div class="form-group">
                    <label style="font-size:12px; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:.04em;">Department</label>
                    <div style="padding:9px 12px; background:var(--bg-secondary,#f9fafb); border:1px solid var(--border); border-radius:var(--radius-sm); font-size:14px; color:var(--text-primary);">
                        <?= e($prefill['reporter_department'] ?? '—') ?>
                    </div>
                    <input type="hidden" name="reporter_department" value="<?= e($prefill['reporter_department'] ?? '') ?>">
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                <!-- Base — editable -->
                <div class="form-group">
                    <label style="font-size:12px; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:.04em;">Base / Station</label>
                    <input type="text" name="reporter_base" class="form-control"
                           value="<?= $dv('reporter_base') ?>"
                           placeholder="e.g. LHR">
                </div>
                <!-- Position — editable -->
                <div class="form-group">
                    <label style="font-size:12px; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:.04em;">Position / Role</label>
                    <input type="text" name="reporter_position" class="form-control"
                           value="<?= $dv('reporter_position') ?>"
                           placeholder="e.g. First Officer">
                </div>
            </div>

            <!-- Flight context link -->
            <div style="margin-top:16px; padding-top:14px; border-top:1px solid var(--border);">
                <label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer; font-weight:400; font-size:14px;">
                    <input type="checkbox" name="link_roster_context" id="linkRosterCtx" value="1"
                           <?= $hasFlightContext ? 'checked' : '' ?>
                           style="width:16px; height:16px; margin-top:2px; flex-shrink:0;">
                    <span>📋 Link this report to today's flight/assignment?</span>
                </label>
                <?php if ($hasFlightContext): ?>
                <p class="text-xs text-muted" style="margin:5px 0 0 26px;">
                    Flight duty detected on your roster today (<?= e(ucfirst($prefill['roster_duty_type'] ?? '')) ?>). Auto-checked.
                </p>
                <?php endif; ?>

                <!-- Roster sub-section, revealed when checked -->
                <div id="rosterContextBlock" style="<?= $hasFlightContext ? '' : 'display:none;' ?> margin-top:12px; padding:12px 14px; background:rgba(59,130,246,0.05); border:1px solid rgba(59,130,246,0.18); border-radius:var(--radius-sm);">
                    <p class="text-sm" style="margin:0 0 6px; font-weight:600; color:var(--text-primary);">Today's Roster Context</p>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <div>
                            <div class="text-xs text-muted">Duty Type</div>
                            <div class="text-sm" style="font-weight:600;"><?= e(ucfirst($prefill['roster_duty_type'] ?? '—')) ?></div>
                        </div>
                        <div>
                            <div class="text-xs text-muted">Notes</div>
                            <div class="text-sm"><?= e($prefill['roster_notes'] ?? '—') ?></div>
                        </div>
                    </div>
                    <p class="text-xs text-muted" style="margin:8px 0 0;">This flight context will be attached to the report.</p>
                    <input type="hidden" name="roster_duty_type" value="<?= e($prefill['roster_duty_type'] ?? '') ?>">
                    <input type="hidden" name="roster_notes"     value="<?= e($prefill['roster_notes'] ?? '') ?>">
                </div>
            </div>
        </div>
    </details>

    <!-- ═══════════════════════════════════════════════════
         SECTION 2 — Event Core (always open)
         ═══════════════════════════════════════════════════ -->
    <details class="card" style="padding:0; margin-bottom:16px;" open>
        <summary style="
            padding:16px 20px; cursor:pointer; list-style:none;
            display:flex; align-items:center; gap:10px;
            font-size:14px; font-weight:700; color:var(--text-primary);
            border-bottom:1px solid var(--border);">
            <span>⚡</span>
            <span>Event Details</span>
            <span style="margin-left:auto; font-size:11px; color:var(--text-muted); font-weight:400;">Required ▾</span>
        </summary>
        <div style="padding:20px;">

            <!-- Occurrence Type pill toggle -->
            <div class="form-group" style="margin-bottom:18px;">
                <label class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:.04em; font-weight:600;">Occurrence Type</label>
                <div style="display:flex; gap:0; margin-top:8px; border:1px solid var(--border); border-radius:var(--radius-sm); overflow:hidden; width:fit-content;">
                    <?php $occType = $draft['occurrence_type'] ?? 'occurrence'; ?>
                    <label style="cursor:pointer;">
                        <input type="radio" name="occurrence_type" value="occurrence"
                               <?= $occType === 'occurrence' ? 'checked' : '' ?>
                               style="display:none;" class="occ-radio">
                        <span class="occ-pill" data-val="occurrence" style="
                            display:block; padding:9px 22px; font-size:13px; font-weight:600;
                            background:<?= $occType === 'occurrence' ? 'var(--accent-blue,#3b82f6)' : 'transparent' ?>;
                            color:<?= $occType === 'occurrence' ? '#fff' : 'var(--text-primary)' ?>;
                            transition:all 0.15s; user-select:none;">
                            Occurrence
                        </span>
                    </label>
                    <label style="cursor:pointer; border-left:1px solid var(--border);">
                        <input type="radio" name="occurrence_type" value="hazard"
                               <?= $occType === 'hazard' ? 'checked' : '' ?>
                               style="display:none;" class="occ-radio">
                        <span class="occ-pill" data-val="hazard" style="
                            display:block; padding:9px 22px; font-size:13px; font-weight:600;
                            background:<?= $occType === 'hazard' ? 'var(--accent-blue,#3b82f6)' : 'transparent' ?>;
                            color:<?= $occType === 'hazard' ? '#fff' : 'var(--text-primary)' ?>;
                            transition:all 0.15s; user-select:none;">
                            Hazard
                        </span>
                    </label>
                </div>
            </div>

            <!-- Title -->
            <div class="form-group" style="margin-bottom:14px;">
                <label>Title of Event <span style="color:#ef4444;">*</span></label>
                <input type="text" name="title" class="form-control" required
                       value="<?= $dv('title') ?>"
                       placeholder="Brief description of what happened">
            </div>

            <!-- Date + Times -->
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; margin-bottom:14px;">
                <div class="form-group">
                    <label>Date <span style="color:#ef4444;">*</span></label>
                    <input type="date" name="event_date" class="form-control" required
                           value="<?= $dv('event_date', date('Y-m-d')) ?>">
                </div>
                <div class="form-group">
                    <label>UTC Time</label>
                    <input type="time" name="event_utc_time" class="form-control"
                           value="<?= $dv('event_utc_time') ?>">
                </div>
                <div class="form-group">
                    <label>Local Time</label>
                    <input type="time" name="event_local_time" class="form-control"
                           value="<?= $dv('event_local_time') ?>">
                </div>
            </div>
            <p class="text-xs text-muted" style="margin:-8px 0 14px;">Times auto-filled from your device clock. Adjust if reporting a past event.</p>

            <!-- Location + ICAO -->
            <div style="display:grid; grid-template-columns:2fr 1fr; gap:14px; margin-bottom:14px;">
                <div class="form-group">
                    <label>Location / Station</label>
                    <input type="text" name="location_name" class="form-control"
                           value="<?= $dv('location_name') ?>"
                           placeholder="Airport name, runway, stand, taxiway, etc.">
                </div>
                <div class="form-group">
                    <label>ICAO Code</label>
                    <input type="text" name="icao_code" id="icaoInput" class="form-control"
                           maxlength="4" placeholder="e.g. EGLL"
                           style="text-transform:uppercase; letter-spacing:.12em;"
                           value="<?= $dv('icao_code') ?>">
                </div>
            </div>

            <!-- Event Type -->
            <div class="form-group">
                <label>Event Type</label>
                <select name="event_type" class="form-control">
                    <option value="">— Select —</option>
                    <?php
                    $eventTypes = [
                        'air_airspace'    => 'Air / Airspace Incident',
                        'ground'          => 'Ground Incident',
                        'personnel_injury'=> 'Personnel Injury',
                        'aircraft_damage' => 'Aircraft Damage',
                        'security'        => 'Security',
                        'environmental'   => 'Environmental',
                        'technical'       => 'Technical / Mechanical',
                        'wildlife_strike' => 'Wildlife Strike',
                        'fuel'            => 'Fuel',
                        'atc'             => 'ATC',
                        'other'           => 'Other',
                    ];
                    $currentEvType = $draft['event_type'] ?? '';
                    foreach ($eventTypes as $etKey => $etLabel):
                    ?>
                    <option value="<?= e($etKey) ?>" <?= $currentEvType === $etKey ? 'selected' : '' ?>><?= e($etLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </details>

    <!-- ═══════════════════════════════════════════════════
         SECTION 3 — Narrative (always open)
         ═══════════════════════════════════════════════════ -->
    <details class="card" style="padding:0; margin-bottom:16px;" open>
        <summary style="
            padding:16px 20px; cursor:pointer; list-style:none;
            display:flex; align-items:center; gap:10px;
            font-size:14px; font-weight:700; color:var(--text-primary);
            border-bottom:1px solid var(--border);">
            <span>📝</span>
            <span>What happened?</span>
            <span style="margin-left:auto; font-size:11px; color:var(--text-muted); font-weight:400;">Required ▾</span>
        </summary>
        <div style="padding:20px;">
            <textarea name="description" id="narrativeTextarea" class="form-control" required rows="7"
                      style="min-height:140px; resize:vertical; line-height:1.65;"
                      placeholder="Describe the sequence of events, what you observed, and any contributing factors. Include as much detail as you can recall."><?= e($draft['description'] ?? '') ?></textarea>
            <div style="display:flex; justify-content:space-between; margin-top:6px;">
                <span class="text-xs text-muted">Minimum 50 characters recommended</span>
                <span class="text-xs text-muted" id="charCount">0 characters</span>
            </div>
        </div>
    </details>

    <!-- ═══════════════════════════════════════════════════
         SECTION 4 — Initial Risk Assessment (always open)
         ═══════════════════════════════════════════════════ -->
    <details class="card" style="padding:0; margin-bottom:16px;" open>
        <summary style="
            padding:16px 20px; cursor:pointer; list-style:none;
            display:flex; align-items:center; gap:10px;
            font-size:14px; font-weight:700; color:var(--text-primary);
            border-bottom:1px solid var(--border);">
            <span>⚠️</span>
            <span>Risk Assessment</span>
            <span style="margin-left:auto; font-size:11px; color:var(--text-muted); font-weight:400;">▾</span>
        </summary>
        <div style="padding:20px;">

            <!-- Risk matrix -->
            <div style="overflow-x:auto;">
                <table id="riskMatrix" style="border-collapse:separate; border-spacing:3px; margin-bottom:12px;">
                    <thead>
                        <tr>
                            <th style="width:80px; text-align:right; font-size:11px; color:var(--text-muted); padding-right:6px; font-weight:600; vertical-align:bottom;">
                                Likelihood →
                            </th>
                            <?php foreach ($likelihoods as $lik => $likLabel): ?>
                            <th style="text-align:center; font-size:11px; color:var(--text-muted); padding-bottom:4px; font-weight:600; min-width:68px;">
                                <?= $lik ?><br><span style="font-weight:400;"><?= $likLabel ?></span>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($severities as $sev => $sevLabel): ?>
                        <tr>
                            <td style="text-align:right; font-size:11px; color:var(--text-muted); padding-right:6px; white-space:nowrap;">
                                <strong><?= $sev ?></strong> <?= $sevLabel ?>
                            </td>
                            <?php foreach ($likelihoods as $lik => $likLabel):
                                $bg    = $riskColor($sev, $lik);
                                $lbl   = $riskLabel($sev, $lik);
                                $code  = $sev . $lik;
                                $isSelected = ($selectedLik === $lik && (string)$selectedSev === (string)$sev);
                            ?>
                            <td>
                                <div class="risk-cell" data-sev="<?= $sev ?>" data-lik="<?= $lik ?>" data-code="<?= $code ?>" data-label="<?= $lbl ?>" data-color="<?= $bg ?>"
                                     style="
                                         width:68px; height:40px; border-radius:6px;
                                         background:<?= $bg ?>; opacity:<?= $isSelected ? '1' : '0.5' ?>;
                                         cursor:pointer; display:flex; align-items:center; justify-content:center;
                                         font-size:12px; font-weight:700; color:#fff;
                                         outline:<?= $isSelected ? '3px solid #1e3a5f' : 'none' ?>;
                                         transition:opacity 0.15s, transform 0.1s;"
                                     onmouseover="this.style.opacity='0.85'; this.style.transform='scale(1.05)'"
                                     onmouseout="this.style.opacity='<?= $isSelected ? '1' : '0.5' ?>'; this.style.transform=''">
                                    <?= $code ?>
                                </div>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Selection result -->
            <div id="riskResult" style="
                padding:10px 14px; border-radius:var(--radius-sm);
                background:var(--bg-secondary,#f9fafb); border:1px solid var(--border);
                display:flex; align-items:center; gap:10px; margin-bottom:10px;
                <?= $selectedCode ? '' : 'opacity:0.5;' ?>">
                <span style="font-size:13px; color:var(--text-muted);">Risk Level:</span>
                <span id="riskBadge" style="
                    padding:3px 10px; border-radius:20px; font-size:12px; font-weight:700; color:#fff;
                    background:<?= $selectedCode && $selectedLik && $selectedSev ? $riskColor((int)$selectedSev, $selectedLik) : '#9ca3af' ?>;">
                    <?= $selectedCode ? ($riskLabel((int)$selectedSev, $selectedLik) . ' — ' . $selectedCode) : 'No selection' ?>
                </span>
            </div>

            <!-- Explanation toggle -->
            <details style="margin-top:4px;">
                <summary style="font-size:12px; color:var(--accent-blue,#3b82f6); cursor:pointer; list-style:none;">
                    ℹ️ What do these mean?
                </summary>
                <div style="margin-top:10px; padding:12px 14px; background:rgba(59,130,246,0.05); border-radius:var(--radius-sm); border:1px solid rgba(59,130,246,0.15);">
                    <p class="text-xs" style="margin:0 0 8px;"><strong>Likelihood</strong> — How often could this occur?
                        A=Frequent (likely to occur many times), B=Occasional (likely to occur sometimes), C=Remote (unlikely but possible),
                        D=Improbable (very unlikely), E=Extremely Improbable (almost inconceivable).</p>
                    <p class="text-xs" style="margin:0 0 8px;"><strong>Severity</strong> — What would the worst-case outcome be?
                        5=Catastrophic (multiple fatalities), 4=Hazardous (serious injury/damage), 3=Major (injury/significant damage),
                        2=Minor (minor injury/damage), 1=Negligible (little consequence).</p>
                    <p class="text-xs" style="margin:0;">
                        <strong style="color:#dc2626;">Intolerable</strong> — Unacceptable risk; immediate action required. &nbsp;
                        <strong style="color:#f59e0b;">Tolerable</strong> — Risk may be acceptable with mitigation measures. &nbsp;
                        <strong style="color:#10b981;">Acceptable</strong> — Risk is within acceptable bounds.
                    </p>
                </div>
            </details>
        </div>
    </details>

    <!-- ═══════════════════════════════════════════════════
         SECTION 5 — Aircraft (conditional)
         ═══════════════════════════════════════════════════ -->
    <?php if ($needsAircraft): ?>
    <details class="card" style="padding:0; margin-bottom:16px;">
        <summary style="
            padding:16px 20px; cursor:pointer; list-style:none;
            display:flex; align-items:center; gap:10px;
            font-size:14px; font-weight:700; color:var(--text-primary);">
            <span>✈️</span>
            <span>Aircraft &amp; Flight Details</span>
            <span style="margin-left:auto; font-size:11px; color:var(--text-muted); font-weight:400;">▾ Click to expand</span>
        </summary>
        <div style="padding:20px; border-top:1px solid var(--border);">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
                <div class="form-group">
                    <label>Aircraft Registration</label>
                    <input type="text" name="aircraft_registration" class="form-control"
                           style="text-transform:uppercase;"
                           placeholder="e.g. G-ABCD"
                           value="<?= $dv('aircraft_registration') ?>">
                </div>
                <div class="form-group">
                    <label>Call Sign</label>
                    <input type="text" name="call_sign" class="form-control"
                           style="text-transform:uppercase;"
                           placeholder="e.g. BAW123"
                           value="<?= $dv('call_sign') ?>">
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                <div class="form-group">
                    <label>Phase of Flight</label>
                    <select name="phase_of_flight" class="form-control">
                        <option value="">— Select —</option>
                        <?php foreach (['Pre-flight','Pushback','Taxi','Takeoff','Climb','Cruise','Descent','Approach','Landing','Parking','Maintenance'] as $phase): ?>
                            <option value="<?= e($phase) ?>" <?= ($draft['phase_of_flight'] ?? '') === $phase ? 'selected' : '' ?>><?= e($phase) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Fleet Type</label>
                    <input type="text" name="fleet_type" class="form-control"
                           placeholder="e.g. B737-800"
                           value="<?= $dv('fleet_type', $pv('reporter_fleet')) ?>">
                </div>
            </div>
        </div>
    </details>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════
         SECTION 6 — Flight Crew (conditional)
         ═══════════════════════════════════════════════════ -->
    <?php if ($needsFlightCrew): ?>
    <details class="card" style="padding:0; margin-bottom:16px;">
        <summary style="
            padding:16px 20px; cursor:pointer; list-style:none;
            display:flex; align-items:center; gap:10px;
            font-size:14px; font-weight:700; color:var(--text-primary);">
            <span>👨‍✈️</span>
            <span>Crew Details</span>
            <span style="margin-left:auto; font-size:11px; color:var(--text-muted); font-weight:400;">▾ Click to expand</span>
        </summary>
        <div style="padding:20px; border-top:1px solid var(--border);">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
                <div class="form-group">
                    <label>Captain Name</label>
                    <input type="text" name="captain_name" class="form-control" value="<?= $dv('captain_name') ?>">
                </div>
                <div class="form-group">
                    <label>First Officer Name</label>
                    <input type="text" name="fo_name" class="form-control" value="<?= $dv('fo_name') ?>">
                </div>
                <div class="form-group">
                    <label>Captain Role / Grade</label>
                    <input type="text" name="captain_role" class="form-control"
                           placeholder="e.g. Pilot Flying" value="<?= $dv('captain_role') ?>">
                </div>
                <div class="form-group">
                    <label>FO Role / Grade</label>
                    <input type="text" name="fo_role" class="form-control"
                           placeholder="e.g. Pilot Monitoring" value="<?= $dv('fo_role') ?>">
                </div>
                <div class="form-group">
                    <label>Cabin Manager Name</label>
                    <input type="text" name="cabin_manager" class="form-control" value="<?= $dv('cabin_manager') ?>">
                </div>
                <div class="form-group">
                    <label>Number of Cabin Crew</label>
                    <input type="number" name="cabin_crew_count" class="form-control" min="0" value="<?= $dv('cabin_crew_count') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Aircraft Engineer</label>
                <input type="text" name="aircraft_engineer" class="form-control" value="<?= $dv('aircraft_engineer') ?>">
            </div>
        </div>
    </details>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════
         SECTION 7 — Maintenance Engineering (conditional)
         ═══════════════════════════════════════════════════ -->
    <?php if ($needsMaint): ?>
    <details class="card" style="padding:0; margin-bottom:16px;">
        <summary style="
            padding:16px 20px; cursor:pointer; list-style:none;
            display:flex; align-items:center; gap:10px;
            font-size:14px; font-weight:700; color:var(--text-primary);">
            <span>🔧</span>
            <span>Engineering Details</span>
            <span style="margin-left:auto; font-size:11px; color:var(--text-muted); font-weight:400;">▾ Click to expand</span>
        </summary>
        <div style="padding:20px; border-top:1px solid var(--border);">
            <div class="form-group" style="margin-bottom:14px;">
                <label>Engineering Finding</label>
                <textarea name="engineering_finding" class="form-control" rows="4"
                          placeholder="Describe the defect or finding."><?= e($draft['engineering_finding'] ?? '') ?></textarea>
            </div>
            <div class="form-group" style="margin-bottom:14px;">
                <label>Recommended Action</label>
                <textarea name="recommended_action" class="form-control" rows="3"><?= e($draft['recommended_action'] ?? '') ?></textarea>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                <div class="form-group">
                    <label>Part Number ON (fitted)</label>
                    <input type="text" name="part_number_on" class="form-control" value="<?= $dv('part_number_on') ?>">
                </div>
                <div class="form-group">
                    <label>Part Number OFF (removed)</label>
                    <input type="text" name="part_number_off" class="form-control" value="<?= $dv('part_number_off') ?>">
                </div>
                <div class="form-group">
                    <label>Serial No ON</label>
                    <input type="text" name="serial_no_on" class="form-control" value="<?= $dv('serial_no_on') ?>">
                </div>
                <div class="form-group">
                    <label>Serial No OFF</label>
                    <input type="text" name="serial_no_off" class="form-control" value="<?= $dv('serial_no_off') ?>">
                </div>
                <div class="form-group">
                    <label>Batch Number</label>
                    <input type="text" name="batch_number" class="form-control" value="<?= $dv('batch_number') ?>">
                </div>
                <div class="form-group">
                    <label>Defect Sheet Number</label>
                    <input type="text" name="defect_sheet_number" class="form-control" value="<?= $dv('defect_sheet_number') ?>">
                </div>
            </div>
        </div>
    </details>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════
         SECTION 8 — FRAT (conditional)
         ═══════════════════════════════════════════════════ -->
    <?php if ($needsFrat): ?>
    <details class="card" style="padding:0; margin-bottom:16px;">
        <summary style="
            padding:16px 20px; cursor:pointer; list-style:none;
            display:flex; align-items:center; gap:10px;
            font-size:14px; font-weight:700; color:var(--text-primary);">
            <span>📋</span>
            <span>Flight Risk Assessment</span>
            <span style="margin-left:auto; font-size:11px; color:var(--text-muted); font-weight:400;">▾ Click to expand</span>
        </summary>
        <div style="padding:20px; border-top:1px solid var(--border);">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
                <div class="form-group">
                    <label>Departure Station</label>
                    <input type="text" name="frat_dep_station" class="form-control" value="<?= $dv('frat_dep_station') ?>">
                </div>
                <div class="form-group">
                    <label>Destination Station</label>
                    <input type="text" name="frat_dest_station" class="form-control" value="<?= $dv('frat_dest_station') ?>">
                </div>
            </div>
            <div class="form-group" style="margin-bottom:14px;">
                <label>Planned Departure UTC</label>
                <input type="time" name="frat_planned_dep" class="form-control" value="<?= $dv('frat_planned_dep') ?>">
            </div>

            <?php
            $fratPills = [
                ['field' => 'frat_crew_rest',   'label' => 'Crew Rest',      'opts' => ['Adequate','Marginal','Inadequate']],
                ['field' => 'frat_fatigue',      'label' => 'Fatigue Level',  'opts' => ['None','Low','Moderate','High']],
                ['field' => 'frat_weather',      'label' => 'Weather Concern','opts' => ['None','Moderate','Significant','Severe']],
            ];
            foreach ($fratPills as $fp):
                $fpVal = $draft[$fp['field']] ?? '';
            ?>
            <div class="form-group" style="margin-bottom:14px;">
                <label style="font-size:12px; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:.04em;"><?= e($fp['label']) ?></label>
                <div style="display:flex; gap:0; margin-top:8px; border:1px solid var(--border); border-radius:var(--radius-sm); overflow:hidden; width:fit-content; flex-wrap:wrap;">
                    <?php foreach ($fp['opts'] as $i => $opt): ?>
                    <label style="cursor:pointer; <?= $i > 0 ? 'border-left:1px solid var(--border);' : '' ?>">
                        <input type="radio" name="<?= e($fp['field']) ?>" value="<?= e($opt) ?>"
                               <?= $fpVal === $opt ? 'checked' : '' ?>
                               style="display:none;" class="frat-radio">
                        <span class="frat-pill" style="
                            display:block; padding:8px 16px; font-size:13px; font-weight:600;
                            background:<?= $fpVal === $opt ? 'var(--accent-blue,#3b82f6)' : 'transparent' ?>;
                            color:<?= $fpVal === $opt ? '#fff' : 'var(--text-primary)' ?>;
                            transition:all 0.15s; user-select:none;">
                            <?= e($opt) ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="form-group">
                <label>Additional Risk Factors</label>
                <textarea name="frat_additional_risks" class="form-control" rows="3"
                          placeholder="Any other risk factors relevant to this flight..."><?= e($draft['frat_additional_risks'] ?? '') ?></textarea>
            </div>
        </div>
    </details>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════
         SECTION 9 — Attachments
         ═══════════════════════════════════════════════════ -->
    <details class="card" style="padding:0; margin-bottom:16px;">
        <summary style="
            padding:16px 20px; cursor:pointer; list-style:none;
            display:flex; align-items:center; gap:10px;
            font-size:14px; font-weight:700; color:var(--text-primary);">
            <span>📎</span>
            <span>Evidence &amp; Attachments</span>
            <span style="margin-left:auto; font-size:11px; color:var(--text-muted); font-weight:400;">Optional ▾</span>
        </summary>
        <div style="padding:20px; border-top:1px solid var(--border);">
            <div id="dropZone" style="
                border:2px dashed var(--border); border-radius:var(--radius-md);
                padding:36px 20px; text-align:center; cursor:pointer;
                transition:border-color 0.15s, background 0.15s;"
                 onclick="document.getElementById('fileInput').click()"
                 ondragover="event.preventDefault(); this.style.borderColor='var(--accent-blue,#3b82f6)'; this.style.background='rgba(59,130,246,0.04)'"
                 ondragleave="this.style.borderColor='var(--border)'; this.style.background=''"
                 ondrop="handleFileDrop(event)">
                <div style="font-size:28px; margin-bottom:8px;">📁</div>
                <p style="margin:0 0 4px; font-size:14px; font-weight:600; color:var(--text-primary);">Drop files here or click to browse</p>
                <p class="text-xs text-muted" style="margin:0;">Photos, documents, or videos relevant to the event</p>
            </div>
            <input type="file" id="fileInput" name="attachments[]" multiple
                   accept=".jpg,.jpeg,.png,.heic,.pdf,.mp4,.mov"
                   style="display:none;" onchange="showSelectedFiles(this)">
            <p class="text-xs text-muted" style="margin:8px 0 0;">Max 25MB per file. Accepted: JPG, PNG, PDF, MP4, MOV, HEIC</p>
            <div id="fileList" style="margin-top:10px; display:flex; flex-direction:column; gap:6px;"></div>
        </div>
    </details>

    <!-- ═══════════════════════════════════════════════════
         SECTION 10 — Anonymous (conditional)
         ═══════════════════════════════════════════════════ -->
    <?php if ($allowAnonymous): ?>
    <div class="card" style="padding:16px 20px; margin-bottom:90px;">
        <label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer; font-weight:400; font-size:14px;">
            <input type="checkbox" name="is_anonymous" value="1"
                   <?= !empty($draft['is_anonymous']) ? 'checked' : '' ?>
                   style="width:16px; height:16px; margin-top:2px; flex-shrink:0;">
            <span>Submit anonymously — Your identity will not be shown to any party</span>
        </label>
        <p class="text-xs text-muted" style="margin:6px 0 0 26px; line-height:1.5;">
            Anonymous reports carry the same weight but we cannot follow up with you.
        </p>
    </div>
    <?php else: ?>
    <div style="margin-bottom:90px;"></div>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════
         STICKY BOTTOM ACTION BAR
         ═══════════════════════════════════════════════════ -->
    <div class="safety-form-bar" style="
        position:fixed; bottom:0; left:0; right:0;
        background:var(--bg-card); border-top:1px solid var(--border);
        padding:12px 24px; display:flex; align-items:center; gap:12px;
        z-index:100; box-shadow:0 -2px 12px rgba(0,0,0,0.08);">
        <span class="text-xs text-muted" style="flex:1; display:flex; align-items:center; gap:5px;">
            🔒 Securely encrypted
        </span>
        <span class="draft-status text-xs text-muted" id="draftStatus"></span>
        <button type="button" onclick="saveDraft()" class="btn btn-outline" style="display:flex; align-items:center; gap:6px;">
            💾 Save Draft
        </button>
        <button type="submit" class="btn btn-primary" style="display:flex; align-items:center; gap:6px;"
                onclick="return confirm('Submit this report to the Safety Team? This action cannot be undone.')">
            🔒 Submit Report
        </button>
    </div>

</form>

<script>
// ─── Occurrence type pill toggle ────────────────────────────────────────────
document.querySelectorAll('.occ-radio').forEach(function(radio) {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.occ-pill').forEach(function(pill) {
            pill.style.background = 'transparent';
            pill.style.color      = 'var(--text-primary)';
        });
        var pill = this.nextElementSibling;
        if (pill) {
            pill.style.background = 'var(--accent-blue,#3b82f6)';
            pill.style.color      = '#fff';
        }
    });
});

// ─── FRAT pill toggle ────────────────────────────────────────────────────────
document.querySelectorAll('.frat-radio').forEach(function(radio) {
    radio.addEventListener('change', function() {
        var group = this.closest('div').querySelectorAll('.frat-radio');
        group.forEach(function(r) {
            var p = r.nextElementSibling;
            if (p) { p.style.background = 'transparent'; p.style.color = 'var(--text-primary)'; }
        });
        var pill = this.nextElementSibling;
        if (pill) { pill.style.background = 'var(--accent-blue,#3b82f6)'; pill.style.color = '#fff'; }
    });
});

// ─── ICAO uppercase ────────────────────────────────────────────────────────
var icaoEl = document.getElementById('icaoInput');
if (icaoEl) icaoEl.addEventListener('input', function() { this.value = this.value.toUpperCase(); });

// ─── Narrative character counter ────────────────────────────────────────────
var narEl = document.getElementById('narrativeTextarea');
var ccEl  = document.getElementById('charCount');
function updateCharCount() {
    var n = narEl ? narEl.value.length : 0;
    if (ccEl) {
        ccEl.textContent = n + ' characters';
        ccEl.style.color = n < 50 ? '#f59e0b' : 'var(--text-muted)';
    }
}
if (narEl) { narEl.addEventListener('input', updateCharCount); updateCharCount(); }

// ─── Roster context toggle ───────────────────────────────────────────────────
var rosterChk   = document.getElementById('linkRosterCtx');
var rosterBlock = document.getElementById('rosterContextBlock');
if (rosterChk && rosterBlock) {
    rosterChk.addEventListener('change', function() {
        rosterBlock.style.display = this.checked ? '' : 'none';
    });
}

// ─── Risk matrix ─────────────────────────────────────────────────────────────
var hiddenLik  = document.getElementById('hiddenLikelihood');
var hiddenSev  = document.getElementById('hiddenSeverity');
var hiddenCode = document.getElementById('hiddenRiskCode');
var riskResult = document.getElementById('riskResult');
var riskBadge  = document.getElementById('riskBadge');

document.querySelectorAll('.risk-cell').forEach(function(cell) {
    cell.addEventListener('click', function() {
        var sev   = this.dataset.sev;
        var lik   = this.dataset.lik;
        var code  = this.dataset.code;
        var lbl   = this.dataset.label;
        var color = this.dataset.color;

        // Update hidden fields
        hiddenLik.value  = lik;
        hiddenSev.value  = sev;
        hiddenCode.value = code;

        // Update display
        if (riskResult) riskResult.style.opacity = '1';
        if (riskBadge)  { riskBadge.textContent = lbl + ' — ' + code; riskBadge.style.background = color; }

        // Reset all cells, highlight selected
        document.querySelectorAll('.risk-cell').forEach(function(c) {
            c.style.opacity = '0.5';
            c.style.outline = 'none';
        });
        this.style.opacity = '1';
        this.style.outline = '3px solid #1e3a5f';
    });
});

// ─── Save Draft via fetch ──────────────────────────────────────────────────
var _formDirty = false;
document.getElementById('safetyReportForm').addEventListener('input', function() { _formDirty = true; });

function saveDraft() {
    var form    = document.getElementById('safetyReportForm');
    var data    = new FormData(form);
    var statusEl = document.getElementById('draftStatus');
    if (statusEl) statusEl.textContent = '⏳ Saving…';

    fetch('/safety/report/draft', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: data
    })
    .then(function(r) { return r.json(); })
    .then(function(j) {
        _formDirty = false;
        if (statusEl) statusEl.textContent = j.success ? '✓ Saved — ' + (j.reference_no || '') : '⚠ Save failed';
        // Populate draft_id if newly created
        if (j.id) {
            var draftIdInput = form.querySelector('input[name="draft_id"]');
            if (!draftIdInput) {
                draftIdInput = document.createElement('input');
                draftIdInput.type = 'hidden';
                draftIdInput.name = 'draft_id';
                form.appendChild(draftIdInput);
            }
            draftIdInput.value = j.id;
        }
    })
    .catch(function() { if (statusEl) statusEl.textContent = '⚠ Save failed'; });
}

// Auto-save every 60s if dirty
setInterval(function() { if (_formDirty) saveDraft(); }, 60000);

// ─── File drop/select display ─────────────────────────────────────────────
function showSelectedFiles(input) {
    var list = document.getElementById('fileList');
    if (!list) return;
    list.innerHTML = '';
    Array.from(input.files).forEach(function(f) {
        var row = document.createElement('div');
        row.style.cssText = 'display:flex;align-items:center;gap:10px;padding:8px 10px;background:var(--bg-secondary,#f9fafb);border-radius:6px;border:1px solid var(--border);font-size:13px;';
        row.innerHTML = '<span>📄</span><span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + f.name + '</span><span style="color:var(--text-muted);font-size:11px;">' + (f.size > 1048576 ? (f.size/1048576).toFixed(1)+'MB' : (f.size/1024).toFixed(0)+'KB') + '</span>';
        list.appendChild(row);
    });
}
function handleFileDrop(event) {
    event.preventDefault();
    var input = document.getElementById('fileInput');
    input.files = event.dataTransfer.files;
    showSelectedFiles(input);
    event.currentTarget.style.borderColor = 'var(--border)';
    event.currentTarget.style.background  = '';
}
</script>
