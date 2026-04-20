<?php
/**
 * OpsOne — Safety Publication Detail
 * Variables: $publication (array), $isTeam (bool)
 */
$pageTitle    = $publication['title'] ?? 'Safety Publication';
$pageSubtitle = 'Safety Bulletin';

$pubStatus = $publication['status'] ?? 'draft';
$statusColors = ['published' => '#10b981', 'draft' => '#6b7280', 'archived' => '#f59e0b'];
$psc = $statusColors[$pubStatus] ?? '#6b7280';
?>

<div style="margin-bottom:16px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
    <a href="/safety/publications" class="btn btn-ghost btn-sm">← Publications</a>
    <?php if (!empty($isTeam)): ?>
        <a href="/safety/publications/edit/<?= (int)$publication['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
        <?php if ($pubStatus !== 'archived'): ?>
        <form method="POST" action="/safety/publications/<?= (int)$publication['id'] ?>/archive" style="display:inline;"
              onsubmit="return confirm('Archive this publication?')">
            <?= csrfField() ?>
            <button type="submit" class="btn btn-sm" style="background:none; border:1px solid #f59e0b; color:#f59e0b;">Archive</button>
        </form>
        <?php endif; ?>
    <?php endif; ?>
    <span class="status-badge" style="--badge-color:<?= $psc ?>; margin-left:auto;"><?= ucfirst($pubStatus) ?></span>
</div>

<!-- Publication Content -->
<div style="max-width:760px;">
    <div class="card" style="padding:32px 36px;">

        <!-- Header -->
        <div style="margin-bottom:24px; padding-bottom:20px; border-bottom:1px solid var(--border);">
            <h2 style="margin:0 0 10px; font-size:22px; font-weight:700; line-height:1.3;"><?= e($publication['title'] ?? '') ?></h2>
            <div style="display:flex; gap:16px; flex-wrap:wrap; align-items:center;">
                <div class="text-sm text-muted">
                    <?php if (!empty($publication['published_at'])): ?>
                        Published <?= date('d M Y', strtotime($publication['published_at'])) ?>
                    <?php else: ?>
                        Last updated <?= !empty($publication['updated_at']) ? date('d M Y', strtotime($publication['updated_at'])) : '—' ?>
                    <?php endif; ?>
                </div>
                <?php if (!empty($publication['issued_by_name'])): ?>
                    <div class="text-sm text-muted">Issued by <strong><?= e($publication['issued_by_name']) ?></strong></div>
                <?php endif; ?>
                <?php if (!empty($publication['audience'])): ?>
                    <span class="status-badge" style="--badge-color:#3b82f6;">
                        <?= e(is_array($publication['audience']) ? implode(', ', $publication['audience']) : $publication['audience']) ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Summary -->
        <?php if (!empty($publication['summary'])): ?>
        <div style="background:rgba(59,130,246,0.06); border:1px solid rgba(59,130,246,0.2); border-radius:var(--radius-md); padding:14px 16px; margin-bottom:20px;">
            <p class="text-sm" style="margin:0; font-weight:500; color:var(--text-secondary); line-height:1.6;">
                <?= e($publication['summary']) ?>
            </p>
        </div>
        <?php endif; ?>

        <!-- Body -->
        <div style="font-size:15px; line-height:1.75; color:var(--text-primary);">
            <?= nl2br(e($publication['content'] ?? '')) ?>
        </div>

        <!-- Related report -->
        <?php if (!empty($publication['related_report_ref'])): ?>
        <div style="margin-top:24px; padding-top:18px; border-top:1px solid var(--border);">
            <p class="text-xs text-muted" style="margin:0;">
                Related Safety Report:
                <?php if (!empty($isTeam)): ?>
                    <a href="/safety/team/report/<?= (int)($publication['related_report_id'] ?? 0) ?>" style="color:var(--accent-blue);">
                        <?= e($publication['related_report_ref']) ?>
                    </a>
                <?php else: ?>
                    <strong><?= e($publication['related_report_ref']) ?></strong>
                <?php endif; ?>
            </p>
        </div>
        <?php endif; ?>

    </div>
</div>
