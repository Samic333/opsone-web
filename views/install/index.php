<?php /** OpsOne — Protected Install Page */ $brand = require CONFIG_PATH . '/branding.php'; ?>

<section class="section section-gradient">
    <div class="section-inner" style="max-width: 800px;">
        
        <div class="section-header">
            <div class="section-label">Enterprise Deployment</div>
            <h1 class="section-title">Install <?= e($brand['product_name']) ?></h1>
            <p class="section-desc">
                This portal is for authorized airline personnel only. The <?= e($brand['product_name']) ?> iPad app is distributed internally through enterprise web installation — not through the App Store.
            </p>
        </div>

        <div class="feature-card" style="margin-bottom: 32px; text-align: center; padding: 48px 32px;">
            <?php if ($latestBuild): ?>
                
                <div class="hero-stats" style="justify-content: center; flex-wrap: wrap; margin-bottom: 32px;">
                    <div class="hero-stat">
                        <div class="hero-stat-value"><?= e($latestBuild['version']) ?></div>
                        <div class="hero-stat-label">Version</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-value"><?= e($latestBuild['build_number']) ?></div>
                        <div class="hero-stat-label">Build</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-value"><?= e($latestBuild['min_os_version']) ?></div>
                        <div class="hero-stat-label">Min iPadOS</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-value" style="font-size: 20px; padding-top: 8px;"><?= formatDate($latestBuild['created_at']) ?></div>
                        <div class="hero-stat-label">Released</div>
                    </div>
                </div>

                <?php if ($latestBuild['release_notes']): ?>
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 16px; margin-bottom: 32px; text-align: left;">
                        <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Release Notes</h4>
                        <p style="font-size: 14px; color: var(--text-secondary); margin: 0;"><?= nl2br(e($latestBuild['release_notes'])) ?></p>
                    </div>
                <?php endif; ?>

                <div style="margin-bottom: 16px;">
                    <a href="itms-services://?action=download-manifest&url=<?= urlencode(config('app.url') . '/install/manifest') ?>"
                       class="pub-btn pub-btn-primary pub-btn-large">
                        📲 Install OpsOne v<?= e($latestBuild['version']) ?>
                    </a>
                </div>
                
                <div style="margin-top: 16px;">
                    <a href="/install/download/<?= $latestBuild['id'] ?>" style="color: var(--text-secondary); font-size: 13px; text-decoration: underline;">
                        Download .ipa file directly
                    </a>
                </div>
                
                <p style="font-size: 13px; color: var(--text-tertiary); margin-top: 24px; max-width: 400px; margin-left: auto; margin-right: auto;">
                    Tap this button on your iPad to install the app. You may need to trust the enterprise certificate afterward.
                </p>

            <?php else: ?>
                <div style="padding: 40px 0;">
                    <div style="font-size: 48px; margin-bottom: 16px;">📦</div>
                    <h3 style="font-size: 20px; font-weight: 700; color: var(--text-primary); margin-bottom: 8px;">No Build Available</h3>
                    <p style="color: var(--text-secondary); font-size: 15px;">
                        The enterprise build has not been uploaded yet. Contact your platform administrator to prepare the first build.
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <div class="features-grid" style="margin-bottom: 32px; grid-template-columns: 1fr;">
            <!-- Device Requirements -->
            <div class="info-card" style="margin: 0; padding: 32px;">
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; display: flex; align-items: center; gap: 8px;"><span style="font-size: 20px;">📱</span> Device Requirements</h3>
                <div style="display: grid; grid-template-columns: 140px 1fr; gap: 16px 24px; font-size: 14px;">
                    <div style="font-weight: 600; color: var(--text-primary);">Device</div>
                    <div style="color: var(--text-secondary);">iPad (2018 or later recommended)</div>
                    
                    <div style="font-weight: 600; color: var(--text-primary);">OS</div>
                    <div style="color: var(--text-secondary);">iPadOS 16.0 or later</div>
                    
                    <div style="font-weight: 600; color: var(--text-primary);">Storage</div>
                    <div style="color: var(--text-secondary);">Minimum 500 MB free (1 GB+ recommended)</div>
                    
                    <div style="font-weight: 600; color: var(--text-primary);">Network</div>
                    <div style="color: var(--text-secondary);">Internet required for initial setup and sync</div>
                </div>
            </div>

            <!-- Quick Install Guide -->
            <div class="info-card" style="margin: 0; padding: 32px;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 24px;">
                    <h3 style="font-size: 18px; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 8px;"><span style="font-size: 20px;">📋</span> Quick Install Guide</h3>
                    <a href="/install/instructions" class="pub-btn pub-btn-outline" style="padding: 6px 12px; font-size: 12px;">Full Instructions →</a>
                </div>
                
                <ol style="padding-left: 20px; line-height: 2.4; font-size: 14px; color: var(--text-secondary); margin: 0;">
                    <li>Tap the <strong style="color: var(--text-primary);">Install</strong> button above on your iPad</li>
                    <li>When prompted, tap <strong style="color: var(--text-primary);">"Install"</strong> to confirm</li>
                    <li>Go to <strong style="color: var(--text-primary);">Settings → General → VPN & Device Management</strong></li>
                    <li>Find the enterprise certificate and tap <strong style="color: var(--text-primary);">"Trust"</strong></li>
                    <li>Open <?= e($brand['product_name']) ?> and <strong style="color: var(--text-primary);">log in</strong> with your airline credentials</li>
                    <li>Your device will be registered — wait for <strong style="color: var(--text-primary);">admin approval</strong></li>
                    <li>Once approved, the app will <strong style="color: var(--text-primary);">sync your airline's content</strong></li>
                </ol>
            </div>
            
            <!-- Support -->
            <div class="info-card" style="margin: 0; padding: 32px; background: rgba(59,130,246,0.03); border-color: rgba(59,130,246,0.15);">
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; color: var(--accent-blue);"><span style="font-size: 20px;">🆘</span> Need Help?</h3>
                <p style="font-size: 14px; color: var(--text-secondary); margin: 0; line-height: 1.7;">
                    If you encounter issues during installation, contact your airline administrator or visit the 
                    <a href="/support" style="color: var(--accent-blue); text-decoration: underline;">support page</a>. 
                    Common issues include needing to trust the enterprise certificate or waiting for device approval.
                </p>
            </div>
        </div>

    </div>
</section>
