<?php if (empty($bases)): ?>
<div class="card">
    <div class="empty-state">
        <div class="icon">📍</div>
        <h3>No Bases Yet</h3>
        <p>Create bases to assign staff to operating locations.</p>
        <a href="/bases/create" class="btn btn-primary mt-2">+ Create Base</a>
    </div>
</div>
<?php else: ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Base Name</th>
                <th>Code</th>
                <th>Staff Count</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($bases as $base): ?>
        <tr>
            <td><strong><?= e($base['name']) ?></strong></td>
            <td><code><?= e($base['code']) ?></code></td>
            <td><?= (int) $base['user_count'] ?></td>
            <td>
                <a href="/bases/edit/<?= $base['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                <?php if ((int) $base['user_count'] === 0): ?>
                <form method="POST" action="/bases/delete/<?= $base['id'] ?>" style="display:inline"
                      onsubmit="return confirm('Delete base \'<?= e(addslashes($base['name'])) ?>\'?')">
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
