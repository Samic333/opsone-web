<?php
/**
 * Personal Change Requests page (leave / correction) — premium redesign.
 *
 * Variables:
 *   $changeType        — 'leave_request' | 'correction'
 *   $open, $closed     — arrays of roster_changes rows
 *   $pageTitle         — page heading
 *   $pageSubtitle      — page subtitle
 *
 * Backend wiring is unchanged: form posts to /roster/changes/request with a
 * hidden change_type. The structured fields (leave type, start/end dates,
 * category) are concatenated into the `message` body so we don't need new DB
 * columns — the backend remains a free-form text store.
 */
$isLeave  = $changeType === 'leave_request';
$verb     = $isLeave ? 'Leave Request' : 'Roster Correction';
$accent   = $isLeave ? 'var(--accent-green)' : 'var(--accent-blue)';

$rows = array_merge($open ?? [], $closed ?? []);
usort($rows, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

// Status filter from query string — premium tabs.
$validFilters = ['all','pending','approved','rejected','noted'];
$filter = $_GET['status'] ?? 'all';
if (!in_array($filter, $validFilters, true)) $filter = 'all';

$counts = ['all' => count($rows), 'pending' => 0, 'approved' => 0, 'rejected' => 0, 'noted' => 0];
foreach ($rows as $r) {
    if (isset($counts[$r['status']])) $counts[$r['status']]++;
}

$filtered = $filter === 'all' ? $rows
    : array_values(array_filter($rows, fn($r) => $r['status'] === $filter));

// Status display mapping. Backend stores 'pending'; UX calls it "Submitted /
// Under Review" with a single chip until the admin moves it to a final state.
$statusLabel = [
    'pending'  => 'Submitted',
    'approved' => 'Approved',
    'rejected' => 'Rejected',
    'noted'    => 'Noted',
];

$today = date('Y-m-d');
?>
<style>
.lc-shell{display:grid;grid-template-columns:minmax(0,1fr) 380px;gap:24px;align-items:start;}
@media (max-width: 1100px){ .lc-shell{ grid-template-columns: 1fr; } }

.lc-summary{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:18px;}
.lc-stat{
    background:var(--bg-card);border:1px solid var(--border-color);border-radius:10px;padding:14px 16px;
    position:relative;overflow:hidden;
}
.lc-stat::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:var(--accent-blue);}
.lc-stat.--pending::before{background:var(--accent-yellow);}
.lc-stat.--approved::before{background:var(--accent-green);}
.lc-stat.--rejected::before{background:var(--accent-red);}
.lc-stat-l{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-tertiary);}
.lc-stat-v{font-size:24px;font-weight:800;color:var(--text-primary);margin-top:4px;line-height:1;}

.lc-tabs{display:flex;gap:4px;background:var(--bg-secondary);border:1px solid var(--border-color);
    border-radius:10px;padding:4px;margin-bottom:14px;overflow-x:auto;}
.lc-tabs a{padding:7px 14px;font-size:12px;font-weight:600;color:var(--text-secondary);
    text-decoration:none;border-radius:7px;transition:all .14s;white-space:nowrap;}
.lc-tabs a.is-active{background:var(--bg-card);color:var(--text-primary);box-shadow:0 1px 2px rgba(0,0,0,.3);}
.lc-tabs .pill{display:inline-block;margin-left:6px;padding:1px 7px;border-radius:4px;
    background:var(--bg-card);font-size:10px;font-weight:700;color:var(--text-tertiary);}

.lc-list-card{background:var(--bg-card);border:1px solid var(--border-color);border-radius:12px;overflow:hidden;}
.lc-empty{padding:60px 32px;text-align:center;color:var(--text-tertiary);font-size:13px;}
.lc-empty svg{display:block;margin:0 auto 14px;opacity:.5;}

.lc-row{padding:18px 22px;border-bottom:1px solid var(--border-light);}
.lc-row:last-child{border-bottom:none;}
.lc-row-top{display:flex;align-items:center;gap:10px;margin-bottom:8px;flex-wrap:wrap;}
.lc-status{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;
    padding:3px 9px;border-radius:5px;}
