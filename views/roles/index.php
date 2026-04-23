<div style="display:flex;justify-content:flex-end;margin-bottom:1rem;gap:8px;">
    <button type="button" id="newRoleBtn" class="btn btn-primary" style="font-size:12px;">+ Create Role</button>
</div>

<div id="newRoleForm" style="display:none;margin-bottom:1.5rem;">
    <div class="card">
        <h3 style="margin:0 0 10px;font-size:1rem;font-weight:600;">Create a custom role</h3>
        <p style="font-size:12px;color:var(--text-muted);margin:0 0 14px;">
            Name is shown in the UI. After creation, open the role to assign capabilities
            from the modules enabled for this airline.
        </p>
        <form method="POST" action="/roles/store"
              style="display:grid;grid-template-columns:1fr 1fr;gap:12px;align-items:end;">
            <?= csrfField() ?>
            <div>
                <label style="display:block;font-size:12px;color:var(--text-muted);margin-bottom:4px;">Role name *</label>
                <input type="text" name="name" required maxlength="80"
                       placeholder="e.g. Flight Dispatcher"
                       style="width:100%;padding:8px 10px;border:1px solid var(--border);
                              background:var(--bg-card,#1e2535);color:var(--text-primary);
                              border-radius:6px;font-size:13px;">
            </div>
            <div>
                <label style="display:block;font-size:12px;color:var(--text-muted);margin-bottom:4px;">Type</label>
                <select name="role_type"
                        style="width:100%;padding:8px 10px;border:1px solid var(--border);
                               background:var(--bg-card,#1e2535);color:var(--text-primary);
                               border-radius:6px;font-size:13px;">
                    <option value="tenant">Administrative / Operational</option>
                    <option value="end_user">End-user (crew)</option>
                </select>
            </div>
            <div style="grid-column:1 / -1;">
                <label style="display:block;font-size:12px;color:var(--text-muted);margin-bottom:4px;">Description (optional)</label>
                <input type="text" name="description" maxlength="200"
                       style="width:100%;padding:8px 10px;border:1px solid var(--border);
                              background:var(--bg-card,#1e2535);color:var(--text-primary);
                              border-radius:6px;font-size:13px;">
            </div>
            <div style="grid-column:1 / -1;display:flex;justify-content:flex-end;gap:8px;">
                <button type="button" id="cancelNewRole" class="btn btn-outline" style="font-size:12px;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="font-size:12px;">Create role</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var btn = document.getElementById('newRoleBtn');
    var panel = document.getElementById('newRoleForm');
    var cancel = document.getElementById('cancelNewRole');
    btn && btn.addEventListener('click', function () { panel.style.display = 'block'; btn.style.display = 'none'; });
    cancel && cancel.addEventListener('click', function () { panel.style.display = 'none'; btn.style.display = 'inline-block'; });
})();
</script>

<div class="card" style="margin-bottom: 2rem;">
    <div class="card-header">
        <div class="card-title">Airline Administrative Roles</div>
        <p style="font-size: 12px; color: var(--text-muted); margin: 0;">Roles that have access to operational and configuration areas of the portal.</p>
    </div>
    
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Role Name</th>
                    <th>System Key</th>
                    <th>Description</th>
                    <th style="width: 100px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($grouped['tenant'])): ?>
                <tr><td colspan="4" class="empty-state">No administrative roles found.</td></tr>
                <?php else: ?>
                    <?php foreach ($grouped['tenant'] as $r): ?>
                    <tr>
                        <td style="font-weight: 500;"><?= e($r['name']) ?></td>
                        <td><code style="font-size: 11px;"><?= e($r['slug']) ?></code></td>
                        <td><span style="font-size: 12px; color: var(--text-muted);"><?= e($r['description']) ?></span></td>
                        <td>
                            <a href="/roles/<?= $r['id'] ?>" class="btn btn-sm btn-outline">Edit & Permissions</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Operational Roles (End Users)</div>
        <p style="font-size: 12px; color: var(--text-muted); margin: 0;">Roles used by crew and staff to access their personal profiles, rosters, and notices.</p>
    </div>
    
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Role Name</th>
                    <th>System Key</th>
                    <th>Description</th>
                    <th style="width: 100px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($grouped['end_user'])): ?>
                <tr><td colspan="4" class="empty-state">No end-user roles found.</td></tr>
                <?php else: ?>
                    <?php foreach ($grouped['end_user'] as $r): ?>
                    <tr>
                        <td style="font-weight: 500;"><?= e($r['name']) ?></td>
                        <td><code style="font-size: 11px;"><?= e($r['slug']) ?></code></td>
                        <td><span style="font-size: 12px; color: var(--text-muted);"><?= e($r['description']) ?></span></td>
                        <td>
                            <a href="/roles/<?= $r['id'] ?>" class="btn btn-sm btn-outline">Edit & Permissions</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
