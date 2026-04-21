<?php
/**
 * Staff missing required documents.
 * Vars: $report — array of user rows each with 'missing' array + 'eligibility'
 */
?>
<div class="card">
    <div class="card-header">
        <div class="card-title">Missing Required Documents</div>
        <span class="text-xs text-muted">Computed against role_required_documents.</span>
    </div>
    <?php if (empty($report)): ?>
        <div class="empty-state">
            <div class="icon">✅</div>
            <h3>All staff have their required documents</h3>
            <p>Every active staff member has the documents required for their role(s).</p>
        </div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr>
                <th>Staff</th><th>Role(s)</th><th>Department</th><th>Base</th>
                <th>Missing</th><th>Eligibility</th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($report as $u):
                $c = ['eligible' => '#10b981', 'warning' => '#f59e0b', 'blocked' => '#ef4444'][$u['eligibility']] ?? '#6b7280';
            ?>
            <tr>
                <td><strong><?= e($u['name']) ?></strong>
                    <?php if (!empty($u['employee_id'])): ?>
                        <span class="text-xs text-muted">(<?= e($u['employee_id']) ?>)</span>
                    <?php endif; ?>
                </td>
                <td><?= e($u['role_names'] ?? '—') ?></td>
                <td><?= e($u['department_name'] ?? '—') ?></td>
                <td><?= e($u['base_name'] ?? '—') ?></td>
                <td style="font-size:12px;">
                    <?php foreach ($u['missing'] as $m): ?>
                        <div><?= e($m['label']) ?></div>
                    <?php endforeach; ?>
                </td>
                <td><span class="status-badge" style="--badge-color:<?= $c ?>"><?= strtoupper($u['eligibility']) ?></span></td>
                <td><a href="/personnel/eligibility/<?= (int) $u['id'] ?>" class="btn btn-outline btn-xs">Detail</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
