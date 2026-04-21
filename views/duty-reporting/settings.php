<?php /** OpsOne — Duty Reporting Settings */ ?>

<form method="POST" action="/duty-reporting/settings">
    <?= csrfField() ?>

    <!-- Module toggle + allowed roles -->
    <div class="card" style="padding:20px; margin-bottom:18px;">
        <h3 style="margin:0 0 14px 0; font-size:15px;">Module Access</h3>

        <div style="margin-bottom:14px;">
            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                <input type="checkbox" name="enabled" value="1" <?= $settings['enabled'] ? 'checked' : '' ?>>
                <span>Enable Duty Reporting for this tenant</span>
            </label>
            <p class="text-xs text-muted" style="margin:4px 0 0 24px;">
                When disabled, the iPad app hides Duty Reporting and API endpoints return 403.
            </p>
        </div>

        <div>
            <label class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:0.06em;">Allowed Roles</label>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:6px; margin-top:8px;">
                <?php
                $currentAllowed = array_map('trim', explode(',', $settings['allowed_roles']));
                foreach ($availableRoles as $slug => $label):
                    $checked = in_array($slug, $currentAllowed, true);
                ?>
                    <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
                        <input type="checkbox" name="allowed_roles[]" value="<?= $slug ?>" <?= $checked ? 'checked' : '' ?>>
                        <span><?= e($label) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <p class="text-xs text-muted" style="margin-top:8px;">
                Only users whose primary or assigned roles are ticked above can check in or clock out.
            </p>
        </div>
    </div>

    <!-- Geo-fence -->
    <div class="card" style="padding:20px; margin-bottom:18px;">
        <h3 style="margin:0 0 14px 0; font-size:15px;">Geo-fence</h3>

        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; margin-bottom:12px;">
            <input type="checkbox" name="geofence_required" value="1" <?= $settings['geofence_required'] ? 'checked' : '' ?>>
            <span>Require geo-fence match on check-in</span>
        </label>
        <p class="text-xs text-muted" style="margin:-6px 0 14px 24px;">
            When enabled, check-in outside a configured base radius must go through the exception flow.
        </p>

        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; margin-bottom:12px;">
            <input type="checkbox" name="allow_outstation" value="1" <?= $settings['allow_outstation'] ? 'checked' : '' ?>>
            <span>Allow outstation check-in (via exception)</span>
        </label>

        <div>
            <label class="text-xs text-muted">Default geofence radius (metres)</label>
            <input type="number" name="default_radius_m" class="form-control" min="0" max="20000"
                   value="<?= (int)$settings['default_radius_m'] ?>" style="max-width:200px;">
            <p class="text-xs text-muted" style="margin-top:4px;">
                Per-base override is configured on each Base record under <em>Bases</em>.
            </p>
        </div>
    </div>

    <!-- Exceptions & reminders -->
    <div class="card" style="padding:20px; margin-bottom:18px;">
        <h3 style="margin:0 0 14px 0; font-size:15px;">Exceptions &amp; Reminders</h3>

        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; margin-bottom:14px;">
            <input type="checkbox" name="exception_approval_required" value="1" <?= $settings['exception_approval_required'] ? 'checked' : '' ?>>
            <span>Exception check-ins require manager approval</span>
        </label>

        <div>
            <label class="text-xs text-muted">Clock-out reminder after N minutes of duty</label>
            <input type="number" name="clock_out_reminder_minutes" class="form-control" min="60" max="2880"
                   value="<?= (int)$settings['clock_out_reminder_minutes'] ?>" style="max-width:200px;">
            <p class="text-xs text-muted" style="margin-top:4px;">
                Default 840 minutes (14h). The iPad app shows a nudge; missed clock-outs are flagged after this + 6h grace.
            </p>
        </div>
    </div>

    <!-- Device / biometric -->
    <div class="card" style="padding:20px; margin-bottom:18px;">
        <h3 style="margin:0 0 14px 0; font-size:15px;">Device &amp; Biometric</h3>

        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; margin-bottom:10px;">
            <input type="checkbox" name="trusted_device_required" value="1" <?= $settings['trusted_device_required'] ? 'checked' : '' ?>>
            <span>Require an approved trusted device</span>
        </label>

        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
            <input type="checkbox" name="biometric_required" value="1" <?= $settings['biometric_required'] ? 'checked' : '' ?>>
            <span>Require platform biometric confirmation (Face ID / Touch ID)</span>
        </label>
        <p class="text-xs text-muted" style="margin:4px 0 0 24px;">
            Uses the device's built-in biometric prompt only. No biometric data is stored by OpsOne.
        </p>
    </div>

    <!-- Retention -->
    <div class="card" style="padding:20px; margin-bottom:18px;">
        <h3 style="margin:0 0 14px 0; font-size:15px;">Retention</h3>
        <label class="text-xs text-muted">Retain duty records for (days)</label>
        <input type="number" name="retention_days" class="form-control" min="30" max="3650"
               value="<?= (int)$settings['retention_days'] ?>" style="max-width:200px;">
        <p class="text-xs text-muted" style="margin-top:4px;">
            Default 180 days. Minimum enforced floor is 30 days (safety guard inside RetentionService).
        </p>
    </div>

    <div style="display:flex; gap:10px;">
        <button type="submit" class="btn btn-primary">Save Settings</button>
        <a href="/duty-reporting" class="btn btn-ghost">Cancel</a>
    </div>
</form>
