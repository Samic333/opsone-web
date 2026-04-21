<?php
/**
 * Full crew compliance report
 * Variables: $summary, $expiredLicenses, $expiringLicenses, $expiringMedicals, $expiringPassports
 */
?>
<style>
.comp-section-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--text-muted); margin:20px 0 8px; }
</style>

<!-- Summary stat cards -->
<div class="stats-grid">
    <div class="stat-card <?= $summary['expired_licenses']  > 0 ? 'red'    : 'blue' ?>">
        <div class="stat-label">Expired Licences</div>
        <div class="stat-value"><?= $summary['expired_licenses'] ?></div>
    </div>
    <div class="stat-card <?= $summary['expiring_licenses'] > 0 ? 'yellow' : 'blue' ?>">
        <div class="stat-label">Licences Expiring (90d)</div>
        <div class="stat-value"><?= $summary['expiring_licenses'] ?></div>
    </div>
    <div class="stat-card <?= $summary['expiring_medicals'] > 0 ? 'yellow' : 'blue' ?>">
        <div class="stat-label">Medicals Expiring (90d)</div>
        <div class="stat-value"><?= $summary['expiring_medicals'] ?></div>
    </div>
    <div class="stat-card <?= $summary['expiring_passports'] > 0 ? 'yellow' : 'blue' ?>">
        <div class="stat-label">Passports Expiring (180d)</div>
        <div class="stat-value"><?= $summary['expiring_passports'] ?></div>
    </div>
    <div class="stat-card <?= ($pendingChangeRequests ?? 0) > 0 ? 'yellow' : 'blue' ?>">
        <div class="stat-label">Pending Change Requests</div>
        <div class="stat-value"><?= (int) ($pendingChangeRequests ?? 0) ?></div>
    </div>
    <div class="stat-card <?= ($pendingDocuments ?? 0) > 0 ? 'yellow' : 'blue' ?>">
        <div class="stat-label">Pending Documents</div>
        <div class="stat-value"><?= (int) ($pendingDocuments ?? 0) ?></div>
    </div>
</div>

<!-- Phase 6: Eligibility / readiness bar -->
<?php if (!empty($eligibilitySummary)): ?>
<div class="card">
    <div class="card-header">
        <div class="card-title">Assignment Readiness</div>
        <a href="/personnel/eligibility" class="btn btn-outline btn-sm">Open Eligibility →</a>
    </div>
    <div class="stats-grid">
        <div class="stat-card green">
            <div class="stat-label">Eligible</div>
            <div class="stat-value"><?= (int) $eligibilitySummary['eligible'] ?></div>
        </div>
        <div class="stat-card <?= $eligibilitySummary['warning'] > 0 ? 'yellow' : 'blue' ?>">
            <div class="stat-label">Warning</div>
            <div class="stat-value"><?= (int) $eligibilitySummary['warning'] ?></div>
        </div>
        <div class="stat-card <?= $eligibilitySummary['blocked'] > 0 ? 'red' : 'blue' ?>">
            <div class="stat-label">Blocked</div>
            <div class="stat-value"><?= (int) $eligibilitySummary['blocked'] ?></div>
        </div>
    </div>
    <form method="POST" action="/compliance/alert-scan" style="margin-top:10px;">
        <?= csrfField() ?>
        <button class="btn btn-outline btn-sm">Run Expiry Alert Scan</button>
        <span class="text-xs text-muted" style="margin-left:10px;">
            Records alerts for items expiring or already expired — dispatch is sent to crew, HR, and line manager.
        </span>
    </form>
</div>
<?php endif; ?>

