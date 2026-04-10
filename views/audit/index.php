<?php
/**
 * Audit Log viewer
 * Variables: $logs, $page, $totalPages, $totalLogs, $filterAction, $filterUser, $filterEntity, $entityTypes, $isSuperAdmin
 */

$actionColors = [
    'Web Login'   => '#10b981', 'Web Logout' => '#6b7280',
    'Created User' => '#3b82f6', 'Updated User' => '#3b82f6', 'Suspended User' => '#ef4444', 'Activated User' => '#10b981',
    'Unauthorized Access Attempt' => '#ef4444',
    'roster_assigned' => '#f59e0b', 'roster_deleted' => '#ef4444',
    'fdm_upload' => '#8b5cf6', 'fdm_delete' => '#ef4444', 'fdm_event_added' => '#06b6d4', 'fdm_event_deleted' => '#ef4444',
    'fdm_manual_event' => '#8b5cf6',
    'notice_created' => '#3b82f6', 'notice_updated' => '#3b82f6', 'notice_deleted' => '#ef4444',
    'Approved Device' => '#10b981', 'Rejected Device' => '#ef4444', 'Revoked Device' => '#ef4444',
];
?>
<style>
.audit-table { width:100%; border-collapse:collapse; font-size:12px; }
.audit-table th, .audit-table td { border:1px solid var(--border); padding:6px 8px; vertical-align:top; }
.audit-table th { background:var(--bg-secondary); font-weight:600; font-size:11px; text-transform:uppercase; white-space:nowrap; }
.audit-table tr:hover td { background:rgba(59,130,246,.04); }
.action-badge { display:inline-block; border-radius:4px; padding:1px 7px; font-size:10px; font-weight:700; color:#fff; white-space:nowrap; }
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
</style>

<div class="tab-nav">
    <a href="/audit-log" class="active">Action Log</a>
    <a href="/audit-log/logins">Login Activity</a>
</div>

<form method="GET" action="/audit-log">
<div class="filter-bar">
    <div>
        <label>Action contains</label>
        <input type="text" name="action" value="<?= e($filterAction) ?>" placeholder="e.g. roster, login, delete" style="width:180px;">
    </div>
    <div>
        <label>User contains</label>
        <input type="text" name="user" value="<?= e($filterUser) ?>" placeholder="e.g. James" style="width:150px;">
    </div>
    <div>
        <label>Entity type</label>
        <select name="entity" style="width:130px;">
            <option value="">All</option>
            <?php foreach ($entityTypes as $et): ?>
            <option value="<?= e($et['entity_type']) ?>" <?= $filterEntity === $et['entity_type'] ? 'selected' : '' ?>><?= e($et['entity_type']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
        <a href="/audit-log" class="btn btn-sm btn-outline" style="margin-left:4px;">Clear</a>
    </div>
    <div style="margin-left:auto;font-size:12px;color:var(--text-muted);align-self:center;">
        <?= number_format($totalLogs) ?> record<?= $totalLogs !== 1 ? 's' : '' ?>
    </div>
</div>
</form>

<div class="card" style="padding:0;overflow-x:auto;">
    <table class="audit-table">
        <thead>
            <tr>
                <th style="min-width:140px;">When</th>
                <th style="min-width:130px;">Action</th>
                <th style="min-width:130px;">User</th>
                <?php if ($isSuperAdmin): ?><th style="min-width:110px;">Tenant</th><?php endif; ?>
                <th style="min-width:90px;">Entity</th>
                <th>Details</th>
                <th style="min-width:100px;">IP</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($logs)): ?>
        <tr><td colspan="<?= $isSuperAdmin ? 7 : 6 ?>" style="text-align:center;padding:24px;color:var(--text-muted);">No audit log entries found.</td></tr>
        <?php else: ?>
        <?php foreach ($logs as $row):
            $color = '#6b7280';
            foreach ($actionColors as $key => $c) {
                if (stripos($row['action'], $key) !== false) { $color = $c; break; }
            }
        ?>
        <tr>
            <td style="white-space:nowrap;font-size:11px;color:var(--text-muted);">
                <?= date('d M Y', strtotime($row['created_at'])) ?><br>
                <?= date('H:i:s', strtotime($row['created_at'])) ?>
            </td>
            <td>
                <span class="action-badge" style="background:<?= $color ?>;"><?= e($row['action']) ?></span>
            </td>
            <td style="font-size:12px;"><?= e($row['user_name'] ?? '—') ?></td>
            <?php if ($isSuperAdmin): ?>
            <td style="font-size:11px;color:var(--text-muted);"><?= e($row['tenant_name'] ?? '—') ?></td>
            <?php endif; ?>
            <td style="font-size:11px;color:var(--text-muted);">
                <?php if ($row['entity_type']): ?>
                <span><?= e($row['entity_type']) ?></span>
                <?php if ($row['entity_id']): ?> <span style="color:var(--text-muted);">#<?= $row['entity_id'] ?></span><?php endif; ?>
                <?php else: ?>—<?php endif; ?>
            </td>
            <td style="font-size:12px;max-width:300px;word-break:break-word;"><?= e($row['details'] ?? '—') ?></td>
            <td style="font-size:11px;color:var(--text-muted);white-space:nowrap;"><?= e($row['ip_address'] ?? '—') ?></td>
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
    <span style="font-size:12px;color:var(--text-muted);">Page <?= $page ?> of <?= $totalPages ?></span>
</div>
<?php endif; ?>
