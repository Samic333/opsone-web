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
// ── Personal "Me" items, gated by role + module ───────────────────────
// Each entry: [label, href, icon, roles?, module?, condition?]
$__userRoles = $_SESSION['user_roles'] ?? [];
$__hasRole = static function(array $needed) use ($__userRoles): bool {
    if (empty($needed)) return true;
    return (bool) array_intersect($needed, $__userRoles);
};
$__moduleOn = static function(?string $mod): bool {
    if (!$mod) return true;
    if (function_exists('isPlatformOnly') && isPlatformOnly()) return true;
    return canAccessModule($mod);
};
$__crewRoles = ['pilot','cabin_crew','engineer'];
$__pilotOnly = ['pilot'];

$__personalSections = [
    'My Workspace' => [
        ['My Profile',         '/my-profile',                'user-circle',     [],            null,            null],
        ['My Roster',          '/my-roster',                 'calendar',        ['pilot','cabin_crew','engineer','chief_pilot','head_cabin_crew','base_manager'], null, null],
        ['My Duty',            '/my-duty',                   'paper-airplane',  $__crewRoles,  'duty_reporting', static fn(): bool => function_exists('sidebar_duty_crew_allowed') ? sidebar_duty_crew_allowed() : true],
        ['My Flights',         '/my-flights',                'paper-airplane',  $__crewRoles,  null,            null],
        ['My Logbook',         '/my-logbook',                'book-open',       $__pilotOnly,  null,            null],
        ['My FDM Events',      '/my-fdm',                    'trending-up',     $__pilotOnly,  'fdm',           null],
        ['My Training',        '/my-training',               'academic-cap',    [],            'training',      null],
    ],
    'Inbox' => [
        ['Notifications',         '/notifications',                 'bell',                [],            null,           null],
        ['Operational Notices',   '/my-notices',                    'megaphone',           $__crewRoles,  'notices',      null],
        ['My Documents',          '/my-files',                      'folder-open',         $__crewRoles,  null,           null],
        ['My Safety Reports',     '/safety/my-reports',             'shield-exclamation',  [],            'safety_reports', null],
        ['Draft Reports',         '/safety/drafts',                 'pencil',              [],            'safety_reports', null],
        ['My Change Requests',    '/my-profile/change-requests',    'document-text',       [],            null,           null],
        ['My Per Diem',           '/my-per-diem',                   'currency-dollar',     [],            null,           null],
        ['Appraisals',            '/appraisals',                    'star',                [],            null,           null],
    ],
    'Account' => [
        ['Profile Settings',  '/account/settings',  'cog',              [], null, null],
        ['Account Security',  '/2fa/setup',         'key',              [], null, null],
        ['Help & Guides',     '/help',              'question-circle',  [], null, null],
    ],
];
$__menuItemStyle = 'display:flex;align-items:center;gap:10px;padding:9px 14px;color:var(--text-primary,#f1f5f9);font-size:13px;text-decoration:none;';
$__menuHeadStyle = 'padding:8px 14px 4px;font-size:10px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:var(--text-tertiary,#5a6480);';
?>
                <div style="max-height:480px;overflow-y:auto;">
                <?php foreach ($__personalSections as $__sectionTitle => $__sectionItems): ?>
                    <?php
                    $__visible = [];
                    foreach ($__sectionItems as $__it) {
                        [$__label, $__href, $__icon, $__roles, $__mod, $__cond] = $__it;
                        if (!$__hasRole($__roles)) continue;
                        if (!$__moduleOn($__mod)) continue;
                        if ($__cond && !$__cond()) continue;
                        $__visible[] = $__it;
                    }
                    if (empty($__visible)) continue;
                    ?>
                    <div style="<?= $__menuHeadStyle ?>border-top:1px solid var(--border-color,rgba(255,255,255,0.06));"><?= e($__sectionTitle) ?></div>
                    <?php foreach ($__visible as $__it): ?>
                        <?php [$__label, $__href, $__icon] = $__it; ?>
                        <a href="<?= e($__href) ?>" role="menuitem" style="<?= $__menuItemStyle ?>">
                            <span style="display:inline-flex;color:var(--accent-blue,#3b82f6);"><?= sidebarIcon($__icon, 16) ?></span>
                            <?= e($__label) ?>
                        </a>
                    <?php endforeach; ?>
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
