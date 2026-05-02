<?php
/**
 * OpsVelo — Login Page
 */
$brand = file_exists(CONFIG_PATH . '/branding.php') ? require CONFIG_PATH . '/branding.php' : ['product_name' => 'OpsVelo'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — <?= e($brand['product_name']) ?></title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/favicon.svg">
    <link rel="stylesheet" href="/css/app.css">
    <style>
        .login-eyebrow {
            font-size: 11px; font-weight: 700;
            letter-spacing: 0.12em; text-transform: uppercase;
            color: var(--accent-cyan, #06b6d4);
            margin-bottom: 8px;
        }
        .login-card-footnote {
            margin-top: 22px; padding-top: 16px;
            border-top: 1px solid var(--border-color);
            text-align: center; font-size: 12px;
            color: var(--text-tertiary, #7484a8);
            display: flex; align-items: center; justify-content: center; gap: 6px;
        }
        .demo-section { margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-color); }
        .demo-warning-banner {
            background: rgba(245, 158, 11, 0.08);
            border: 1px solid rgba(245, 158, 11, 0.35);
            border-radius: var(--radius-sm, 6px);
            padding: 12px 14px;
            margin-bottom: 12px;
        }
        .demo-warning-eyebrow {
            display: flex; align-items: center; gap: 6px;
            font-size: 10px; font-weight: 800;
            letter-spacing: 0.10em; text-transform: uppercase;
            color: var(--accent-yellow, #f59e0b);
            margin-bottom: 6px;
        }
        .demo-warning-eyebrow svg { color: var(--accent-yellow, #f59e0b); }
        .demo-warning-banner p {
            font-size: 12px; line-height: 1.5;
            color: var(--text-secondary, #8b95b0);
            margin: 4px 0 0;
        }
        .demo-warning-banner code {
            color: var(--accent-cyan, #06b6d4);
            font-family: ui-monospace, 'JetBrains Mono', monospace;
            font-size: 11px;
            background: rgba(6, 182, 212, 0.08);
            border: 1px solid rgba(6, 182, 212, 0.2);
            padding: 1px 5px; border-radius: 3px;
        }
        .demo-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 5px; margin-top: 10px; }
        .demo-group-label { grid-column: 1 / -1; font-size: 10px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text-muted); margin: 8px 0 2px; }
        .demo-btn { padding: 6px 10px; background: var(--bg-input); border: 1px solid var(--border-color); border-radius: var(--radius-sm); color: var(--text-secondary); font-size: 11px; cursor: pointer; text-align: left; font-family: inherit; transition: background 0.15s, border-color 0.15s; }
        .demo-btn:hover { background: var(--bg-hover, var(--bg-card)); border-color: var(--accent-blue); color: var(--text-primary); }
    </style>
</head>
<body>
<div class="login-page">
    <div style="position: absolute; top: var(--spacing-lg); left: var(--spacing-lg);">
        <a href="/home" style="color: var(--text-secondary); text-decoration: none; font-size: 14px; display: flex; align-items: center; gap: 6px;">
            ← Back to Website
        </a>
    </div>
    <div class="login-card">
        <div class="login-logo">
            <a href="/home" style="text-decoration: none; color: inherit; display: inline-block;">
                <?php if (!empty($tenant['logo_path'])): ?>
                    <img src="<?= e($tenant['logo_path']) ?>" alt="<?= e($tenant['display_name'] ?? $tenant['name']) ?>" style="width: 64px; height: 64px; object-fit: contain; border-radius: 12px; margin-bottom: 12px;">
                <?php else: ?>
                    <div class="login-logo-icon"><?= opsoneLogoMark(36, '#06b6d4', '#e8eaf0') ?></div>
                <?php endif; ?>
                <?php if (!empty($tenant)): ?>
                    <div class="login-eyebrow">Airline Operations Portal</div>
                <?php endif; ?>
                <h1>
                    <?php if (!empty($tenant)): ?>
                        <?= e($tenant['display_name'] ?? $tenant['name']) ?>
                    <?php else: ?>
                        <?= opsoneWordmark('lg') ?>
                    <?php endif; ?>
                </h1>
            </a>
            <p>
                <?php if (!empty($tenant)): ?>
                    Sign in to your operations portal
                <?php else: ?>
                    Airline Operations Portal
                <?php endif; ?>
            </p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><span class="alert-icon"><?= sidebarIcon('exclamation', 16) ?></span><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= !empty($tenantSlug) ? '/airline/' . urlencode($tenantSlug) . '/login' : '/login' ?>">
            <?= csrfField() ?>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control"
                       placeholder="you@yourairline.com" required autofocus
                       value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control"
                       placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn btn-primary login-btn">Sign In</button>
            <p style="text-align:center;margin-top:12px;font-size:12px;">
                <a href="/forgot-password" style="color:var(--text-secondary);">Forgot your password?</a>
            </p>
        </form>

        <?php
        // SECURITY: demo-account quick-picker + password display is a public-takeover vector in prod.
        // Only render when APP_ENV is 'development' or 'local' AND APP_DEBUG is true.
        $__env   = env('APP_ENV', 'production');
        $__debug = env('APP_DEBUG', 'false') === 'true';
        $showDemoBlock = in_array($__env, ['development','local','dev'], true) && $__debug;
        ?>
        <?php if ($showDemoBlock): ?>
        <div class="demo-section">
            <div class="demo-warning-banner" role="note" aria-label="Internal demo notice">
                <div class="demo-warning-eyebrow"><?= sidebarIcon('exclamation', 12) ?> Internal demo · Development testing only</div>
                <p>Hidden in production. Shared seed password used for local QA only — never used by real airline accounts.</p>
                <p>Password: <code>DemoOps2026!</code></p>
            </div>

            <div class="demo-grid">
                <div class="demo-group-label">Platform Level</div>
                <?php
                $demos = [
                    // [email, label, icon, group]
                    // Platform
                    ['demo.superadmin@opsvelo.com', 'Platform Super Admin', '👑', 'platform'],
                    ['demo.support@opsvelo.com',    'Platform Support',     '🛎', 'platform'],
                    ['demo.security@opsvelo.com',   'Platform Security',    '🔒', 'platform'],
                    // Airline
                    ['demo.airadmin@opsvelo.com',   'Airline Super Admin',  '✈',  'airline'],
                    // Management
                    ['demo.hr@opsvelo.com',         'HR Admin',             '👥', 'mgmt'],
                    ['demo.scheduler@opsvelo.com',  'Scheduler Admin',      '🗓', 'mgmt'],
                    ['demo.chiefpilot@opsvelo.com', 'Chief Pilot',          '🛫', 'mgmt'],
                    ['demo.headcabin@opsvelo.com',  'Head of Cabin Crew',   '💁', 'mgmt'],
                    ['demo.engmanager@opsvelo.com', 'Engineering Manager',  '⚙',  'mgmt'],
                    ['demo.safety@opsvelo.com',     'Safety Manager',       '⚠',  'mgmt'],
                    ['demo.fdm@opsvelo.com',        'FDM Analyst',          '📊', 'mgmt'],
                    ['demo.doccontrol@opsvelo.com', 'Doc Control Mgr',      '📄', 'mgmt'],
                    ['demo.basemanager@opsvelo.com','Base Manager',          '🏠', 'mgmt'],
                    // Operational
                    ['demo.pilot@opsvelo.com',      'Pilot',                '🛩', 'ops'],
                    ['demo.cabin@opsvelo.com',      'Cabin Crew',           '🧳', 'ops'],
                    ['demo.engineer@opsvelo.com',   'Engineer',             '🔧', 'ops'],
                    // Optional
                    ['demo.training@opsvelo.com',   'Training Admin',       '🎓', 'opt'],
                    ['demo.sysmonitor@opsvelo.com', 'System Monitor',       '🖥', 'opt'],
                ];
                $currentGroup = 'platform';
                $groupLabels = [
                    'airline' => 'Airline Level',
                    'mgmt'    => 'Management Level',
                    'ops'     => 'Operational Crew',
                    'opt'     => 'Optional Roles',
                ];
                foreach ($demos as [$dEmail, $dLabel, $dIcon, $dGroup]):
                    if ($dGroup !== $currentGroup):
                        $currentGroup = $dGroup;
                        echo '<div class="demo-group-label">' . e($groupLabels[$dGroup] ?? $dGroup) . '</div>';
                    endif;
                ?>
                <button type="button"
                        onclick="document.getElementById('email').value='<?= $dEmail ?>';document.getElementById('password').value='DemoOps2026!';"
                        class="demo-btn">
                    <?= $dIcon ?> <?= e($dLabel) ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; /* $showDemoBlock */ ?>

        <?php if (!empty($tenant)): ?>
            <p class="login-card-footnote">
                Powered by <?= opsoneWordmark('sm') ?>
            </p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
