<?php /** Phase 10 — Pilot's FDM events */ ?>
<?php if (empty($events)): ?>
    <div class="card"><div class="empty-state" style="padding:40px 0;"><div class="icon">📈</div><h3>No FDM events</h3><p>You don't have any FDM events currently logged against your flights.</p></div></div>
<?php else: ?>
<div class="card">
    <table class="table">
        <thead><tr><th>Date</th><th>Flight</th><th>Aircraft</th><th>Type</th><th>Severity</th><th>Phase</th><th>Parameter</th><th>Recorded/Threshold</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($events as $e):
            $acked = !empty($e['pilot_ack_at']);
            $sevColor = match (strtolower($e['severity'] ?? '')) {
                'high'   => '#ef4444', 'critical' => '#991b1b',
                'medium' => '#f59e0b', default    => '#64748b',
            };
        ?>
            <tr<?= $acked ? '' : ' style="background:rgba(245,158,11,0.06);"' ?>>
                <td class="text-sm"><?= e($e['flight_date'] ?? '—') ?></td>
                <td class="text-sm"><?= e($e['flight_number'] ?? '—') ?></td>
                <td class="text-sm"><?= e($e['aircraft_reg'] ?? '—') ?></td>
                <td class="text-sm"><?= e($e['event_type']) ?></td>
                <td><span style="color:<?= $sevColor ?>;font-weight:600;"><?= e($e['severity']) ?></span></td>
                <td class="text-sm"><?= e($e['flight_phase'] ?? '—') ?></td>
                <td class="text-sm"><?= e($e['parameter'] ?? '—') ?></td>
                <td class="text-sm"><?= $e['value_recorded'] !== null ? e($e['value_recorded']) : '—' ?>
                    <?= $e['threshold'] !== null ? ' / ' . e($e['threshold']) : '' ?></td>
                <td>
                    <?php if ($acked): ?>
                        <span style="color:#10b981;">✓ Acked <?= formatDate($e['pilot_ack_at']) ?></span>
                    <?php else: ?>
                        <span style="color:#f59e0b;">Pending ack</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!$acked): ?>
                        <form method="POST" action="/my-fdm/ack/<?= (int)$e['id'] ?>" style="margin:0;">
                            <?= csrfField() ?>
                            <button class="btn btn-xs btn-primary" type="submit">Acknowledge</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
