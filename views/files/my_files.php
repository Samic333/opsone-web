<?php /** OpsOne — My Documents (Crew Portal) — Phase 4: unread/read/ack status */ ?>

<?php if (empty($files)): ?>
    <div class="card">
        <div class="empty-state" style="padding:48px 0;">
            <div class="icon">📁</div>
            <h3>No Documents</h3>
            <p>No active manuals or documents are currently assigned to you.</p>
        </div>
    </div>
<?php else: ?>

    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Version & Date</th>
                    <th>Status</th>
                    <th>Size</th>
                    <th style="text-align:right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($files as $file): ?>
                <?php
                    $status = $fileStatus[$file['id']] ?? 'unread';
                    $requiresAck = (bool)$file['requires_ack'];

                    $statusLabel = match ($status) {
                        'acknowledged' => ['✓ Acknowledged',        '#065f46', '#d1fae5'],
                        'ack_outdated' => ['⚠ New version',         '#92400e', '#fef3c7'],
                        'read'         => ['Read',                  '#1e40af', '#dbeafe'],
                        default        => ['Unread',                '#fff',    '#ef4444'],
                    };
                ?>
                <tr>
                    <td>
                        <div style="font-weight:600; font-size:14px;"><?= e($file['title']) ?></div>
                        <div class="text-xs text-muted" style="margin-top:2px;">
                            <?php if (!empty($file['category_name'])): ?>
                                <span class="status-badge" style="--badge-color:#6b7280"><?= e($file['category_name']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($file['description'])): ?>
                                &mdash; <?= e(mb_substr($file['description'], 0, 80)) ?><?= mb_strlen($file['description']) > 80 ? '…' : '' ?>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div style="font-size:13px;">v<?= e($file['version']) ?></div>
                        <div class="text-xs text-muted">
                            <?= $file['effective_date']
                                ? 'Effective ' . formatDate($file['effective_date'])
                                : 'Published ' . formatDate($file['created_at']) ?>
                        </div>
                    </td>
                    <td>
                        <span style="display:inline-block; padding:3px 9px; border-radius:10px;
                                     font-size:11px; font-weight:600;
                                     color:<?= $statusLabel[1] ?>; background:<?= $statusLabel[2] ?>;">
                            <?= $statusLabel[0] ?>
                        </span>
                        <?php if ($requiresAck && $status !== 'acknowledged'): ?>
                            <div class="text-xs" style="color:#f59e0b; margin-top:3px;">Ack required</div>
                        <?php endif; ?>
                    </td>
                    <td class="text-sm">
                        <?= formatBytes($file['file_size']) ?>
                        <div class="text-xs text-muted"><?= strtoupper(pathinfo($file['file_name'], PATHINFO_EXTENSION)) ?></div>
                    </td>
                    <td>
                        <div style="display:flex; gap:8px; justify-content:flex-end; align-items:center;">
                            <a href="/files/download/<?= $file['id'] ?>" class="btn btn-sm btn-outline" target="_blank">Download</a>
                            <?php if ($requiresAck && $status !== 'acknowledged'): ?>
                                <form method="POST" action="/my-files/acknowledge/<?= $file['id'] ?>" style="margin:0;">
                                    <?= csrfField() ?>
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <?= $status === 'ack_outdated' ? 'Ack new version' : 'Acknowledge' ?>
                                    </button>
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
