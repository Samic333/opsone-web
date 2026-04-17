<?php /** OpsOne — Roster Change Requests (Phase 9: status tabs + response history) */ ?>

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

// $statusFilter, $changes, $statusCounts, $totalCount, $pendingCount injected by controller
$currentStatus = $statusFilter ?? 'pending';

function changeBadge(string $status, string $label, int $count, string $current): string {
    $active = $status === $current;
    $bg     = $active ? 'var(--accent-blue,#3b82f6)' : 'var(--bg-secondary)';
    $color  = $active ? '#fff' : 'var(--text-muted)';
    $border = $active ? 'transparent' : 'var(--border)';
    $cnt    = $count > 0 ? " <span style='background:rgba(255,255,255,.25);border-radius:10px;padding:0 6px;font-size:10px;'>$count</span>" : '';
    return "<a href='/roster/changes?status=$status'
               style='padding:6px 14px;border-radius:6px;font-size:13px;font-weight:600;
                      text-decoration:none;color:$color;background:$bg;border:1px solid $border;display:inline-flex;align-items:center;gap:4px;'>
               $label$cnt
            </a>";
}
?>

<div style="margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
    <a href="/roster" class="btn btn-ghost">← Back to Roster</a>
    <?php if ($pendingCount > 0): ?>
    <span style="font-size:13px; color:var(--accent-amber,#f59e0b); font-weight:600;">
        ⚠ <?= $pendingCount ?> request<?= $pendingCount !== 1 ? 's' : '' ?> awaiting review
    </span>
    <?php endif; ?>
</div>

<!-- Status Tabs -->
<div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px;">
    <?= changeBadge('pending',  'Pending',  $statusCounts['pending']  ?? 0, $currentStatus) ?>
    <?= changeBadge('approved', 'Approved', $statusCounts['approved'] ?? 0, $currentStatus) ?>
    <?= changeBadge('rejected', 'Rejected', $statusCounts['rejected'] ?? 0, $currentStatus) ?>
    <?= changeBadge('noted',    'Noted',    $statusCounts['noted']    ?? 0, $currentStatus) ?>
    <?= changeBadge('all',      'All',      $totalCount,                    $currentStatus) ?>
</div>

<?php if (empty($changes)): ?>
    <div class="card">
        <div class="empty-state" style="padding:48px 0;">
            <div class="icon"><?= $currentStatus === 'pending' ? '✅' : '📋' ?></div>
            <p><?= $currentStatus === 'pending' ? 'No pending change requests.' : 'No requests in this category.' ?></p>
            <p class="text-sm text-muted">
                <?= $currentStatus === 'pending' ? 'All roster requests have been reviewed.' : 'Try a different status tab.' ?>
            </p>
        </div>
    </div>
<?php else: ?>
    <div style="display:flex; flex-direction:column; gap:12px;">
        <?php foreach ($changes as $req): ?>
        <?php
            $typeInfo    = $typeLabels[$req['change_type']] ?? ['label' => ucfirst($req['change_type']), 'color' => '#6b7280'];
            $statusColor = $statusColors[$req['status']]   ?? '#6b7280';
            $isPending   = $req['status'] === 'pending';
        ?>
        <div class="card" style="border-left:4px solid <?= $typeInfo['color'] ?>;">
            <div style="display:flex; align-items:flex-start; gap:16px; flex-wrap:wrap;">

                <!-- Request details -->
                <div style="flex:1; min-width:240px;">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px; flex-wrap:wrap;">
                        <span class="status-badge" style="--badge-color:<?= $typeInfo['color'] ?>;"><?= $typeInfo['label'] ?></span>
                        <span class="status-badge" style="--badge-color:<?= $statusColor ?>;"><?= ucfirst($req['status']) ?></span>
                        <?php if (!empty($req['period_name'])): ?>
                            <span class="text-xs text-muted">· <?= e($req['period_name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div style="font-weight:600; margin-bottom:4px;">
                        <?= e($req['user_name']) ?>
                        <?php if (!empty($req['employee_id'])): ?>
                            <span class="text-xs text-muted">(<?= e($req['employee_id']) ?>)</span>
                        <?php endif; ?>
                    </div>
                    <p style="margin:0 0 6px; font-size:14px; color:var(--text-primary);"><?= nl2br(e($req['message'])) ?></p>
                    <p class="text-xs text-muted" style="margin:0;">
                        Submitted <?= date('d M Y H:i', strtotime($req['created_at'])) ?>
                    </p>

                    <!-- Response history (if already actioned) -->
                    <?php if (!$isPending && (!empty($req['response']) || !empty($req['responded_by_name']))): ?>
                    <div style="margin-top:10px; padding:10px 12px; background:var(--bg-secondary); border-radius:8px; border:1px solid var(--border);">
                        <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted); margin-bottom:4px;">
                            Response
                        </div>
                        <?php if (!empty($req['response'])): ?>
                        <p style="margin:0 0 4px; font-size:13px; color:var(--text);"><?= nl2br(e($req['response'])) ?></p>
                        <?php endif; ?>
                        <p class="text-xs text-muted" style="margin:0;">
                            <?= !empty($req['responded_by_name']) ? 'By ' . e($req['responded_by_name']) : 'System' ?>
                            <?= !empty($req['responded_at']) ? ' · ' . date('d M Y H:i', strtotime($req['responded_at'])) : '' ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Action form (pending only) or status indicator -->
                <div style="min-width:260px; flex-shrink:0;">
                    <?php if ($isPending): ?>
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
                                    class="btn btn-xs" style="background:#10b981;color:#fff;border:none;">✓ Approve</button>
                            <button type="submit" name="status" value="rejected"
                                    class="btn btn-xs" style="background:#ef4444;color:#fff;border:none;">✗ Reject</button>
                            <button type="submit" name="status" value="noted"
                                    class="btn btn-ghost btn-xs">Note</button>
                        </div>
                    </form>
                    <?php else: ?>
                    <div style="text-align:center; padding:16px; background:var(--bg-secondary); border-radius:8px;">
                        <div style="font-size:22px; margin-bottom:4px;">
                            <?= $req['status'] === 'approved' ? '✅' : ($req['status'] === 'rejected' ? '❌' : '📝') ?>
                        </div>
                        <div style="font-size:12px; font-weight:700; color:<?= $statusColor ?>; text-transform:uppercase; letter-spacing:.05em;">
                            <?= ucfirst($req['status']) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
