<?php
/**
 * Revision Center — post-publication roster changes
 * Variables: $revisions, $periods, $periodId (optional filter)
 */

$statusColors = [
    'draft'     => ['bg' => '#f3f4f6', 'color' => '#374151', 'label' => 'Draft'],
    'issued'    => ['bg' => '#d1fae5', 'color' => '#065f46', 'label' => 'Issued'],
    'withdrawn' => ['bg' => '#fef2f2', 'color' => '#991b1b', 'label' => 'Withdrawn'],
];
$sourceLabels = [
    'scheduler'        => 'Scheduler',
    'manager_request'  => 'Manager Request',
    'crew_request'     => 'Crew Request',
    'operational'      => 'Operational',
    'system'           => 'System',
];
?>
<style>
.rev-toolbar{display:flex;align-items:center;gap:8px;margin-bottom:18px;flex-wrap:wrap;}
.rev-filter-bar{display:flex;align-items:center;gap:8px;margin-bottom:16px;flex-wrap:wrap;}
.rev-card{background:var(--bg-card);border:1px solid var(--border);border-radius:10px;
 margin-bottom:12px;overflow:hidden;transition:box-shadow .15s;}
.rev-card:hover{box-shadow:0 2px 12px rgba(0,0,0,.08);}
.rev-card-hdr{display:flex;align-items:center;gap:12px;padding:14px 16px;
 border-bottom:1px solid var(--border);background:var(--bg-secondary);}
.rev-ref{font-size:14px;font-weight:800;font-family:monospace;color:var(--text-primary);}
.rev-badge{font-size:10px;font-weight:700;padding:2px 8px;border-radius:4px;text-transform:uppercase;letter-spacing:.06em;}
.rev-source{font-size:11px;color:var(--text-muted);}
.rev-date{font-size:11px;color:var(--text-muted);margin-left:auto;}
.rev-card-body{padding:14px 16px;}
.rev-reason{font-size:13px;color:var(--text-primary);margin-bottom:10px;}
.rev-meta{display:flex;gap:16px;flex-wrap:wrap;}
.rev-meta-item{font-size:12px;color:var(--text-muted);}
.rev-meta-item strong{color:var(--text-primary);}
.rev-items-list{margin-top:12px;border:1px solid var(--border);border-radius:7px;overflow:hidden;}
.rev-item-row{display:grid;grid-template-columns:160px 90px 1fr 1fr 1fr;gap:8px;
 padding:8px 12px;font-size:12px;border-bottom:1px solid var(--border);align-items:center;}
.rev-item-row:last-child{border:none;}
.rev-item-row.hdr{background:var(--bg-secondary);font-weight:700;font-size:10px;
 text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);}
.duty-arrow{display:flex;align-items:center;gap:4px;}
.duty-chip-sm{display:inline-flex;align-items:center;justify-content:center;
 border-radius:3px;padding:1px 5px;font-size:9.5px;font-weight:800;}
.empty-rev{text-align:center;padding:56px 24px;}
.empty-rev .icon{font-size:36px;margin-bottom:12px;}
</style>

<div class="rev-toolbar">
    <?php if (hasAnyRole(['scheduler','airline_admin','super_admin'])): ?>
        <a href="/roster/revisions/create" class="btn btn-primary">＋ New Revision</a>
    <?php endif; ?>
    <a href="/roster" class="btn btn-ghost" style="margin-left:auto;">← Workbench</a>
</div>

<!-- Filter by period -->
<?php if (!empty($periods)): ?>
<form method="GET" action="/roster/revisions" class="rev-filter-bar">
    <label style="font-size:12px;color:var(--text-muted);font-weight:600;">Filter by Period:</label>
    <select name="period_id" class="form-control" style="width:240px;padding:4px 10px;font-size:12px;"
            onchange="this.form.submit()">
        <option value="">— All Periods —</option>
        <?php foreach ($periods as $p): ?>
            <option value="<?= $p['id'] ?>" <?= (($periodId ?? null) == $p['id']) ? 'selected' : '' ?>>
                <?= e($p['name']) ?> (<?= ucfirst($p['status']) ?>)
            </option>
        <?php endforeach; ?>
    </select>
    <?php if ($periodId): ?>
        <a href="/roster/revisions" class="btn btn-ghost btn-sm" style="font-size:12px;">Clear</a>
    <?php endif; ?>
</form>
<?php endif; ?>

<!-- Summary stats -->
<?php if (!empty($revisions)):
    $issued   = count(array_filter($revisions, fn($r) => $r['status'] === 'issued'));
    $drafts   = count(array_filter($revisions, fn($r) => $r['status'] === 'draft'));
    $totalItems = array_sum(array_column($revisions, 'item_count'));
?>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin-bottom:16px;">
    <div class="card" style="padding:14px;text-align:center;">
        <div style="font-size:22px;font-weight:800;color:#2563eb;"><?= count($revisions) ?></div>
        <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">Total Revisions</div>
    </div>
    <div class="card" style="padding:14px;text-align:center;">
        <div style="font-size:22px;font-weight:800;color:#059669;"><?= $issued ?></div>
        <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">Issued</div>
    </div>
    <div class="card" style="padding:14px;text-align:center;">
        <div style="font-size:22px;font-weight:800;color:#d97706;"><?= $drafts ?></div>
        <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">Draft</div>
    </div>
    <div class="card" style="padding:14px;text-align:center;">
        <div style="font-size:22px;font-weight:800;color:var(--text-primary);"><?= $totalItems ?></div>
        <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">Duty Changes</div>
    </div>
