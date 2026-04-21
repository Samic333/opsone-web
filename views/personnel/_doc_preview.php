<?php
/**
 * Reusable document preview partial.
 *
 * Renders an inline preview of a crew_documents row:
 *   - PDFs → <object> with <iframe> fallback (both point at /view endpoint)
 *   - Images → <img> at /view endpoint
 *   - Other → "Preview not available — use Download" message
 *
 * Expects $doc to be defined in the including scope (array from CrewDocumentModel).
 * Optional:
 *   $previewHeight (default 520)
 *   $previewCollapsed (default false — if true, wraps in a <details>)
 */
if (!isset($doc) || !is_array($doc)) return;
$docId       = (int) $doc['id'];
$mime        = $doc['file_mime'] ?? '';
$hasFile     = !empty($doc['file_path']);
$height      = (int) ($previewHeight ?? 520);
$collapsed   = !empty($previewCollapsed);
$viewUrl     = '/personnel/documents/' . $docId . '/view';
$dlUrl       = '/personnel/documents/' . $docId . '/download';
$isPdf       = $mime === 'application/pdf';
$isImage     = str_starts_with($mime, 'image/');
$name        = $doc['file_name'] ?? ('document-' . $docId);
?>

<?php if ($collapsed): ?>
<details style="margin-top:10px;">
    <summary style="cursor:pointer;color:var(--accent-primary,#3b82f6);font-weight:600;">
        📎 Preview attachment (<?= e($name) ?>)
    </summary>
<?php endif; ?>

<div class="doc-preview" style="margin-top:10px;border:1px solid var(--border-color,#333);
     border-radius:8px;overflow:hidden;background:var(--bg-secondary,#111);">

    <div style="padding:6px 10px;display:flex;justify-content:space-between;align-items:center;
                background:var(--bg-card,#1a1a1a);border-bottom:1px solid var(--border-color,#333);">
        <div style="font-size:12px;color:var(--text-muted);">
            <span style="margin-right:8px;"><strong><?= e($name) ?></strong></span>
            <?php if (!empty($mime)): ?>
                <span style="font-family:monospace;"><?= e($mime) ?></span>
            <?php endif; ?>
        </div>
        <div>
            <a href="<?= e($viewUrl) ?>" target="_blank" class="btn btn-outline btn-xs">Open in new tab</a>
            <a href="<?= e($dlUrl) ?>" class="btn btn-outline btn-xs">Download</a>
        </div>
    </div>

    <?php if (!$hasFile): ?>
        <div style="padding:24px;text-align:center;color:var(--text-muted);">
            No file attached to this record.
        </div>
    <?php elseif ($isPdf): ?>
        <object data="<?= e($viewUrl) ?>" type="application/pdf"
                style="width:100%;height:<?= $height ?>px;display:block;">
            <iframe src="<?= e($viewUrl) ?>" style="width:100%;height:<?= $height ?>px;border:0;"></iframe>
        </object>
    <?php elseif ($isImage): ?>
        <div style="max-height:<?= $height ?>px;overflow:auto;text-align:center;background:#000;">
            <img src="<?= e($viewUrl) ?>" alt="<?= e($name) ?>"
                 style="max-width:100%;max-height:<?= $height ?>px;display:inline-block;">
        </div>
    <?php else: ?>
        <div style="padding:24px;text-align:center;color:var(--text-muted);">
            Inline preview is not available for this file type.<br>
            Use <a href="<?= e($dlUrl) ?>">Download</a> to open it.
        </div>
    <?php endif; ?>
</div>

<?php if ($collapsed): ?>
</details>
<?php endif; ?>
