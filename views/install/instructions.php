<?php /** OpsOne — Full Installation Instructions */ $brand = require CONFIG_PATH . '/branding.php'; ?>

<div class="card mb-3">
    <div class="card-header">
        <div class="card-title">📖 Complete Installation Guide</div>
    </div>

    <h3 style="margin: 20px 0 12px; font-size: 16px;">Step 1: Download the App</h3>
    <p class="text-muted text-sm">Navigate to the <a href="/install">Install Page</a> on your iPad's Safari browser. Tap the "Install" button. A confirmation dialog will appear — tap "Install" to begin the download.</p>

    <h3 style="margin: 20px 0 12px; font-size: 16px;">Step 2: Trust the Enterprise Certificate</h3>
    <p class="text-muted text-sm">After the app downloads, you <strong>must</strong> trust the enterprise developer certificate before the app will open:</p>
    <ol style="padding-left: 20px; line-height: 2; font-size: 13px; color: var(--text-secondary);">
        <li>Open <strong>Settings</strong> on your iPad</li>
        <li>Go to <strong>General</strong></li>
        <li>Scroll down and tap <strong>VPN & Device Management</strong></li>
        <li>Under "Enterprise App", find the developer certificate</li>
        <li>Tap the certificate name</li>
        <li>Tap <strong>"Trust [Developer Name]"</strong></li>
        <li>Confirm by tapping <strong>"Trust"</strong> in the dialog</li>
    </ol>

    <h3 style="margin: 20px 0 12px; font-size: 16px;">Step 3: Open the App</h3>
    <p class="text-muted text-sm">Find <?= e($brand['product_name']) ?> on your home screen and tap to open. If you see an "Untrusted Developer" message, go back to Step 2 and ensure the certificate is trusted.</p>

    <h3 style="margin: 20px 0 12px; font-size: 16px;">Step 4: Log In</h3>
    <p class="text-muted text-sm">Enter your airline email and password — the same credentials used for the web portal. If you don't have credentials, contact your airline administrator.</p>

    <h3 style="margin: 20px 0 12px; font-size: 16px;">Step 5: Device Registration</h3>
    <p class="text-muted text-sm">After your first login, your iPad is registered with the platform. You will see a "Device Pending Approval" screen. Your airline administrator must approve your device before you can access content.</p>

    <h3 style="margin: 20px 0 12px; font-size: 16px;">Step 6: Sync Content</h3>
    <p class="text-muted text-sm">Once approved, the app will automatically sync your airline's content — manuals, notices, briefings, and other documents. You can check sync status in the Sync Center module.</p>
</div>

<div class="card mb-3">
    <div class="card-header">
        <div class="card-title">⚠️ Known Limitations</div>
    </div>
    <ul style="padding-left: 20px; line-height: 2; font-size: 13px; color: var(--text-secondary);">
        <li>Enterprise certificates expire annually — app updates will be needed when the certificate is renewed</li>
        <li>The app must be installed via Safari — other browsers may not support the manifest-based install</li>
        <li>Push notifications are not available for enterprise-distributed apps without additional configuration</li>
        <li>First-time sync may take a few minutes depending on the amount of content your airline has uploaded</li>
    </ul>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">🔗 Useful Links</div>
    </div>
    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
        <a href="/install" class="btn btn-primary">← Back to Install Page</a>
        <a href="/support" class="btn btn-outline">Support Center</a>
        <a href="/faq" class="btn btn-outline">FAQ</a>
    </div>
</div>
