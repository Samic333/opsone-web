<?php /** OpsOne — Homepage (premium redesign) */ ?>

<!-- ============================================================
     HERO  —  Copy left, dual mockup right (iPad + dashboard)
============================================================ -->
<section class="hero">
    <div class="hero-grid-overlay"></div>
    <div class="hero-inner">
        <div class="hero-content animate-in">
            <div class="hero-badge">Aviation Operations Platform &middot; Web + iPad</div>
            <h1>
                One Platform for<br>
                Airline <span class="gradient-text">Operations</span>,<br>
                Crew, Safety &amp; iPad Workflows
            </h1>
            <p class="hero-subtitle">
                A web command dashboard for airline admins and a native iPad app for crew &mdash;
                rosters, manuals, safety reports, duty reporting, and devices, all under one
                tenant-secured platform.
            </p>
            <div class="hero-actions">
                <a href="/contact" class="pub-btn pub-btn-primary pub-btn-large">Request Demo</a>
                <a href="#platform-overview" class="pub-btn pub-btn-ghost pub-btn-large">View Platform Overview</a>
            </div>
            <p class="hero-tertiary">
                Already evaluating? <a href="/contact?type=sales">Contact sales</a>
                &nbsp;&middot;&nbsp;
                <a href="/pricing">See pricing</a>
            </p>
            <div class="hero-stats">
                <div class="hero-stat">
                    <div class="hero-stat-value">15+</div>
                    <div class="hero-stat-label">Operational Modules</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-value">16</div>
                    <div class="hero-stat-label">Crew Role Types</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-value">Real-Time</div>
                    <div class="hero-stat-label">Web &harr; iPad Sync</div>
                </div>
            </div>
        </div>

        <div class="hero-visual animate-in animate-delay-2">
            <!-- Dashboard browser frame (back layer) -->
            <div class="browser-frame browser-frame--hero">
                <div class="browser-bar">
                    <span class="browser-dot"></span>
                    <span class="browser-dot"></span>
                    <span class="browser-dot"></span>
                    <div class="browser-url">
                        <?= sidebarIcon('lock-closed', 12) ?>
                        <span>opsone.aero/dashboard</span>
                    </div>
                </div>
                <div class="dash-mock">
                    <aside class="dash-mock-side">
                        <div class="dash-mock-brand">
                            <?= opsoneLogoMark(16) ?>
                            <span>OpsOne</span>
                        </div>
                        <div class="dash-mock-grp">Main</div>
                        <div class="dash-mock-link is-active"><?= sidebarIcon('squares', 11) ?> Dashboard</div>
                        <div class="dash-mock-grp">People</div>
                        <div class="dash-mock-link"><?= sidebarIcon('users', 11) ?> Users</div>
                        <div class="dash-mock-link"><?= sidebarIcon('identification', 11) ?> Crew</div>
                        <div class="dash-mock-grp">Operations</div>
                        <div class="dash-mock-link"><?= sidebarIcon('paper-airplane', 11) ?> Flights</div>
                        <div class="dash-mock-link"><?= sidebarIcon('calendar', 11) ?> Roster</div>
                        <div class="dash-mock-grp">Safety</div>
                        <div class="dash-mock-link"><?= sidebarIcon('shield-exclamation', 11) ?> Reports</div>
                    </aside>
                    <div class="dash-mock-main">
                        <div class="dash-mock-header">
                            <div class="dash-mock-h">Airline Dashboard</div>
                            <div class="dash-mock-pill"><?= sidebarIcon('bell', 10) ?> 3</div>
                        </div>
                        <div class="dash-mock-stats">
                            <div class="dash-mock-stat" style="--c:var(--accent-green);"><div class="lbl">Active Staff</div><div class="val">128</div></div>
                            <div class="dash-mock-stat" style="--c:var(--accent-yellow);"><div class="lbl">Pending</div><div class="val">7</div></div>
                            <div class="dash-mock-stat" style="--c:var(--accent-red);"><div class="lbl">Devices</div><div class="val">3</div></div>
                            <div class="dash-mock-stat" style="--c:var(--accent-blue);"><div class="lbl">Documents</div><div class="val">412</div></div>
                        </div>
                        <div class="dash-mock-card">
                            <div class="dash-mock-card-h">Compliance Alerts <span>next 90d</span></div>
                            <div class="dash-mock-li"><span class="d red"></span><span class="lbl-a">Capt. R. Mwangi</span> &mdash; Medical <span class="lbl-b">12d</span></div>
                            <div class="dash-mock-li"><span class="d yellow"></span><span class="lbl-a">FO. T. Bekele</span> &mdash; Type rating <span class="lbl-b">28d</span></div>
                            <div class="dash-mock-li"><span class="d yellow"></span><span class="lbl-a">CC. A. Wanjiku</span> &mdash; Passport <span class="lbl-b">41d</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- iPad mockup (front layer, overlapping bottom-right) -->
            <div class="ipad-frame ipad-frame--hero">
                <div class="ipad-screen">
                    <img src="/images/screenshots/ipad-home.png"
                         alt="OpsOne CrewAssist on iPad — pilot home view"
                         loading="eager" decoding="async">
                </div>
                <div class="ipad-home-indicator"></div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     1. PLATFORM OVERVIEW  —  6 capability tiles
