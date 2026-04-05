<?php /** OpsOne — Support */ ?>
<div class="info-page">
    <div class="info-page-inner">
        <div class="section-label">✦ Support</div>
        <h1>Support Center</h1>
        <p class="lead">Need help with <?= e($brand['product_name']) ?>? We're here to assist airline administrators and crew members.</p>

        <h2>For Crew Members</h2>
        <div class="info-card">
            <h3>📱 App Issues</h3>
            <p>If you're experiencing issues with the iPad app, try the following:</p>
            <ul>
                <li>Ensure your iPad is connected to the internet</li>
                <li>Force-close the app and reopen it</li>
                <li>Go to Sync Center and tap "Re-Sync"</li>
                <li>Check that your iPadOS version is 16.0 or later</li>
                <li>Contact your airline administrator if the issue persists</li>
            </ul>
        </div>
        <div class="info-card">
            <h3>🔐 Login Issues</h3>
            <p>If you cannot log in:</p>
            <ul>
                <li>Verify your email and password are correct</li>
                <li>Check that your account is active (contact your admin)</li>
                <li>Ensure your device has been approved by an administrator</li>
                <li>Try logging in on the web portal first to verify credentials</li>
            </ul>
        </div>

        <h2>For Airline Administrators</h2>
        <div class="info-card">
            <h3>🏢 Portal Administration</h3>
            <p>Common admin tasks:</p>
            <ul>
                <li><strong>Creating users:</strong> Go to Users → Create User. Assign a role, department, and base.</li>
                <li><strong>Approving devices:</strong> Go to Devices. Pending devices appear with an "Approve" button.</li>
                <li><strong>Uploading documents:</strong> Go to Documents → Upload. Select category, set visibility, and publish.</li>
                <li><strong>Publishing notices:</strong> Go to Notices → New Notice. Set priority and publish immediately or schedule.</li>
            </ul>
        </div>

        <h2>Contact Support</h2>
        <p>If you need further assistance, contact us at:</p>
        <ul>
            <li><strong>Email:</strong> <a href="mailto:<?= e($brand['support_email']) ?>"><?= e($brand['support_email']) ?></a></li>
            <li><strong>Web:</strong> <a href="/contact">Contact Form</a></li>
        </ul>
    </div>
</div>
