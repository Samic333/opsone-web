<?php
/**
 * OpsOne — Reporter's View of a Single Safety Report (Tabbed)
 * Variables: $report (array), $threads (array, public only), $attachments (array),
 *            $statusHistory (array)
 */

$currentUserId = currentUser()['id'] ?? null;

// Active tab from query string
$activeTab = $_GET['tab'] ?? 'overview';
$validTabs = ['overview', 'discussion', 'attachments', 'history'];
if (!in_array($activeTab, $validTabs)) $activeTab = 'overview';

// Status helpers
$statusMeta = [
    'draft'              => ['color' => '#6b7280', 'label' => 'Draft',             'meaning' => 'Your report has not been submitted yet.'],
    'submitted'          => ['color' => '#3b82f6', 'label' => 'Submitted',         'meaning' => 'Your report has been received by the safety team.'],
    'under_review'       => ['color' => '#f59e0b', 'label' => 'Under Review',      'meaning' => 'A safety officer is reviewing your report.'],
    'investigation'      => ['color' => '#ef4444', 'label' => 'Investigation',     'meaning' => 'A formal investigation has been opened for this report.'],
    'action_in_progress' => ['color' => '#8b5cf6', 'label' => 'Action In Progress','meaning' => 'Safety actions are being taken in response to your report.'],
    'closed'             => ['color' => '#10b981', 'label' => 'Closed',            'meaning' => 'This report has been reviewed and closed.'],
    'reopened'           => ['color' => '#f59e0b', 'label' => 'Reopened',          'meaning' => 'This report has been reopened for further review.'],
];
$status   = $report['status'] ?? 'submitted';
$sm       = $statusMeta[$status] ?? ['color' => '#6b7280', 'label' => ucfirst($status), 'meaning' => ''];
$sc       = $sm['color'];

$typeLabels = [
    'general_hazard'          => 'General Hazard',
    'flight_crew_occurrence'  => 'Flight Crew Occurrence',
    'maintenance_engineering' => 'Maintenance Engineering',
    'ground_ops'              => 'Ground Ops',
    'quality'                 => 'Quality',
    'hse'                     => 'HSE',
    'tcas'                    => 'TCAS',
    'environmental'           => 'Environmental',
    'frat'                    => 'FRAT',
];

$riskCodeLabel = function(string $code): string {
    if (!$code) return '—';
    $sev = (int) substr($code, 0, 1);
    $lik = substr($code, 1, 1);
    $sevLabels = [5=>'Catastrophic',4=>'Hazardous',3=>'Major',2=>'Minor',1=>'Negligible'];
    $likLabels = ['A'=>'Frequent','B'=>'Occasional','C'=>'Remote','D'=>'Improbable','E'=>'Ext. Improbable'];
    return $code . ' — ' . ($sevLabels[$sev] ?? '') . ' / ' . ($likLabels[$lik] ?? '');
};
$riskCodeColor = function(string $code): string {
    if (!$code) return '#9ca3af';
    $sev = (int) substr($code, 0, 1);
    $lik = substr($code, 1, 1);
    $likIdx = ['A'=>0,'B'=>1,'C'=>2,'D'=>3,'E'=>4][$lik] ?? 4;
    if ($sev >= 4 && $likIdx <= 2) return '#dc2626';
    if ($sev >= 3 && $likIdx <= 2) return '#f59e0b';
    if ($sev === 2 && $likIdx <= 1) return '#f59e0b';
    return '#10b981';
};

$fileIcon = function(string $type): string {
    if (str_starts_with($type, 'image/')) return '🖼️';
    if (str_contains($type, 'pdf'))       return '📄';
    if (str_starts_with($type, 'video/')) return '🎥';
    return '📎';
};

$baseUrl = '/safety/report/' . (int)($report['id'] ?? 0);
?>

<!-- BACK LINK -->
<div style="margin-bottom:18px;">
    <a href="/safety/my-reports" style="color:var(--text-muted); text-decoration:none; font-size:13px; font-weight:600; display:inline-flex; align-items:center; gap:6px;">
        ← Back to My Reports
    </a>