============================================================ -->
<section id="platform-overview" class="section section-alt">
    <div class="section-inner">
        <div class="section-header">
            <div class="section-label">Platform Overview</div>
            <h2 class="section-title">One Operating System for Airline Operations</h2>
            <p class="section-desc">
                Web dashboard for the operations side. iPad app for the crew side. Tenant-isolated
                data for every airline. Role-based access for every user. Auditable from end to end.
            </p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"><?= sidebarIcon('chart-bar', 22) ?></div>
                <h3>Web Command Dashboard</h3>
                <p>Operational view for airline admins, schedulers, HR, safety, and engineering — KPIs, compliance, devices, and documents in one place.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><?= sidebarIcon('device-tablet', 22) ?></div>
                <h3>iPad Crew App</h3>
                <p>CrewAssist on iPad for pilots and cabin crew: roster, briefing, manuals, notices, safety reports, and report-for-duty in one app.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><?= sidebarIcon('building-office', 22) ?></div>
                <h3>Airline Tenant Portal</h3>
                <p>Each airline gets its own tenant-secured portal at <code>/airline/&#123;slug&#125;/login</code>. Data, users, devices, and audit trails are fully isolated.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><?= sidebarIcon('key', 22) ?></div>
                <h3>Role-Based Dashboards</h3>
                <p>16 configurable roles from Pilot to Platform Super Admin. Each role sees only the modules and data their position requires.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><?= sidebarIcon('cloud-arrow-up', 22) ?></div>
                <h3>Device &amp; Manual Distribution</h3>
                <p>Approve every iPad before it touches your data. Push manuals, notices, and revisions to crew devices with version-tracked acknowledgement.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><?= sidebarIcon('shield-exclamation', 22) ?></div>
                <h3>Safety &amp; Reporting Workflows</h3>
                <p>Safety reports, investigations, corrective actions, FDM events, and risk tracking — wired into the crew app and the safety team's dashboard.</p>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     2. iPAD CREW APP
============================================================ -->
<section class="section">
    <div class="section-inner">
        <div class="split-row">
            <div class="split-visual">
                <div class="ipad-frame">
                    <div class="ipad-screen">
                        <img src="/images/screenshots/ipad-roster.png" alt="My Roster on iPad — monthly view" loading="lazy" decoding="async">
                    </div>
                    <div class="ipad-home-indicator"></div>
                </div>
            </div>
            <div class="split-content">
                <div class="section-label">iPad Crew App &middot; CrewAssist</div>
                <h2 class="split-title">Built for the Cockpit, Galley, and Crew Room</h2>
                <p class="split-desc">
                    Crew open one app to see their full month, today's flight, the latest manuals,
                    and any safety bulletins they need to acknowledge. The iPad is a workspace, not
                    a portal.
                </p>
                <ul class="split-list">
                    <li><?= sidebarIcon('check-badge', 16) ?><div><strong>Roster &amp; briefings</strong> &mdash; full month at a glance, with duty types color-coded</div></li>
                    <li><?= sidebarIcon('check-badge', 16) ?><div><strong>Report for duty</strong> &mdash; one-tap check-in with location and timestamp</div></li>
                    <li><?= sidebarIcon('check-badge', 16) ?><div><strong>Flight package</strong> &mdash; navigation logs, fuel, waypoints, after-mission forms</div></li>
                    <li><?= sidebarIcon('check-badge', 16) ?><div><strong>Manuals &amp; notices</strong> &mdash; latest revisions pushed to every device, acknowledgement tracked</div></li>
                    <li><?= sidebarIcon('check-badge', 16) ?><div><strong>Safety reporting</strong> &mdash; submit reports inline, with categories and severity</div></li>
                    <li><?= sidebarIcon('check-badge', 16) ?><div><strong>FDM &amp; logbook</strong> &mdash; pilot inbox for events, electronic logbook export</div></li>
                    <li><?= sidebarIcon('check-badge', 16) ?><div><strong>Offline-ready</strong> &mdash; previously synced manuals and roster work without network</div></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     3. AIRLINE OPERATIONS DASHBOARD
