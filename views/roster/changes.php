<?php /** OpsOne — Roster Change Requests */ ?>

<?php
$typeLabels = [
    'comment'          => ['label' => 'Comment',       'color' => '#6b7280'],
    'leave_request'    => ['label' => 'Leave Request', 'color' => '#8b5cf6'],
    'swap_request'     => ['label' => 'Swap Request',  'color' => '#f59e0b'],
    'correction'       => ['label' => 'Correction',    'color' => '#3b82f6'],
    'training_request' => ['label' => 'Training Req.', 'color' => '#06b6d4'],
];
$statusColors = [
    'pending'  => '#f59e0b',
    'approved' => '#10b981',
    'rejected' => '#ef4444',
    'noted'    => '#6b7280',
];
?>

<div style="margin-bottom:20px;">
    <a href="/roster" class="btn btn-ghost">← Back to Roster</a>
</div>

<?php if (empty($pending)): ?>
    <div class="card">
        <div class="empty-state" style="padding:48px 0;">
            <div class="icon">✅</div>
            <p>No pending change requests.</p>
            <p class="text-sm text-muted">All roster requests have been reviewed.</p>
        </div>
    </div>
<?php else: ?>
    <div style="display:flex; flex-direction:column; gap:12px;">
        <?php foreach ($pending as $req): ?>
        <?php
            $typeInfo = $typeLabels[$req['change_type']] ?? ['label' => ucfirst($req['change_type']), 'color' => '#6b7280'];
            $statusColor = $statusColors[$req['status']] ?? '#6b7280';
        ?>
        <div class="card" style="border-left:4px solid <?= $typeInfo['color'] ?>;">
            <div style="display:flex; align-items:flex-start; gap:16px; flex-wrap:wrap;">
                <div style="flex:1; min-width:240px;">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px; flex-wrap:wrap;">
                        <span class="status-badge" style="--badge-color:<?= $typeInfo['color'] ?>;"><?= $typeInfo['label'] ?></span>
                        <span class="status-badge" style="--badge-color:<?= $statusColor ?>;"><?= ucfirst($req['status']) ?></span>
                        <?php if ($req['period_name']): ?>
                            <span class="text-xs text-muted">· <?= e($req['period_name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div style="font-weight:600; margin-bottom:4px;"><?= e($req['user_name']) ?>
                        <?php if ($req['employee_id']): ?>
                            <span class="text-xs text-muted">(<?= e($req['employee_id']) ?>)</span>
                        <?php endif; ?>
                    </div>
                    <p style="margin:0 0 4px; font-size:14px; color:var(--text-primary);"><?= nl2br(e($req['message'])) ?></p>
                    <p class="text-xs text-muted" style="margin:0;">
                        Submitted <?= date('d M Y H:i', strtotime($req['created_at'])) ?>
                    </p>
                </div>

                <!-- Response form -->
                <div style="min-width:280px; flex-shrink:0;">
                    <form method="POST" action="/roster/changes/respond/<?= $req['id'] ?>">
                        <?= csrfField() ?>
                        <div class="form-group" style="margin-bottom:8px;">
                            <label style="font-size:12px;">Response <span class="text-muted">(optional)</span></label>
                            <textarea name="response" class="form-control" rows="2"
                                      placeholder="Note for the crew member..."
                                      style="font-size:13px;"></textarea>
                        </div>
                        <div style="display:flex; gap:6px; flex-wrap:wrap;">
                            <button type="submit" name="status" value="approved"
                                    class="btn btn-xs" style="background:#10b981;color:#fff;border:none;">Approve</button>
                            <button type="submit" name="status" value="rejected"
                                    class="btn btn-xs" style="background:#ef4444;color:#fff;border:none;">Reject</button>
                            <button type="submit" name="status" value="noted"
                                    class="btn btn-ghost btn-xs">Note</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
