<?php /** OpsOne — Duty Record Detail */ ?>

<?php
$stateColor = match ($report['state']) {
    'checked_in', 'on_duty'        => '#10b981',
    'checked_out'                  => '#6366f1',
    'exception_pending_review'     => '#f59e0b',
    'exception_approved'           => '#8b5cf6',
    'exception_rejected'           => '#ef4444',
    'missed_report'                => '#ef4444',
    default                        => '#6b7280',
};
$dur = isset($report['duration_minutes']) && $report['duration_minutes'] !== null
    ? sprintf('%dh %dm', intdiv((int)$report['duration_minutes'], 60), (int)$report['duration_minutes'] % 60)
    : '—';
$geoLabel = $report['inside_geofence'] === null ? '—'
    : ((int)$report['inside_geofence'] === 1 ? 'Inside' : 'Outside');
?>

<!-- Overview card -->
<div class="card" style="padding:20px; margin-bottom:20px;">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
        <h3 style="margin:0; font-size:16px;"><?= e($user['name'] ?? 'Unknown') ?></h3>
        <span class="status-badge" style="--badge-color: <?= $stateColor ?>"><?= e(ucfirst(str_replace('_',' ', $report['state']))) ?></span>
        <span class="text-xs text-muted" style="margin-left:auto;">Record #<?= (int)$report['id'] ?></span>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px;">
        <div>
            <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em;">Role at event</div>
            <div><?= e(ucfirst(str_replace('_',' ', $report['role_at_event'] ?? '—'))) ?></div>
        </div>
        <div>
            <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em;">Base</div>
            <div><?= $base ? e($base['name'] . ' (' . $base['code'] . ')') : '—' ?></div>
        </div>
        <div>
            <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em;">Method</div>
            <div><?= e(ucfirst(str_replace('_',' ', $report['check_in_method'] ?? '—'))) ?></div>
        </div>
        <div>
            <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em;">Geofence</div>
            <div><?= e($geoLabel) ?></div>
        </div>
        <div>
            <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em;">Check-in UTC</div>
            <div class="text-sm"><?= e($report['check_in_at_utc'] ?? '—') ?></div>
        </div>
        <div>
            <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em;">Check-out UTC</div>
            <div class="text-sm"><?= e($report['check_out_at_utc'] ?? '—') ?></div>
        </div>
        <div>
            <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em;">Duration</div>
            <div><?= e($dur) ?></div>
        </div>
        <div>
            <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em;">Coords (in)</div>
            <div class="text-sm">
                <?php if ($report['check_in_lat'] !== null && $report['check_in_lng'] !== null): ?>
                    <?= e(number_format((float)$report['check_in_lat'], 5)) ?>,
                    <?= e(number_format((float)$report['check_in_lng'], 5)) ?>
                <?php else: ?>—<?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($report['notes'])): ?>
    <div style="margin-top:14px; padding-top:14px; border-top:1px solid var(--card-border, #eee);">
        <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.08em;">Notes</div>
        <div style="white-space:pre-wrap;"><?= e($report['notes']) ?></div>
    </div>
    <?php endif; ?>
</div>

<!-- Exceptions on this record -->
<?php if (!empty($exceptions)): ?>
<div class="card" style="padding:20px; margin-bottom:20px;">
    <h3 style="margin:0 0 12px 0; font-size:15px;">Exceptions</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Submitted</th><th>Reason</th><th>Note</th><th>Status</th><th>Reviewer</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($exceptions as $ex): ?>
                <?php
                $statusColor = match ($ex['status']) {
                    'pending'  => '#f59e0b',
                    'approved' => '#10b981',
                    'rejected' => '#ef4444',
                    default    => '#6b7280',
                };
                ?>
                <tr>
                    <td class="text-sm text-muted"><?= e($ex['submitted_at']) ?></td>
                    <td class="text-sm"><?= e(DutyException::REASONS[$ex['reason_code']] ?? $ex['reason_code']) ?></td>
                    <td class="text-sm"><?= e((string)($ex['reason_text'] ?? '')) ?></td>
                    <td><span class="status-badge" style="--badge-color: <?= $statusColor ?>"><?= e(ucfirst($ex['status'])) ?></span></td>
                    <td class="text-sm">
                        <?php if (!empty($ex['reviewed_by'])): ?>
                            <?php $rev = Database::fetch("SELECT name FROM users WHERE id = ?", [$ex['reviewed_by']]); ?>
                            <?= e($rev['name'] ?? '—') ?>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td>
                    <?php if ($canReview && $ex['status'] === 'pending'): ?>
                        <form method="POST" action="/duty-reporting/exception/<?= (int)$ex['id'] ?>/approve" style="display:inline;">
                            <?= csrfField() ?>
                            <input type="hidden" name="review_notes" value="">
                            <button class="btn btn-primary btn-sm" type="submit">Approve</button>
                        </form>
                        <form method="POST" action="/duty-reporting/exception/<?= (int)$ex['id'] ?>/reject" style="display:inline; margin-left:6px;">
                            <?= csrfField() ?>
                            <input type="hidden" name="review_notes" value="">
                            <button class="btn btn-outline btn-sm" type="submit">Reject</button>
                        </form>
                    <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Admin correction form -->
<?php if ($canReview): ?>
<div class="card" style="padding:20px;">
    <h3 style="margin:0 0 12px 0; font-size:15px;">Admin Correction</h3>
    <p class="text-xs text-muted" style="margin-bottom:14px;">
        Use only when operational circumstances make the record genuinely incorrect.
        A correction note is required and every change is audited.
    </p>
    <form method="POST" action="/duty-reporting/report/<?= (int)$report['id'] ?>/correct">
        <?= csrfField() ?>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:12px; margin-bottom:12px;">
            <div>
                <label class="text-xs text-muted">Check-in UTC</label>
                <input type="text" name="check_in_at_utc" class="form-control"
                       placeholder="YYYY-MM-DD HH:MM:SS" value="">
            </div>
            <div>
                <label class="text-xs text-muted">Check-out UTC</label>
                <input type="text" name="check_out_at_utc" class="form-control"
                       placeholder="YYYY-MM-DD HH:MM:SS" value="">
            </div>
            <div>
                <label class="text-xs text-muted">State</label>
                <select name="state" class="form-control">
                    <option value="">(no change)</option>
                    <?php foreach (DutyReport::STATES as $s): ?>
                        <option value="<?= $s ?>"><?= e(ucfirst(str_replace('_',' ', $s))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div>
            <label class="text-xs text-muted">Correction note (required)</label>
            <textarea name="correction_note" class="form-control" rows="3" required></textarea>
        </div>
        <button class="btn btn-primary" type="submit" style="margin-top:10px;">Apply Correction</button>
    </form>
</div>
<?php endif; ?>

<div style="margin-top:20px;"><a href="/duty-reporting" class="btn btn-ghost btn-sm">← Back to overview</a></div>
