<?php
/**
 * My Roster — crew self-service personal view
 * Variables: $year, $month, $daysInMonth, $byDate, $upcoming, $summary,
 *            $dutyTypes, $activePeriod, $myChanges,
 *            $prevMonth, $prevYear, $nextMonth, $nextYear
 */
$today     = date('Y-m-d');
$dutyTypes = $dutyTypes ?? RosterModel::dutyTypes();
?>
<style>
.myr-layout{display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start;}
@media(max-width:960px){.myr-layout{grid-template-columns:1fr;}}
/* Monthly calendar */
.myr-cal-wrap{background:var(--bg-card);border:1px solid var(--border);border-radius:12px;overflow:hidden;}
.myr-cal-hdr{padding:16px 20px;background:var(--bg-secondary);border-bottom:1px solid var(--border);
 display:flex;align-items:center;gap:10px;}
.myr-cal-title{font-size:17px;font-weight:800;}
.myr-cal-nav{display:flex;align-items:center;gap:6px;margin-left:auto;}
.myr-cal-body{padding:16px;}
.myr-dow-row{display:grid;grid-template-columns:repeat(7,1fr);gap:4px;margin-bottom:6px;}
.myr-dow-cell{text-align:center;font-size:9px;font-weight:700;text-transform:uppercase;
 letter-spacing:.06em;color:var(--text-muted);padding:3px 0;}
.myr-cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:4px;}
.myr-day{border-radius:8px;min-height:60px;padding:6px;display:flex;flex-direction:column;
 border:1px solid var(--border);transition:box-shadow .12s;}
