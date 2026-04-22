<?php
/**
 * Partial — Sidebar Navigation
 *
 * Thin renderer. All decisions (role gates, module gates, badge counts,
 * active state) live in NavigationService + config/sidebar.php.
 *
 * Required locals (set by the layout):
 *   $currentPath     — current request path
 *   $brandName       — top-of-sidebar title
 *   $brandSmall      — subtitle under the brand
 *   $isPlat          — bool; true if platform-only session
 */
$sections = NavigationService::build();
$badges   = NavigationService::badges();

/**
 * Small inline helper used below — render a badge when the key has a positive value.
 */
$renderBadge = function (array $item) use ($badges): string {
    $key = $item['badge'] ?? null;
    if (!$key) return '';
    $n = (int)($badges[$key] ?? 0);
    if ($n <= 0) return '';
    $display = $n > 99 ? '99+' : (string) $n;

    // Colour-of-urgency: pending_change_requests / safety_pending_replies / aog are red,
    // notifications are blue, everything else amber. Keeps cognitive load low.
    $red    = ['pending_change_requests','safety_pending_replies','roster_pending_changes','aog_count'];
    $blue   = ['notif_unread'];
    $colour = in_array($key, $red) ? '#ef4444'
            : (in_array($key, $blue) ? '#3b82f6' : '#f59e0b');

    $suffix = $key === 'aog_count' ? ' AOG' : '';
    return '<span class="sidebar-badge" style="margin-left:auto;background:' . $colour
         . ';color:#fff;font-size:9px;font-weight:800;padding:1px 6px;border-radius:3px;">'
         . htmlspecialchars($display . $suffix, ENT_QUOTES) . '</span>';
};
?>
<aside class="sidebar <?= $isPlat ? 'sidebar-platform' : 'sidebar-airline' ?>" id="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon"><?= $isPlat ? '🛡' : '✈' ?></div>
        <div>
            <h1><?= e($brandName) ?></h1>
            <small><?= e($brandSmall) ?></small>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ($sections as $sec): ?>
            <div class="sidebar-section">
                <?php if (!empty($sec['title'])): ?>
                    <div class="sidebar-section-title"><?= e($sec['title']) ?></div>
                <?php endif; ?>

                <?php foreach ($sec['items'] as $item):
                    $active = NavigationService::isActive($item, $currentPath) ? 'active' : '';
                ?>
                    <a href="<?= e($item['href']) ?>" class="sidebar-link <?= $active ?>">
                        <span class="icon"><?= $item['icon'] ?? '•' ?></span>
                        <?= e($item['label']) ?>
                        <?= $renderBadge($item) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <?php if ($isPlat): ?>
        <!-- Platform mode notice — reminds admins to use Controlled Access for tenant data. -->
        <div class="sidebar-section" style="margin-top:auto;">
            <div style="padding:10px 12px;background:rgba(245,158,11,0.1);border-radius:6px;
                        border-left:3px solid #f59e0b;font-size:11px;color:var(--text-muted);">
                <strong style="color:#f59e0b;">⚠ Platform Mode</strong><br>
                To access airline operational data, open the airline record and use
                <em>Controlled Access</em>.
            </div>
        </div>
        <?php endif; ?>
    </nav>
</aside>
