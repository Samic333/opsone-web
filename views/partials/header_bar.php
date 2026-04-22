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
                  color:var(--text-secondary,#94a3b8);text-decoration:none;font-size:17px;">
            🛡️
            <span id="safety-bell-badge" style="display:none;position:absolute;top:-4px;right:-4px;
                   min-width:16px;height:16px;padding:0 4px;background:#ef4444;color:#fff;font-size:9px;
                   font-weight:800;line-height:16px;border-radius:8px;text-align:center;
                   border:2px solid var(--bg-main,#111827);pointer-events:none;">0</span>
        </a>

        <!-- Notification inbox bell -->
        <a id="notif-bell-btn" href="/notifications" title="Notifications"
           style="position:relative;display:inline-flex;align-items:center;justify-content:center;
                  width:36px;height:36px;border-radius:8px;background:var(--bg-card,#1e2535);
                  border:1px solid var(--border-color,rgba(255,255,255,0.08));
                  color:var(--text-secondary,#94a3b8);text-decoration:none;font-size:17px;">
            🔔
            <span id="notif-bell-badge" style="display:none;position:absolute;top:-4px;right:-4px;
                   min-width:16px;height:16px;padding:0 4px;background:#3b82f6;color:#fff;font-size:9px;
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

                <a href="/my-profile" role="menuitem"
                   style="display:flex;align-items:center;gap:8px;padding:10px 14px;color:var(--text-primary,#f1f5f9);
                          font-size:13px;text-decoration:none;">
                    <span>👤</span> Profile
                </a>
                <a href="/account/settings" role="menuitem"
                   style="display:flex;align-items:center;gap:8px;padding:10px 14px;color:var(--text-primary,#f1f5f9);
                          font-size:13px;text-decoration:none;">
                    <span>⚙️</span> Profile Settings
                </a>
                <a href="/2fa/setup" role="menuitem"
                   style="display:flex;align-items:center;gap:8px;padding:10px 14px;color:var(--text-primary,#f1f5f9);
                          font-size:13px;text-decoration:none;">
                    <span>🔐</span> Account Security
                </a>
                <a href="/logout" role="menuitem"
                   style="display:flex;align-items:center;gap:8px;padding:10px 14px;
                          color:var(--accent-red,#ef4444);font-size:13px;text-decoration:none;
                          border-top:1px solid var(--border-color,rgba(255,255,255,0.08));">
                    <span>🚪</span> Sign Out
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
