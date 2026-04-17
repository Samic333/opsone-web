<?php
$pageTitle   = 'Module Catalog';
$pageSubtitle = 'Platform-controlled module availability';
ob_start();

$statusMeta = [
    'available'    => ['label' => 'Available',    'bg' => 'rgba(16,185,129,.1)',  'color' => '#10b981'],
    'beta'         => ['label' => 'Beta',         'bg' => 'rgba(99,102,241,.1)', 'color' => '#6366f1'],
    'coming_soon'  => ['label' => 'Coming Soon',  'bg' => 'rgba(245,158,11,.1)', 'color' => '#f59e0b'],
    'disabled'     => ['label' => 'Disabled',     'bg' => 'rgba(107,114,128,.1)','color' => '#6b7280'],
];

$activeModules   = array_filter($modules, fn($m) => in_array($m['platform_status'], ['available', 'beta']));
$upcomingModules = array_filter($modules, fn($m) => $m['platform_status'] === 'coming_soon');
$disabledModules = array_filter($modules, fn($m) => $m['platform_status'] === 'disabled');
?>

<div class="card" style="margin-bottom:1.5rem; padding:12px 16px; background:rgba(99,102,241,0.06); border:1px solid #6366f1;">
    <p style="margin:0; font-size:13px;">
        <strong>Module Catalog</strong> — These are the modules available across the platform.
        Enable or disable them per-airline from the
        <a href="/tenants" style="color:#6366f1;">Airline Registry</a> or each airline's detail page.
        Modules marked <em>Coming Soon</em> are on the roadmap and cannot yet be assigned to airlines.
    </p>
</div>

<?php if (empty($modules)): ?>
<div class="card">
    <p style="color:var(--text-muted); text-align:center; padding:2rem;">No modules defined. Run the Phase Zero seeder.</p>
</div>
<?php else: ?>

<?php
// Reusable module card renderer
function renderModuleCard(array $mod, array $statusMeta): void {
    $sm      = $statusMeta[$mod['platform_status']] ?? $statusMeta['disabled'];
    $dimmed  = $mod['platform_status'] === 'coming_soon' || $mod['platform_status'] === 'disabled';
    $opacity = $dimmed ? 'opacity:.65;' : '';
    ?>
    <div class="card" style="padding:1rem; <?= $opacity ?>">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px;">
            <div>
                <?php if (!empty($mod['icon'])): ?>
                <span style="font-size:20px; margin-right:6px;"><?= $mod['icon'] ?></span>
                <?php endif; ?>
                <div style="font-weight:600; font-size:.95rem; display:inline;"><?= e($mod['name']) ?></div>
                <div><code style="font-size:11px; color:var(--text-muted);"><?= e($mod['code']) ?></code></div>
            </div>
            <div style="display:flex; gap:6px; flex-wrap:wrap; justify-content:flex-end;">
                <?php if ($mod['mobile_capable']): ?>
                    <span style="font-size:10px; padding:2px 6px; background:rgba(99,102,241,0.1); color:#6366f1; border-radius:4px;">📱 iPad</span>
                <?php endif; ?>
                <span style="font-size:10px; padding:2px 7px; border-radius:4px;
                              background:<?= $sm['bg'] ?>; color:<?= $sm['color'] ?>; font-weight:700;">
                    <?= $sm['label'] ?>
                </span>
            </div>
        </div>

        <?php if ($mod['description']): ?>
        <p style="font-size:12px; color:var(--text-muted); margin:0 0 10px;"><?= e($mod['description']) ?></p>
        <?php endif; ?>

        <?php if (!empty($mod['capabilities'])): ?>
        <div style="display:flex; flex-wrap:wrap; gap:4px;">
            <?php foreach ($mod['capabilities'] as $cap): ?>
            <span style="font-size:10px; padding:2px 7px; background:var(--surface);
                          border:1px solid var(--border); border-radius:10px; color:var(--text-muted);">
                <?= e($cap['capability']) ?>
            </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
}
?>

<!-- Active & Beta -->
<?php if (!empty($activeModules)): ?>
<div style="margin-bottom:8px;">
    <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--text-muted); margin-bottom:12px;">
        Active Modules (<?= count($activeModules) ?>)
    </div>
    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:1rem;">
        <?php foreach ($activeModules as $mod): renderModuleCard($mod, $statusMeta); endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Coming Soon -->
<?php if (!empty($upcomingModules)): ?>
<div style="margin-top:32px; margin-bottom:8px;">
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
        <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--text-muted);">
            Coming Soon (<?= count($upcomingModules) ?>)
        </div>
        <span style="font-size:11px; padding:2px 8px; background:rgba(245,158,11,.1); color:#f59e0b; border-radius:8px; font-weight:600;">
            On the Roadmap — not yet assignable to airlines
        </span>
    </div>
    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:1rem;">
        <?php foreach ($upcomingModules as $mod): renderModuleCard($mod, $statusMeta); endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Disabled -->
<?php if (!empty($disabledModules)): ?>
<div style="margin-top:32px; margin-bottom:8px;">
    <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--text-muted); margin-bottom:12px;">
        Disabled (<?= count($disabledModules) ?>)
    </div>
    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:1rem;">
        <?php foreach ($disabledModules as $mod): renderModuleCard($mod, $statusMeta); endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/app.php'; ?>
