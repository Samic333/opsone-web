<?php
$pageTitle   = 'Airline Registry';
$pageSubtitle = 'All airline tenants on the platform';
$headerAction = '<a href="/tenants/create" class="btn btn-primary">+ Add Airline</a>';
ob_start();
?>
<div class="stats-grid" style="margin-bottom:1.5rem;">
    <div class="stat-card">
        <div class="stat-value"><?= count($tenants) ?></div>
        <div class="stat-label">Total Airlines</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= count(array_filter($tenants, fn($t) => $t['is_active'])) ?></div>
        <div class="stat-label">Active</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $pendingOnboarding ?></div>
        <div class="stat-label">Pending Onboarding</div>
    </div>
</div>

<?php if (empty($tenants)): ?>
    <div class="card">
        <div class="empty-state">
            <div class="icon">🏢</div>
            <h3>No Airlines Yet</h3>
            <p>Create your first airline tenant to get started.</p>
            <a href="/tenants/create" class="btn btn-primary mt-2">+ Create Airline</a>
        </div>
    </div>
<?php else: ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Airline</th>
                <th>Code</th>
                <th>Country</th>
                <th>Tier</th>
                <th style="text-align:center;">Users</th>
                <th style="text-align:center;">Modules</th>
                <th style="text-align:center;">Devices ⏳</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($tenants as $t): ?>
        <tr>
            <td>
                <strong><?= e($t['name']) ?></strong>
                <?php if (($t['legal_name'] ?? '') && ($t['legal_name'] ?? '') !== $t['name']): ?>
                <div style="font-size:11px; color:var(--text-muted);"><?= e($t['legal_name']) ?></div>
                <?php endif; ?>
            </td>
            <td>
                <code><?= e($t['code']) ?></code>
                <?php if ($t['icao_code'] ?? null): ?>
                <small style="color:var(--text-muted);"> / <?= e($t['icao_code']) ?></small>
                <?php endif; ?>
            </td>
            <td><?= e($t['primary_country'] ?? '—') ?></td>
            <td><span style="text-transform:capitalize;"><?= e($t['support_tier'] ?? 'standard') ?></span></td>
            <td style="text-align:center;"><?= $t['user_count'] ?? 0 ?></td>
            <td style="text-align:center;"><?= $t['enabled_modules'] ?? 0 ?></td>
            <td style="text-align:center;">
                <?php if (($t['pending_devices'] ?? 0) > 0): ?>
                    <span style="color:var(--accent-yellow); font-weight:600;"><?= $t['pending_devices'] ?></span>
                <?php else: ?>
                    —
                <?php endif; ?>
            </td>
            <td><?= statusBadge($t['is_active'] ? 'active' : 'suspended') ?></td>
            <td>
                <div class="btn-group">
                    <a href="/tenants/<?= $t['id'] ?>" class="btn btn-xs btn-outline">View</a>
                    <a href="/tenants/edit/<?= $t['id'] ?>" class="btn btn-xs btn-outline">Edit</a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if ($pendingOnboarding > 0): ?>
<div style="margin-top:1.5rem; padding:12px 16px; background:rgba(99,102,241,0.08);
            border:1px solid #6366f1; border-radius:8px; font-size:13px;">
    📋 <strong><?= $pendingOnboarding ?></strong> pending onboarding request(s) awaiting review.
    <a href="/platform/onboarding" style="color:#6366f1; font-weight:600; margin-left:8px;">
        Review now →
    </a>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/app.php'; ?>
