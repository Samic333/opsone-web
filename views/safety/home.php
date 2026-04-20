<?php
/**
 * OpsOne — Safety Reporting Home (Crew / All Users)
 * Variables: $reportTypes, $draftCount, $submittedCount
 */
$pageTitle    = 'Safety Reporting';
$pageSubtitle = 'Confidential. Protected under Just Culture policy.';
?>

<!-- Quick Access Row -->
<div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:28px;">
    <a href="/safety/my-drafts" class="btn btn-outline" style="display:flex; align-items:center; gap:8px; padding:10px 18px;">
        <span>📝</span>
        <span>My Drafts <strong>(<?= (int)($draftCount ?? 0) ?>)</strong></span>
    </a>
    <a href="/safety/my-reports" class="btn btn-outline" style="display:flex; align-items:center; gap:8px; padding:10px 18px;">
        <span>📋</span>
        <span>My Submitted Reports <strong>(<?= (int)($submittedCount ?? 0) ?>)</strong></span>
    </a>
</div>

<!-- Section Header -->
<div style="margin-bottom:20px;">
    <h3 style="margin:0 0 4px; font-size:16px; font-weight:700; color:var(--text-primary);">Select Report Type</h3>
    <p class="text-sm text-muted">Choose the category that best describes the event you want to report.</p>
</div>

<!-- Report Type Grid -->
<?php
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
    'general_hazard'          => 'Report any safety hazard or near-miss',
    'flight_crew_occurrence'  => 'Flight crew occurrences, hazards, AIRPROX',
    'maintenance_engineering' => 'Engineering findings, defects, part exchanges',
    'ground_ops'              => 'Ground handling occurrences and hazards',
    'quality'                 => 'Quality assurance observations and findings',
    'hse'                     => 'Health, safety, and environment incidents',
    'tcas'                    => 'TCAS resolution advisory events',
    'environmental'           => 'Environmental incidents and spills',
    'frat'                    => 'Pre-flight risk assessment tool',
];
?>

<div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:16px; margin-bottom:32px;">
    <?php foreach ($reportTypes as $type): ?>
    <?php
    $slug  = $type['slug'] ?? '';
    $icon  = $type['icon'] ?? ($typeIcons[$slug] ?? '📄');
    $desc  = $type['description'] ?? ($typeDescriptions[$slug] ?? '');
    $label = $type['label'] ?? ucwords(str_replace('_', ' ', $slug));
    ?>
    <div class="card" style="display:flex; flex-direction:column; gap:12px; padding:22px 20px; transition:box-shadow 0.15s, transform 0.15s; cursor:default;"
         onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.12)'; this.style.transform='translateY(-2px)'"
         onmouseout="this.style.boxShadow=''; this.style.transform=''">
        <div style="font-size:32px; line-height:1;"><?= $icon ?></div>
        <div>
            <h4 style="margin:0 0 4px; font-size:15px; font-weight:700; color:var(--text-primary);"><?= e($label) ?></h4>
            <p class="text-sm text-muted" style="margin:0; line-height:1.5;"><?= e($desc) ?></p>
        </div>
        <div style="margin-top:auto;">
            <a href="/safety/report/new/<?= e($slug) ?>" class="btn btn-primary btn-sm" style="width:100%; text-align:center;">
                Start Report →
            </a>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Footer Notice -->
<div style="background:var(--bg-secondary); border:1px solid var(--border); border-radius:var(--radius-md); padding:14px 18px; display:flex; align-items:center; gap:12px;">
    <span style="font-size:16px; color:var(--text-muted);">🔒</span>
    <p class="text-sm text-muted" style="margin:0;">
        Reports are encrypted and only visible to authorised safety personnel. Your identity is protected under the company's Just Culture policy.
    </p>
</div>
