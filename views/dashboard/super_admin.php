<?php
/**
 * Platform Super Admin Dashboard.
 *
 * Layout (Phase K redesign):
 *   1. KPI hero strip — 4 stat cards (airlines / onboarding / users / devices)
 *   2. Quick actions row — primary platform admin tasks as 1-click chips
 *   3. Main grid (2 cols) — Airlines list (left) + Onboarding Queue (right)
 *   4. Recent platform activity — scannable single-column feed
 *   5. Health tile — module catalog / tier mix / pipeline counts in one row
 *
 * Data shape comes from DashboardController::buildPlatformDashboardData().
 * No data keys are added, removed, or renamed — pure visual redesign.
 */
$pageTitle    = 'Platform Overview';
$pageSubtitle = 'Platform Control Plane — Super Admin';
ob_start();

// Cockpit-light tier colour map. Keep aligned with views/tenants/index.php.
$tierColors = [
    'standard'   => 'var(--text-tertiary)',
    'premium'    => 'var(--accent-purple)',
    'enterprise' => 'var(--accent-yellow)',
];

// Onboarding pipeline labels + colours for the header chip + health tile.
$pipelineLabels = [
    'pending'     => ['label' => 'Awaiting Review',   'color' => 'var(--status-advisory)'],
    'in_review'   => ['label' => 'In Review',         'color' => 'var(--accent-purple)'],
    'approved'    => ['label' => 'Approved',          'color' => 'var(--status-cleared)'],
    'provisioned' => ['label' => 'Provisioned',       'color' => 'var(--text-tertiary)'],
];

// Onboarding action status colour for the queue card.
$obStatusColors = [
    'pending'   => 'var(--status-advisory)',
    'in_review' => 'var(--accent-purple)',
    'approved'  => 'var(--status-cleared)',
];
?>

<!-- ─── 1. KPI Hero Strip ────────────────────────────────────────────── -->
<div class="stats-grid" style="margin-bottom:1.25rem;">
    <a href="/tenants" class="stat-card blue" style="text-decoration:none; color:inherit; cursor:pointer;">
        <div class="stat-label">Total Airlines</div>
        <div class="stat-value"><?= (int) $data['total_airlines'] ?></div>
        <div style="font-size:11px; color:var(--text-tertiary); margin-top:6px;">
            <span style="color:var(--status-cleared); font-weight:600;"><?= (int) $data['active_airlines'] ?></span> active
            <?php if (($data['suspended_airlines'] ?? 0) > 0): ?>
                · <span style="color:var(--status-critical); font-weight:600;"><?= (int) $data['suspended_airlines'] ?></span> suspended
            <?php endif; ?>
        </div>
    </a>

    <a href="/platform/onboarding" class="stat-card <?= ($data['pending_onboarding'] ?? 0) > 0 ? 'yellow' : 'green' ?>"
       style="text-decoration:none; color:inherit; cursor:pointer;">
        <div class="stat-label">Onboarding Queue</div>
        <div class="stat-value"><?= (int) $data['pending_onboarding'] ?></div>
        <div style="font-size:11px; color:var(--text-tertiary); margin-top:6px;">
            pending review
            <?php if (($data['awaiting_provision'] ?? 0) > 0): ?>
                · <span style="color:var(--status-cleared); font-weight:600;"><?= (int) $data['awaiting_provision'] ?> ready to provision</span>
            <?php endif; ?>
        </div>
    </a>

    <a href="/platform/users" class="stat-card cyan" style="text-decoration:none; color:inherit; cursor:pointer;">
        <div class="stat-label">Airline Users</div>
        <div class="stat-value"><?= (int) $data['airline_users'] ?></div>
        <div style="font-size:11px; color:var(--text-tertiary); margin-top:6px;">
            + <span style="color:var(--text-primary); font-weight:600;"><?= (int) $data['platform_staff'] ?></span> platform staff
        </div>
    </a>

    <a href="/devices" class="stat-card <?= ($data['pending_devices'] ?? 0) > 0 ? 'yellow' : '' ?>"
       style="text-decoration:none; color:inherit; cursor:pointer;">
        <div class="stat-label">Pending Devices</div>
        <div class="stat-value"><?= (int) $data['pending_devices'] ?></div>
        <div style="font-size:11px; color:var(--text-tertiary); margin-top:6px;">across all airlines</div>
    </a>
</div>

