<?php /** OpsOne — Pricing */ ?>
<div class="info-page">
    <div class="info-page-inner" style="max-width: 1100px;">
        <div class="section-label">Pricing</div>
        <h1>Pricing That Tracks Your Operation</h1>
        <p class="lead">
            <?= e($brand['product_name']) ?> is sold per-airline with a per-seat component for active crew. Onboarding,
            data migration, and admin training are bundled into the launch fee. We publish the structure here — the
            specific numbers depend on fleet size, modules enabled, and support tier.
        </p>

        <div class="pricing-grid">
            <div class="tier-card">
                <div class="tier-name">Starter</div>
                <div class="tier-price">Contact us</div>
                <div class="tier-price-note">Single base, single fleet type</div>
                <p class="tier-desc">For new operators or single-fleet airlines getting their first digital ops platform off the ground.</p>
                <ul class="tier-features">
                    <li><?= sidebarIcon('check-badge', 16) ?>Up to 50 active crew seats</li>
                    <li><?= sidebarIcon('check-badge', 16) ?>Roster, Flights, Manuals, Notices</li>
                    <li><?= sidebarIcon('check-badge', 16) ?>Crew Reporting &amp; Duty</li>
                    <li><?= sidebarIcon('check-badge', 16) ?>Basic Safety Reports</li>
                    <li><?= sidebarIcon('check-badge', 16) ?>Email support, 1 business day SLA</li>
                </ul>
                <a href="/contact" class="pub-btn pub-btn-ghost tier-cta">Get a Quote</a>
            </div>

            <div class="tier-card tier-featured">
                <div class="tier-name">Operational</div>
                <div class="tier-price">Contact us</div>
                <div class="tier-price-note">Multi-base, multi-fleet</div>
                <p class="tier-desc">For airlines running daily commercial or charter operations who need the full crew &amp; safety stack.</p>
                <ul class="tier-features">
                    <li><?= sidebarIcon('check-badge', 16) ?>Unlimited active crew seats</li>
                    <li><?= sidebarIcon('check-badge', 16) ?>All Starter modules</li>
                    <li><?= sidebarIcon('check-badge', 16) ?>FDM, Compliance, Personnel Records</li>
                    <li><?= sidebarIcon('check-badge', 16) ?>Per Diem, Training, Appraisals</li>
                    <li><?= sidebarIcon('check-badge', 16) ?>Roster Workbench &amp; Revisions</li>
                    <li><?= sidebarIcon('check-badge', 16) ?>Priority support, 4 hour SLA</li>
                </ul>
                <a href="/contact" class="pub-btn pub-btn-primary tier-cta">Get a Quote</a>
            </div>

            <div class="tier-card">
                <div class="tier-name">Enterprise</div>
                <div class="tier-price">Custom</div>
                <div class="tier-price-note">Group / holding company</div>
                <p class="tier-desc">For platform operators running multiple airline tenants, with custom integrations and white-glove onboarding.</p>
                <ul class="tier-features">
                    <li><?= sidebarIcon('check-badge', 16) ?>Multi-tenant platform admin</li>
                    <li><?= sidebarIcon('check-badge', 16) ?>All Operational features</li>
                    <li><?= sidebarIcon('check-badge', 16) ?>Custom integrations (HR, payroll, FDM vendor)</li>
                    <li><?= sidebarIcon('check-badge', 16) ?>SSO &amp; advanced audit exports</li>
                    <li><?= sidebarIcon('check-badge', 16) ?>Dedicated success manager</li>
                    <li><?= sidebarIcon('check-badge', 16) ?>1 hour critical SLA, 24/7 on-call</li>
                </ul>
                <a href="/contact" class="pub-btn pub-btn-ghost tier-cta">Talk to Sales</a>
            </div>
        </div>

        <div class="info-card" style="margin-top: 64px;">
            <h3>What's included in every plan</h3>
            <p style="color: var(--text-secondary); margin-bottom: 16px;">
                Regardless of tier, every airline gets:
            </p>
            <ul style="list-style: none; padding: 0; display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px;">
                <li style="display:flex;gap:10px;align-items:flex-start;color:var(--text-secondary);font-size:14px;"><?= sidebarIcon('shield-check', 16) ?>Tenant data isolation</li>
                <li style="display:flex;gap:10px;align-items:flex-start;color:var(--text-secondary);font-size:14px;"><?= sidebarIcon('shield-check', 16) ?>Enterprise iPad deployment</li>
                <li style="display:flex;gap:10px;align-items:flex-start;color:var(--text-secondary);font-size:14px;"><?= sidebarIcon('shield-check', 16) ?>Encrypted backups &amp; audit trail</li>
                <li style="display:flex;gap:10px;align-items:flex-start;color:var(--text-secondary);font-size:14px;"><?= sidebarIcon('shield-check', 16) ?>Free admin training (web sessions)</li>
                <li style="display:flex;gap:10px;align-items:flex-start;color:var(--text-secondary);font-size:14px;"><?= sidebarIcon('shield-check', 16) ?>iPad app updates included</li>
                <li style="display:flex;gap:10px;align-items:flex-start;color:var(--text-secondary);font-size:14px;"><?= sidebarIcon('shield-check', 16) ?>No per-document fees</li>
            </ul>
        </div>

        <div style="text-align: center; margin-top: 64px;">
            <h2 style="font-size: 28px; font-weight: 800; margin-bottom: 12px;">Not sure which plan fits?</h2>
            <p style="color: var(--text-secondary); margin-bottom: 24px;">
                Start with an operational assessment — we'll review your fleet, crew base, and current tools, then recommend the right tier and modules.
            </p>
            <a href="/request-assessment" class="pub-btn pub-btn-primary pub-btn-large">Request an Assessment</a>
        </div>
    </div>
</div>
