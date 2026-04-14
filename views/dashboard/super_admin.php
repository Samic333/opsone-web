<?php
/**
 * Platform Super Admin Dashboard — Phase 1
 * Rich platform control plane overview with airline, onboarding, module, and audit data.
 */
$pageTitle    = 'Platform Overview';
$pageSubtitle = 'Platform Control Plane — Super Admin';
ob_start();

$tierColors = [
    'standard'   => '#6b7280',
    'premium'    => '#6366f1',
    'enterprise' => '#f59e0b',
];
?>

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
    <div class="stat-card <?= $data['pending_onboarding'] > 0 ? 'yellow' : 'green' ?>">
        <div class="stat-label">Onboarding Queue</div>
        <div class="stat-value"><?= $data['pending_onboarding'] ?></div>
        <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">
            pending + in review
            <?php if ($data['awaiting_provision'] > 0): ?>
            · <span style="color:#10b981;"><?= $data['awaiting_provision'] ?> ready to provision</span>
            <?php endif; ?>
        </div>
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

<!-- ─── Secondary Stats Row ───────────────────────────────────────────────── -->
<div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:1rem; margin-bottom:1.5rem;">

    <!-- Module Assignments -->
    <div class="card" style="padding:14px 16px;">
        <div style="font-size:11px; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:.05em; margin-bottom:6px;">
            🧩 Module Catalog
        </div>
        <div style="font-size:1.6rem; font-weight:700; color:#6366f1;">
            <?= $data['modules_in_catalog'] ?>
        </div>
        <div style="font-size:12px; color:var(--text-muted); margin-top:3px;">
            modules available &nbsp;·&nbsp;
            <strong style="color:var(--text);"><?= $data['module_assignments'] ?></strong> active assignments
        </div>
        <a href="/platform/modules" style="font-size:11px; color:#6366f1; text-decoration:none; margin-top:8px; display:inline-block;">
            View Catalog →
        </a>
    </div>

    <!-- Support Tier Distribution -->
    <div class="card" style="padding:14px 16px;">
        <div style="font-size:11px; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:.05em; margin-bottom:8px;">
            🏷 Support Tiers
        </div>
        <?php if (empty($data['tier_distribution'])): ?>
            <div style="font-size:12px; color:var(--text-muted);">No airlines registered yet.</div>
        <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:5px;">
            <?php foreach ($data['tier_distribution'] as $tier => $cnt): ?>
            <?php $color = $tierColors[$tier] ?? '#6b7280'; ?>
            <div style="display:flex; align-items:center; gap:8px;">
                <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:<?= $color ?>;"></span>
                <span style="font-size:12px; color:var(--text); text-transform:capitalize; min-width:70px;"><?= e($tier) ?></span>
                <span style="font-size:13px; font-weight:700; color:<?= $color ?>;"><?= $cnt ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Onboarding Pipeline -->
    <div class="card" style="padding:14px 16px;">
        <div style="font-size:11px; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:.05em; margin-bottom:8px;">
            ✈ Onboarding Pipeline
        </div>
        <div style="display:flex; flex-direction:column; gap:5px;">
            <?php
            $pipelineLabels = [
                'pending'     => ['label' => 'Awaiting Review',   'color' => '#f59e0b'],
                'in_review'   => ['label' => 'In Review',         'color' => '#6366f1'],
                'approved'    => ['label' => 'Approved',          'color' => '#10b981'],
                'provisioned' => ['label' => 'Provisioned',       'color' => '#6b7280'],
            ];
            foreach ($pipelineLabels as $st => $info):
                $cnt = $data['onboarding_counts'][$st] ?? 0;
                if ($cnt === 0) continue;
            ?>
            <div style="display:flex; align-items:center; gap:8px;">
                <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:<?= $info['color'] ?>;"></span>
                <span style="font-size:12px; color:var(--text); min-width:110px;"><?= $info['label'] ?></span>
                <span style="font-size:13px; font-weight:700; color:<?= $info['color'] ?>;"><?= $cnt ?></span>
            </div>
            <?php endforeach; ?>
            <?php if (array_sum($data['onboarding_counts'] ?? []) === 0): ?>
                <div style="font-size:12px; color:var(--text-muted);">No requests yet.</div>
            <?php endif; ?>
        </div>
        <a href="/platform/onboarding" style="font-size:11px; color:#6366f1; text-decoration:none; margin-top:8px; display:inline-block;">
            Manage Onboarding →
        </a>
    </div>
</div>

