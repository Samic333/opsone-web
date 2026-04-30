<?php /** OpsOne — Pricing (gated: details under NDA, contact-first) */ ?>
<div class="info-page">
    <div class="info-page-inner" style="max-width: 720px;">
        <div class="section-label">Pricing</div>
        <h1>Pricing Is Tailored To Each Airline</h1>
        <p class="lead">
            <?= e($brand['product_name']) ?> is sold per-airline. The price depends on fleet size,
            number of active crew, the modules you enable, and the support tier you need. We share
            the structure and a fixed first-year quote with you in your demo.
        </p>

        <div class="info-card" style="margin-top: 40px; text-align: center; padding: 36px 28px;">
            <h3 style="margin-bottom: 12px;">Get a quote with your operation in mind</h3>
            <p style="color: var(--text-secondary); margin: 0 auto 24px; max-width: 480px;">
                Tell us about your fleet, bases, and crew size. We'll come back within one business
                day with a tailored proposal — modules, migration approach, training plan, and a
                fixed first-year price.
            </p>
            <a href="/contact?type=sales" class="pub-btn pub-btn-primary pub-btn-large">Request a Quote</a>
        </div>

        <div style="margin-top: 48px; text-align: center;">
            <p style="font-size: 13px; color: var(--text-tertiary);">
                Already evaluating <?= e($brand['product_name']) ?>?
                <a href="/contact" style="color: var(--text-secondary);">Book a demo</a>
                &middot;
                Existing client? <a href="/login" style="color: var(--text-secondary);">Sign in</a>
            </p>
        </div>
    </div>
</div>
