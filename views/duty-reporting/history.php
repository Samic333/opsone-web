<?php /** OpsOne — Duty Reporting History (filterable list) */ ?>

<!-- Filter bar -->
<div class="card" style="padding:16px 20px; margin-bottom:20px;">
    <form method="GET" action="/duty-reporting/history" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <label class="text-xs text-muted">From</label>
        <input type="date" name="from" value="<?= e($fromDate) ?>" class="form-control" style="width:auto;">
        <label class="text-xs text-muted">To</label>
        <input type="date" name="to"   value="<?= e($toDate)   ?>" class="form-control" style="width:auto;">
        <select name="role" class="form-control" style="width:auto; min-width:150px;">
            <option value="">All roles</option>
            <?php foreach (['pilot','cabin_crew','engineer','chief_pilot','head_cabin_crew','engineering_manager','base_manager'] as $r): ?>
                <option value="<?= $r ?>" <?= $roleF === $r ? 'selected' : '' ?>><?= e(ucwords(str_replace('_',' ',$r))) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline btn-sm" type="submit">Filter</button>
        <a href="/duty-reporting/history" class="btn btn-ghost btn-sm">Clear</a>
        <span class="text-sm text-muted" style="margin-left:auto;"><?= count($records) ?> record<?= count($records) !== 1 ? 's' : '' ?></span>
    </form>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Crew</th>
                <th>Role</th>
                <th>Base</th>
                <th>Check-in (UTC)</th>
                <th>Check-out (UTC)</th>
                <th>Duration</th>
                <th>State</th>
                <th>Geofence</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($records)): ?>
            <tr><td colspan="9">
                <div class="empty-state">
                    <div class="icon">📋</div>
                    <h3>No duty records in this range</h3>
                    <p>Try adjusting the filter or extending the date range.</p>
                </div>
            </td></tr>
        <?php else: ?>
            <?php foreach ($records as $r): ?>
                <?php
                $stateColor = match ($r['state']) {
                    'checked_in', 'on_duty'        => '#10b981',
                    'checked_out'                  => '#6366f1',
                    'exception_pending_review'     => '#f59e0b',
                    'exception_approved'           => '#8b5cf6',
                    'exception_rejected'           => '#ef4444',
                    'missed_report'                => '#ef4444',
                    default                        => '#6b7280',
                };
                $dur = isset($r['duration_minutes']) && $r['duration_minutes'] !== null
                    ? sprintf('%dh %dm', intdiv((int)$r['duration_minutes'], 60), (int)$r['duration_minutes'] % 60)
                    : '—';
                $geoLabel = $r['inside_geofence'] === null ? '—' :
                    ((int)$r['inside_geofence'] === 1 ? 'Inside' : 'Outside');
                ?>
                <tr>
                    <td>
                        <strong><?= e($r['user_name'] ?? '—') ?></strong>
                    </td>
                    <td class="text-sm"><?= e(ucfirst(str_replace('_',' ', $r['role_at_event'] ?? '—'))) ?></td>
                    <td class="text-sm"><?= e($r['base_name'] ?? '—') ?></td>
                    <td class="text-sm text-muted"><?= e($r['check_in_at_utc'] ?? '—') ?></td>
                    <td class="text-sm text-muted"><?= e($r['check_out_at_utc'] ?? '—') ?></td>
                    <td class="text-sm"><?= e($dur) ?></td>
                    <td><span class="status-badge" style="--badge-color: <?= $stateColor ?>"><?= e(ucfirst(str_replace('_',' ', $r['state']))) ?></span></td>
                    <td class="text-sm"><?= e($geoLabel) ?></td>
                    <td><a href="/duty-reporting/report/<?= (int)$r['id'] ?>" class="btn btn-ghost btn-sm">View</a></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
