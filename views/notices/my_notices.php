<?php /** OpsOne — My Notices (Crew Portal) */ ?>

<?php
$priorityColors = ['normal' => '#6b7280', 'urgent' => '#f59e0b', 'critical' => '#ef4444'];
$priorityIcons  = ['normal' => '📢', 'urgent' => '⚠️', 'critical' => '🚨'];
?>

<?php if (empty($notices)): ?>
    <div class="card">
        <div class="empty-state" style="padding:48px 0;">
            <div class="icon">📭</div>
            <h3>All clear</h3>
            <p>No active notices for your role at this time.</p>
        </div>
    </div>
<?php else: ?>

    <!-- Critical & Urgent notices first in alert-style cards -->
    <?php foreach ($notices as $notice): ?>
    <?php
    $priority = $notice['priority'] ?? 'normal';
    $color    = $priorityColors[$priority] ?? '#6b7280';
    $icon     = $priorityIcons[$priority]  ?? '📢';
    $isAcked  = !empty($notice['acknowledged_at']);
    ?>
    <div class="card" style="margin-bottom:16px; border-left:4px solid <?= $color ?>;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;">
            <div style="flex:1; min-width:0;">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                    <span style="font-size:18px;"><?= $icon ?></span>
                    <h3 style="margin:0; font-size:16px;"><?= e($notice['title']) ?></h3>
                    <span class="status-badge" style="--badge-color:<?= $color ?>"><?= ucfirst($priority) ?></span>
                    <?php if ($notice['category']): ?>
                        <span class="status-badge" style="--badge-color:#6b7280"><?= ucfirst(e($notice['category'])) ?></span>
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
            <div style="flex-shrink:0; text-align:center; min-width:120px;">
                <?php if ($isAcked): ?>
                    <div style="padding:10px 16px; background:#d1fae5; border-radius:8px; color:#065f46; font-size:13px; font-weight:600;">
                        ✓ Acknowledged
                    </div>
                <?php else: ?>
                    <form method="POST" action="/my-notices/acknowledge/<?= $notice['id'] ?>">
                        <?= csrfField() ?>
                        <button type="submit" class="btn btn-primary btn-sm">Acknowledge</button>
                    </form>
                    <p class="text-xs text-muted" style="margin-top:4px;">Action required</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

<?php endif; ?>
