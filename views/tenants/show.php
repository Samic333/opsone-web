<?php
$pageTitle   = e($tenant['name']) . ' — Airline Detail';
$pageSubtitle = e($tenant['legal_name'] ?? $tenant['name']) . ' · ' . e($tenant['code']);
$headerAction = '<a href="/tenants/edit/' . $tenant['id'] . '" class="btn btn-outline">Edit Airline</a>';
ob_start();
?>

<!-- ─── Status banner ────────────────────────────────────────────────────── -->
<?php if (!$tenant['is_active']): ?>
<div class="alert alert-error" style="margin-bottom:1.5rem;">
    ⚠ This airline is currently <strong>suspended</strong>.
    All user logins for this tenant will be blocked.
</div>
<?php endif; ?>

<!-- ─── Stats row ─────────────────────────────────────────────────────────── -->
<div class="stats-grid" style="margin-bottom:1.5rem;">
    <div class="stat-card">
        <div class="stat-value"><?= $stats['user_count'] ?></div>
        <div class="stat-label">Total Users</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $stats['pending_devices'] ?></div>
        <div class="stat-label">Pending Devices</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $stats['enabled_modules'] ?></div>
        <div class="stat-label">Active Modules</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= statusBadge($tenant['onboarding_status'] ?? 'active') ?></div>
        <div class="stat-label">Status</div>
    </div>
</div>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem; align-items:start;">

