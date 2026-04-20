<?php
/**
 * OpsOne — Safety Publications List
 * Variables: $publications (array)
 */
$pageTitle    = 'Safety Publications';
$pageSubtitle = 'Bulletins and communications issued to crew';

$headerAction = '<a href="/safety/publications/create" class="btn btn-primary btn-sm">＋ New Publication</a>';

$filterTab = $_GET['tab'] ?? 'published';
?>

<!-- Filter Tabs -->
<div class="nav-bar" style="margin-bottom:20px;">
    <?php foreach (['published' => 'Published', 'draft' => 'Drafts', 'archived' => 'Archived'] as $slug => $label): ?>
    <a href="/safety/publications?tab=<?= $slug ?>"
       class="btn btn-sm <?= $filterTab === $slug ? 'btn-primary' : 'btn-ghost' ?>">
        <?= $label ?>
    </a>
    <?php endforeach; ?>
</div>

<?php if (empty($publications)): ?>
    <div class="card">
        <div class="empty-state" style="padding:48px 0;">
            <div class="icon">📢</div>
            <h3>No Publications</h3>
            <p>No safety publications found for this filter.</p>
            <a href="/safety/publications/create" class="btn btn-primary btn-sm">Create Publication</a>
        </div>
    </div>
<?php else: ?>
    <div style="display:flex; flex-direction:column; gap:14px;">
        <?php foreach ($publications as $pub): ?>
        <?php
        $pubStatus = $pub['status'] ?? 'draft';
        $statusColors = ['published' => '#10b981', 'draft' => '#6b7280', 'archived' => '#f59e0b'];
        $psc = $statusColors[$pubStatus] ?? '#6b7280';
        ?>
        <div class="card" style="border-left:4px solid <?= $psc ?>;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;">
                <div style="flex:1; min-width:0;">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px; flex-wrap:wrap;">
                        <h4 style="margin:0; font-size:15px; font-weight:700;"><?= e($pub['title'] ?? '') ?></h4>
                        <span class="status-badge" style="--badge-color:<?= $psc ?>;"><?= ucfirst($pubStatus) ?></span>
                        <?php if (!empty($pub['audience'])): ?>
                            <span class="status-badge" style="--badge-color:#6b7280;">
                                <?= e(is_array($pub['audience']) ? implode(', ', $pub['audience']) : $pub['audience']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($pub['summary'])): ?>
                        <p class="text-sm text-muted" style="margin:0 0 8px; line-height:1.5;">
                            <?= e(mb_strimwidth($pub['summary'], 0, 180, '…')) ?>
                        </p>
                    <?php endif; ?>
                    <div class="text-xs text-muted">
                        <?php if (!empty($pub['published_at'])): ?>
                            Published <?= date('d M Y', strtotime($pub['published_at'])) ?>
                        <?php else: ?>
                            Last edited <?= !empty($pub['updated_at']) ? date('d M Y', strtotime($pub['updated_at'])) : '—' ?>
                        <?php endif; ?>
                        <?php if (!empty($pub['issued_by_name'])): ?>
                            · by <?= e($pub['issued_by_name']) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="flex-shrink:0;">
                    <a href="/safety/publications/<?= (int)$pub['id'] ?>" class="btn btn-outline btn-sm">View</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
