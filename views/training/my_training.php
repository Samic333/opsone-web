<?php /** Phase 12 — My Training */ ?>
<?php if (empty($records)): ?>
    <div class="card"><div class="empty-state" style="padding:40px 0;"><div class="icon">🎓</div><h3>No training records</h3><p>Your recurrent training and certification records will appear here.</p></div></div>
<?php else: ?>
<div class="card">
    <table class="table">
        <thead><tr><th>Training</th><th>Completed</th><th>Expires</th><th>Days to expiry</th><th>Provider</th><th>Result</th></tr></thead>
        <tbody>
        <?php foreach ($records as $r):
            $exp = $r['expires_date'];
            $days = $exp ? (int)((strtotime($exp) - time()) / 86400) : null;
            $color = $days === null ? '' : ($days < 0 ? '#ef4444' : ($days < 30 ? '#f59e0b' : '#10b981'));
        ?>
            <tr>
                <td><?= e($r['type_name'] ?? $r['type_code'] ?? '—') ?></td>
                <td class="text-sm"><?= e($r['completed_date']) ?></td>
                <td class="text-sm"><?= e($exp ?? '—') ?></td>
                <td class="text-sm" style="color:<?= $color ?>;">
                    <?= $days === null ? '—' : ($days < 0 ? (abs($days) . 'd overdue') : ($days . 'd')) ?>
                </td>
                <td class="text-sm"><?= e($r['provider'] ?? '—') ?></td>
                <td class="text-sm"><?= e($r['result']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