</div>

<!-- ═══════════════════════════════
     REPORT HEADER
     ═══════════════════════════════ -->
<div style="margin-bottom:20px;">
    <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:8px;">
        <h2 style="margin:0; font-size:22px; font-family:monospace; font-weight:800; letter-spacing:.04em; color:var(--text-primary);">
            <?= e($report['reference_no'] ?? '—') ?>
        </h2>
        <span style="
            padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700;
            background:<?= $sc ?>22; color:<?= $sc ?>; border:1px solid <?= $sc ?>55;">
            <?= e($sm['label']) ?>
        </span>
        <?php if (!empty($report['report_type'])): ?>
        <span style="
            padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600;
            background:var(--bg-secondary,#f1f5f9); color:var(--text-muted); border:1px solid var(--border);">
            <?= e($typeLabels[$report['report_type']] ?? ucwords(str_replace('_',' ',$report['report_type']))) ?>
        </span>
        <?php endif; ?>
    </div>
    <p class="text-sm text-muted" style="margin:0;">
        Submitted <?= !empty($report['submitted_at']) ? date('d M Y, H:i', strtotime($report['submitted_at'])) . ' UTC' : (isset($report['created_at']) ? date('d M Y, H:i', strtotime($report['created_at'])) . ' UTC' : '—') ?>
    </p>
</div>

<!-- ═══════════════════════════════
     TAB BAR
     ═══════════════════════════════ -->
<?php
$tabs = [
    'overview'    => 'Overview',
    'discussion'  => 'Discussion' . (count($threads ?? []) > 0 ? ' (' . count($threads) . ')' : ''),
    'attachments' => 'Attachments' . (count($attachments ?? []) > 0 ? ' (' . count($attachments) . ')' : ''),
    'history'     => 'History',
];
?>
<div style="display:flex; gap:0; border-bottom:2px solid var(--border); margin-bottom:24px; overflow-x:auto;">
    <?php foreach ($tabs as $tabKey => $tabLabel): ?>
    <a href="<?= e($baseUrl . '?tab=' . $tabKey) ?>" style="
        padding:10px 20px; font-size:13px; font-weight:600; text-decoration:none;
        border-bottom:2px solid <?= $activeTab === $tabKey ? 'var(--accent-blue,#3b82f6)' : 'transparent' ?>;
        color:<?= $activeTab === $tabKey ? 'var(--accent-blue,#3b82f6)' : 'var(--text-muted)' ?>;
        margin-bottom:-2px; white-space:nowrap;
        transition:color 0.15s;">
        <?= e($tabLabel) ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- ═══════════════════════════════
     TAB: OVERVIEW
     ═══════════════════════════════ -->
<?php if ($activeTab === 'overview'): ?>
<div style="display:grid; grid-template-columns:1fr 340px; gap:24px; align-items:start;">

    <!-- LEFT COLUMN -->
    <div style="display:flex; flex-direction:column; gap:20px;">

        <!-- Type badges -->
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <?php if (!empty($report['report_type'])): ?>
            <span style="padding:4px 14px; border-radius:20px; font-size:12px; font-weight:600; background:rgba(59,130,246,0.1); color:#2563eb; border:1px solid rgba(59,130,246,0.25);">
                <?= e($typeLabels[$report['report_type']] ?? ucwords(str_replace('_',' ',$report['report_type']))) ?>
            </span>
            <?php endif; ?>
            <?php if (!empty($report['occurrence_type'])): ?>
            <span style="padding:4px 14px; border-radius:20px; font-size:12px; font-weight:600; background:rgba(100,116,139,0.1); color:#475569; border:1px solid rgba(100,116,139,0.2);">
                <?= e(ucfirst($report['occurrence_type'])) ?>
            </span>
            <?php endif; ?>
        </div>

        <!-- Event Details Card -->
        <div class="card" style="padding:20px;">
            <h4 style="margin:0 0 16px; font-size:14px; font-weight:700; color:var(--text-primary);">Event Details</h4>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px 24px;">
                <div>
                    <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:.05em; margin-bottom:3px;">Event Date</div>
                    <div style="font-size:14px; font-weight:600; color:var(--text-primary);">
                        <?= !empty($report['event_date']) ? date('d M Y', strtotime($report['event_date'])) : '—' ?>
                    </div>
                </div>
                <div>
                    <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:.05em; margin-bottom:3px;">UTC Time</div>
                    <div style="font-size:14px; font-weight:600; color:var(--text-primary);">
                        <?= e($report['event_utc_time'] ?? '—') ?>
                    </div>
                </div>
                <div>
                    <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:.05em; margin-bottom:3px;">Location</div>
                    <div style="font-size:14px; font-weight:600; color:var(--text-primary);">
                        <?= e($report['location_name'] ?? $report['location'] ?? '—') ?>
                    </div>
                </div>
                <div>
                    <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:.05em; margin-bottom:3px;">ICAO Code</div>
                    <div style="font-size:14px; font-weight:600; font-family:monospace; color:var(--text-primary);">
                        <?= e($report['icao_code'] ?? '—') ?>
                    </div>
                </div>
                <?php if (!empty($report['event_type'])): ?>
                <div style="grid-column:span 2;">
                    <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:.05em; margin-bottom:3px;">Event Type</div>
                    <div style="font-size:14px; font-weight:600; color:var(--text-primary);">
                        <?= e(ucwords(str_replace('_',' ',$report['event_type']))) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Risk Assessment Card -->
        <?php if (!empty($report['initial_risk_code']) || !empty($report['initial_risk_score'])): ?>
        <?php
        $rCode  = $report['initial_risk_code'] ?? '';
        $rColor = $rCode ? $riskCodeColor($rCode) : '#9ca3af';
        ?>
        <div class="card" style="padding:20px;">
            <h4 style="margin:0 0 14px; font-size:14px; font-weight:700; color:var(--text-primary);">Risk Assessment</h4>
            <div style="display:flex; align-items:center; gap:14px;">
                <div style="width:48px; height:48px; border-radius:10px; background:<?= $rColor ?>; display:flex; align-items:center; justify-content:center; font-size:16px; font-weight:800; color:#fff; flex-shrink:0;">
                    <?= e($rCode ?: ($report['initial_risk_score'] ?? '—')) ?>
                </div>
                <div>
                    <div style="font-size:14px; font-weight:700; color:var(--text-primary);">
                        <?= $rCode ? e($riskCodeLabel($rCode)) : ('Risk Score: ' . e($report['initial_risk_score'] ?? '—') . '/5') ?>
                    </div>
                    <div style="
                        display:inline-block; margin-top:4px;
                        padding:2px 10px; border-radius:20px; font-size:11px; font-weight:700;
                        background:<?= $rColor ?>22; color:<?= $rColor ?>; border:1px solid <?= $rColor ?>55;">
                        Initial Reporter Assessment
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Narrative -->
        <div class="card" style="padding:20px;">
            <h4 style="margin:0 0 6px; font-size:14px; font-weight:700; color:var(--text-primary);">
                <?= e($report['title'] ?? 'Narrative') ?>
            </h4>
            <p class="text-xs text-muted" style="margin:0 0 14px;">Full description as submitted</p>
            <div style="
                background:var(--bg-secondary,#f9fafb);
                border:1px solid var(--border);
                border-radius:var(--radius-sm);
                padding:16px 18px;
                font-size:14px; line-height:1.7;
                color:var(--text-primary);
                white-space:pre-wrap;">
                <?= e($report['description'] ?? '—') ?>
            </div>
        </div>

    </div><!-- /left -->

    <!-- RIGHT SIDEBAR -->
    <div style="display:flex; flex-direction:column; gap:16px;">

        <!-- Status Card -->
        <div class="card" style="padding:18px;">
            <h4 style="margin:0 0 12px; font-size:13px; text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted);">Report Status</h4>
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                <span style="width:10px; height:10px; border-radius:50%; background:<?= $sc ?>; flex-shrink:0; display:inline-block;"></span>
                <span style="font-size:16px; font-weight:700; color:<?= $sc ?>;"><?= e($sm['label']) ?></span>
            </div>
            <p class="text-sm text-muted" style="margin:0 0 10px; line-height:1.5;"><?= e($sm['meaning']) ?></p>
            <?php if (in_array($status, ['under_review','investigation','action_in_progress'])): ?>
            <div style="padding:10px 12px; background:rgba(59,130,246,0.06); border:1px solid rgba(59,130,246,0.18); border-radius:var(--radius-sm);">
                <p class="text-xs" style="margin:0; line-height:1.5; color:var(--text-primary);">
                    A safety officer is reviewing your report. You'll be notified of any questions or updates.
                </p>
            </div>
            <?php endif; ?>
        </div>

        <!-- What happens next? -->
        <div class="card" style="padding:18px;">
            <h4 style="margin:0 0 12px; font-size:13px; text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted);">What happens next?</h4>
            <?php
            $nextSteps = [
                'submitted'          => 'Your report is in the safety team\'s queue. They will review it and may contact you for more information.',
                'under_review'       => 'A safety officer has picked up your report and is reviewing the details. No action is required from you unless they message you.',
                'investigation'      => 'A formal safety investigation has been opened. The investigation is handled confidentially by the safety team.',
                'action_in_progress' => 'Safety actions are being taken based on your report. You will be notified once the case is closed.',
                'closed'             => 'This report has been reviewed and closed. Thank you for contributing to a safer operation.',
                'reopened'           => 'This report has been reopened. The safety team will be in touch if they need further information.',
                'draft'              => 'Your report has not been submitted. Return to the form to complete and submit it.',
            ];
            $nextText = $nextSteps[$status] ?? 'The safety team will review your report and be in touch if needed.';
            ?>
            <p class="text-sm" style="margin:0; line-height:1.6; color:var(--text-primary);"><?= e($nextText) ?></p>
        </div>

        <!-- Quick actions -->
        <div class="card" style="padding:18px;">
            <h4 style="margin:0 0 12px; font-size:13px; text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted);">Actions</h4>
            <a href="<?= e($baseUrl . '?tab=discussion') ?>" class="btn btn-outline btn-sm" style="width:100%; text-align:center; margin-bottom:8px; display:block;">
                💬 Message Safety Team
            </a>
            <a href="<?= e($baseUrl . '?tab=attachments') ?>" class="btn btn-outline btn-sm" style="width:100%; text-align:center; display:block;">
                📎 View Attachments (<?= count($attachments ?? []) ?>)
            </a>
        </div>

        <!-- Confidentiality note -->
        <div style="padding:12px 14px; background:rgba(59,130,246,0.04); border:1px solid rgba(59,130,246,0.18); border-radius:var(--radius-md);">
            <p class="text-xs text-muted" style="margin:0; line-height:1.5;">
                🔒 This report is encrypted and accessible only to authorised safety personnel. Internal notes and investigation details are not visible to you.
            </p>
        </div>

    </div><!-- /sidebar -->

</div><!-- /overview grid -->
<?php endif; ?>

<!-- ═══════════════════════════════
     TAB: DISCUSSION
     ═══════════════════════════════ -->
<?php if ($activeTab === 'discussion'): ?>
<div style="max-width:680px;">
    <p class="text-sm text-muted" style="margin:0 0 20px; line-height:1.5;">
        Messages here are visible to you and the safety team. Replies are not real-time — the team will respond when available.
    </p>

    <!-- Thread messages -->
    <?php if (empty($threads)): ?>
    <div style="text-align:center; padding:40px 0; color:var(--text-muted);">
        <div style="font-size:32px; margin-bottom:10px;">💬</div>
        <p class="text-sm">No messages yet. Send a message below if you have additional information.</p>
    </div>
    <?php else: ?>
    <div style="display:flex; flex-direction:column; gap:14px; margin-bottom:28px;">
        <?php foreach ($threads as $msg):
            $isOwn    = (int)($msg['author_id'] ?? $msg['user_id'] ?? 0) === (int)$currentUserId;
            $initials = strtoupper(substr($msg['author_name'] ?? 'U', 0, 1));
            $isTeam   = !$isOwn;
        ?>
        <div style="display:flex; gap:10px; <?= $isOwn ? 'flex-direction:row-reverse;' : '' ?>">
            <!-- Avatar -->
            <div style="
                flex-shrink:0; width:34px; height:34px; border-radius:50%;
                background:<?= $isOwn ? 'var(--accent-blue,#3b82f6)' : 'var(--bg-secondary,#e5e7eb)' ?>;
                color:<?= $isOwn ? '#fff' : 'var(--text-primary)' ?>;
                display:flex; align-items:center; justify-content:center;
                font-size:13px; font-weight:700; flex-shrink:0;">
                <?= e($initials) ?>
            </div>
            <!-- Bubble -->
            <div style="max-width:72%; display:flex; flex-direction:column; gap:4px; <?= $isOwn ? 'align-items:flex-end;' : '' ?>">
                <div style="font-size:11px; color:var(--text-muted);">
                    <?php if ($isTeam): ?>
                        <strong style="color:var(--text-primary);">Safety Team</strong>
                        <?php if (!empty($msg['author_name'])): ?> · <?= e($msg['author_name']) ?><?php endif; ?>
                    <?php else: ?>
                        You
                    <?php endif; ?>
                    <?= !empty($msg['created_at']) ? ' · ' . date('d M Y, H:i', strtotime($msg['created_at'])) : '' ?>
                </div>
                <div style="
                    background:<?= $isOwn ? 'var(--accent-blue,#3b82f6)' : 'var(--bg-secondary,#f1f5f9)' ?>;
                    color:<?= $isOwn ? '#fff' : 'var(--text-primary)' ?>;
                    padding:10px 14px;
                    border-radius:<?= $isOwn ? '12px 4px 12px 12px' : '4px 12px 12px 12px' ?>;
                    font-size:14px; line-height:1.6; white-space:pre-wrap;">
                    <?= e($msg['body'] ?? '') ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Reply form -->
    <div class="card" style="padding:18px;">
        <h4 style="margin:0 0 12px; font-size:14px; font-weight:700; color:var(--text-primary);">Add Information or Reply</h4>
        <form method="POST" action="<?= e($baseUrl . '/reply') ?>">
            <?= csrfField() ?>
            <div class="form-group" style="margin-bottom:12px;">
                <textarea name="body" class="form-control" rows="4" required
                          placeholder="Ask a question or add additional information..."
                          style="resize:vertical;"></textarea>
            </div>
            <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                <p class="text-xs text-muted" style="margin:0;">Replies are visible to the safety team handling your report.</p>
                <button type="submit" class="btn btn-primary btn-sm">Send Message →</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════
     TAB: ATTACHMENTS
     ═══════════════════════════════ -->
<?php if ($activeTab === 'attachments'): ?>
<?php if (empty($attachments)): ?>
<div style="text-align:center; padding:40px 0; color:var(--text-muted);">
    <div style="font-size:32px; margin-bottom:10px;">📎</div>
    <p class="text-sm">No attachments on this report.</p>
</div>
<?php else: ?>
<div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:14px;">
    <?php foreach ($attachments as $att):
        $mime = $att['file_type'] ?? $att['mime_type'] ?? '';
        $icon = $fileIcon($mime);
        $size = isset($att['file_size']) ? ($att['file_size'] > 1048576 ? round($att['file_size']/1048576,1).' MB' : round($att['file_size']/1024).' KB') : '—';
    ?>
    <div class="card" style="padding:16px; display:flex; gap:12px; align-items:flex-start;">
        <span style="font-size:28px; line-height:1; flex-shrink:0;"><?= $icon ?></span>
        <div style="flex:1; min-width:0;">
            <div style="font-size:13px; font-weight:600; color:var(--text-primary); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                <?= e($att['file_name'] ?? $att['original_name'] ?? 'File') ?>
            </div>
            <div class="text-xs text-muted" style="margin-top:3px;"><?= e($size) ?></div>
            <?php if (!empty($att['created_at'])): ?>
            <div class="text-xs text-muted" style="margin-top:2px;">
                <?= date('d M Y', strtotime($att['created_at'])) ?>
                <?php if (!empty($att['uploader_name'])): ?>— <?= e($att['uploader_name']) ?><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- ═══════════════════════════════
     TAB: HISTORY
     ═══════════════════════════════ -->
<?php if ($activeTab === 'history'): ?>
<div style="max-width:560px;">
    <div style="position:relative; padding-left:28px; border-left:2px solid var(--border); display:flex; flex-direction:column; gap:20px;">

        <!-- Initial submission -->
        <div style="position:relative;">
            <div style="position:absolute; left:-35px; top:2px; width:12px; height:12px; border-radius:50%; background:var(--accent-blue,#3b82f6); outline:3px solid var(--bg-card);"></div>
            <div class="text-xs text-muted" style="margin-bottom:3px;">
                <?= !empty($report['created_at']) ? date('d M Y, H:i', strtotime($report['created_at'])) . ' UTC' : '—' ?>
            </div>
            <div style="font-size:14px; font-weight:700; color:var(--text-primary);">Report Created</div>
            <?php if (!empty($report['submitted_at'])): ?>
            <div style="font-size:13px; color:var(--text-muted); margin-top:2px;">
                Submitted <?= date('d M Y, H:i', strtotime($report['submitted_at'])) ?> UTC
            </div>
            <?php endif; ?>
        </div>

        <!-- Status history entries -->
        <?php foreach (($statusHistory ?? []) as $sh):
            $shStatus = $sh['to_status'] ?? $sh['new_status'] ?? '';
            $shMeta   = $statusMeta[$shStatus] ?? ['color' => '#9ca3af', 'label' => ucfirst(str_replace('_',' ',$shStatus))];
        ?>
        <div style="position:relative;">
            <div style="position:absolute; left:-35px; top:2px; width:12px; height:12px; border-radius:50%; background:<?= $shMeta['color'] ?>; outline:3px solid var(--bg-card);"></div>
            <div class="text-xs text-muted" style="margin-bottom:3px;">
                <?= !empty($sh['created_at']) ? date('d M Y, H:i', strtotime($sh['created_at'])) . ' UTC' : '—' ?>
            </div>
            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                <span style="font-size:14px; font-weight:700; color:var(--text-primary);">
                    Status → <?= e($shMeta['label']) ?>
                </span>
                <span style="padding:2px 8px; border-radius:20px; font-size:11px; font-weight:700; background:<?= $shMeta['color'] ?>22; color:<?= $shMeta['color'] ?>;">
                    <?= e($shMeta['label']) ?>
                </span>
            </div>
            <?php if (!empty($sh['from_status'])): ?>
            <div class="text-xs text-muted" style="margin-top:2px;">
                Was: <?= e(ucfirst(str_replace('_',' ',$sh['from_status']))) ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($sh['comment'])): ?>
            <div style="margin-top:6px; padding:8px 12px; background:var(--bg-secondary,#f9fafb); border-radius:var(--radius-sm); border:1px solid var(--border); font-size:13px; color:var(--text-primary);">
                <?= e($sh['comment']) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <!-- Current status dot -->
        <div style="position:relative;">
            <div style="position:absolute; left:-35px; top:2px; width:12px; height:12px; border-radius:50%; background:<?= $sc ?>; outline:3px solid var(--bg-card); box-shadow:0 0 0 3px <?= $sc ?>44;"></div>
            <div class="text-xs text-muted" style="margin-bottom:3px;">Current</div>
            <div style="font-size:14px; font-weight:700; color:<?= $sc ?>;"><?= e($sm['label']) ?></div>
        </div>

    </div>
</div>
<?php endif; ?>
