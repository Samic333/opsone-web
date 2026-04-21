<?php
/**
 * Tenant-wide eligibility overview.
 * Vars: $bulk (user_id => eligibility), $users (user_id => user info), $summary, $filter
 */
$colorFor = ['eligible' => '#10b981', 'warning' => '#f59e0b', 'blocked' => '#ef4444'];
?>
<div class="stats-grid">
    <div class="stat-card green">
        <div class="stat-label">Eligible</div>
        <div class="stat-value"><?= (int) $summary['eligible'] ?></div>
    </div>
    <div class="stat-card <?= $summary['warning'] > 0 ? 'yellow' : 'blue' ?>">
        <div class="stat-label">Warning</div>
        <div class="stat-value"><?= (int) $summary['warning'] ?></div>
    </div>
    <div class="stat-card <?= $summary['blocked'] > 0 ? 'red' : 'blue' ?>">
        <div class="stat-label">Blocked</div>
        <div class="stat-value"><?= (int) $summary['blocked'] ?></div>
    </div>
    <div class="stat-card blue">
        <div class="stat-label">Total Staff</div>
        <div class="stat-value"><?= (int) $summary['total'] ?></div>
    </div>
</div>

<div class="card">
    <form method="GET" class="flex-row" style="gap:10px;align-items:flex-end;">
        <div>
            <label class="text-xs text-muted">Filter by status</label>
            <select name="status" class="form-control">
                <option value="">All</option>
                <?php foreach (['eligible','warning','blocked'] as $s): ?>
                <option value="<?= $s ?>" <?= ($filter ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-primary btn-sm">Filter</button>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Assignment Readiness</div>
        <span class="text-xs text-muted">Used by roster/scheduling to pre-check eligibility.</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr>
                <th>Staff</th><th>Role</th><th>Department</th><th>Base</th>
                <th>Status</th><th>Top Reason</th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($bulk as $uid => $e):
                if ($filter && $e['status'] !== $filter) continue;
                $u = $users[$uid] ?? null;
                if (!$u) continue;
                $c = $colorFor[$e['status']] ?? '#6b7280';
                $firstReason = $e['reasons'][0] ?? '—';
            ?>
            <tr>
                <td><strong><?= e($u['name']) ?></strong>
                    <?php if (!empty($u['employee_id'])): ?>
                        <span class="text-xs text-muted">(<?= e($u['employee_id']) ?>)</span>
                    <?php endif; ?>
                </td>
                <td><?= e($u['role_names'] ?? '—') ?></td>
                <td><?= e($u['department_name'] ?? '—') ?></td>
                <td><?= e($u['base_name'] ?? '—') ?></td>
                <td><span class="status-badge" style="--badge-color:<?= $c ?>">
                    <?= strtoupper($e['status']) ?>
                </span></td>
                <td style="font-size:12px;max-width:320px;"><?= e($firstReason) ?></td>
                <td><a href="/personnel/eligibility/<?= (int) $uid ?>" class="btn btn-outline btn-xs">Detail</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
