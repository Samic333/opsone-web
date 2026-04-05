<?php /** OpsOne — Privacy Policy */ ?>
<div class="info-page">
    <div class="info-page-inner">
        <div class="section-label">✦ Legal</div>
        <h1>Privacy Policy</h1>
        <p class="lead">Last updated: <?= date('F j, Y') ?></p>

        <h2>1. Introduction</h2>
        <p><?= e($brand['company_name']) ?> ("we", "our", "us") operates the <?= e($brand['product_name']) ?> platform, consisting of the web portal and iPad application. This Privacy Policy describes how we collect, use, and protect information in connection with your use of the platform.</p>

        <h2>2. Information We Collect</h2>
        <h3>Account Information</h3>
        <p>When your airline administrator creates your account, the following information is stored: name, email address, employee ID, department, base assignment, and assigned role.</p>
        <h3>Device Information</h3>
        <p>When you register an iPad with the platform, we collect: device model, platform, operating system version, app version, and a unique device identifier.</p>
        <h3>Usage Information</h3>
        <p>We log login activity, file downloads, sync events, and administrative actions for security, compliance, and audit purposes.</p>

        <h2>3. How We Use Information</h2>
        <ul>
            <li>To authenticate and authorize access to the platform</li>
            <li>To deliver airline-specific content to authorized users</li>
            <li>To maintain audit trails for regulatory compliance</li>
            <li>To monitor device approvals and security</li>
            <li>To improve platform functionality and reliability</li>
        </ul>

        <h2>4. Data Isolation</h2>
        <p>Each airline's data is stored in a separate tenant. There is no cross-airline data sharing. Your airline administrator controls who can access your organization's data.</p>

        <h2>5. Data Security</h2>
        <p>We implement industry-standard security measures including: password hashing (bcrypt), token-based API authentication, role-based access control, encrypted connections (HTTPS), and session security.</p>

        <h2>6. Data Retention</h2>
        <p>Account data is retained for the duration of your organization's subscription. Audit logs are retained per your airline's compliance requirements. You may request deletion of personal data through your airline administrator.</p>

        <h2>7. Third Parties</h2>
        <p>We do not sell, rent, or share personal information with third parties. Data is accessible only to authorized personnel within your airline and platform administrators.</p>

        <h2>8. Changes to This Policy</h2>
        <p>We may update this Privacy Policy from time to time. Changes will be posted on this page with an updated revision date.</p>

        <h2>9. Contact</h2>
        <p>For privacy-related questions, contact us at <a href="mailto:<?= e($brand['support_email']) ?>"><?= e($brand['support_email']) ?></a>.</p>
    </div>
</div>
