<form method="POST" action="/roles/capabilities/<?= $role['id'] ?>">
    <?= csrfField() ?>
<div class="row">
    <div class="col-md-5">
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <div class="card-title">Role Details</div>
            </div>
            <div class="card-body">

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

                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="/roles" class="btn btn-outline" style="margin-left: 0.5rem;">Cancel</a>
            </div>
        </div>

        <div class="alert alert-info" style="font-size: 13px;">
            <strong style="display: block; margin-bottom: 5px;">How capability overrides work</strong>
            The <strong>checked</strong> boxes on the right are the permissions this role currently has in your airline.
            Ticks marked <em>(default)</em> come from the OpsOne template. Unticking a default or ticking an un-default
            capability stores a per-airline <strong>override</strong>. Per-user overrides are still editable under
            <em>User Edit → Capabilities</em>.
        </div>
    </div>

    <div class="col-md-7">
        <div class="card">
            <div class="card-header" style="padding-bottom: 1rem; border-bottom: 1px solid var(--border);">
                <div class="card-title">Permissions</div>
                <p style="font-size: 12px; color: var(--text-muted); margin: 0;">Toggle what this role can do inside your airline. Changes take effect for every user holding this role.</p>
            </div>

            <div style="padding: 1rem;">
                <?php if (empty($groupedCaps)): ?>
                    <div class="empty-state">
                        <div style="font-size: 2rem; margin-bottom: 1rem; color: var(--text-muted);">🛡</div>
                        <p>No modules are currently enabled for your airline, so there are no capabilities to configure.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($groupedCaps as $moduleName => $caps): ?>
                        <div style="margin-bottom: 1.5rem;">
                            <h4 style="margin-bottom: 0.75rem; font-size: 14px; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em; border-bottom: 1px solid var(--border); padding-bottom: 5px;">
                                <span style="color: var(--accent-magenta);">■</span> <?= e($moduleName) ?>
                            </h4>
                            <ul style="list-style: none; padding: 0; margin: 0;">
                                <?php foreach ($caps as $cap): ?>
                                    <?php $cid = (int) $cap['id']; ?>
                                    <li style="display: flex; gap: 10px; margin-bottom: 0.5rem; align-items: flex-start;">
                                        <label style="display: flex; gap: 8px; align-items: flex-start; cursor: pointer; margin: 0;">
                                            <input type="hidden" name="caps_all[]" value="<?= $cid ?>">
                                            <input type="checkbox" name="caps[<?= $cid ?>]" value="1"
                                                <?= $cap['effective_allowed'] ? 'checked' : '' ?>
                                                style="margin-top: 4px; flex-shrink: 0;">
                                            <div>
                                                <div style="font-size: 13px; font-weight: 500; font-family: monospace;">
                                                    <?= e($cap['capability']) ?>
                                                    <?php if ($cap['is_template_default']): ?>
                                                        <span style="font-family: inherit; font-size: 10px; color: var(--text-muted);">(default)</span>
                                                    <?php endif; ?>
                                                    <?php if ($cap['is_overridden']): ?>
                                                        <span style="font-family: inherit; font-size: 10px; color: var(--accent-amber, #f59e0b);">(override)</span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (!empty($cap['description'])): ?>
                                                    <div style="font-size: 12px; color: var(--text-muted);"><?= e($cap['description']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </label>
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
</form>
