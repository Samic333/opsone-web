<?php /** OpsOne — Internal Deployment Info */ ?>
<div class="info-page">
    <div class="info-page-inner">
        <div class="section-label">✦ Internal Deployment</div>
        <h1>Enterprise iPad Installation</h1>
        <p class="lead"><?= e($brand['product_name']) ?> is distributed internally through a secure website-based installation flow — not through the App Store or TestFlight.</p>

        <div class="info-card">
            <h3>⚠️ Internal Use Only</h3>
            <p>This application is intended exclusively for authorized airline personnel. It is not available for public download. Access is controlled through your airline's administrator.</p>
        </div>

        <h2>How Installation Works</h2>
        <ol>
            <li><strong>Get an account</strong> — Your airline administrator creates your user account with the appropriate role.</li>
            <li><strong>Log in to the portal</strong> — Visit the <a href="/install"><?= e($brand['product_name']) ?> install page</a> and sign in with your airline credentials.</li>
            <li><strong>Download the app</strong> — Tap the install button on the install page. Your iPad will download the enterprise-signed app.</li>
            <li><strong>Trust the developer</strong> — Go to <strong>Settings → General → VPN & Device Management</strong> and trust the enterprise developer certificate.</li>
            <li><strong>Open the app</strong> — Launch <?= e($brand['product_name']) ?> and log in with the same airline credentials.</li>
            <li><strong>Device registration</strong> — Your iPad is registered and must be approved by your airline administrator before you can access content.</li>
        </ol>

        <h2>Device Requirements</h2>
        <ul>
            <li>iPad (any model from 2018 or later)</li>
            <li>iPadOS 16.0 or later</li>
            <li>At least 500 MB free storage (more recommended for offline documents)</li>
            <li>Internet connection for initial setup and sync</li>
        </ul>

        <h2>After Installation</h2>
        <ul>
            <li>The app will sync your airline's content automatically</li>
            <li>Downloaded documents are available offline</li>
            <li>You can check sync status in the Sync Center module</li>
            <li>Updates to the app will be available through the same install page</li>
        </ul>

        <h2>Troubleshooting</h2>
        <div class="info-card">
            <h3>"Unable to Install" Error</h3>
            <p>This usually means the enterprise certificate needs to be trusted. Go to <strong>Settings → General → VPN & Device Management</strong>, find the enterprise certificate, and tap "Trust".</p>
        </div>
        <div class="info-card">
            <h3>"Device Not Approved" Screen</h3>
            <p>After logging in, your device must be approved by an airline administrator. This is a security measure. Contact your admin if approval is delayed.</p>
        </div>
        <div class="info-card">
            <h3>Need Help?</h3>
            <p>Contact your airline administrator or visit the <a href="/support">support page</a> for assistance.</p>
        </div>
    </div>
</div>