<!-- Phase 6: Open expiry alerts ledger -->
<?php if (!empty($openAlerts)): ?>
<div class="card" style="border-left:3px solid var(--accent-amber,#f59e0b);">
    <div class="card-header">
        <div class="card-title">Open Expiry Alerts (<?= count($openAlerts) ?>)</div>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Staff</th><th>Entity</th><th>Level</th><th>Expiry</th><th>Sent</th></tr></thead>
            <tbody>
            <?php foreach (array_slice($openAlerts, 0, 25) as $a):
                $c = ['expired' => '#ef4444', 'critical' => '#f59e0b', 'warning' => '#f59e0b'][$a['alert_level']] ?? '#6b7280';
            ?>
            <tr>
                <td><strong><?= e($a['user_name']) ?></strong>
                    <?php if (!empty($a['employee_id'])): ?><span class="text-xs text-muted"> (<?= e($a['employee_id']) ?>)</span><?php endif; ?>
                </td>
                <td><code><?= e($a['entity_type']) ?></code></td>
                <td><span class="status-badge" style="--badge-color:<?= $c ?>"><?= strtoupper($a['alert_level']) ?></span></td>
                <td><?= e($a['expiry_date']) ?></td>
                <td style="font-size:11px;color:var(--text-muted);">
                    <?= $a['sent_to_user']    ? '👤 user ' : '' ?>
                    <?= $a['sent_to_hr']      ? '🏢 HR '   : '' ?>
                    <?= $a['sent_to_manager'] ? '👔 manager' : '' ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ─── Expired Licences ─── -->
<?php if (!empty($expiredLicenses)): ?>
<div class="card" style="border-left:3px solid var(--accent-red);">
    <div class="card-header">
        <div class="card-title" style="color:var(--accent-red);">⛔ Expired Licences</div>
        <a href="/users" class="btn btn-sm btn-outline">Manage Staff →</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Crew Member</th><th>Licence Type</th><th>Number</th><th>Expired</th><th>Days Overdue</th></tr></thead>
            <tbody>
            <?php foreach ($expiredLicenses as $l):
                $overdue = (int) ceil((time() - strtotime($l['expiry_date'])) / 86400);
            ?>
            <tr>
                <td>
                    <strong><?= e($l['user_name']) ?></strong>
                    <?php if ($l['employee_id']): ?><span class="text-xs text-muted">(<?= e($l['employee_id']) ?>)</span><?php endif; ?>
                </td>
                <td><?= e($l['license_type']) ?></td>
                <td><code><?= e($l['license_number'] ?? '—') ?></code></td>
                <td style="color:var(--accent-red);font-weight:600;"><?= e($l['expiry_date']) ?></td>
                <td style="color:var(--accent-red);font-weight:700;"><?= $overdue ?>d overdue</td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ─── Expiring Licences ─── -->
<?php if (!empty($expiringLicenses)): ?>
<div class="card" style="border-left:3px solid var(--accent-amber,#f59e0b);">
    <div class="card-header">
        <div class="card-title">⚠ Licences Expiring Within 90 Days</div>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Crew Member</th><th>Licence Type</th><th>Number</th><th>Authority</th><th>Expires</th><th>Days Left</th></tr></thead>
            <tbody>
            <?php foreach ($expiringLicenses as $l):
                $daysLeft = (int) ceil((strtotime($l['expiry_date']) - time()) / 86400);
                $col = $daysLeft <= 30 ? 'var(--accent-red)' : 'var(--accent-amber,#f59e0b)';
            ?>
            <tr>
                <td>
                    <strong><?= e($l['user_name']) ?></strong>
                    <?php if ($l['employee_id']): ?><span class="text-xs text-muted">(<?= e($l['employee_id']) ?>)</span><?php endif; ?>
                </td>
                <td><?= e($l['license_type']) ?></td>
                <td><code><?= e($l['license_number'] ?? '—') ?></code></td>
                <td style="font-size:12px;color:var(--text-muted);"><?= e($l['issuing_authority'] ?? '—') ?></td>
                <td style="font-weight:600;color:<?= $col ?>;"><?= e($l['expiry_date']) ?></td>
                <td style="font-weight:700;color:<?= $col ?>;"><?= $daysLeft ?>d</td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ─── Expiring Medicals ─── -->
