<?php
/**
 * Change request queue for reviewers.
 * Vars: $requests, $counts
 */
$badgeColor = [
    'submitted'      => '#f59e0b',
    'under_review'   => '#3b82f6',
    'info_requested' => '#8b5cf6',
    'approved'       => '#10b981',
    'rejected'       => '#ef4444',
    'withdrawn'      => '#6b7280',
];
?>
<div class="stats-grid">
    <div class="stat-card <?= $counts['pending'] > 0 ? 'yellow' : 'blue' ?>">
        <div class="stat-label">Pending</div>
        <div class="stat-value"><?= (int) $counts['pending'] ?></div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Approved (recent)</div>
        <div class="stat-value"><?= (int) $counts['approved'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Rejected (recent)</div>
        <div class="stat-value"><?= (int) $counts['rejected'] ?></div>
    </div>
</div>

<div class="card">
    <form method="GET" class="flex-row" style="gap:10px;align-items:flex-end;">
        <div>
            <label class="text-xs text-muted">Status</label>
            <select name="status" class="form-control">
                <option value="">All</option>
                <?php foreach (['submitted','under_review','info_requested','approved','rejected','withdrawn'] as $s): ?>
                <option value="<?= $s ?>" <?= ($_GET['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-primary btn-sm">Filter</button>
    </form>
</div>

<div class="card">
    <?php if (empty($requests)): ?>
        <div class="empty-state"><div class="icon">📝</div><h3>No change requests</h3>
            <p>Pending compliance updates from staff will appear here for review.</p>
        </div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr>
                <th>#</th><th>Staff</th><th>Target</th><th>Type</th>
                <th>Submitted</th><th>Status</th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($requests as $r): $color = $badgeColor[$r['status']] ?? '#6b7280'; ?>
            <tr>
                <td>#<?= (int) $r['id'] ?></td>
                <td><strong><?= e($r['user_name']) ?></strong>
                    <?php if (!empty($r['user_employee_id'])): ?>
                        <span class="text-xs text-muted">(<?= e($r['user_employee_id']) ?>)</span>
                    <?php endif; ?>
                </td>
                <td>
                    <code><?= e($r['target_entity']) ?></code>
                    <?php if (!empty($r['target_id'])): ?>
                        <span class="text-xs text-muted">#<?= (int) $r['target_id'] ?></span>
                    <?php endif; ?>
                </td>
                <td><?= e($r['change_type']) ?></td>
                <td><?= formatDateTime($r['submitted_at']) ?></td>
                <td><span class="status-badge" style="--badge-color:<?= $color ?>">
                    <?= ucwords(str_replace('_',' ',$r['status'])) ?>
                </span></td>
                <td>
                    <a href="/personnel/change-requests/<?= (int) $r['id'] ?>" class="btn btn-outline btn-xs">Review</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