============================================================ -->
<section class="section section-alt">
    <div class="section-inner">
        <div class="split-row split-row--reverse">
            <div class="split-visual">
                <div class="browser-frame">
                    <div class="browser-bar">
                        <span class="browser-dot"></span>
                        <span class="browser-dot"></span>
                        <span class="browser-dot"></span>
                        <div class="browser-url">
                            <?= sidebarIcon('lock-closed', 12) ?>
                            <span>opsone.aero/dashboard</span>
                        </div>
                    </div>
                    <div class="dash-mock dash-mock--lg">
                        <aside class="dash-mock-side">
                            <div class="dash-mock-brand">
                                <?= opsoneLogoMark(16) ?>
                                <span>OpsOne</span>
                            </div>
                            <div class="dash-mock-grp">Main</div>
                            <div class="dash-mock-link is-active"><?= sidebarIcon('squares', 11) ?> Dashboard</div>
                            <div class="dash-mock-grp">People</div>
                            <div class="dash-mock-link"><?= sidebarIcon('users', 11) ?> Users</div>
                            <div class="dash-mock-link"><?= sidebarIcon('identification', 11) ?> Crew Profiles</div>
                            <div class="dash-mock-link"><?= sidebarIcon('device-tablet', 11) ?> iPad Devices <span class="dash-mock-badge">3</span></div>
                            <div class="dash-mock-link"><?= sidebarIcon('shield-check', 11) ?> Compliance</div>
                            <div class="dash-mock-grp">Operations</div>
                            <div class="dash-mock-link"><?= sidebarIcon('paper-airplane', 11) ?> Flights</div>
                            <div class="dash-mock-link"><?= sidebarIcon('calendar', 11) ?> Roster</div>
                            <div class="dash-mock-link"><?= sidebarIcon('clock', 11) ?> Duty Reporting</div>
                            <div class="dash-mock-grp">Content</div>
                            <div class="dash-mock-link"><?= sidebarIcon('folder-open', 11) ?> Documents</div>
                            <div class="dash-mock-link"><?= sidebarIcon('megaphone', 11) ?> Notices</div>
                            <div class="dash-mock-grp">Safety</div>
                            <div class="dash-mock-link"><?= sidebarIcon('chart-bar', 11) ?> Safety Dashboard</div>
                            <div class="dash-mock-link"><?= sidebarIcon('clipboard-list', 11) ?> Reports Queue</div>
                            <div class="dash-mock-link"><?= sidebarIcon('signal', 11) ?> FDM Data</div>
                        </aside>
                        <div class="dash-mock-main">
                            <div class="dash-mock-header">
                                <div class="dash-mock-h">Airline Dashboard</div>
                                <div class="dash-mock-pill"><?= sidebarIcon('bell', 10) ?> 3</div>
                            </div>
                            <div class="dash-mock-stats">
                                <div class="dash-mock-stat" style="--c:var(--accent-green);"><div class="lbl">Active Staff</div><div class="val">128</div></div>
                                <div class="dash-mock-stat" style="--c:var(--accent-yellow);"><div class="lbl">Pending Users</div><div class="val">7</div></div>
                                <div class="dash-mock-stat" style="--c:var(--accent-red);"><div class="lbl">Pending Devices</div><div class="val">3</div></div>
                                <div class="dash-mock-stat" style="--c:var(--accent-blue);"><div class="lbl">Documents</div><div class="val">412</div></div>
                            </div>
                            <div class="dash-mock-row">
                                <div class="dash-mock-card">
                                    <div class="dash-mock-card-h">Compliance Alerts <span>next 90d</span></div>
                                    <div class="dash-mock-li"><span class="d red"></span><span class="lbl-a">Capt. R. Mwangi</span> &mdash; Medical <span class="lbl-b">12d</span></div>
                                    <div class="dash-mock-li"><span class="d yellow"></span><span class="lbl-a">FO. T. Bekele</span> &mdash; Type rating <span class="lbl-b">28d</span></div>
                                    <div class="dash-mock-li"><span class="d yellow"></span><span class="lbl-a">CC. A. Wanjiku</span> &mdash; Passport <span class="lbl-b">41d</span></div>
                                </div>
                                <div class="dash-mock-card">
                                    <div class="dash-mock-card-h">Recent Activity</div>
                                    <div class="dash-mock-li"><span class="d blue"></span><span class="lbl-a">Notice published</span> &mdash; Ops Manual Rev 12</div>
                                    <div class="dash-mock-li"><span class="d green"></span><span class="lbl-a">Roster revision</span> &mdash; Approved by Chief Pilot</div>
                                    <div class="dash-mock-li"><span class="d blue"></span><span class="lbl-a">Device approved</span> &mdash; Capt. R. Mwangi (iPad Pro)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="split-content">
                <div class="section-label">Web Command Dashboard</div>
                <h2 class="split-title">Run the Airline From One Screen</h2>
                <p class="split-desc">
                    The dashboard is built around six purposeful groups &mdash; not 50 menu items.
                    Every role lands on a view shaped to their day.
                </p>
                <ul class="split-list split-list--two-col">
                    <li><?= sidebarIcon('check-badge', 16) ?><div><strong>Scheduling</strong> &mdash; flights, roster workbench, revisions, change requests</div></li>
                    <li><?= sidebarIcon('check-badge', 16) ?><div><strong>Staff &amp; roles</strong> &mdash; users, departments, bases, fleets, capabilities</div></li>
                    <li><?= sidebarIcon('check-badge', 16) ?><div><strong>Crew records</strong> &mdash; profiles, qualifications, expiries, eligibility</div></li>
                    <li><?= sidebarIcon('check-badge', 16) ?><div><strong>iPad devices</strong> &mdash; approve, revoke, audit every device that connects</div></li>
                    <li><?= sidebarIcon('check-badge', 16) ?><div><strong>Documents &amp; notices</strong> &mdash; manuals, bulletins, version history, ack reports</div></li>
                    <li><?= sidebarIcon('check-badge', 16) ?><div><strong>Audit logs</strong> &mdash; every login, upload, approval, and config change</div></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     4. SAFETY MANAGEMENT
