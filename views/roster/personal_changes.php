<?php
/**
 * Personal Change Requests page (leave / correction)
 *
 * Variables:
 *   $changeType        — 'leave_request' | 'correction'
 *   $open, $closed     — arrays of roster_changes rows
 *   $pageTitle         — page heading
 *   $pageSubtitle      — page subtitle
 *
 * The form posts to /roster/changes/request with a hidden change_type
 * matching this page, so the backend wiring is identical to the form
 * embedded on /my-roster.
 */
$isLeave = $changeType === 'leave_request';
$verb    = $isLeave ? 'Leave Request' : 'Roster Correction';
$accent  = $isLeave ? '#059669' : '#3b82f6';
$hint    = $isLeave
    ? 'Include preferred dates and the type of leave (annual, sick, study).'
    : 'Describe the incorrect entry, the correct information, and the date(s) affected.';
?>
<style>
.lc-grid{display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start;}
@media (max-width:980px){.lc-grid{grid-template-columns:1fr;}}
.lc-list-card{background:var(--bg-card);border:1px solid var(--border);border-radius:12px;overflow:hidden;}
.lc-list-hdr{padding:14px 18px;border-bottom:1px solid var(--border);background:var(--bg-secondary);
    display:flex;align-items:center;justify-content:space-between;}
.lc-list-hdr strong{font-size:13px;}
.lc-empty{padding:32px;text-align:center;color:var(--text-muted);font-size:13px;}
.lc-row{padding:14px 18px;border-bottom:1px solid var(--border);}
.lc-row:last-child{border-bottom:none;}
.lc-row-meta{display:flex;align-items:center;gap:8px;margin-bottom:6px;flex-wrap:wrap;}
.lc-status-chip{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;
    padding:2px 8px;border-radius:4px;}
.lc-status-pending{background:#fef9c3;color:#92400e;}
.lc-status-approved{background:#d1fae5;color:#065f46;}
.lc-status-rejected{background:#fee2e2;color:#991b1b;}
.lc-status-noted{background:#f3f4f6;color:#374151;}
.lc-row-msg{font-size:13px;color:var(--text-primary);line-height:1.5;}
.lc-row-date{font-size:11px;color:var(--text-muted);margin-top:4px;}
.lc-row-response{margin-top:8px;padding:8px 12px;border-radius:6px;
    background:var(--bg-secondary);font-size:12px;color:var(--text-primary);
    border-left:3px solid var(--text-muted);}
.lc-form-card{background:var(--bg-card);border:1px solid var(--border);border-radius:12px;overflow:hidden;}
.lc-form-hdr{padding:14px 18px;border-bottom:1px solid var(--border);background:var(--bg-secondary);}
.lc-form-body{padding:18px;}
.lc-form-body label{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;
    color:var(--text-muted);display:block;margin-bottom:6px;}
.lc-form-body textarea{width:100%;min-height:140px;padding:10px 12px;border-radius:8px;
    border:1px solid var(--border);background:var(--bg-secondary);
    color:var(--text-primary);font-size:14px;font-family:inherit;resize:vertical;}
.lc-form-body button{margin-top:14px;width:100%;padding:11px;border:none;border-radius:8px;
    font-size:14px;font-weight:700;color:#fff;cursor:pointer;}
.lc-form-body button:hover{opacity:.9;}
.lc-hint{font-size:11px;color:var(--text-muted);margin-top:6px;line-height:1.4;}
</style>

<div class="lc-grid">
    <!-- LEFT: requests list -->
    <div>
        <?php if (!empty($open)): ?>
        <div class="lc-list-card" style="margin-bottom:16px;">
            <div class="lc-list-hdr">
                <strong>Open</strong>
                <span class="text-xs text-muted"><?= count($open) ?> awaiting response</span>
            </div>
            <?php foreach ($open as $r): ?>
                <div class="lc-row">
                    <div class="lc-row-meta">
                        <span class="lc-status-chip lc-status-<?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span>
                        <span class="text-xs text-muted"><?= e(date('d M Y · H:i', strtotime($r['created_at']))) ?></span>
                    </div>
                    <div class="lc-row-msg"><?= nl2br(e($r['message'])) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="lc-list-card">
            <div class="lc-list-hdr">
                <strong>History</strong>
                <span class="text-xs text-muted"><?= count($closed) ?> closed</span>
            </div>
            <?php if (empty($closed)): ?>
                <div class="lc-empty">
                    <?= $isLeave ? 'No past leave requests yet.' : 'No past corrections yet.' ?>
                </div>
            <?php else: ?>
                <?php foreach ($closed as $r):
                    $border = match ($r['status']) {
                        'approved' => '#10b981',
                        'rejected' => '#ef4444',
                        default    => '#6b7280',
                    };
                ?>
                <div class="lc-row">
                    <div class="lc-row-meta">
                        <span class="lc-status-chip lc-status-<?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span>
                        <span class="text-xs text-muted"><?= e(date('d M Y', strtotime($r['created_at']))) ?></span>
                    </div>
                    <div class="lc-row-msg"><?= nl2br(e($r['message'])) ?></div>
                    <?php if (!empty($r['response'])): ?>
                        <div class="lc-row-response" style="border-left-color:<?= $border ?>;">
                            <strong>Scheduling response:</strong>
                            <?= nl2br(e($r['response'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- RIGHT: new request form -->
    <div>
        <div class="lc-form-card">
            <div class="lc-form-hdr">
                <strong style="font-size:13px;">New <?= e($verb) ?></strong>
            </div>
            <div class="lc-form-body">
                <form method="POST" action="/roster/changes/request">
                    <?= csrfField() ?>
                    <input type="hidden" name="change_type" value="<?= e($changeType) ?>">
                    <input type="hidden" name="redirect" value="<?= e($isLeave ? '/leave-requests' : '/roster/corrections') ?>">

                    <label for="lcMessage">Message</label>
                    <textarea id="lcMessage" name="message" required maxlength="1000"
                              placeholder="<?= e($hint) ?>"></textarea>
                    <div class="lc-hint"><?= e($hint) ?> Average response: 24–48 hours.</div>

                    <button type="submit" style="background:<?= $accent ?>;">Submit <?= e($verb) ?></button>
                </form>
            </div>
        </div>
    </div>
</div>
