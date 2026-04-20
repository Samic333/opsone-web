<?php
/**
 * OpsOne — Reporter's View of a Single Safety Report
 * Variables: $report (array), $threads (array, is_internal=false only), $attachments (array)
 */
$pageTitle    = $report['reference_no'] ?? 'Safety Report';
$pageSubtitle = 'Filed on ' . (isset($report['created_at']) ? date('d M Y, H:i', strtotime($report['created_at'])) : '—');

// Status colour helper
$statusColor = function(string $s): string {
    return match($s) {
        'submitted'          => '#3b82f6',
        'under_review'       => '#f59e0b',
        'investigation'      => '#ef4444',
        'action_in_progress' => '#8b5cf6',
        'closed'             => '#10b981',
        'reopened'           => '#f59e0b',
        default              => '#6b7280',
    };
};
$sc = $statusColor($report['status'] ?? '');

$currentUserId = currentUser()['id'] ?? null;
?>

<div style="margin-bottom:16px;">
    <a href="/safety/my-reports" class="btn btn-ghost btn-sm">← My Reports</a>
</div>

<!-- Report Header -->
<div style="display:flex; align-items:center; gap:12px; margin-bottom:24px; flex-wrap:wrap;">
    <h3 style="margin:0; font-size:20px; font-family:monospace; font-weight:700;"><?= e($report['reference_no'] ?? '—') ?></h3>
    <span class="status-badge" style="--badge-color:<?= $sc ?>; font-size:13px; padding:5px 12px;">
        <?= ucfirst(str_replace('_', ' ', $report['status'] ?? 'unknown')) ?>
    </span>
    <span style="font-size:12px; color:var(--text-muted); margin-left:auto;">
        <?= e(ucwords(str_replace('_', ' ', $report['report_type'] ?? ''))) ?>
    </span>
</div>

