<?php /** OpsOne — My Documents (Crew Portal) */ ?>

<?php if (empty($files)): ?>
    <div class="card">
        <div class="empty-state" style="padding:48px 0;">
            <div class="icon">📁</div>
            <h3>No Documents</h3>
            <p>No active manuals or documents are currently assigned to your role.</p>
        </div>
    </div>
<?php else: ?>

    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>Title & Category</th>
                    <th>Version & Date</th>
                    <th>Size</th>
                    <th style="text-align:right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($files as $file): ?>
                <?php 
                $ackRecords = $acknowledgedVersions[$file['id']] ?? null;
                $isAcked = $ackRecords && $ackRecords['version'] === $file['version'];
                ?>
                <tr>
                    <td>
                        <div style="font-weight:600; font-size:14px;"><?= e($file['title']) ?></div>
                        <div class="text-xs text-muted" style="margin-top:2px;">
                            <?php if ($file['category_name']): ?>
                                <span class="status-badge" style="--badge-color:#6b7280"><?= e($file['category_name']) ?></span>
                            <?php endif; ?>
                            <?php if ($file['description']): ?>
                                &mdash; <?= e(substr($file['description'], 0, 50)) ?><?= strlen($file['description']) > 50 ? '...' : '' ?>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div style="font-size:13px;">v<?= e($file['version']) ?></div>
                        <div class="text-xs text-muted"><?= $file['effective_date'] ? formatDate($file['effective_date']) : 'Published ' . formatDate($file['created_at']) ?></div>
                    </td>
                    <td class="text-sm">
                        <?= formatBytes($file['file_size']) ?>
                        <div class="text-xs text-muted"><?= strtoupper(pathinfo($file['file_name'], PATHINFO_EXTENSION)) ?></div>
                    </td>
                    <td>
                        <div style="display:flex; gap:8px; justify-content:flex-end; align-items:center;">
                            <a href="/files/download/<?= $file['id'] ?>" class="btn btn-sm btn-outline" target="_blank">Download</a>
                            
                            <?php if ($file['requires_ack']): ?>
                                <?php if ($isAcked): ?>
                                    <div style="padding:6px 12px; background:#d1fae5; border-radius:6px; color:#065f46; font-size:12px; font-weight:600;">
                                        ✓ Ack'd
                                    </div>
                                <?php else: ?>
                                    <form method="POST" action="/my-files/acknowledge/<?= $file['id'] ?>" style="margin:0;">
                                        <?= csrfField() ?>
                                        <button type="submit" class="btn btn-primary btn-sm">Acknowledge</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php endif; ?>
