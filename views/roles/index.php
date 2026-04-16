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
