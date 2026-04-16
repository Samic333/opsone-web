<?php
/**
 * OpsOne — Admin Portal Layout
 *
 * Phase Zero: Platform users see ONLY platform navigation.
 *             Airline users see ONLY airline navigation.
 *             Cross-contamination of sidebar items is blocked.
 */
$brand = $brand ?? (file_exists(CONFIG_PATH . '/branding.php')
    ? require CONFIG_PATH . '/branding.php'
    : ['product_name' => 'OpsOne']);

$user    = currentUser();
$roles   = $_SESSION['user_roles'] ?? [];
$tenant  = $_SESSION['tenant'] ?? null;

$pendingDevices = 0;
try {
    if (!isPlatformOnly() && currentTenantId()) {
        $pendingDevices = Device::countPending(currentTenantId());
    }
} catch (\Exception $e) {}

$pendingOnboarding = 0;
try {
    if (isPlatformOnly()) {
        $pendingOnboarding = OnboardingRequest::countPending();
    }
} catch (\Exception $e) {}

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

    <aside class="sidebar <?= $isPlat ? 'sidebar-platform' : 'sidebar-airline' ?>" id="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon"><?= $isPlat ? '🛡' : '✈' ?></div>
            <div>
                <h1><?= e($brandName) ?></h1>
                <small><?= e($brandSmall) ?></small>
            </div>
        </div>

        <nav class="sidebar-nav">

        <?php if ($isPlat): ?>
        <!-- ═══════════════════════════════════════════════════════
             PLATFORM NAVIGATION — visible to platform admins only
             ═══════════════════════════════════════════════════════ -->

            <div class="sidebar-section">
                <div class="sidebar-section-title">Platform</div>
                <a href="/dashboard" class="sidebar-link <?= str_starts_with($currentPath, '/dashboard') ? 'active' : '' ?>">
                    <span class="icon">📊</span> Platform Overview
                </a>
            </div>

            <?php if (hasAnyRole(['super_admin', 'platform_support', 'system_monitoring'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Airlines</div>
                <a href="/tenants" class="sidebar-link <?= str_starts_with($currentPath, '/tenants') ? 'active' : '' ?>">
                    <span class="icon">🏢</span> Airline Registry
                </a>
                <?php if (hasRole('super_admin')): ?>
                <a href="/platform/onboarding" class="sidebar-link <?= str_starts_with($currentPath, '/platform/onboarding') ? 'active' : '' ?>">
                    <span class="icon">✈</span> Onboarding
                    <?php if ($pendingOnboarding > 0): ?>
                        <span class="badge"><?= $pendingOnboarding ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (hasRole('super_admin')): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Platform Staff</div>
                <a href="/platform/users" class="sidebar-link <?= str_starts_with($currentPath, '/platform/users') ? 'active' : '' ?>">
                    <span class="icon">👤</span> Staff Accounts
                </a>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Configuration</div>
                <a href="/platform/modules" class="sidebar-link <?= str_starts_with($currentPath, '/platform/modules') ? 'active' : '' ?>">
                    <span class="icon">🧩</span> Module Catalog
                </a>
            </div>
            <?php endif; ?>

            <?php if (hasAnyRole(['super_admin', 'platform_security'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Security</div>
                <a href="/audit-log" class="sidebar-link <?= str_starts_with($currentPath, '/audit-log') ? 'active' : '' ?>">
                    <span class="icon">🔒</span> Audit Log
                </a>
                <a href="/audit-log/logins" class="sidebar-link <?= str_starts_with($currentPath, '/audit-log/logins') ? 'active' : '' ?>">
                    <span class="icon">🔑</span> Login Activity
                </a>
            </div>
            <?php endif; ?>

            <?php if (hasAnyRole(['super_admin', 'platform_support'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Support</div>
                <a href="/devices" class="sidebar-link <?= str_starts_with($currentPath, '/devices') ? 'active' : '' ?>">
                    <span class="icon">📱</span> All Devices
                </a>
                <a href="/install" class="sidebar-link <?= str_starts_with($currentPath, '/install') ? 'active' : '' ?>">
                    <span class="icon">📲</span> App Builds
                </a>
            </div>
            <?php endif; ?>

            <!-- Platform notice: accessing airline data -->
            <div class="sidebar-section" style="margin-top: auto;">
                <div style="padding: 10px 12px; background: rgba(245,158,11,0.1); border-radius: 6px;
                            border-left: 3px solid #f59e0b; font-size: 11px; color: var(--text-muted);">
                    <strong style="color: #f59e0b;">⚠ Platform Mode</strong><br>
                    To access airline operational data, open the airline record and use
                    <em>Controlled Access</em>.
                </div>
            </div>

        <?php else: ?>
        <!-- ═══════════════════════════════════════════════════════
             AIRLINE NAVIGATION — visible to airline/tenant users
             ═══════════════════════════════════════════════════════ -->

            <div class="sidebar-section">
                <div class="sidebar-section-title">Main</div>
                <a href="/dashboard" class="sidebar-link <?= str_starts_with($currentPath, '/dashboard') || $currentPath === '/' ? 'active' : '' ?>">
                    <span class="icon">📊</span> Dashboard
                </a>
            </div>

            <!-- ─── People & Devices ─────────────────────── -->
            <?php if (hasAnyRole(['airline_admin', 'hr', 'training_admin'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">People</div>
                <a href="/users" class="sidebar-link <?= str_starts_with($currentPath, '/users') ? 'active' : '' ?>">
                    <span class="icon">👥</span> Users
                </a>
                <a href="/crew-profiles" class="sidebar-link <?= str_starts_with($currentPath, '/crew-profiles') ? 'active' : '' ?>">
                    <span class="icon">🪪</span> Crew Profiles
                </a>
                <a href="/devices" class="sidebar-link <?= str_starts_with($currentPath, '/devices') ? 'active' : '' ?>">
                    <span class="icon">📱</span> Devices
                    <?php if ($pendingDevices > 0): ?>
                        <span class="badge"><?= $pendingDevices ?></span>
                    <?php endif; ?>
                </a>
            </div>
            <?php elseif (hasAnyRole(['chief_pilot','head_cabin_crew','engineering_manager','base_manager','safety_officer'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">People</div>
                <a href="/crew-profiles" class="sidebar-link <?= str_starts_with($currentPath, '/crew-profiles') ? 'active' : '' ?>">
                    <span class="icon">🪪</span> Crew Profiles
                </a>
                <a href="/devices" class="sidebar-link <?= str_starts_with($currentPath, '/devices') ? 'active' : '' ?>">
                    <span class="icon">📱</span> iPad Devices
                    <?php if ($pendingDevices > 0): ?>
                        <span class="badge"><?= $pendingDevices ?></span>
                    <?php endif; ?>
                </a>
            </div>
            <?php endif; ?>

            <!-- ─── Me (all logged-in crew) ─────────────── -->
            <?php if (hasAnyRole(['pilot','cabin_crew','engineer','scheduler','chief_pilot',
                                   'head_cabin_crew','engineering_manager','base_manager',
                                   'training_admin','fdm_analyst','document_control','safety_officer'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Me</div>
                <a href="/my-profile" class="sidebar-link <?= str_starts_with($currentPath, '/my-profile') ? 'active' : '' ?>">
                    <span class="icon">👤</span> My Profile
                </a>
                <a href="/my-notices" class="sidebar-link <?= str_starts_with($currentPath, '/my-notices') ? 'active' : '' ?>">
                    <span class="icon">📬</span> My Notices
                </a>
                <a href="/safety/my-reports" class="sidebar-link <?= str_starts_with($currentPath, '/safety/my-reports') || str_starts_with($currentPath, '/safety/submit') ? 'active' : '' ?>">
                    <span class="icon">🛡️</span> My Reports
                </a>
            </div>
            <?php endif; ?>

            <!-- ─── Scheduling ───────────────────────────── -->
            <?php if (hasAnyRole(['airline_admin','scheduler','chief_pilot','head_cabin_crew','base_manager','pilot','cabin_crew','engineer'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Scheduling</div>
                <a href="/roster" class="sidebar-link <?= ($currentPath === '/roster' || $currentPath === '/roster/assign') ? 'active' : '' ?>">
                    <span class="icon">📅</span> Roster
                </a>
                <?php if (hasAnyRole(['airline_admin','scheduler','chief_pilot','head_cabin_crew'])): ?>
                <a href="/roster/standby" class="sidebar-link <?= str_starts_with($currentPath, '/roster/standby') ? 'active' : '' ?>">
                    <span class="icon">📋</span> Standby Pool
                </a>
                <?php endif; ?>
                <?php if (hasAnyRole(['airline_admin','scheduler','chief_pilot','head_cabin_crew'])): ?>
                <a href="/roster/changes" class="sidebar-link <?= str_starts_with($currentPath, '/roster/changes') ? 'active' : '' ?>">
                    <span class="icon">💬</span> Change Requests
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- ─── Content: Documents & Notices ─────────── -->
            <?php if (hasAnyRole(['airline_admin','hr','document_control','safety_officer','chief_pilot',
                                   'head_cabin_crew','engineering_manager','base_manager','training_admin',
                                   'fdm_analyst','pilot','cabin_crew','engineer'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Content</div>
                <a href="/files" class="sidebar-link <?= str_starts_with($currentPath, '/files') ? 'active' : '' ?>">
                    <span class="icon">📄</span> Documents
                </a>
                <?php if (hasAnyRole(['airline_admin','safety_officer','document_control','chief_pilot',
                                       'head_cabin_crew','engineering_manager','hr','training_admin'])): ?>
                <a href="/notices" class="sidebar-link <?= str_starts_with($currentPath, '/notices') ? 'active' : '' ?>">
                    <span class="icon">📢</span> Notices
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- ─── Safety & Compliance ──────────────────── -->
            <?php if (hasAnyRole(['airline_admin','safety_officer','fdm_analyst','chief_pilot','hr'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Safety</div>
                <?php if (hasAnyRole(['airline_admin','safety_officer','fdm_analyst'])): ?>
                <a href="/fdm" class="sidebar-link <?= str_starts_with($currentPath, '/fdm') ? 'active' : '' ?>">
                    <span class="icon">📊</span> FDM Data
                </a>
                <?php endif; ?>
                <?php if (hasAnyRole(['airline_admin','safety_officer'])): ?>
                <a href="/safety" class="sidebar-link <?= str_starts_with($currentPath, '/safety') && !str_starts_with($currentPath, '/safety/my') && !str_starts_with($currentPath, '/safety/submit') ? 'active' : '' ?>">
                    <span class="icon">🚨</span> Investigations
                </a>
                <?php endif; ?>
                <a href="/compliance" class="sidebar-link <?= str_starts_with($currentPath, '/compliance') ? 'active' : '' ?>">
                    <span class="icon">✅</span> Compliance
                </a>
            </div>
            <?php endif; ?>

            <!-- ─── Audit Log (airline level) ────────────── -->
            <?php if (hasAnyRole(['airline_admin','safety_officer'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Security</div>
                <a href="/audit-log" class="sidebar-link <?= str_starts_with($currentPath, '/audit-log') ? 'active' : '' ?>">
                    <span class="icon">🔒</span> Audit Log
                </a>
            </div>
            <?php endif; ?>

            <!-- ─── Administration ───────────────────────── -->
            <?php if (hasAnyRole(['airline_admin', 'hr', 'base_manager', 'chief_pilot', 'engineering_manager'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Administration</div>
                <?php if (hasAnyRole(['airline_admin', 'hr'])): ?>
                <a href="/departments" class="sidebar-link <?= str_starts_with($currentPath, '/departments') ? 'active' : '' ?>">
                    <span class="icon">🏢</span> Departments
                </a>
                <?php endif; ?>
                <?php if (hasAnyRole(['airline_admin', 'base_manager'])): ?>
                <a href="/bases" class="sidebar-link <?= str_starts_with($currentPath, '/bases') ? 'active' : '' ?>">
                    <span class="icon">📍</span> Bases
                </a>
                <?php endif; ?>
                <?php if (hasAnyRole(['airline_admin', 'chief_pilot', 'engineering_manager'])): ?>
                <a href="/fleets" class="sidebar-link <?= str_starts_with($currentPath, '/fleets') ? 'active' : '' ?>">
                    <span class="icon">✈</span> Fleets
                </a>
                <?php endif; ?>
                <?php if (hasRole('airline_admin')): ?>
                <a href="/roles" class="sidebar-link <?= str_starts_with($currentPath, '/roles') ? 'active' : '' ?>">
                    <span class="icon">🛡</span> Roles & Permissions
                </a>
                <a href="/airline/profile" class="sidebar-link <?= str_starts_with($currentPath, '/airline') ? 'active' : '' ?>">
                    <span class="icon">⚙️</span> Airline Profile
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- ─── App Install ───────────────────────────── -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">App</div>
                <a href="/install" class="sidebar-link <?= str_starts_with($currentPath, '/install') ? 'active' : '' ?>">
                    <span class="icon">📲</span> Install App
                </a>
            </div>

        <?php endif; ?>
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
</script>
</body>
</html>
