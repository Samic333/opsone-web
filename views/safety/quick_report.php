<?php
/**
 * OpsOne — Safety Quick Report
 * Variables: $reportType (string), $reportTypeLabel (string),
 *            $prefill (array from buildPrefill), $user (array)
 */

$pageTitle    = 'Quick Report';
$pageSubtitle = e($reportTypeLabel ?? 'Safety Event');

$todayDate = date('Y-m-d');
$nowUtc    = gmdate('H:i');
?>

<!-- ═══════════════════════════════
     PAGE HEADER
     ═══════════════════════════════ -->
<div style="margin-bottom:16px;">
    <h2 style="margin:0 0 4px; font-size:22px; font-weight:700; color:var(--text-primary);">⚡ Quick Report</h2>
    <p class="text-sm text-muted" style="margin:0;"><?= e($reportTypeLabel ?? '') ?></p>
</div>

<!-- Amber Banner -->
<div style="
    background:rgba(245,158,11,0.10);
    border:1px solid rgba(245,158,11,0.40);
    border-radius:var(--radius-md);
    padding:12px 18px;
    display:flex; align-items:center; gap:12px;
    margin-bottom:22px;">
    <span style="font-size:18px; flex-shrink:0;">⚡</span>
    <p class="text-sm" style="margin:0; color:var(--text-primary); line-height:1.5;">
        <strong>Quick capture</strong> — submit now, add detail later if needed.
    </p>
</div>

<!-- ═══════════════════════════════
     SINGLE CARD FORM
     ═══════════════════════════════ -->
