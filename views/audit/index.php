<?php
/**
 * Audit Log viewer — Phase 1
 * Variables: $logs, $page, $totalPages, $totalLogs,
 *            $filterAction, $filterUser, $filterEntity,
 *            $filterDateFrom, $filterDateTo, $filterTenantId, $filterPlatform,
 *            $entityTypes, $allTenants, $isSuperAdmin
 */

$actionColors = [
    'web.login'   => '#10b981', 'Web Login'   => '#10b981',
    'web.logout'  => '#6b7280', 'Web Logout'  => '#6b7280',
    'user.created'   => '#3b82f6', 'Created User' => '#3b82f6',
    'user.updated'   => '#3b82f6', 'Updated User' => '#3b82f6',
    'user.suspended' => '#ef4444', 'Suspended User' => '#ef4444',
    'user.activated' => '#10b981', 'Activated User' => '#10b981',
    'auth.module_access_denied' => '#ef4444', 'Unauthorized Access Attempt' => '#ef4444',
    'roster_assigned' => '#f59e0b', 'roster_deleted' => '#ef4444',
    'fdm_upload' => '#8b5cf6', 'fdm_delete' => '#ef4444',
    'fdm_event_added' => '#06b6d4', 'fdm_event_deleted' => '#ef4444',
    'fdm_manual_event' => '#8b5cf6',
    'notice_created' => '#3b82f6', 'notice_updated' => '#3b82f6', 'notice_deleted' => '#ef4444',
    'device.approved'  => '#10b981', 'Approved Device' => '#10b981',
    'device.rejected'  => '#ef4444', 'Rejected Device' => '#ef4444',
    'device.revoked'   => '#ef4444', 'Revoked Device'  => '#ef4444',
    'onboarding.' => '#6366f1',
    'tenant.'     => '#6366f1',
    'module.'     => '#8b5cf6',
];
?>
<style>
.audit-table { width:100%; border-collapse:collapse; font-size:12px; }
.audit-table th, .audit-table td { border:1px solid var(--border); padding:6px 8px; vertical-align:top; }
.audit-table th { background:var(--bg-secondary); font-weight:600; font-size:11px; text-transform:uppercase; white-space:nowrap; }
.audit-table tr:hover td { background:rgba(59,130,246,.04); }
.action-badge { display:inline-block; border-radius:4px; padding:1px 7px; font-size:10px; font-weight:700; color:#fff; white-space:nowrap; word-break:break-all; }
.filter-bar { display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end; margin-bottom:16px; }
.filter-bar label { font-size:11px; font-weight:600; color:var(--text-muted); display:block; margin-bottom:3px; }
.filter-bar input, .filter-bar select { padding:5px 8px; border-radius:6px; border:1px solid var(--border); background:var(--bg-card); color:var(--text); font-size:12px; }
.pagination { display:flex; gap:6px; align-items:center; margin-top:14px; flex-wrap:wrap; }
.pagination a { padding:4px 10px; border:1px solid var(--border); border-radius:5px; font-size:12px; text-decoration:none; color:var(--text); }
.pagination a:hover { background:var(--bg-secondary); }
.pagination .active { background:var(--accent-blue, #3b82f6); color:#fff; border-color:transparent; }
.tab-nav { display:flex; gap:8px; margin-bottom:16px; }
.tab-nav a { padding:6px 14px; border-radius:6px; font-size:13px; font-weight:600; text-decoration:none; color:var(--text-muted); border:1px solid var(--border); }
.tab-nav a.active { background:var(--accent-blue, #3b82f6); color:#fff; border-color:transparent; }
.filter-chip { display:inline-flex; align-items:center; gap:4px; padding:2px 9px; background:rgba(99,102,241,.1); color:#6366f1; border-radius:10px; font-size:11px; font-weight:600; }
</style>

<div class="tab-nav">
    <a href="/audit-log" class="active">Action Log</a>
    <a href="/audit-log/logins">Login Activity</a>
</div>

<form method="GET" action="/audit-log">
<div class="filter-bar">

    <!-- Text filters -->
    <div>
        <label>Action contains</label>
        <input type="text" name="action" value="<?= e($filterAction) ?>"
               placeholder="e.g. onboarding, login, delete" style="width:180px;">
    </div>
    <div>
        <label>User contains</label>
        <input type="text" name="user" value="<?= e($filterUser) ?>"
               placeholder="e.g. James" style="width:140px;">
    </div>
    <div>
        <label>Entity type</label>
        <select name="entity" style="width:130px;">
            <option value="">All</option>
            <?php foreach ($entityTypes as $et): ?>
            <option value="<?= e($et['entity_type']) ?>"
                <?= ($filterEntity ?? '') === $et['entity_type'] ? 'selected' : '' ?>>
                <?= e($et['entity_type']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Date range -->
    <div>
        <label>From date</label>
        <input type="date" name="date_from" value="<?= e($filterDateFrom ?? '') ?>" style="width:140px;">
    </div>
    <div>
        <label>To date</label>
        <input type="date" name="date_to" value="<?= e($filterDateTo ?? '') ?>" style="width:140px;">
    </div>

    <?php if ($isSuperAdmin): ?>
    <!-- Airline / platform scope filters (super admin only) -->
    <div>
        <label>Airline</label>
        <select name="tenant_id" style="width:160px;">
            <option value="0">All airlines</option>
            <option value="0" style="color:var(--text-muted);" disabled>────────</option>
            <?php foreach ($allTenants as $t): ?>
            <option value="<?= $t['id'] ?>"
                <?= ($filterTenantId ?? 0) === (int)$t['id'] ? 'selected' : '' ?>>
                <?= e($t['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div style="display:flex; align-items:flex-end; padding-bottom:1px;">
        <label style="display:flex; align-items:center; gap:5px; cursor:pointer; font-size:12px; color:var(--text);">
            <input type="checkbox" name="platform_only" value="1"
                   <?= !empty($filterPlatform) ? 'checked' : '' ?>>
            Platform events only
        </label>
    </div>
    <?php endif; ?>

    <div style="display:flex; gap:6px; align-items:flex-end;">
        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
        <a href="/audit-log" class="btn btn-sm btn-outline">Clear</a>
    </div>
    <div style="margin-left:auto; display:flex; align-items:flex-end; gap:10px;">
        <span style="font-size:12px; color:var(--text-muted); padding-bottom:2px;">
            <?= number_format($totalLogs) ?> record<?= $totalLogs !== 1 ? 's' : '' ?>
        </span>
        <a href="/audit-log/export?<?= http_build_query(array_filter([
            'action'    => $filterAction,
            'user'      => $filterUser,
            'entity'    => $filterEntity,
            'date_from' => $filterDateFrom,
            'date_to'   => $filterDateTo,
            'tenant_id' => $filterTenantId > 0 ? $filterTenantId : null,
        ])) ?>"
           class="btn btn-sm btn-outline"
           title="Export current filter as CSV (up to 10,000 rows)">
            ↓ Export CSV
        </a>
    </div>
</div>
</form>

<!-- Active filter chips -->
<?php
$activeFilters = [];
if ($filterAction)                   $activeFilters[] = 'Action: ' . $filterAction;
if ($filterUser)                     $activeFilters[] = 'User: ' . $filterUser;
if ($filterEntity)                   $activeFilters[] = 'Entity: ' . $filterEntity;
if ($filterDateFrom)                 $activeFilters[] = 'From: ' . $filterDateFrom;
if ($filterDateTo)                   $activeFilters[] = 'To: ' . $filterDateTo;
if ($isSuperAdmin && $filterTenantId > 0) {
    $tn = array_filter($allTenants ?? [], fn($t) => (int)$t['id'] === $filterTenantId);
    $activeFilters[] = 'Airline: ' . (reset($tn)['name'] ?? '#' . $filterTenantId);
}
if (!empty($filterPlatform))         $activeFilters[] = 'Platform events only';
?>
<?php if (!empty($activeFilters)): ?>
<div style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:12px;">
    <span style="font-size:11px; color:var(--text-muted); align-self:center;">Active filters:</span>
    <?php foreach ($activeFilters as $f): ?>
    <span class="filter-chip"><?= e($f) ?></span>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card" style="padding:0; overflow-x:auto;">
    <table class="audit-table">
        <thead>
            <tr>
                <th style="min-width:130px;">When</th>
                <th style="min-width:140px;">Action</th>
                <th style="min-width:120px;">User</th>
                <th style="min-width:90px;">Role</th>
                <?php if ($isSuperAdmin): ?><th style="min-width:110px;">Airline</th><?php endif; ?>
                <th style="min-width:90px;">Entity</th>
                <th>Details</th>
                <th style="min-width:90px;">IP</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($logs)): ?>
        <tr>
            <td colspan="<?= $isSuperAdmin ? 8 : 7 ?>"
                style="text-align:center; padding:24px; color:var(--text-muted);">
                No audit log entries found<?= !empty($activeFilters) ? ' matching the current filters' : '' ?>.
            </td>
        </tr>
        <?php else: ?>
        <?php foreach ($logs as $row):
            // Pick color from action code or fallback to grey
            $color = '#6b7280';
            foreach ($actionColors as $key => $c) {
                if (stripos($row['action'], $key) !== false) { $color = $c; break; }
            }
        ?>
        <tr>
            <td style="white-space:nowrap; font-size:11px; color:var(--text-muted);">
                <?= date('d M Y', strtotime($row['created_at'])) ?><br>
                <?= date('H:i:s', strtotime($row['created_at'])) ?>
            </td>
            <td>
                <span class="action-badge" style="background:<?= $color ?>;">
                    <?= e($row['action']) ?>
                </span>
            </td>
            <td style="font-size:12px;"><?= e($row['user_name'] ?? '—') ?></td>
            <td style="font-size:11px; color:var(--text-muted);">
                <?= e(str_replace('_', ' ', $row['actor_role'] ?? '')) ?: '—' ?>
            </td>
            <?php if ($isSuperAdmin): ?>
            <td style="font-size:11px; color:var(--text-muted);">
                <?= e($row['tenant_name'] ?? '—') ?>
            </td>
            <?php endif; ?>
            <td style="font-size:11px; color:var(--text-muted);">
                <?php if ($row['entity_type']): ?>
                    <?= e($row['entity_type']) ?>
                    <?php if ($row['entity_id']): ?>
                        <span style="color:var(--text-muted);">#<?= $row['entity_id'] ?></span>
                    <?php endif; ?>
                <?php else: ?>—<?php endif; ?>
            </td>
            <td style="font-size:12px; max-width:280px; word-break:break-word;">
                <?= e($row['details'] ?? '—') ?>
            </td>
            <td style="font-size:11px; color:var(--text-muted); white-space:nowrap;">
                <?= e($row['ip_address'] ?? '—') ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
    <a href="<?= '/audit-log?' . http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">← Prev</a>
    <?php endif; ?>
    <?php
    $start = max(1, $page - 2);
    $end   = min($totalPages, $page + 2);
    for ($p = $start; $p <= $end; $p++):
    ?>
    <a href="<?= '/audit-log?' . http_build_query(array_merge($_GET, ['page' => $p])) ?>"
       class="<?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
    <?php endfor; ?>
    <?php if ($page < $totalPages): ?>
    <a href="<?= '/audit-log?' . http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next →</a>
    <?php endif; ?>
    <span style="font-size:12px; color:var(--text-muted);">Page <?= $page ?> of <?= $totalPages ?></span>
</div>
<?php endif; ?>
