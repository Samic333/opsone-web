<?php
/**
 * Documents for a single staff member with approval actions.
 * Vars: $user, $documents, $required
 */
?>
<div class="card">
    <div class="card-header">
        <div class="card-title">Required Documents for Role</div>
    </div>
    <?php if (empty($required)): ?>
        <p class="text-muted">No role-specific document requirements defined.</p>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Document</th><th>Type</th><th>Mandatory</th><th>Warn (d)</th><th>Critical (d)</th></tr></thead>
            <tbody>
            <?php foreach ($required as $r): ?>
            <tr>
                <td><strong><?= e($r['doc_label']) ?></strong></td>
                <td><code><?= e($r['doc_type']) ?></code></td>
                <td><?= ((int) $r['is_mandatory']) ? '✅ Yes' : 'Optional' ?></td>
                <td><?= (int) $r['warning_days'] ?></td>
                <td><?= (int) $r['critical_days'] ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">All Documents</div>
    </div>
    <?php if (empty($documents)): ?>
        <div class="empty-state"><div class="icon">📄</div><h3>No documents yet</h3>
            <p>Staff may upload via the iPad app or web self-service; uploads arrive here as pending.</p>
        </div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Title</th><th>Type</th><th>Number</th><th>Issue</th><th>Expiry</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($documents as $d):
                $statusColor = [
                    'valid' => '#10b981', 'pending_approval' => '#f59e0b',
                    'expired' => '#ef4444', 'rejected' => '#dc2626', 'revoked' => '#6b7280',
                ][$d['status']] ?? '#6b7280';
            ?>
            <tr>
                <td><strong><?= e($d['doc_title']) ?></strong>
                    <?php if (!empty($d['notes'])): ?><br><span class="text-xs text-muted"><?= e($d['notes']) ?></span><?php endif; ?>
                </td>
                <td><?= e($d['doc_type']) ?></td>
                <td><code><?= e($d['doc_number'] ?? '—') ?></code></td>
                <td><?= e($d['issue_date'] ?? '—') ?></td>
                <td><?= e($d['expiry_date'] ?? '—') ?></td>
                <td><span class="status-badge" style="--badge-color:<?= $statusColor ?>">
                    <?= ucwords(str_replace('_',' ',$d['status'])) ?>
                </span>
                <?php if (!empty($d['rejection_reason'])): ?>
                    <br><span class="text-xs" style="color:var(--accent-red);"><?= e($d['rejection_reason']) ?></span>
                <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($d['file_path'])): ?>
                    <a href="/personnel/documents/<?= (int) $d['id'] ?>/download" class="btn btn-outline btn-xs">Download</a>
                    <?php endif; ?>
                    <?php if ($d['status'] === 'pending_approval'): ?>
                    <form method="POST" action="/personnel/documents/<?= (int) $d['id'] ?>/approve" style="display:inline;">
                        <?= csrfField() ?>
                        <button class="btn btn-primary btn-xs">Approve</button>
                    </form>
                    <button type="button" class="btn btn-outline btn-xs" onclick="document.getElementById('rejectForm<?= (int) $d['id'] ?>').style.display='block';">Reject</button>
                    <form id="rejectForm<?= (int) $d['id'] ?>" method="POST" action="/personnel/documents/<?= (int) $d['id'] ?>/reject" style="display:none;margin-top:6px;">
                        <?= csrfField() ?>
                        <input type="text" name="reason" placeholder="Rejection reason (required)" class="form-control" required>
                        <button class="btn btn-danger btn-xs" style="margin-top:4px;">Confirm Reject</button>
                    </form>
                    <?php elseif ($d['status'] === 'valid'): ?>
                    <form method="POST" action="/personnel/documents/<?= (int) $d['id'] ?>/revoke" style="display:inline;"
                          onsubmit="return confirm('Revoke this document? Staff will need to re-submit.');">
                        <?= csrfField() ?>
                        <button class="btn btn-outline btn-xs">Revoke</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
