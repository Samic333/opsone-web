<?php
$pageTitle = 'My Safety Submissions';
$pageSubtitle = 'A history of reports you have securely filed.';
?>

<div style="max-width:1000px; margin:0 auto;">
    <div style="display:flex; justify-content:flex-end; margin-bottom:20px;">
        <a href="/safety/submit" class="btn btn-primary" style="background:#ef4444; border-color:#ef4444;">＋ File New Report</a>
    </div>

    <?php if (empty($reports)): ?>
        <div class="card">
            <div class="empty-state" style="padding:48px 0;">
                <div class="icon">✈️</div>
                <p>No safety reports submitted yet.</p>
                <p class="text-sm text-muted">Acentoza operates a Just Culture. Reporting hazards keeps our operations secure.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="grid-table">
            <div class="grid-table-header" style="grid-template-columns: 120px 100px 100px 1fr 140px;">
                <div>Reference No</div>
                <div>Date</div>
                <div>Type</div>
                <div>Summary Title</div>
                <div>Status</div>
            </div>
            
            <?php foreach ($reports as $r): ?>
            <div class="grid-table-row" style="grid-template-columns: 120px 100px 100px 1fr 140px;">
                <div style="font-family:monospace; font-weight:600; font-size:13px;"><?= e($r['reference_no']) ?></div>
                <div class="text-muted text-sm"><?= $r['event_date'] ? date('d M Y', strtotime($r['event_date'])) : '—' ?></div>
                <div style="font-size:12px; font-weight:600; color:var(--accent-blue);"><?= e($r['report_type']) ?></div>
                <div style="font-weight:500;">
                    <?= e($r['title']) ?>
                    <?php if ($r['is_anonymous']): ?>
                        <span class="text-xs text-muted" title="Filed Anonymously">🕵️</span>
                    <?php endif; ?>
                </div>
                <div>
                    <?php
                        $color = '#6b7280';
                        if ($r['status'] === 'submitted') $color = '#3b82f6';
                        if ($r['status'] === 'under_review') $color = '#f59e0b';
                        if ($r['status'] === 'investigation') $color = '#ef4444';
                        if ($r['status'] === 'closed') $color = '#10b981';
                    ?>
                    <span class="status-badge" style="--badge-color:<?= $color ?>;"><?= ucfirst(str_replace('_', ' ', $r['status'])) ?></span>
                </div>
            </div>
            <div class="grid-table-row" style="grid-template-columns: 1fr; border-top:none; padding-top:0; padding-bottom:16px;">
                <p class="text-sm text-muted" style="margin:0;">
                    <em>Submitted on <?= date('d M Y H:i', strtotime($r['created_at'])) ?></em>
                </p>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

