<?php
$pageTitle    = 'FDM Analyst Dashboard';
$pageSubtitle = 'Flight Data Monitoring';
ob_start();
$s = $data['fdm_summary'];
$eventTypes = FdmModel::eventTypes();
$severities = FdmModel::severities();
?>

<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-label">FDM Uploads</div>
        <div class="stat-value"><?= $s['total_uploads'] ?></div>
    </div>
    <div class="stat-card cyan">
        <div class="stat-label">Total Events</div>
        <div class="stat-value"><?= $s['total_events'] ?></div>
    </div>
    <div class="stat-card red">
        <div class="stat-label">Critical / High Events</div>
        <div class="stat-value"><?= $s['critical_high'] ?></div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Active Staff</div>
        <div class="stat-value"><?= $data['active_staff'] ?></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
    <!-- Events by type -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Events by Type</div>
            <a href="/fdm" class="btn btn-sm btn-primary">FDM Module →</a>
        </div>
        <?php if (empty($s['events_by_type'])): ?>
            <div class="empty-state">
                <div class="icon">📊</div>
                <p>No FDM events yet. <a href="/fdm/upload">Upload data →</a></p>
            </div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Type</th><th>Count</th></tr></thead>
                <tbody>
                <?php foreach ($s['events_by_type'] as $row):
                    $et = $eventTypes[$row['event_type']] ?? ['label' => ucfirst($row['event_type']), 'icon' => '📋'];
                ?>
                <tr>
                    <td><?= $et['icon'] ?> <?= e($et['label']) ?></td>
                    <td><strong><?= $row['count'] ?></strong></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Recent events -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Recent Events</div>
            <a href="/fdm" class="btn btn-sm btn-outline">All Records →</a>
        </div>
        <?php if (empty($data['recent_events'])): ?>
            <div class="empty-state"><p>No events recorded yet.</p></div>
        <?php else: ?>
        <ul class="activity-list">
            <?php foreach ($data['recent_events'] as $ev):
                $et  = $eventTypes[$ev['event_type']] ?? ['label' => ucfirst($ev['event_type']), 'icon' => '📋'];
                $sev = $severities[$ev['severity']]   ?? ['label' => ucfirst($ev['severity']),   'color' => '#6b7280'];
            ?>
            <li class="activity-item">
                <div class="activity-dot" style="background:<?= $sev['color'] ?>"></div>
                <div>
                    <div><?= $et['icon'] ?> <strong><?= e($et['label']) ?></strong>
                        <?php if ($ev['aircraft_reg']): ?> — <code><?= e($ev['aircraft_reg']) ?></code><?php endif; ?>
                    </div>
                    <div class="activity-time">
                        <span style="background:<?= $sev['color'] ?>;color:#fff;padding:1px 6px;border-radius:3px;font-size:10px;font-weight:700;"><?= e($sev['label']) ?></span>
                        <?= $ev['flight_date'] ? ' · ' . e($ev['flight_date']) : '' ?>
                        <?= $ev['flight_phase'] ? ' · ' . e($ev['flight_phase']) : '' ?>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>

<!-- Audit trail -->
<div class="card">
    <div class="card-header"><div class="card-title">Platform Audit Trail</div></div>
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

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/app.php';
