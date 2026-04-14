<?php
$pageTitle   = 'Modules — ' . e($tenant['name']);
$pageSubtitle = 'Enable or disable modules for this airline';
$headerAction = '<a href="/tenants/' . $tenant['id'] . '" class="btn btn-outline">← Back to Airline</a>';
ob_start();
?>

<div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:1rem;">
    <?php foreach ($modules as $mod): ?>
    <div class="card" style="padding:1rem; border-left:3px solid <?= $mod['tenant_enabled'] ? '#10b981' : 'var(--border)' ?>;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px;">
            <div>
                <div style="font-weight:600; font-size:.9rem;"><?= e($mod['name']) ?></div>
                <code style="font-size:11px; color:var(--text-muted);"><?= e($mod['code']) ?></code>
            </div>
            <form method="POST" action="/tenants/<?= $tenant['id'] ?>/modules/<?= $mod['id'] ?>/toggle">
                <?= csrfField() ?>
                <button type="submit" class="btn btn-outline" style="font-size:11px; padding:3px 10px;
                    <?= $mod['tenant_enabled'] ? 'border-color:#10b981; color:#10b981;' : '' ?>">
                    <?= $mod['tenant_enabled'] ? '✓ Enabled' : 'Enable' ?>
                </button>
            </form>
        </div>
        <?php if ($mod['description']): ?>
        <p style="font-size:12px; color:var(--text-muted); margin:0;"><?= e($mod['description']) ?></p>
        <?php endif; ?>
        <?php if ($mod['mobile_capable']): ?>
        <div style="margin-top:8px; font-size:10px; color:#6366f1;">📱 iPad capable</div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/app.php'; ?>
