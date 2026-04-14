<?php
$pageTitle   = 'Airline Onboarding';
$pageSubtitle = 'Manage onboarding requests for new airlines';
$headerAction = '<a href="/platform/onboarding/create" class="btn btn-primary">+ New Request</a>';
ob_start();

// Summary counts for the quick stats
$totalPending  = count($pending ?? []);
$totalInReview = count($inReview ?? []);
$totalApproved = count($approved ?? []);
?>

<!-- Quick stats -->
<div class="stats-grid" style="margin-bottom:1.5rem;">
    <div class="stat-card yellow">
        <div class="stat-value"><?= $totalPending ?></div>
        <div class="stat-label">Awaiting Review</div>
    </div>
    <div class="stat-card" style="border-left:3px solid #6366f1;">
        <div class="stat-value" style="color:#6366f1;"><?= $totalInReview ?></div>
        <div class="stat-label">In Review</div>
    </div>
    <div class="stat-card green">
        <div class="stat-value"><?= $totalApproved ?></div>
        <div class="stat-label">Approved — Pending Provision</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= count($provisioned ?? []) ?></div>
        <div class="stat-label">Provisioned</div>
    </div>
</div>

<?php if (!empty($pending)): ?>
<h3 style="font-size:.9rem; font-weight:600; color:#f59e0b; margin:0 0 .75rem;">
    ⏳ Awaiting Review (<?= count($pending) ?>)
</h3>
<?php foreach ($pending as $req): ?>
<div class="card" style="margin-bottom:.75rem; border-left:3px solid #f59e0b;">
    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
        <div>
            <div style="font-weight:600;"><?= e($req['legal_name']) ?></div>
            <div style="font-size:12px; color:var(--text-muted);">
                <?= e($req['contact_name']) ?> · <?= e($req['contact_email']) ?>
                <?php if ($req['primary_country']): ?> · <?= e($req['primary_country']) ?><?php endif; ?>
            </div>
            <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">
                Submitted: <?= formatDateTime($req['created_at']) ?>
                · Tier: <?= ucfirst($req['support_tier']) ?>
                <?php if ($req['expected_headcount']): ?> · <?= $req['expected_headcount'] ?> staff<?php endif; ?>
            </div>
        </div>
        <a href="/platform/onboarding/<?= $req['id'] ?>" class="btn btn-outline" style="font-size:11px; padding:4px 12px; white-space:nowrap;">
            Review →
        </a>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($inReview)): ?>
<h3 style="font-size:.9rem; font-weight:600; color:#6366f1; margin:1.25rem 0 .75rem;">
    🔍 In Review (<?= count($inReview) ?>)
</h3>
<?php foreach ($inReview as $req): ?>
<div class="card" style="margin-bottom:.75rem; border-left:3px solid #6366f1;">
    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
        <div>
            <div style="font-weight:600;"><?= e($req['legal_name']) ?></div>
            <div style="font-size:12px; color:var(--text-muted);">
                <?= e($req['contact_name']) ?> · <?= e($req['contact_email']) ?>
                <?php if ($req['primary_country']): ?> · <?= e($req['primary_country']) ?><?php endif; ?>
            </div>
            <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">
                In review since: <?= formatDateTime($req['reviewed_at'] ?? $req['created_at']) ?>
                <?php if ($req['reviewed_by_name']): ?> · by <?= e($req['reviewed_by_name']) ?><?php endif; ?>
            </div>
        </div>
        <a href="/platform/onboarding/<?= $req['id'] ?>" class="btn btn-outline"
           style="font-size:11px; padding:4px 12px; white-space:nowrap; border-color:#6366f1; color:#6366f1;">
            Continue →
        </a>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($approved)): ?>
<h3 style="font-size:.9rem; font-weight:600; color:#10b981; margin:1.25rem 0 .75rem;">
    ✓ Approved — Pending Provisioning (<?= count($approved) ?>)
</h3>
<?php foreach ($approved as $req): ?>
<div class="card" style="margin-bottom:.75rem; border-left:3px solid #10b981;">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <strong><?= e($req['legal_name']) ?></strong>
            <span style="font-size:12px; color:var(--text-muted); margin-left:8px;">
                Approved: <?= formatDate($req['reviewed_at']) ?>
            </span>
            <?php if ($req['support_tier'] !== 'standard'): ?>
            <span style="font-size:11px; margin-left:6px; padding:2px 7px; background:rgba(99,102,241,0.1); color:#6366f1; border-radius:4px;">
                <?= ucfirst($req['support_tier']) ?>
            </span>
            <?php endif; ?>
        </div>
        <a href="/platform/onboarding/<?= $req['id'] ?>" class="btn btn-primary" style="font-size:11px; padding:4px 12px; white-space:nowrap;">
            🚀 Provision →
        </a>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($rejected)): ?>
<h3 style="font-size:.9rem; font-weight:600; color:var(--text-muted); margin:1.25rem 0 .75rem;">
    ✗ Rejected (<?= count($rejected) ?>)
</h3>
<div class="table-wrap">
    <table>
        <thead><tr><th>Airline</th><th>Contact</th><th>Rejected By</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($rejected as $req): ?>
        <tr>
            <td><a href="/platform/onboarding/<?= $req['id'] ?>" style="color:var(--text);"><?= e($req['legal_name']) ?></a></td>
            <td style="font-size:12px; color:var(--text-muted);"><?= e($req['contact_name']) ?></td>
            <td style="font-size:12px; color:var(--text-muted);"><?= e($req['reviewed_by_name'] ?? '—') ?></td>
            <td style="font-size:12px; color:var(--text-muted);"><?= formatDate($req['reviewed_at']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if (!empty($provisioned)): ?>
<h3 style="font-size:.9rem; font-weight:600; color:var(--text-muted); margin:1.25rem 0 .75rem;">
    ✅ Provisioned (<?= count($provisioned) ?>)
</h3>
<div class="table-wrap">
    <table>
        <thead><tr><th>Airline</th><th>Tenant</th><th>Provisioned</th></tr></thead>
        <tbody>
        <?php foreach ($provisioned as $req): ?>
        <tr>
            <td><?= e($req['legal_name']) ?></td>
            <td>
                <?php if ($req['tenant_id']): ?>
                <a href="/tenants/<?= $req['tenant_id'] ?>">#<?= $req['tenant_id'] ?></a>
                <?php else: ?>—<?php endif; ?>
            </td>
            <td style="font-size:12px; color:var(--text-muted);"><?= formatDate($req['reviewed_at']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if (empty($pending) && empty($inReview) && empty($approved) && empty($provisioned) && empty($rejected)): ?>
<div class="card">
    <div class="empty-state">
        <div class="icon">✈</div>
        <h3>No Onboarding Requests</h3>
        <p>Create a request to start the airline provisioning workflow.</p>
        <a href="/platform/onboarding/create" class="btn btn-primary mt-2">+ Create Request</a>
    </div>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/app.php'; ?>
