<?php
/**
 * OpsOne — Admin Portal Layout
 */
$brand = $brand ?? (file_exists(CONFIG_PATH . '/branding.php') ? require CONFIG_PATH . '/branding.php' : ['product_name' => 'OpsOne']);
$user = currentUser();
$roles = $_SESSION['user_roles'] ?? [];
$tenant = $_SESSION['tenant'] ?? null;
$pendingDevices = 0;
try {
    $pendingDevices = Device::countPending(currentTenantId());
} catch (\Exception $e) {}

$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Derive a readable role label from first role slug
$roleLabelMap = [
    'super_admin'         => 'Platform Super Admin',
    'platform_support'    => 'Platform Support Admin',
    'platform_security'   => 'Platform Security Admin',
    'system_monitoring'   => 'System Monitoring Admin',
    'airline_admin'       => 'Airline Admin',
    'hr'                  => 'HR Admin',
    'scheduler'           => 'Scheduler Admin',
    'chief_pilot'         => 'Chief Pilot',
    'head_cabin_crew'     => 'Head of Cabin Crew',
    'engineering_manager' => 'Engineering Manager',
    'safety_officer'      => 'Safety Manager',
    'fdm_analyst'         => 'FDM Analyst',
    'document_control'    => 'Document Control Mgr',
    'base_manager'        => 'Base Manager',
    'pilot'               => 'Pilot',
    'cabin_crew'          => 'Cabin Crew',
    'engineer'            => 'Engineer',
    'training_admin'      => 'Training Admin',
    'director'            => 'Director',
];
$roleLabel = $roleLabelMap[$roles[0] ?? ''] ?? ucwords(str_replace('_', ' ', $roles[0] ?? 'User'));
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
<body>
<div class="app-layout">
    <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle menu">
        <span></span><span></span><span></span>
    </button>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">✈</div>
            <div>
                <h1><?= e($brand['product_name']) ?></h1>
                <small><?= e($tenant['name'] ?? 'Control Portal') ?></small>
            </div>
        </div>

        <nav class="sidebar-nav">

            <!-- ─── Main ──────────────────────────────── -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">Main</div>
                <a href="/dashboard" class="sidebar-link <?= str_starts_with($currentPath, '/dashboard') || $currentPath === '/' ? 'active' : '' ?>">
                    <span class="icon">📊</span> Dashboard
                </a>
            </div>

            <!-- ─── Platform (super_admin / support / security) ─ -->
            <?php if (hasAnyRole(['super_admin', 'platform_support', 'platform_security', 'system_monitoring']) && isMultiTenant()): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Platform</div>
                <?php if (hasRole('super_admin')): ?>
                <a href="/tenants" class="sidebar-link <?= str_starts_with($currentPath, '/tenants') ? 'active' : '' ?>">
                    <span class="icon">🏢</span> Airlines
                </a>
                <?php else: ?>
                <a href="/tenants" class="sidebar-link <?= str_starts_with($currentPath, '/tenants') ? 'active' : '' ?>" style="opacity:0.7;" title="Read-only view">
                    <span class="icon">🏢</span> Airlines <span style="font-size:9px;opacity:0.6;">(view only)</span>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- ─── People & Devices ───────────────────── -->
            <?php if (hasAnyRole(['super_admin', 'airline_admin', 'hr', 'training_admin'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">People</div>
                <a href="/users" class="sidebar-link <?= str_starts_with($currentPath, '/users') ? 'active' : '' ?>">
                    <span class="icon">👥</span> Users
                </a>
                <a href="/devices" class="sidebar-link <?= str_starts_with($currentPath, '/devices') ? 'active' : '' ?>">
                    <span class="icon">📱</span> Devices
                    <?php if ($pendingDevices > 0): ?>
                        <span class="badge"><?= $pendingDevices ?></span>
                    <?php endif; ?>
                </a>
            </div>
            <?php elseif (hasAnyRole(['chief_pilot', 'head_cabin_crew', 'engineering_manager', 'base_manager', 'safety_officer'])): ?>
            <!-- Management roles: devices only (for approval awareness) -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">Devices</div>
                <a href="/devices" class="sidebar-link <?= str_starts_with($currentPath, '/devices') ? 'active' : '' ?>">
                    <span class="icon">📱</span> iPad Devices
                    <?php if ($pendingDevices > 0): ?>
                        <span class="badge"><?= $pendingDevices ?></span>
                    <?php endif; ?>
                </a>
            </div>
            <?php endif; ?>

            <!-- ─── Scheduling (placeholder for roster module) ─ -->
            <?php if (hasAnyRole(['super_admin', 'airline_admin', 'scheduler', 'chief_pilot', 'head_cabin_crew', 'base_manager'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Scheduling</div>
                <a href="/dashboard" class="sidebar-link" style="opacity:0.55; cursor:default;" title="Roster module — coming in Phase 4">
                    <span class="icon">📅</span> Roster
                    <span style="font-size:9px;background:var(--accent-amber,#f59e0b);color:#000;padding:1px 5px;border-radius:3px;margin-left:4px;font-weight:700;">SOON</span>
                </a>
            </div>
            <?php endif; ?>

            <!-- ─── Content: Documents ─────────────────── -->
            <?php if (hasAnyRole(['super_admin', 'airline_admin', 'hr', 'document_control', 'safety_officer', 'chief_pilot', 'head_cabin_crew', 'engineering_manager', 'base_manager', 'training_admin', 'fdm_analyst'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Content</div>
                <a href="/files" class="sidebar-link <?= str_starts_with($currentPath, '/files') ? 'active' : '' ?>">
                    <span class="icon">📄</span> Documents
                </a>
                <?php if (hasAnyRole(['super_admin', 'airline_admin', 'safety_officer', 'document_control', 'chief_pilot', 'head_cabin_crew', 'engineering_manager', 'hr'])): ?>
                <a href="/notices" class="sidebar-link <?= str_starts_with($currentPath, '/notices') ? 'active' : '' ?>">
                    <span class="icon">📢</span> Notices
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- ─── Safety & FDM (placeholder) ────────── -->
            <?php if (hasAnyRole(['super_admin', 'airline_admin', 'safety_officer', 'fdm_analyst'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Safety</div>
                <a href="/dashboard" class="sidebar-link" style="opacity:0.55; cursor:default;" title="FDM upload — coming in Phase 5">
                    <span class="icon">📊</span> FDM Upload
                    <span style="font-size:9px;background:var(--accent-amber,#f59e0b);color:#000;padding:1px 5px;border-radius:3px;margin-left:4px;font-weight:700;">SOON</span>
                </a>
            </div>
            <?php endif; ?>

            <!-- ─── Operational crew: Documents & Notices ─ -->
            <?php if (hasAnyRole(['pilot', 'cabin_crew', 'engineer']) && !hasAnyRole(['airline_admin', 'hr', 'chief_pilot', 'head_cabin_crew', 'engineering_manager', 'safety_officer', 'document_control', 'fdm_analyst', 'base_manager', 'scheduler'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Operations</div>
                <a href="/files" class="sidebar-link <?= str_starts_with($currentPath, '/files') ? 'active' : '' ?>">
                    <span class="icon">📄</span> Documents
                </a>
                <a href="/notices" class="sidebar-link <?= str_starts_with($currentPath, '/notices') ? 'active' : '' ?>">
                    <span class="icon">📢</span> Notices
                </a>
            </div>
            <?php endif; ?>

            <!-- ─── App Install ────────────────────────── -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">App</div>
                <a href="/install" class="sidebar-link <?= str_starts_with($currentPath, '/install') ? 'active' : '' ?>">
                    <span class="icon">📲</span> Install App
                </a>
            </div>

        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-user-avatar">
                    <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?= e($user['name'] ?? '') ?></div>
                    <div class="sidebar-user-role"><?= e($roleLabel) ?></div>
                </div>
            </div>
            <a href="/logout" class="sidebar-link mt-1" style="color: var(--accent-red);">
                <span class="icon">🚪</span> Sign Out
            </a>
        </div>
    </aside>

    <main class="main-content">
        <div class="content-header">
            <div>
                <h2><?= e($pageTitle ?? 'Dashboard') ?></h2>
                <?php if (!empty($pageSubtitle)): ?>
                    <p><?= e($pageSubtitle) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <?php if (!empty($headerAction)): ?>
                    <?= $headerAction ?>
                <?php endif; ?>
            </div>
        </div>

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
    const toggle = document.getElementById('mobileToggle');
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
</script>
</body>
</html>
