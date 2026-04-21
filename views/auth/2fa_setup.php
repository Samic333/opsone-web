<?php
// Renders inside layouts/app.php — $content wraps this file.
// Variables: $isEnabled (bool), $secret, $provisioningUri, $error, $success, $justGeneratedBackup
$qrDataUri = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data='
           . urlencode($provisioningUri);
?>
<div style="max-width: 720px; margin: 0 auto;">
    <?php if (!empty($error)): ?>
        <div class="alert alert-error" style="margin-bottom:16px;">⚠ <?= e($error) ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success" style="margin-bottom:16px;background:#0f3d2b;color:#7ee8b4;border:1px solid #2a7f5a;padding:12px;border-radius:6px;">✓ <?= e($success) ?></div>
    <?php endif; ?>

    <?php if (!empty($justGeneratedBackup) && is_array($justGeneratedBackup)): ?>
        <div class="card" style="margin-bottom:24px;border:1px solid #f59e0b;">
            <div class="card-header"><div class="card-title" style="color:#f59e0b;">⚠ Save these backup codes</div></div>
            <div class="card-body">
                <p style="color:var(--text-secondary);font-size:13px;">Each code works once. Store them somewhere safe — you won't see them again.</p>
                <div style="display:grid;grid-template-columns:repeat(4, 1fr);gap:8px;font-family:monospace;font-size:16px;margin-top:12px;">
                    <?php foreach ($justGeneratedBackup as $bc): ?>
                        <div style="padding:8px;background:var(--bg-input);border-radius:6px;text-align:center;letter-spacing:2px;"><?= e($bc) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-header"><div class="card-title"><?= $isEnabled ? 'Two-Factor Authentication is Active' : 'Enable Two-Factor Authentication' ?></div></div>
        <div class="card-body">
            <?php if ($isEnabled): ?>
                <p style="color:#7ee8b4;">✓ Your account is protected with an authenticator app.</p>
                <p style="color:var(--text-secondary);font-size:13px;">To disable, enter a current code and click Disable. We strongly recommend keeping 2FA on — especially for platform and airline admin roles.</p>
                <form method="POST" action="/2fa/disable" style="margin-top:16px;display:flex;gap:8px;align-items:flex-end;">
                    <?= csrfField() ?>
                    <div style="flex:1;">
                        <label class="form-label">Current 6-digit code</label>
                        <input type="text" name="code" class="form-control" maxlength="16" required inputmode="numeric" placeholder="123 456">
                    </div>
                    <button type="submit" class="btn btn-danger">Disable 2FA</button>
                </form>
            <?php else: ?>
                <ol style="padding-left:20px;color:var(--text-secondary);font-size:14px;line-height:1.7;">
                    <li>Install an authenticator app (Google Authenticator, 1Password, Microsoft Authenticator, Authy, etc.)</li>
                    <li>Scan the QR code below, or enter the secret manually.</li>
                    <li>Enter the 6-digit code shown in your app to confirm enrolment.</li>
                </ol>
                <div style="display:flex;gap:24px;margin-top:16px;align-items:flex-start;flex-wrap:wrap;">
                    <div style="flex:0 0 auto;padding:10px;background:#fff;border-radius:8px;">
                        <img src="<?= e($qrDataUri) ?>" alt="QR code" width="200" height="200" style="display:block;">
                    </div>
                    <div style="flex:1;min-width:240px;">
                        <label class="form-label">Secret (enter this if QR doesn't scan)</label>
                        <input type="text" class="form-control" value="<?= e($secret) ?>" readonly
                               style="font-family:monospace;letter-spacing:2px;font-size:13px;">
                        <p style="font-size:12px;color:var(--text-muted);margin-top:8px;">
                            Account: <code><?= e($_SESSION['user']['email'] ?? '') ?></code>
                            · Type: Time-based · Digits: 6
                        </p>

                        <form method="POST" action="/2fa/setup" style="margin-top:16px;">
                            <?= csrfField() ?>
                            <label class="form-label" for="confirmCode">Confirmation code</label>
                            <input type="text" id="confirmCode" name="code" class="form-control" required
                                   inputmode="numeric" maxlength="16" autocomplete="one-time-code"
                                   placeholder="123 456"
                                   style="font-size:18px;letter-spacing:3px;text-align:center;font-family:monospace;">
                            <button type="submit" class="btn btn-primary" style="margin-top:12px;">Confirm &amp; Enable</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <p style="text-align:center;font-size:12px;color:var(--text-muted);">
        <a href="/dashboard">← Back to dashboard</a>
    </p>
</div>
