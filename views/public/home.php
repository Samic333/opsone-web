<?php /** OpsOne — Homepage */ ?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-grid-overlay"></div>
    <div class="hero-inner">
        <div class="hero-content animate-in">
            <div class="hero-badge">Internal Platform · Enterprise Use Only</div>
            <h1>
                <span class="gradient-text"><?= e($brand['product_name']) ?></span><br>
                Airline Operations<br>
                & Crew System
            </h1>
            <p class="hero-subtitle">
                A secure, iPad-first internal platform for airline operations, crew management,
                document distribution, and real-time synchronization. Built for authorized airline personnel only.
            </p>
            <div class="hero-actions">
                <a href="/install" class="pub-btn pub-btn-primary pub-btn-large">Install <?= e($brand['product_name']) ?></a>
                <a href="/login" class="pub-btn pub-btn-ghost pub-btn-large">Airline Login</a>
                <a href="/contact" class="pub-btn pub-btn-outline pub-btn-large">Request Demo</a>
            </div>
            <div class="hero-stats">
                <div class="hero-stat">
                    <div class="hero-stat-value">15+</div>
                    <div class="hero-stat-label">App Modules</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-value">Multi-Tenant</div>
                    <div class="hero-stat-label">Airline Isolation</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-value">Real-Time</div>
                    <div class="hero-stat-label">Content Sync</div>
                </div>
            </div>
        </div>

        <div class="hero-visual animate-in animate-delay-2">
            <div class="hero-device">
                <div class="hero-screen">
                    <div class="hero-screen-header">
                        <div class="hero-screen-dot" style="background: #ef4444;"></div>
                        <div class="hero-screen-dot" style="background: #f59e0b;"></div>
                        <div class="hero-screen-dot" style="background: #10b981;"></div>
                        <div class="hero-screen-title"><?= e($brand['product_name']) ?> — Operations Hub</div>
                    </div>
                    <div class="hero-screen-modules">
                        <div class="hero-module"><div class="hero-module-icon">🏠</div><div class="hero-module-label">Home</div></div>
                        <div class="hero-module"><div class="hero-module-icon">📅</div><div class="hero-module-label">Roster</div></div>
                        <div class="hero-module"><div class="hero-module-icon">✈️</div><div class="hero-module-label">Flights</div></div>
                        <div class="hero-module"><div class="hero-module-icon">📚</div><div class="hero-module-label">Manuals</div></div>
                        <div class="hero-module"><div class="hero-module-icon">📋</div><div class="hero-module-label">Briefing</div></div>
                        <div class="hero-module"><div class="hero-module-icon">🔔</div><div class="hero-module-label">Notices</div></div>
                        <div class="hero-module"><div class="hero-module-icon">⚠️</div><div class="hero-module-label">Safety</div></div>
                        <div class="hero-module"><div class="hero-module-icon">🔄</div><div class="hero-module-label">Sync</div></div>
                        <div class="hero-module"><div class="hero-module-icon">👤</div><div class="hero-module-label">Profile</div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Product Overview -->
<section class="section section-alt">
    <div class="section-inner">
        <div class="section-header">
            <div class="section-label">✦ Product Overview</div>
            <h2 class="section-title">Everything Your Airline Crew Needs in One App</h2>
            <p class="section-desc">
                <?= e($brand['product_name']) ?> brings together operational tools, document management,
                crew reporting, and communication into a unified iPad-first platform designed for professional airline environments.
            </p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📋</div>
                <h3>Crew Reporting</h3>
                <p>Report for duty, clock in and out with location tracking. Complete duty logs with time-stamped events for compliance and payroll accuracy.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📅</div>
                <h3>Roster Management</h3>
                <p>View monthly flight schedules, duty assignments, training sessions, and leave. Calendar-based interface with duty type color coding.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">✈️</div>
                <h3>Flight Package</h3>
                <p>Access navigation logs, flight details, fuel data, waypoint tracking, and after-mission reports. Complete flight folder for every assignment.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📚</div>
                <h3>Document Library</h3>
                <p>Access company manuals, safety bulletins, training materials, and operational documents. Download for offline access with version tracking.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⚠️</div>
                <h3>Safety Reports</h3>
                <p>Submit and track safety and incident reports. Categorized by type and severity with full investigation workflow support.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔄</div>
                <h3>Automatic Sync</h3>
                <p>Content uploaded by your airline administrator automatically syncs to your iPad. Stay current with the latest manuals, notices, and briefings.</p>
            </div>
        </div>
    </div>