<!-- ─── 2. Quick Actions ─────────────────────────────────────────────── -->
<?php
$quickActions = [
    [
        'href'   => '/platform/onboarding/create',
        'icon'   => 'rocket-launch',
        'title'  => 'Onboard Airline',
        'sub'    => 'Start a new tenant',
        'tone'   => 'var(--accent-cyan)',
    ],
    [
        'href'   => '/platform/users',
        'icon'   => 'user',
        'title'  => 'Platform Staff',
        'sub'    => 'Add or manage internal users',
        'tone'   => 'var(--accent-blue)',
    ],
    [
        'href'   => '/platform/modules',
        'icon'   => 'puzzle-piece',
        'title'  => 'Module Catalog',
        'sub'    => 'Configure platform features',
        'tone'   => 'var(--accent-purple)',
    ],
    [
        'href'   => '/audit-log',
        'icon'   => 'lock-closed',
        'title'  => 'Audit Log',
        'sub'    => 'Review platform activity',
        'tone'   => 'var(--accent-green)',
    ],
];
?>
<div class="dash-quick-grid" style="display:grid; grid-template-columns:repeat(4,1fr); gap:0.85rem; margin-bottom:1.5rem;">
    <?php foreach ($quickActions as $qa): ?>
    <a href="<?= e($qa['href']) ?>"
       style="display:flex; align-items:center; gap:12px;
              padding:14px 16px;
              background:var(--bg-card);
              border:1px solid var(--border-color);
              border-left:3px solid <?= $qa['tone'] ?>;
              border-radius:var(--radius-md);
              text-decoration:none; color:inherit;
              transition:background 0.15s, transform 0.15s;"
       onmouseover="this.style.background='var(--bg-card-hover)';this.style.transform='translateY(-1px)';"
       onmouseout="this.style.background='var(--bg-card)';this.style.transform='translateY(0)';">
        <span style="display:inline-flex;align-items:center;justify-content:center;
                     width:36px;height:36px;border-radius:8px;
                     background:rgba(255,255,255,0.04);color:<?= $qa['tone'] ?>;">
            <?= sidebarIcon($qa['icon'], 18) ?>
        </span>
        <span style="display:flex; flex-direction:column; min-width:0;">
            <span style="font-size:13px; font-weight:600; color:var(--text-primary); line-height:1.2;">
                <?= e($qa['title']) ?>
            </span>
            <span style="font-size:11px; color:var(--text-tertiary); line-height:1.3; margin-top:2px;">
                <?= e($qa['sub']) ?>
            </span>
        </span>
    </a>
    <?php endforeach; ?>
</div>

