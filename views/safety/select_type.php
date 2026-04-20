<?php
/**
 * OpsOne — Select Safety Report Type
 * Variables: $reportTypes (array of type_slug => label)
 */

$typeIcons = [
    'general_hazard'          => '⚠️',
    'flight_crew_occurrence'  => '✈️',
    'maintenance_engineering' => '🔧',
    'ground_ops'              => '🚧',
    'quality'                 => '✅',
    'hse'                     => '🦺',
    'tcas'                    => '📡',
    'environmental'           => '🌿',
    'frat'                    => '📋',
];

$typeDescriptions = [
    'general_hazard'          => 'Report any safety hazard or near-miss event',
    'flight_crew_occurrence'  => 'Flight crew occurrences, hazards, AIRPROX events',
    'maintenance_engineering' => 'Engineering findings, defects, part exchanges',
    'ground_ops'              => 'Ground handling occurrences and hazards',
    'quality'                 => 'Quality assurance observations and findings',
    'hse'                     => 'Health, safety, and environment incidents',
    'tcas'                    => 'TCAS resolution advisory events',
    'environmental'           => 'Environmental incidents and spills',
    'frat'                    => 'Pre-flight risk assessment tool',
];
?>

<!-- Just Culture reminder -->
<div style="
    background:rgba(245,158,11,0.08);
    border:1px solid rgba(245,158,11,0.3);
    border-radius:var(--radius-md);
    padding:12px 18px;
    display:flex; align-items:center; gap:12px;
    margin-bottom:28px;">
    <span style="font-size:18px; flex-shrink:0;">🛡️</span>
    <p class="text-sm" style="margin:0; color:var(--text-primary); line-height:1.5;">
        All reports are <strong>confidential</strong> and protected under the airline's Just Culture policy.
        Choose the report type that best matches your event.
    </p>
</div>

<!-- Report Type Grid -->
<?php if (empty($reportTypes)): ?>
    <div class="card" style="text-align:center; padding:48px;">
        <div style="font-size:40px; margin-bottom:12px;">🚫</div>
        <h3 style="margin:0 0 8px; color:var(--text-primary);">No Report Types Available</h3>
        <p class="text-sm text-muted" style="margin:0 0 20px;">
            No safety report types are currently enabled for your account.
            Contact your safety manager to enable report types.
        </p>
        <a href="/safety" class="btn btn-ghost btn-sm">← Back to Safety Home</a>
    </div>
<?php else: ?>
    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:16px; margin-bottom:32px;">
        <?php foreach ($reportTypes as $slug => $label): ?>
        <a href="/safety/report/new/<?= e($slug) ?>"
           style="
               display:block;
               background:var(--surface-2);
               border:1px solid var(--border);
               border-radius:var(--radius-lg);
               padding:20px 22px;
               text-decoration:none;
               transition:border-color 0.15s, box-shadow 0.15s;
           "
           onmouseover="this.style.borderColor='var(--accent-blue)'; this.style.boxShadow='0 0 0 3px rgba(59,130,246,0.12)';"
           onmouseout="this.style.borderColor='var(--border)'; this.style.boxShadow='none';">
            <div style="display:flex; align-items:flex-start; gap:14px;">
                <div style="
                    font-size:28px;
                    width:44px; height:44px;
                    display:flex; align-items:center; justify-content:center;
                    background:var(--surface-3);
                    border-radius:var(--radius-md);
                    flex-shrink:0;">
                    <?= $typeIcons[$slug] ?? '📝' ?>
                </div>
                <div>
                    <div style="font-weight:700; font-size:14px; color:var(--text-primary); margin-bottom:5px; line-height:1.3;">
                        <?= e($label) ?>
                    </div>
                    <div class="text-xs text-muted" style="line-height:1.4;">
                        <?= e($typeDescriptions[$slug] ?? 'Submit a safety report') ?>
                    </div>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Quick Report shortcut -->
    <div style="
        background:var(--surface-2);
        border:1px solid var(--border);
        border-radius:var(--radius-lg);
        padding:18px 22px;
        display:flex; align-items:center; justify-content:space-between; gap:16px;
        flex-wrap:wrap;">
        <div>
            <div style="font-weight:700; font-size:14px; color:var(--text-primary); margin-bottom:3px;">⚡ Quick Report</div>
            <div class="text-xs text-muted">Submit a general hazard report in under 2 minutes — no detailed fields required.</div>
        </div>
        <a href="/safety/quick-report/general_hazard" class="btn btn-outline btn-sm" style="flex-shrink:0; white-space:nowrap;">
            Start Quick Report →
        </a>
    </div>
<?php endif; ?>

<!-- Back link -->
<div style="margin-top:24px;">
    <a href="/safety" class="btn btn-ghost btn-sm">← Back to Safety Home</a>
</div>
