<?php
/**
 * Single change request review page.
 * Vars: $request, $payload, $supportingDoc
 */
$color = [
    'submitted'      => '#f59e0b',
    'under_review'   => '#3b82f6',
    'info_requested' => '#8b5cf6',
    'approved'       => '#10b981',
    'rejected'       => '#ef4444',
    'withdrawn'      => '#6b7280',
][$request['status']] ?? '#6b7280';
$isResolved = in_array($request['status'], ['approved','rejected','withdrawn'], true);
?>
<div class="card">
    <div class="card-header">
        <div class="card-title">Change Request #<?= (int) $request['id'] ?></div>
        <span class="status-badge" style="--badge-color:<?= $color ?>">
            <?= ucwords(str_replace('_',' ',$request['status'])) ?>
        </span>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div>
            <div class="text-xs text-muted">Staff</div>
            <div><strong><?= e($request['user_name']) ?></strong>
                <?php if (!empty($request['user_employee_id'])): ?>
                <span class="text-xs text-muted">(<?= e($request['user_employee_id']) ?>)</span>
                <?php endif; ?>
            </div>

            <div class="text-xs text-muted" style="margin-top:10px;">Submitted by</div>
            <div><?= e($request['requester_name'] ?? '—') ?></div>

            <div class="text-xs text-muted" style="margin-top:10px;">Target</div>
            <div><code><?= e($request['target_entity']) ?></code>
                <?php if (!empty($request['target_id'])): ?>
                    (record #<?= (int) $request['target_id'] ?>)
                <?php endif; ?>
            </div>

            <div class="text-xs text-muted" style="margin-top:10px;">Change type</div>
            <div><?= e($request['change_type']) ?></div>
        </div>
        <div>
            <div class="text-xs text-muted">Submitted at</div>
            <div><?= formatDateTime($request['submitted_at']) ?></div>

            <?php if (!empty($request['reviewed_at'])): ?>
            <div class="text-xs text-muted" style="margin-top:10px;">Reviewed at</div>
            <div><?= formatDateTime($request['reviewed_at']) ?>
                <?= !empty($request['reviewer_name']) ? '— ' . e($request['reviewer_name']) : '' ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($request['reviewer_notes'])): ?>
            <div class="text-xs text-muted" style="margin-top:10px;">Reviewer notes</div>
            <div style="white-space:pre-wrap;"><?= e($request['reviewer_notes']) ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><div class="card-title">Proposed Changes</div></div>
    <?php if (empty($payload)): ?>
        <p class="text-muted">No payload provided.</p>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Field</th><th>Proposed Value</th></tr></thead>
            <tbody>
            <?php foreach ($payload as $k => $v): ?>
            <tr>
                <td><code><?= e((string) $k) ?></code></td>
                <td><?= e(is_array($v) ? json_encode($v) : (string) $v) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($supportingDoc)): ?>
<div class="card">
    <div class="card-header"><div class="card-title">Supporting Document</div></div>
    <p><strong><?= e($supportingDoc['doc_title']) ?></strong>
        — <code><?= e($supportingDoc['doc_type']) ?></code>
        <?php if (!empty($supportingDoc['expiry_date'])): ?>
            · expires <?= e($supportingDoc['expiry_date']) ?>
        <?php endif; ?>
    </p>
    <?php if (!empty($supportingDoc['file_path'])): ?>
        <a href="/personnel/documents/<?= (int) $supportingDoc['id'] ?>/download" class="btn btn-outline btn-sm">Download scan</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!$isResolved): ?>
<div class="card">
    <div class="card-header"><div class="card-title">Decision</div></div>

    <?php if ($request['status'] === 'submitted'): ?>
    <form method="POST" action="/personnel/change-requests/<?= (int) $request['id'] ?>/mark-review" style="display:inline;">
        <?= csrfField() ?>
        <button class="btn btn-outline btn-sm">Mark Under Review</button>
    </form>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-top:16px;">
        <form method="POST" action="/personnel/change-requests/<?= (int) $request['id'] ?>/approve">
            <?= csrfField() ?>
            <h4>Approve</h4>
            <textarea name="notes" class="form-control" rows="3" placeholder="Optional approval note"></textarea>
            <button class="btn btn-primary btn-sm" style="margin-top:6px;width:100%;">Approve &amp; Apply</button>
        </form>

        <form method="POST" action="/personnel/change-requests/<?= (int) $request['id'] ?>/request-info">
            <?= csrfField() ?>
            <h4>Request Info</h4>
            <textarea name="notes" class="form-control" rows="3" placeholder="What additional info is needed?" required></textarea>
            <button class="btn btn-outline btn-sm" style="margin-top:6px;width:100%;">Request Info</button>
        </form>

        <form method="POST" action="/personnel/change-requests/<?= (int) $request['id'] ?>/reject">
            <?= csrfField() ?>
            <h4>Reject</h4>
            <textarea name="notes" class="form-control" rows="3" placeholder="Reason for rejection (required)" required></textarea>
            <button class="btn btn-danger btn-sm" style="margin-top:6px;width:100%;">Reject</button>
        </form>
    </div>
</div>
<?php endif; ?>
