<?php
/**
 * Competency Records — index
 *
 * Variables in scope (set by CompetencyController::index):
 *   $summary, $sourceRecords, $totalAccepted, $overallAvg
 */
?>

<div class="page-header" style="margin-bottom:20px;">
    <h1 style="margin:0; font-size:24px; letter-spacing:-0.02em;">Competency Records</h1>
    <p class="text-muted" style="margin:6px 0 0;">
        Your competency profile, aggregated from <?= (int)$totalAccepted ?> accepted peer
        <?= $totalAccepted === 1 ? 'appraisal' : 'appraisals' ?>. Scores follow the
        Field Stations Staff Appraisal Form (Form No. 150, Rev 01) — 1 = Poor &middot;
        5 = Excellent.
    </p>
</div>

<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-label">Accepted appraisals</div>
        <div class="stat-value"><?= (int)$totalAccepted ?></div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Overall average</div>
        <div class="stat-value">
            <?= $overallAvg !== null ? number_format($overallAvg, 1) . ' / 5' : '—' ?>
        </div>
    </div>
    <div class="stat-card purple">
        <div class="stat-label">Attributes scored</div>
        <div class="stat-value">
            <?= count(array_filter($summary, fn($s) => $s['count'] > 0)) ?> / <?= count($summary) ?>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom:16px;">
    <h3 style="margin-top:0;">Competency profile</h3>
    <?php if ($totalAccepted === 0): ?>
        <p class="text-muted">
            No accepted peer appraisals yet. Once HR / your Chief Pilot accepts a peer
            appraisal about you, the per-attribute scores will populate this view.
        </p>
    <?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Attribute</th>
                    <th>Average</th>
                    <th>Latest</th>
                    <th>Records</th>
                    <th style="min-width:200px;">Strength</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($summary as $row):
                    $pct = $row['avg'] !== null ? min(100, ($row['avg'] / 5) * 100) : 0;
                    $color = $row['avg'] >= 4 ? '#10b981' : ($row['avg'] >= 3 ? '#3b82f6' : '#f59e0b');
                ?>
                <tr>
                    <td>
                        <div style="font-weight:600;"><?= e($row['label']) ?></div>
                    </td>
                    <td>
                        <?php if ($row['avg'] !== null): ?>
                            <strong><?= number_format($row['avg'], 1) ?></strong>
                            <span class="text-xs text-muted">/ 5</span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $row['latest'] !== null ? str_repeat('★', (int)$row['latest']) : '<span class="text-muted">—</span>' ?>
                    </td>
                    <td><?= (int)$row['count'] ?></td>
                    <td>
                        <?php if ($row['avg'] !== null): ?>
                            <div style="background: var(--bg-secondary); border-radius:6px; height:8px; overflow:hidden;">
                                <div style="background: <?= $color ?>; width: <?= $pct ?>%; height:100%;"></div>
                            </div>
                        <?php else: ?>
                            <span class="text-xs text-muted">No ratings yet</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($sourceRecords)): ?>
<div class="card">
    <h3 style="margin-top:0;">Source appraisals</h3>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Appraiser</th>
                    <th>Rotation</th>
                    <th>Period</th>
                    <th>Overall</th>
                    <th>Per-attribute scores</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sourceRecords as $r): ?>
                <tr>
                    <td><?= e($r['appraiser_name']) ?></td>
                    <td class="text-sm"><?= e($r['rotation_ref'] ?: '—') ?></td>
                    <td class="text-sm"><?= e($r['period_from']) ?> → <?= e($r['period_to']) ?></td>
                    <td><?= $r['rating_overall'] ? str_repeat('★', (int)$r['rating_overall']) : '—' ?></td>
                    <td class="text-xs">
                        <?php foreach ($r['_decoded'] as $key => $score):
                            if (!isset($summary[$key])) continue; ?>
                            <span class="status-badge" style="--badge-color: #6366f1; margin:1px 2px;">
                                <?= e($summary[$key]['label']) ?>: <?= (int)$score ?>
                            </span>
                        <?php endforeach; ?>
                        <?php if (empty($r['_decoded'])): ?>
                            <span class="text-muted">No structured ratings recorded</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
