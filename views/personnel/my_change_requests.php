<?php
/**
 * Self-service change-request list.
 * Vars: $mine
 */
$color = [
    'submitted'      => '#f59e0b',
    'under_review'   => '#3b82f6',
    'info_requested' => '#8b5cf6',
    'approved'       => '#10b981',
    'rejected'       => '#ef4444',
    'withdrawn'      => '#6b7280',
];
?>
<div class="card">
    <div class="card-header">
        <div class="card-title">My Change Requests</div>
        <a href="/my-profile" class="btn btn-outline btn-sm">← Back to My Profile</a>
    </div>
    <p class="text-muted" style="font-size:13px;">
        These are compliance change requests you've submitted (profile, license,
        qualification or document updates). Sensitive compliance fields require
        approval from HR or your line manager before they take effect. Your
        original approved records remain unchanged until a request is approved.
    </p>

    <?php if (empty($mine)): ?>
        <div class="empty-state">
            <div class="icon">📝</div>
            <h3>No change requests yet</h3>
            <p>Use the <em>My Profile</em> page to request changes to licence, medical, passport, visa or other compliance data.</p>
        </div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr>
                <th>#</th><th>Target</th><th>Type</th><th>Submitted</th>
                <th>Status</th><th>Reviewer note</th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($mine as $r): $c = $color[$r['status']] ?? '#6b7280'; ?>
            <tr>
                <td>#<?= (int) $r['id'] ?></td>
                <td><code><?= e($r['target_entity']) ?></code></td>
                <td><?= e($r['change_type']) ?></td>
                <td><?= formatDateTime($r['submitted_at']) ?></td>
                <td><span class="status-badge" style="--badge-color:<?= $c ?>">
                    <?= ucwords(str_replace('_',' ',$r['status'])) ?>
                </span></td>
                <td style="max-width:280px;"><?= e($r['reviewer_notes'] ?? '—') ?></td>
                <td>
                <?php if (in_array($r['status'], ['submitted','info_requested'], true)): ?>
                    <form method="POST" action="/my-profile/change-requests/<?= (int) $r['id'] ?>/withdraw" onsubmit="return confirm('Withdraw this request?');">
                        <?= csrfField() ?>
                        <button class="btn btn-outline btn-xs">Withdraw</button>
                    </form>
                <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
