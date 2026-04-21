<?php /** Phase 13 — Appraisals index */ ?>
<div style="margin-bottom:12px;"><a href="/appraisals/new" class="btn btn-primary">+ New Appraisal</a></div>

<?php if (!empty($pending)): ?>
<div class="card" style="margin-bottom:16px;">
    <h3 style="margin-top:0;">Submitted — awaiting review</h3>
    <table class="table">
        <thead><tr><th>Subject</th><th>Appraiser</th><th>Rotation</th><th>Period</th><th>Rating</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($pending as $a): ?>
            <tr>
                <td><?= e($a['subject_name']) ?></td>
                <td><?= e($a['appraiser_name']) ?></td>
                <td class="text-sm"><?= e($a['rotation_ref'] ?? '—') ?></td>
                <td class="text-sm"><?= e($a['period_from']) ?> → <?= e($a['period_to']) ?></td>
                <td><?= $a['rating_overall'] ? str_repeat('★', (int)$a['rating_overall']) : '—' ?></td>
                <td>
                    <form method="POST" action="/appraisals/<?= (int)$a['id'] ?>/accept" style="display:inline;">
                        <?= csrfField() ?><button class="btn btn-xs btn-success" type="submit">Accept</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="card" style="margin-bottom:16px;">
    <h3 style="margin-top:0;">My appraisals (written)</h3>
    <?php if (empty($mine)): ?>
        <p class="text-muted">No appraisals written yet.</p>
    <?php else: ?>
    <table class="table">
        <thead><tr><th>Subject</th><th>Rotation</th><th>Period</th><th>Rating</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($mine as $a): ?>
            <tr>
                <td><?= e($a['subject_name']) ?></td>
                <td class="text-sm"><?= e($a['rotation_ref'] ?? '—') ?></td>
                <td class="text-sm"><?= e($a['period_from']) ?> → <?= e($a['period_to']) ?></td>
                <td><?= $a['rating_overall'] ? str_repeat('★', (int)$a['rating_overall']) : '—' ?></td>
                <td><?= statusBadge($a['status']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div class="card">
    <h3 style="margin-top:0;">About me (accepted only)</h3>
    <?php if (empty($aboutMe)): ?>
        <p class="text-muted">No accepted appraisals yet.</p>
    <?php else: ?>
    <table class="table">
        <thead><tr><th>Appraiser</th><th>Rotation</th><th>Period</th><th>Rating</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($aboutMe as $a): ?>
            <tr>
                <td><?= e($a['appraiser_name']) ?></td>
                <td class="text-sm"><?= e($a['rotation_ref'] ?? '—') ?></td>
                <td class="text-sm"><?= e($a['period_from']) ?> → <?= e($a['period_to']) ?></td>
                <td><?= $a['rating_overall'] ? str_repeat('★', (int)$a['rating_overall']) : '—' ?></td>
                <td><?= statusBadge($a['status']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
