<?php
/**
 * OpsOne — Admin Portal Layout
 * Variables expected: $pageTitle, content rendered via sections
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($brand['product_name']) ?> — Airline operations and crew management portal">
    <title><?= e($pageTitle ?? 'Dashboard') ?> — <?= e($brand['product_name']) ?></title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
<div class="app-layout">
    <!-- Mobile Toggle -->
    <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle menu">
        <span></span><span></span><span></span>
    </button>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">✈</div>
            <div>
                <h1><?= e($brand['product_name']) ?></h1>
                <small><?= e($tenant['name'] ?? 'Control Portal') ?></small>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="sidebar-section">
                <div class="sidebar-section-title">Main</div>
                <a href="/" class="sidebar-link <?= $currentPath === '/' ? 'active' : '' ?>">
                    <span class="icon">📊</span> Dashboard
                </a>
            </div>

            <?php if (hasRole('super_admin') && isMultiTenant()): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Platform</div>
                <a href="/tenants" class="sidebar-link <?= str_starts_with($currentPath, '/tenants') ? 'active' : '' ?>">
                    <span class="icon">🏢</span> Airlines
                </a>
            </div>
            <?php endif; ?>

            <?php if (hasAnyRole(['super_admin', 'airline_admin', 'hr'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Management</div>
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
            <?php endif; ?>

            <?php if (hasAnyRole(['super_admin', 'airline_admin', 'hr', 'document_control', 'safety_officer'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Content</div>
                <a href="/files" class="sidebar-link <?= str_starts_with($currentPath, '/files') ? 'active' : '' ?>">
                    <span class="icon">📄</span> Documents
                </a>
            </div>
            <?php endif; ?>

            <?php if (hasAnyRole(['super_admin', 'airline_admin', 'safety_officer', 'document_control'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Communications</div>
                <a href="/notices" class="sidebar-link <?= str_starts_with($currentPath, '/notices') ? 'active' : '' ?>">
                    <span class="icon">📢</span> Notices
                </a>
            </div>
            <?php endif; ?>

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
                    <div class="sidebar-user-role"><?= e(ucwords(str_replace('_', ' ', $roles[0] ?? 'User'))) ?></div>
                </div>
            </div>
            <a href="/logout" class="sidebar-link mt-1" style="color: var(--accent-red);">
                <span class="icon">🚪</span> Sign Out
            </a>
        </div>
    </aside>

    <!-- Main Content -->
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
