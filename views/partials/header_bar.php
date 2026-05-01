<?php
/**
 * Partial — Top Header Bar (page title, alert bells, profile dropdown)
 *
 * Required locals:
 *   $pageTitle      — main heading
 *   $pageSubtitle   — optional subtitle
 *   $headerAction   — optional raw HTML injected by the controller
 *   $user           — currentUser() row
 *   $roleLabel      — human-readable role label
 */
$userName    = $user['name']    ?? '';
$userEmail   = $user['email']   ?? '';
$userAvatar  = $user['avatar']  ?? ($user['profile_photo'] ?? null);
$initials    = strtoupper(substr($userName, 0, 1) ?: 'U');

// Safety bell visibility — same logic as before.
$safetyRoles = ['safety_manager','safety_staff','safety_officer','airline_admin','super_admin'];
$isTeamBell  = (bool) array_intersect($safetyRoles, $_SESSION['user_roles'] ?? []);
$bellLink    = $isTeamBell ? '/safety/queue' : '/safety/my-reports';
$bellTitle   = $isTeamBell ? 'Pilot replies waiting' : 'Safety team messages';
?>

<div class="content-header">
    <div>
        <h2><?= e($pageTitle ?? 'Dashboard') ?></h2>
        <?php if (!empty($pageSubtitle)): ?>
            <p><?= e($pageSubtitle) ?></p>
        <?php endif; ?>
    </div>

    <div style="display:flex;align-items:center;gap:10px;">
        <?php if (!empty($headerAction)): ?>
            <?= $headerAction ?>
        <?php endif; ?>

        <!-- Quick search trigger — opens the Cmd+K command palette.
             Visible affordance for the keyboard shortcut so new users discover it. -->
        <button type="button" id="cmdk-trigger" title="Search (⌘K)"
                onclick="document.dispatchEvent(new KeyboardEvent('keydown',{key:'k',metaKey:true,ctrlKey:true,bubbles:true}));"
                style="display:inline-flex;align-items:center;gap:8px;
                       padding:7px 12px 7px 10px;border-radius:8px;
                       background:var(--bg-card,#1e2535);
                       border:1px solid var(--border-color,rgba(255,255,255,0.08));
                       color:var(--text-tertiary,#7484a8);
                       font-size:12px;font-family:inherit;cursor:pointer;">
            <?= sidebarIcon('chevron-right', 14) ?>
            <span style="opacity:0.85;">Search</span>
            <kbd style="margin-left:6px;padding:2px 6px;font-size:10px;font-weight:700;
                        font-family:ui-monospace,'JetBrains Mono',monospace;
                        background:var(--bg-input,#151b2e);
                        border:1px solid var(--border-color,rgba(255,255,255,0.08));
                        border-radius:4px;color:var(--text-secondary);">⌘K</kbd>
        </button>

        <!-- Safety notification bell -->
        <a id="safety-bell-btn" href="<?= $bellLink ?>" title="<?= e($bellTitle) ?>"
           style="position:relative;display:inline-flex;align-items:center;justify-content:center;
                  width:36px;height:36px;border-radius:8px;background:var(--bg-card,#1e2535);
                  border:1px solid var(--border-color,rgba(255,255,255,0.08));
                  color:var(--text-secondary,#94a3b8);text-decoration:none;">
            <?= sidebarIcon('shield-exclamation', 18) ?>
            <span id="safety-bell-badge" style="display:none;position:absolute;top:-4px;right:-4px;
                   min-width:16px;height:16px;padding:0 4px;background:var(--status-critical,#ef4444);color:#fff;font-size:9px;
                   font-weight:800;line-height:16px;border-radius:8px;text-align:center;
                   border:2px solid var(--bg-main,#111827);pointer-events:none;">0</span>
        </a>

        <!-- Notification inbox bell -->
        <a id="notif-bell-btn" href="/notifications" title="Notifications"
           style="position:relative;display:inline-flex;align-items:center;justify-content:center;
                  width:36px;height:36px;border-radius:8px;background:var(--bg-card,#1e2535);
                  border:1px solid var(--border-color,rgba(255,255,255,0.08));
                  color:var(--text-secondary,#94a3b8);text-decoration:none;">
            <?= sidebarIcon('bell', 18) ?>
            <span id="notif-bell-badge" style="display:none;position:absolute;top:-4px;right:-4px;
                   min-width:16px;height:16px;padding:0 4px;background:var(--status-info,#3b82f6);color:#fff;font-size:9px;
                   font-weight:800;line-height:16px;border-radius:8px;text-align:center;
                   border:2px solid var(--bg-main,#111827);pointer-events:none;">0</span>
        </a>

        <!-- Profile dropdown -->
        <div class="user-menu" id="userMenu" style="position:relative;">
            <button type="button" id="userMenuToggle" aria-haspopup="true" aria-expanded="false"
                style="display:flex;align-items:center;gap:8px;padding:4px 10px 4px 4px;
                       background:var(--bg-card,#1e2535);
                       border:1px solid var(--border-color,rgba(255,255,255,0.08));
                       border-radius:20px;color:var(--text-primary,#f1f5f9);cursor:pointer;font-size:13px;">
                <?php if ($userAvatar): ?>
                    <img src="<?= e($userAvatar) ?>" alt=""
                         style="width:28px;height:28px;border-radius:50%;object-fit:cover;">
                <?php else: ?>
                    <span style="width:28px;height:28px;border-radius:50%;background:var(--accent-blue,#3b82f6);
                                 color:#fff;display:inline-flex;align-items:center;justify-content:center;
                                 font-weight:600;font-size:12px;">
                        <?= e($initials) ?>
                    </span>
                <?php endif; ?>
                <span class="user-menu-name" style="max-width:160px;overflow:hidden;text-overflow:ellipsis;
                                                    white-space:nowrap;">
                    <?= e($userName) ?>
                </span>
                <span style="font-size:10px;opacity:0.6;">▾</span>
            </button>

            <div id="userMenuPanel" role="menu"
                 style="display:none;position:absolute;top:calc(100% + 6px);right:0;min-width:240px;
                        background:var(--bg-card,#1e2535);border:1px solid var(--border-color,rgba(255,255,255,0.12));
                        border-radius:8px;box-shadow:0 10px 30px rgba(0,0,0,0.3);z-index:100;overflow:hidden;">
                <div style="padding:12px 14px;border-bottom:1px solid var(--border-color,rgba(255,255,255,0.08));">
                    <div style="font-weight:600;font-size:13px;color:var(--text-primary,#f1f5f9);">
                        <?= e($userName) ?>
                    </div>
                    <div style="font-size:11px;color:var(--text-muted,#94a3b8);margin-top:2px;">
                        <?= e($roleLabel) ?>
                    </div>
                    <?php if ($userEmail): ?>
                        <div style="font-size:11px;color:var(--text-muted,#94a3b8);margin-top:2px;">
                            <?= e($userEmail) ?>
                        </div>
                    <?php endif; ?>
                </div>

<?php
// ── Slim avatar dropdown — Profile / Security / Help only ─────────────
// Operational "My X" items have moved into the sidebar (My Work + Inbox
// groups in config/sidebar.php). The dropdown stays a pure account menu
// so the top-right of the header doesn't get crowded.
$__menuItemStyle = 'display:flex;align-items:center;gap:10px;padding:10px 14px;color:var(--text-primary,#f1f5f9);font-size:13px;text-decoration:none;';
$__menuItems = [
    ['My Profile',        '/my-profile',        'user-circle'],
    ['Account Security',  '/2fa/setup',         'key'],
    ['Help & Guides',     '/help',              'question-circle'],
];
?>
                <div>
                    <?php foreach ($__menuItems as [$__label, $__href, $__icon]): ?>
                        <a href="<?= e($__href) ?>" role="menuitem" style="<?= $__menuItemStyle ?>">
                            <span style="display:inline-flex;color:var(--accent-blue,#3b82f6);"><?= sidebarIcon($__icon, 16) ?></span>
                            <?= e($__label) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <a href="/logout" role="menuitem"
                   style="display:flex;align-items:center;gap:10px;padding:10px 14px;
                          color:var(--accent-red,#ef4444);font-size:13px;text-decoration:none;
                          border-top:1px solid var(--border-color,rgba(255,255,255,0.08));">
                    <span style="display:inline-flex;"><?= sidebarIcon('key', 16) ?></span>
                    Sign Out
                </a>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var toggle = document.getElementById('userMenuToggle');
    var panel  = document.getElementById('userMenuPanel');
    if (!toggle || !panel) return;

    function close() {
        panel.style.display = 'none';
        toggle.setAttribute('aria-expanded', 'false');
    }
    function open() {
        panel.style.display = 'block';
        toggle.setAttribute('aria-expanded', 'true');
    }
    toggle.addEventListener('click', function (e) {
        e.stopPropagation();
        panel.style.display === 'block' ? close() : open();
    });
    document.addEventListener('click', function (e) {
        if (!panel.contains(e.target) && !toggle.contains(e.target)) close();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') close();
    });
})();
</script>
