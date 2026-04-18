<?php /** OpsOne — Notices & Bulletins (Admin) */ ?>

<?php
$filterCategory = $_GET['category'] ?? '';
$filterPriority = $_GET['priority'] ?? '';
?>

<!-- Filter Bar -->
<div class="card" style="padding: 16px 20px; margin-bottom: 20px;">
    <form method="GET" action="/notices" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
        <select name="category" class="form-control" style="width:auto; min-width:160px;">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= e($cat['slug']) ?>" <?= $filterCategory === $cat['slug'] ? 'selected' : '' ?>>
                    <?= e($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="priority" class="form-control" style="width:auto; min-width:140px;">
            <option value="">All Priorities</option>
            <option value="normal"   <?= $filterPriority === 'normal'   ? 'selected' : '' ?>>Normal</option>
            <option value="urgent"   <?= $filterPriority === 'urgent'   ? 'selected' : '' ?>>Urgent</option>
            <option value="critical" <?= $filterPriority === 'critical' ? 'selected' : '' ?>>Critical</option>
        </select>
        <button type="submit" class="btn btn-outline btn-sm">Filter</button>
        <?php if ($filterCategory || $filterPriority): ?>
            <a href="/notices" class="btn btn-ghost btn-sm">Clear</a>
        <?php endif; ?>
        <span class="text-sm text-muted" style="margin-left:auto;"><?= count($notices) ?> notice<?= count($notices) !== 1 ? 's' : '' ?></span>
        <a href="/notices/categories" class="btn btn-ghost btn-sm">Manage Categories</a>
    </form>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Priority</th>
                <th>Category</th>
                <th>Ack Status</th>
                <th>Status</th>
                <th>Expires</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($notices)): ?>
            <tr><td colspan="8">
                <div class="empty-state">
                    <div class="icon">📢</div>
                    <h3>No Notices Yet</h3>
                    <p>Create your first notice or bulletin for your airline crew.</p>
                    <a href="/notices/create" class="btn btn-primary btn-sm">＋ New Notice</a>
                </div>
            </td></tr>
        <?php else: ?>
            <?php foreach ($notices as $notice): ?>
            <tr>
                <td>
                    <strong><?= e($notice['title']) ?></strong>
                    <br><span class="text-xs text-muted">by <?= e($notice['author_name'] ?? 'System') ?></span>
                </td>
                <td>
                    <?php
                    $priorityColors = ['normal' => '#6b7280', 'urgent' => '#f59e0b', 'critical' => '#ef4444'];
                    $pc = $priorityColors[$notice['priority']] ?? '#6b7280';
                    ?>
                    <span class="status-badge" style="--badge-color: <?= $pc ?>"><?= ucfirst(e($notice['priority'])) ?></span>
                </td>
                <td class="text-muted text-sm"><?= ucfirst(e($notice['category'] ?? '—')) ?></td>
                <td>
                    <?php if ($notice['requires_ack']): ?>
                        <?php
                        $nid     = (int)$notice['id'];
                        $acked   = $ackStats[$nid] ?? 0;
                        $total   = $totalCrew;
                        $pct     = $total > 0 ? round(($acked / $total) * 100) : 0;
                        $barCol  = $pct >= 80 ? '#10b981' : ($pct >= 50 ? '#f59e0b' : '#ef4444');
                        ?>
                        <a href="/notices/ack-report/<?= $nid ?>" style="text-decoration:none;">
                            <div style="min-width:90px;">
                                <div style="background:var(--bg-secondary);border-radius:4px;height:6px;overflow:hidden;margin-bottom:3px;">
                                    <div style="width:<?= $pct ?>%;height:100%;background:<?= $barCol ?>;border-radius:4px;"></div>
                                </div>
                                <span style="font-size:11px;color:var(--text-muted);"><?= $acked ?>/<?= $total ?> crew</span>
                                <span style="font-size:11px;color:<?= $barCol ?>;font-weight:600;margin-left:4px;">(<?= $pct ?>%)</span>
                            </div>
                        </a>
                    <?php else: ?>
                        <span class="text-muted text-xs">Not required</span>
                    <?php endif; ?>
                </td>
                <td><?= $notice['published'] ? statusBadge('published') : statusBadge('draft') ?></td>
                <td class="text-sm text-muted">
                    <?php if ($notice['expires_at']): ?>
                        <?php
                        $diff = (strtotime($notice['expires_at']) - time()) / 86400;
                        $color = $diff < 0 ? '#ef4444' : ($diff < 7 ? '#f59e0b' : '#6b7280');
                        ?>
                        <span style="color:<?= $color ?>"><?= formatDate($notice['expires_at']) ?></span>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <td class="text-sm text-muted"><?= formatDate($notice['created_at']) ?></td>
                <td>
                    <div class="btn-group">
                        <a href="/notices/edit/<?= $notice['id'] ?>" class="btn btn-outline btn-xs">Edit</a>
                        <?php if ($notice['requires_ack']): ?>
                            <a href="/notices/ack-report/<?= $notice['id'] ?>" class="btn btn-outline btn-xs">Ack Report</a>
                        <?php endif; ?>
                        <form method="POST" action="/notices/toggle/<?= $notice['id'] ?>" style="display:inline;">
                            <?= csrfField() ?>
                            <button type="submit" class="btn btn-xs <?= $notice['published'] ? 'btn-warning' : 'btn-success' ?>">
                                <?= $notice['published'] ? 'Unpublish' : 'Publish' ?>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