<form method="POST" action="/safety/report/quick/<?= e($reportType ?? '') ?>" enctype="multipart/form-data" id="quickReportForm">
    <?= csrfField() ?>
    <input type="hidden" name="report_type" value="<?= e($reportType ?? '') ?>">
    <input type="hidden" name="is_quick_report" value="1">

    <div class="card" style="padding:24px 28px; margin-bottom:16px;">

        <!-- Occurrence Type: pill toggle -->
        <div class="form-group" style="margin-bottom:20px;">
            <label style="display:block; margin-bottom:8px; font-weight:600; font-size:14px;">Occurrence Type</label>
            <div style="display:inline-flex; gap:0; border:1px solid var(--border); border-radius:var(--radius-md); overflow:hidden;">
                <label style="display:flex; align-items:center; cursor:pointer;">
                    <input type="radio" name="occurrence_type" value="occurrence" style="display:none;"
                           id="occType_occurrence" checked>
                    <span id="pill_occurrence" onclick="setPill('occurrence')" style="
                        padding:10px 22px; font-size:14px; font-weight:600; user-select:none;
                        background:var(--accent-blue,#3b82f6); color:#fff;
                        transition:background 0.15s, color 0.15s;">
                        Occurrence
                    </span>
                </label>
                <label style="display:flex; align-items:center; cursor:pointer;">
                    <input type="radio" name="occurrence_type" value="hazard" style="display:none;"
                           id="occType_hazard">
                    <span id="pill_hazard" onclick="setPill('hazard')" style="
                        padding:10px 22px; font-size:14px; font-weight:600; user-select:none;
                        background:transparent; color:var(--text-secondary);
                        transition:background 0.15s, color 0.15s;">
                        Hazard
                    </span>
                </label>
            </div>
        </div>

        <!-- Event Title -->
        <div class="form-group" style="margin-bottom:20px;">
            <label for="qr_title" style="font-weight:600; font-size:14px;">Event Title <span style="color:#ef4444;">*</span></label>
            <input type="text" id="qr_title" name="title" class="form-control"
                   placeholder="Brief title" required
                   value="<?= e($prefill['title'] ?? '') ?>"
                   style="font-size:16px; padding:12px 14px; margin-top:6px;">
        </div>

        <!-- Date + Time row -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px;">
            <div class="form-group">
                <label for="qr_date" style="font-weight:600; font-size:14px;">Date</label>
                <input type="date" id="qr_date" name="event_date" class="form-control"
                       value="<?= e($prefill['event_date'] ?? $todayDate) ?>"
                       style="font-size:16px; padding:12px 14px; margin-top:6px;">
            </div>
            <div class="form-group">
                <label for="qr_time" style="font-weight:600; font-size:14px;">Time (UTC)</label>
                <input type="time" id="qr_time" name="event_time_utc" class="form-control"
                       value="<?= e($prefill['event_time_utc'] ?? $nowUtc) ?>"
                       style="font-size:16px; padding:12px 14px; margin-top:6px;">
            </div>
        </div>

        <!-- Location + ICAO row -->
        <div style="display:grid; grid-template-columns:2fr 1fr; gap:16px; margin-bottom:20px;">
            <div class="form-group">
                <label for="qr_location" style="font-weight:600; font-size:14px;">Location</label>
                <input type="text" id="qr_location" name="location" class="form-control"
                       placeholder="Airport, city or area"
                       value="<?= e($prefill['location'] ?? '') ?>"
                       style="font-size:15px; padding:12px 14px; margin-top:6px;">
            </div>
            <div class="form-group">
                <label for="qr_icao" style="font-weight:600; font-size:14px;">ICAO</label>
                <input type="text" id="qr_icao" name="icao_code" class="form-control"
                       placeholder="e.g. EGLL" maxlength="4"
                       value="<?= e($prefill['icao_code'] ?? '') ?>"
                       style="font-size:15px; padding:12px 14px; margin-top:6px; text-transform:uppercase; letter-spacing:.08em;">
            </div>
        </div>

        <!-- Short Description -->
        <div class="form-group" style="margin-bottom:20px;">
            <label for="qr_description" style="font-weight:600; font-size:14px;">Description <span style="color:#ef4444;">*</span></label>
            <textarea id="qr_description" name="description" class="form-control" rows="4" required
                      placeholder="What happened? Where? Who was involved?"
                      style="font-size:15px; line-height:1.6; padding:12px 14px; margin-top:6px; resize:vertical;"><?= e($prefill['description'] ?? '') ?></textarea>
        </div>

        <!-- Initial Risk: horizontal 1–5 pill row -->
        <div class="form-group" style="margin-bottom:20px;">
            <label style="display:block; margin-bottom:10px; font-weight:600; font-size:14px;">Initial Risk</label>
            <div style="display:flex; gap:8px; flex-wrap:wrap;" id="riskPills">
                <?php
                $riskLevels = [
                    1 => ['label' => '1', 'sub' => 'Negligible',   'bg' => '#16a34a', 'bg_inactive' => 'rgba(22,163,74,0.12)',  'color_inactive' => '#15803d'],
                    2 => ['label' => '2', 'sub' => 'Minor',        'bg' => '#65a30d', 'bg_inactive' => 'rgba(101,163,13,0.12)', 'color_inactive' => '#4d7c0f'],
                    3 => ['label' => '3', 'sub' => 'Major',        'bg' => '#d97706', 'bg_inactive' => 'rgba(217,119,6,0.12)',  'color_inactive' => '#b45309'],
                    4 => ['label' => '4', 'sub' => 'Hazardous',    'bg' => '#ea580c', 'bg_inactive' => 'rgba(234,88,12,0.12)',  'color_inactive' => '#c2410c'],
                    5 => ['label' => '5', 'sub' => 'Catastrophic', 'bg' => '#dc2626', 'bg_inactive' => 'rgba(220,38,38,0.12)',  'color_inactive' => '#b91c1c'],
                ];
                $selectedRisk = (int)($prefill['initial_risk'] ?? 0);
                foreach ($riskLevels as $val => $r):
                $isActive = $selectedRisk === $val;
                ?>
                <label style="cursor:pointer; text-align:center;">
                    <input type="radio" name="initial_risk" value="<?= $val ?>"
                           id="risk_<?= $val ?>" style="display:none;"
                           <?= $isActive ? 'checked' : '' ?>
                           onchange="highlightRisk(<?= $val ?>)">
                    <span id="riskPill_<?= $val ?>" style="
                        display:flex; flex-direction:column; align-items:center; justify-content:center;
                        width:72px; padding:10px 4px 8px;
                        border-radius:var(--radius-md); border:2px solid <?= $isActive ? $r['bg'] : 'transparent' ?>;
                        background:<?= $isActive ? $r['bg'] : $r['bg_inactive'] ?>;
                        color:<?= $isActive ? '#fff' : $r['color_inactive'] ?>;
                        font-weight:700; font-size:16px;
                        transition:background 0.15s, color 0.15s, border 0.15s;
                        user-select:none;">
                        <?= $r['label'] ?>
                        <span style="font-size:10px; font-weight:600; margin-top:3px; opacity:0.85; line-height:1.2;"><?= $r['sub'] ?></span>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Anonymous checkbox (if tenant allows) -->
        <?php if (!empty($settings['allow_anonymous'])): ?>
        <div class="form-group" style="margin-bottom:20px;">
            <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-size:14px; font-weight:500;">
                <input type="checkbox" name="is_anonymous" value="1"
                       style="width:18px; height:18px; cursor:pointer;"
                       <?= !empty($prefill['is_anonymous']) ? 'checked' : '' ?>>
                <span>Submit anonymously — your name will not be visible to investigators</span>
            </label>
        </div>
        <?php endif; ?>

        <!-- Attachment -->
        <div class="form-group" style="margin-bottom:4px;">
            <label for="qr_attachment" style="font-weight:600; font-size:14px;">Attachment <span class="text-muted" style="font-weight:400;">(optional)</span></label>
            <input type="file" id="qr_attachment" name="attachment" class="form-control"
                   accept="image/*,application/pdf,video/mp4,video/quicktime"
                   style="margin-top:6px; padding:10px 12px;">
            <p class="text-xs text-muted" style="margin-top:5px;">Images, PDFs, or short video. Max 25 MB.</p>
        </div>

    </div><!-- /card -->

    <!-- Switch to Full Report link -->
    <p class="text-sm text-muted" style="margin:0 0 80px; text-align:center;">
        Need to add more detail?
        <a href="/safety/report/new/<?= e($reportType ?? '') ?>" style="color:var(--accent-blue);">→ Switch to Full Report</a>
    </p>

    <!-- ═══════════════════════════════
         STICKY ACTION BAR
         ═══════════════════════════════ -->
    <div class="form-action-bar">
        <a href="/safety" class="btn btn-ghost">← Back</a>
        <span style="flex:1;"></span>
        <button type="submit" name="is_draft" value="1" class="btn btn-outline">Save Draft</button>
        <button type="submit" name="is_draft" value="0" class="btn btn-primary">🔒 Submit Report</button>
    </div>

</form>

<style>
.form-action-bar {
    position:fixed;
    bottom:0; left:0; right:0;
    z-index:100;
    display:flex; align-items:center; gap:10px;
    padding:12px 24px 16px;
    background:var(--bg-card, #fff);
    border-top:1px solid var(--border);
    box-shadow:0 -2px 12px rgba(0,0,0,0.08);
}
@media (max-width: 600px) {
    .form-action-bar { padding:10px 12px 14px; gap:8px; }
    .form-action-bar .btn { font-size:13px; padding:10px 14px; }
}
</style>

<script>
// Occurrence type pill toggle
function setPill(val) {
    document.getElementById('occType_' + val).checked = true;
    ['occurrence','hazard'].forEach(function(v) {
        var el = document.getElementById('pill_' + v);
        if (v === val) {
            el.style.background = 'var(--accent-blue,#3b82f6)';
            el.style.color = '#fff';
        } else {
            el.style.background = 'transparent';
            el.style.color = 'var(--text-secondary)';
        }
    });
}

// Risk pill highlight
var riskColors = {
    1: {bg:'#16a34a', bgInactive:'rgba(22,163,74,0.12)',  colorInactive:'#15803d'},
    2: {bg:'#65a30d', bgInactive:'rgba(101,163,13,0.12)', colorInactive:'#4d7c0f'},
    3: {bg:'#d97706', bgInactive:'rgba(217,119,6,0.12)',  colorInactive:'#b45309'},
    4: {bg:'#ea580c', bgInactive:'rgba(234,88,12,0.12)',  colorInactive:'#c2410c'},
    5: {bg:'#dc2626', bgInactive:'rgba(220,38,38,0.12)',  colorInactive:'#b91c1c'}
};
function highlightRisk(selected) {
    for (var i = 1; i <= 5; i++) {
        var pill = document.getElementById('riskPill_' + i);
        if (!pill) continue;
        var c = riskColors[i];
        if (i === selected) {
            pill.style.background = c.bg;
            pill.style.color = '#fff';
            pill.style.borderColor = c.bg;
        } else {
            pill.style.background = c.bgInactive;
            pill.style.color = c.colorInactive;
            pill.style.borderColor = 'transparent';
        }
    }
}

// ICAO uppercase
var icaoInput = document.getElementById('qr_icao');
if (icaoInput) {
    icaoInput.addEventListener('input', function() {
        var pos = this.selectionStart;
        this.value = this.value.toUpperCase();
        this.setSelectionRange(pos, pos);
    });
}
</script>
