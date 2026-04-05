<?php /** OpsOne — About */ ?>
<div class="info-page">
    <div class="info-page-inner">
        <div class="section-label">✦ About</div>
        <h1>About <?= e($brand['product_name']) ?></h1>
        <p class="lead"><?= e($brand['product_name']) ?> is an internal airline operations platform built to streamline crew workflows, document distribution, and operational communication for aviation professionals.</p>

        <h2>Our Mission</h2>
        <p>We believe airline crews deserve modern, reliable, and secure tools that work as hard as they do. <?= e($brand['product_name']) ?> was built to replace fragmented manual processes with a unified digital platform that pilots, cabin crew, engineers, and administrators can rely on — even offline, even in the air.</p>

        <h2>What We Do</h2>
        <p><?= e($brand['product_name']) ?> provides:</p>
        <ul>
            <li>An iPad-first app for airline crew with 15+ operational modules</li>
            <li>A web-based admin portal for airline administrators</li>
            <li>Secure document distribution with automatic syncing</li>
            <li>Multi-tenant architecture for platform operators managing multiple airlines</li>
            <li>Enterprise-grade security with role-based access, device approval, and full audit logging</li>
        </ul>

        <h2>Internal Distribution</h2>
        <p><?= e($brand['product_name']) ?> is designed for internal airline use and is distributed through a secure, company-controlled website installation — not through public app stores. This ensures that only authorized personnel within your airline can access the application and its data.</p>

        <h2>Technology</h2>
        <p>The platform is built with:</p>
        <ul>
            <li><strong>iPad App:</strong> Native Swift/SwiftUI with offline-first architecture</li>
            <li><strong>Web Portal:</strong> PHP 8.x with MySQL/SQLite, RESTful API</li>
            <li><strong>Security:</strong> bcrypt authentication, token-based API auth, tenant isolation, RBAC</li>
        </ul>

        <div class="info-card">
            <h3>Built by <?= e($brand['company_name']) ?></h3>
            <p>For questions, demo requests, or partnership inquiries, please <a href="/contact">contact us</a>.</p>
        </div>
    </div>
</div>