.myr-day:hover{box-shadow:0 1px 6px rgba(0,0,0,.1);}
.myr-day-num{font-size:13px;font-weight:700;line-height:1;margin-bottom:3px;}
.myr-day.is-today{border-color:#2563eb;box-shadow:0 0 0 1.5px #2563eb;}
.myr-day.is-today .myr-day-num{color:#2563eb;}
.myr-day.is-wknd{background:rgba(239,68,68,.025);}
.myr-duty-chip{display:inline-flex;align-items:center;justify-content:center;
 border-radius:4px;padding:2px 6px;font-size:10px;font-weight:800;letter-spacing:.04em;width:100%;margin-top:1px;}
.myr-day-code{font-size:9px;color:var(--text-muted);margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.myr-empty-day{color:var(--border);font-size:18px;text-align:center;margin-top:8px;}
/* Summary panel */
.myr-summary-card{background:var(--bg-card);border:1px solid var(--border);border-radius:12px;overflow:hidden;}
.myr-summary-hdr{padding:14px 16px;background:var(--bg-secondary);border-bottom:1px solid var(--border);}
.myr-summary-body{padding:0;}
.myr-stat-row{display:flex;justify-content:space-between;align-items:center;
 padding:11px 16px;border-bottom:1px solid var(--border);}
.myr-stat-row:last-child{border:none;}
.myr-stat-label{font-size:13px;color:var(--text-muted);}
.myr-stat-val{font-size:20px;font-weight:800;}
/* Upcoming duties */
.upcoming-card{background:var(--bg-card);border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-top:16px;}
.upcoming-item{display:flex;align-items:center;gap:12px;padding:10px 16px;border-bottom:1px solid var(--border);}
.upcoming-item:last-child{border:none;}
.upcoming-date{width:44px;text-align:center;flex-shrink:0;}
.upcoming-date-day{font-size:20px;font-weight:800;line-height:1;color:var(--text-primary);}
.upcoming-date-dow{font-size:10px;text-transform:uppercase;color:var(--text-muted);letter-spacing:.05em;}
.upcoming-chip{flex-shrink:0;}
.upcoming-detail{flex:1;min-width:0;}
.upcoming-duty-name{font-size:13px;font-weight:600;}
.upcoming-duty-note{font-size:11px;color:var(--text-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
/* ── Request form ─────────────────────────────── */
.req-card{background:var(--bg-card);border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-top:16px;}
.req-card-hdr{padding:14px 16px;background:var(--bg-secondary);border-bottom:1px solid var(--border);
 display:flex;align-items:center;gap:10px;}
.req-card-hdr-icon{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;
 background:var(--accent-blue,#3b82f6);color:#fff;font-size:15px;flex-shrink:0;}
.req-card-body{padding:18px 16px;}
.req-type-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:16px;}
.req-type-btn{padding:10px 8px;border-radius:8px;border:2px solid var(--border);cursor:pointer;
 text-align:center;transition:all .15s;background:var(--bg-secondary);}
.req-type-btn:hover{border-color:var(--accent-blue,#3b82f6);background:rgba(37,99,235,.05);}
.req-type-btn.active{border-color:var(--accent-blue,#3b82f6);background:rgba(37,99,235,.07);}
.req-type-icon{font-size:18px;display:block;margin-bottom:3px;}
.req-type-label{font-size:11px;font-weight:700;color:var(--text-primary);}
.req-field{margin-bottom:14px;}
.req-field label{display:block;font-size:12px;font-weight:600;color:var(--text-muted);
 text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px;}
.req-field .form-control{font-size:14px;padding:10px 12px;border-radius:8px;}
.req-field textarea.form-control{resize:vertical;min-height:90px;}
.req-hint{font-size:11px;color:var(--text-muted);margin-top:4px;line-height:1.4;}
.req-submit-btn{width:100%;padding:11px;font-size:14px;font-weight:700;border-radius:8px;
 border:none;cursor:pointer;transition:opacity .15s;display:flex;align-items:center;justify-content:center;gap:8px;}
.req-submit-btn:hover{opacity:.88;}
/* Change requests history */
.cr-item{display:flex;align-items:flex-start;gap:10px;padding:12px 0;border-bottom:1px solid var(--border);}
.cr-item:last-child{border:none;}
.cr-type-chip{font-size:10px;font-weight:700;padding:2px 7px;border-radius:4px;white-space:nowrap;}
.cr-status-pending{background:#fef9c3;color:#92400e;}
.cr-status-approved{background:#d1fae5;color:#065f46;}
.cr-status-rejected{background:#fee2e2;color:#991b1b;}
.cr-status-noted{background:#f3f4f6;color:#374151;}
</style>

<!-- Period status banner -->
<?php if ($activePeriod): ?>
<div style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;border:1px solid;margin-bottom:14px;font-size:13px;
     <?= $activePeriod['status'] === 'published' ? 'border-color:#10b981;background:rgba(16,185,129,.08);color:#065f46;' : 'border-color:#f59e0b;background:rgba(245,158,11,.08);color:#b45309;' ?>">
    <?php if ($activePeriod['status'] === 'published'): ?>
        ✓ Roster published for <strong><?= e($activePeriod['name']) ?></strong>
        (<?= date('d M', strtotime($activePeriod['start_date'])) ?> – <?= date('d M Y', strtotime($activePeriod['end_date'])) ?>)
    <?php elseif ($activePeriod['status'] === 'frozen'): ?>
        🔒 Roster frozen for <strong><?= e($activePeriod['name']) ?></strong>
    <?php else: ?>
        ⚠ Roster for <strong><?= e($activePeriod['name']) ?></strong> is still in draft — check back soon
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="myr-layout">
    <!-- Monthly Calendar -->
    <div>
        <div class="myr-cal-wrap">
            <div class="myr-cal-hdr">
                <div class="myr-cal-title"><?= date('F Y', mktime(0,0,0,$month,1,$year)) ?></div>
                <div class="myr-cal-nav">
                    <a href="/my-roster?year=<?= $prevYear ?>&month=<?= $prevMonth ?>" class="btn btn-ghost btn-xs">← <?= date('M', mktime(0,0,0,$prevMonth,1,$prevYear)) ?></a>
                    <a href="/my-roster?year=<?= $nextYear ?>&month=<?= $nextMonth ?>" class="btn btn-ghost btn-xs"><?= date('M', mktime(0,0,0,$nextMonth,1,$nextYear)) ?> →</a>
                </div>
            </div>
            <div class="myr-cal-body">
                <div class="myr-dow-row">
                    <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $dow): ?>
                        <div class="myr-dow-cell"><?= $dow ?></div>
                    <?php endforeach; ?>
                </div>

                <?php
                $firstDay = date('N', mktime(0,0,0,$month,1,$year));
                echo '<div class="myr-cal-grid">';
                for ($e = 1; $e < $firstDay; $e++) echo '<div></div>';

                for ($d = 1; $d <= $daysInMonth; $d++):
                    $dt     = sprintf('%04d-%02d-%02d', $year, $month, $d);
                    $entry  = $byDate[$dt] ?? null;
                    $dow    = (int)date('N', strtotime($dt));
                    $isWknd = $dow >= 6;
                    $isTdy  = $dt === $today;
                    $cls    = 'myr-day';
                    if ($isTdy)  $cls .= ' is-today';
                    if ($isWknd) $cls .= ' is-wknd';
                    $dtype  = $entry['duty_type'] ?? null;
                    $dtMeta = $dtype ? ($dutyTypes[$dtype] ?? null) : null;
                ?>
                <div class="<?= $cls ?>">
                    <div class="myr-day-num"><?= $d ?></div>
                    <?php if ($entry && $dtMeta): ?>
                        <div class="myr-duty-chip"
                             style="background:<?= $dtMeta['bg'] ?>;color:<?= $dtMeta['color'] ?>;">
                            <?= e($entry['duty_code'] ?: $dtMeta['code']) ?>
                        </div>
                        <?php if ($entry['notes']): ?>
                            <div class="myr-day-code" title="<?= e($entry['notes']) ?>"><?= e(substr($entry['notes'],0,20)) ?></div>
                        <?php endif; ?>
                    <?php elseif ($entry && !$dtMeta): ?>
                        <div class="myr-day-code" style="color:var(--text-muted);font-size:10px;"><?= e(strtoupper($entry['duty_type'])) ?></div>
                    <?php else: ?>
                        <div class="myr-empty-day">·</div>
                    <?php endif; ?>
                </div>
                <?php endfor;
                echo '</div>'; ?>
            </div>
        </div>

        <!-- Upcoming 14 days -->
        <?php if (!empty($upcoming)): ?>
        <div class="upcoming-card">
            <div style="padding:12px 16px;border-bottom:1px solid var(--border);background:var(--bg-secondary);">
                <strong style="font-size:13px;">Next 14 Days</strong>
            </div>
            <?php foreach ($upcoming as $e):
                $dtMeta = $dutyTypes[$e['duty_type']] ?? ['bg'=>'#f3f4f6','color'=>'#6b7280','code'=>strtoupper(substr($e['duty_type'],0,3)),'label'=>$e['duty_type']];
                $isUpTdy = $e['roster_date'] === $today;
            ?>
            <div class="upcoming-item" style="<?= $isUpTdy ? 'background:rgba(37,99,235,.05);' : '' ?>">
                <div class="upcoming-date">
                    <div class="upcoming-date-day"><?= date('j', strtotime($e['roster_date'])) ?></div>
                    <div class="upcoming-date-dow"><?= date('D', strtotime($e['roster_date'])) ?></div>
                </div>
                <div class="upcoming-chip">
                    <span class="myr-duty-chip" style="background:<?= $dtMeta['bg'] ?>;color:<?= $dtMeta['color'] ?>;width:auto;padding:3px 8px;font-size:11px;">
                        <?= e($e['duty_code'] ?: $dtMeta['code']) ?>
                    </span>
                </div>
                <div class="upcoming-detail">
                    <div class="upcoming-duty-name"><?= $dtMeta['label'] ?></div>
                    <?php if ($e['notes']): ?>
                        <div class="upcoming-duty-note"><?= e($e['notes']) ?></div>
                    <?php endif; ?>
                </div>
                <?php if ($isUpTdy): ?>
                    <span style="font-size:10px;font-weight:700;color:#2563eb;padding:2px 6px;background:#dbeafe;border-radius:4px;">TODAY</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right sidebar -->
    <div>

        <!-- ── Month Summary ───────────────────── -->
        <div class="myr-summary-card">
            <div class="myr-summary-hdr">
                <strong style="font-size:13px;">Month Summary</strong>
                <div style="font-size:11px;color:var(--text-muted);margin-top:2px;"><?= date('F Y', mktime(0,0,0,$month,1,$year)) ?></div>
            </div>
            <div class="myr-summary-body">
                <div class="myr-stat-row">
                    <div>
                        <div class="myr-stat-label">Flight Days</div>
                        <div style="font-size:10px;color:var(--text-muted);">Active flying assignments</div>
                    </div>
                    <span class="myr-stat-val" style="color:#2563eb;"><?= $summary['flight'] ?></span>
                </div>
                <div class="myr-stat-row">
                    <div>
                        <div class="myr-stat-label">Standby / Reserve</div>
                        <div style="font-size:10px;color:var(--text-muted);">On-call availability</div>
                    </div>
                    <span class="myr-stat-val" style="color:#d97706;"><?= ($summary['standby'] ?? 0) + ($summary['reserve'] ?? 0) ?></span>
                </div>
                <div class="myr-stat-row">
                    <div>
                        <div class="myr-stat-label">Training</div>
                        <div style="font-size:10px;color:var(--text-muted);">Sim / ground / recurrent</div>
                    </div>
                    <span class="myr-stat-val" style="color:#7c3aed;"><?= $summary['training'] ?></span>
                </div>
                <div class="myr-stat-row">
                    <div>
                        <div class="myr-stat-label">Leave</div>
                        <div style="font-size:10px;color:var(--text-muted);">Annual, sick, study</div>
                    </div>
                    <span class="myr-stat-val" style="color:#059669;"><?= $summary['leave'] ?></span>
                </div>
                <div class="myr-stat-row">
                    <div>
                        <div class="myr-stat-label">Days Off / Rest</div>
                        <div style="font-size:10px;color:var(--text-muted);">Off duty, rest periods</div>
                    </div>
                    <span class="myr-stat-val" style="color:var(--text-muted);"><?= ($summary['off'] ?? 0) + ($summary['rest'] ?? 0) ?></span>
                </div>
                <div class="myr-stat-row" style="background:rgba(37,99,235,.04);">
                    <div>
                        <div class="myr-stat-label" style="font-weight:700;color:var(--text-primary);">Total Rostered Days</div>
                    </div>
                    <span class="myr-stat-val" style="font-size:24px;"><?= $summary['total'] ?></span>
                </div>
            </div>
        </div>

        <!-- ── Submit a Request ─────────────────── -->
        <div class="req-card">
            <div class="req-card-hdr">
                <div class="req-card-hdr-icon">✍️</div>
                <div>
                    <div style="font-size:14px;font-weight:700;color:var(--text-primary);">Submit a Request</div>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:1px;">Your request is sent directly to scheduling</div>
                </div>
            </div>
            <div class="req-card-body">
                <form method="POST" action="/roster/changes/request" id="reqForm">
                    <?= csrfField() ?>
                    <input type="hidden" name="redirect" value="/my-roster?year=<?= $year ?>&month=<?= $month ?>">
                    <input type="hidden" name="change_type" id="changeTypeHidden" value="leave_request">

                    <!-- Request type selector -->
                    <div class="req-field">
                        <label>Request Type</label>
                        <div class="req-type-grid">
                            <div class="req-type-btn active" data-type="leave_request" onclick="selectType(this,'leave_request')">
                                <span class="req-type-icon">🏖️</span>
                                <span class="req-type-label">Leave Request</span>
                            </div>
                            <div class="req-type-btn" data-type="swap_request" onclick="selectType(this,'swap_request')">
                                <span class="req-type-icon">🔄</span>
                                <span class="req-type-label">Duty Swap</span>
                            </div>
                            <div class="req-type-btn" data-type="correction" onclick="selectType(this,'correction')">
                                <span class="req-type-icon">✏️</span>
                                <span class="req-type-label">Correction</span>
                            </div>
                            <div class="req-type-btn" data-type="comment" onclick="selectType(this,'comment')">
                                <span class="req-type-icon">💬</span>
                                <span class="req-type-label">Comment / Query</span>
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic hint based on type -->
                    <div id="typeHint" style="margin-bottom:14px;padding:10px 12px;border-radius:8px;background:rgba(37,99,235,.06);
                         border:1px solid rgba(37,99,235,.15);font-size:12px;color:#1d4ed8;line-height:1.5;">
                        <strong>Leave Request:</strong> Include your preferred dates and type of leave (annual, sick, study).
                    </div>

                    <!-- Message -->
                    <div class="req-field">
                        <label>Your Message <span style="color:#ef4444;">*</span></label>
                        <textarea name="message" id="reqMessage" class="form-control"
                                  placeholder="Describe your request in detail. Include relevant dates, flight numbers, or crew members where applicable."
                                  required maxlength="1000"></textarea>
                        <div class="req-hint">
                            <span id="charCount">0</span>/1000 characters · Be as specific as possible to help scheduling respond quickly.
                        </div>
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="req-submit-btn" id="reqSubmitBtn"
                            style="background:var(--accent-blue,#3b82f6);color:#fff;">
                        <span id="submitIcon">🏖️</span>
                        <span id="submitLabel">Submit Leave Request</span>
                    </button>

                    <p style="font-size:11px;color:var(--text-muted);text-align:center;margin:10px 0 0;">
                        You'll be notified when scheduling responds. Average response: 24–48 hours.
                    </p>
                </form>
            </div>
        </div>

        <!-- ── My Recent Requests ───────────────── -->
        <?php if (!empty($myChanges)): ?>
        <div style="margin-top:16px;background:var(--bg-card);border:1px solid var(--border);border-radius:12px;overflow:hidden;">
            <div style="padding:14px 16px;border-bottom:1px solid var(--border);background:var(--bg-secondary);
                        display:flex;align-items:center;justify-content:space-between;">
                <strong style="font-size:13px;">My Recent Requests</strong>
                <a href="/roster/changes" style="font-size:12px;color:var(--accent-blue,#3b82f6);text-decoration:none;font-weight:600;">View All →</a>
            </div>
            <div style="padding:4px 16px 8px;">
                <?php
                $crTypeLabels = [
                    'leave_request'    => ['label'=>'Leave',    'icon'=>'🏖️'],
                    'swap_request'     => ['label'=>'Swap',     'icon'=>'🔄'],
                    'correction'       => ['label'=>'Correct',  'icon'=>'✏️'],
                    'comment'          => ['label'=>'Comment',  'icon'=>'💬'],
                    'training_request' => ['label'=>'Training', 'icon'=>'📚'],
                ];
                $statusIcons = ['pending'=>'⏳','approved'=>'✅','rejected'=>'❌','noted'=>'📝'];
                foreach ($myChanges as $cr):
                    $crMeta = $crTypeLabels[$cr['change_type']] ?? ['label'=>ucfirst($cr['change_type']),'icon'=>'📋'];
                    $sIcon  = $statusIcons[$cr['status']] ?? '•';
                ?>
                <div class="cr-item">
                    <div style="flex-shrink:0;font-size:20px;margin-top:1px;"><?= $crMeta['icon'] ?></div>
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;flex-wrap:wrap;">
                            <span class="cr-type-chip" style="background:var(--bg-secondary);color:var(--text-muted);">
                                <?= $crMeta['label'] ?>
                            </span>
                            <span class="cr-type-chip cr-status-<?= $cr['status'] ?>">
                                <?= $sIcon ?> <?= ucfirst($cr['status']) ?>
                            </span>
                        </div>
                        <div style="font-size:13px;color:var(--text-primary);line-height:1.4;">
                            <?= e(substr($cr['message'], 0, 70)) ?><?= strlen($cr['message']) > 70 ? '…' : '' ?>
                        </div>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:3px;">
                            <?= date('d M Y', strtotime($cr['created_at'])) ?>
                        </div>
                        <?php if ($cr['response']): ?>
                        <div style="margin-top:6px;padding:6px 10px;border-radius:6px;background:var(--bg-secondary);
                                    border-left:3px solid <?= $cr['status'] === 'approved' ? '#10b981' : ($cr['status'] === 'rejected' ? '#ef4444' : '#6b7280') ?>;">
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;
                                        color:var(--text-muted);margin-bottom:2px;">Scheduling Response</div>
                            <div style="font-size:12px;color:var(--text-primary);"><?= e(substr($cr['response'], 0, 80)) ?><?= strlen($cr['response']) > 80 ? '…' : '' ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /right sidebar -->
</div>

<script>
const typeConfig = {
    leave_request:  { hint: '<strong>Leave Request:</strong> Include your preferred dates and type of leave (annual, sick, study leave).', icon: '🏖️', label: 'Submit Leave Request', color: '#059669' },
    swap_request:   { hint: '<strong>Duty Swap:</strong> Specify the date(s) you want to swap, and if possible the crew member you want to swap with.', icon: '🔄', label: 'Submit Swap Request', color: '#d97706' },
    correction:     { hint: '<strong>Roster Correction:</strong> Describe the incorrect entry, the correct information, and the date(s) affected.', icon: '✏️', label: 'Submit Correction', color: '#3b82f6' },
    comment:        { hint: '<strong>Comment / Query:</strong> Ask a question or leave a general comment for scheduling to review.', icon: '💬', label: 'Send Comment', color: '#6b7280' },
};

function selectType(el, type) {
    document.querySelectorAll('.req-type-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('changeTypeHidden').value = type;
    const cfg = typeConfig[type];
    document.getElementById('typeHint').innerHTML = cfg.hint;
    document.getElementById('submitIcon').textContent = cfg.icon;
    document.getElementById('submitLabel').textContent = cfg.label;
    document.getElementById('reqSubmitBtn').style.background = cfg.color;
}

document.getElementById('reqMessage').addEventListener('input', function() {
    document.getElementById('charCount').textContent = this.value.length;
});
</script>
