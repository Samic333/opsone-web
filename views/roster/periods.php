<?php /** OpsOne — Roster Periods */ ?>

<?php
$statusColors = [
    'draft'     => '#6b7280',
    'published' => '#10b981',
    'frozen'    => '#3b82f6',
    'archived'  => '#9ca3af',
];
$statusLabels = [
    'draft'     => 'Draft',
    'published' => 'Published',
    'frozen'    => 'Frozen',
    'archived'  => 'Archived',
];
?>

<div style="display:flex; gap:16px; align-items:center; margin-bottom:24px; flex-wrap:wrap;">
    <a href="/roster/periods/create" class="btn btn-primary">＋ New Period</a>
    <?php if (!empty($pending)): ?>
        <a href="/roster/changes" class="btn btn-outline" style="color:#f59e0b; border-color:#f59e0b;">
            ⚠ <?= count($pending) ?> pending change request<?= count($pending) !== 1 ? 's' : '' ?>
        </a>
    <?php endif; ?>
    <a href="/roster" class="btn btn-ghost" style="margin-left:auto;">← Back to Roster</a>
</div>

<?php if (empty($periods)): ?>
    <div class="card">
        <div class="empty-state" style="padding:48px 0;">
            <div class="icon">📅</div>
            <p>No roster periods yet.</p>
            <p class="text-sm text-muted">Create a period to organise your roster into scheduling cycles.</p>
            <a href="/roster/periods/create" class="btn btn-primary" style="margin-top:12px;">Create First Period</a>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Period</th>
                    <th>Dates</th>
                    <th>Duration</th>
                    <th>Status</th>
                    <th>Created By</th>
                    <th>Notes</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($periods as $p): ?>
                <?php
                    $start    = new DateTime($p['start_date']);
                    $end      = new DateTime($p['end_date']);
                    $days     = $start->diff($end)->days + 1;
                    $color    = $statusColors[$p['status']] ?? '#6b7280';
                    $label    = $statusLabels[$p['status']] ?? ucfirst($p['status']);
                ?>
                <tr>
                    <td>
                        <strong><?= e($p['name']) ?></strong>
                    </td>
                    <td class="text-sm">
                        <?= date('d M Y', strtotime($p['start_date'])) ?>
                        <span class="text-muted">→</span>
                        <?= date('d M Y', strtotime($p['end_date'])) ?>
                    </td>
                    <td class="text-sm text-muted"><?= $days ?> days</td>
                    <td>
                        <span class="status-badge" style="--badge-color:<?= $color ?>;"><?= $label ?></span>
                    </td>
                    <td class="text-sm text-muted"><?= e($p['created_by_name'] ?? '—') ?></td>
                    <td class="text-sm text-muted"><?= e($p['notes'] ?? '—') ?></td>
                    <td>
                        <div style="display:flex; gap:6px; flex-wrap:wrap; justify-content:flex-end;">
                            <!-- View in roster grid -->
                            <?php [$sy, $sm] = explode('-', $p['start_date']); ?>
                            <a href="/roster?year=<?= $sy ?>&month=<?= ltrim($sm, '0') ?>"
                               class="btn btn-ghost btn-xs">View</a>

                            <?php if ($p['status'] === 'draft'): ?>
                                <!-- Publish toggle -->
                                <form method="POST" action="/roster/periods/publish/<?= $p['id'] ?>" style="display:inline;">
                                    <?= csrfField() ?>
                                    <button type="submit" class="btn btn-xs"
                                            style="background:#10b981;color:#fff;border:none;">Publish</button>
                                </form>
                                <!-- Delete draft -->
                                <form method="POST" action="/roster/periods/delete/<?= $p['id'] ?>"
                                      onsubmit="return confirm('Delete this draft period?')" style="display:inline;">
                                    <?= csrfField() ?>
                                    <button type="submit" class="btn btn-ghost btn-xs" style="color:#ef4444;">Delete</button>
                                </form>

                            <?php elseif ($p['status'] === 'published'): ?>
                                <!-- Unpublish (move back to draft) -->
                                <form method="POST" action="/roster/periods/publish/<?= $p['id'] ?>" style="display:inline;">
                                    <?= csrfField() ?>
                                    <button type="submit" class="btn btn-ghost btn-xs">Unpublish</button>
                                </form>
                                <!-- Freeze -->
                                <form method="POST" action="/roster/periods/freeze/<?= $p['id'] ?>"
                                      onsubmit="return confirm('Freeze this period? No further changes will be allowed.')"
                                      style="display:inline;">
                                    <?= csrfField() ?>
                                    <button type="submit" class="btn btn-xs"
                                            style="background:#3b82f6;color:#fff;border:none;">Freeze</button>
                                </form>

                            <?php elseif ($p['status'] === 'frozen'): ?>
                                <span class="text-xs text-muted">Locked</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<div class="card" style="margin-top:16px; background:var(--bg-card); border:1px solid var(--border);">
    <h4 style="margin:0 0 8px; font-size:13px; color:var(--text-muted);">Period Lifecycle</h4>
    <div style="display:flex; gap:12px; align-items:center; font-size:13px; flex-wrap:wrap;">
        <span class="status-badge" style="--badge-color:#6b7280;">Draft</span>
        <span class="text-muted">→ build your roster →</span>
        <span class="status-badge" style="--badge-color:#10b981;">Published</span>
        <span class="text-muted">→ crew can see it →</span>
        <span class="status-badge" style="--badge-color:#3b82f6;">Frozen</span>
        <span class="text-muted">→ locked, no changes</span>
    </div>
</div>