.lc-status.--pending{background:rgba(245,158,11,.15);color:#fde68a;border:1px solid rgba(245,158,11,.35);}
.lc-status.--approved{background:rgba(16,185,129,.15);color:#a7f3d0;border:1px solid rgba(16,185,129,.35);}
.lc-status.--rejected{background:rgba(239,68,68,.15);color:#fecaca;border:1px solid rgba(239,68,68,.35);}
.lc-status.--noted{background:rgba(139,92,246,.12);color:#ddd6fe;border:1px solid rgba(139,92,246,.35);}
.lc-row-when{font-size:11px;color:var(--text-tertiary);}
.lc-row-msg{font-size:13px;color:var(--text-primary);line-height:1.5;
    background:var(--bg-secondary);border:1px solid var(--border-light);
    border-radius:8px;padding:12px 14px;white-space:pre-wrap;}
.lc-row-resp{margin-top:10px;padding:12px 14px;border-radius:8px;background:var(--bg-secondary);
    font-size:13px;color:var(--text-primary);line-height:1.5;
    border-left:3px solid var(--text-tertiary);}
.lc-row-resp.--approved{border-left-color:var(--accent-green);}
.lc-row-resp.--rejected{border-left-color:var(--accent-red);}
.lc-row-resp-l{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;
    color:var(--text-tertiary);margin-bottom:4px;}

/* Form card */
.lc-form-card{background:var(--bg-card);border:1px solid var(--border-color);border-radius:12px;overflow:hidden;}
.lc-form-hdr{padding:14px 18px;border-bottom:1px solid var(--border-light);background:var(--bg-secondary);
    display:flex;align-items:center;gap:10px;}
.lc-form-hdr-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;
    color:#fff;flex-shrink:0;background:<?= $accent ?>;}
.lc-form-body{padding:18px;}
.lc-form-body label{display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;
    color:var(--text-tertiary);margin-bottom:6px;margin-top:14px;}
.lc-form-body label:first-of-type{margin-top:0;}
.lc-form-body input, .lc-form-body select, .lc-form-body textarea{
    width:100%;padding:10px 12px;border-radius:8px;border:1px solid var(--border-color);
    background:var(--bg-input);color:var(--text-primary);font-size:14px;font-family:inherit;
}
.lc-form-body textarea{min-height:110px;resize:vertical;}
.lc-form-body .row-2{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.lc-form-body button{margin-top:18px;width:100%;padding:11px;border:none;border-radius:8px;
    font-size:14px;font-weight:700;color:#fff;cursor:pointer;background:<?= $accent ?>;
    transition:opacity .14s;display:flex;align-items:center;justify-content:center;gap:8px;}
.lc-form-body button:hover{opacity:.9;}
.lc-hint{font-size:11px;color:var(--text-tertiary);margin-top:6px;line-height:1.4;}
.lc-err{display:none;margin-top:10px;padding:10px 12px;border-radius:8px;
    background:rgba(239,68,68,.1);color:#fecaca;border:1px solid rgba(239,68,68,.3);font-size:12px;}
.lc-err.is-shown{display:block;}
</style>

<div class="lc-shell">
    <!-- LEFT: history + filter -->
    <div>
        <!-- Stat strip -->
        <div class="lc-summary">
            <div class="lc-stat">
                <div class="lc-stat-l">All</div>
                <div class="lc-stat-v"><?= (int)$counts['all'] ?></div>
            </div>
            <div class="lc-stat --pending">
                <div class="lc-stat-l">Submitted</div>
                <div class="lc-stat-v"><?= (int)$counts['pending'] ?></div>
            </div>
            <div class="lc-stat --approved">
                <div class="lc-stat-l">Approved</div>
                <div class="lc-stat-v"><?= (int)$counts['approved'] ?></div>
            </div>
            <div class="lc-stat --rejected">
                <div class="lc-stat-l">Rejected</div>
                <div class="lc-stat-v"><?= (int)$counts['rejected'] ?></div>
            </div>
        </div>

        <!-- Status tabs -->
        <div class="lc-tabs" role="tablist">
            <?php foreach ($validFilters as $f):
                $label = $f === 'all' ? 'All' : ucfirst($f);
                if ($f === 'pending') $label = 'Submitted';
            ?>
            <a href="?status=<?= e($f) ?>" class="<?= $filter === $f ? 'is-active' : '' ?>" role="tab">
                <?= $label ?><span class="pill"><?= (int)($counts[$f] ?? 0) ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- History list -->
        <div class="lc-list-card">
            <?php if (empty($filtered)): ?>
                <div class="lc-empty">
                    <?= sidebarIcon($isLeave ? 'calendar-days' : 'pencil', 32) ?>
                    <?php if ($filter === 'all'): ?>
                        <?= $isLeave ? 'No leave requests yet.' : 'No corrections submitted yet.' ?><br>
                        Use the form on the right to submit your first one.
                    <?php else: ?>
                        No <?= e(strtolower($filter === 'pending' ? 'submitted' : $filter)) ?>
                        <?= $isLeave ? 'leave requests' : 'corrections' ?>.
                    <?php endif; ?>
                </div>
            <?php else: foreach ($filtered as $r):
                $sLabel = $statusLabel[$r['status']] ?? ucfirst($r['status']);
            ?>
            <div class="lc-row">
                <div class="lc-row-top">
                    <span class="lc-status --<?= e($r['status']) ?>"><?= e($sLabel) ?></span>
                    <span class="lc-row-when">
                        Submitted <?= e(date('d M Y · H:i', strtotime($r['created_at']))) ?>
                        <?php if (!empty($r['responded_at'])): ?>
                            · responded <?= e(date('d M Y', strtotime($r['responded_at']))) ?>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="lc-row-msg"><?= e($r['message']) ?></div>
                <?php if (!empty($r['response'])): ?>
                <div class="lc-row-resp --<?= e($r['status']) ?>">
                    <div class="lc-row-resp-l">Scheduling response</div>
                    <?= e($r['response']) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- RIGHT: structured request form -->
    <div>
        <div class="lc-form-card">
            <div class="lc-form-hdr">
                <div class="lc-form-hdr-icon">
                    <?= sidebarIcon($isLeave ? 'calendar-days' : 'pencil', 16) ?>
                </div>
                <div>
                    <strong style="font-size:14px;color:var(--text-primary);">New <?= e($verb) ?></strong>
                    <div style="font-size:11px;color:var(--text-tertiary);margin-top:1px;">
                        <?= $isLeave ? 'Time off, sick or study leave' : 'Flag an incorrect roster entry' ?>
                    </div>
                </div>
            </div>
            <div class="lc-form-body">
                <form method="POST" action="/roster/changes/request" id="lcForm">
                    <?= csrfField() ?>
                    <input type="hidden" name="change_type" value="<?= e($changeType) ?>">
                    <input type="hidden" name="redirect" value="<?= e($isLeave ? '/leave-requests' : '/roster/corrections') ?>">
                    <input type="hidden" name="message" id="lcMessage">

                    <?php if ($isLeave): ?>
                        <label for="lcLeaveType">Leave type</label>
                        <select id="lcLeaveType" required>
                            <option value="">Select type…</option>
                            <option value="Annual leave">Annual leave</option>
                            <option value="Sick leave">Sick leave</option>
                            <option value="Study leave">Study leave</option>
                            <option value="Compassionate">Compassionate</option>
                            <option value="Unpaid">Unpaid</option>
                            <option value="Other">Other</option>
                        </select>

                        <div class="row-2">
                            <div>
                                <label for="lcStart">Start date</label>
                                <input type="date" id="lcStart" required min="<?= e($today) ?>">
                            </div>
                            <div>
                                <label for="lcEnd">End date</label>
                                <input type="date" id="lcEnd" required min="<?= e($today) ?>">
                            </div>
                        </div>

                        <label for="lcReason">Reason / notes <span style="color:var(--text-tertiary);font-weight:500;">(optional)</span></label>
                        <textarea id="lcReason" maxlength="800"
                                  placeholder="Add any context that will help scheduling — preferred dates, planned travel, medical info if relevant."></textarea>
                    <?php else: ?>
                        <label for="lcCorrDate">Roster date</label>
                        <input type="date" id="lcCorrDate" required>

                        <label for="lcCategory">Category</label>
                        <select id="lcCategory" required>
                            <option value="">Select category…</option>
                            <option value="Wrong duty type">Wrong duty type</option>
                            <option value="Wrong flight number">Wrong flight number</option>
                            <option value="Wrong times (STD/STA)">Wrong times (STD/STA)</option>
                            <option value="Missing assignment">Missing assignment</option>
                            <option value="Wrong base / station">Wrong base / station</option>
                            <option value="Wrong aircraft">Wrong aircraft</option>
                            <option value="Other">Other</option>
                        </select>

                        <label for="lcDesc">What is incorrect? <span style="color:var(--accent-red);">*</span></label>
                        <textarea id="lcDesc" required maxlength="800"
                                  placeholder="Describe what is wrong on this duty and what it should be — be as specific as possible (e.g. 'KQ410 STD shown as 0600, actual schedule is 0530')."></textarea>
                    <?php endif; ?>

                    <div class="lc-err" id="lcErr"></div>

                    <button type="submit">
                        <?= sidebarIcon($isLeave ? 'calendar-days' : 'pencil', 14) ?>
                        Submit <?= e($verb) ?>
                    </button>

                    <div class="lc-hint" style="margin-top:10px;text-align:center;">
                        Average response time 24–48 h.
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';
    const form = document.getElementById('lcForm');
    if (!form) return;
    const errEl = document.getElementById('lcErr');
    const messageHidden = document.getElementById('lcMessage');

    function showErr(msg) {
        errEl.textContent = msg;
        errEl.classList.add('is-shown');
    }
    function clearErr() { errEl.classList.remove('is-shown'); }

    form.addEventListener('submit', function (ev) {
        clearErr();
        let composed = '';
        <?php if ($isLeave): ?>
        const t = document.getElementById('lcLeaveType').value.trim();
        const s = document.getElementById('lcStart').value;
        const e = document.getElementById('lcEnd').value;
        const r = document.getElementById('lcReason').value.trim();
        if (!t || !s || !e) {
            ev.preventDefault();
            showErr('Pick a leave type and both dates.');
            return;
        }
        if (e < s) {
            ev.preventDefault();
            showErr('End date cannot be before the start date.');
            return;
        }
        const days = Math.round((new Date(e) - new Date(s)) / 86400000) + 1;
        composed = '[' + t + '] ' + s + ' → ' + e + ' (' + days + ' day' + (days === 1 ? '' : 's') + ')';
        if (r) composed += '\n\n' + r;
        <?php else: ?>
        const dt   = document.getElementById('lcCorrDate').value;
        const cat  = document.getElementById('lcCategory').value.trim();
        const desc = document.getElementById('lcDesc').value.trim();
        if (!dt || !cat || !desc) {
            ev.preventDefault();
            showErr('Roster date, category and description are required.');
            return;
        }
        composed = '[' + cat + '] Roster date ' + dt + '\n\n' + desc;
        <?php endif; ?>
        messageHidden.value = composed;
    });
})();
</script>
