<?php
$pageTitle   = 'Module Catalog';
$pageSubtitle = 'Platform-controlled module availability';
ob_start();
?>

<div class="card" style="margin-bottom:1.5rem; padding:12px 16px; background:rgba(99,102,241,0.06); border:1px solid #6366f1;">
    <p style="margin:0; font-size:13px;">
        <strong>Module Catalog</strong> — These are the modules available across the platform.
        Enable or disable them per-airline from the
        <a href="/tenants" style="color:#6366f1;">Airline Registry</a> or each airline's detail page.
    </p>
</div>

<?php if (empty($modules)): ?>
<div class="card">
    <p style="color:var(--text-muted); text-align:center; padding:2rem;">No modules defined. Run the Phase Zero seeder.</p>
</div>
<?php else: ?>
<div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:1rem;">
    <?php foreach ($modules as $mod): ?>
    <div class="card" style="padding:1rem;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px;">
            <div>
                <div style="font-weight:600; font-size:.95rem;"><?= e($mod['name']) ?></div>
                <code style="font-size:11px; color:var(--text-muted);"><?= e($mod['code']) ?></code>
            </div>
            <div style="display:flex; gap:6px; flex-wrap:wrap; justify-content:flex-end;">
                <?php if ($mod['mobile_capable']): ?>
                    <span style="font-size:10px; padding:2px 6px; background:rgba(99,102,241,0.1); color:#6366f1; border-radius:4px;">📱 iPad</span>
                <?php endif; ?>
                <span style="font-size:10px; padding:2px 6px; border-radius:4px;
                              background:<?= $mod['platform_status'] === 'available' ? 'rgba(16,185,129,0.1)' : 'rgba(245,158,11,0.1)' ?>;
                              color:<?= $mod['platform_status'] === 'available' ? '#10b981' : '#f59e0b' ?>;">
                    <?= ucfirst($mod['platform_status']) ?>
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
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/app.php'; ?>
