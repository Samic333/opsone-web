<?php
/**
 * OpsOne — Follow-Ups (Crew View)
 * Reports where the safety team has sent the last public message and the reporter hasn't replied yet.
 * Variables: $followUps (array)
 */
$pageTitle    = 'Follow-Ups';
$pageSubtitle = 'Reports awaiting your response to the safety team';

$headerAction = '<a href="/safety" class="btn btn-primary btn-sm">＋ New Report</a>';

// Status badge colour helper
$statusColor = function(string $status): string {
    return match($status) {
        'draft'              => '#6b7280',
        'submitted'          => '#3b82f6',
        'under_review'       => '#f59e0b',
        'investigation'      => '#ef4444',
        'action_in_progress' => '#8b5cf6',
        'closed'             => '#10b981',
        'reopened'           => '#f59e0b',
        default              => '#6b7280',
    };
};
?>

<!-- Back link -->
<div style="margin-bottom:16px;">
    <a href="/safety" class="btn btn-ghost btn-sm">← Safety Home</a>
</div>

<!-- Info banner -->
<div style="
    display:flex; align-items:center; gap:12px;
    padding:13px 18px;
    background:rgba(245,158,11,0.08);
    border:1px solid rgba(245,158,11,0.35);
    border-radius:var(--radius-md);
    margin-bottom:24px;">
    <span style="font-size:18px; flex-shrink:0;">💬</span>
    <p class="text-sm" style="margin:0; color:var(--text-primary); line-height:1.5;">
        The safety team has sent you a message on the reports below. Please review and reply.
    </p>
</div>

<?php if (empty($followUps)): ?>
    <div class="card">
        <div class="empty-state" style="padding:48px 0;">
            <div class="icon" style="font-size:36px; margin-bottom:12px;">✅</div>
            <h3 style="margin:0 0 8px;">No Pending Follow-Ups</h3>
            <p style="margin:0; color:var(--text-muted);">You're all caught up. No reports are waiting for your reply.</p>
        </div>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Ref No.</th>
                    <th>Type</th>
                    <th>Last Message From</th>
                    <th>Last Message Date</th>
                    <th>Status</th>
                    <th style="text-align:right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($followUps as $r):
                    $sc = $statusColor($r['status'] ?? '');
                ?>
                <tr>
                    <td style="font-family:monospace; font-weight:700; font-size:13px;">
                        <a href="/safety/report/<?= (int)$r['id'] ?>" style="color:var(--accent-blue); text-decoration:none;">
                            <?= e($r['reference_no'] ?? '—') ?>
                        </a>
                    </td>
                    <td style="font-size:12px; font-weight:600; color:var(--text-secondary);">
                        <?= e(ucwords(str_replace('_', ' ', $r['report_type'] ?? '—'))) ?>
                    </td>
                    <td class="text-sm">
                        <?= e($r['last_message_by'] ?? '—') ?>
                    </td>
                    <td class="text-sm text-muted" style="white-space:nowrap;">
                        <?= !empty($r['last_message_at']) ? date('d M Y, H:i', strtotime($r['last_message_at'])) : '—' ?>
                    </td>
                    <td>
                        <span class="status-badge" style="--badge-color:<?= $sc ?>;">
                            <?= ucfirst(str_replace('_', ' ', $r['status'] ?? 'unknown')) ?>
                        </span>
                    </td>
                    <td style="text-align:right;">
                        <a href="/safety/report/<?= (int)$r['id'] ?>?tab=discussion" class="btn btn-primary btn-xs">
                            Reply →
                        </a>
                    </td>
                </tr>
                <?php if (!empty($r['last_message'])): ?>
                <tr style="background:var(--bg-secondary,#f8f9fa);">
                    <td colspan="6" style="padding:8px 14px; font-size:12px; color:var(--text-muted); font-style:italic; border-top:none;">
                        <span style="font-weight:600; color:var(--text-secondary);"><?= e($r['last_message_by'] ?? '') ?>:</span>
                        &ldquo;<?= e(mb_substr($r['last_message'], 0, 80)) ?><?= mb_strlen($r['last_message']) > 80 ? '…' : '' ?>&rdquo;
                    </td>
                </tr>
                <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
