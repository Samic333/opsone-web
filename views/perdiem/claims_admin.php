<?php /** Phase 11 — Admin claims review */ ?>
<div style="display:flex; gap:8px; margin-bottom:12px;">
    <?php foreach (['submitted','approved','rejected','paid','all'] as $s):
        $active = ($_GET['status'] ?? 'submitted') === $s;
    ?>
        <a href="/per-diem/claims?status=<?= $s ?>" class="btn btn-sm <?= $active ? 'btn-primary' : 'btn-outline' ?>"><?= ucfirst($s) ?></a>
    <?php endforeach; ?>
</div>

<?php if (empty($claims)): ?>
    <div class="card"><p class="text-muted" style="padding:20px;">No claims in this status.</p></div>
<?php else: ?>
<div class="card">
    <table class="table">
        <thead><tr><th>Crew</th><th>Period</th><th>Country/Station</th><th>Days</th><th>Rate</th><th>Total</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($claims as $c): ?>
            <tr>
                <td>
                    <strong><?= e($c['user_name']) ?></strong>
                    <div class="text-xs text-muted"><?= e($c['employee_id'] ?? '') ?></div>
                </td>
                <td class="text-sm"><?= e($c['period_from']) ?> → <?= e($c['period_to']) ?></td>
                <td class="text-sm"><?= e($c['country']) ?><?= $c['station'] ? ' / ' . e($c['station']) : '' ?></td>
                <td class="text-sm"><?= number_format((float)$c['days'], 2) ?></td>
                <td class="text-sm"><?= number_format((float)$c['rate'], 2) ?> <?= e($c['currency']) ?></td>
                <td><strong><?= number_format((float)$c['amount'], 2) ?> <?= e($c['currency']) ?></strong></td>
                <td><?= statusBadge($c['status']) ?></td>
                <td>
                    <?php if ($c['status'] === 'submitted'): ?>
                        <form method="POST" action="/per-diem/claims/<?= (int)$c['id'] ?>/approve" style="display:inline;">
                            <?= csrfField() ?><button class="btn btn-xs btn-success" type="submit">Approve</button>
                        </form>
                        <form method="POST" action="/per-diem/claims/<?= (int)$c['id'] ?>/reject" style="display:inline;">
                            <?= csrfField() ?><button class="btn btn-xs btn-danger" type="submit">Reject</button>
                        </form>
                    <?php elseif ($c['status'] === 'approved'): ?>
                        <form method="POST" action="/per-diem/claims/<?= (int)$c['id'] ?>/pay" style="display:inline;">
                            <?= csrfField() ?><button class="btn btn-xs btn-primary" type="submit">Mark paid</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