<?php if (!empty($expiringMedicals)): ?>
<div class="card" style="border-left:3px solid var(--accent-amber,#f59e0b);">
    <div class="card-header">
        <div class="card-title">⚠ Medicals Expiring Within 90 Days</div>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Crew Member</th><th>Medical Class</th><th>Expires</th><th>Days Left</th></tr></thead>
            <tbody>
            <?php foreach ($expiringMedicals as $m):
                $daysLeft = (int) ceil((strtotime($m['medical_expiry']) - time()) / 86400);
                $col = $daysLeft <= 30 ? 'var(--accent-red)' : 'var(--accent-amber,#f59e0b)';
            ?>
            <tr>
                <td>
                    <strong><?= e($m['user_name']) ?></strong>
                    <?php if ($m['employee_id']): ?><span class="text-xs text-muted">(<?= e($m['employee_id']) ?>)</span><?php endif; ?>
                </td>
                <td><?= e($m['medical_class'] ?? '—') ?></td>
                <td style="font-weight:600;color:<?= $col ?>;"><?= e($m['medical_expiry']) ?></td>
                <td style="font-weight:700;color:<?= $col ?>;"><?= $daysLeft ?>d</td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ─── Expiring Passports ─── -->
<?php if (!empty($expiringPassports)): ?>
<div class="card" style="border-left:3px solid var(--accent-amber,#f59e0b);">
    <div class="card-header">
        <div class="card-title">⚠ Passports Expiring Within 180 Days</div>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Crew Member</th><th>Passport Country</th><th>Passport No.</th><th>Expires</th><th>Days Left</th></tr></thead>
            <tbody>
            <?php foreach ($expiringPassports as $p):
                $daysLeft = (int) ceil((strtotime($p['passport_expiry']) - time()) / 86400);
                $col = $daysLeft <= 60 ? 'var(--accent-red)' : 'var(--accent-amber,#f59e0b)';
            ?>
            <tr>
                <td>
                    <strong><?= e($p['user_name']) ?></strong>
                    <?php if ($p['employee_id']): ?><span class="text-xs text-muted">(<?= e($p['employee_id']) ?>)</span><?php endif; ?>
                </td>
                <td><?= e($p['passport_country'] ?? '—') ?></td>
                <td><code><?= e($p['passport_number'] ?? '—') ?></code></td>
                <td style="font-weight:600;color:<?= $col ?>;"><?= e($p['passport_expiry']) ?></td>
                <td style="font-weight:700;color:<?= $col ?>;"><?= $daysLeft ?>d</td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ─── Pending Notice Acknowledgements ─── -->
<?php if (!empty($pendingNoticeAcks)): ?>
<div class="card" style="border-left:3px solid #f59e0b;">
    <div class="card-header">
        <div class="card-title" style="color:#d97706;">✍️ Notices Awaiting Crew Acknowledgement</div>
        <a href="/notices" class="btn btn-sm btn-outline">Manage Notices →</a>
    </div>
    <p style="font-size:13px;color:var(--text-muted);margin:0 0 12px;">
        These notices require acknowledgement from mobile crew but have outstanding sign-offs.
        Click the notice title to see per-crew status.
    </p>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Notice</th>
                    <th>Priority</th>
                    <th>Category</th>
                    <th>Required</th>
                    <th>Acknowledged</th>
                    <th>Outstanding</th>
                    <th>Progress</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pendingNoticeAcks as $na):
                $pct = $na['total_required'] > 0
                    ? round(($na['total_acked'] / $na['total_required']) * 100)
                    : 0;
                $barColor = $pct >= 75 ? '#10b981' : ($pct >= 40 ? '#f59e0b' : '#ef4444');
                $pc = ['normal'=>'#6b7280','urgent'=>'#f59e0b','critical'=>'#ef4444'][$na['priority']] ?? '#6b7280';
            ?>
            <tr>
                <td>
                    <a href="/notices/ack-report/<?= (int)$na['id'] ?>" style="font-weight:600;color:var(--text-primary);text-decoration:none;">
                        <?= e($na['title']) ?>
                    </a>
                    <br><span class="text-xs text-muted"><?= formatDate($na['published_at'] ?? $na['created_at']) ?></span>
                </td>
                <td><span class="status-badge" style="--badge-color:<?= $pc ?>"><?= ucfirst(e($na['priority'])) ?></span></td>
                <td style="font-size:12px;color:var(--text-muted);"><?= ucfirst(e($na['category'] ?? '—')) ?></td>
                <td style="text-align:center;"><?= (int)$na['total_required'] ?></td>
                <td style="text-align:center;color:#10b981;font-weight:600;"><?= (int)$na['total_acked'] ?></td>
                <td style="text-align:center;color:#ef4444;font-weight:700;"><?= (int)$na['pending_count'] ?></td>
                <td style="min-width:100px;">
                    <div style="background:var(--bg-secondary);border-radius:4px;height:8px;overflow:hidden;">
                        <div style="width:<?= $pct ?>%;height:100%;background:<?= $barColor ?>;border-radius:4px;transition:width .3s;"></div>
                    </div>
                    <div style="font-size:10px;color:var(--text-muted);margin-top:2px;text-align:right;"><?= $pct ?>%</div>
                </td>
                <td>
                    <a href="/notices/ack-report/<?= (int)$na['id'] ?>" class="btn btn-outline btn-xs">View Report</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ─── Pending Document Acknowledgements ─── -->
