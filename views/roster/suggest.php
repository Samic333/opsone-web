<?php
/**
 * Replacement Suggestions view
 * Variables: $crewMember, $currentDuty, $crewCompliance, $suggestions, $date, $dutyTypes
 */
$standbyList  = $suggestions['standby']  ?? [];
$availList    = $suggestions['available'] ?? [];
$totalFound   = count($standbyList) + count($availList);
?>
<style>
.suggest-header { background: var(--bg-card); border: 1px solid var(--border); border-radius: 10px; padding: 16px 20px; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 20px; }
.suggest-person { flex: 1; }
.suggest-person .name { font-size: 18px; font-weight: 700; margin-bottom: 2px; }
.suggest-person .meta { font-size: 12px; color: var(--text-muted); }
.suggest-issue { flex: 1; }
.issue-box { border-radius: 8px; padding: 10px 14px; }
.issue-box.critical { background: rgba(239,68,68,.08); border: 1px solid rgba(239,68,68,.3); }
.issue-box.warning  { background: rgba(245,158,11,.08); border: 1px solid rgba(245,158,11,.3); }
.issue-box ul { margin: 4px 0 0; padding-left: 16px; font-size: 12px; }
.issue-box ul li { margin-bottom: 2px; }
.issue-box.critical ul li { color: #ef4444; }
.issue-box.warning ul li  { color: #f59e0b; }
.suggest-section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted); margin: 20px 0 10px; border-bottom: 1px solid var(--border); padding-bottom: 6px; }
.candidate-table { width: 100%; border-collapse: collapse; }
.candidate-table th, .candidate-table td { border: 1px solid var(--border); padding: 8px 10px; font-size: 13px; }
.candidate-table th { background: var(--bg-secondary); font-weight: 600; font-size: 11px; text-transform: uppercase; }
.badge-standby { background: #f59e0b; color:#fff; border-radius:4px; padding:1px 7px; font-size:10px; font-weight:700; }
.badge-off     { background: #6b7280; color:#fff; border-radius:4px; padding:1px 7px; font-size:10px; font-weight:700; }
.badge-ok      { background: #10b981; color:#fff; border-radius:4px; padding:1px 7px; font-size:10px; font-weight:700; }
.badge-warn    { background: #f59e0b; color:#fff; border-radius:4px; padding:1px 7px; font-size:10px; font-weight:700; }
</style>

<!-- Back nav -->
<div style="margin-bottom:16px;">
    <a href="/roster?year=<?= date('Y', strtotime($date)) ?>&month=<?= (int)date('n', strtotime($date)) ?>"
       class="btn btn-sm btn-outline">← Back to Roster</a>
    <a href="/roster/standby?date=<?= urlencode($date) ?>"
       class="btn btn-sm btn-outline" style="margin-left:8px;">Standby Pool</a>
</div>

<!-- Original crew member + compliance issue -->
<div class="suggest-header">
    <div class="suggest-person">
        <div class="name"><?= e($crewMember['user_name']) ?></div>
        <div class="meta">
            <?= e($crewMember['role_name']) ?>
            <?php if ($crewMember['employee_id']): ?> &nbsp;·&nbsp; ID: <?= e($crewMember['employee_id']) ?><?php endif; ?>
            &nbsp;·&nbsp; <?= e(date('D, d M Y', strtotime($date))) ?>
        </div>
        <?php if ($currentDuty):
            $dt = $dutyTypes[$currentDuty['duty_type']] ?? ['label' => ucfirst($currentDuty['duty_type']), 'color' => '#6b7280', 'code' => '?'];
        ?>
        <div style="margin-top:8px;">
            <span style="background:<?= $dt['color'] ?>;color:#fff;border-radius:4px;padding:2px 9px;font-size:11px;font-weight:700;">
                <?= e($dt['label']) ?> — <?= e($currentDuty['duty_code'] ?? $dt['code']) ?>
            </span>
            <?php if ($currentDuty['notes']): ?>
                <span style="font-size:12px;color:var(--text-muted);margin-left:6px;"><?= e($currentDuty['notes']) ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="suggest-issue">
        <?php if ($crewCompliance): ?>
        <div class="issue-box <?= $crewCompliance['severity'] ?>">
            <strong style="font-size:12px;"><?= $crewCompliance['severity'] === 'critical' ? '✕ Compliance Issue' : '⚠ Compliance Warning' ?></strong>
            <ul>
                <?php foreach ($crewCompliance['issues'] as $issue): ?>
                <li><?= e($issue) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php else: ?>
        <div class="issue-box" style="background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.3);">
            <strong style="font-size:12px;color:#10b981;">✓ No compliance issues</strong>
            <p style="font-size:12px;color:var(--text-muted);margin:4px 0 0;">This crew member is fully compliant. Replacement suggestions are still shown below.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($totalFound === 0): ?>
<div class="card">
    <div class="empty-state">
        <div class="icon">🔍</div>
        <p>No compliant replacement candidates found for this date.</p>
        <p style="font-size:12px;color:var(--text-muted);">All available crew may have compliance issues, or there are no crew rostered as standby/off.</p>
        <a href="/roster/assign" class="btn btn-sm btn-primary" style="margin-top:8px;">Assign Standby →</a>
    </div>
</div>
<?php else: ?>

<!-- Standby candidates (preferred) -->
<?php if (!empty($standbyList)): ?>
<div class="suggest-section-title">On Standby — Preferred Replacements (<?= count($standbyList) ?>)</div>
<div class="card" style="padding:0;">
    <table class="candidate-table">
        <thead>
            <tr>
                <th>Name</th><th>Role</th><th>Duty</th><th>Compliance</th><th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($standbyList as $c): ?>
        <tr>
            <td>
                <strong><?= e($c['user_name']) ?></strong>
                <?php if ($c['employee_id']): ?><br><span style="font-size:11px;color:var(--text-muted);"><?= e($c['employee_id']) ?></span><?php endif; ?>
            </td>
            <td style="font-size:12px;"><?= e($c['role_name']) ?></td>
            <td><span class="badge-standby">SBY</span>
                <?php if ($c['duty_notes']): ?><br><span style="font-size:11px;color:var(--text-muted);"><?= e($c['duty_notes']) ?></span><?php endif; ?>
            </td>
            <td>
                <?php if (!$c['compliance']): ?>
                    <span class="badge-ok">✓ OK</span>
                <?php else: ?>
                    <span class="badge-warn">⚠ Warning</span>
                    <ul style="margin:4px 0 0;padding-left:14px;font-size:11px;color:#f59e0b;">
                        <?php foreach ($c['compliance']['issues'] as $iss): ?>
                        <li><?= e($iss) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </td>
            <td>
                <a href="/roster/assign?prefill_user=<?= $c['user_id'] ?>&prefill_date=<?= urlencode($date) ?>&prefill_duty=flight"
                   class="btn btn-sm btn-primary">Assign →</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Available (off/rest/unrostered) candidates -->
<?php if (!empty($availList)): ?>
<div class="suggest-section-title">Available (Off / Rest / Unrostered) — Fallback Options (<?= count($availList) ?>)</div>
<div class="card" style="padding:0;">
    <table class="candidate-table">
        <thead>
            <tr>
                <th>Name</th><th>Role</th><th>Current Duty</th><th>Compliance</th><th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($availList as $c):
            $dutyLabel = $c['duty_type'] === 'unrostered' ? 'Unrostered' : ucfirst($c['duty_type']);
        ?>
        <tr>
            <td>
                <strong><?= e($c['user_name']) ?></strong>
                <?php if ($c['employee_id']): ?><br><span style="font-size:11px;color:var(--text-muted);"><?= e($c['employee_id']) ?></span><?php endif; ?>
            </td>
            <td style="font-size:12px;"><?= e($c['role_name']) ?></td>
            <td><span class="badge-off"><?= e($dutyLabel) ?></span></td>
            <td>
                <?php if (!$c['compliance']): ?>
                    <span class="badge-ok">✓ OK</span>
                <?php else: ?>
                    <span class="badge-warn">⚠ Warning</span>
                    <ul style="margin:4px 0 0;padding-left:14px;font-size:11px;color:#f59e0b;">
                        <?php foreach ($c['compliance']['issues'] as $iss): ?>
                        <li><?= e($iss) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </td>
            <td>
                <a href="/roster/assign?prefill_user=<?= $c['user_id'] ?>&prefill_date=<?= urlencode($date) ?>&prefill_duty=flight"
                   class="btn btn-sm btn-outline">Assign →</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php endif; ?>

<p style="font-size:11px;color:var(--text-muted);margin-top:16px;">
    Crew with <strong>critical</strong> compliance issues (expired licenses or medical) are excluded from suggestions.
    Crew with <strong>warnings</strong> (expiring within 30 days) are shown but flagged.
    The "Assign" button pre-fills the duty assignment form — confirm and save to apply.
</p>
