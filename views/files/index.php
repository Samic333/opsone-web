<?php
/**
 * Documents & Manuals — file distribution registry.
 *
 * Layout (Phase K redesign):
 *   1. KPI mini-cards (Total / Published / Drafts / Expiring)
 *   2. Toolbar — client-side search input
 *   3. Modern document table:
 *      - Title cell with category chip + filename + size
 *      - Audience chip
 *      - Version code chip
 *      - Status badge
 *      - Ack chip (warning amber if required, neutral if not)
 *      - Effective / Expires dates (with overdue/soon-to-expire colour cue)
 *      - Uploaded by with initials avatar
 *      - Action buttons: Download (primary) + Edit + secondary icon-only
 *        buttons (Report / New Version / Publish toggle / Delete) — POST forms
 *        with CSRF preserved verbatim; delete confirm() preserved.
 *
 * Controller passes $files. No data shape changes; pure visual redesign.
 */
$pageTitle    = 'Documents & Manuals';
$pageSubtitle = 'Manage operational documents, manuals, and files';
$headerAction = '<a href="/files/upload" class="btn btn-primary">+ Upload Document</a>';

// Tally KPIs from $files.
$counts = ['total' => count($files), 'published' => 0, 'draft' => 0, 'expiring' => 0];
$now = time();
$soon = $now + (30 * 86400); // 30 days from now
foreach ($files as $f) {
    if (($f['status'] ?? '') === 'published') $counts['published']++;
    elseif (($f['status'] ?? '') === 'draft')  $counts['draft']++;
    if (!empty($f['expires_at'])) {
        $exp = strtotime($f['expires_at']);
        if ($exp !== false && $exp <= $soon && $exp >= $now) $counts['expiring']++;
    }
}

$__initials = static function (?string $name): string {
    $name = trim((string) $name);
    if ($name === '') return '?';
    $parts = preg_split('/\s+/', $name);
    $i = '';
    foreach ($parts as $p) {
        if ($p !== '' && strlen($i) < 2) $i .= mb_substr($p, 0, 1);
    }
    return strtoupper($i ?: 'U');
};

ob_start();
?>

<!-- ─── 1. KPI mini cards ────────────────────────────────────────────── -->
<div class="files-kpi-grid"
     style="display:grid; grid-template-columns:repeat(4, 1fr); gap:0.85rem; margin-bottom:1.25rem;">
    <?php
    $kpiCards = [
        ['label' => 'Total Documents', 'value' => $counts['total'],     'tone' => 'var(--accent-blue)'],
        ['label' => 'Published',       'value' => $counts['published'], 'tone' => 'var(--status-cleared)'],
        ['label' => 'Drafts',          'value' => $counts['draft'],     'tone' => 'var(--accent-purple)'],
        ['label' => 'Expiring (30d)',  'value' => $counts['expiring'],  'tone' => 'var(--status-advisory)'],
    ];
    foreach ($kpiCards as $kpi):
    ?>
    <div style="display:flex; flex-direction:column; gap:6px;
                padding:14px 16px;
                background:var(--bg-card);
                border:1px solid var(--border-color);
                border-left:3px solid <?= $kpi['tone'] ?>;
                border-radius:var(--radius-md);">
        <span style="font-size:11px; font-weight:700; text-transform:uppercase;
                     letter-spacing:.06em; color:var(--text-tertiary);">
            <?= e($kpi['label']) ?>
        </span>
        <span style="font-size:1.6rem; font-weight:700; color:<?= $kpi['tone'] ?>;
                     letter-spacing:-0.02em; line-height:1.1;">
            <?= (int) $kpi['value'] ?>
        </span>
    </div>
    <?php endforeach; ?>
</div>

<!-- ─── 2. Toolbar (search) ─────────────────────────────────────────── -->
<div class="files-toolbar"
     style="display:flex; flex-wrap:wrap; align-items:center; gap:12px;
            justify-content:flex-end; margin-bottom:1rem;">
    <div style="position:relative; min-width:280px; flex:1; max-width:380px;">
        <span style="position:absolute; left:10px; top:50%; transform:translateY(-50%);
                     color:var(--text-tertiary); display:inline-flex;">
            <?= sidebarIcon('chevron-right', 14) ?>
        </span>
        <input id="files-search" type="search" autocomplete="off"
               placeholder="Search title, file name, or category…"
               style="width:100%; padding:8px 12px 8px 32px;
                      background:var(--bg-input); color:var(--text-primary);
                      border:1px solid var(--border-color); border-radius:var(--radius-sm);
                      font-size:13px; font-family:inherit; outline:none;">
    </div>
