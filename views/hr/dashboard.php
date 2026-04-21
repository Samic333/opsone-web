<?php /** Phase 14 — HR workflow dashboard */ ?>
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px,1fr)); gap:12px; margin-bottom:18px;">
    <div class="card" style="padding:12px;"><div class="text-xs text-muted">Onboarding</div><div style="font-size:22px; font-weight:700;"><?= count($onboarding) ?></div></div>
    <div class="card" style="padding:12px;"><div class="text-xs text-muted">In probation</div><div style="font-size:22px; font-weight:700;"><?= count($probation) ?></div></div>
    <div class="card" style="padding:12px;"><div class="text-xs text-muted">Contracts exp. 90d</div><div style="font-size:22px; font-weight:700; color:#f59e0b;"><?= count($contractExpiring) ?></div></div>
    <div class="card" style="padding:12px;"><div class="text-xs text-muted">Pending change reqs</div><div style="font-size:22px; font-weight:700;"><?= (int)$pendingChangeRequests ?></div></div>
    <div class="card" style="padding:12px;"><div class="text-xs text-muted">Inactive users</div><div style="font-size:22px; font-weight:700; color:#64748b;"><?= (int)$inactiveUsers ?></div></div>
</div>

<div class="card" style="margin-bottom:16px;">
    <h3 style="margin-top:0;">Onboarding queue</h3>
    <?php if (empty($onboarding)): ?>
        <p class="text-muted">Nobody currently in onboarding or in-review.</p>
    <?php else: ?>
    <table class="table">
        <thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Emp. status</th><th>Joined</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($onboarding as $u): ?>
            <tr>
                <td><strong><?= e($u['name']) ?></strong></td>
                <td class="text-sm"><?= e($u['email']) ?></td>
                <td><?= statusBadge($u['status']) ?></td>
                <td><?= statusBadge($u['employment_status']) ?></td>
                <td class="text-xs text-muted"><?= formatDate($u['created_at']) ?></td>
                <td>
                    <form method="POST" action="/hr/users/<?= (int)$u['id'] ?>/employment-status" style="display:inline;">
                        <?= csrfField() ?>
                        <select name="employment_status" class="form-control" style="display:inline; width:auto;">
                            <option>onboarding</option><option>in_review</option><option>probation</option><option>active</option>
                        </select>
                        <button type="submit" class="btn btn-xs btn-primary">Set</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div class="card" style="margin-bottom:16px;">
    <h3 style="margin-top:0;">Contracts expiring (next 90d)</h3>
    <?php if (empty($contractExpiring)): ?>
        <p class="text-muted">No contracts expiring in the next 90 days.</p>
    <?php else: ?>
    <table class="table">
        <thead><tr><th>Name</th><th>Emp #</th><th>Contract type</th><th>Expires</th></tr></thead>
        <tbody>
        <?php foreach ($contractExpiring as $u): ?>
            <tr>
                <td><?= e($u['name']) ?></td>
                <td class="text-xs text-muted"><?= e($u['employee_id'] ?? '') ?></td>
                <td class="text-sm"><?= e($u['contract_type'] ?? '—') ?></td>
                <td class="text-sm"><strong style="color:#f59e0b;"><?= e($u['contract_expiry']) ?></strong></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div class="card" style="margin-bottom:16px;">
    <h3 style="margin-top:0;">Active users — deactivate</h3>
    <p class="text-xs text-muted">Use with care. Deactivating blocks login and removes the user from rosters, but all historical records stay in place.</p>
    <?php if (empty($activeList)): ?>
        <p class="text-muted">No active users.</p>
    <?php else: ?>
    <table class="table">
        <thead><tr><th>Name</th><th>Email</th><th>Emp. status</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($activeList as $u): ?>
            <tr>
                <td><strong><?= e($u['name']) ?></strong> <span class="text-xs text-muted"><?= e($u['employee_id'] ?? '') ?></span></td>
                <td class="text-xs text-muted"><?= e($u['email']) ?></td>
                <td class="text-xs"><?= e($u['employment_status'] ?? '—') ?></td>
                <td>
                    <form method="POST" action="/hr/users/<?= (int)$u['id'] ?>/deactivate" style="display:inline;"
                          onsubmit="return confirm('Deactivate <?= e($u['name']) ?>? They will lose login access.');">
                        <?= csrfField() ?>
                        <button type="submit" class="btn btn-xs btn-danger">Deactivate</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php if (!empty($inactiveList)): ?>
<div class="card" style="margin-bottom:16px;">
    <h3 style="margin-top:0;">Inactive users</h3>
    <table class="table">
        <thead><tr><th>Name</th><th>Email</th><th>Emp. status</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($inactiveList as $u): ?>
            <tr>
                <td><?= e($u['name']) ?></td>
                <td class="text-xs text-muted"><?= e($u['email']) ?></td>
                <td class="text-xs"><?= e($u['employment_status'] ?? '—') ?></td>
                <td>
                    <form method="POST" action="/hr/users/<?= (int)$u['id'] ?>/reactivate" style="display:inline;">
                        <?= csrfField() ?>
                        <button type="submit" class="btn btn-xs btn-success">Reactivate</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="card">
    <h3 style="margin-top:0;">Probation</h3>
    <?php if (empty($probation)): ?>
        <p class="text-muted">No staff currently on probation.</p>
    <?php else: ?>
    <table class="table">
        <thead><tr><th>Name</th><th>Emp #</th><th>Contract type</th><th>Expires</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($probation as $u): ?>
            <tr>
                <td><?= e($u['name']) ?></td>
                <td class="text-xs text-muted"><?= e($u['employee_id'] ?? '') ?></td>
                <td class="text-sm"><?= e($u['contract_type'] ?? '—') ?></td>
                <td class="text-sm"><?= e($u['contract_expiry'] ?? '—') ?></td>
                <td>
                    <form method="POST" action="/hr/users/<?= (int)$u['id'] ?>/employment-status" style="display:inline;">
                        <?= csrfField() ?>
                        <input type="hidden" name="employment_status" value="active">
                        <button type="submit" class="btn btn-xs btn-success">Confirm active</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
