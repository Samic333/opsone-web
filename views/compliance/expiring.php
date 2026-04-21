<?php
/**
 * Compliance expiring drill-down.
 * Vars: $expiringLicenses, $expiringMedicals, $expiringPassports, $expiringDocuments, $expiringQuals
 */
$rowsBlock = function (string $title, array $rows, string $dateCol, array $columns, int $cutoff = 30) {
    if (empty($rows)) return;
    ?>
    <div class="card" style="border-left:3px solid var(--accent-amber,#f59e0b);">
        <div class="card-header"><div class="card-title">⚠ <?= e($title) ?></div></div>
        <div class="table-wrap">
            <table>
                <thead><tr>
                    <?php foreach ($columns as $label): ?><th><?= e($label) ?></th><?php endforeach; ?>
                    <th>Expires</th><th>Days Left</th>
                </tr></thead>
                <tbody>
                <?php foreach ($rows as $row):
                    $daysLeft = (int) ceil((strtotime($row[$dateCol] ?? '9999-12-31') - time()) / 86400);
                    $col = $daysLeft <= $cutoff ? 'var(--accent-red)' : 'var(--accent-amber,#f59e0b)';
                ?>
                <tr>
                    <?php foreach (array_keys($columns) as $key): ?>
                        <td><?= e((string) ($row[$key] ?? '—')) ?></td>
                    <?php endforeach; ?>
                    <td style="color:<?= $col ?>;font-weight:600;"><?= e($row[$dateCol] ?? '—') ?></td>
                    <td style="color:<?= $col ?>;font-weight:700;"><?= $daysLeft ?>d</td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
};

$rowsBlock('Licences Expiring Within 90 Days', $expiringLicenses, 'expiry_date', [
    'user_name' => 'Crew', 'license_type' => 'Type', 'license_number' => 'Number',
]);
$rowsBlock('Medicals Expiring Within 90 Days', $expiringMedicals, 'medical_expiry', [
    'user_name' => 'Crew', 'medical_class' => 'Class',
]);
$rowsBlock('Passports Expiring Within 180 Days', $expiringPassports, 'passport_expiry', [
    'user_name' => 'Crew', 'passport_country' => 'Country', 'passport_number' => 'Number',
], 60);
$rowsBlock('Documents Expiring Within 90 Days', $expiringDocuments, 'expiry_date', [
    'user_name' => 'Crew', 'doc_title' => 'Document', 'doc_type' => 'Type',
]);
$rowsBlock('Qualifications Expiring Within 90 Days', $expiringQuals, 'expiry_date', [
    'user_name' => 'Crew', 'qual_name' => 'Qualification', 'qual_type' => 'Type',
]);

if (empty($expiringLicenses) && empty($expiringMedicals) && empty($expiringPassports)
    && empty($expiringDocuments) && empty($expiringQuals)):
?>
<div class="card">
    <div class="empty-state">
        <div class="icon">✅</div>
        <h3>Nothing is expiring soon</h3>
        <p>No compliance items are approaching their warning window.</p>
    </div>
</div>
<?php endif; ?>
