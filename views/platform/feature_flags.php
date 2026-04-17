<?php
/**
 * Platform Feature Flags — Phase 10
 * Variables: $flags, $categories
 */
$categoryLabels = [
    'integration' => ['label' => 'External Integrations', 'icon' => '🔌', 'color' => '#6366f1'],
    'mobile'      => ['label' => 'Mobile & iPad',         'icon' => '📱', 'color' => '#3b82f6'],
    'ops'         => ['label' => 'Operations',            'icon' => '✈️', 'color' => '#10b981'],
    'platform'    => ['label' => 'Platform & AI',         'icon' => '🤖', 'color' => '#8b5cf6'],
    'general'     => ['label' => 'General',               'icon' => '⚙️', 'color' => '#6b7280'],
];
?>

<style>
.flag-card { border:1px solid var(--border); border-radius:10px; padding:16px; background:var(--bg-card); margin-bottom:10px; }
.flag-card:hover { border-color:var(--accent-blue,#3b82f6); }
.flag-meta { font-size:11px; color:var(--text-muted); display:flex; gap:12px; flex-wrap:wrap; margin-top:4px; }
.flag-badge { display:inline-flex; align-items:center; gap:3px; padding:1px 8px; border-radius:10px; font-size:10px; font-weight:700; }
.toggle-btn { padding:4px 12px; border-radius:6px; border:1px solid var(--border); cursor:pointer; font-size:12px; font-weight:600; background:var(--bg-secondary); color:var(--text); }
.toggle-btn.on  { background:rgba(16,185,129,.1); border-color:#10b981; color:#10b981; }
.toggle-btn.off { background:rgba(239,68,68,.08); border-color:#ef4444; color:#ef4444; }
</style>

<div class="card" style="margin-bottom:24px; padding:12px 16px; background:rgba(99,102,241,.06); border:1px solid #6366f1;">
    <p style="margin:0; font-size:13px;">
        <strong>Feature Flags</strong> control which experimental or beta capabilities are active.
        Toggle <em>Global</em> to enable a flag for every airline, or use per-airline toggles on the
        <a href="/tenants" style="color:#6366f1;">Airline Registry</a> page.
        Flags marked <span style="color:#10b981; font-weight:700;">Global</span> override per-airline settings.
    </p>
</div>

<?php
$byCategory = [];
foreach ($flags as $f) {
    $byCategory[$f['category']][] = $f;
}

foreach ($categories as $cat):
    $catInfo = $categoryLabels[$cat] ?? ['label' => ucfirst($cat), 'icon' => '⚙️', 'color' => '#6b7280'];
    $catFlags = $byCategory[$cat] ?? [];
    if (empty($catFlags)) continue;
?>
<div style="margin-bottom:28px;">
    <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
        <span style="font-size:18px;"><?= $catInfo['icon'] ?></span>
        <div>
            <div style="font-weight:700; font-size:.95rem; color:var(--text);"><?= e($catInfo['label']) ?></div>
            <div style="font-size:11px; color:var(--text-muted);"><?= count($catFlags) ?> flag<?= count($catFlags) !== 1 ? 's' : '' ?></div>
        </div>
    </div>

    <?php foreach ($catFlags as $flag): ?>
    <div class="flag-card">
        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap;">

            <div style="flex:1; min-width:240px;">
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:4px;">
                    <code style="font-size:12px; padding:1px 6px; background:var(--bg-secondary); border-radius:4px; color:var(--text-muted);"><?= e($flag['code']) ?></code>
                    <strong style="font-size:14px;"><?= e($flag['name']) ?></strong>
                    <?php if ($flag['is_global']): ?>
                        <span class="flag-badge" style="background:rgba(16,185,129,.1); color:#10b981;">🌍 Global ON</span>
                    <?php endif; ?>
                    <?php if ($flag['enabled_by_default']): ?>
                        <span class="flag-badge" style="background:rgba(99,102,241,.1); color:#6366f1;">Default ON</span>
                    <?php endif; ?>
                </div>
                <?php if ($flag['description']): ?>
                <p style="margin:0; font-size:13px; color:var(--text-muted);"><?= e($flag['description']) ?></p>
                <?php endif; ?>
                <div class="flag-meta">
                    <span>Category: <strong><?= e($cat) ?></strong></span>
                    <span>Tenant overrides: <strong><?= (int)$flag['tenant_count'] ?></strong></span>
                    <span>Enabled for: <strong><?= (int)$flag['enabled_tenant_count'] ?></strong> airline<?= $flag['enabled_tenant_count'] !== 1 ? 's' : '' ?></span>
                </div>
            </div>

            <div style="display:flex; gap:8px; align-items:center; flex-shrink:0;">
                <!-- Global toggle -->
                <form method="POST" action="/platform/feature-flags/toggle/<?= $flag['id'] ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="global_toggle">
                    <button type="submit"
                            class="toggle-btn <?= $flag['is_global'] ? 'on' : 'off' ?>"
                            title="<?= $flag['is_global'] ? 'Disable globally' : 'Enable globally (all airlines)' ?>">
                        <?= $flag['is_global'] ? '✓ Global ON' : '○ Global OFF' ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>

<?php if (empty($flags)): ?>
<div class="card">
    <div class="empty-state">
        <div class="icon">🚩</div>
        <h3>No Feature Flags Defined</h3>
        <p>Run the Phase 10 migration to seed the initial flag set.</p>
        <code style="font-size:12px;">018_phase10_future_readiness.sql</code>
    </div>
</div>
<?php endif; ?>