</div>
<?php endif; ?>

<?php if (empty($revisions)): ?>
<div class="card">
    <div class="empty-rev">
        <div class="icon">📋</div>
        <h3 style="margin:0 0 6px;font-size:17px;">No revisions yet</h3>
        <p style="color:var(--text-muted);font-size:13px;margin:0 0 14px;">
            Revisions are created when a published roster needs post-publication changes.<br>
            All changes are tracked here with before/after duty details.
        </p>
        <?php if (hasAnyRole(['scheduler','airline_admin','super_admin'])): ?>
            <a href="/roster/revisions/create" class="btn btn-primary">Create First Revision</a>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>

<?php
// Load duty types for chip rendering
$dutyTypes = RosterModel::dutyTypes();
foreach ($revisions as $rev):
    $sc = $statusColors[$rev['status']] ?? $statusColors['draft'];
    $sourceLabel = $sourceLabels[$rev['change_source']] ?? ucfirst($rev['change_source']);
?>
<div class="rev-card">
    <div class="rev-card-hdr">
        <span class="rev-ref"><?= e($rev['revision_ref']) ?></span>
        <span class="rev-badge" style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>;"><?= $sc['label'] ?></span>
        <span class="rev-source">via <?= $sourceLabel ?></span>
        <?php if ($rev['period_name']): ?>
            <span style="font-size:11px;color:var(--text-muted);">· <?= e($rev['period_name']) ?></span>
        <?php endif; ?>
        <span class="rev-date">
            <?= $rev['issued_at']
                ? 'Issued ' . date('d M Y H:i', strtotime($rev['issued_at']))
                : 'Created ' . date('d M Y', strtotime($rev['created_at'])) ?>
        </span>
    </div>
    <div class="rev-card-body">
        <div class="rev-reason"><?= e($rev['reason']) ?></div>
        <div class="rev-meta">
            <div class="rev-meta-item">By <strong><?= e($rev['requested_by_name'] ?? '—') ?></strong></div>
            <?php if ($rev['approved_by_name']): ?>
                <div class="rev-meta-item">Approved by <strong><?= e($rev['approved_by_name']) ?></strong></div>
            <?php endif; ?>
            <div class="rev-meta-item"><strong><?= $rev['item_count'] ?></strong> change<?= $rev['item_count'] != 1 ? 's' : '' ?></div>
            <?php if ($rev['notes']): ?>
                <div class="rev-meta-item" style="font-style:italic;"><?= e($rev['notes']) ?></div>
            <?php endif; ?>
        </div>

        <?php
        // Load items if available (only for detail when item_count > 0)
        if ($rev['item_count'] > 0):
            $items = Database::fetchAll(
                "SELECT rvi.*, u.name AS user_name, u.employee_id
                 FROM roster_revision_items rvi
                 JOIN users u ON u.id = rvi.user_id
                 WHERE rvi.roster_revision_id = ?
                 ORDER BY rvi.roster_date, u.name",
                [$rev['id']]
            );
        ?>
        <div class="rev-items-list">
            <div class="rev-item-row hdr">
                <span>Crew Member</span>
                <span>Date</span>
                <span>From</span>
                <span>To</span>
                <span>Note</span>
            </div>
            <?php foreach ($items as $item):
                $oldMeta = $item['old_duty_type'] ? ($dutyTypes[$item['old_duty_type']] ?? null) : null;
                $newMeta = $item['new_duty_type'] ? ($dutyTypes[$item['new_duty_type']] ?? null) : null;
            ?>
            <div class="rev-item-row">
                <div>
                    <div style="font-weight:600;"><?= e($item['user_name']) ?></div>
                    <div style="font-size:10px;color:var(--text-muted);font-family:monospace;"><?= e($item['employee_id'] ?? '') ?></div>
                </div>
                <div style="font-size:12px;"><?= date('d M', strtotime($item['roster_date'])) ?></div>
                <div>
                    <?php if ($oldMeta): ?>
                        <span class="duty-chip-sm" style="background:<?= $oldMeta['bg'] ?>;color:<?= $oldMeta['color'] ?>;">
                            <?= $item['old_duty_code'] ?: $oldMeta['code'] ?>
                        </span>
                    <?php else: ?>
                        <span style="color:var(--text-muted);font-size:11px;">—</span>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if ($newMeta): ?>
                        <span class="duty-chip-sm" style="background:<?= $newMeta['bg'] ?>;color:<?= $newMeta['color'] ?>;">
                            <?= $item['new_duty_code'] ?: $newMeta['code'] ?>
                        </span>
                    <?php else: ?>
                        <span style="color:var(--text-muted);font-size:11px;">—</span>
                    <?php endif; ?>
                </div>
                <div style="font-size:11px;color:var(--text-muted);"><?= e($item['change_note'] ?? '—') ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<div class="card" style="margin-top:16px;padding:14px 16px;">
    <h4 style="margin:0 0 8px;font-size:13px;color:var(--text-muted);">About Revisions</h4>
    <p style="font-size:12px;color:var(--text-muted);margin:0;line-height:1.7;">
        Revisions track all post-publication changes to a roster. Once a period is published,
        any duty changes must be recorded as a revision rather than a silent edit.
        Each revision has a unique reference number, a stated reason, and a full before/after record
        of the changes made.
    </p>
</div>
