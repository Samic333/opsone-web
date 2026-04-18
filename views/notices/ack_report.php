<?php /** OpsOne — Notice Acknowledgement Report */ ?>

<?php
$priorityColors = ['normal' => '#6b7280', 'urgent' => '#f59e0b', 'critical' => '#ef4444'];
$pc = $priorityColors[$notice['priority'] ?? 'normal'] ?? '#6b7280';
$barColor = $pct >= 80 ? '#10b981' : ($pct >= 50 ? '#f59e0b' : '#ef4444');
?>

<!-- Notice summary card -->
<div class="card" style="border-left:4px solid <?= $pc ?>; margin-bottom:24px;">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;">
        <div>
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                <span class="status-badge" style="--badge-color:<?= $pc ?>"><?= ucfirst(e($notice['priority'] ?? 'normal')) ?></span>
                <span class="status-badge" style="--badge-color:#6b7280"><?= ucfirst(e($notice['category'] ?? 'general')) ?></span>
                <?php if ($notice['requires_ack']): ?>
                    <span class="status-badge" style="--badge-color:#6366f1">⚠ Acknowledgement Required</span>
                <?php endif; ?>
            </div>
            <div class="text-sm text-muted">
                Published <?= formatDate($notice['published_at'] ?? $notice['created_at']) ?>
                <?php if ($notice['expires_at']): ?>
                    · Expires <?= formatDate($notice['expires_at']) ?>
                <?php endif; ?>
                · Created by <?= e($notice['author_name'] ?? 'System') ?>
            </div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:28px; font-weight:800; color:<?= $barColor ?>;"><?= $pct ?>%</div>
            <div style="font-size:12px; color:var(--text-muted);">Crew Compliance</div>
        </div>
    </div>
</div>

<!-- Stats row -->
<div class="stats-grid" style="margin-bottom:24px;">
    <div class="stat-card blue">
        <div class="stat-label">Total Crew in Scope</div>
        <div class="stat-value"><?= $totalCrew ?></div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Acknowledged</div>
        <div class="stat-value"><?= $ackedCount ?></div>
    </div>
    <div class="stat-card <?= $pendingCount > 0 ? 'red' : 'blue' ?>">
        <div class="stat-label">Pending</div>
        <div class="stat-value"><?= $pendingCount ?></div>
    </div>
    <div class="stat-card <?= $pct >= 80 ? 'green' : ($pct >= 50 ? 'yellow' : 'red') ?>">
        <div class="stat-label">Compliance Rate</div>
        <div class="stat-value"><?= $pct ?>%</div>
    </div>
</div>

<!-- Compliance progress bar -->
<div class="card" style="padding:16px 20px; margin-bottom:24px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
        <span class="text-sm" style="font-weight:600;">Overall Acknowledgement Progress</span>
        <span style="font-size:13px; color:var(--text-muted);"><?= $ackedCount ?> of <?= $totalCrew ?> crew signed off</span>
    </div>
    <div style="background:var(--bg-secondary);border-radius:6px;height:12px;overflow:hidden;">
        <div style="width:<?= $pct ?>%;height:100%;background:<?= $barColor ?>;border-radius:6px;transition:width .4s;"></div>
    </div>
    <?php if ($pendingCount > 0): ?>
        <p style="font-size:12px;color:#f59e0b;margin:8px 0 0;">⚠ <?= $pendingCount ?> crew member<?= $pendingCount !== 1 ? 's' : '' ?> have not yet acknowledged this notice.</p>
    <?php else: ?>
        <p style="font-size:12px;color:#10b981;margin:8px 0 0;">✓ All crew members in scope have acknowledged this notice.</p>
    <?php endif; ?>
</div>

<!-- Crew table -->
<div class="card">
    <div class="card-header">
        <div class="card-title">Crew Acknowledgement Status</div>
        <div style="display:flex;gap:8px;align-items:center;">
            <?php if ($pendingCount > 0): ?>
                <span class="status-badge" style="--badge-color:#ef4444;"><?= $pendingCount ?> Pending</span>
            <?php endif; ?>
            <span class="status-badge" style="--badge-color:#10b981;"><?= $ackedCount ?> Acknowledged</span>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Crew Member</th>
                    <th>Employee ID</th>
                    <th>Role(s)</th>
                    <th>Read At</th>
                    <th>Acknowledged At</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($crew)): ?>
                <tr><td colspan="6">
                    <div class="empty-state">
                        <div class="icon">👥</div>
                        <h3>No Crew in Scope</h3>
                        <p>No active crew members with mobile access match this notice's role visibility.</p>
                    </div>
                </td></tr>
            <?php else: ?>
                <?php foreach ($crew as $member): ?>
                <tr>
                    <td><strong><?= e($member['name']) ?></strong></td>
                    <td class="text-sm text-muted"><code><?= e($member['employee_id'] ?? '—') ?></code></td>
                    <td style="font-size:12px;color:var(--text-muted);max-width:180px;"><?= e($member['user_roles'] ?? '—') ?></td>
                    <td class="text-sm text-muted">
                        <?= !empty($member['read_at']) ? date('d M Y H:i', strtotime($member['read_at'])) : '<span style="color:var(--text-muted)">Not read</span>' ?>
                    </td>
                    <td>
                        <?php if (!empty($member['acknowledged_at'])): ?>
                            <span style="color:#10b981; font-weight:600; font-size:13px;">
                                <?= date('d M Y', strtotime($member['acknowledged_at'])) ?>
                                <span style="font-weight:400; color:var(--text-muted);"> <?= date('H:i', strtotime($member['acknowledged_at'])) ?> UTC</span>
                            </span>
                        <?php else: ?>
                            <span style="color:#ef4444; font-size:13px;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($member['acknowledged_at'])): ?>
                            <span class="status-badge" style="--badge-color:#10b981;">✓ Acknowledged</span>
                        <?php elseif (!empty($member['read_at'])): ?>
                            <span class="status-badge" style="--badge-color:#f59e0b;">Read — Pending Ack</span>
                        <?php else: ?>
                            <span class="status-badge" style="--badge-color:#ef4444;">Not Read</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
