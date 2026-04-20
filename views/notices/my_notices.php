<?php /** OpsOne — Operational Notices (Crew Portal) */ ?>

<?php
$pageTitle    = 'Operational Notices';
$pageSubtitle = 'Company and operational information from your airline.';

$priorityColors = ['normal' => '#6b7280', 'urgent' => '#f59e0b', 'critical' => '#ef4444'];
$priorityIcons  = ['normal' => '📢', 'urgent' => '⚠️', 'critical' => '🚨'];

// Count pending acknowledgements for summary banner
$pendingAcks = count(array_filter($notices ?? [], fn($n) => !empty($n['requires_ack']) && empty($n['acknowledged_at'])));
?>

<?php if ($pendingAcks > 0): ?>
<div style="background:linear-gradient(135deg,#f59e0b,#d97706); color:#fff; border-radius:10px; padding:14px 18px; margin-bottom:20px; display:flex; align-items:center; gap:14px;">
    <span style="font-size:22px;">✍️</span>
    <div>
        <strong style="font-size:14px;"><?= $pendingAcks ?> notice<?= $pendingAcks !== 1 ? 's' : '' ?> require<?= $pendingAcks === 1 ? 's' : '' ?> your acknowledgement</strong>
        <div style="font-size:12px; opacity:0.9; margin-top:2px;">Scroll down to review and sign off — your acknowledgement is recorded with a timestamp.</div>
    </div>
</div>
<?php endif; ?>

<?php if (empty($notices)): ?>
    <div class="card">
        <div class="empty-state" style="padding:48px 0;">
            <div class="icon">📭</div>
            <h3>All clear</h3>
            <p>No active notices for your role at this time.</p>
        </div>
    </div>
<?php else: ?>

    <?php foreach ($notices as $notice): ?>
    <?php
    $priority = $notice['priority'] ?? 'normal';
    $color    = $priorityColors[$priority] ?? '#6b7280';
    $icon     = $priorityIcons[$priority]  ?? '📢';
    $isAcked  = !empty($notice['acknowledged_at']);
    ?>
    <div class="card" style="margin-bottom:16px; border-left:4px solid <?= $color ?>;<?= ($notice['requires_ack'] && !$isAcked) ? ' box-shadow:0 0 0 2px rgba(245,158,11,0.25);' : '' ?>">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;">
            <div style="flex:1; min-width:0;">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px; flex-wrap:wrap;">
                    <span style="font-size:18px;"><?= $icon ?></span>
                    <h3 style="margin:0; font-size:16px;"><?= e($notice['title']) ?></h3>
                    <span class="status-badge" style="--badge-color:<?= $color ?>"><?= ucfirst($priority) ?></span>
                    <?php if ($notice['category']): ?>
                        <span class="status-badge" style="--badge-color:#6b7280"><?= ucfirst(e($notice['category'])) ?></span>
                    <?php endif; ?>
                    <?php if ($notice['requires_ack']): ?>
                        <span class="status-badge" style="--badge-color:<?= $isAcked ? '#10b981' : '#f59e0b' ?>">
                            <?= $isAcked ? '✓ Ack Required' : '⚠ Ack Required' ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="text-sm text-muted" style="margin-bottom:12px;">
                    Published <?= formatDate($notice['published_at'] ?? $notice['created_at']) ?>
                    <?php if ($notice['expires_at']): ?>
                        · Expires <?= formatDate($notice['expires_at']) ?>
                    <?php endif; ?>
                    <?php if ($notice['author_name']): ?>
                        · by <?= e($notice['author_name']) ?>
                    <?php endif; ?>
                </div>
                <div class="text-sm" style="white-space:pre-wrap; line-height:1.6;"><?= e($notice['body']) ?></div>
            </div>

            <?php if ($notice['requires_ack']): ?>
            <div style="flex-shrink:0; text-align:center; min-width:140px;">
                <?php if ($isAcked): ?>
                    <div style="padding:12px 16px; background:#d1fae5; border-radius:10px; color:#065f46; border:1px solid #a7f3d0;">
                        <div style="font-size:18px; margin-bottom:4px;">✓</div>
                        <div style="font-size:13px; font-weight:700;">Acknowledged</div>
                        <div style="font-size:11px; margin-top:4px; opacity:0.75;">
                            <?= date('d M Y', strtotime($notice['acknowledged_at'])) ?>
                            <br><?= date('H:i', strtotime($notice['acknowledged_at'])) ?> UTC
                        </div>
                    </div>
                <?php else: ?>
                    <form method="POST" action="/my-notices/acknowledge/<?= $notice['id'] ?>">
                        <?= csrfField() ?>
                        <button type="submit" class="btn btn-primary btn-sm" style="width:100%;">
                            ✍️ Acknowledge
                        </button>
                    </form>
                    <p class="text-xs text-muted" style="margin-top:6px; line-height:1.4;">
                        Action required.<br>Tapping records your sign-off.
                    </p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

<?php endif; ?>
