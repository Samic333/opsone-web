<?php
/**
 * OpsOne — Login Page
 */
$brand = file_exists(CONFIG_PATH . '/branding.php') ? require CONFIG_PATH . '/branding.php' : ['product_name' => 'OpsOne'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — <?= e($brand['product_name']) ?></title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="stylesheet" href="/css/app.css">
    <style>
        .demo-section { margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-color); }
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
                <div class="login-logo-icon">✈</div>
                <h1><?= e($brand['product_name']) ?></h1>
            </a>
            <p>Airline Operations Portal</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">⚠ <?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/login">
            <?= csrfField() ?>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control"
                       placeholder="demo.[role]@acentoza.com" required autofocus
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
            <p style="text-align:center; font-size:12px; color:var(--text-muted);">
                Dev-only — Password: <code style="color:var(--accent-cyan);">DemoOps2026!</code>
            </p>

            <div class="demo-grid">
                <div class="demo-group-label">Platform Level</div>
                <?php
                $demos = [
                    // [email, label, icon, group]
                    // Platform
                    ['demo.superadmin@acentoza.com', 'Platform Super Admin', '👑', 'platform'],
                    ['demo.support@acentoza.com',    'Platform Support',     '🛎', 'platform'],
                    ['demo.security@acentoza.com',   'Platform Security',    '🔒', 'platform'],
                    // Airline
                    ['demo.airadmin@acentoza.com',   'Airline Super Admin',  '✈',  'airline'],
                    // Management
                    ['demo.hr@acentoza.com',         'HR Admin',             '👥', 'mgmt'],
                    ['demo.scheduler@acentoza.com',  'Scheduler Admin',      '🗓', 'mgmt'],
                    ['demo.chiefpilot@acentoza.com', 'Chief Pilot',          '🛫', 'mgmt'],
                    ['demo.headcabin@acentoza.com',  'Head of Cabin Crew',   '💁', 'mgmt'],
                    ['demo.engmanager@acentoza.com', 'Engineering Manager',  '⚙',  'mgmt'],
                    ['demo.safety@acentoza.com',     'Safety Manager',       '⚠',  'mgmt'],
                    ['demo.fdm@acentoza.com',        'FDM Analyst',          '📊', 'mgmt'],
                    ['demo.doccontrol@acentoza.com', 'Doc Control Mgr',      '📄', 'mgmt'],
                    ['demo.basemanager@acentoza.com','Base Manager',          '🏠', 'mgmt'],
                    // Operational
                    ['demo.pilot@acentoza.com',      'Pilot',                '🛩', 'ops'],
                    ['demo.cabin@acentoza.com',      'Cabin Crew',           '🧳', 'ops'],
                    ['demo.engineer@acentoza.com',   'Engineer',             '🔧', 'ops'],
                    // Optional
                    ['demo.training@acentoza.com',   'Training Admin',       '🎓', 'opt'],
                    ['demo.sysmonitor@acentoza.com', 'System Monitor',       '🖥', 'opt'],
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
    </div>
</div>
</body>
</html>