<?php if (!empty($pendingAcks)): ?>
<div class="card" style="border-left:3px solid #6366f1;">
    <div class="card-header">
        <div class="card-title" style="color:#6366f1;">📋 Documents Awaiting Acknowledgement</div>
        <a href="/files" class="btn btn-sm btn-outline">Manage Documents →</a>
    </div>
    <p style="font-size:13px;color:var(--text-muted);margin:0 0 12px;">
        These documents require acknowledgement from mobile crew but have outstanding sign-offs.
    </p>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Document</th>
                    <th>Category</th>
                    <th>Version</th>
                    <th>Required</th>
                    <th>Acknowledged</th>
                    <th>Outstanding</th>
                    <th>Progress</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pendingAcks as $ack):
                $pct = $ack['total_required'] > 0
                    ? round(($ack['total_acked'] / $ack['total_required']) * 100)
                    : 0;
                $barColor = $pct >= 75 ? '#10b981' : ($pct >= 40 ? '#f59e0b' : '#ef4444');
            ?>
            <tr>
                <td><strong><?= e($ack['title']) ?></strong></td>
                <td style="font-size:12px;color:var(--text-muted);"><?= e($ack['category_name'] ?? '—') ?></td>
                <td><code><?= e($ack['version'] ?? '—') ?></code></td>
                <td style="text-align:center;"><?= (int)$ack['total_required'] ?></td>
                <td style="text-align:center;color:#10b981;font-weight:600;"><?= (int)$ack['total_acked'] ?></td>
                <td style="text-align:center;color:#ef4444;font-weight:700;"><?= (int)$ack['pending_count'] ?></td>
                <td style="min-width:100px;">
                    <div style="background:var(--bg-secondary);border-radius:4px;height:8px;overflow:hidden;">
                        <div style="width:<?= $pct ?>%;height:100%;background:<?= $barColor ?>;border-radius:4px;transition:width .3s;"></div>
                    </div>
                    <div style="font-size:10px;color:var(--text-muted);margin-top:2px;text-align:right;"><?= $pct ?>%</div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- All clear state -->
<?php if (empty($expiredLicenses) && empty($expiringLicenses) && empty($expiringMedicals) && empty($expiringPassports) && empty($pendingAcks) && empty($pendingNoticeAcks)): ?>
<div class="card">
    <div class="empty-state">
        <div class="icon">✅</div>
        <h3>All Crew Compliant</h3>
        <p>No licences, medicals, passports, or outstanding document acknowledgements.</p>
    </div>
</div>
<?php endif; ?>
