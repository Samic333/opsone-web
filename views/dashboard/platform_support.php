<?php
/**
 * Platform Support / Security Dashboard — Phase 1
 * Same enriched data as super_admin but with a read-only context notice.
 * Shown to: platform_support, platform_security, system_monitoring roles.
 */
$roleSlug   = $_SESSION['user_roles'][0] ?? 'platform_support';
$roleLabels = [
    'platform_support'  => 'Platform Support Admin',
    'platform_security' => 'Platform Security Admin',
    'system_monitoring' => 'System Monitoring',
];
$pageTitle    = 'Platform Overview';
$pageSubtitle = $roleLabels[$roleSlug] ?? 'Platform View';
ob_start();

$tierColors = [
    'standard'   => '#6b7280',
    'premium'    => '#6366f1',
    'enterprise' => '#f59e0b',
];
?>

<!-- Read-only context notice -->
<div style="margin-bottom:1.5rem; padding:10px 14px;
            background:rgba(99,102,241,0.07); border:1px solid #6366f1;
            border-radius:8px; font-size:12px; color:var(--text);">
    👁 <strong>Read-Only Platform View</strong> — You can monitor platform health and airline status.
    Provisioning, module changes, and staff management require Super Admin access.
</div>

<!-- ─── Primary KPI Stats Row ──────────────────────────────────────────────── -->
<div class="stats-grid" style="margin-bottom:1.5rem;">
    <div class="stat-card blue">
        <div class="stat-label">Total Airlines</div>
        <div class="stat-value"><?= $data['total_airlines'] ?></div>
        <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">
            <?= $data['active_airlines'] ?> active
            <?php if ($data['suspended_airlines'] > 0): ?>
            · <span style="color:#ef4444;"><?= $data['suspended_airlines'] ?> suspended</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="stat-card <?= $data['pending_onboarding'] > 0 ? 'yellow' : '' ?>">
        <div class="stat-label">Onboarding Queue</div>
        <div class="stat-value"><?= $data['pending_onboarding'] ?></div>
        <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">pending + in review</div>
    </div>
    <div class="stat-card cyan">
        <div class="stat-label">Airline Users</div>
        <div class="stat-value"><?= $data['airline_users'] ?></div>
        <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">
            + <?= $data['platform_staff'] ?> platform staff
        </div>
    </div>
    <div class="stat-card <?= $data['pending_devices'] > 0 ? 'yellow' : '' ?>">
        <div class="stat-label">Pending Devices</div>
        <div class="stat-value"><?= $data['pending_devices'] ?></div>
        <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">across all airlines</div>
    </div>
</div>

<!-- ─── Module + Tier Summary ─────────────────────────────────────────────── -->
<div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
    <div class="card" style="padding:14px 16px;">
        <div style="font-size:11px; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:.05em; margin-bottom:8px;">
            🧩 Module Catalog
        </div>
        <div style="font-size:1.4rem; font-weight:700; color:#6366f1;"><?= $data['modules_in_catalog'] ?></div>
        <div style="font-size:12px; color:var(--text-muted); margin-top:3px;">
            modules available · <strong style="color:var(--text);"><?= $data['module_assignments'] ?></strong> active assignments
        </div>
        <a href="/platform/modules" style="font-size:11px; color:#6366f1; text-decoration:none; margin-top:6px; display:inline-block;">
            View Catalog →
        </a>
    </div>
    <div class="card" style="padding:14px 16px;">
        <div style="font-size:11px; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:.05em; margin-bottom:8px;">
            🏷 Support Tiers
        </div>
        <?php if (empty($data['tier_distribution'])): ?>
        <div style="font-size:12px; color:var(--text-muted);">No airlines registered.</div>
        <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:5px;">
            <?php foreach ($data['tier_distribution'] as $tier => $cnt): ?>
            <?php $color = $tierColors[$tier] ?? '#6b7280'; ?>
            <div style="display:flex; align-items:center; gap:8px;">
                <span style="width:8px; height:8px; border-radius:50%; background:<?= $color ?>; display:inline-block;"></span>
                <span style="font-size:12px; text-transform:capitalize; min-width:70px;"><?= e($tier) ?></span>
                <span style="font-size:13px; font-weight:700; color:<?= $color ?>;"><?= $cnt ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ─── Airlines + Activity ───────────────────────────────────────────────── -->
