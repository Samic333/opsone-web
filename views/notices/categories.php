<?php /** OpsOne — Notice Categories */ ?>
<div class="grid grid-2" style="gap:24px; align-items:start;">

    <!-- Category List -->
    <div class="card">
        <h3 style="margin:0 0 16px; font-size:15px;">Notice Categories</h3>
        <?php
        // Built-in slugs that can't be deleted
        $builtInSlugs = ['general', 'operational', 'safety', 'training', 'policy', 'schedule'];
        ?>
        <?php if (empty($categories)): ?>
            <div class="empty-state" style="padding:32px 0;">
                <div class="icon">🏷️</div>
                <p>No categories yet. Add your first category.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                    <?php $isBuiltIn = in_array($cat['slug'], $builtInSlugs); ?>
                    <tr>
                        <td>
                            <strong><?= e($cat['name']) ?></strong>
                            <?php if ($isBuiltIn): ?>
                                <span class="status-badge" style="--badge-color:#6b7280;font-size:10px;vertical-align:middle;">built-in</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted text-sm"><?= e($cat['slug']) ?></td>
                        <td>
                            <?php if (!$isBuiltIn): ?>
                            <form method="POST" action="/notices/categories/delete/<?= $cat['id'] ?>"
                                  onsubmit="return confirm('Remove this category?')">
                                <?= csrfField() ?>
                                <button type="submit" class="btn btn-ghost btn-xs" style="color:#ef4444;">Remove</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <p class="text-xs text-muted" style="margin-top:12px;">Built-in categories cannot be removed.</p>
    </div>

    <!-- Add Category Form -->
    <div class="card">
        <h3 style="margin:0 0 16px; font-size:15px;">Add Custom Category</h3>
        <form method="POST" action="/notices/categories/store">
            <?= csrfField() ?>
            <div class="form-group">
                <label>Category Name <span style="color:#ef4444;">*</span></label>
                <input type="text" name="name" class="form-control" placeholder="e.g. HR Bulletin" required>
                <p class="text-xs text-muted" style="margin-top:4px;">A URL-friendly slug will be generated automatically.</p>
            </div>
            <button type="submit" class="btn btn-primary">Add Category</button>
        </form>
    </div>

</div>
