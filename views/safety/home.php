<?php
/**
 * OpsOne — Safety Reporting Home
 * Variables: $reportTypes (array), $draftCount (int), $submittedCount (int),
 *            $isTeamUser (bool), $user (array)
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

<!-- ═══════════════════════════════
     PAGE HEADER
     ═══════════════════════════════ -->
<div style="margin-bottom:20px;">
    <h2 style="margin:0 0 4px; font-size:22px; font-weight:700; color:var(--text-primary);">Safety Reporting</h2>
    <p class="text-sm text-muted" style="margin:0;">Just Culture — Confidential &amp; Protected</p>
</div>

<!-- Just Culture Banner -->
<div style="
    background:rgba(245,158,11,0.08);
    border:1px solid rgba(245,158,11,0.35);
    border-radius:var(--radius-md);
    padding:13px 18px;
    display:flex; align-items:center; gap:12px;
    margin-bottom:24px;">
    <span style="font-size:18px; flex-shrink:0;">🛡️</span>
    <p class="text-sm" style="margin:0; color:var(--text-primary); line-height:1.5;">
        All safety reports are <strong>confidential</strong>. You are protected under the airline's Just Culture policy.
        Reports are used to improve safety — not to apportion blame.
    </p>
</div>

<!-- Primary CTA -->
<div style="margin-bottom:28px;">
    <a href="/safety/select-type" class="btn btn-primary" style="padding:12px 28px; font-size:15px; font-weight:700;">
        + Start a Report
    </a>
</div>

<!-- ═══════════════════════════════
     QUICK ACCESS CHIPS
     ═══════════════════════════════ -->
<div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:32px;">

    <a href="/safety/drafts" style="
        display:flex; align-items:center; gap:8px;
        padding:10px 16px; border-radius:var(--radius-md);
        background:var(--card-bg, var(--bg-card)); border:1px solid var(--border);
        text-decoration:none; color:var(--text-primary);
        font-size:13px; font-weight:600;
        transition:box-shadow 0.15s, transform 0.15s;"
        onmouseover="this.style.boxShadow='0 2px 10px rgba(0,0,0,0.1)'; this.style.transform='translateY(-1px)'"
        onmouseout="this.style.boxShadow=''; this.style.transform=''">
        <span>📝</span>
        <span>My Drafts</span>
        <span style="
            background:var(--accent-blue,#3b82f6); color:#fff;
            border-radius:20px; padding:1px 8px; font-size:11px; font-weight:700;">
            <?= (int)($draftCount ?? 0) ?>
        </span>
    </a>

    <a href="/safety/my-reports" style="
        display:flex; align-items:center; gap:8px;
        padding:10px 16px; border-radius:var(--radius-md);
        background:var(--card-bg, var(--bg-card)); border:1px solid var(--border);
        text-decoration:none; color:var(--text-primary);
        font-size:13px; font-weight:600;
        transition:box-shadow 0.15s, transform 0.15s;"
        onmouseover="this.style.boxShadow='0 2px 10px rgba(0,0,0,0.1)'; this.style.transform='translateY(-1px)'"
        onmouseout="this.style.boxShadow=''; this.style.transform=''">
        <span>📋</span>
        <span>My Reports</span>
        <span style="
            background:var(--accent-blue,#3b82f6); color:#fff;
            border-radius:20px; padding:1px 8px; font-size:11px; font-weight:700;">
            <?= (int)($submittedCount ?? 0) ?>
        </span>
    </a>

    <a href="/safety/follow-ups" style="
        display:flex; align-items:center; gap:8px;
        padding:10px 16px; border-radius:var(--radius-md);
        background:var(--card-bg, var(--bg-card)); border:1px solid var(--border);
        text-decoration:none; color:var(--text-primary);
        font-size:13px; font-weight:600;
        transition:box-shadow 0.15s, transform 0.15s;"
        onmouseover="this.style.boxShadow='0 2px 10px rgba(0,0,0,0.1)'; this.style.transform='translateY(-1px)'"
        onmouseout="this.style.boxShadow=''; this.style.transform=''">
        <span>💬</span>
        <span>Follow-ups</span>
        <?php if (!empty($followUpCount)): ?>
        <span style="
            background:#f59e0b; color:#fff;
            border-radius:20px; padding:1px 8px; font-size:11px; font-weight:700;">
            <?= (int)$followUpCount ?>
        </span>
        <?php endif; ?>
    </a>

    <a href="/safety/publications" style="
        display:flex; align-items:center; gap:8px;
        padding:10px 16px; border-radius:var(--radius-md);
        background:var(--card-bg, var(--bg-card)); border:1px solid var(--border);
        text-decoration:none; color:var(--text-primary);
        font-size:13px; font-weight:600;
        transition:box-shadow 0.15s, transform 0.15s;"
        onmouseover="this.style.boxShadow='0 2px 10px rgba(0,0,0,0.1)'; this.style.transform='translateY(-1px)'"
        onmouseout="this.style.boxShadow=''; this.style.transform=''">
        <span>📢</span>
        <span>Safety Bulletins</span>
    </a>

</div>

<!-- ═══════════════════════════════
     REPORT TYPE GRID
     ═══════════════════════════════ -->
<div style="margin-bottom:12px;">
    <h3 style="margin:0 0 4px; font-size:16px; font-weight:700; color:var(--text-primary);">What are you reporting?</h3>
    <p class="text-sm text-muted" style="margin:0;">Choose the category that best describes the event.</p>
</div>

<?php if (empty($reportTypes)): ?>
<div class="card">
    <div class="empty-state" style="padding:36px 0;">
        <div class="icon">🔒</div>
        <h3>No report types available</h3>
        <p>No safety report types are enabled for your role. Contact your safety manager.</p>
    </div>
</div>
<?php else: ?>
<div style="
    display:grid;
    grid-template-columns: repeat(3, 1fr);
    gap:16px;
    margin-bottom:36px;">
    <?php foreach ($reportTypes as $slug => $label): ?>
    <?php
    $icon = $typeIcons[$slug] ?? '📄';
    $desc = $typeDescriptions[$slug] ?? '';
    ?>
    <div class="card" style="
        display:flex; flex-direction:column; gap:12px;
        padding:22px 20px;
        transition:box-shadow 0.15s, transform 0.15s;
        cursor:default;"
         onmouseover="this.style.boxShadow='0 4px 18px rgba(0,0,0,0.12)'; this.style.transform='translateY(-2px)'"
         onmouseout="this.style.boxShadow=''; this.style.transform=''">
        <div style="font-size:30px; line-height:1;"><?= $icon ?></div>
        <div style="flex:1;">
            <h4 style="margin:0 0 5px; font-size:14px; font-weight:700; color:var(--text-primary);"><?= e($label) ?></h4>
            <p class="text-muted" style="margin:0; font-size:12px; line-height:1.5;"><?= e($desc) ?></p>
        </div>
        <div>
            <a href="/safety/report/new/<?= e($slug) ?>" class="btn btn-primary btn-sm" style="width:100%; text-align:center;">
                Start →
            </a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════
     SAFETY TEAM SECTION (conditional)
     ═══════════════════════════════ -->
<?php if (!empty($isTeamUser)): ?>
<div style="
    margin-bottom:8px;
    padding-top:24px;
    border-top:1px solid var(--border);
    display:flex; align-items:center; gap:10px;">
    <h3 style="margin:0; font-size:16px; font-weight:700; color:var(--text-primary);">Safety Team</h3>
    <span style="
        background:rgba(139,92,246,0.12); color:#7c3aed;
        border-radius:20px; padding:2px 10px; font-size:11px; font-weight:700;">
        Team Access
    </span>
</div>
<p class="text-sm text-muted" style="margin:0 0 16px;">Quick access to safety management tools.</p>

<div style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:32px;">
    <?php
    $teamActions = [
        ['icon' => '📊', 'label' => 'Safety Dashboard',  'url' => '/safety/dashboard'],
        ['icon' => '🔍', 'label' => 'Under Review',      'url' => '/safety/queue?status=under_review'],
        ['icon' => '🔬', 'label' => 'Investigation',     'url' => '/safety/queue?status=investigation'],
        ['icon' => '✅', 'label' => 'Actions',           'url' => '/safety/queue?status=action_in_progress'],
        ['icon' => '📢', 'label' => 'Publications',      'url' => '/safety/publications'],
        ['icon' => '⚙️', 'label' => 'Settings',          'url' => '/safety/settings'],
    ];
    foreach ($teamActions as $action):
    ?>
    <a href="<?= e($action['url']) ?>" style="
        display:flex; align-items:center; gap:8px;
        padding:10px 16px; border-radius:var(--radius-md);
        background:rgba(139,92,246,0.06); border:1px solid rgba(139,92,246,0.2);
        text-decoration:none; color:var(--text-primary);
        font-size:13px; font-weight:600;
        transition:background 0.15s, transform 0.15s;"
        onmouseover="this.style.background='rgba(139,92,246,0.12)'; this.style.transform='translateY(-1px)'"
        onmouseout="this.style.background='rgba(139,92,246,0.06)'; this.style.transform=''">
        <span><?= $action['icon'] ?></span>
        <span><?= e($action['label']) ?></span>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Confidentiality footer -->
<div style="
    background:var(--bg-secondary, var(--bg-body));
    border:1px solid var(--border);
    border-radius:var(--radius-md);
    padding:13px 18px;
    display:flex; align-items:center; gap:12px;">
    <span style="font-size:14px; color:var(--text-muted); flex-shrink:0;">🔒</span>
    <p class="text-sm text-muted" style="margin:0; line-height:1.5;">
        Reports are encrypted and accessible only to authorised safety personnel.
        Internal notes and investigation details are not visible to reporters.
    </p>
</div>

<style>
@media (max-width: 900px) {
    .safety-type-grid { grid-template-columns: repeat(2, 1fr) !important; }
}
@media (max-width: 560px) {
    .safety-type-grid { grid-template-columns: 1fr !important; }
}
</style>
