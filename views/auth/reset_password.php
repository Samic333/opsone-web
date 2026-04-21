<?php
$brand = file_exists(CONFIG_PATH . '/branding.php') ? require CONFIG_PATH . '/branding.php' : ['product_name' => 'OpsOne'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — <?= e($brand['product_name']) ?></title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <div class="login-logo">
            <div class="login-logo-icon">✈</div>
            <h1><?= e($brand['product_name']) ?></h1>
            <p>Choose a new password</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">⚠ <?= e($error) ?></div>
        <?php endif; ?>

        <?php if (empty($valid)): ?>
            <div class="alert alert-error" style="padding:12px;border-radius:6px;background:#3d1313;color:#ffb0b0;border:1px solid #8a2a2a;">
                ⚠ This reset link is invalid, expired, or has already been used.
                <a href="/forgot-password" style="color:#fff;text-decoration:underline;">Request a new one</a>.
            </div>
        <?php else: ?>
            <form method="POST" action="/reset-password">
                <?= csrfField() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="At least 10 characters, letters + digits" minlength="10" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password_confirm">Confirm Password</label>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-control"
                           placeholder="Type it again" minlength="10" required>
                </div>
                <button type="submit" class="btn btn-primary login-btn">Update Password</button>
            </form>
        <?php endif; ?>

        <p style="margin-top:16px;font-size:12px;color:var(--text-muted);text-align:center;">
            <a href="/login">← Back to sign in</a>
        </p>
    </div>
</div>
</body>
</html>