<div style="display:grid; grid-template-columns: 2fr 1fr; gap:24px; align-items:start;">

    <!-- LEFT: Report Details -->
    <div style="display:flex; flex-direction:column; gap:20px;">

        <!-- Meta Card -->
        <div class="card">
            <h4 style="margin:0 0 16px; font-size:15px;"><?= e($report['title'] ?? '') ?></h4>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px 20px; margin-bottom:16px;">
                <div>
                    <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:.05em; margin-bottom:2px;">Event Date</div>
                    <div class="text-sm" style="font-weight:600;"><?= !empty($report['event_date']) ? date('d M Y', strtotime($report['event_date'])) : '—' ?></div>
                </div>
                <div>
                    <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:.05em; margin-bottom:2px;">Location</div>
                    <div class="text-sm" style="font-weight:600;"><?= e($report['location'] ?? '—') ?></div>
                </div>
                <div>
                    <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:.05em; margin-bottom:2px;">Report Type</div>
                    <div class="text-sm" style="font-weight:600;"><?= e(ucwords(str_replace('_', ' ', $report['report_type'] ?? '—'))) ?></div>
                </div>
                <div>
                    <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:.05em; margin-bottom:2px;">Initial Risk</div>
                    <div class="text-sm" style="font-weight:600;"><?= e($report['initial_risk'] ?? '—') ?>/5</div>
                </div>
                <?php if (!empty($report['final_severity'])): ?>
                <div>
                    <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:.05em; margin-bottom:2px;">Classified Severity</div>
                    <div class="text-sm" style="font-weight:600;"><?= e(ucfirst($report['final_severity'])) ?></div>
                </div>
                <?php endif; ?>
            </div>

            <h4 style="margin:0 0 8px; font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted);">Description</h4>
            <div style="background:var(--bg-body); padding:14px 16px; border-radius:var(--radius-md); border:1px solid var(--border); line-height:1.65; white-space:pre-wrap; font-size:14px; color:var(--text-primary);"><?= e($report['description'] ?? '') ?></div>
        </div>

        <!-- Attachments -->
        <?php if (!empty($attachments)): ?>
        <div class="card">
            <h4 style="margin:0 0 14px; font-size:15px;">📎 Attachments</h4>
            <div style="display:flex; flex-direction:column; gap:8px;">
                <?php foreach ($attachments as $att): ?>
                <div style="display:flex; align-items:center; gap:12px; padding:10px 12px; background:var(--bg-body); border-radius:var(--radius-sm); border:1px solid var(--border);">
                    <span style="font-size:18px;"><?= str_starts_with($att['mime_type'] ?? '', 'image/') ? '🖼️' : (str_contains($att['mime_type'] ?? '', 'pdf') ? '📄' : '🎥') ?></span>
                    <div style="flex:1; min-width:0;">
                        <div class="text-sm" style="font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= e($att['original_name'] ?? $att['filename'] ?? 'File') ?></div>
                        <div class="text-xs text-muted"><?= isset($att['file_size']) ? round($att['file_size'] / 1024, 1) . ' KB' : '' ?></div>
                    </div>
                    <a href="/safety/attachments/<?= (int)$att['id'] ?>/download" class="btn btn-outline btn-xs">Download</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Discussion Thread -->
        <div class="card" id="discussion">
            <h4 style="margin:0 0 6px; font-size:15px;">💬 Discussion with Safety Team</h4>
            <p class="text-xs text-muted" style="margin:0 0 18px;">Messages here are visible to you and the safety team. This is not a real-time chat — the team will respond when available.</p>

            <?php if (empty($threads)): ?>
                <div style="padding:24px 0; text-align:center; color:var(--text-muted);">
                    <div style="font-size:28px; margin-bottom:8px;">💬</div>
                    <p class="text-sm">No messages yet. Send a message below if you have additional information.</p>
                </div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:12px; margin-bottom:20px;">
                    <?php foreach ($threads as $msg): ?>
                    <?php
                    $isOwn = (int)($msg['user_id'] ?? 0) === (int)$currentUserId;
                    $initials = strtoupper(substr($msg['author_name'] ?? 'U', 0, 1));
                    ?>
                    <div style="display:flex; gap:10px; <?= $isOwn ? 'flex-direction:row-reverse;' : '' ?>">
                        <div style="
                            flex-shrink:0; width:32px; height:32px; border-radius:50%;
                            background:<?= $isOwn ? 'var(--accent-blue)' : 'var(--bg-secondary)' ?>;
                            color:<?= $isOwn ? '#fff' : 'var(--text-primary)' ?>;
                            display:flex; align-items:center; justify-content:center;
                            font-size:12px; font-weight:700;">
                            <?= $initials ?>
                        </div>
                        <div style="max-width:70%; <?= $isOwn ? 'align-items:flex-end;' : '' ?> display:flex; flex-direction:column; gap:3px;">
                            <div style="font-size:11px; color:var(--text-muted); <?= $isOwn ? 'text-align:right;' : '' ?>">
                                <?= e($msg['author_name'] ?? ($isOwn ? 'You' : 'Safety Team')) ?>
                                · <?= !empty($msg['created_at']) ? date('d M Y, H:i', strtotime($msg['created_at'])) : '' ?>
                            </div>
                            <div style="
                                background:<?= $isOwn ? 'var(--accent-blue)' : 'var(--bg-secondary)' ?>;
                                color:<?= $isOwn ? '#fff' : 'var(--text-primary)' ?>;
                                padding:10px 14px; border-radius:<?= $isOwn ? '12px 4px 12px 12px' : '4px 12px 12px 12px' ?>;
                                font-size:14px; line-height:1.5; white-space:pre-wrap;">
                                <?= e($msg['body'] ?? '') ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Reply form -->
            <form method="POST" action="/safety/my-reports/<?= (int)$report['id'] ?>/thread">
                <?= csrfField() ?>
                <div class="form-group">
                    <textarea name="body" class="form-control" rows="3"
                              placeholder="Add additional information or a message to the safety team..."
                              style="resize:vertical;"></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Send Message</button>
            </form>
        </div>

    </div>

    <!-- RIGHT: Sidebar -->
    <div style="display:flex; flex-direction:column; gap:16px;">

        <!-- Status Timeline -->
        <div class="card">
            <h4 style="margin:0 0 16px; font-size:15px;">📍 Status Timeline</h4>
            <div style="position:relative; margin-left:8px; border-left:2px solid var(--border); padding-left:20px; display:flex; flex-direction:column; gap:16px;">

                <!-- Submission -->
                <div style="position:relative;">
                    <div style="position:absolute; left:-26px; top:1px; width:12px; height:12px; border-radius:50%; background:var(--accent-blue); outline:3px solid var(--bg-card);"></div>
                    <div class="text-xs text-muted" style="margin-bottom:2px;"><?= !empty($report['created_at']) ? date('d M Y, H:i', strtotime($report['created_at'])) : '' ?></div>
                    <div class="text-sm" style="font-weight:600;">Report Submitted</div>
                </div>

                <?php if (!empty($report['status_history']) && is_array($report['status_history'])): ?>
                    <?php foreach ($report['status_history'] as $sh): ?>
                    <div style="position:relative;">
                        <div style="position:absolute; left:-26px; top:1px; width:12px; height:12px; border-radius:50%; background:var(--border); outline:3px solid var(--bg-card);"></div>
                        <div class="text-xs text-muted" style="margin-bottom:2px;"><?= !empty($sh['created_at']) ? date('d M Y, H:i', strtotime($sh['created_at'])) : '' ?></div>
                        <div class="text-sm" style="font-weight:600;">
                            <?= e(ucfirst(str_replace('_', ' ', $sh['new_status'] ?? ''))) ?>
                        </div>
                        <?php if (!empty($sh['comment'])): ?>
                            <p class="text-xs text-muted" style="margin:3px 0 0;"><?= e($sh['comment']) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Current status dot -->
                <div style="position:relative;">
                    <div style="position:absolute; left:-26px; top:1px; width:12px; height:12px; border-radius:50%; background:<?= $sc ?>; outline:3px solid var(--bg-card);"></div>
                    <div class="text-xs text-muted" style="margin-bottom:2px;">Current</div>
                    <div class="text-sm" style="font-weight:600; color:<?= $sc ?>;">
                        <?= ucfirst(str_replace('_', ' ', $report['status'] ?? '')) ?>
                    </div>
                </div>

            </div>
        </div>

        <!-- Actions -->
        <div class="card">
            <h4 style="margin:0 0 14px; font-size:15px;">Actions</h4>
            <a href="#discussion" class="btn btn-outline btn-sm" style="width:100%; display:block; text-align:center; margin-bottom:8px;">
                💬 Contact Safety Team
            </a>
            <?php if (in_array($report['status'] ?? '', ['draft'])): ?>
            <a href="/safety/report/edit/<?= (int)$report['id'] ?>" class="btn btn-primary btn-sm" style="width:100%; display:block; text-align:center;">
                Continue Editing
            </a>
            <?php endif; ?>
        </div>

        <!-- Confidentiality Note -->
        <div style="padding:12px 14px; background:rgba(59,130,246,0.06); border:1px solid rgba(59,130,246,0.2); border-radius:var(--radius-md);">
            <p class="text-xs text-muted" style="margin:0; line-height:1.5;">
                🔒 This report is encrypted and accessible only to authorised safety personnel. Internal notes and investigation details are not visible to reporters.
            </p>
        </div>
    </div>

</div>
