<?php
$brand = file_exists(CONFIG_PATH . '/branding.php') ? require CONFIG_PATH . '/branding.php' : ['product_name' => 'OpsOne'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication — <?= e($brand['product_name']) ?></title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <div class="login-logo">
            <div class="login-logo-icon">🔐</div>
            <h1><?= e($brand['product_name']) ?></h1>
            <p>Two-Factor Verification</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">⚠ <?= e($error) ?></div>
        <?php endif; ?>

        <p style="font-size:13px;color:var(--text-secondary);margin-bottom:16px;">
            Enter the 6-digit code from your authenticator app, or a one-time backup code.
        </p>

        <form method="POST" action="/2fa/challenge">
            <?= csrfField() ?>
            <div class="form-group">
                <label for="code">Verification Code</label>
                <input type="text" id="code" name="code" class="form-control"
                       autocomplete="one-time-code" inputmode="numeric"
                       maxlength="16" placeholder="123 456" required autofocus
                       style="font-size:20px; letter-spacing:4px; text-align:center; font-family:monospace;">
            </div>
            <button type="submit" class="btn btn-primary login-btn">Verify</button>
        </form>

        <p style="margin-top:16px;font-size:12px;color:var(--text-muted);text-align:center;">
            <a href="/logout">← Cancel and sign out</a>
        </p>
    </div>
</div>
</body>
</html>
