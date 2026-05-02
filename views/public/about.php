<?php /** OpsVelo — About */ ?>
<div class="info-page">
    <div class="info-page-inner">
        <div class="section-label">✦ About</div>
        <h1>About <?= e($brand['product_name']) ?></h1>
        <p class="lead"><?= e($brand['product_name']) ?> is an airline operations platform purpose-built for aviation professionals — combining crew workflow management, compliance tracking, document distribution, and safety tools in a single connected system.</p>

        <h2>Our Mission</h2>
        <p>Airline crews deserve modern, reliable tools that work as hard as they do. <?= e($brand['product_name']) ?> replaces fragmented manual processes — paper rosters, email document chains, compliance spreadsheets — with a unified digital platform that pilots, cabin crew, engineers, and administrators can rely on, including offline.</p>

        <h2>What We Offer</h2>
        <p><?= e($brand['product_name']) ?> delivers a full aviation operations stack:</p>
        <ul>
            <li>An iPad-native crew app (CrewAssist) with 15+ operational modules</li>
            <li>A web-based admin portal for airline administrators and operations managers</li>
            <li>Crew compliance and licence tracking with automated expiry alerts</li>
            <li>Secure document distribution with automatic offline syncing</li>
            <li>Safety management — reports, FDM data, analytics, and audit trail</li>
            <li>Multi-airline architecture supporting fleet operators and charter operators</li>
        </ul>

        <h2>Who It Is For</h2>
        <p>We work with commercial operators, charter airlines, and cargo carriers. The platform is designed for airlines with 10 to 500+ crew members who need structured operations management without the overhead of legacy enterprise software.</p>

        <h2>Enterprise Deployment</h2>
        <p><?= e($brand['product_name']) ?> is deployed as a private enterprise installation for each airline — giving operators complete control over their data, users, and configuration. Crew access the app via a company-controlled deployment, and all data remains within your airline's tenant environment.</p>

        <h2>Technology</h2>
        <ul>
            <li><strong>iPad App (CrewAssist):</strong> Native Swift/SwiftUI, offline-first with background sync</li>
            <li><strong>Web Portal:</strong> PHP 8.x with MySQL, RESTful API, role-based access control</li>
            <li><strong>Security:</strong> bcrypt authentication, token-based API auth, tenant isolation, full audit logging</li>
        </ul>

        <div class="info-card">
            <h3>Request a Demo</h3>
            <p>Ready to see <?= e($brand['product_name']) ?> in action? <a href="/contact">Contact us</a> to arrange a demo or discuss your airline's requirements.</p>
        </div>
    </div>
</div>
