<?php
/**
 * OpsOne — Create / Edit Safety Publication
 * Variables: $publication (array|null), $relatedReport (array|null)
 */
$isEdit       = !empty($publication['id']);
$pageTitle    = $isEdit ? 'Edit Publication' : 'New Safety Publication';
$pageSubtitle = $isEdit ? 'Update the bulletin content and audience' : 'Create a new safety bulletin or communication';

$v = function(string $key, string $default = '') use ($publication): string {
    return e($publication[$key] ?? $default);
};

$audienceOptions = ['All Staff', 'Pilots', 'Engineers', 'Cabin Crew', 'Management'];
$selectedAudience = $publication['audience'] ?? [];
if (is_string($selectedAudience)) {
    $selectedAudience = array_map('trim', explode(',', $selectedAudience));
}
?>

<div style="margin-bottom:16px;">
    <a href="/safety/publications" class="btn btn-ghost btn-sm">← Publications</a>
</div>

<div style="max-width:820px;">
<form method="POST" action="<?= $isEdit ? '/safety/publications/update/' . (int)$publication['id'] : '/safety/publications/store' ?>">
    <?= csrfField() ?>

    <?php if (!empty($relatedReport['id'])): ?>
        <input type="hidden" name="related_report_id" value="<?= (int)$relatedReport['id'] ?>">
    <?php endif; ?>

    <!-- Related report notice -->
    <?php if (!empty($relatedReport)): ?>
    <div style="background:rgba(245,158,11,0.08); border:1px solid rgba(245,158,11,0.3); border-radius:var(--radius-md); padding:12px 16px; margin-bottom:20px; display:flex; align-items:center; gap:10px;">
        <span>📎</span>
        <p class="text-sm" style="margin:0;">
            Creating publication from report
            <strong><?= e($relatedReport['reference_no'] ?? '') ?></strong>:
            <?= e($relatedReport['title'] ?? '') ?>
        </p>
    </div>
    <?php endif; ?>

    <div class="card" style="padding:24px; margin-bottom:20px;">
        <h4 style="margin:0 0 18px; font-size:15px; font-weight:700; padding-bottom:12px; border-bottom:1px solid var(--border);">Publication Details</h4>

        <div class="form-group">
            <label>Title <span style="color:#ef4444;">*</span></label>
            <input type="text" name="title" class="form-control" required
                   value="<?= $v('title') ?>"
                   placeholder="e.g. Safety Bulletin SB-2026-001 — TCAS Advisory Procedures">
        </div>

        <div class="form-group">
            <label>Summary</label>
            <textarea name="summary" class="form-control" rows="3"
                      placeholder="Brief overview displayed in the publication list…"><?= $v('summary') ?></textarea>
        </div>

        <div class="form-group">
            <label>Content <span style="color:#ef4444;">*</span></label>
            <textarea name="content" class="form-control" required rows="14"
                      style="min-height:300px; font-family:inherit; line-height:1.65;"
                      placeholder="Full publication body. Markdown-style formatting supported."><?= $v('content') ?></textarea>
            <p class="text-xs text-muted" style="margin-top:4px;">Supports line breaks. Use clear headings and numbered points for operational procedures.</p>
        </div>
    </div>

    <div class="card" style="padding:24px; margin-bottom:20px;">
        <h4 style="margin:0 0 14px; font-size:15px; font-weight:700; padding-bottom:12px; border-bottom:1px solid var(--border);">Audience</h4>
        <p class="text-sm text-muted" style="margin:0 0 14px;">Select who should receive this publication.</p>
        <div style="display:flex; gap:20px; flex-wrap:wrap;">
            <?php foreach ($audienceOptions as $opt): ?>
            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:400;">
                <input type="checkbox" name="audience[]" value="<?= e($opt) ?>"
                       <?= in_array($opt, $selectedAudience) ? 'checked' : '' ?>
                       style="width:15px; height:15px;">
                <?= e($opt) ?>
            </label>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (!empty($relatedReport['id']) || !empty($publication['related_report_ref'])): ?>
    <div class="card" style="padding:20px 24px; margin-bottom:24px;">
        <div class="form-group" style="margin-bottom:0;">
            <label>Related Report Reference</label>
            <input type="text" name="related_report_ref" class="form-control"
                   value="<?= e($publication['related_report_ref'] ?? ($relatedReport['reference_no'] ?? '')) ?>"
                   placeholder="e.g. SR-2026-0042">
        </div>
    </div>
    <?php endif; ?>

    <!-- Action Bar -->
    <div style="display:flex; gap:12px; justify-content:flex-end;">
        <a href="/safety/publications" class="btn btn-ghost">Cancel</a>
        <button type="submit" name="pub_action" value="draft" class="btn btn-outline">
            💾 Save as Draft
        </button>
        <button type="submit" name="pub_action" value="publish" class="btn btn-primary"
                onclick="return confirm('Publish this bulletin? It will be visible to the selected audience.')">
            📢 Publish
        </button>
    </div>
</form>
</div>
