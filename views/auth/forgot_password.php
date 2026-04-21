<?php
$brand = file_exists(CONFIG_PATH . '/branding.php') ? require CONFIG_PATH . '/branding.php' : ['product_name' => 'OpsOne'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — <?= e($brand['product_name']) ?></title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
<div class="login-page">
    <div style="position: absolute; top: var(--spacing-lg); left: var(--spacing-lg);">
        <a href="/login" style="color: var(--text-secondary); text-decoration: none; font-size: 14px;">← Back to Sign In</a>
    </div>
    <div class="login-card">
        <div class="login-logo">
            <div class="login-logo-icon">✈</div>
            <h1><?= e($brand['product_name']) ?></h1>
            <p>Password Reset</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">⚠ <?= e($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success" style="background:#0f3d2b;color:#7ee8b4;border:1px solid #2a7f5a;padding:12px;border-radius:6px;margin-bottom:16px;">✓ <?= e($success) ?></div>
        <?php endif; ?>

        <p style="font-size:13px;color:var(--text-secondary);margin-bottom:16px;">
            Enter your account email. If it's on file you'll receive a reset link valid for two hours.
        </p>

        <form method="POST" action="/forgot-password">
            <?= csrfField() ?>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control"
                       placeholder="you@airline.com" required autofocus
                       value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-primary login-btn">Send Reset Link</button>
        </form>

        <p style="margin-top:16px;font-size:12px;color:var(--text-muted);text-align:center;">
            Still stuck? Contact your airline administrator or <a href="/support">support</a>.
        </p>
    </div>
</div>
</body>
</html>
