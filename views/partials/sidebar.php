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
 * Render a badge when the key has a positive value.
 */
$renderBadge = function (array $item) use ($badges): string {
    $key = $item['badge'] ?? null;
    if (!$key) return '';
    $n = (int)($badges[$key] ?? 0);
    if ($n <= 0) return '';
    $display = $n > 99 ? '99+' : (string) $n;

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
        <div class="sidebar-brand-icon">
            <?= $isPlat ? sidebarIcon('shield-check', 22) : opsoneLogoMark(22) ?>
        </div>
        <div>
            <h1><?= $isPlat ? e($brandName) : opsoneWordmark('md') ?></h1>
            <small><?= e($brandSmall) ?></small>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ($sections as $sec):
            $sectionKey = strtolower(preg_replace('/\s+/', '-', $sec['title'] ?? 'section'));
        ?>
            <div class="sidebar-section" data-section="<?= e($sectionKey) ?>">
                <?php if (!empty($sec['title'])): ?>
                    <div class="sidebar-section-title sidebar-section-toggle" data-section="<?= e($sectionKey) ?>">
                        <span><?= e($sec['title']) ?></span>
                        <span class="sidebar-chevron"><?= sidebarIcon('chevron-right', 12) ?></span>
                    </div>
                <?php endif; ?>

                <div class="sidebar-section-items">
                <?php foreach ($sec['items'] as $item):
                    $active = NavigationService::isActive($item, $currentPath) ? 'active' : '';
                ?>
                    <a href="<?= e($item['href']) ?>" class="sidebar-link <?= $active ?>">
                        <span class="icon"><?= sidebarIcon($item['icon'] ?? 'dot') ?></span>
                        <?= e($item['label']) ?>
                        <?= $renderBadge($item) ?>
                    </a>
                <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if ($isPlat): ?>
        <div class="sidebar-section" style="margin-top:auto;">
            <div style="padding:10px 12px;background:rgba(245,158,11,0.1);border-radius:6px;
                        border-left:3px solid #f59e0b;font-size:11px;color:var(--text-muted);">
                <strong style="color:#f59e0b;">Platform Mode</strong><br>
                To access airline operational data, open the airline record and use
                <em>Controlled Access</em>.
            </div>
        </div>
        <?php endif; ?>
    </nav>
</aside>

<script>
(function () {
    var STORE_KEY = 'sidebar_collapsed_v1';

    function getCollapsed() {
        try { return JSON.parse(localStorage.getItem(STORE_KEY) || '{}'); } catch(e) { return {}; }
    }
    function setCollapsed(key, val) {
        var s = getCollapsed(); s[key] = val;
        try { localStorage.setItem(STORE_KEY, JSON.stringify(s)); } catch(e) {}
    }

    // Restore collapsed state on load; always keep section with active link open.
    var collapsed = getCollapsed();
    document.querySelectorAll('.sidebar-section[data-section]').forEach(function(sec) {
        var key = sec.getAttribute('data-section');
        var items = sec.querySelector('.sidebar-section-items');
        var toggle = sec.querySelector('.sidebar-section-toggle');
        if (!items || !toggle) return;

        var hasActive = sec.querySelector('.sidebar-link.active');
        if (hasActive) {
            // Force open sections that contain the active link
            sec.classList.remove('collapsed');
            setCollapsed(key, false);
        } else if (collapsed[key] === true) {
            sec.classList.add('collapsed');
        }
    });

    // Attach click handlers to section titles
    document.querySelectorAll('.sidebar-section-toggle').forEach(function(toggle) {
        toggle.addEventListener('click', function() {
            var key = toggle.getAttribute('data-section');
            var sec = document.querySelector('.sidebar-section[data-section="' + key + '"]');
            if (!sec) return;
            var isCollapsed = sec.classList.toggle('collapsed');
            setCollapsed(key, isCollapsed);
        });
    });
})();
</script>
