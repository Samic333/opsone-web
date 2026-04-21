<?php
/** OpsOne — Document Version History */
$chain = $data['chain'] ?? [];
$newer = $data['newer'] ?? [];
?>
<div class="card" style="max-width:880px;">
    <h3 style="margin-top:0;">Version Chain</h3>
    <p class="text-xs text-muted">
        Each revision links back to the document it replaced. The active version is at the top of the chain; older revisions are archived and hidden from the crew portal.
    </p>

    <?php if (!empty($newer)): ?>
    <div class="card bg-secondary" style="margin:12px 0; padding:10px 14px; border-left:3px solid #10b981;">
        <div class="text-xs text-muted">Newer version<?= count($newer) > 1 ? 's' : '' ?> of this document:</div>
        <?php foreach ($newer as $n): ?>
            <div style="margin-top:4px;">
                <a href="/files/edit/<?= (int)$n['id'] ?>"><strong><?= e($n['title']) ?> v<?= e($n['version']) ?></strong></a>
                <span class="text-xs text-muted">· <?= formatDate($n['created_at']) ?> · <?= e($n['status']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <table class="table">
        <thead>
            <tr>
                <th>Version</th>
                <th>Title</th>
                <th>Status</th>
                <th>Uploaded</th>
                <th>Superseded</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($chain as $i => $v): ?>
            <tr<?= $i === 0 ? '' : ' style="opacity:0.7;"' ?>>
                <td><code>v<?= e($v['version']) ?></code><?= $i === 0 ? ' <span class="text-xs" style="color:#10b981;">· current</span>' : '' ?></td>
                <td><?= e($v['title']) ?></td>
                <td><?= statusBadge($v['status']) ?></td>
                <td class="text-xs"><?= formatDate($v['created_at']) ?></td>
                <td class="text-xs text-muted"><?= !empty($v['superseded_at']) ? formatDate($v['superseded_at']) : '—' ?></td>
                <td>
                    <a href="/files/download/<?= (int)$v['id'] ?>" class="btn btn-xs btn-outline">Download</a>
                    <a href="/files/edit/<?= (int)$v['id'] ?>" class="btn btn-xs btn-outline">Edit</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($chain)): ?>
            <tr><td colspan="6" class="text-muted">No history yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top:16px;">
        <a href="/files" class="btn btn-outline">← Back to Documents</a>
    </div>
</div>
