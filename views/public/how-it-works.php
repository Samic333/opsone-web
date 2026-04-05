<?php /** OpsOne — How It Works */ ?>
<div class="info-page">
    <div class="info-page-inner">
        <div class="section-label">✦ How It Works</div>
        <h1>How <?= e($brand['product_name']) ?> Works</h1>
        <p class="lead">From airline onboarding to daily crew operations — here's the complete workflow.</p>
    </div>
</div>

<section class="section">
    <div class="section-inner">
        <div class="section-header">
            <h2 class="section-title">Airline Onboarding</h2>
            <p class="section-desc">Setting up your airline takes minutes, not months.</p>
        </div>
        <div class="steps-grid">
            <div class="step-card">
                <h3>Airline Created</h3>
                <p>Platform administrator creates your airline tenant with company name, code, and contact details. Your data is fully isolated from other airlines.</p>
            </div>
            <div class="step-card">
                <h3>Roles & Departments</h3>
                <p>Configure departments (Flight Ops, Cabin, Engineering, etc.) and assign roles to match your organization structure.</p>
            </div>
            <div class="step-card">
                <h3>Users Added</h3>
                <p>Create user accounts for your crew, assign roles, and enable mobile access. Users receive credentials for app login.</p>
            </div>
            <div class="step-card">
                <h3>Content Uploaded</h3>
                <p>Upload manuals, SOPs, training materials, and operational documents. Organize by category and control visibility by role.</p>
            </div>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="section-inner">
        <div class="section-header">
            <h2 class="section-title">Daily Operations</h2>
            <p class="section-desc">What happens every day for pilots and crew.</p>
        </div>
        <div class="steps-grid">
            <div class="step-card">
                <h3>Open App</h3>
                <p>Crew member opens <?= e($brand['product_name']) ?> on their iPad. The app syncs with the server to fetch the latest content and schedule updates.</p>
            </div>
            <div class="step-card">
                <h3>Check Dashboard</h3>
                <p>The home dashboard shows today's duties, upcoming flights, unread notices, and pending acknowledgements. Everything at a glance.</p>
            </div>
            <div class="step-card">
                <h3>Report for Duty</h3>
                <p>Tap "Report" to log duty start. Clock in at the airport with location stamping. Clock out when the duty ends.</p>
            </div>
            <div class="step-card">
                <h3>Access Flight Package</h3>
                <p>Open the flight folder for your assigned flight. View the navigation log, fuel data, waypoints, and complete after-mission reports.</p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="section-inner">
        <div class="section-header">
            <h2 class="section-title">Admin Content Flow</h2>
            <p class="section-desc">How content moves from administrators to crew iPads.</p>
        </div>
        <div class="steps-grid">
            <div class="step-card">
                <h3>Admin Uploads</h3>
                <p>Airline admin logs into the web portal and uploads a new manual revision, notice, or briefing document.</p>
            </div>
            <div class="step-card">
                <h3>Categorize & Publish</h3>
                <p>The document is tagged with a category, version number, and role visibility. Admin clicks "Publish" to make it available.</p>
            </div>
            <div class="step-card">
                <h3>Sync Triggered</h3>
                <p>When crew members open the app or perform a manual sync, the app checks the sync manifest and downloads new/updated content.</p>
            </div>
            <div class="step-card">
                <h3>Offline Ready</h3>
                <p>Downloaded files are stored locally. Crew can access them during flights, even without internet. Last sync time is always visible.</p>
            </div>
        </div>
    </div>
</section>
