<?php
/**
 * Standby Pool view
 * Variables: $pool (from RosterModel::getStandbyPool), $date
 */
$dutyTypes = RosterModel::dutyTypes();
?>
<style>
.pool-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
.pool-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 10px; padding: 16px; }
.pool-card.ok   { border-left: 4px solid #10b981; }
.pool-card.warn { border-left: 4px solid #f59e0b; }
.pool-card.crit { border-left: 4px solid #ef4444; }
.pool-role { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: var(--text-muted); margin-bottom: 4px; }
.pool-name { font-size: 15px; font-weight: 700; margin-bottom: 2px; }
.pool-meta { font-size: 12px; color: var(--text-muted); margin-bottom: 8px; }
.issue-list { list-style: none; padding: 0; margin: 6px 0 0; }
.issue-list li { font-size: 11px; padding: 2px 0; }
.issue-crit { color: #ef4444; }
.issue-warn { color: #f59e0b; }
.badge-ok   { display: inline-block; background: #10b981; color: #fff; border-radius: 4px; padding: 1px 7px; font-size: 10px; font-weight: 700; }
.badge-warn { display: inline-block; background: #f59e0b; color: #fff; border-radius: 4px; padding: 1px 7px; font-size: 10px; font-weight: 700; }
.badge-crit { display: inline-block; background: #ef4444; color: #fff; border-radius: 4px; padding: 1px 7px; font-size: 10px; font-weight: 700; }
</style>

<!-- Date picker -->
<form method="GET" action="/roster/standby" style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
    <label style="font-size:13px;font-weight:600;">Date:</label>
    <input type="date" name="date" value="<?= e($date) ?>" style="padding:6px 10px;border-radius:6px;border:1px solid var(--border);background:var(--bg-card);color:var(--text);">
    <button type="submit" class="btn btn-sm btn-primary">View</button>
    <a href="/roster/standby" class="btn btn-sm btn-outline">Today</a>
    <a href="/roster" class="btn btn-sm btn-outline" style="margin-left:auto;">← Roster Grid</a>
</form>

<?php if (empty($pool)): ?>
<div class="card">
    <div class="empty-state">
        <div class="icon">📋</div>
        <p>No crew on standby for <?= e(date('D, d M Y', strtotime($date))) ?>.</p>
        <a href="/roster/assign" class="btn btn-sm btn-primary" style="margin-top:8px;">Assign Standby →</a>
    </div>
</div>
<?php else: ?>

<div style="margin-bottom:12px;font-size:13px;color:var(--text-muted);">
    <?= count($pool) ?> crew member<?= count($pool) !== 1 ? 's' : '' ?> on standby.
    Cards with a <span style="color:#ef4444;font-weight:700;">red</span> border have compliance issues and should not be deployed.
</div>

<div class="pool-grid">
<?php foreach ($pool as $member):
    $c = $member['compliance'];
    $severity = $c['severity'] ?? 'ok';
    $cardClass = $severity === 'critical' ? 'crit' : ($severity === 'warning' ? 'warn' : 'ok');
?>
<div class="pool-card <?= $cardClass ?>">
    <div class="pool-role"><?= e($member['role_name']) ?></div>
    <div class="pool-name"><?= e($member['user_name']) ?></div>
    <div class="pool-meta">
        <?php if ($member['employee_id']): ?>ID: <?= e($member['employee_id']) ?> &nbsp;·&nbsp;<?php endif; ?>
        <?= e($member['duty_code'] ?: 'SBY') ?>
        <?php if ($member['notes']): ?>&nbsp;·&nbsp;<?= e($member['notes']) ?><?php endif; ?>
    </div>

    <?php if (!$c): ?>
        <span class="badge-ok">✓ Compliant</span>
    <?php elseif ($severity === 'warning'): ?>
        <span class="badge-warn">⚠ Expiry Warning</span>
        <ul class="issue-list">
            <?php foreach ($c['issues'] as $issue): ?>
            <li class="issue-warn">⚠ <?= e($issue) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <span class="badge-crit">✕ Non-Compliant</span>
        <ul class="issue-list">
            <?php foreach ($c['issues'] as $issue): ?>
            <li class="issue-crit">✕ <?= e($issue) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <div style="margin-top:12px;">
        <a href="/roster/suggest/<?= $member['user_id'] ?>?date=<?= urlencode($date) ?>"
           class="btn btn-sm btn-outline" style="font-size:11px;">
            Find Replacement
        </a>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
