<?php /** Opsvelo — Platform Admin → Branding */ ?>

<div class="page-content">

    <?php if ($flash = flash('success')): ?>
        <div class="alert alert-success" style="margin-bottom:18px;"><?= e($flash) ?></div>
    <?php endif; ?>
    <?php if ($flash = flash('error')): ?>
        <div class="alert alert-error" style="margin-bottom:18px;"><?= e($flash) ?></div>
    <?php endif; ?>

    <div class="card" style="max-width:680px;">
        <div class="card-body" style="padding:24px;">

            <h2 style="margin:0 0 6px;font-size:18px;font-weight:700;">Brand icon</h2>
            <p style="margin:0 0 20px;color:var(--text-muted);font-size:13px;">
                The icon shown in the browser tab (favicon), the iPad app launch screen, and the
                top-left corner of the public marketing site. Recommended: square PNG with a
                transparent background, at least 256&times;256&nbsp;px.
            </p>

            <div style="display:flex;gap:24px;align-items:center;
                        padding:18px;border:1px solid var(--border-color);
                        border-radius:8px;margin-bottom:24px;
                        background:var(--bg-elevated, rgba(255,255,255,0.02));">
                <div style="width:96px;height:96px;display:flex;align-items:center;justify-content:center;
                            background:repeating-conic-gradient(#1a1f2e 0% 25%, #232838 0% 50%) 0/16px 16px;
                            border-radius:8px;flex-shrink:0;">
                    <?php if ($iconExists): ?>
                        <img src="/<?= e(PlatformBrandingController_relativeIconPath()) ?>?v=<?= (int) $iconMtime ?>"
                             alt="Current brand icon"
                             style="max-width:80px;max-height:80px;display:block;">
                    <?php else: ?>
                        <span style="color:var(--text-muted);font-size:11px;">No icon</span>
                    <?php endif; ?>
                </div>
                <div style="font-size:13px;color:var(--text-muted);line-height:1.6;">
                    <div><strong style="color:var(--text-primary);">Current file</strong></div>
                    <div>public/images/brand/opsvelo-icon.png</div>
                    <?php if ($iconExists): ?>
                        <div><?= number_format($iconBytes) ?> bytes &middot; updated <?= date('Y-m-d H:i', $iconMtime) ?></div>
                    <?php else: ?>
                        <div style="color:#f59e0b;">File missing</div>
                    <?php endif; ?>
                </div>
            </div>

            <form action="/platform/branding/upload" method="POST" enctype="multipart/form-data">
                <?= csrfField() ?>

                <label for="iconFile" style="display:block;font-size:13px;font-weight:600;
                       margin-bottom:8px;color:var(--text-primary);">
                    Upload replacement (PNG, max 2&nbsp;MB)
                </label>
                <input type="file" id="iconFile" name="icon" accept="image/png" required
                       style="display:block;width:100%;padding:8px;
                              border:1px dashed var(--border-color);border-radius:6px;
                              background:var(--bg-elevated, rgba(255,255,255,0.02));
                              color:var(--text-primary);margin-bottom:18px;">

                <button type="submit" class="btn btn-primary">
                    Replace brand icon
                </button>
                <p style="margin:14px 0 0;font-size:12px;color:var(--text-muted);">
                    The previous icon is saved as <code>opsvelo-icon.png.bak.&lt;timestamp&gt;</code>
                    on the server. Browsers cache favicons aggressively &mdash; hard-refresh
                    (<kbd>Cmd</kbd>+<kbd>Shift</kbd>+<kbd>R</kbd>) after upload to see the change immediately.
                </p>
            </form>

        </div>
    </div>

</div>

<?php
// Tiny helper for the asset path so the view doesn't have to know the constant.
// Defined inline because the controller is loaded before the view renders.
function PlatformBrandingController_relativeIconPath(): string {
    return 'images/brand/opsvelo-icon.png';
}
?>
