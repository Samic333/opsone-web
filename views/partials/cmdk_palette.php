<?php
/**
 * Partial — Global Cmd+K (Ctrl+K) command palette.
 *
 * Indexes every visible sidebar destination for the current user and
 * lets them jump there by typing. Loaded from views/layouts/app.php.
 */

// Build the destination index from the same nav source the sidebar uses, so
// gating (role/module/platform/airline) stays consistent automatically.
$__cmdk_index = [];
try {
    $__cmdk_nav = class_exists('NavigationService') ? NavigationService::build() : [];
    foreach ($__cmdk_nav as $__cmdk_section) {
        $__cmdk_groupTitle = $__cmdk_section['title'] ?? '';
        foreach (($__cmdk_section['items'] ?? []) as $__cmdk_item) {
            if (empty($__cmdk_item['href'])) continue;
            $__cmdk_index[] = [
                'label' => $__cmdk_item['label'] ?? '',
                'href'  => $__cmdk_item['href'],
                'group' => $__cmdk_groupTitle,
                'icon'  => $__cmdk_item['icon'] ?? 'dot',
            ];
        }
    }
} catch (\Throwable $e) {
    // Palette is non-essential; on any error, render an empty index so it's a no-op.
}
// Always include a few safe global entries so even minimal-permission users
// can reach the basics.
$__cmdk_extras = [
    ['label' => 'Profile',           'href' => '/my-profile',        'group' => 'Personal', 'icon' => 'user-circle'],
    ['label' => 'Notifications',     'href' => '/notifications',     'group' => 'Personal', 'icon' => 'bell'],
    ['label' => 'Help & Guides',     'href' => '/help',              'group' => 'Personal', 'icon' => 'question-circle'],
    ['label' => 'Sign Out',          'href' => '/logout',            'group' => 'Account',  'icon' => 'key'],
];
foreach ($__cmdk_extras as $__e) {
    $__cmdk_index[] = $__e;
}
?>
<div id="cmdk-overlay" class="cmdk-overlay" role="dialog" aria-modal="true" aria-label="Command palette" hidden>
    <div class="cmdk-panel">
        <div class="cmdk-search-row">
            <span class="cmdk-search-icon" aria-hidden="true"><?= sidebarIcon('chevron-right', 16) ?></span>
            <input id="cmdk-input" type="text" class="cmdk-input"
                   placeholder="Search pages, sections, settings…"
                   autocomplete="off" spellcheck="false">
            <kbd class="cmdk-hint">Esc</kbd>
        </div>
        <ul id="cmdk-results" class="cmdk-results" role="listbox"></ul>
        <div class="cmdk-footer">
            <span><kbd>↑</kbd><kbd>↓</kbd> navigate</span>
            <span><kbd>Enter</kbd> open</span>
            <span><kbd>Esc</kbd> close</span>
        </div>
    </div>
</div>

