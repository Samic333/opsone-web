<?php if (empty($departments)): ?>
<div class="card">
    <div class="empty-state">
        <div class="icon">🏢</div>
        <h3>No Departments Yet</h3>
        <p>Create departments to organise your staff by function or area.</p>
        <a href="/departments/create" class="btn btn-primary mt-2">+ Create Department</a>
    </div>
</div>
<?php else: ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Department Name</th>
                <th>Code</th>
                <th>Staff Count</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($departments as $dept): ?>
        <tr>
            <td><strong><?= e($dept['name']) ?></strong></td>
            <td><code><?= e($dept['code'] ?? '—') ?></code></td>
            <td><?= (int) $dept['user_count'] ?></td>
            <td>
                <a href="/departments/edit/<?= $dept['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                <?php if ((int) $dept['user_count'] === 0): ?>
                <form method="POST" action="/departments/delete/<?= $dept['id'] ?>" style="display:inline"
                      onsubmit="return confirm('Delete department \'<?= e(addslashes($dept['name'])) ?>\'?')">
                    <?= csrfField() ?>
                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
