<?php
$pageTitle   = 'Onboarding Request #' . $request['id'];
$pageSubtitle = e($request['legal_name']);
$headerAction = '<a href="/platform/onboarding" class="btn btn-outline">← Back</a>';
ob_start();
?>

<div style="display:grid; grid-template-columns:1fr 380px; gap:1.5rem; align-items:start;">
<div>
    <div class="card" style="margin-bottom:1.5rem;">
        <h3 style="margin:0 0 1rem; font-size:.95rem; font-weight:600;">Request Details</h3>
        <table style="width:100%; font-size:13px; border-collapse:collapse;">
        <?php $rows = [
            'Legal Name'    => $request['legal_name'],
            'Display Name'  => $request['display_name'],
            'ICAO Code'     => $request['icao_code'],
            'IATA Code'     => $request['iata_code'],
            'Country'       => $request['primary_country'],
            'Contact'       => $request['contact_name'] . ' &lt;' . e($request['contact_email']) . '&gt;',
            'Phone'         => $request['contact_phone'],
            'Headcount'     => $request['expected_headcount'],
            'Support Tier'  => ucfirst($request['support_tier']),
            'Submitted'     => formatDateTime($request['created_at']),
            'Status'        => statusBadge($request['status']),
        ]; ?>
        <?php foreach ($rows as $label => $value): ?>
        <?php if ($value): ?>
        <tr>
            <td style="padding:5px 10px 5px 0; color:var(--text-muted); width:130px;"><?= e($label) ?></td>
            <td style="padding:5px 0;"><?= $value ?></td>
        </tr>
        <?php endif; ?>
        <?php endforeach; ?>
        </table>
    </div>

    <?php if ($request['requested_modules']): ?>
    <div class="card" style="margin-bottom:1.5rem;">
        <h3 style="margin:0 0 .75rem; font-size:.95rem; font-weight:600;">Requested Modules</h3>
        <?php
        $reqMods = json_decode($request['requested_modules'], true) ?? [];
        $modNames = array_column($allModules, 'name', 'code');
        ?>
        <div style="display:flex; flex-wrap:wrap; gap:8px;">
        <?php foreach ($reqMods as $code): ?>
        <span style="padding:4px 10px; background:var(--surface); border:1px solid var(--border); border-radius:6px; font-size:12px;">
            <?= e($modNames[$code] ?? $code) ?>
        </span>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($request['notes']): ?>
    <div class="card" style="margin-bottom:1.5rem;">
        <h3 style="margin:0 0 .5rem; font-size:.95rem; font-weight:600;">Notes</h3>
        <p style="font-size:13px; margin:0;"><?= nl2br(e($request['notes'])) ?></p>
    </div>
    <?php endif; ?>

    <?php if ($request['review_notes']): ?>
    <div class="card" style="margin-bottom:1.5rem; border-left:3px solid #6366f1;">
        <h3 style="margin:0 0 .5rem; font-size:.95rem; font-weight:600;">Review Notes</h3>
        <p style="font-size:13px; margin:0;"><?= nl2br(e($request['review_notes'])) ?></p>
        <p style="font-size:11px; color:var(--text-muted); margin:8px 0 0;">
            By: <?= e($request['reviewed_by_name'] ?? '—') ?> · <?= formatDateTime($request['reviewed_at']) ?>
        </p>
    </div>
    <?php endif; ?>
</div>

<!-- Actions panel -->
<div>
    <?php if ($request['status'] === 'pending'): ?>
    <div class="card" style="margin-bottom:1rem;">
        <h3 style="margin:0 0 1rem; font-size:.95rem; font-weight:600;">Review Request</h3>
        <form method="POST" action="/platform/onboarding/<?= $request['id'] ?>/approve" style="margin-bottom:1rem;">
            <?= csrfField() ?>
            <div class="form-group">
                <label style="font-size:12px;">Review notes (optional)</label>
                <textarea name="review_notes" class="form-control" rows="2" placeholder="Internal notes..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">✓ Approve</button>
        </form>
        <form method="POST" action="/platform/onboarding/<?= $request['id'] ?>/reject">
            <?= csrfField() ?>
            <textarea name="review_notes" class="form-control" rows="2" style="margin-bottom:.5rem;"
                      placeholder="Rejection reason..." required></textarea>
            <button type="submit" class="btn btn-outline" style="width:100%; border-color:var(--accent-red); color:var(--accent-red);"
                    onclick="return confirm('Reject this request?')">
                ✗ Reject
            </button>
        </form>
    </div>
    <?php endif; ?>

    <?php if ($request['status'] === 'approved'): ?>
    <div class="card" style="border-left:3px solid #10b981;">
        <h3 style="margin:0 0 .5rem; font-size:.95rem; font-weight:600; color:#10b981;">Ready to Provision</h3>
        <p style="font-size:12px; color:var(--text-muted); margin:0 0 1rem;">
            This request is approved. Click below to create the tenant and generate the admin invitation token.
        </p>
        <form method="POST" action="/platform/onboarding/<?= $request['id'] ?>/provision">
            <?= csrfField() ?>
            <button type="submit" class="btn btn-primary" style="width:100%;"
                    onclick="return confirm('Provision this airline as a new tenant?')">
                🚀 Provision Airline
            </button>
        </form>
    </div>
    <?php endif; ?>

    <?php if ($request['status'] === 'provisioned' && $request['tenant_id']): ?>
    <div class="card" style="border-left:3px solid #10b981;">
        <p style="font-size:13px; margin:0 0 .75rem;">✅ Provisioned as tenant #<?= $request['tenant_id'] ?>.</p>
        <a href="/tenants/<?= $request['tenant_id'] ?>" class="btn btn-outline" style="width:100%;">
            View Airline →
        </a>
    </div>
    <?php endif; ?>
</div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/app.php'; ?>
