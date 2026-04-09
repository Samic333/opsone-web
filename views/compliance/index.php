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
</div>

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

<!-- All clear state -->
<?php if (empty($expiredLicenses) && empty($expiringLicenses) && empty($expiringMedicals) && empty($expiringPassports)): ?>
<div class="card">
    <div class="empty-state">
        <div class="icon">✅</div>
        <h3>All Crew Compliant</h3>
        <p>No licences, medicals, or passports expiring within the next 180 days.</p>
    </div>
</div>
<?php endif; ?>