<div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem;">
    <div class="card">
        <div class="card-header">
            <div class="card-title">Airlines</div>
            <a href="/tenants" class="btn btn-sm btn-outline">View All →</a>
        </div>
        <?php if (empty($data['tenants'])): ?>
        <div class="empty-state"><p>No airlines registered yet.</p></div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Airline</th><th>Code</th><th>Tier</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach (array_slice($data['tenants'], 0, 8) as $t): ?>
                <tr>
                    <td>
                        <a href="/tenants/<?= $t['id'] ?>" style="font-weight:500; color:var(--text); text-decoration:none;">
                            <?= e($t['name']) ?>
                        </a>
                    </td>
                    <td><code style="font-size:11px;"><?= e($t['code']) ?></code></td>
                    <td>
                        <?php $tc = $tierColors[$t['support_tier']] ?? '#6b7280'; ?>
                        <span style="font-size:11px; color:<?= $tc ?>; text-transform:capitalize;">
                            <?= e($t['support_tier'] ?? 'standard') ?>
                        </span>
                    </td>
                    <td><?= statusBadge($t['is_active'] ? 'active' : 'suspended') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">Recent Platform Activity</div>
            <a href="/audit-log" class="btn btn-sm btn-outline">Full Log →</a>
        </div>
        <?php if (empty($data['recent_activity'])): ?>
        <div class="empty-state"><p>No platform activity yet.</p></div>
        <?php else: ?>
        <ul style="margin:0; padding:0; list-style:none;">
            <?php foreach (array_slice($data['recent_activity'], 0, 10) as $log): ?>
            <li style="display:flex; gap:8px; padding:7px 0; border-bottom:1px solid var(--border);">
                <div style="width:7px; height:7px; border-radius:50%; background:#6366f1; margin-top:4px; flex-shrink:0;"></div>
                <div style="min-width:0;">
                    <div style="font-size:12px;">
                        <strong><?= e($log['user_name'] ?? 'System') ?></strong>
                        — <span style="font-size:11px; font-family:monospace; color:#6366f1;"><?= e($log['action']) ?></span>
                    </div>
                    <?php if ($log['tenant_name']): ?>
                    <div style="font-size:10px; color:var(--text-muted);">Airline: <?= e($log['tenant_name']) ?></div>
                    <?php endif; ?>
                    <div style="font-size:10px; color:var(--text-muted);"><?= formatDateTime($log['created_at']) ?></div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>

<!-- ─── Quick Access ──────────────────────────────────────────────────────── -->
<div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:1rem; margin-top:1.5rem;">
    <?php
    $links = [
        ['🏢', 'Airline Registry',  '/tenants',          '#3b82f6'],
        ['📱', 'All Devices',       '/devices',          '#06b6d4'],
        ['🔒', 'Audit Log',         '/audit-log',        '#ef4444'],
        ['🔑', 'Login Activity',    '/audit-log/logins', '#f97316'],
        ['📲', 'App Builds',        '/install',          '#8b5cf6'],
        ['🧩', 'Module Catalog',    '/platform/modules', '#6366f1'],
    ];
    foreach ($links as [$icon, $label, $url, $color]):
    ?>
    <a href="<?= $url ?>" style="text-decoration:none;">
        <div class="card" style="padding:11px 14px; display:flex; align-items:center; gap:8px;">
            <span style="font-size:1.2rem;"><?= $icon ?></span>
            <span style="font-size:12px; font-weight:600; color:var(--text);"><?= e($label) ?></span>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/app.php';
