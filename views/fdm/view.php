<?php
/**
 * FDM record detail view
 * Variables: $upload, $events, $eventTypes, $severities
 */
?>
<style>
.sev-badge { display:inline-block; padding:2px 9px; border-radius:4px; font-size:11px; font-weight:700; color:#fff; }
</style>

<!-- Upload metadata -->
<div class="card">
    <div class="card-header">
        <div class="card-title"><?= e($upload['original_name']) ?></div>
        <?php if (hasAnyRole(['fdm_analyst', 'airline_admin', 'super_admin'])): ?>
        <form method="POST" action="/fdm/delete/<?= $upload['id'] ?>" style="display:inline;" onsubmit="return confirm('Delete this record and all events?');">
            <?= csrfField() ?>
            <button type="submit" class="btn btn-sm btn-outline" style="color:var(--accent-red);border-color:var(--accent-red);">Delete Record</button>
        </form>
        <?php endif; ?>
    </div>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;padding:0 0 8px;">
        <div><div style="font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:.06em;">Flight Date</div>
            <div style="font-size:15px;font-weight:600;margin-top:4px;"><?= $upload['flight_date'] ? e($upload['flight_date']) : '—' ?></div></div>
        <div><div style="font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:.06em;">Aircraft</div>
            <div style="font-size:15px;font-weight:600;margin-top:4px;"><?= $upload['aircraft_reg'] ? e($upload['aircraft_reg']) : '—' ?></div></div>
        <div><div style="font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:.06em;">Flight No.</div>
            <div style="font-size:15px;font-weight:600;margin-top:4px;"><?= $upload['flight_number'] ? e($upload['flight_number']) : '—' ?></div></div>
        <div><div style="font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:.06em;">Events</div>
            <div style="font-size:15px;font-weight:600;margin-top:4px;<?= $upload['event_count'] > 0 ? 'color:var(--accent-red)' : '' ?>"><?= $upload['event_count'] ?></div></div>
    </div>
    <?php if ($upload['notes']): ?>
    <div style="font-size:13px;color:var(--text-muted);padding:4px 0 4px;border-top:1px solid var(--border);margin-top:4px;"><?= e($upload['notes']) ?></div>
    <?php endif; ?>
    <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">Uploaded by <?= e($upload['uploader_name']) ?> · <?= formatDateTime($upload['created_at']) ?></div>
</div>

<!-- Events table -->
<div class="card">
    <div class="card-header">
        <div class="card-title">Events (<?= count($events) ?>)</div>
        <a href="/fdm" class="btn btn-sm btn-outline">← All Records</a>
    </div>
    <?php if (empty($events)): ?>
        <div class="empty-state"><p>No events recorded for this upload yet.</p></div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Severity</th><th>Type</th><th>Date</th><th>Aircraft</th><th>Flight</th><th>Phase</th><th>Parameter</th><th>Value / Threshold</th><th>Notes</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($events as $ev):
                $et  = $eventTypes[$ev['event_type']] ?? ['label' => ucfirst($ev['event_type']), 'icon' => '📋'];
                $sev = $severities[$ev['severity']]   ?? ['label' => ucfirst($ev['severity']),  'color' => '#6b7280'];
            ?>
            <tr>
                <td><span class="sev-badge" style="background:<?= $sev['color'] ?>;"><?= e($sev['label']) ?></span></td>
                <td><?= $et['icon'] ?> <?= e($et['label']) ?></td>
                <td style="font-size:12px;"><?= $ev['flight_date'] ? e($ev['flight_date']) : '—' ?></td>
                <td><?= $ev['aircraft_reg'] ? '<code>' . e($ev['aircraft_reg']) . '</code>' : '—' ?></td>
                <td><?= $ev['flight_number'] ? e($ev['flight_number']) : '—' ?></td>
                <td style="font-size:12px;"><?= $ev['flight_phase'] ? e($ev['flight_phase']) : '—' ?></td>
                <td style="font-size:12px;"><?= $ev['parameter'] ? e($ev['parameter']) : '—' ?></td>
                <td style="font-size:12px;">
                    <?php if ($ev['value_recorded'] !== null): ?>
                        <strong><?= $ev['value_recorded'] ?></strong>
                        <?php if ($ev['threshold'] !== null): ?>
                            <span style="color:var(--text-muted);"> / <?= $ev['threshold'] ?></span>
                        <?php endif; ?>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td style="font-size:12px;color:var(--text-muted);max-width:160px;"><?= $ev['notes'] ? e($ev['notes']) : '—' ?></td>
                <td>
                    <?php if (hasAnyRole(['fdm_analyst', 'airline_admin', 'super_admin'])): ?>
                    <form method="POST" action="/fdm/<?= $upload['id'] ?>/events/delete/<?= $ev['id'] ?>" style="display:inline;" onsubmit="return confirm('Remove event?');">
                        <?= csrfField() ?>
                        <button type="submit" style="background:none;border:none;color:var(--accent-red);font-size:11px;cursor:pointer;">✕</button>
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

<!-- Add event form -->
<?php if (hasAnyRole(['fdm_analyst', 'safety_officer', 'airline_admin', 'super_admin'])): ?>
<div class="card">
    <div class="card-header"><div class="card-title">Add Event to This Record</div></div>
    <form method="POST" action="/fdm/<?= $upload['id'] ?>/events/add">
        <?= csrfField() ?>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
            <div class="form-group">
                <label class="form-label">Event Type</label>
                <select name="event_type" class="form-control">
                    <?php foreach ($eventTypes as $key => $et): ?>
                    <option value="<?= $key ?>"><?= $et['icon'] ?> <?= $et['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Severity</label>
                <select name="severity" class="form-control">
                    <?php foreach ($severities as $key => $sv): ?>
                    <option value="<?= $key ?>"><?= ucfirst($key) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Flight Phase</label>
                <input type="text" name="flight_phase" class="form-control" placeholder="e.g. landing">
            </div>
            <div class="form-group">
                <label class="form-label">Parameter</label>
                <input type="text" name="parameter" class="form-control" placeholder="e.g. vertical_g">
            </div>
            <div class="form-group">
                <label class="form-label">Value Recorded</label>
                <input type="number" step="0.001" name="value_recorded" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Threshold</label>
                <input type="number" step="0.001" name="threshold" class="form-control">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Notes</label>
            <input type="text" name="notes" class="form-control" maxlength="255">
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Add Event</button>
    </form>
</div>
<?php endif; ?>