<!-- ─── Left: Airline info ──────────────────────────────────────────────── -->
<div>
    <div class="card" style="margin-bottom:1.5rem;">
        <h3 style="margin:0 0 1rem; font-size:.95rem; font-weight:600;">Airline Information</h3>
        <table style="width:100%; font-size:13px; border-collapse:collapse;">
            <?php $rows = [
                'Legal Name'      => $tenant['legal_name'],
                'Display Name'    => $tenant['display_name'],
                'ICAO Code'       => $tenant['icao_code'],
                'IATA Code'       => $tenant['iata_code'],
                'System Code'     => $tenant['code'],
                'Country'         => $tenant['primary_country'],
                'Primary Base'    => $tenant['primary_base'],
                'Contact Email'   => $tenant['contact_email'],
                'Support Tier'    => ucfirst($tenant['support_tier'] ?? 'standard'),
                'Onboarded'       => formatDate($tenant['onboarded_at'] ?? null),
            ]; ?>
            <?php foreach ($rows as $label => $value): ?>
            <?php if ($value): ?>
            <tr>
                <td style="padding:5px 10px 5px 0; color:var(--text-muted); white-space:nowrap; width:130px;"><?= e($label) ?></td>
                <td style="padding:5px 0;"><?= e($value) ?></td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- Expected headcount -->
    <?php
    $hc = array_filter([
        'Total Expected'  => $tenant['expected_headcount'],
        'Pilots'          => $tenant['headcount_pilots'],
        'Cabin Crew'      => $tenant['headcount_cabin'],
        'Engineers'       => $tenant['headcount_engineers'],
        'Schedulers'      => $tenant['headcount_schedulers'],
        'Training Staff'  => $tenant['headcount_training'],
        'Safety Staff'    => $tenant['headcount_safety'],
        'HR Staff'        => $tenant['headcount_hr'],
    ]);
    if (!empty($hc)): ?>
    <div class="card" style="margin-bottom:1.5rem;">
        <h3 style="margin:0 0 1rem; font-size:.95rem; font-weight:600;">Headcount</h3>
        <table style="width:100%; font-size:13px; border-collapse:collapse;">
        <?php foreach ($hc as $label => $value): ?>
        <tr>
            <td style="padding:4px 10px 4px 0; color:var(--text-muted); width:140px;"><?= e($label) ?></td>
            <td style="padding:4px 0; font-weight:600;"><?= (int)$value ?></td>
        </tr>
        <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>

    <!-- Controlled access panel -->
    <div class="card" style="margin-bottom:1.5rem; border-left: 3px solid #f59e0b;">
        <h3 style="margin:0 0 0.5rem; font-size:.95rem; font-weight:600; color:#f59e0b;">
            🔍 Controlled Airline Access
        </h3>
        <p style="font-size:12px; color:var(--text-muted); margin:0 0 1rem;">
            Platform admins must log a reason before accessing airline operational data.
            This access is audited.
        </p>
        <form method="POST" action="/tenants/<?= $tenant['id'] ?>/access">
            <?= csrfField() ?>
            <div class="form-group" style="margin-bottom:.5rem;">
                <input type="text" name="access_reason" class="form-control"
                       placeholder="Reason for access (e.g. support ticket #1234)" required>
            </div>
            <div class="form-row" style="margin-bottom:.5rem;">
                <input type="text" name="ticket_ref" class="form-control"
                       placeholder="Ticket ref (optional)">
                <select name="module_area" class="form-control">
                    <option value="general">General</option>
                    <option value="users">Users</option>
                    <option value="devices">Devices</option>
                    <option value="roster">Roster</option>
                    <option value="notices">Notices</option>
                    <option value="documents">Documents</option>
                    <option value="fdm">FDM</option>
                    <option value="compliance">Compliance</option>
                    <option value="audit">Audit Log</option>
                </select>
            </div>
            <button type="submit" class="btn btn-outline" style="border-color:#f59e0b; color:#f59e0b;">
                Log Access & Proceed
            </button>
        </form>
    </div>

    <!-- Recent access log -->
    <?php if (!empty($accessLog)): ?>
    <div class="card" style="margin-bottom:1.5rem;">
        <h3 style="margin:0 0 1rem; font-size:.95rem; font-weight:600;">Platform Access History</h3>
        <table style="width:100%; font-size:12px; border-collapse:collapse;">
            <thead>
                <tr style="color:var(--text-muted);">
                    <th style="text-align:left; padding:4px 8px 4px 0;">Admin</th>
                    <th style="text-align:left; padding:4px 8px;">Area</th>
                    <th style="text-align:left; padding:4px 0;">When</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($accessLog as $entry): ?>
            <tr>
                <td style="padding:4px 8px 4px 0;"><?= e($entry['platform_user_name'] ?? 'Unknown') ?></td>
                <td style="padding:4px 8px;"><?= e($entry['module_area'] ?? '—') ?></td>
                <td style="padding:4px 0; color:var(--text-muted);"><?= formatDateTime($entry['access_started_at']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- ─── Right: Modules & Invitations ──────────────────────────────────────── -->
<div>

    <!-- Modules -->
    <div class="card" style="margin-bottom:1.5rem;">
        <h3 style="margin:0 0 1rem; font-size:.95rem; font-weight:600;">🧩 Enabled Modules</h3>
        <?php if (empty($modules)): ?>
            <p style="font-size:13px; color:var(--text-muted);">No modules configured yet.</p>
        <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:8px;">
            <?php foreach ($modules as $mod): ?>
            <div style="display:flex; justify-content:space-between; align-items:center;
                        padding:8px 10px; background:var(--surface); border-radius:6px;
                        border:1px solid var(--border);">
                <div>
                    <div style="font-size:13px; font-weight:500;"><?= e($mod['name']) ?></div>
                    <div style="font-size:11px; color:var(--text-muted);"><?= e($mod['code']) ?></div>
                </div>
                <form method="POST" action="/tenants/<?= $tenant['id'] ?>/modules/<?= $mod['id'] ?>/toggle"
                      style="display:inline;">
                    <?= csrfField() ?>
                    <button type="submit"
                            class="btn btn-outline"
                            style="font-size:11px; padding:3px 10px;
                                   <?= $mod['tenant_enabled'] ? 'border-color:#10b981;color:#10b981;' : 'border-color:var(--text-muted);color:var(--text-muted);' ?>">
                        <?= $mod['tenant_enabled'] ? '✓ Enabled' : 'Disabled' ?>
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div style="margin-top:1rem;">
            <a href="/platform/modules/tenant/<?= $tenant['id'] ?>" class="btn btn-outline" style="font-size:12px;">
                Manage All Modules →
            </a>
        </div>
    </div>

    <!-- Invitation tokens -->
    <div class="card" style="margin-bottom:1.5rem;">
        <h3 style="margin:0 0 0.5rem; font-size:.95rem; font-weight:600;">📧 Pending Invitations</h3>
        <?php if (empty($invitations)): ?>
            <p style="font-size:13px; color:var(--text-muted); margin:0 0 1rem;">No pending invitations.</p>
        <?php else: ?>
        <?php foreach ($invitations as $inv): ?>
        <div style="padding:8px 10px; background:var(--surface); border-radius:6px; margin-bottom:8px;
                    border:1px solid var(--border); font-size:12px;">
            <strong><?= e($inv['name']) ?></strong> (<?= e($inv['email']) ?>)<br>
            <span style="color:var(--text-muted);">
                Role: <?= e($inv['role_slug']) ?> · Expires: <?= formatDateTime($inv['expires_at']) ?>
            </span>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <form method="POST" action="/tenants/<?= $tenant['id'] ?>/invite" style="margin-top:1rem;">
            <?= csrfField() ?>
            <div class="form-row" style="margin-bottom:.5rem;">
                <input type="text" name="invite_name" class="form-control" placeholder="Full name" required>
                <input type="email" name="invite_email" class="form-control" placeholder="Email address" required>
            </div>
            <select name="invite_role_slug" class="form-control" style="margin-bottom:.75rem;">
                <option value="airline_admin">Airline Admin</option>
                <option value="hr">HR Admin</option>
                <option value="chief_pilot">Chief Pilot</option>
                <option value="safety_officer">Safety Manager</option>
                <option value="scheduler">Scheduler</option>
            </select>
            <button type="submit" class="btn btn-primary" style="font-size:12px;">
                Create Invitation Token
            </button>
        </form>
    </div>

    <!-- Danger zone -->
    <div class="card" style="border-left: 3px solid var(--accent-red);">
        <h3 style="margin:0 0 0.5rem; font-size:.95rem; font-weight:600; color:var(--accent-red);">
            ⚠ Danger Zone
        </h3>
        <form method="POST" action="/tenants/toggle/<?= $tenant['id'] ?>">
            <?= csrfField() ?>
            <button type="submit" class="btn btn-outline" style="border-color:var(--accent-red); color:var(--accent-red);"
                    onclick="return confirm('Are you sure you want to <?= $tenant['is_active'] ? 'suspend' : 'activate' ?> this airline?')">
                <?= $tenant['is_active'] ? '⏸ Suspend Airline' : '▶ Activate Airline' ?>
            </button>
        </form>
    </div>

</div>
</div>

<!-- ─── Notes ─────────────────────────────────────────────────────────────── -->
<?php if ($tenant['notes']): ?>
<div class="card" style="margin-top:1.5rem;">
    <h3 style="margin:0 0 0.5rem; font-size:.95rem; font-weight:600;">Internal Notes</h3>
    <p style="font-size:13px; margin:0;"><?= nl2br(e($tenant['notes'])) ?></p>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/app.php'; ?>