<!-- ─── 3. Main Grid: Airlines (left) + Onboarding Queue (right) ─────── -->
<div class="dash-main-grid" style="display:grid; grid-template-columns:1.4fr 1fr; gap:1.25rem;">

    <!-- Airlines list -->
    <div class="card">
        <div class="card-header">
            <div class="card-title" style="display:flex;align-items:center;gap:8px;">
                <span style="display:inline-flex;color:var(--accent-blue);"><?= sidebarIcon('building-office', 16) ?></span>
                Airlines
            </div>
            <a href="/tenants" class="btn btn-sm btn-outline">Manage all →</a>
        </div>
        <?php if (empty($data['tenants'])): ?>
            <div class="empty-state">
                <p>No airlines registered yet.</p>
                <a href="/platform/onboarding/create" class="btn btn-primary" style="margin-top:.5rem;">
                    Start onboarding
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
                            <th style="text-align:right;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach (array_slice($data['tenants'], 0, 6) as $t): ?>
                        <?php $tc = $tierColors[$t['support_tier'] ?? 'standard'] ?? 'var(--text-tertiary)'; ?>
                        <tr>
                            <td>
                                <a href="/tenants/<?= (int) $t['id'] ?>"
                                   style="font-weight:500; color:var(--text-primary); text-decoration:none;">
                                    <?= e($t['name']) ?>
                                </a>
                            </td>
                            <td><code style="font-size:11px; color:var(--text-secondary);"><?= e($t['code']) ?></code></td>
                            <td>
                                <span style="font-size:11px; color:<?= $tc ?>; text-transform:capitalize; font-weight:600;">
                                    <?= e($t['support_tier'] ?? 'standard') ?>
                                </span>
                            </td>
                            <td style="text-align:right;"><?= statusBadge(!empty($t['is_active']) ? 'active' : 'suspended') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($data['tenants']) > 6): ?>
                <div style="padding:10px 0 2px; text-align:right;">
                    <a href="/tenants" style="font-size:12px; color:var(--accent-blue); text-decoration:none;">
                        View all <?= count($data['tenants']) ?> airlines →
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Onboarding queue -->
    <div class="card">
        <div class="card-header">
            <div class="card-title" style="display:flex;align-items:center;gap:8px;">
                <span style="display:inline-flex;color:var(--accent-yellow);"><?= sidebarIcon('rocket-launch', 16) ?></span>
                Onboarding Queue
                <?php $obCount = count($data['onboarding_pipeline'] ?? []); ?>
                <?php if ($obCount > 0): ?>
                    <span style="margin-left:6px; font-size:10px; font-weight:700;
                                 background:var(--status-advisory); color:#0a0e1a;
                                 padding:2px 7px; border-radius:10px;">
                        <?= $obCount ?>
                    </span>
                <?php endif; ?>
            </div>
            <a href="/platform/onboarding" class="btn btn-sm btn-outline">View all →</a>
        </div>
        <?php if (empty($data['onboarding_pipeline'])): ?>
            <div class="empty-state" style="padding:1.5rem 1rem;">
                <p style="margin:0;font-size:13px;color:var(--text-tertiary);">No onboarding requests waiting.</p>
                <a href="/platform/onboarding/create" class="btn btn-primary btn-sm" style="margin-top:.75rem;">
                    + Start a new onboarding
                </a>
            </div>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:8px;">
                <?php foreach (array_slice($data['onboarding_pipeline'], 0, 5) as $req): ?>
                    <?php $stColor = $obStatusColors[$req['status']] ?? 'var(--text-tertiary)'; ?>
                    <a href="/platform/onboarding/<?= (int) $req['id'] ?>"
                       style="display:flex; align-items:center; gap:12px;
                              padding:10px 12px;
                              background:rgba(255,255,255,0.02);
                              border:1px solid var(--border-light);
                              border-left:3px solid <?= $stColor ?>;
                              border-radius:var(--radius-sm);
                              text-decoration:none; color:inherit;
                              transition:background 0.15s;"
                       onmouseover="this.style.background='rgba(255,255,255,0.04)';"
                       onmouseout="this.style.background='rgba(255,255,255,0.02)';">
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:13px; font-weight:600; color:var(--text-primary);
                                        white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                <?= e($req['legal_name']) ?>
                            </div>
                            <div style="font-size:11px; color:var(--text-tertiary); margin-top:2px;">
                                <?= e($req['contact_name'] ?? '—') ?> ·
                                <?= e(ucfirst($req['support_tier'] ?? 'standard')) ?>
                            </div>
                        </div>
                        <span style="font-size:10px; padding:3px 8px; border-radius:10px;
                                     background:rgba(255,255,255,0.04);
                                     color:<?= $stColor ?>; font-weight:700; text-transform:uppercase;
                                     letter-spacing:0.04em; white-space:nowrap;">
                            <?= e($req['status'] === 'approved' ? 'Provision' : str_replace('_', ' ', $req['status'])) ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php if ($obCount > 5): ?>
                <div style="padding:10px 0 0; text-align:right;">
                    <a href="/platform/onboarding" style="font-size:12px; color:var(--accent-blue); text-decoration:none;">
                        + <?= $obCount - 5 ?> more →
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ─── 4. Recent Platform Activity ─────────────────────────────────── -->
<div class="card" style="margin-top:1.25rem;">
    <div class="card-header">
        <div class="card-title" style="display:flex;align-items:center;gap:8px;">
            <span style="display:inline-flex;color:var(--accent-green);"><?= sidebarIcon('chart-bar', 16) ?></span>
            Recent Platform Activity
        </div>
        <a href="/audit-log" class="btn btn-sm btn-outline">Full log →</a>
    </div>

    <?php if (empty($data['recent_activity'])): ?>
        <div class="empty-state"><p>No platform activity recorded yet.</p></div>
    <?php else: ?>
        <ul class="activity-list" style="margin:0; padding:0; list-style:none;">
            <?php foreach (array_slice($data['recent_activity'], 0, 8) as $log): ?>
                <li class="activity-item"
                    style="display:flex; gap:12px; padding:10px 0; border-bottom:1px solid var(--border-light);">
                    <span class="activity-dot"
                          style="width:8px;height:8px;border-radius:50%;
                                 background:var(--accent-purple);margin-top:7px;flex-shrink:0;"></span>
                    <div style="flex:1; min-width:0;">
                        <div style="font-size:13px; color:var(--text-primary);">
                            <strong><?= e($log['user_name'] ?? 'System') ?></strong>
                            <?php if (!empty($log['actor_role'])): ?>
                                <span style="color:var(--text-tertiary); font-size:11px;">
                                    (<?= e(str_replace('_', ' ', $log['actor_role'])) ?>)
                                </span>
                            <?php endif; ?>
                            <span style="margin-left:6px; font-size:11px; font-family:ui-monospace,monospace;
                                         color:var(--accent-blue); background:rgba(59,130,246,0.08);
                                         padding:1px 7px; border-radius:4px;">
                                <?= e($log['action']) ?>
                            </span>
                        </div>
                        <?php if (!empty($log['tenant_name'])): ?>
                            <div style="font-size:11px; color:var(--text-tertiary); margin-top:3px;">
                                <?= e($log['tenant_name']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:11px; color:var(--text-tertiary); white-space:nowrap; align-self:center;">
                        <?= formatDateTime($log['created_at']) ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<!-- ─── 5. Health Tile (modules / tier mix / pipeline counts) ───────── -->
<div class="dash-health-grid" style="display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; margin-top:1.25rem;">

    <!-- Module catalog -->
    <div class="card" style="padding:14px 16px;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
            <span style="font-size:11px; color:var(--text-tertiary); font-weight:700;
                         text-transform:uppercase; letter-spacing:.06em;">
                Module Catalog
            </span>
            <span style="display:inline-flex;color:var(--accent-purple);"><?= sidebarIcon('puzzle-piece', 14) ?></span>
        </div>
        <div style="font-size:1.6rem; font-weight:700; color:var(--accent-purple); line-height:1.1;">
            <?= (int) ($data['modules_in_catalog'] ?? 0) ?>
        </div>
        <div style="font-size:12px; color:var(--text-tertiary); margin-top:4px;">
            modules · <strong style="color:var(--text-primary);"><?= (int) ($data['module_assignments'] ?? 0) ?></strong> active assignments
        </div>
        <a href="/platform/modules"
           style="font-size:11px; color:var(--accent-blue); text-decoration:none; margin-top:10px; display:inline-block;">
            Manage catalog →
        </a>
    </div>

    <!-- Tier mix -->
    <div class="card" style="padding:14px 16px;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
            <span style="font-size:11px; color:var(--text-tertiary); font-weight:700;
                         text-transform:uppercase; letter-spacing:.06em;">
                Support Tiers
            </span>
            <span style="display:inline-flex;color:var(--accent-yellow);"><?= sidebarIcon('star', 14) ?></span>
        </div>
        <?php if (empty($data['tier_distribution'])): ?>
            <div style="font-size:12px; color:var(--text-tertiary);">No airlines registered yet.</div>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:6px;">
                <?php foreach ($data['tier_distribution'] as $tier => $cnt): ?>
                    <?php $color = $tierColors[$tier] ?? 'var(--text-tertiary)'; ?>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:<?= $color ?>;"></span>
                        <span style="font-size:12px; color:var(--text-primary); text-transform:capitalize; flex:1;"><?= e($tier) ?></span>
                        <span style="font-size:13px; font-weight:700; color:<?= $color ?>;"><?= (int) $cnt ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Onboarding pipeline -->
    <div class="card" style="padding:14px 16px;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
            <span style="font-size:11px; color:var(--text-tertiary); font-weight:700;
                         text-transform:uppercase; letter-spacing:.06em;">
                Onboarding Pipeline
            </span>
            <span style="display:inline-flex;color:var(--accent-green);"><?= sidebarIcon('rocket-launch', 14) ?></span>
        </div>
        <?php
        $hasPipeline = false;
        foreach (($data['onboarding_counts'] ?? []) as $c) { if ($c > 0) { $hasPipeline = true; break; } }
        ?>
        <?php if (!$hasPipeline): ?>
            <div style="font-size:12px; color:var(--text-tertiary);">No requests in pipeline.</div>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:6px;">
                <?php foreach ($pipelineLabels as $st => $info): ?>
                    <?php $cnt = (int) ($data['onboarding_counts'][$st] ?? 0); ?>
                    <?php if ($cnt === 0) continue; ?>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:<?= $info['color'] ?>;"></span>
                        <span style="font-size:12px; color:var(--text-primary); flex:1;"><?= e($info['label']) ?></span>
                        <span style="font-size:13px; font-weight:700; color:<?= $info['color'] ?>;"><?= $cnt ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <a href="/platform/onboarding"
           style="font-size:11px; color:var(--accent-blue); text-decoration:none; margin-top:10px; display:inline-block;">
            Manage onboarding →
        </a>
    </div>
</div>

<!-- Responsive collapse for narrower windows. -->
<style>
@media (max-width: 1100px) {
    .dash-quick-grid   { grid-template-columns: repeat(2, 1fr) !important; }
    .dash-main-grid    { grid-template-columns: 1fr !important; }
    .dash-health-grid  { grid-template-columns: 1fr !important; }
}
</style>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/app.php';