<!-- ─── Main Content Grid ─────────────────────────────────────────────────── -->
<div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem;">

    <!-- Airlines List -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Airlines</div>
            <a href="/tenants" class="btn btn-sm btn-outline">Manage All →</a>
        </div>
        <?php if (empty($data['tenants'])): ?>
        <div class="empty-state">
            <p>No airlines registered yet.</p>
            <a href="/platform/onboarding/create" class="btn btn-primary" style="margin-top:.5rem;">
                + Start Onboarding
            </a>
        </div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Airline</th>
                        <th>Code</th>
                        <th>Tier</th>
                        <th>Status</th>
                    </tr>
                </thead>
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
        <?php if (count($data['tenants']) > 8): ?>
        <div style="padding:8px 0 0; text-align:right;">
            <a href="/tenants" style="font-size:12px; color:#6366f1;">
                View all <?= count($data['tenants']) ?> airlines →
            </a>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Platform Activity Log -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Recent Platform Activity</div>
            <a href="/audit-log" class="btn btn-sm btn-outline">Full Log →</a>
        </div>
        <?php if (empty($data['recent_activity'])): ?>
        <div class="empty-state"><p>No platform activity recorded yet.</p></div>
        <?php else: ?>
        <ul class="activity-list" style="margin:0; padding:0; list-style:none;">
            <?php foreach ($data['recent_activity'] as $log): ?>
            <li class="activity-item" style="display:flex; gap:10px; padding:8px 0; border-bottom:1px solid var(--border);">
                <div class="activity-dot" style="width:8px; height:8px; border-radius:50%; background:#6366f1; margin-top:4px; flex-shrink:0;"></div>
                <div style="min-width:0;">
                    <div style="font-size:12px;">
                        <strong><?= e($log['user_name'] ?? 'System') ?></strong>
                        <?php if ($log['actor_role']): ?>
                        <span style="color:var(--text-muted); font-size:10px;">
                            (<?= e(str_replace('_', ' ', $log['actor_role'])) ?>)
                        </span>
                        <?php endif; ?>
                        — <span style="font-size:11px; font-family:monospace; color:#6366f1;"><?= e($log['action']) ?></span>
                    </div>
                    <?php if ($log['details']): ?>
                    <div style="font-size:11px; color:var(--text-muted); margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        <?= e(is_array($log['details']) ? json_encode($log['details']) : $log['details']) ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($log['tenant_name']): ?>
                    <div style="font-size:10px; color:var(--text-muted);">
                        Airline: <?= e($log['tenant_name']) ?>
                    </div>
                    <?php endif; ?>
                    <div style="font-size:10px; color:var(--text-muted); margin-top:2px;">
                        <?= formatDateTime($log['created_at']) ?>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>

<!-- ─── Onboarding Pipeline (if any actionable items) ────────────────────── -->
<?php if (!empty($data['onboarding_pipeline'])): ?>
<div class="card" style="margin-top:1.5rem;">
    <div class="card-header">
        <div class="card-title">
            ⏳ Onboarding Action Required
            <span style="margin-left:8px; font-size:11px; background:#f59e0b; color:#fff;
                          padding:2px 8px; border-radius:10px;">
                <?= count($data['onboarding_pipeline']) ?>
            </span>
        </div>
        <a href="/platform/onboarding" class="btn btn-sm btn-outline">View All →</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Airline</th>
                    <th>Contact</th>
                    <th>Tier</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach (array_slice($data['onboarding_pipeline'], 0, 6) as $req): ?>
            <?php
            $stColors = ['pending' => '#f59e0b', 'in_review' => '#6366f1', 'approved' => '#10b981'];
            $stColor  = $stColors[$req['status']] ?? '#6b7280';
            ?>
            <tr>
                <td style="font-weight:500;"><?= e($req['legal_name']) ?></td>
                <td style="font-size:12px; color:var(--text-muted);"><?= e($req['contact_name']) ?></td>
                <td style="font-size:12px; text-transform:capitalize;"><?= e($req['support_tier']) ?></td>
                <td>
                    <span style="font-size:11px; padding:2px 7px; border-radius:4px;
                                  background:<?= $stColor ?>22; color:<?= $stColor ?>; font-weight:600;">
                        <?= ucfirst(str_replace('_', ' ', $req['status'])) ?>
                    </span>
                </td>
                <td style="font-size:11px; color:var(--text-muted);"><?= formatDate($req['created_at']) ?></td>
                <td>
                    <a href="/platform/onboarding/<?= $req['id'] ?>" class="btn btn-xs btn-outline">
                        <?= $req['status'] === 'approved' ? '🚀 Provision' : 'Review' ?>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ─── Quick Links ───────────────────────────────────────────────────────── -->
<div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:1rem; margin-top:1.5rem;">
    <?php
    $quickLinks = [
        ['icon' => '🏢', 'label' => 'Airline Registry',  'url' => '/tenants',           'color' => '#3b82f6'],
        ['icon' => '✈',  'label' => 'Onboarding',        'url' => '/platform/onboarding','color' => '#f59e0b'],
        ['icon' => '🧩', 'label' => 'Module Catalog',    'url' => '/platform/modules',  'color' => '#6366f1'],
        ['icon' => '👤', 'label' => 'Platform Staff',    'url' => '/platform/users',    'color' => '#10b981'],
        ['icon' => '🔒', 'label' => 'Audit Log',         'url' => '/audit-log',         'color' => '#ef4444'],
        ['icon' => '🔑', 'label' => 'Login Activity',    'url' => '/audit-log/logins',  'color' => '#f97316'],
        ['icon' => '📱', 'label' => 'All Devices',       'url' => '/devices',           'color' => '#06b6d4'],
        ['icon' => '📲', 'label' => 'App Builds',        'url' => '/install',           'color' => '#8b5cf6'],
    ];
    ?>
    <?php foreach ($quickLinks as $ql): ?>
    <a href="<?= $ql['url'] ?>" style="text-decoration:none;">
        <div class="card" style="padding:12px 14px; display:flex; align-items:center; gap:10px;
                                  transition:border-color .15s; border:1px solid transparent;"
             onmouseover="this.style.borderColor='<?= $ql['color'] ?>'"
             onmouseout="this.style.borderColor='transparent'">
            <span style="font-size:1.3rem;"><?= $ql['icon'] ?></span>
            <span style="font-size:12px; font-weight:600; color:var(--text);"><?= e($ql['label']) ?></span>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/app.php';
