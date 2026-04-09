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
    <div class="stat-card cyan">
        <div class="stat-label">Crew on Leave Today</div>
        <div class="stat-value"><?= $data['on_leave_today'] ?></div>
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

<!-- Quick links -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
    <div class="card">
        <div class="card-header"><div class="card-title">Monthly Roster</div></div>
        <div class="empty-state">
            <div class="icon">🗓</div>
            <p>View and manage the full crew roster grid.</p>
            <a href="/roster" class="btn btn-sm btn-primary" style="margin-top:8px;">Open Roster →</a>
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
