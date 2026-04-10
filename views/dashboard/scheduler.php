<?php
$pageTitle    = 'Scheduler Dashboard';
$pageSubtitle = 'Schedule Overview';
ob_start();
?>
<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-label">Active Staff</div>
        <div class="stat-value"><?= $data['active_staff'] ?></div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Roster Entries This Month</div>
        <div class="stat-value"><?= $data['roster_count_month'] ?></div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-label">Crew on Duty Today</div>
        <div class="stat-value"><?= $data['on_duty_today'] ?></div>
    </div>
    <div class="stat-card <?= count($data['flagged_crew']) > 0 ? 'red' : 'cyan' ?>">
        <div class="stat-label">Compliance Issues</div>
        <div class="stat-value"><?= count($data['flagged_crew']) ?></div>
    </div>
</div>

<!-- Today's duty summary -->
<?php if (!empty($data['today_duties'])): ?>
<div class="card">
    <div class="card-header">
        <div class="card-title">Today's Duties — <?= date('d M Y') ?></div>
        <a href="/roster" class="btn btn-sm btn-primary">Full Roster →</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Crew Member</th><th>Duty</th><th>Code</th><th>Notes</th></tr></thead>
            <tbody>
            <?php foreach ($data['today_duties'] as $row):
                $dutyTypes = RosterModel::dutyTypes();
                $dt = $dutyTypes[$row['duty_type']] ?? ['label' => ucfirst($row['duty_type']), 'color' => '#6b7280', 'code' => '?'];
            ?>
            <tr>
                <td><strong><?= e($row['user_name']) ?></strong>
                    <?php if ($row['employee_id']): ?><span class="text-xs text-muted">(<?= e($row['employee_id']) ?>)</span><?php endif; ?>
                </td>
                <td><span style="display:inline-block;background:<?= $dt['color'] ?>;color:#fff;border-radius:4px;padding:2px 8px;font-size:11px;font-weight:700;"><?= $dt['label'] ?></span></td>
                <td><code><?= e($row['duty_code'] ?? $dt['code']) ?></code></td>
                <td style="color:var(--text-muted);font-size:12px;"><?= e($row['notes'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="card-header">
        <div class="card-title">Today's Duties — <?= date('d M Y') ?></div>
        <a href="/roster" class="btn btn-sm btn-primary">Full Roster →</a>
    </div>
    <div class="empty-state">
        <div class="icon">📅</div>
        <p>No duties assigned for today yet.</p>
        <a href="/roster/assign" class="btn btn-sm btn-primary" style="margin-top:8px;">Assign Duty →</a>
    </div>
</div>
<?php endif; ?>

<!-- Compliance alerts -->
<?php if (!empty($data['flagged_crew'])): ?>
<div class="card">
    <div class="card-header">
        <div class="card-title" style="color:#ef4444;">✕ Compliance Issues — Action Required</div>
        <a href="/compliance" class="btn btn-sm btn-outline">Full Report →</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Crew Member</th><th>Role</th><th>Issues</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($data['flagged_crew'] as $fc): ?>
            <tr>
                <td><strong><?= e($fc['user_name']) ?></strong>
                    <?php if ($fc['employee_id']): ?><span class="text-xs text-muted"> (<?= e($fc['employee_id']) ?>)</span><?php endif; ?>
                </td>
                <td style="font-size:12px;"><?= e($fc['role_name']) ?></td>
                <td>
                    <ul style="margin:0;padding-left:16px;font-size:11px;color:#ef4444;">
                        <?php foreach ($fc['compliance']['issues'] as $iss): ?>
                        <li><?= e($iss) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </td>
                <td>
                    <a href="/roster/suggest/<?= $fc['id'] ?>?date=<?= urlencode(date('Y-m-d')) ?>"
                       class="btn btn-sm btn-primary" style="font-size:11px;">Find Replacement</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Today's standby pool -->
<?php if (!empty($data['standby_today'])): ?>
<div class="card">
    <div class="card-header">
        <div class="card-title">Standby Pool — Today</div>
        <a href="/roster/standby" class="btn btn-sm btn-outline">Full Pool →</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Crew Member</th><th>Role</th><th>Code / Notes</th><th>Compliance</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($data['standby_today'] as $sb): ?>
            <tr>
                <td><strong><?= e($sb['user_name']) ?></strong>
                    <?php if ($sb['employee_id']): ?><span class="text-xs text-muted"> (<?= e($sb['employee_id']) ?>)</span><?php endif; ?>
                </td>
                <td style="font-size:12px;"><?= e($sb['role_name']) ?></td>
                <td>
                    <code><?= e($sb['duty_code'] ?: 'SBY') ?></code>
                    <?php if ($sb['notes']): ?><span style="font-size:11px;color:var(--text-muted);"> — <?= e($sb['notes']) ?></span><?php endif; ?>
                </td>
                <td>
                    <?php $c = $sb['compliance'];
                    if (!$c): ?>
                        <span style="color:#10b981;font-weight:600;font-size:12px;">✓ OK</span>
                    <?php elseif ($c['severity'] === 'critical'): ?>
                        <span style="color:#ef4444;font-weight:600;font-size:12px;">✕ Non-compliant</span>
                    <?php else: ?>
                        <span style="color:#f59e0b;font-weight:600;font-size:12px;">⚠ Expiry soon</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="/roster/suggest/<?= $sb['user_id'] ?>?date=<?= urlencode(date('Y-m-d')) ?>"
                       class="btn btn-sm btn-outline" style="font-size:11px;">Replace</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Quick links -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
    <div class="card">
        <div class="card-header"><div class="card-title">Monthly Roster</div></div>
        <div class="empty-state">
            <div class="icon">🗓</div>
            <p>View and manage the full crew roster grid.</p>
            <a href="/roster" class="btn btn-sm btn-primary" style="margin-top:8px;">Open Roster →</a>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><div class="card-title">Standby Pool</div></div>
        <div class="empty-state">
            <div class="icon">📋</div>
            <p>View all standby crew and their compliance status.</p>
            <a href="/roster/standby" class="btn btn-sm btn-primary" style="margin-top:8px;">View Standby →</a>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><div class="card-title">Assign Duty</div></div>
        <div class="empty-state">
            <div class="icon">✏️</div>
            <p>Add or update individual crew duty assignments.</p>
            <a href="/roster/assign" class="btn btn-sm btn-primary" style="margin-top:8px;">Assign Duty →</a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/app.php';