</section>

<!-- iPad Usage Section -->
<section class="section">
    <div class="section-inner">
        <div class="section-header">
            <div class="section-label">✦ iPad-First Design</div>
            <h2 class="section-title">Built From the Ground Up for iPad</h2>
            <p class="section-desc">
                <?= e($brand['product_name']) ?> uses iPad-optimized layouts with a professional sidebar navigation,
                large touch targets, and an operational design language that works in cockpits, galleys, and crew rooms.
            </p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h3>Adaptive Layout</h3>
                <p>Sidebar navigation on iPad with tab-based navigation on iPhone. Designed for multitasking and Split View support.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🌙</div>
                <h3>Dark Mode Optimized</h3>
                <p>Professional dark interface designed for low-light environments including cockpits and night operations. Reduces eye strain during extended use.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📥</div>
                <h3>Offline Access</h3>
                <p>Previously synced documents and manuals are stored locally. Access critical materials even without network connectivity.</p>
            </div>
        </div>
    </div>
</section>

<!-- Admin Benefits -->
<section class="section section-alt">
    <div class="section-inner">
        <div class="section-header">
            <div class="section-label">✦ Airline Administration</div>
            <h2 class="section-title">Powerful Admin Portal for Airline Managers</h2>
            <p class="section-desc">
                Airline administrators manage users, upload documents, publish notices, control device access,
                and monitor sync status through a dedicated web portal.
            </p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">👥</div>
                <h3>User Management</h3>
                <p>Create accounts, assign roles, manage departments. Control who has access to which modules and documents.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📄</div>
                <h3>Document Uploads</h3>
                <p>Upload manuals, notices, briefings, and safety bulletins. Organize by category, set visibility by role, and track versions.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h3>Device Approval</h3>
                <p>Approve or revoke iPad devices that connect to your airline's content. Full audit trail of device registrations.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🏢</div>
                <h3>Multi-Tenant Isolation</h3>
                <p>Each airline's data is completely isolated. No cross-airline data leakage. Perfect for platform operators managing multiple airlines.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Sync Monitoring</h3>
                <p>See which users have synced, when they last connected, and what content they have. Ensure compliance with document distribution.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📝</div>
                <h3>Audit Logs</h3>
                <p>Every action is logged — uploads, downloads, logins, device approvals. Full audit trail for regulatory compliance.</p>
            </div>
        </div>
    </div>
</section>

<!-- How Sync Works -->
<section class="section">
    <div class="section-inner">
        <div class="section-header">
            <div class="section-label">✦ How Sync Works</div>
            <h2 class="section-title">Upload Once, Available Everywhere</h2>
            <p class="section-desc">
                Content flows securely from your admin portal to every authorized iPad in your fleet.
            </p>
        </div>
        <div class="steps-grid">
            <div class="step-card">
                <h3>Admin Uploads</h3>
                <p>Airline admin uploads a document, notice, or briefing through the web portal. Tags it by category and visibility.</p>
            </div>
            <div class="step-card">
                <h3>Content Published</h3>
                <p>Admin publishes the content. It becomes available in the sync manifest with version and metadata information.</p>
            </div>
            <div class="step-card">
                <h3>App Syncs</h3>
                <p>iPad app checks for new content. Downloads only what's new or updated based on the user's role and permissions.</p>
            </div>
            <div class="step-card">
                <h3>Available Offline</h3>
                <p>Downloaded content is stored locally on the iPad. Crew can access manuals and documents without internet connection.</p>
            </div>
        </div>
    </div>
</section>

