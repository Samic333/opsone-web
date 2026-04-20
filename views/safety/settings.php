<?php
/**
 * OpsOne — Safety Module Settings
 * Variables: $settings (array), $retentionDays (int), $safetyUsers (array — users with safety roles)
 */
$pageTitle    = 'Safety Module Settings';
$pageSubtitle = 'Configure safety reporting behaviour and retention policy';

$s = $settings ?? [];

// All report type slugs
$reportTypeSlugs = [
    'general_hazard'          => 'General Hazard',
    'flight_crew_occurrence'  => 'Flight Crew Occurrence',
    'maintenance_engineering' => 'Maintenance / Engineering',
    'ground_ops'              => 'Ground Operations',
    'quality'                 => 'Quality',
    'hse'                     => 'Health, Safety & Environment',
    'tcas'                    => 'TCAS',
    'environmental'           => 'Environmental',
    'frat'                    => 'FRAT (Pre-Flight Risk Assessment)',
];

$enabledTypes = $s['enabled_report_types'] ?? array_keys($reportTypeSlugs);
if (is_string($enabledTypes)) {
    $enabledTypes = json_decode($enabledTypes, true) ?? array_keys($reportTypeSlugs);
}
?>

<form method="POST" action="/safety/settings/save">
    <?= csrfField() ?>

    <!-- Section: Enabled Report Types -->
    <div class="card" style="padding:24px; margin-bottom:20px;">
        <h4 style="margin:0 0 6px; font-size:15px; font-weight:700;">Enabled Report Types</h4>
        <p class="text-sm text-muted" style="margin:0 0 18px;">Uncheck a type to hide it from the report submission screen.</p>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:10px;">
            <?php foreach ($reportTypeSlugs as $slug => $label): ?>
            <label style="display:flex; align-items:center; gap:10px; padding:10px 14px; background:var(--bg-body); border-radius:var(--radius-sm); border:1px solid var(--border); cursor:pointer;">
                <input type="checkbox" name="enabled_report_types[]" value="<?= $slug ?>"
                       <?= in_array($slug, (array)$enabledTypes) ? 'checked' : '' ?>
                       style="width:15px; height:15px; flex-shrink:0;">
                <span class="text-sm" style="font-weight:500;"><?= e($label) ?></span>
            </label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Section: General Settings -->
    <div class="card" style="padding:24px; margin-bottom:20px;">
        <h4 style="margin:0 0 18px; font-size:15px; font-weight:700; padding-bottom:12px; border-bottom:1px solid var(--border);">General Settings</h4>

        <?php
        $toggles = [
            'allow_anonymous'          => ['label' => 'Allow Anonymous Reports',     'desc' => 'Reporters may opt to hide their identity when submitting a report.'],
            'require_aircraft_reg'     => ['label' => 'Require Aircraft Registration', 'desc' => 'Aircraft registration becomes mandatory for flight-related report types.'],
            'risk_matrix_enabled'      => ['label' => 'Enable Risk Matrix (1–5)',    'desc' => 'Show the initial risk assessment selector on all report forms.'],
        ];
        foreach ($toggles as $key => $info):
            $checked = !empty($s[$key]);
        ?>
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:20px; padding:14px 0; border-bottom:1px solid var(--border);">
            <div>
                <div style="font-size:14px; font-weight:600; margin-bottom:3px;"><?= $info['label'] ?></div>
                <div class="text-xs text-muted"><?= $info['desc'] ?></div>
            </div>
            <label style="flex-shrink:0; display:flex; align-items:center; gap:8px; cursor:pointer;">
                <input type="hidden" name="<?= $key ?>" value="0">
                <input type="checkbox" name="<?= $key ?>" value="1" <?= $checked ? 'checked' : '' ?>
                       style="width:16px; height:16px;">
                <span class="text-sm"><?= $checked ? 'Enabled' : 'Disabled' ?></span>
            </label>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Section: Retention -->
    <div class="card" style="padding:24px; margin-bottom:20px;">
        <h4 style="margin:0 0 6px; font-size:15px; font-weight:700;">Data Retention</h4>
        <p class="text-sm text-muted" style="margin:0 0 18px;">How long safety reports are retained before archiving or deletion.</p>
        <div class="form-group" style="max-width:320px; margin-bottom:0;">
            <label>Retention Period (days)</label>
            <input type="number" name="retention_days" class="form-control"
                   min="2555" step="1"
                   value="<?= (int)($retentionDays ?? $s['retention_days'] ?? 2555) ?>">
            <p class="text-xs text-muted" style="margin-top:6px;">
                Regulatory minimum: <strong>2,555 days (7 years)</strong>. Do not set below this value.
            </p>
        </div>
    </div>

    <!-- Section: Assigned Safety Users (read-only) -->
    <div class="card" style="padding:24px; margin-bottom:28px;">
        <h4 style="margin:0 0 6px; font-size:15px; font-weight:700;">Safety Team Members</h4>
        <p class="text-sm text-muted" style="margin:0 0 16px;">Users with <code>safety_officer</code> or <code>safety_staff</code> roles who can access the safety queue and investigations. Manage roles via Administration → Roles &amp; Permissions.</p>

        <?php if (empty($safetyUsers)): ?>
            <div style="padding:16px; background:rgba(245,158,11,0.07); border-radius:var(--radius-sm); border:1px solid rgba(245,158,11,0.25);">
                <p class="text-sm" style="margin:0; color:#92400e;">⚠ No safety team members configured. Assign the <strong>safety_officer</strong> role to at least one user.</p>
            </div>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:6px;">
                <?php foreach ($safetyUsers as $u): ?>
                <div style="display:flex; align-items:center; gap:12px; padding:10px 14px; background:var(--bg-body); border-radius:var(--radius-sm); border:1px solid var(--border);">
                    <div style="width:32px; height:32px; border-radius:50%; background:var(--accent-blue); color:#fff; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; flex-shrink:0;">
                        <?= strtoupper(substr($u['name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div>
                        <div class="text-sm" style="font-weight:600;"><?= e($u['name'] ?? '') ?></div>
                        <div class="text-xs text-muted"><?= e(ucwords(str_replace('_', ' ', $u['role'] ?? ''))) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Save -->
    <div style="display:flex; justify-content:flex-end; gap:12px;">
        <button type="submit" class="btn btn-primary">Save Settings</button>
    </div>
</form>
