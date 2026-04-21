<?php
/**
 * Login Activity viewer
 * Variables: $activity, $page, $totalPages, $filterEmail, $filterResult, $isSuperAdmin
 */
$isSuperAdmin   = $isSuperAdmin   ?? (function_exists('hasRole') && hasRole('super_admin'));
$activity       = $activity       ?? [];
$page           = $page           ?? 1;
$totalPages     = $totalPages     ?? 1;
$filterEmail    = $filterEmail    ?? '';
$filterResult   = $filterResult   ?? '';
?>
<style>
.audit-table { width:100%; border-collapse:collapse; font-size:12px; }
.audit-table th, .audit-table td { border:1px solid var(--border); padding:6px 8px; vertical-align:top; }
.audit-table th { background:var(--bg-secondary); font-weight:600; font-size:11px; text-transform:uppercase; white-space:nowrap; }
.audit-table tr:hover td { background:rgba(59,130,246,.04); }
.filter-bar { display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end; margin-bottom:16px; }
.filter-bar label { font-size:11px; font-weight:600; color:var(--text-muted); display:block; margin-bottom:3px; }
.filter-bar input, .filter-bar select { padding:5px 8px; border-radius:6px; border:1px solid var(--border); background:var(--bg-card); color:var(--text); font-size:12px; }
.pagination { display:flex; gap:6px; align-items:center; margin-top:14px; flex-wrap:wrap; }
.pagination a { padding:4px 10px; border:1px solid var(--border); border-radius:5px; font-size:12px; text-decoration:none; color:var(--text); }
.pagination a.active { background:var(--accent-blue, #3b82f6); color:#fff; border-color:transparent; }
.tab-nav { display:flex; gap:8px; margin-bottom:16px; }
.tab-nav a { padding:6px 14px; border-radius:6px; font-size:13px; font-weight:600; text-decoration:none; color:var(--text-muted); border:1px solid var(--border); }
.tab-nav a.active { background:var(--accent-blue, #3b82f6); color:#fff; border-color:transparent; }
.badge-success { background:#10b981; color:#fff; border-radius:4px; padding:1px 7px; font-size:10px; font-weight:700; }
.badge-fail    { background:#ef4444; color:#fff; border-radius:4px; padding:1px 7px; font-size:10px; font-weight:700; }
.badge-web     { background:#3b82f6; color:#fff; border-radius:4px; padding:1px 7px; font-size:10px; font-weight:700; }
.badge-api     { background:#8b5cf6; color:#fff; border-radius:4px; padding:1px 7px; font-size:10px; font-weight:700; }
</style>

<div class="tab-nav">
    <a href="/audit-log">Action Log</a>
    <a href="/audit-log/logins" class="active">Login Activity</a>
</div>

<form method="GET" action="/audit-log/logins">
<div class="filter-bar">
    <div>
        <label>Email contains</label>
        <input type="text" name="email" value="<?= e($filterEmail) ?>" placeholder="e.g. demo.pilot" style="width:200px;">
    </div>
    <div>
        <label>Result</label>
        <select name="result" style="width:120px;">
            <option value="">All</option>
            <option value="success" <?= $filterResult === 'success' ? 'selected' : '' ?>>Success</option>
            <option value="fail"    <?= $filterResult === 'fail'    ? 'selected' : '' ?>>Failed</option>
        </select>
    </div>
    <div>
        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
        <a href="/audit-log/logins" class="btn btn-sm btn-outline" style="margin-left:4px;">Clear</a>
    </div>
</div>
</form>

<div class="card" style="padding:0;overflow-x:auto;">
    <table class="audit-table">
        <thead>
            <tr>
                <th style="min-width:140px;">When</th>
                <th style="min-width:140px;">Email</th>
                <?php if ($isSuperAdmin): ?><th style="min-width:110px;">Tenant</th><?php endif; ?>
                <th style="min-width:80px;">Result</th>
                <th style="min-width:60px;">Source</th>
                <th style="min-width:100px;">IP Address</th>
                <th>User Agent</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($activity)): ?>
        <tr><td colspan="<?= $isSuperAdmin ? 7 : 6 ?>" style="text-align:center;padding:24px;color:var(--text-muted);">No login activity found.</td></tr>
        <?php else: ?>
        <?php foreach ($activity as $row): ?>
        <tr>
            <td style="white-space:nowrap;font-size:11px;color:var(--text-muted);">
                <?= date('d M Y', strtotime($row['created_at'])) ?><br>
                <?= date('H:i:s', strtotime($row['created_at'])) ?>
            </td>
            <td style="font-size:12px;"><?= e($row['email']) ?></td>
            <?php if ($isSuperAdmin): ?>
            <td style="font-size:11px;color:var(--text-muted);"><?= e($row['tenant_name'] ?? '—') ?></td>
            <?php endif; ?>
            <td>
                <?php if ($row['success']): ?>
                <span class="badge-success">✓ OK</span>
                <?php else: ?>
                <span class="badge-fail">✕ FAIL</span>
                <?php endif; ?>
            </td>
            <td>
                <span class="badge-<?= $row['source'] === 'api' ? 'api' : 'web' ?>"><?= strtoupper(e($row['source'])) ?></span>
            </td>
            <td style="font-size:11px;white-space:nowrap;"><?= e($row['ip_address'] ?? '—') ?></td>
            <td style="font-size:10px;color:var(--text-muted);max-width:200px;word-break:break-all;"><?= e(substr($row['user_agent'] ?? '', 0, 100)) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
    <a href="<?= '/audit-log/logins?' . http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">← Prev</a>
    <?php endif; ?>
    <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
    <a href="<?= '/audit-log/logins?' . http_build_query(array_merge($_GET, ['page' => $p])) ?>"
       class="<?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
    <?php endfor; ?>
    <?php if ($page < $totalPages): ?>
    <a href="<?= '/audit-log/logins?' . http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next →</a>
    <?php endif; ?>
    <span style="font-size:12px;color:var(--text-muted);">Page <?= $page ?> of <?= $totalPages ?></span>
</div>
<?php endif; ?>
