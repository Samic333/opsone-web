<?php /** OpsOne — Notices List (Admin) */ ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Priority</th>
                <th>Category</th>
                <th>Status</th>
                <th>Published</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($notices)): ?>
            <tr><td colspan="7">
                <div class="empty-state">
                    <div class="icon">📢</div>
                    <h3>No Notices Yet</h3>
                    <p>Create your first notice or bulletin for your airline crew.</p>
                </div>
            </td></tr>
        <?php else: ?>
            <?php foreach ($notices as $notice): ?>
            <tr>
                <td><strong><?= e($notice['title']) ?></strong><br><span class="text-xs text-muted">by <?= e($notice['author_name'] ?? 'System') ?></span></td>
                <td>
                    <?php
                    $priorityColors = ['normal' => '#6b7280', 'urgent' => '#f59e0b', 'critical' => '#ef4444'];
                    $pc = $priorityColors[$notice['priority']] ?? '#6b7280';
                    ?>
                    <span class="status-badge" style="--badge-color: <?= $pc ?>"><?= ucfirst(e($notice['priority'])) ?></span>
                </td>
                <td class="text-muted"><?= ucfirst(e($notice['category'])) ?></td>
                <td><?= $notice['published'] ? statusBadge('published') : statusBadge('draft') ?></td>
                <td class="text-sm text-muted"><?= formatDateTime($notice['published_at']) ?></td>
                <td class="text-sm text-muted"><?= formatDate($notice['created_at']) ?></td>
                <td>
                    <div class="btn-group">
                        <a href="/notices/edit/<?= $notice['id'] ?>" class="btn btn-outline btn-xs">Edit</a>
                        <form method="POST" action="/notices/toggle/<?= $notice['id'] ?>" style="display: inline;">
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
