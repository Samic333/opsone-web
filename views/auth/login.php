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
    <meta name="description" content="Sign in to the <?= e($brand['product_name']) ?> airline operations portal">
    <title>Sign In — <?= e($brand['product_name']) ?></title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
<div class="login-page">
    <div style="position: absolute; top: var(--spacing-lg); left: var(--spacing-lg);">
        <a href="/home" style="color: var(--text-secondary); text-decoration: none; font-size: 14px; display: flex; align-items: center; gap: 6px;">
            <span>←</span> Back to Website
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
                       placeholder="you@airline.com" required autofocus
                       value="<?= e($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" 
                       placeholder="Enter your password" required>
            </div>

            <button type="submit" class="btn btn-primary login-btn">Sign In</button>
        </form>

        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-color); text-align: center;">
            <p class="text-xs text-muted">Demo accounts — Password: <code style="color: var(--accent-cyan);">demo</code></p>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-top: 12px;">
                <?php
                $demos = [
                    ['ceo@airline.com', 'Airline Super Admin', '👑'],
                    ['admin@airline.com', 'System Admin', '🔧'],
                    ['hr@airline.com', 'HR Manager', '👥'],
                    ['doccontrol@airline.com', 'Doc Control', '📄'],
                    ['scheduling@airline.com', 'Scheduler', '🗓'],
                    ['chiefpilot@airline.com', 'Chief Pilot', '👨‍✈️'],
                    ['pilot@airline.com', 'Pilot', '✈'],
                    ['cabin@airline.com', 'Cabin Crew', '💁'],
                    ['engineer@airline.com', 'Engineer', '🧰'],
                    ['safety@airline.com', 'Safety Officer', '⚠'],
                    ['fdm@airline.com', 'FDM Analyst', '📊'],
                ];
                foreach ($demos as [$dEmail, $dLabel, $dIcon]):
                ?>
                <button type="button" onclick="document.getElementById('email').value='<?= $dEmail ?>';document.getElementById('password').value='demo';"
                        style="padding:6px 10px;background:var(--bg-input);border:1px solid var(--border-color);border-radius:var(--radius-sm);color:var(--text-secondary);font-size:11px;cursor:pointer;text-align:left;font-family:inherit;">
                    <?= $dIcon ?> <?= $dLabel ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