<!-- Security -->
<section class="section section-alt">
    <div class="section-inner">
        <div class="section-header">
            <div class="section-label">✦ Security & Compliance</div>
            <h2 class="section-title">Enterprise-Grade Security for Internal Use</h2>
            <p class="section-desc">
                <?= e($brand['product_name']) ?> is designed for internal airline use with strict access controls
                and no public exposure of sensitive operational data.
            </p>
        </div>
        <div class="security-grid">
            <div class="security-item">
                <div class="security-icon">🔐</div>
                <div>
                    <h4>Role-Based Access</h4>
                    <p>15+ configurable roles from Pilot to Super Admin. Each role sees only authorized modules and content.</p>
                </div>
            </div>
            <div class="security-item">
                <div class="security-icon">🏢</div>
                <div>
                    <h4>Tenant Isolation</h4>
                    <p>Complete data separation between airlines. No shared content unless explicitly configured as global.</p>
                </div>
            </div>
            <div class="security-item">
                <div class="security-icon">📱</div>
                <div>
                    <h4>Device Approval</h4>
                    <p>Every iPad must be approved before accessing content. Administrators can revoke access at any time.</p>
                </div>
            </div>
            <div class="security-item">
                <div class="security-icon">📋</div>
                <div>
                    <h4>Full Audit Trail</h4>
                    <p>Every login, upload, download, and sync event is logged with timestamps, user identity, and IP addresses.</p>
                </div>
            </div>
            <div class="security-item">
                <div class="security-icon">🔒</div>
                <div>
                    <h4>Protected File Delivery</h4>
                    <p>Documents are never publicly accessible. All downloads require authentication and authorization checks.</p>
                </div>
            </div>
            <div class="security-item">
                <div class="security-icon">🛡️</div>
                <div>
                    <h4>Internal Distribution</h4>
                    <p>Enterprise-style deployment via secure website installation. Not distributed through public app stores.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modules Overview -->
<section class="section">
    <div class="section-inner">
        <div class="section-header">
            <div class="section-label">✦ App Modules</div>
            <h2 class="section-title">Comprehensive Operations Suite</h2>
            <p class="section-desc">
                Every module is role-aware. Users see only what their position requires.
            </p>
        </div>
        <div class="modules-grid">
            <div class="module-card"><div class="module-icon">🏠</div><div class="module-name">Home</div><div class="module-desc">Dashboard overview</div></div>
            <div class="module-card"><div class="module-icon">⏰</div><div class="module-name">Reporting</div><div class="module-desc">Duty clock-in/out</div></div>
            <div class="module-card"><div class="module-icon">📅</div><div class="module-name">Roster</div><div class="module-desc">Flight schedule</div></div>
            <div class="module-card"><div class="module-icon">✈️</div><div class="module-name">Flights</div><div class="module-desc">Flight packages</div></div>
            <div class="module-card"><div class="module-icon">📚</div><div class="module-name">Manuals</div><div class="module-desc">Document library</div></div>
            <div class="module-card"><div class="module-icon">📝</div><div class="module-name">Logbook</div><div class="module-desc">Flight history</div></div>
            <div class="module-card"><div class="module-icon">⚠️</div><div class="module-name">Safety</div><div class="module-desc">Incident reports</div></div>
            <div class="module-card"><div class="module-icon">🔔</div><div class="module-name">Notices</div><div class="module-desc">Bulletins & alerts</div></div>
            <div class="module-card"><div class="module-icon">📋</div><div class="module-name">Briefing</div><div class="module-desc">Operational briefs</div></div>
            <div class="module-card"><div class="module-icon">📜</div><div class="module-name">Licenses</div><div class="module-desc">Expiry tracking</div></div>
            <div class="module-card"><div class="module-icon">📈</div><div class="module-name">FDM</div><div class="module-desc">Flight data monitor</div></div>
            <div class="module-card"><div class="module-icon">🔄</div><div class="module-name">Sync Center</div><div class="module-desc">Content sync status</div></div>
            <div class="module-card"><div class="module-icon">👤</div><div class="module-name">Profile</div><div class="module-desc">Personal details</div></div>
            <div class="module-card"><div class="module-icon">✅</div><div class="module-name">Ack's</div><div class="module-desc">Document sign-offs</div></div>
            <div class="module-card"><div class="module-icon">⚙️</div><div class="module-name">Settings</div><div class="module-desc">App configuration</div></div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="cta-inner">
        <div class="section-label">✦ Get Started</div>
        <h2 class="section-title">Ready to Deploy <?= e($brand['product_name']) ?>?</h2>
        <p class="section-desc" style="margin: 0 auto;">
            Contact us to set up your airline on the platform, or log in if your organization already has access.
        </p>
        <div class="cta-actions">
            <a href="/install" class="pub-btn pub-btn-primary pub-btn-large">Install <?= e($brand['product_name']) ?></a>
            <a href="/login" class="pub-btn pub-btn-ghost pub-btn-large">Airline Login</a>
            <a href="/login" class="pub-btn pub-btn-outline pub-btn-large">Admin Login</a>
            <a href="/contact" class="pub-btn pub-btn-outline pub-btn-large">Request Demo</a>
            <a href="/support" class="pub-btn pub-btn-outline pub-btn-large">Contact Support</a>
        </div>
    </div>
</section>
