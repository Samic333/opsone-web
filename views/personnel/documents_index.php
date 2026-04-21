<?php
/**
 * Personnel documents — tenant-wide list with approval queue at top.
 * Vars: $documents, $pendingCount, $filters, $docTypes
 */
?>
<script>
function toggleDocPreview(id) {
    var row = document.getElementById('preview-row-' + id);
    if (row) row.style.display = (row.style.display === 'none' || row.style.display === '') ? 'table-row' : 'none';
}
</script>
<div class="stats-grid">
    <div class="stat-card <?= $pendingCount > 0 ? 'yellow' : 'blue' ?>">
        <div class="stat-label">Pending Approval</div>
        <div class="stat-value"><?= (int) $pendingCount ?></div>
    </div>
    <div class="stat-card blue">
        <div class="stat-label">Documents Shown</div>
        <div class="stat-value"><?= count($documents) ?></div>
    </div>
</div>

<div class="card">
    <form method="GET" class="flex-row" style="gap:10px;flex-wrap:wrap;align-items:flex-end;">
        <div>
            <label class="text-xs text-muted">Status</label>
            <select name="status" class="form-control">
                <option value="">All</option>
                <?php foreach (['pending_approval','valid','expired','rejected','revoked'] as $s): ?>
                <option value="<?= $s ?>" <?= ($filters['status'] ?? null) === $s ? 'selected' : '' ?>>
                    <?= ucwords(str_replace('_',' ',$s)) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="text-xs text-muted">Type</label>
            <select name="doc_type" class="form-control">
                <option value="">All</option>
                <?php foreach ($docTypes as $t): ?>
                <option value="<?= e($t['doc_type']) ?>" <?= ($filters['doc_type'] ?? null) === $t['doc_type'] ? 'selected' : '' ?>>
                    <?= e($t['doc_type']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <a href="/personnel/documents" class="btn btn-outline btn-sm">Reset</a>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Personnel Documents</div>
    </div>
    <?php if (empty($documents)): ?>
        <div class="empty-state">
            <div class="icon">📄</div>
            <h3>No documents match the filters</h3>
            <p>Crew uploads and approved compliance documents will appear here.</p>
        </div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Staff</th>
                    <th>Document</th>
                    <th>Type</th>
                    <th>Number</th>
                    <th>Expiry</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($documents as $d):
                $statusColor = [
                    'valid' => '#10b981', 'pending_approval' => '#f59e0b',
                    'expired' => '#ef4444', 'rejected' => '#dc2626', 'revoked' => '#6b7280',
                ][$d['status']] ?? '#6b7280';
            ?>
            <tr>
                <td>
                    <strong><?= e($d['user_name']) ?></strong>
                    <?php if (!empty($d['employee_id'])): ?>
                        <span class="text-xs text-muted">(<?= e($d['employee_id']) ?>)</span>
                    <?php endif; ?>
                </td>
                <td><?= e($d['doc_title']) ?></td>
                <td><span class="text-xs text-muted"><?= e($d['doc_type']) ?></span></td>
                <td><code><?= e($d['doc_number'] ?? '—') ?></code></td>
                <td><?= e($d['expiry_date'] ?? '—') ?></td>
                <td><span class="status-badge" style="--badge-color:<?= $statusColor ?>">
                    <?= ucwords(str_replace('_',' ',$d['status'])) ?>
                </span></td>
                <td>
                    <?php if (!empty($d['file_path'])): ?>
                    <button type="button" class="btn btn-primary btn-xs"
                            onclick="toggleDocPreview(<?= (int) $d['id'] ?>)">Preview</button>
                    <?php endif; ?>
                    <a href="/personnel/documents/user/<?= (int) $d['user_id'] ?>" class="btn btn-outline btn-xs">Open</a>
                </td>
            </tr>
            <?php if (!empty($d['file_path'])): ?>
            <tr id="preview-row-<?= (int) $d['id'] ?>" style="display:none;">
                <td colspan="7" style="background:var(--bg-secondary,#0f0f0f);padding:10px;">
                    <?php $previewHeight = 520; include VIEWS_PATH . '/personnel/_doc_preview.php'; ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