============================================================ -->
<section class="section">
    <div class="section-inner">
        <div class="section-header">
            <div class="section-label">Safety Management</div>
            <h2 class="section-title">From Crew Submission to Closed Action</h2>
            <p class="section-desc">
                Reports submitted on the iPad land in the safety officer's queue with severity,
                category, and assignee. Investigations, corrective actions, and FDM events are
                tracked end-to-end &mdash; with publications back to crew when needed.
            </p>
        </div>
        <div class="safety-grid">
            <div class="safety-card">
                <div class="safety-icon" style="--c:var(--accent-blue);"><?= sidebarIcon('clipboard-list', 22) ?></div>
                <h3>Reports</h3>
                <p>Crew submit safety reports from iPad with category, severity, location, and attachments. Drafts saved automatically.</p>
            </div>
            <div class="safety-card">
                <div class="safety-icon" style="--c:var(--accent-yellow);"><?= sidebarIcon('shield-exclamation', 22) ?></div>
                <h3>Investigations</h3>
                <p>Safety team triages, assigns, and threads internal notes alongside crew replies. Status moves through the full investigation lifecycle.</p>
            </div>
            <div class="safety-card">
                <div class="safety-icon" style="--c:var(--accent-green);"><?= sidebarIcon('wrench', 22) ?></div>
                <h3>Corrective Actions</h3>
                <p>Open actions against root causes, assign owners, and track them to verified closure. Linked back to the originating report.</p>
            </div>
            <div class="safety-card">
                <div class="safety-icon" style="--c:var(--accent-purple);"><?= sidebarIcon('trending-up', 22) ?></div>
                <h3>FDM &amp; Risk</h3>
                <p>Flight data events flow into a pilot inbox for acknowledgement. Risk register tracks emerging issues alongside published lessons.</p>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     5. MULTI-TENANT AIRLINE MODEL
