<?php /** OpsOne — Protected Install Page */ $brand = require CONFIG_PATH . '/branding.php'; ?>

<div class="card mb-3">
    <div class="card-header">
        <div class="card-title">🛡️ Internal Enterprise Installation</div>
    </div>
    <p class="text-muted text-sm" style="margin-bottom: 16px;">
        This page is for authorized airline personnel only. The <?= e($brand['product_name']) ?> iPad app is distributed internally through enterprise web installation — not through the App Store.
    </p>

    <?php if ($latestBuild): ?>
    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="stat-label">Latest Version</div>
            <div class="stat-value"><?= e($latestBuild['version']) ?></div>
        </div>
        <div class="stat-card cyan">
            <div class="stat-label">Build Number</div>
            <div class="stat-value"><?= e($latestBuild['build_number']) ?></div>
        </div>
        <div class="stat-card green">
            <div class="stat-label">Min iPadOS</div>
            <div class="stat-value"><?= e($latestBuild['min_os_version']) ?></div>
        </div>
        <div class="stat-card purple">
            <div class="stat-label">Released</div>
            <div class="stat-value" style="font-size: 18px;"><?= formatDate($latestBuild['created_at']) ?></div>
        </div>
    </div>

    <?php if ($latestBuild['release_notes']): ?>
    <div class="card mb-2">
        <div class="card-title" style="margin-bottom: 8px;">Release Notes</div>
        <p class="text-muted text-sm"><?= nl2br(e($latestBuild['release_notes'])) ?></p>
    </div>
    <?php endif; ?>

    <div style="text-align: center; padding: 32px 0;">
        <?php if ($latestBuild['file_path']): ?>
        <a href="itms-services://?action=download-manifest&url=<?= urlencode(config('app.url') . '/install/manifest') ?>"
           class="btn btn-primary" style="font-size: 18px; padding: 16px 48px;">
            📲 Install <?= e($brand['product_name']) ?> v<?= e($latestBuild['version']) ?>
        </a>
        <p class="text-muted text-sm mt-2">Tap this button on your iPad to install the app. You may need to trust the enterprise certificate afterward.</p>
        <?php else: ?>
        <div class="alert alert-error">
            ⚠ No build file available yet. The enterprise build (.ipa) has not been uploaded. Contact your platform administrator.
        </div>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <div class="empty-state">
        <div class="icon">📦</div>
        <h3>No Build Available</h3>
        <p>The enterprise build has not been uploaded yet. Contact your platform administrator to prepare the first build.</p>
    </div>
    <?php endif; ?>
</div>

<!-- Device Requirements -->
<div class="card mb-3">
    <div class="card-header">
        <div class="card-title">📱 Device Requirements</div>
    </div>
    <table>
        <tbody>
            <tr><td style="font-weight: 600;">Device</td><td>iPad (2018 or later recommended)</td></tr>
            <tr><td style="font-weight: 600;">Operating System</td><td>iPadOS 16.0 or later</td></tr>
            <tr><td style="font-weight: 600;">Storage</td><td>Minimum 500 MB free (1 GB+ recommended)</td></tr>
            <tr><td style="font-weight: 600;">Network</td><td>Internet required for initial setup and sync</td></tr>
            <tr><td style="font-weight: 600;">Distribution</td><td>Enterprise internal — not App Store</td></tr>
        </tbody>
    </table>
</div>

<!-- Quick Install Guide -->
<div class="card mb-3">
    <div class="card-header">
        <div class="card-title">📋 Quick Install Guide</div>
        <a href="/install/instructions" class="btn btn-outline btn-sm">Full Instructions →</a>
    </div>
    <ol style="padding-left: 20px; line-height: 2.2; font-size: 14px; color: var(--text-secondary);">
        <li>Tap the <strong>Install</strong> button above on your iPad</li>
        <li>When prompted, tap <strong>"Install"</strong> to confirm</li>
        <li>Go to <strong>Settings → General → VPN & Device Management</strong></li>
        <li>Find the enterprise certificate and tap <strong>"Trust"</strong></li>
        <li>Open <?= e($brand['product_name']) ?> and <strong>log in</strong> with your airline credentials</li>
        <li>Your device will be registered — wait for <strong>admin approval</strong></li>
        <li>Once approved, the app will <strong>sync your airline's content</strong></li>
    </ol>
</div>

<!-- Support -->
<div class="card">
    <div class="card-header">
        <div class="card-title">🆘 Need Help?</div>
    </div>
    <p class="text-muted text-sm">
        If you encounter issues during installation, contact your airline administrator or visit the
        <a href="/support">support page</a>. Common issues include needing to trust the enterprise certificate
        or waiting for device approval.
    </p>
</div>
