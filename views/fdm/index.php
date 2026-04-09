<?php
/**
 * FDM index — uploads list + summary stats
 * Variables: $uploads, $summary, $eventTypes, $severities
 */
?>
<style>
.sev-badge { display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:700; color:#fff; }
</style>

<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-label">Total Uploads</div>
        <div class="stat-value"><?= $summary['total_uploads'] ?></div>
    </div>
    <div class="stat-card cyan">
        <div class="stat-label">Total Events</div>
        <div class="stat-value"><?= $summary['total_events'] ?></div>
    </div>
    <div class="stat-card red">
        <div class="stat-label">Critical / High Events</div>
        <div class="stat-value"><?= $summary['critical_high'] ?></div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-label">Event Types Recorded</div>
        <div class="stat-value"><?= count($summary['events_by_type']) ?></div>
    </div>
</div>

<?php if (!empty($summary['events_by_type'])): ?>
<div class="card">
    <div class="card-header"><div class="card-title">Events by Type</div></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Event Type</th><th>Count</th></tr></thead>
            <tbody>
            <?php foreach ($summary['events_by_type'] as $row):
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
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">FDM Uploads</div>
        <a href="/fdm/upload" class="btn btn-sm btn-primary">＋ Upload / Log Event</a>
    </div>
    <?php if (empty($uploads)): ?>
        <div class="empty-state">
            <div class="icon">📊</div>
            <h3>No FDM data yet</h3>
            <p>Upload a CSV flight data file or log a manual event to get started.</p>
            <a href="/fdm/upload" class="btn btn-sm btn-primary" style="margin-top:8px;">Upload FDM Data →</a>
        </div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>File / Entry</th><th>Flight Date</th><th>Aircraft</th><th>Flight No.</th><th>Events</th><th>Uploaded By</th><th>Uploaded</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($uploads as $u): ?>
            <tr>
                <td><a href="/fdm/view/<?= $u['id'] ?>" style="font-weight:600;"><?= e($u['original_name']) ?></a></td>
                <td><?= $u['flight_date'] ? e($u['flight_date']) : '<span style="color:var(--text-muted)">—</span>' ?></td>
                <td><?= $u['aircraft_reg'] ? '<code>' . e($u['aircraft_reg']) . '</code>' : '—' ?></td>
                <td><?= $u['flight_number'] ? e($u['flight_number']) : '—' ?></td>
                <td>
                    <span style="font-weight:700;<?= $u['event_count'] > 0 ? 'color:var(--accent-red)' : '' ?>">
                        <?= $u['event_count'] ?>
                    </span>
                </td>
                <td><?= e($u['uploader_name']) ?></td>
                <td style="font-size:12px;color:var(--text-muted);"><?= formatDateTime($u['created_at']) ?></td>
                <td>
                    <a href="/fdm/view/<?= $u['id'] ?>" class="btn btn-xs btn-outline">View</a>
                    <?php if (hasAnyRole(['fdm_analyst', 'airline_admin', 'super_admin'])): ?>
                    <form method="POST" action="/fdm/delete/<?= $u['id'] ?>" style="display:inline;" onsubmit="return confirm('Delete this FDM record and all its events?');">
                        <?= csrfField() ?>
                        <button type="submit" class="btn btn-xs btn-outline" style="color:var(--accent-red);border-color:var(--accent-red);">Delete</button>
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