============================================================ -->
<section class="section section-alt">
    <div class="section-inner">
        <div class="section-header">
            <div class="section-label">Multi-Tenant Architecture</div>
            <h2 class="section-title">Every Airline Gets Its Own Secure Portal</h2>
            <p class="section-desc">
                OpsOne is multi-tenant by design. Platform admin onboards an airline, provisions an
                isolated tenant, and hands off full administration to the airline's own admin team.
                One platform, many airlines, zero cross-tenant data exposure.
            </p>
        </div>

        <div class="tenant-diagram">
            <div class="tenant-platform">
                <div class="tenant-platform-icon"><?= sidebarIcon('squares', 22) ?></div>
                <div>
                    <div class="tenant-platform-title">OpsOne Platform</div>
                    <div class="tenant-platform-sub">Onboarding &middot; Module Catalog &middot; Audit</div>
                </div>
            </div>
            <div class="tenant-connectors" aria-hidden="true">
                <span class="tenant-line"></span>
                <span class="tenant-line"></span>
                <span class="tenant-line"></span>
            </div>
            <div class="tenant-airlines">
                <div class="tenant-airline">
                    <div class="tenant-airline-header">
                        <?= sidebarIcon('paper-airplane', 16) ?>
                        <span>Airline A</span>
                    </div>
                    <code>/airline/airline-a/login</code>
                    <ul>
                        <li>Own users, roles, devices</li>
                        <li>Own roster &amp; manuals</li>
                        <li>Own admin team</li>
                    </ul>
                </div>
                <div class="tenant-airline tenant-airline--featured">
                    <div class="tenant-airline-header">
                        <?= sidebarIcon('paper-airplane', 16) ?>
                        <span>Airline B</span>
                    </div>
                    <code>/airline/airline-b/login</code>
                    <ul>
                        <li>Own users, roles, devices</li>
                        <li>Own roster &amp; manuals</li>
                        <li>Own admin team</li>
                    </ul>
                </div>
                <div class="tenant-airline">
                    <div class="tenant-airline-header">
                        <?= sidebarIcon('paper-airplane', 16) ?>
                        <span>Airline C</span>
                    </div>
                    <code>/airline/airline-c/login</code>
                    <ul>
                        <li>Own users, roles, devices</li>
                        <li>Own roster &amp; manuals</li>
                        <li>Own admin team</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="tenant-points">
            <div class="tenant-point">
                <div class="tenant-point-icon"><?= sidebarIcon('shield-check', 18) ?></div>
                <div>
                    <h4>Strict tenant isolation</h4>
                    <p>Every table is scoped by tenant. No cross-airline data is ever queryable, even by accident.</p>
                </div>
            </div>
            <div class="tenant-point">
                <div class="tenant-point-icon"><?= sidebarIcon('rocket-launch', 18) ?></div>
                <div>
                    <h4>Platform-driven onboarding</h4>
                    <p>Platform super admin provisions the tenant, enables modules, and creates the airline's first admin account.</p>
                </div>
            </div>
            <div class="tenant-point">
                <div class="tenant-point-icon"><?= sidebarIcon('key', 18) ?></div>
                <div>
                    <h4>Tenant-scoped login URL</h4>
                    <p>Each airline can use its own branded login at <code>/airline/&#123;slug&#125;/login</code> alongside the global <code>/login</code>.</p>
                </div>
            </div>
            <div class="tenant-point">
                <div class="tenant-point-icon"><?= sidebarIcon('lock-closed', 18) ?></div>
                <div>
                    <h4>Full audit trail</h4>
                    <p>Logins, uploads, approvals, and config changes are all logged per-tenant with user, timestamp, and IP.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     6. REQUEST DEMO  —  Final CTA
============================================================ -->
<section class="cta-section">
    <div class="cta-inner">
        <div class="section-label">Get Started</div>
        <h2 class="section-title">Ready to See OpsOne With Your Airline's Data?</h2>
        <p class="section-desc" style="margin: 0 auto;">
            Tell us about your operation. We'll book a 30-minute demo with your team and walk
            through the platform with workflows shaped to your fleet, your bases, and your roles.
        </p>
        <div class="cta-actions">
            <a href="/contact" class="pub-btn pub-btn-primary pub-btn-large">Request Demo</a>
            <a href="/request-assessment" class="pub-btn pub-btn-ghost pub-btn-large">Request Assessment</a>
        </div>
        <p class="cta-fineprint">
            Already a client? <a href="/login">Sign in</a> &middot;
            New airline? <a href="/contact?type=onboarding">Get onboarded</a>
        </p>
    </div>
</section>
