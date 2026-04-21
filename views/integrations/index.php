<?php /** Phase 16 — Integrations registry */ ?>
<div class="card" style="margin-bottom:12px; padding:14px;">
    <p class="text-sm" style="margin:0;">
        These are optional external connectors. They are <strong>disabled by default</strong>. Enable only after core modules are stable for your airline.
    </p>
</div>

<?php if (empty($integrations)): ?>
    <div class="card"><p class="text-muted" style="padding:20px;">No integrations registered.</p></div>
<?php else: ?>
<div class="card">
    <table class="table">
        <thead><tr><th>Provider</th><th>Status</th><th>Last sync</th><th>Notes</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($integrations as $i): ?>
            <tr>
                <td>
                    <strong><?= e($i['display_name']) ?></strong>
                    <div class="text-xs text-muted"><?= e($i['provider']) ?></div>
                </td>
                <td><?= statusBadge($i['status']) ?></td>
                <td class="text-xs text-muted"><?= e($i['last_sync_at'] ?? '—') ?></td>
                <td class="text-xs text-muted"><?= $i['last_error'] ? '<span style="color:#ef4444;">' . e(mb_substr($i['last_error'],0,100)) . '</span>' : '—' ?></td>
                <td>
                    <form method="POST" action="/integrations/<?= (int)$i['id'] ?>/status" style="display:inline-flex; gap:6px;">
                        <?= csrfField() ?>
                        <select name="status" class="form-control" style="width:auto; display:inline;">
                            <option value="disabled" <?= $i['status']==='disabled'?'selected':'' ?>>Disabled</option>
                            <option value="pending"  <?= $i['status']==='pending'?'selected':''  ?>>Pending setup</option>
                            <option value="live"     <?= $i['status']==='live'?'selected':''     ?>>Live</option>
                            <option value="error"    <?= $i['status']==='error'?'selected':''    ?>>Error</option>
                        </select>
                        <button class="btn btn-xs btn-primary" type="submit">Save</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
