<?php
/**
 * OpsOne — Admin Portal Layout
 *
 * Phase Zero: Platform users see ONLY platform navigation.
 *             Airline users see ONLY airline navigation.
 * Cleanup pass: sidebar is rendered from config/sidebar.php via NavigationService,
 *               header has a proper top-right profile dropdown.
 */
$brand = $brand ?? (file_exists(CONFIG_PATH . '/branding.php')
    ? require CONFIG_PATH . '/branding.php'
    : ['product_name' => 'OpsOne']);

$user    = currentUser();
$roles   = $_SESSION['user_roles'] ?? [];
$tenant  = $_SESSION['tenant'] ?? null;

$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$isPlat      = isPlatformOnly();

$roleLabelMap = [
    'super_admin'         => 'Platform Super Admin',
    'platform_support'    => 'Platform Support Admin',
    'platform_security'   => 'Platform Security Admin',
    'system_monitoring'   => 'System Monitoring',
    'airline_admin'       => 'Airline Admin',
    'hr'                  => 'HR Admin',
    'scheduler'           => 'Scheduler / Crew Control',
    'chief_pilot'         => 'Chief Pilot',
    'head_cabin_crew'     => 'Head of Cabin Crew',
    'engineering_manager' => 'Engineering Manager',
    'safety_officer'      => 'Safety Manager',
    'fdm_analyst'         => 'FDM Analyst',
    'document_control'    => 'Document Control',
    'base_manager'        => 'Base Manager',
    'training_admin'      => 'Training Admin',
    'pilot'               => 'Pilot',
    'cabin_crew'          => 'Cabin Crew',
    'engineer'            => 'Engineer',
    'director'            => 'Director',
];
$roleLabel = $roleLabelMap[$roles[0] ?? '']
           ?? ucwords(str_replace('_', ' ', $roles[0] ?? 'User'));

$brandName  = $isPlat ? ($brand['product_name'] . ' Platform') : $brand['product_name'];
$brandSmall = $isPlat ? 'Platform Administration' : ($tenant['name'] ?? 'Airline Portal');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Dashboard') ?> — <?= e($brand['product_name']) ?></title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="stylesheet" href="/css/app.css">
</head>
<body class="<?= $isPlat ? 'ctx-platform' : 'ctx-airline' ?>">
<div class="app-layout">
    <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle menu">
        <span></span><span></span><span></span>
    </button>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php require VIEWS_PATH . '/partials/sidebar.php'; ?>

    <main class="main-content">
        <?php require VIEWS_PATH . '/partials/header_bar.php'; ?>

        <div class="content-body">
            <?php if ($msg = flash('success')): ?>
                <div class="alert alert-success">✓ <?= e($msg) ?></div>
            <?php endif; ?>
            <?php if ($msg = flash('error')): ?>
                <div class="alert alert-error">⚠ <?= e($msg) ?></div>
            <?php endif; ?>

            <?= $content ?? '' ?>
        </div>
    </main>
</div>
<script>
(function() {
    const toggle  = document.getElementById('mobileToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (toggle && sidebar && overlay) {
        toggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        });
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });
    }
})();

// ── Safety Notification Bell — live poll every 30 s ──────────────────────
(function() {
    var badge = document.getElementById('safety-bell-badge');
    var btn   = document.getElementById('safety-bell-btn');
    if (!badge || !btn) return;

    function updateBell() {
        fetch('/safety/notifications/count', {credentials: 'same-origin'})
            .then(function(r) { return r.ok ? r.json() : null; })
            .then(function(data) {
                if (!data) return;
                var n = parseInt(data.count, 10) || 0;
                badge.textContent = n > 99 ? '99+' : String(n);
                if (n > 0) {
                    badge.style.display = 'block';
                    btn.style.borderColor = 'rgba(239,68,68,0.6)';
                    btn.title = (data.for_team
                        ? 'Pilot replies waiting: ' + n
                        : 'Safety team messages: ' + n);
                } else {
                    badge.style.display = 'none';
                    btn.style.borderColor = 'var(--border-color,rgba(255,255,255,0.08))';
                }
            })
            .catch(function() { /* silently ignore network errors */ });
    }

    updateBell();
    setInterval(updateBell, 30000);
})();

// ── Notification Inbox Bell — poll every 30 s ──────────────────
(function() {
    var badge = document.getElementById('notif-bell-badge');
    var btn   = document.getElementById('notif-bell-btn');
    if (!badge || !btn) return;

    function updateNotif() {
        fetch('/notifications/unread-count', {credentials: 'same-origin', headers: {'Accept':'application/json'}})
            .then(function(r) { return r.ok ? r.json() : null; })
            .then(function(data) {
                if (!data) return;
                var n = (parseInt(data.unread, 10) || 0);
                badge.textContent = n > 99 ? '99+' : String(n);
                if (n > 0) {
                    badge.style.display = 'block';
                    var loud = (parseInt(data.loud, 10) || 0);
                    btn.style.borderColor = loud > 0 ? 'rgba(245,158,11,0.6)' : 'rgba(59,130,246,0.6)';
                    badge.style.background = loud > 0 ? '#f59e0b' : '#3b82f6';
                    btn.title = 'Unread notifications: ' + n + (loud > 0 ? ' (' + loud + ' loud)' : '');
                } else {
                    badge.style.display = 'none';
                    btn.style.borderColor = 'var(--border-color,rgba(255,255,255,0.08))';
                }
            })
            .catch(function() { /* silent */ });
    }

    updateNotif();
    setInterval(updateNotif, 30000);
})();
</script>
</body>
</html>
