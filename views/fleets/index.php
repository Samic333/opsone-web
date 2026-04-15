<?php if (empty($fleets)): ?>
<div class="card">
    <div class="empty-state">
        <div class="icon">✈</div>
        <h3>No Fleets Yet</h3>
        <p>Create fleets to group crew by aircraft type.</p>
        <a href="/fleets/create" class="btn btn-primary mt-2">+ Create Fleet</a>
    </div>
</div>
<?php else: ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Fleet Name</th>
                <th>Code</th>
                <th>Aircraft Type</th>
                <th>Crew Assigned</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($fleets as $fleet): ?>
        <tr>
            <td><strong><?= e($fleet['name']) ?></strong></td>
            <td><code><?= e($fleet['code'] ?? '—') ?></code></td>
            <td><?= e($fleet['aircraft_type'] ?? '—') ?></td>
            <td><?= (int) $fleet['user_count'] ?></td>
            <td>
                <a href="/fleets/edit/<?= $fleet['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                <?php if ((int) $fleet['user_count'] === 0): ?>
                <form method="POST" action="/fleets/delete/<?= $fleet['id'] ?>" style="display:inline"
                      onsubmit="return confirm('Delete fleet \'<?= e(addslashes($fleet['name'])) ?>\'?')">
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
