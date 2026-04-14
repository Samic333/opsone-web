<?php
$pageTitle   = 'Edit Airline — ' . e($tenant['name']);
$headerAction = '<a href="/tenants/' . $tenant['id'] . '" class="btn btn-outline">← Back to Detail</a>';
ob_start();
?>

<div style="max-width:900px;">
<form method="POST" action="/tenants/update/<?= $tenant['id'] ?>">
    <?= csrfField() ?>

    <!-- ─── Identity ────────────────────────────────────────────── -->
    <div class="card" style="margin-bottom:1.5rem;">
        <h3 style="margin:0 0 1.2rem; font-size:1rem; font-weight:600;">Airline Identity</h3>
        <div class="form-row">
            <div class="form-group">
                <label>Legal Company Name *</label>
                <input type="text" name="legal_name" class="form-control"
                       value="<?= e($tenant['legal_name'] ?? $tenant['name']) ?>" required>
            </div>
            <div class="form-group">
                <label>Display / Short Name</label>
                <input type="text" name="display_name" class="form-control"
                       value="<?= e($tenant['display_name'] ?? '') ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Portal Name *</label>
                <input type="text" name="name" class="form-control" required
                       value="<?= e($tenant['name']) ?>">
            </div>
            <div class="form-group">
                <label>System Code *</label>
                <input type="text" name="code" class="form-control" maxlength="10" required
                       style="text-transform:uppercase" value="<?= e($tenant['code']) ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>ICAO Code</label>
                <input type="text" name="icao_code" class="form-control" maxlength="4"
                       style="text-transform:uppercase" value="<?= e($tenant['icao_code'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>IATA Code</label>
                <input type="text" name="iata_code" class="form-control" maxlength="3"
                       style="text-transform:uppercase" value="<?= e($tenant['iata_code'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Primary Country</label>
                <input type="text" name="primary_country" class="form-control"
                       value="<?= e($tenant['primary_country'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Primary Base</label>
                <input type="text" name="primary_base" class="form-control"
                       value="<?= e($tenant['primary_base'] ?? '') ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Contact Email</label>
                <input type="email" name="contact_email" class="form-control"
                       value="<?= e($tenant['contact_email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Support Tier</label>
                <select name="support_tier" class="form-control">
                    <?php foreach (['standard','premium','enterprise'] as $tier): ?>
                    <option value="<?= $tier ?>" <?= ($tenant['support_tier'] ?? 'standard') === $tier ? 'selected' : '' ?>>
                        <?= ucfirst($tier) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Onboarding Status</label>
                <select name="onboarding_status" class="form-control">
                    <?php foreach (['onboarding','active','suspended','offboarding'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($tenant['onboarding_status'] ?? 'active') === $s ? 'selected' : '' ?>>
                        <?= ucfirst($s) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Internal Notes</label>
            <textarea name="notes" class="form-control" rows="2"><?= e($tenant['notes'] ?? '') ?></textarea>
        </div>
    </div>

    <!-- ─── Headcount ──────────────────────────────────────────── -->
    <div class="card" style="margin-bottom:1.5rem;">
        <h3 style="margin:0 0 1.2rem; font-size:1rem; font-weight:600;">Expected Headcount</h3>
        <div class="form-row">
            <?php
            $hcFields = [
                'expected_headcount'  => 'Total Expected',
                'headcount_pilots'    => 'Pilots',
                'headcount_cabin'     => 'Cabin Crew',
                'headcount_engineers' => 'Engineers',
            ];
            foreach ($hcFields as $field => $label): ?>
            <div class="form-group">
                <label><?= $label ?></label>
                <input type="number" name="<?= $field ?>" class="form-control" min="0"
                       value="<?= (int)($tenant[$field] ?? 0) ?: '' ?>" placeholder="0">
            </div>
            <?php endforeach; ?>
        </div>
        <div class="form-row">
            <?php
            $hcFields2 = [
                'headcount_schedulers' => 'Schedulers',
                'headcount_training'   => 'Training Staff',
                'headcount_safety'     => 'Safety Staff',
                'headcount_hr'         => 'HR Staff',
            ];
            foreach ($hcFields2 as $field => $label): ?>
            <div class="form-group">
                <label><?= $label ?></label>
                <input type="number" name="<?= $field ?>" class="form-control" min="0"
                       value="<?= (int)($tenant[$field] ?? 0) ?: '' ?>" placeholder="0">
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="flex gap-1">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="/tenants/<?= $tenant['id'] ?>" class="btn btn-outline">Cancel</a>
    </div>
</form>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/app.php'; ?>