<style>
.cmdk-overlay {
    position: fixed; inset: 0; z-index: 10000;
    background: rgba(5,8,16,0.65); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px);
    display: flex; align-items: flex-start; justify-content: center;
    padding-top: 12vh;
}
.cmdk-overlay[hidden] { display: none; }
.cmdk-panel {
    width: min(640px, 92vw);
    background: var(--bg-card, #1a1f35);
    border: 1px solid var(--border-color, #2a3154);
    border-radius: 14px;
    box-shadow: 0 24px 64px rgba(0,0,0,0.55);
    overflow: hidden;
}
.cmdk-search-row {
    display: flex; align-items: center; gap: 12px;
    padding: 16px 18px;
    border-bottom: 1px solid var(--border-color, #2a3154);
}
.cmdk-search-icon { color: var(--text-tertiary, #7484a8); }
.cmdk-input {
    flex: 1; background: transparent; border: 0; outline: 0;
    color: var(--text-primary, #e8eaf0);
    font: 500 16px/1.4 'Inter', -apple-system, sans-serif;
}
.cmdk-input::placeholder { color: var(--text-tertiary, #7484a8); }
.cmdk-hint {
    font: 600 11px/1 'Inter', sans-serif;
    color: var(--text-tertiary, #7484a8);
    background: var(--bg-input, #151b2e);
    border: 1px solid var(--border-color, #2a3154);
    padding: 4px 6px; border-radius: 4px;
}
.cmdk-results {
    list-style: none; margin: 0; padding: 6px;
    max-height: 56vh; overflow-y: auto;
}
.cmdk-result {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 12px;
    border-radius: 8px; cursor: pointer;
    color: var(--text-primary, #e8eaf0);
    text-decoration: none;
}
.cmdk-result-icon {
    color: var(--accent-blue, #3b82f6);
    display: inline-flex;
}
.cmdk-result-label { flex: 1; font-size: 14px; font-weight: 500; }
.cmdk-result-group {
    font-size: 11px; font-weight: 600;
    text-transform: uppercase; letter-spacing: 0.06em;
    color: var(--text-tertiary, #7484a8);
}
.cmdk-result.is-active,
.cmdk-result:hover {
    background: var(--bg-card-hover, #222845);
}
.cmdk-empty {
    padding: 32px 16px; text-align: center;
    color: var(--text-tertiary, #7484a8); font-size: 14px;
}
.cmdk-footer {
    display: flex; gap: 18px; justify-content: flex-end;
    padding: 10px 16px; border-top: 1px solid var(--border-color, #2a3154);
    color: var(--text-tertiary, #7484a8); font-size: 11px;
}
.cmdk-footer kbd {
    background: var(--bg-input, #151b2e);
    border: 1px solid var(--border-color, #2a3154);
    padding: 2px 5px; border-radius: 3px;
    font: 600 10px/1 'Inter', sans-serif;
    margin: 0 4px 0 0;
}
</style>

<script>
(function () {
    var nav = <?= json_encode(array_values(array_unique(array_map(
        fn($r) => $r['label'].'|'.$r['href'].'|'.$r['group'].'|'.$r['icon'],
        $__cmdk_index
    )))) ?>.map(function (s) {
        var p = s.split('|');
        return { label: p[0], href: p[1], group: p[2], icon: p[3] };
    });

    var overlay = document.getElementById('cmdk-overlay');
    var input   = document.getElementById('cmdk-input');
    var list    = document.getElementById('cmdk-results');
    if (!overlay || !input || !list) return;

    var activeIndex = 0;
    var visible = nav.slice();

    function open() {
        overlay.hidden = false;
        input.value = '';
        activeIndex = 0;
        render(nav);
        setTimeout(function () { input.focus(); }, 0);
    }
    function close() {
        overlay.hidden = true;
    }
    function render(items) {
        visible = items;
        list.innerHTML = '';
        if (!items.length) {
            var empty = document.createElement('div');
            empty.className = 'cmdk-empty';
            empty.textContent = 'No matches.';
            list.appendChild(empty);
            return;
        }
        items.slice(0, 80).forEach(function (it, i) {
            var li = document.createElement('li');
            var a  = document.createElement('a');
            a.className = 'cmdk-result' + (i === activeIndex ? ' is-active' : '');
            a.href = it.href;
            a.dataset.index = String(i);
            a.innerHTML =
                '<span class="cmdk-result-icon" aria-hidden="true">' + iconSvg(it.icon) + '</span>' +
                '<span class="cmdk-result-label"></span>' +
                '<span class="cmdk-result-group"></span>';
            a.querySelector('.cmdk-result-label').textContent = it.label;
            a.querySelector('.cmdk-result-group').textContent = it.group || '';
            a.addEventListener('mouseenter', function () {
                activeIndex = i; refreshActive();
            });
            li.appendChild(a);
            list.appendChild(li);
        });
    }
    function refreshActive() {
        list.querySelectorAll('.cmdk-result').forEach(function (el, i) {
            el.classList.toggle('is-active', i === activeIndex);
            if (i === activeIndex) el.scrollIntoView({ block: 'nearest' });
        });
    }
    function filter(q) {
        q = (q || '').trim().toLowerCase();
        if (!q) { activeIndex = 0; render(nav); return; }
        var hits = nav.filter(function (it) {
            return (it.label + ' ' + (it.group || '')).toLowerCase().indexOf(q) !== -1;
        });
        // also rank exact prefix matches first
        hits.sort(function (a, b) {
            var ap = a.label.toLowerCase().indexOf(q) === 0 ? 0 : 1;
            var bp = b.label.toLowerCase().indexOf(q) === 0 ? 0 : 1;
            return ap - bp;
        });
        activeIndex = 0;
        render(hits);
    }
    function iconSvg(name) {
        // Cheap fallback: a single-pixel dot. The real icons are server-rendered
        // for sidebar items, but inside the JS-driven palette we use a uniform
        // dot to avoid coupling to PHP-side SVG output.
        return '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="3"/></svg>';
    }

    document.addEventListener('keydown', function (e) {
        // Cmd+K (Mac) or Ctrl+K
        if ((e.metaKey || e.ctrlKey) && (e.key === 'k' || e.key === 'K')) {
            e.preventDefault();
            overlay.hidden ? open() : close();
            return;
        }
        if (overlay.hidden) return;
        if (e.key === 'Escape') { e.preventDefault(); close(); return; }
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, visible.length - 1);
            refreshActive();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
            refreshActive();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            var pick = visible[activeIndex];
            if (pick && pick.href) window.location.href = pick.href;
        }
    });
    input.addEventListener('input', function () { filter(input.value); });
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) close();
    });
})();
</script>
