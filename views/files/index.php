<?php
$pageTitle = 'Documents';
$pageSubtitle = 'Manage manuals, notices, and files';
$headerAction = '<a href="/files/upload" class="btn btn-primary">+ Upload Document</a>';
ob_start();
?>

<?php if (empty($files)): ?>
<div class="card">
    <div class="empty-state">
        <div class="icon">📄</div>
        <h3>No Documents Yet</h3>
        <p>Upload your first document, manual, or notice.</p>
        <a href="/files/upload" class="btn btn-primary mt-2">+ Upload Document</a>
    </div>
</div>
<?php else: ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr><th>Title</th><th>Category</th><th>Version</th><th>Status</th><th>File</th><th>Uploaded By</th><th>Date</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($files as $f): ?>
        <tr>
            <td>
                <strong><?= e($f['title']) ?></strong>
                <?php if ($f['requires_ack']): ?><span style="color:var(--accent-yellow);font-size:11px"> ◆ Ack Required</span><?php endif; ?>
            </td>
            <td class="text-sm"><?= e($f['category_name'] ?? 'Uncategorized') ?></td>
            <td><code><?= e($f['version']) ?></code></td>
            <td><?= statusBadge($f['status']) ?></td>
            <td class="text-sm">
                <?= e($f['file_name']) ?>
                <div class="text-xs text-muted"><?= number_format(($f['file_size'] ?? 0) / 1024, 1) ?> KB</div>
            </td>
            <td class="text-sm"><?= e($f['uploaded_by_name'] ?? '—') ?></td>
            <td class="text-sm"><?= formatDate($f['created_at']) ?></td>
            <td>
                <div class="btn-group">
                    <a href="/files/download/<?= $f['id'] ?>" class="btn btn-xs btn-outline">Download</a>
                    <form method="POST" action="/files/toggle/<?= $f['id'] ?>" style="display:inline">
                        <?= csrfField() ?>
                        <button type="submit" class="btn btn-xs <?= $f['status'] === 'published' ? 'btn-warning' : 'btn-success' ?>">
                            <?= $f['status'] === 'published' ? 'Unpublish' : 'Publish' ?>
                        </button>
                    </form>
                    <form method="POST" action="/files/delete/<?= $f['id'] ?>" style="display:inline"
                          onsubmit="return confirm('Delete this document permanently?')">
                        <?= csrfField() ?>
                        <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/app.php'; ?>
