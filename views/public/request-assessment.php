<?php /** OpsOne — Request Assessment */ ?>
<div class="info-page">
    <div class="info-page-inner">
        <div class="section-label">Assessment</div>
        <h1>Request an Operational Assessment</h1>
        <p class="lead">
            Tell us about your operation and we'll come back with a deployment plan: which modules to enable first,
            data-migration approach, training schedule, and a fixed price for your first year.
        </p>

        <div class="contact-grid" style="margin-top: 40px;">
            <div class="contact-form">
                <div class="info-card">
                    <h3>Operational Snapshot</h3>
                    <?php if (!empty($flashMsg)): ?>
                        <div style="margin-bottom: 16px; padding: 12px 14px; border-radius: 6px; <?= ($flashType ?? '') === 'success' ? 'background:#0f3d2b;color:#7ee8b4;border:1px solid #2a7f5a;' : 'background:#3d1313;color:#ffb0b0;border:1px solid #8a2a2a;' ?>">
                            <?= e($flashMsg) ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="/request-assessment">
                        <?= csrfField() ?>
                        <div class="form-group">
                            <label for="ra-name">Full Name</label>
                            <input type="text" id="ra-name" name="name" placeholder="Your name" required>
                        </div>
                        <div class="form-group">
                            <label for="ra-email">Work Email</label>
                            <input type="email" id="ra-email" name="email" placeholder="you@yourairline.com" required>
                        </div>
                        <div class="form-group">
                            <label for="ra-airline">Airline / Operator</label>
                            <input type="text" id="ra-airline" name="airline" placeholder="e.g. 748 Air Services" required>
                        </div>
                        <div class="form-group">
                            <label for="ra-role">Your Role</label>
                            <select id="ra-role" name="role">
                                <option value="">Select your role</option>
                                <option value="ceo">CEO / Accountable Manager</option>
                                <option value="dfo">DFO / Director of Flight Ops</option>
                                <option value="chief_pilot">Chief Pilot</option>
                                <option value="head_cabin_crew">Head of Cabin Crew</option>
                                <option value="safety_manager">Safety Manager</option>
                                <option value="hr_director">HR Director</option>
                                <option value="ops_manager">Ops / Crew Control Manager</option>
                                <option value="it">IT / Digital</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="ra-fleet">Fleet Size</label>
                            <select id="ra-fleet" name="fleet_size">
                                <option value="">Select fleet size</option>
                                <option value="1-3">1–3 aircraft</option>
                                <option value="4-10">4–10 aircraft</option>
                                <option value="11-25">11–25 aircraft</option>
                                <option value="26-50">26–50 aircraft</option>
                                <option value="50+">50+ aircraft</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="ra-crew">Active Crew (pilots + cabin)</label>
                            <select id="ra-crew" name="crew_size">
                                <option value="">Select crew size</option>
                                <option value="1-25">Up to 25</option>
                                <option value="26-100">26–100</option>
                                <option value="101-300">101–300</option>
                                <option value="301-1000">301–1,000</option>
                                <option value="1000+">1,000+</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="ra-tools">Current Tools (paper, Excel, vendor)</label>
                            <input type="text" id="ra-tools" name="current_tools" placeholder="e.g. Paper rosters + WhatsApp + AIMS">
                        </div>
                        <div class="form-group">
                            <label for="ra-pain">Biggest Operational Pain Point</label>
                            <textarea id="ra-pain" name="pain_point" placeholder="What's costing you the most time, money, or compliance risk today?"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="ra-golive">Target Go-Live</label>
                            <select id="ra-golive" name="target_go_live">
                                <option value="">Select target</option>
                                <option value="asap">As soon as possible</option>
                                <option value="1-3m">1–3 months</option>
                                <option value="3-6m">3–6 months</option>
                                <option value="6-12m">6–12 months</option>
                                <option value="exploring">Just exploring</option>
                            </select>
                        </div>
                        <button type="submit" class="pub-btn pub-btn-primary pub-btn-large" style="width: 100%;">Send Assessment Request</button>
                    </form>
                </div>
            </div>
            <div>
                <h3 style="margin-bottom: 24px;">What You'll Get Back</h3>
                <div class="contact-info-item">
                    <div class="contact-info-icon" style="color: var(--accent-blue);"><?= sidebarIcon('clipboard-list', 18) ?></div>
                    <div>
                        <h4>Module Recommendation</h4>
                        <p>Which of the 15+ modules to enable first, and which to defer. Phased rollout plan.</p>
                    </div>
                </div>
                <div class="contact-info-item">
                    <div class="contact-info-icon" style="color: var(--accent-blue);"><?= sidebarIcon('arrow-path', 18) ?></div>
                    <div>
                        <h4>Migration Path</h4>
                        <p>How we'll move existing crew records, manuals, and rosters onto the platform.</p>
                    </div>
                </div>
                <div class="contact-info-item">
                    <div class="contact-info-icon" style="color: var(--accent-blue);"><?= sidebarIcon('academic-cap', 18) ?></div>
                    <div>
                        <h4>Training Schedule</h4>
                        <p>Admin and crew training plan, including iPad rollout timing and trust-cert process.</p>
                    </div>
                </div>
                <div class="contact-info-item">
                    <div class="contact-info-icon" style="color: var(--accent-blue);"><?= sidebarIcon('currency-dollar', 18) ?></div>
                    <div>
                        <h4>Fixed-Price First Year</h4>
                        <p>Total price for software + onboarding + support for year one. No hidden seat creep.</p>
                    </div>
                </div>
                <div class="info-card" style="margin-top: 32px;">
                    <h3>Already a Client?</h3>
                    <p>If your airline is on <?= e($brand['product_name']) ?>, your operations contact can request expansion modules directly through your airline admin portal.</p>
                </div>
            </div>
        </div>
    </div>
</div>
