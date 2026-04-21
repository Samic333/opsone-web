<?php /** OpsOne — Notification Center */ ?>
<div style="display:flex; gap:12px; margin-bottom:16px;">
    <a href="/notifications" class="btn btn-sm <?= ($_GET['filter'] ?? 'all') === 'all' ? 'btn-primary' : 'btn-outline' ?>">
        All (<?= (int)$counts['total'] ?>)
    </a>
    <a href="/notifications?filter=unread" class="btn btn-sm <?= ($_GET['filter'] ?? '') === 'unread' ? 'btn-primary' : 'btn-outline' ?>">
        Unread (<?= (int)$counts['unread'] ?>)
    </a>
    <a href="/notifications?filter=unack" class="btn btn-sm <?= ($_GET['filter'] ?? '') === 'unack' ? 'btn-primary' : 'btn-outline' ?>">
        Awaiting ack (<?= (int)$counts['unack'] ?>)
    </a>
    <?php if (($counts['unread'] ?? 0) > 0): ?>
        <form method="POST" action="/notifications/mark-all-read" style="margin-left:auto;">
            <?= csrfField() ?>
            <button class="btn btn-sm btn-outline" type="submit">Mark all as read</button>
        </form>
    <?php endif; ?>
</div>

<?php if (empty($notifications)): ?>
    <div class="card">
        <div class="empty-state" style="padding:48px 0;">
            <div class="icon">🔔</div>
            <h3>No notifications</h3>
            <p>You'll see operational updates, duty reminders, and document alerts here.</p>
        </div>
    </div>
<?php else: ?>
<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th style="width:90px;">Priority</th>
                <th>Title / Body</th>
                <th style="width:160px;">Received</th>
                <th style="width:240px;">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($notifications as $n):
            $prio  = $n['priority'] ?? 'normal';
            $color = match ($prio) {
                'critical'  => '#ef4444',
                'important' => '#f59e0b',
                'silent'    => '#64748b',
                default     => '#3b82f6',
            };
            $unread  = empty($n['is_read']);
            $needAck = !empty($n['ack_required']) && empty($n['acknowledged_at']);
        ?>
            <tr<?= $unread ? ' style="background:rgba(59,130,246,0.06);"' : '' ?>>
                <td>
                    <span style="display:inline-block; padding:2px 8px; border-radius:10px;
                                 font-size:10px; font-weight:700; text-transform:uppercase;
                                 color:#fff; background:<?= $color ?>;">
                        <?= e($prio) ?>
                    </span>
                </td>
                <td>
                    <div style="font-weight:<?= $unread ? '600' : '400' ?>;"><?= e($n['title']) ?></div>
                    <div class="text-xs text-muted" style="margin-top:2px;"><?= e(mb_substr($n['body'], 0, 140)) ?></div>
                    <?php if (!empty($n['event'])): ?>
                        <div class="text-xs text-muted" style="margin-top:2px;">event: <code><?= e($n['event']) ?></code></div>
                    <?php endif; ?>
                </td>
                <td class="text-xs text-muted">
                    <?= formatDateTime($n['created_at']) ?>
                    <?php if (!empty($n['read_at'])): ?>
                        <div style="color:#3b82f6;">read <?= formatDateTime($n['read_at']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($n['acknowledged_at'])): ?>
                        <div style="color:#10b981;">acked <?= formatDateTime($n['acknowledged_at']) ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display:flex; gap:6px;">
                        <?php if (!empty($n['link'])): ?>
                            <a href="/notifications/open/<?= (int)$n['id'] ?>" class="btn btn-xs btn-outline">Open</a>
                        <?php endif; ?>
                        <?php if ($unread): ?>
                            <form method="POST" action="/notifications/mark-read/<?= (int)$n['id'] ?>" style="margin:0;">
                                <?= csrfField() ?>
                                <button class="btn btn-xs btn-outline" type="submit">Mark read</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($needAck): ?>
                            <form method="POST" action="/notifications/acknowledge/<?= (int)$n['id'] ?>" style="margin:0;">
                                <?= csrfField() ?>
                                <button class="btn btn-xs btn-primary" type="submit">Acknowledge</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
