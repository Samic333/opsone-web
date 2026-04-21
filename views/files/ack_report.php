<?php /** OpsOne — Per-Document Acknowledgement Report */ ?>
<div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:16px;">
    <div class="card" style="flex:1; min-width:140px; padding:12px;">
        <div class="text-xs text-muted">Recipients</div>
        <div style="font-size:24px; font-weight:700;"><?= (int)$totals['recipients'] ?></div>
    </div>
    <div class="card" style="flex:1; min-width:140px; padding:12px;">
        <div class="text-xs text-muted">Read</div>
        <div style="font-size:24px; font-weight:700; color:#3b82f6;">
            <?= (int)$totals['read'] ?>
            <?php if ($totals['recipients']): ?>
                <span class="text-xs text-muted" style="font-weight:400;">
                    (<?= round(100 * $totals['read'] / $totals['recipients']) ?>%)
                </span>
            <?php endif; ?>
        </div>
    </div>
    <div class="card" style="flex:1; min-width:140px; padding:12px;">
        <div class="text-xs text-muted">Acknowledged</div>
        <div style="font-size:24px; font-weight:700; color:#10b981;">
            <?= (int)$totals['acknowledged'] ?>
            <?php if ($totals['recipients']): ?>
                <span class="text-xs text-muted" style="font-weight:400;">
                    (<?= round(100 * $totals['acknowledged'] / $totals['recipients']) ?>%)
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Staff</th>
                <th>Employee ID</th>
                <th>First Read</th>
                <th>Acknowledged</th>
                <th>Ack'd Version</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($recipients)): ?>
            <tr><td colspan="5" class="text-muted">No recipients in the targeted audience yet.</td></tr>
        <?php else: foreach ($recipients as $r): ?>
            <tr>
                <td>
                    <strong><?= e($r['name']) ?></strong>
                    <div class="text-xs text-muted"><?= e($r['email']) ?></div>
                </td>
                <td class="text-xs"><?= e($r['employee_id'] ?? '—') ?></td>
                <td class="text-xs">
                    <?= $r['read_at']
                        ? '<span style="color:#3b82f6;">' . e($r['read_at']) . '</span>'
                        : '<span class="text-muted">—</span>' ?>
                </td>
                <td class="text-xs">
                    <?= $r['acknowledged_at']
                        ? '<span style="color:#10b981;">' . e($r['acknowledged_at']) . '</span>'
                        : '<span class="text-muted">—</span>' ?>
                </td>
                <td class="text-xs"><?= $r['acked_version'] ? 'v' . e($r['acked_version']) : '<span class="text-muted">—</span>' ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<div style="margin-top:16px;">
    <a href="/files" class="btn btn-outline">← Back to Documents</a>
    <a href="/files/edit/<?= (int)$file['id'] ?>" class="btn btn-outline">Edit Document</a>
</div>
