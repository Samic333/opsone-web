<?php
$pageTitle = 'Activate Account';
$hideSidebar = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — OpsOne</title>
    <link rel="stylesheet" href="/css/app.css">
    <style>
        body {
            background-color: var(--bg-body);
            background-image: radial-gradient(circle at top right, rgba(99,102,241,0.05), transparent 40%);
        }
        .auth-container {
            max-width: 420px;
            margin: 6rem auto;
            padding: 2.5rem;
            background: var(--bg-card);
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border: 1px solid var(--border);
        }
    </style>
</head>
<body class="ctx-airline">
    <div class="auth-container">
        <div style="text-align: center; margin-bottom: 2rem;">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">✈</div>
            <h2 style="margin-bottom: 0.5rem; color: var(--text);">Join <?= e($tenant['name'] ?? 'Airline') ?></h2>
            <p style="color: var(--text-muted); font-size: 0.95rem;">Please set your password to activate your account</p>
        </div>
        
        <?php if ($msg = flash('error')): ?>
            <div class="alert alert-error" style="margin-bottom: 1.5rem;">⚠ <?= e($msg) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="/activate">
            <?= csrfField() ?>
            <input type="hidden" name="token" value="<?= e($tokenStr) ?>">
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label class="form-label">Name</label>
                <input type="text" class="form-control" value="<?= e($token['name']) ?>" disabled>
            </div>
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label class="form-label">Email</label>
                <input type="text" class="form-control" value="<?= e($token['email']) ?>" disabled>
            </div>
            
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label">Set Password</label>
                <input type="password" name="password" class="form-control" required minlength="8" placeholder="Minimum 8 characters">
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem; font-size: 1rem;">Activate Account</button>
        </form>
    </div>
</body>
</html>
