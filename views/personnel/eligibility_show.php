<?php
/**
 * Single-staff eligibility detail.
 * Vars: $user, $eligibility, $required, $documents, $licenses
 */
$color = ['eligible' => '#10b981', 'warning' => '#f59e0b', 'blocked' => '#ef4444'][$eligibility['status']] ?? '#6b7280';
?>
<div class="card" style="border-left:4px solid <?= $color ?>;">
    <div class="card-header">
        <div>
            <div class="card-title"><?= e($user['name']) ?></div>
            <div class="text-xs text-muted">Assignment readiness</div>
        </div>
        <span class="status-badge" style="--badge-color:<?= $color ?>;font-size:14px;padding:6px 14px;">
            <?= strtoupper($eligibility['status']) ?>
        </span>
    </div>

    <?php if (!empty($eligibility['reasons'])): ?>
    <div>
        <h4 style="margin-top:8px;">Reasons</h4>
        <ul>
        <?php foreach ($eligibility['reasons'] as $r): ?>
            <li><?= e($r) ?></li>
        <?php endforeach; ?>
        </ul>
    </div>
    <?php else: ?>
    <p class="text-muted">No issues detected. Staff is eligible for assignment.</p>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header"><div class="card-title">Required Documents</div></div>
    <?php if (empty($required)): ?>
        <p class="text-muted">No role-based requirements defined.</p>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Document</th><th>Mandatory</th><th>Warning</th><th>Critical</th><th>Description</th></tr></thead>
            <tbody>
            <?php foreach ($required as $r): ?>
            <tr>
                <td><strong><?= e($r['doc_label']) ?></strong> <span class="text-xs text-muted">(<?= e($r['doc_type']) ?>)</span></td>
                <td><?= ((int) $r['is_mandatory']) ? '✅' : '—' ?></td>
                <td><?= (int) $r['warning_days'] ?>d</td>
                <td><?= (int) $r['critical_days'] ?>d</td>
                <td style="font-size:12px;"><?= e($r['description'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header"><div class="card-title">Detail Breakdown</div></div>
    <?php foreach (['expired','expiring_soon','missing_required','pending_approval'] as $key):
        $rows = $eligibility['details'][$key] ?? [];
        if (empty($rows)) continue;
        $title = ['expired'=>'Expired','expiring_soon'=>'Expiring Soon','missing_required'=>'Missing Required','pending_approval'=>'Pending Approval'][$key];
    ?>
    <h4 style="margin-top:10px;"><?= $title ?></h4>
    <ul>
    <?php foreach ($rows as $row): ?>
        <li>
        <?php if ($key === 'missing_required'): ?>
            <strong><?= e($row['label']) ?></strong> <span class="text-xs text-muted">(<?= e($row['doc_type']) ?>)</span>
        <?php elseif ($key === 'expired' || $key === 'expiring_soon'): ?>
            <strong><?= e($row['label']) ?></strong> — expires <?= e($row['expiry_date']) ?>
            <?php if (isset($row['days'])): ?>(<?= (int) $row['days'] ?>d)<?php endif; ?>
        <?php elseif ($key === 'pending_approval'): ?>
            <?= e($row['kind'] ?? 'item') ?>
            <?php if (!empty($row['doc_type'])): ?>— <?= e($row['doc_type']) ?><?php endif; ?>
            <?php if (!empty($row['target_entity'])): ?>— <?= e($row['target_entity']) ?><?php endif; ?>
        <?php endif; ?>
        </li>
    <?php endforeach; ?>
    </ul>
    <?php endforeach; ?>
    <div class="text-xs text-muted" style="margin-top:10px;">Checked at <?= e($eligibility['checked_at']) ?></div>
</div>

<div class="card">
    <div class="card-header"><div class="card-title">Active Documents</div></div>
    <?php if (empty($documents)): ?>
        <p class="text-muted">No documents uploaded.</p>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Title</th><th>Type</th><th>Expiry</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($documents as $d): ?>
            <tr>
                <td><?= e($d['doc_title']) ?></td>
                <td><?= e($d['doc_type']) ?></td>
                <td><?= e($d['expiry_date'] ?? '—') ?></td>
                <td><?= e($d['status']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    <p style="margin-top:8px;">
        <a href="/personnel/documents/user/<?= (int) $user['id'] ?>" class="btn btn-outline btn-sm">Manage Documents</a>
    </p>
</div>