</div>

<!-- ─── 3. Documents table ──────────────────────────────────────────── -->
<?php if (empty($files)): ?>
    <div class="card">
        <div class="empty-state">
            <div class="icon"><?= sidebarIcon('folder-open', 32) ?></div>
            <h3>No Documents Yet</h3>
            <p>Upload your first document, manual, or notice.</p>
            <a href="/files/upload" class="btn btn-primary" style="margin-top:0.5rem;">
                + Upload Document
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card" style="padding:0; overflow:hidden;">
        <div class="table-wrap" style="margin:0;">
            <table id="files-table" style="margin:0;">
                <thead>
                    <tr>
                        <th>Document</th>
                        <th>Audience</th>
                        <th style="text-align:center;">Version</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:center;">Ack</th>
                        <th>Effective / Expires</th>
                        <th>Uploaded By</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($files as $f):
                    $superseded = !empty($f['superseded_at']);
                    $isPub      = ($f['status'] ?? '') === 'published';
                    $uploadedBy = $f['uploaded_by_name'] ?? '';
                    $haystack   = strtolower(
                        ($f['title'] ?? '') . ' ' .
                        ($f['file_name'] ?? '') . ' ' .
                        ($f['category_name'] ?? '') . ' ' .
                        ($f['audience_summary'] ?? '')
                    );
                ?>
                    <tr data-search="<?= e($haystack) ?>"
                        <?= $superseded ? 'style="opacity:0.55;"' : '' ?>>
                        <td>
                            <div style="font-weight:600; color:var(--text-primary);">
                                <?= e($f['title']) ?>
                                <?php if (!empty($f['category_name'])): ?>
                                    <span style="font-size:10px; font-weight:600; padding:2px 8px;
                                                 border-radius:10px; margin-left:6px;
                                                 background:rgba(139,92,246,0.10);
                                                 color:var(--accent-purple);
                                                 vertical-align:middle;">
                                        <?= e($f['category_name']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size:11px; color:var(--text-tertiary); margin-top:3px;">
                                <span style="font-family:ui-monospace,monospace;">
                                    <?= e($f['file_name']) ?>
                                </span>
                                · <?= number_format(($f['file_size'] ?? 0) / 1024, 1) ?> KB
                                <?php if (!empty($f['replaces_file_id'])): ?>
                                    · <a href="/files/history/<?= (int) $f['id'] ?>"
                                         style="color:var(--accent-blue);">
                                        replaces #<?= (int) $f['replaces_file_id'] ?>
                                      </a>
                                <?php endif; ?>
                                <?php if ($superseded): ?>
                                    · <span style="color:var(--text-tertiary); font-style:italic;">superseded</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span style="font-size:12px; color:var(--text-secondary);">
                                <?= e($f['audience_summary'] ?? '—') ?>
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <code style="font-size:11px; color:var(--text-secondary);
                                         background:var(--bg-input);
                                         padding:2px 6px; border-radius:4px;">
                                <?= e($f['version']) ?>
                            </code>
                        </td>
                        <td style="text-align:center;"><?= statusBadge($f['status']) ?></td>
                        <td style="text-align:center;">
                            <?php if (!empty($f['requires_ack'])): ?>
                                <span style="display:inline-flex; align-items:center; gap:4px;
                                             font-size:11px; font-weight:600;
                                             padding:2px 8px; border-radius:10px;
                                             background:rgba(245,158,11,0.10);
                                             color:var(--status-advisory);">
                                    <?= sidebarIcon('exclamation', 11) ?>
                                    Yes
                                </span>
                            <?php else: ?>
                                <span style="font-size:11px; color:var(--text-tertiary);">No</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-size:11px; color:var(--text-secondary);
                                        font-variant-numeric:tabular-nums; line-height:1.4;">
                                <div>
                                    <?= !empty($f['effective_date']) ? formatDate($f['effective_date']) : '—' ?>
                                </div>
                                <?php if (!empty($f['expires_at'])):
                                    $diff = (strtotime($f['expires_at']) - time()) / 86400;
                                    $col  = $diff < 0
                                        ? 'var(--status-critical)'
                                        : ($diff < 30 ? 'var(--status-advisory)' : 'var(--text-tertiary)');
                                ?>
                                    <div style="color:<?= $col ?>; font-size:10px;">
                                        ↳ expires <?= formatDate($f['expires_at']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($uploadedBy): ?>
                                <div style="display:flex; align-items:center; gap:8px; min-width:0;">
                                    <span style="width:24px; height:24px; border-radius:50%;
                                                 display:inline-flex; align-items:center; justify-content:center;
                                                 background:var(--accent-blue); color:#fff;
                                                 font-size:9px; font-weight:700; flex-shrink:0;">
                                        <?= e($__initials($uploadedBy)) ?>
                                    </span>
                                    <span style="font-size:12px; color:var(--text-primary);
                                                 overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
                                                 max-width:120px;">
                                        <?= e($uploadedBy) ?>
                                    </span>
                                </div>
                            <?php else: ?>
                                <span style="color:var(--text-tertiary); font-size:12px;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right;">
                            <div class="btn-group" style="justify-content:flex-end; gap:4px; flex-wrap:nowrap;">
                                <a href="/files/download/<?= (int) $f['id'] ?>"
                                   class="btn btn-xs btn-primary" title="Download file">Download</a>
                                <a href="/files/edit/<?= (int) $f['id'] ?>"
                                   class="btn btn-xs btn-outline" title="Edit document">Edit</a>
                                <a href="/files/ack-report/<?= (int) $f['id'] ?>"
                                   class="btn btn-xs btn-outline"
                                   title="Acknowledgement report"
                                   style="padding:2px 8px;">
                                    <?= sidebarIcon('chart-bar', 12) ?>
                                </a>
                                <?php if (!$superseded): ?>
                                    <a href="/files/upload?replaces=<?= (int) $f['id'] ?>"
                                       class="btn btn-xs btn-outline"
                                       title="Upload a new version that replaces this one"
                                       style="padding:2px 8px;">
                                        <?= sidebarIcon('cloud-arrow-up', 12) ?>
                                    </a>
                                <?php endif; ?>
                                <form method="POST" action="/files/toggle/<?= (int) $f['id'] ?>"
                                      style="display:inline; margin:0;">
                                    <?= csrfField() ?>
                                    <button type="submit"
                                            class="btn btn-xs <?= $isPub ? 'btn-warning' : 'btn-success' ?>"
                                            title="<?= $isPub ? 'Unpublish (hide from crew)' : 'Publish to crew' ?>">
                                        <?= $isPub ? 'Unpublish' : 'Publish' ?>
                                    </button>
                                </form>
                                <form method="POST" action="/files/delete/<?= (int) $f['id'] ?>"
                                      style="display:inline; margin:0;"
                                      onsubmit="return confirm('Delete this document permanently?')">
                                    <?= csrfField() ?>
                                    <button type="submit" class="btn btn-xs btn-danger"
                                            title="Delete this document permanently"
                                            style="padding:2px 8px;">
                                        <?= sidebarIcon('exclamation', 12) ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="files-empty-search"
         style="display:none; margin-top:1rem; padding:1.5rem; text-align:center;
                color:var(--text-tertiary); font-size:14px;
                background:var(--bg-card); border:1px solid var(--border-color);
                border-radius:var(--radius-md);">
        No documents match your search. <a href="#" id="files-clear-search" style="color:var(--accent-blue);">Clear search</a>
    </div>

    <script>
    (function () {
        var input    = document.getElementById('files-search');
        var table    = document.getElementById('files-table');
        var emptyMsg = document.getElementById('files-empty-search');
        if (!input || !table) return;

        function filter() {
            var q = input.value.trim().toLowerCase();
            var rows = table.querySelectorAll('tbody tr[data-search]');
            var visible = 0;
            rows.forEach(function (row) {
                var match = q === '' || row.getAttribute('data-search').indexOf(q) !== -1;
                row.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            if (emptyMsg) emptyMsg.style.display = (visible === 0 && q !== '') ? '' : 'none';
        }
        input.addEventListener('input', filter);

        var clear = document.getElementById('files-clear-search');
        if (clear) clear.addEventListener('click', function (e) {
            e.preventDefault();
            input.value = '';
            filter();
            input.focus();
        });
    })();
    </script>
<?php endif; ?>

<style>
@media (max-width: 1100px) {
    .files-kpi-grid { grid-template-columns: repeat(2, 1fr) !important; }
}
</style>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/app.php'; ?>
