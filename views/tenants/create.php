<?php $pageTitle = 'Add New Airline'; ob_start(); ?>

<div style="max-width: 900px;">
<form method="POST" action="/tenants/store">
    <?= csrfField() ?>

    <!-- ─── Identity ─────────────────────────────────────────── -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <h3 style="margin:0 0 1.2rem; font-size:1rem; font-weight:600; color:var(--text-primary);">
            🏢 Airline Identity
        </h3>
        <div class="form-row">
            <div class="form-group">
                <label for="legal_name">Legal Company Name *</label>
                <input type="text" id="legal_name" name="legal_name" class="form-control"
                       placeholder="e.g. Gulf Wings Aviation LLC" required>
            </div>
            <div class="form-group">
                <label for="display_name">Display / Short Name</label>
                <input type="text" id="display_name" name="display_name" class="form-control"
                       placeholder="e.g. Gulf Wings">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="name">Portal Name *</label>
                <input type="text" id="name" name="name" class="form-control"
                       placeholder="Name shown in the portal" required>
            </div>
            <div class="form-group">
                <label for="code">System Code *
                    <small style="font-weight:400; color:var(--text-muted);">(max 10 chars)</small>
                </label>
                <input type="text" id="code" name="code" class="form-control"
                       placeholder="e.g. GWA" maxlength="10" required style="text-transform:uppercase">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="icao_code">ICAO Code</label>
                <input type="text" id="icao_code" name="icao_code" class="form-control"
                       placeholder="e.g. GWA" maxlength="4" style="text-transform:uppercase">
            </div>
            <div class="form-group">
                <label for="iata_code">IATA Code</label>
                <input type="text" id="iata_code" name="iata_code" class="form-control"
                       placeholder="e.g. GW" maxlength="3" style="text-transform:uppercase">
            </div>
            <div class="form-group">
                <label for="primary_country">Primary Country</label>
                <input type="text" id="primary_country" name="primary_country" class="form-control"
                       placeholder="e.g. UAE">
            </div>
            <div class="form-group">
                <label for="primary_base">Primary Base / HQ</label>
                <input type="text" id="primary_base" name="primary_base" class="form-control"
                       placeholder="e.g. Dubai (DXB)">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="contact_email">Airline Contact Email</label>
                <input type="email" id="contact_email" name="contact_email" class="form-control"
                       placeholder="admin@airline.com">
            </div>
            <div class="form-group">
                <label for="support_tier">Support Tier</label>
                <select id="support_tier" name="support_tier" class="form-control">
                    <option value="standard">Standard</option>
                    <option value="premium">Premium</option>
                    <option value="enterprise">Enterprise</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="notes">Internal Notes</label>
            <textarea id="notes" name="notes" class="form-control" rows="2"
                      placeholder="Any internal notes about this airline..."></textarea>
        </div>
    </div>

    <!-- ─── Headcount ─────────────────────────────────────────── -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <h3 style="margin:0 0 1.2rem; font-size:1rem; font-weight:600; color:var(--text-primary);">
            👥 Expected Headcount
        </h3>
        <div class="form-row">
            <div class="form-group">
                <label>Total Expected</label>
                <input type="number" name="expected_headcount" class="form-control" min="0" placeholder="0">
            </div>
            <div class="form-group">
                <label>Pilots</label>
                <input type="number" name="headcount_pilots" class="form-control" min="0" placeholder="0">
            </div>
            <div class="form-group">
                <label>Cabin Crew</label>
                <input type="number" name="headcount_cabin" class="form-control" min="0" placeholder="0">
            </div>
            <div class="form-group">
                <label>Engineers</label>
                <input type="number" name="headcount_engineers" class="form-control" min="0" placeholder="0">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Schedulers</label>
                <input type="number" name="headcount_schedulers" class="form-control" min="0" placeholder="0">
            </div>
            <div class="form-group">
                <label>Training Staff</label>
                <input type="number" name="headcount_training" class="form-control" min="0" placeholder="0">
            </div>
            <div class="form-group">
                <label>Safety Staff</label>
                <input type="number" name="headcount_safety" class="form-control" min="0" placeholder="0">
            </div>
            <div class="form-group">
                <label>HR Staff</label>
                <input type="number" name="headcount_hr" class="form-control" min="0" placeholder="0">
            </div>
        </div>
    </div>

    <!-- ─── Initial Admin Contact ─────────────────────────────── -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <h3 style="margin:0 0 0.4rem; font-size:1rem; font-weight:600; color:var(--text-primary);">
            👤 Initial Admin Contact
        </h3>
        <p style="margin:0 0 1rem; font-size:12px; color:var(--text-muted);">
            An invitation token will be created for this contact (no plain-text password sent).
            Email delivery is wired up in Phase 1.
        </p>
        <div class="form-row">
            <div class="form-group">
                <label for="admin_contact_name">Full Name</label>
                <input type="text" id="admin_contact_name" name="admin_contact_name" class="form-control"
                       placeholder="Airline Administrator">
            </div>
            <div class="form-group">
                <label for="admin_contact_email">Email Address</label>
                <input type="email" id="admin_contact_email" name="admin_contact_email" class="form-control"
                       placeholder="admin@airline.aero">
            </div>
        </div>
    </div>

    <!-- ─── Modules ───────────────────────────────────────────── -->
    <?php if (!empty($allModules)): ?>
    <div class="card" style="margin-bottom: 1.5rem;">
        <h3 style="margin:0 0 0.4rem; font-size:1rem; font-weight:600; color:var(--text-primary);">
            🧩 Modules to Enable
        </h3>
        <p style="margin:0 0 1rem; font-size:12px; color:var(--text-muted);">
            Select which modules to activate for this airline at launch.
            More can be enabled later from the airline detail page.
        </p>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px,1fr)); gap: 10px;">
            <?php foreach ($allModules as $mod): ?>
            <label style="display:flex; align-items:flex-start; gap:8px; padding:10px;
                          border:1px solid var(--border); border-radius:6px; cursor:pointer;
                          background:var(--surface);">
                <input type="checkbox" name="modules[]" value="<?= e($mod['code']) ?>"
                       style="margin-top:2px; flex-shrink:0;"
                       <?= in_array($mod['code'], ['crew_profiles','notices','mobile_ipad_access']) ? 'checked' : '' ?>>
                <div>
                    <div style="font-size:13px; font-weight:600;"><?= e($mod['name']) ?></div>
                    <?php if ($mod['description']): ?>
                    <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">
                        <?= e($mod['description']) ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($mod['mobile_capable']): ?>
                    <span style="font-size:10px; color:#6366f1;">📱 iPad</span>
                    <?php endif; ?>
                </div>
            </label>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ─── Actions ───────────────────────────────────────────── -->
    <div class="flex gap-1">
        <button type="submit" class="btn btn-primary">Create Airline</button>
        <a href="/tenants" class="btn btn-outline">Cancel</a>
    </div>
</form>
</div>
<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/app.php'; ?>
