<?php /** OpsOne — Features (high-level only; details shared under NDA in demo) */ ?>
<div class="info-page">
    <div class="info-page-inner" style="max-width: 900px;">
        <div class="section-label">Features</div>
        <h1>Built for Airline Operations and Crew</h1>
        <p class="lead">
            <?= e($brand['product_name']) ?> brings together the everyday tools an airline runs on —
            crew, flights, manuals, safety, devices — in one secure platform. Specifics are shared
            with you in a demo against your own workflows.
        </p>
    </div>
</div>

<section class="section">
    <div class="section-inner">
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"><?= sidebarIcon('clipboard-list', 22) ?></div>
                <h3>Crew Reporting &amp; Duty</h3>
                <p>Crew check in, clock out, and submit duty events from iPad. Records flow straight to your operations team.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><?= sidebarIcon('calendar', 22) ?></div>
                <h3>Roster &amp; Schedule</h3>
                <p>Crew see their full month at a glance with duty types color-coded. Operations builds and revises rosters in the web dashboard.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><?= sidebarIcon('paper-airplane', 22) ?></div>
                <h3>Flight Operations</h3>
                <p>Crew receive flight-day documents, complete required forms in-app, and submit them back to ops.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><?= sidebarIcon('book-open', 22) ?></div>
                <h3>Manuals &amp; Documents</h3>
                <p>Centralised manuals, notices, and reference documents. Pushed to crew devices with version-tracked acknowledgement.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><?= sidebarIcon('shield-exclamation', 22) ?></div>
                <h3>Safety Reporting</h3>
                <p>Crew submit reports from iPad. Safety team triages, investigates, and publishes lessons back to the line.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><?= sidebarIcon('cloud-arrow-up', 22) ?></div>
                <h3>Device &amp; Sync Control</h3>
                <p>Approve every iPad before it touches your data. Content distribution and device status are managed from the dashboard.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><?= sidebarIcon('users', 22) ?></div>
                <h3>People Management</h3>
                <p>Profiles, qualifications, expiries, training, per diem, and appraisals — role-aware and audit-logged.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><?= sidebarIcon('lock-closed', 22) ?></div>
                <h3>Audit &amp; Compliance</h3>
                <p>Every login, upload, approval, and configuration change is logged with timestamp, user, and IP for regulatory review.</p>
            </div>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="cta-inner">
        <div class="section-label">See It With Your Data</div>
        <h2 class="section-title">Request a Demo</h2>
        <p class="section-desc" style="margin: 0 auto;">
            We share the full feature set, integrations, and screens privately with airlines we're
            in conversation with. Tell us about your operation and we'll book a 30-minute walkthrough.
        </p>
        <div class="cta-actions">
            <a href="/contact" class="pub-btn pub-btn-primary pub-btn-large">Request Demo</a>
            <a href="/contact?type=sales" class="pub-btn pub-btn-ghost pub-btn-large">Contact Sales</a>
        </div>
    </div>
</section>
