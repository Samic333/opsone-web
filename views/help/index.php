<?php /** Phase 15 — Help Hub index */ ?>
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px,1fr)); gap:12px;">
    <?php foreach ($topics as [$title, $link, $icon]): ?>
        <a href="<?= e($link) ?>" style="text-decoration:none; color:inherit;">
            <div class="card" style="padding:16px; display:flex; gap:12px; align-items:center;">
                <div style="font-size:26px;"><?= $icon ?></div>
                <div>
                    <div style="font-weight:600;"><?= e($title) ?></div>
                    <div class="text-xs text-muted">Open guide →</div>
                </div>
            </div>
        </a>
    <?php endforeach; ?>
</div>

<div class="card" style="margin-top:18px; padding:14px;">
    <h4 style="margin-top:0;">Support</h4>
    <p class="text-sm">Need help not covered above? Contact your airline admin or OpsOne support at <strong>support@opsone.example</strong>.</p>
</div>
