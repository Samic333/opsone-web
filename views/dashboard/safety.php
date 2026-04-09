<?php
$pageTitle    = 'Safety Manager Dashboard';
$pageSubtitle = 'Safety Management System';
ob_start();
$c = $data['compliance'];
$f = $data['fdm_summary'];
?>

<div class="stats-grid">
    <div class="stat-card red">
        <div class="stat-label">Critical/Urgent Notices</div>
        <div class="stat-value"><?= $data['critical_notices'] ?></div>
    </div>
    <div class="stat-card blue">
        <div class="stat-label">Published Notices</div>
        <div class="stat-value"><?= $data['total_notices'] ?></div>
    </div>
    <div class="stat-card <?= $f['critical_high'] > 0 ? 'red' : 'cyan' ?>">
        <div class="stat-label">FDM Critical/High Events</div>
        <div class="stat-value"><?= $f['critical_high'] ?></div>
    </div>
    <div class="stat-card <?= ($c['expired_licenses'] + $c['expiring_licenses'] + $c['expiring_medicals']) > 0 ? 'yellow' : 'green' ?>">
        <div class="stat-label">Compliance Alerts</div>
        <div class="stat-value"><?= $c['expired_licenses'] + $c['expiring_licenses'] + $c['expiring_medicals'] ?></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
    <!-- Safety Notices -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Safety Notices</div>
            <a href="/notices" class="btn btn-sm btn-primary">Manage →</a>
        </div>
        <?php if (empty($data['recent_notices'])): ?>
            <div class="empty-state"><p>No active notices</p></div>
        <?php else: ?>
        <ul class="activity-list">
            <?php foreach ($data['recent_notices'] as $n): ?>
            <li class="activity-item">
                <div class="activity-dot" style="background: <?= $n['priority'] === 'critical' ? 'var(--accent-red)' : ($n['priority'] === 'urgent' ? 'var(--accent-amber, #f59e0b)' : 'var(--accent-blue)') ?>"></div>
                <div>
                    <div><strong><?= e($n['title']) ?></strong></div>
                    <div class="activity-time"><?= statusBadge($n['priority']) ?> · <?= formatDateTime($n['published_at'] ?? $n['created_at']) ?></div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>

    <!-- Audit Trail -->
    <div class="card">
        <div class="card-header"><div class="card-title">Audit Trail</div></div>
        <?php if (empty($data['recent_activity'])): ?>
            <div class="empty-state"><p>No recent activity</p></div>
        <?php else: ?>
        <ul class="activity-list">
            <?php foreach ($data['recent_activity'] as $log): ?>
            <li class="activity-item">
                <div class="activity-dot"></div>
                <div>
                    <div><strong><?= e($log['user_name'] ?? 'System') ?></strong> — <?= e($log['action']) ?></div>
                    <?php if ($log['details']): ?><div class="text-xs text-muted"><?= e($log['details']) ?></div><?php endif; ?>
                    <div class="activity-time"><?= formatDateTime($log['created_at']) ?></div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
    <!-- FDM Summary -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">FDM Analysis</div>
            <a href="/fdm" class="btn btn-sm btn-outline">View FDM →</a>
        </div>
        <?php if ($f['total_uploads'] === 0): ?>
            <div class="empty-state">
                <div class="icon">📊</div>
                <p>No FDM data uploaded yet.</p>
                <a href="/fdm/upload" class="btn btn-sm btn-primary" style="margin-top:8px;">Upload Data →</a>
            </div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Metric</th><th>Count</th></tr></thead>
                <tbody>
                    <tr><td>Total Uploads</td><td><strong><?= $f['total_uploads'] ?></strong></td></tr>
                    <tr><td>Total Events</td><td><strong><?= $f['total_events'] ?></strong></td></tr>
                    <tr><td style="color:var(--accent-red);">Critical / High Events</td><td><strong style="color:var(--accent-red);"><?= $f['critical_high'] ?></strong></td></tr>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Compliance Summary -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Crew Compliance</div>
            <a href="/compliance" class="btn btn-sm btn-outline">Full Report →</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Category</th><th>Count</th></tr></thead>
                <tbody>
                    <tr>
                        <td style="<?= $c['expired_licenses'] > 0 ? 'color:var(--accent-red);font-weight:600;' : '' ?>">Expired Licences</td>
                        <td><strong style="<?= $c['expired_licenses'] > 0 ? 'color:var(--accent-red);' : '' ?>"><?= $c['expired_licenses'] ?></strong></td>
                    </tr>
                    <tr>
                        <td style="<?= $c['expiring_licenses'] > 0 ? 'color:var(--accent-amber,#f59e0b);font-weight:600;' : '' ?>">Licences Expiring (90d)</td>
                        <td><strong style="<?= $c['expiring_licenses'] > 0 ? 'color:var(--accent-amber,#f59e0b);' : '' ?>"><?= $c['expiring_licenses'] ?></strong></td>
                    </tr>
                    <tr>
                        <td style="<?= $c['expiring_medicals'] > 0 ? 'color:var(--accent-amber,#f59e0b);font-weight:600;' : '' ?>">Medicals Expiring (90d)</td>
                        <td><strong style="<?= $c['expiring_medicals'] > 0 ? 'color:var(--accent-amber,#f59e0b);' : '' ?>"><?= $c['expiring_medicals'] ?></strong></td>
                    </tr>
                    <tr>
                        <td style="<?= $c['expiring_passports'] > 0 ? 'color:var(--accent-amber,#f59e0b);font-weight:600;' : '' ?>">Passports Expiring (180d)</td>
                        <td><strong style="<?= $c['expiring_passports'] > 0 ? 'color:var(--accent-amber,#f59e0b);' : '' ?>"><?= $c['expiring_passports'] ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/app.php';
