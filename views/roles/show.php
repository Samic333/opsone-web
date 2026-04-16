<div class="row">
    <div class="col-md-5">
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <div class="card-title">Role Details</div>
            </div>
            <div class="card-body">
                <form method="POST" action="/roles/capabilities/<?= $role['id'] ?>">
                    <?= csrfField() ?>
                    
                    <div class="form-group">
                        <label class="form-label">System Key (Cannot be changed)</label>
                        <input type="text" class="form-control" value="<?= e($role['slug']) ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Display Name</label>
                        <input type="text" name="name" class="form-control" value="<?= e($role['name']) ?>" required>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label class="form-label">Internal Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= e($role['description']) ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Details</button>
                    <a href="/roles" class="btn btn-outline" style="margin-left: 0.5rem;">Cancel</a>
                </form>
            </div>
        </div>
        
        <div class="alert alert-info" style="font-size: 13px;">
            <strong style="display: block; margin-bottom: 5px;">How do permissions work?</strong>
            The base capabilities listed on the right are managed globally by the OpsOne Platform. As an airline administrator, you cannot change the underlying template capabilities for a role.
            <br><br>
            If you need to grant or restrict specific actions for an individual user, open their <strong>User Edit</strong> page and use the <strong>Capabilities Override</strong> tab.
        </div>
    </div>
    
    <div class="col-md-7">
        <div class="card">
            <div class="card-header" style="padding-bottom: 1rem; border-bottom: 1px solid var(--border);">
                <div class="card-title">Base Permissions Dictionary</div>
                <p style="font-size: 12px; color: var(--text-muted); margin: 0;">These are the foundational capabilities granted automatically to any user assigned this role.</p>
            </div>
            
            <div style="padding: 1rem;">
                <?php if (empty($groupedCaps)): ?>
                    <div class="empty-state">
                        <div style="font-size: 2rem; margin-bottom: 1rem; color: var(--text-muted);">🛡</div>
                        <p>No capabilities are explicitly mapped to this role for any enabled modules.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($groupedCaps as $moduleName => $caps): ?>
                        <div style="margin-bottom: 1.5rem;">
                            <h4 style="margin-bottom: 0.75rem; font-size: 14px; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em; border-bottom: 1px solid var(--border); padding-bottom: 5px;">
                                <span style="color: var(--accent-magenta);">■</span> <?= e($moduleName) ?>
                            </h4>
                            <ul style="list-style: none; padding: 0; margin: 0;">
                                <?php foreach ($caps as $cap): ?>
                                    <li style="display: flex; gap: 10px; margin-bottom: 0.75rem; align-items: flex-start;">
                                        <div style="margin-top: 2px;">
                                            <span style="display: inline-block; width: 14px; height: 14px; border-radius: 50%; background-color: var(--accent-green); color: white; line-height: 14px; text-align: center; font-size: 9px;">✓</span>
                                        </div>
                                        <div>
                                            <div style="font-size: 13px; font-weight: 500; font-family: monospace;"><?= e($cap['capability']) ?></div>
                                            <?php if ($cap['description']): ?>
                                                <div style="font-size: 12px; color: var(--text-muted);"><?= e($cap['description']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
