<?php
$pageTitle   = 'Airline Onboarding';
$pageSubtitle = 'Manage onboarding requests for new airlines';
$headerAction = '<a href="/platform/onboarding/create" class="btn btn-primary">+ New Request</a>';
ob_start();
?>

<?php if (!empty($pending)): ?>
<h3 style="font-size:.9rem; font-weight:600; color:var(--accent-yellow); margin:0 0 1rem;">
    ⏳ Pending Review (<?= count($pending) ?>)
</h3>
<?php foreach ($pending as $req): ?>
<div class="card" style="margin-bottom:1rem; border-left:3px solid #f59e0b;">
    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
        <div>
            <div style="font-weight:600;"><?= e($req['legal_name']) ?></div>
            <div style="font-size:12px; color:var(--text-muted);">
                <?= e($req['contact_name']) ?> · <?= e($req['contact_email']) ?>
                <?php if ($req['primary_country']): ?>
                · <?= e($req['primary_country']) ?>
                <?php endif; ?>
            </div>
            <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">
                Submitted: <?= formatDateTime($req['created_at']) ?>
                · Tier: <?= ucfirst($req['support_tier']) ?>
                <?php if ($req['expected_headcount']): ?>
                · <?= $req['expected_headcount'] ?> staff
                <?php endif; ?>
            </div>
        </div>
        <a href="/platform/onboarding/<?= $req['id'] ?>" class="btn btn-outline" style="font-size:11px; padding:4px 12px;">
            Review →
        </a>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($approved)): ?>
<h3 style="font-size:.9rem; font-weight:600; color:#10b981; margin:1.5rem 0 1rem;">
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
        </div>
        <a href="/platform/onboarding/<?= $req['id'] ?>" class="btn btn-primary" style="font-size:11px; padding:4px 12px;">
            Provision →
        </a>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($provisioned)): ?>
<h3 style="font-size:.9rem; font-weight:600; color:var(--text-muted); margin:1.5rem 0 1rem;">
    ✅ Provisioned (<?= count($provisioned) ?>)
</h3>
<div class="table-wrap">
    <table>
        <thead><tr><th>Airline</th><th>Tenant ID</th><th>Provisioned</th></tr></thead>
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

<?php if (empty($pending) && empty($approved) && empty($provisioned)): ?>
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
