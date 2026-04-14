<?php $pageTitle = 'New Onboarding Request'; ob_start(); ?>

<div style="max-width:800px;">
<form method="POST" action="/platform/onboarding/store">
    <?= csrfField() ?>

    <div class="card" style="margin-bottom:1.5rem;">
        <h3 style="margin:0 0 1rem; font-size:1rem; font-weight:600;">Airline Details</h3>
        <div class="form-row">
            <div class="form-group">
                <label>Legal Company Name *</label>
                <input type="text" name="legal_name" class="form-control" required placeholder="Full legal name">
            </div>
            <div class="form-group">
                <label>Display Name</label>
                <input type="text" name="display_name" class="form-control" placeholder="Short name for the portal">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>ICAO Code</label>
                <input type="text" name="icao_code" class="form-control" maxlength="4" style="text-transform:uppercase" placeholder="e.g. GWA">
            </div>
            <div class="form-group">
                <label>IATA Code</label>
                <input type="text" name="iata_code" class="form-control" maxlength="3" style="text-transform:uppercase" placeholder="e.g. GW">
            </div>
            <div class="form-group">
                <label>Primary Country</label>
                <input type="text" name="primary_country" class="form-control" placeholder="e.g. UAE">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Support Tier</label>
                <select name="support_tier" class="form-control">
                    <option value="standard">Standard</option>
                    <option value="premium">Premium</option>
                    <option value="enterprise">Enterprise</option>
                </select>
            </div>
            <div class="form-group">
                <label>Expected Headcount</label>
                <input type="number" name="expected_headcount" class="form-control" min="0" placeholder="0">
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:1.5rem;">
        <h3 style="margin:0 0 1rem; font-size:1rem; font-weight:600;">Primary Contact</h3>
        <div class="form-row">
            <div class="form-group">
                <label>Contact Name *</label>
                <input type="text" name="contact_name" class="form-control" required placeholder="Primary admin name">
            </div>
            <div class="form-group">
                <label>Contact Email *</label>
                <input type="email" name="contact_email" class="form-control" required placeholder="admin@airline.aero">
            </div>
            <div class="form-group">
                <label>Contact Phone</label>
                <input type="text" name="contact_phone" class="form-control" placeholder="+971 50 000 0000">
            </div>
        </div>
    </div>

    <?php if (!empty($allModules)): ?>
    <div class="card" style="margin-bottom:1.5rem;">
        <h3 style="margin:0 0 0.5rem; font-size:1rem; font-weight:600;">Requested Modules</h3>
        <p style="font-size:12px; color:var(--text-muted); margin:0 0 1rem;">Select which modules this airline has requested.</p>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(200px,1fr)); gap:8px;">
            <?php foreach ($allModules as $mod): ?>
            <label style="display:flex; align-items:center; gap:8px; padding:8px 10px;
                          border:1px solid var(--border); border-radius:6px; cursor:pointer; font-size:13px;">
                <input type="checkbox" name="modules[]" value="<?= e($mod['code']) ?>">
                <?= e($mod['name']) ?>
            </label>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="card" style="margin-bottom:1.5rem;">
        <div class="form-group">
            <label>Internal Notes</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Any notes about this onboarding request..."></textarea>
        </div>
    </div>

    <div class="flex gap-1">
        <button type="submit" class="btn btn-primary">Submit Request</button>
        <a href="/platform/onboarding" class="btn btn-outline">Cancel</a>
    </div>
</form>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/app.php'; ?>
