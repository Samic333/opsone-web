<?php /** OpsOne — Duty Reporting Exceptions Queue */ ?>

<?php $activeStatus = $_GET['status'] ?? 'pending'; ?>

<!-- Tabs -->
<div class="card" style="padding:14px 18px; margin-bottom:20px; display:flex; gap:10px; align-items:center;">
    <?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $slug => $label): ?>
        <a href="/duty-reporting/exceptions?status=<?= $slug ?>"
           class="btn <?= $activeStatus === $slug ? 'btn-primary' : 'btn-ghost' ?> btn-sm">
            <?= e($label) ?>
        </a>
    <?php endforeach; ?>
    <span class="text-sm text-muted" style="margin-left:auto;"><?= count($rows) ?> result<?= count($rows) !== 1 ? 's' : '' ?></span>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Submitted</th>
                <th>Crew</th>
                <th>Reason</th>
                <th>Note</th>
                <th>Status</th>
                <th>Reviewer</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="7">
                <div class="empty-state">
                    <div class="icon">✅</div>
                    <h3>No exceptions in this view</h3>
                    <p>Change the tab above to see approved, rejected, or all exceptions.</p>
                </div>
            </td></tr>
        <?php else: ?>
            <?php foreach ($rows as $ex): ?>
                <?php
                $statusColor = match ($ex['status']) {
                    'pending'  => '#f59e0b',
                    'approved' => '#10b981',
                    'rejected' => '#ef4444',
                    default    => '#6b7280',
                };
                ?>
                <tr>
                    <td class="text-sm text-muted"><?= e($ex['submitted_at']) ?></td>
                    <td><strong><?= e($ex['user_name'] ?? '—') ?></strong></td>
                    <td class="text-sm"><?= e(DutyException::REASONS[$ex['reason_code']] ?? $ex['reason_code']) ?></td>
                    <td class="text-sm"><?= e(mb_substr((string)($ex['reason_text'] ?? ''), 0, 120)) ?></td>
                    <td><span class="status-badge" style="--badge-color: <?= $statusColor ?>"><?= e(ucfirst($ex['status'])) ?></span></td>
                    <td class="text-sm"><?= e($ex['reviewer_name'] ?? '—') ?></td>
                    <td>
                        <a href="/duty-reporting/report/<?= (int)$ex['duty_report_id'] ?>" class="btn btn-ghost btn-sm">Open</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
