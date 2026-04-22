<?php
/** @var array $user */
/** @var array $has  */
/** @var array $roles */
$u = $data['user'];
$has = $data['has'];
?>
<div class="card" style="max-width:720px;">
    <h3 style="margin:0 0 4px;font-size:1rem;font-weight:600;">Account</h3>
    <p style="margin:0 0 16px;color:var(--text-muted);font-size:12px;">
        These details appear in the header, emails, and activity logs. For crew-specific records
        (licences, medical, passport), use
        <a href="/my-profile" style="color:var(--accent-blue);">My Profile</a>.
    </p>

    <form method="POST" action="/account/settings/update" style="display:flex;flex-direction:column;gap:14px;">
        <?= csrfField() ?>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div>
                <label style="display:block;font-size:12px;color:var(--text-muted);margin-bottom:4px;">
                    Full name
                </label>
                <input type="text" name="name" required value="<?= e($u['name'] ?? '') ?>"
                       style="width:100%;padding:8px 10px;border:1px solid var(--border);
                              background:var(--bg-card,#1e2535);color:var(--text-primary);
                              border-radius:6px;font-size:13px;">
            </div>
            <div>
                <label style="display:block;font-size:12px;color:var(--text-muted);margin-bottom:4px;">
                    Email
                </label>
                <input type="email" name="email" required value="<?= e($u['email'] ?? '') ?>"
                       style="width:100%;padding:8px 10px;border:1px solid var(--border);
                              background:var(--bg-card,#1e2535);color:var(--text-primary);
                              border-radius:6px;font-size:13px;">
            </div>
        </div>

        <?php if (in_array('phone', $has, true)): ?>
        <div>
            <label style="display:block;font-size:12px;color:var(--text-muted);margin-bottom:4px;">Phone</label>
            <input type="text" name="phone" value="<?= e($u['phone'] ?? '') ?>"
                   style="width:100%;max-width:260px;padding:8px 10px;border:1px solid var(--border);
                          background:var(--bg-card,#1e2535);color:var(--text-primary);
                          border-radius:6px;font-size:13px;">
        </div>
        <?php endif; ?>

        <?php if (in_array('timezone', $has, true)): ?>
        <div>
            <label style="display:block;font-size:12px;color:var(--text-muted);margin-bottom:4px;">Timezone</label>
            <input type="text" name="timezone" placeholder="e.g. Africa/Addis_Ababa"
                   value="<?= e($u['timezone'] ?? '') ?>"
                   style="width:100%;max-width:260px;padding:8px 10px;border:1px solid var(--border);
                          background:var(--bg-card,#1e2535);color:var(--text-primary);
                          border-radius:6px;font-size:13px;">
        </div>
        <?php endif; ?>

        <?php if (in_array('avatar_url', $has, true)): ?>
        <div>
            <label style="display:block;font-size:12px;color:var(--text-muted);margin-bottom:4px;">
                Avatar URL (optional)
            </label>
            <input type="url" name="avatar_url" placeholder="https://…"
                   value="<?= e($u['avatar_url'] ?? '') ?>"
                   style="width:100%;padding:8px 10px;border:1px solid var(--border);
                          background:var(--bg-card,#1e2535);color:var(--text-primary);
                          border-radius:6px;font-size:13px;">
        </div>
        <?php endif; ?>

        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:6px;">
            <a href="/2fa/setup" class="btn btn-outline" style="font-size:12px;">
                🔐 Account Security →
            </a>
            <button type="submit" class="btn btn-primary" style="font-size:13px;">Save changes</button>
        </div>
    </form>
</div>

<div class="card" style="max-width:720px;margin-top:16px;">
    <h3 style="margin:0 0 10px;font-size:1rem;font-weight:600;">Your roles</h3>
    <?php if (empty($data['roles'])): ?>
        <p style="margin:0;color:var(--text-muted);font-size:13px;">No roles assigned.</p>
    <?php else: ?>
        <div style="display:flex;flex-wrap:wrap;gap:6px;">
            <?php foreach ($data['roles'] as $r): ?>
                <span style="padding:3px 9px;border-radius:12px;background:rgba(99,102,241,0.12);
                             color:#6366f1;font-size:11px;font-weight:600;">
                    <?= e($r['name'] ?? $r['slug'] ?? '—') ?>
                </span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <p style="margin:10px 0 0;color:var(--text-muted);font-size:11px;">
        Role assignments are controlled by your airline administrator.
    </p>
</div>
