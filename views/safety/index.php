<?php
$pageTitle = 'Safety Management';
$pageSubtitle = 'Aviation Safety, Hazards, and Compliance Reports.';
?>

<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:24px;">
    <div class="card" style="display:flex; flex-direction:column; align-items:center; padding:24px 16px;">
        <span class="text-muted text-sm" style="text-transform:uppercase; letter-spacing:.05em; font-weight:600;">Open Reports</span>
        <span style="font-size:36px; font-weight:700; color:var(--text-primary); margin-top:8px;"><?= $openCount ?></span>
    </div>
    <div class="card" style="display:flex; flex-direction:column; align-items:center; padding:24px 16px;">
        <span class="text-muted text-sm" style="text-transform:uppercase; letter-spacing:.05em; font-weight:600;">Investigations</span>
        <span style="font-size:36px; font-weight:700; color:#ef4444; margin-top:8px;"><?= $investCount ?></span>
    </div>
    <div class="card" style="display:flex; flex-direction:column; align-items:center; padding:24px 16px;">
        <span class="text-muted text-sm" style="text-transform:uppercase; letter-spacing:.05em; font-weight:600;">Closed</span>
        <span style="font-size:36px; font-weight:700; color:#10b981; margin-top:8px;"><?= $closedCount ?></span>
    </div>
</div>

<div class="nav-bar" style="margin-bottom:16px;">
    <a href="/safety?status=open" class="btn btn-sm <?= $statusFilter === 'open' ? 'btn-primary' : 'btn-ghost' ?>">Open Reports</a>
    <a href="/safety?status=investigation" class="btn btn-sm <?= $statusFilter === 'investigation' ? 'btn-primary' : 'btn-ghost' ?>">Investigations</a>
    <a href="/safety?status=closed" class="btn btn-sm <?= $statusFilter === 'closed' ? 'btn-primary' : 'btn-ghost' ?>">Closed</a>
    <a href="/safety?status=all" class="btn btn-sm <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-ghost' ?>">All History</a>
</div>

<div class="card" style="padding:0; overflow:hidden;">
    <?php if (empty($reports)): ?>
        <div class="empty-state" style="padding:48px 0;">
            <div class="icon">🔍</div>
            <p>No safety reports found for this filter.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:120px;">Ref No.</th>
                        <th>Type</th>
                        <th>Reporter</th>
                        <th>Event Date</th>
                        <th>Summary</th>
                        <th style="width:120px;">Severity</th>
                        <th style="width:120px;">Status</th>
                        <th style="width:80px; text-align:right;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $r): ?>
                    <tr>
                        <td style="font-family:monospace; font-weight:600; font-size:13px;">
                            <a href="/safety/report/<?= $r['id'] ?>" style="color:var(--accent-blue); text-decoration:none;"><?= e($r['reference_no']) ?></a>
                        </td>
                        <td style="font-size:12px; font-weight:600; color:var(--text-secondary);"><?= e($r['report_type']) ?></td>
                        <td>
                            <?php if ($r['is_anonymous']): ?>
                                <span class="text-sm" style="color:var(--text-muted); font-style:italic;">🔒 Anonymous</span>
                            <?php else: ?>
                                <?= e($r['reporter_name']) ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted text-sm"><?= $r['event_date'] ? date('d M Y', strtotime($r['event_date'])) : '—' ?></td>
                        <td style="font-weight:500;"><?= e($r['title']) ?></td>
                        <td>
                            <?php
                                $sevColor = '#6b7280';
                                if ($r['severity'] === 'low') $sevColor = '#3b82f6';
                                if ($r['severity'] === 'medium') $sevColor = '#f59e0b';
                                if ($r['severity'] === 'high') $sevColor = '#f97316';
                                if ($r['severity'] === 'critical') $sevColor = '#ef4444';
                            ?>
                            <span class="status-badge" style="--badge-color:<?= $sevColor ?>;"><?= ucfirst($r['severity']) ?></span>
                        </td>
                        <td>
                            <?php
                                $color = '#6b7280';
                                if ($r['status'] === 'submitted') $color = '#3b82f6';
                                if ($r['status'] === 'under_review') $color = '#f59e0b';
                                if ($r['status'] === 'investigation') $color = '#ef4444';
                                if ($r['status'] === 'closed') $color = '#10b981';
                            ?>
                            <span class="status-badge" style="--badge-color:<?= $color ?>;"><?= ucfirst(str_replace('_', ' ', $r['status'])) ?></span>
                        </td>
                        <td style="text-align:right;">
                            <a href="/safety/report/<?= $r['id'] ?>" class="btn btn-xs btn-outline">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

