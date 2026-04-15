<?php
/**
 * Crew Profile Detail
 * Variables: $user, $crewProfile, $licenses, $qualifications, $completion, $profileType
 */

// Profile completion color
$pctCol = $completion >= 80 ? 'var(--accent-green)' : ($completion >= 50 ? 'var(--accent-amber,#f59e0b)' : 'var(--accent-red)');

// Role type badge
$typeBadge = [
    'pilot'      => ['✈ Pilot',     'blue'],
    'cabin_crew' => ['🧑‍✈️ Cabin Crew', 'cyan'],
    'engineer'   => ['🔧 Engineer',  'purple'],
    'crew'       => ['👤 Crew',      'blue'],
][$profileType] ?? ['👤 Crew', 'blue'];
?>

<div class="grid grid-2" style="gap:1.5rem; align-items:start;">

    <!-- ─── Left Column ──────────────────────────────────── -->
    <div>

        <!-- Identity Card -->
        <div class="card">
            <div style="display:flex; align-items:center; gap:16px; margin-bottom:20px;">
                <div style="width:56px;height:56px;background:linear-gradient(135deg,var(--accent-blue),var(--accent-cyan));
                            border-radius:50%;display:flex;align-items:center;justify-content:center;
                            font-size:22px;font-weight:700;color:#fff;flex-shrink:0;">
                    <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
                </div>
                <div>
                    <h3 style="font-size:18px;font-weight:700;margin-bottom:4px;"><?= e($user['name']) ?></h3>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <span class="status-badge status-<?= e($typeBadge[1]) ?>"><?= $typeBadge[0] ?></span>
                        <?= statusBadge($user['status'] ?? 'active') ?>
                        <?php if (!empty($user['employee_id'])): ?>
                            <code style="font-size:11px;padding:2px 6px;background:var(--bg-card);border:1px solid var(--border-color);border-radius:4px;">
                                <?= e($user['employee_id']) ?>
                            </code>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                <?php $infoItems = [
                    ['Email',       $user['email'] ?? '—'],
                    ['Department',  $user['department_name'] ?? '—'],
                    ['Base',        $user['base_name'] ?? '—'],
                    ['Fleet',       $user['fleet_name'] ?? '—'],
                    ['Employment',  ucwords(str_replace('_', ' ', $user['employment_status'] ?? '—'))],
                    ['Web Access',  !empty($user['web_access']) ? 'Yes' : 'No'],
                ]; ?>
                <?php foreach ($infoItems as [$label, $value]): ?>
                <div>
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);"><?= $label ?></div>
                    <div style="font-size:13px;margin-top:2px;"><?= e($value) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <!-- Profile Completion Bar -->
            <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border-light);">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                    <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);">Profile Completion</span>
                    <span style="font-size:13px;font-weight:700;color:<?= $pctCol ?>;"><?= $completion ?>%</span>
                </div>
                <div style="height:8px;background:var(--border-color);border-radius:4px;overflow:hidden;">
                    <div style="height:100%;width:<?= $completion ?>%;background:<?= $pctCol ?>;border-radius:4px;transition:width .3s;"></div>
                </div>
                <?php if ($completion < 100): ?>
                <div style="font-size:11px;color:var(--text-muted);margin-top:6px;">
                    <?php if (empty($crewProfile)): ?>
                        No crew profile saved yet. <a href="/users/edit/<?= $user['id'] ?>#crew-profile" style="color:var(--accent-blue);">Add profile →</a>
                    <?php elseif (empty($licenses)): ?>
                        No licences on file. <a href="/users/edit/<?= $user['id'] ?>#licenses" style="color:var(--accent-blue);">Add licence →</a>
                    <?php else: ?>
                        <a href="/users/edit/<?= $user['id'] ?>#crew-profile" style="color:var(--accent-blue);">Complete profile →</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Personal Information -->
        <div class="card mt-3">
            <div class="card-header"><div class="card-title">Personal Information</div></div>
            <?php if (empty($crewProfile)): ?>
                <div class="empty-state" style="padding:16px 0 8px;">
                    <p>No personal information recorded.</p>
                    <a href="/users/edit/<?= $user['id'] ?>#crew-profile" class="btn btn-outline btn-sm">Add Profile →</a>
                </div>
            <?php else: ?>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <?php $personal = [
                    ['Date of Birth',   $crewProfile['date_of_birth']   ?? null],
                    ['Nationality',     $crewProfile['nationality']      ?? null],
                    ['Phone',           $crewProfile['phone']            ?? null],
                    ['Contract Type',   isset($crewProfile['contract_type']) ? ucwords(str_replace('_',' ',$crewProfile['contract_type'])) : null],
                    ['Contract Expiry', $crewProfile['contract_expiry']  ?? null],
                ]; ?>
                <?php foreach ($personal as [$label, $val]): ?>
                <div>
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);"><?= $label ?></div>
                    <div style="font-size:13px;margin-top:2px;"><?= $val ? e($val) : '<span style="color:var(--text-muted)">—</span>' ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($crewProfile['emergency_name'])): ?>
            <div style="margin-top:16px;padding-top:12px;border-top:1px solid var(--border-light);">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:8px;">Emergency Contact</div>
                <div style="font-size:13px;">
                    <strong><?= e($crewProfile['emergency_name']) ?></strong>
                    <?php if (!empty($crewProfile['emergency_relation'])): ?>
                        <span class="text-muted"> · <?= e($crewProfile['emergency_relation']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($crewProfile['emergency_phone'])): ?>
                        <div class="text-muted" style="font-size:12px;"><?= e($crewProfile['emergency_phone']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Documents (Passport & Medical) -->
        <div class="card mt-3">
            <div class="card-header"><div class="card-title">Documents</div></div>
            <?php if (empty($crewProfile)): ?>
                <div class="empty-state" style="padding:12px 0 4px;"><p>No document records.</p></div>
            <?php else: ?>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <!-- Passport -->
                <div style="background:var(--bg-secondary);border-radius:var(--radius-md);padding:14px;">
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:8px;">Passport</div>
                    <?php
                    $passExp  = $crewProfile['passport_expiry'] ?? null;
                    $passDays = $passExp ? (int)ceil((strtotime($passExp) - time()) / 86400) : null;
                    $passStyle = '';
                    if ($passDays !== null) {
                        if ($passDays < 0)        $passStyle = 'color:var(--accent-red);font-weight:700;';
                        elseif ($passDays <= 60)   $passStyle = 'color:var(--accent-red);';
                        elseif ($passDays <= 180)  $passStyle = 'color:var(--accent-amber,#f59e0b);';
                    }
                    ?>
                    <div style="font-size:13px;">
                        <div><?= e($crewProfile['passport_number'] ?? '—') ?></div>
                        <div class="text-muted text-xs"><?= e($crewProfile['passport_country'] ?? '') ?></div>
                        <?php if ($passExp): ?>
                        <div style="margin-top:4px;<?= $passStyle ?>font-size:12px;">
                            Expires: <?= e($passExp) ?>
                            <?php if ($passDays !== null && $passDays < 0): ?> <strong>(EXPIRED)</strong>
                            <?php elseif ($passDays !== null && $passDays <= 180): ?> (<?= $passDays ?>d)<?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Medical -->
                <div style="background:var(--bg-secondary);border-radius:var(--radius-md);padding:14px;">
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:8px;">Medical Certificate</div>
                    <?php
                    $medExp   = $crewProfile['medical_expiry'] ?? null;
                    $medDays  = $medExp ? (int)ceil((strtotime($medExp) - time()) / 86400) : null;
                    $medStyle = '';
                    if ($medDays !== null) {
                        if ($medDays < 0)        $medStyle = 'color:var(--accent-red);font-weight:700;';
                        elseif ($medDays <= 30)  $medStyle = 'color:var(--accent-red);';
                        elseif ($medDays <= 90)  $medStyle = 'color:var(--accent-amber,#f59e0b);';
                    }
                    ?>
                    <div style="font-size:13px;">
                        <div><?= e($crewProfile['medical_class'] ?? '—') ?></div>
                        <?php if ($medExp): ?>
                        <div style="margin-top:4px;<?= $medStyle ?>font-size:12px;">
                            Expires: <?= e($medExp) ?>
                            <?php if ($medDays !== null && $medDays < 0): ?> <strong>(EXPIRED)</strong>
                            <?php elseif ($medDays !== null && $medDays <= 90): ?> (<?= $medDays ?>d)<?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- ─── Right Column ─────────────────────────────────── -->
    <div>

        <!-- Licences & Ratings -->
        <div class="card" id="licenses">
            <div class="card-header">
                <div class="card-title">Licences &amp; Ratings</div>
                <a href="/users/edit/<?= $user['id'] ?>#licenses" class="btn btn-sm btn-outline">Manage →</a>
            </div>
            <?php if (empty($licenses)): ?>
                <div class="empty-state" style="padding:12px 0 4px;"><p>No licences on file.</p></div>
            <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Type</th><th>Number</th><th>Authority</th><th>Expires</th></tr></thead>
                    <tbody>
                    <?php foreach ($licenses as $lic):
                        $expiry = $lic['expiry_date'] ?? null;
                        $daysLeft = $expiry ? (int)ceil((strtotime($expiry) - time()) / 86400) : null;
                        $expiryStyle = '';
                        if ($daysLeft !== null) {
                            if ($daysLeft < 0)       $expiryStyle = 'color:var(--accent-red);font-weight:700;';
                            elseif ($daysLeft <= 30) $expiryStyle = 'color:var(--accent-red);';
                            elseif ($daysLeft <= 90) $expiryStyle = 'color:var(--accent-amber,#f59e0b);';
                        }
                    ?>
                    <tr>
                        <td><strong><?= e($lic['license_type']) ?></strong></td>
                        <td><code class="text-xs"><?= e($lic['license_number'] ?? '—') ?></code></td>
                        <td class="text-xs text-muted"><?= e($lic['issuing_authority'] ?? '—') ?></td>
                        <td style="<?= $expiryStyle ?>font-size:12px;">
                            <?= e($expiry ?? '—') ?>
                            <?php if ($daysLeft !== null && $daysLeft < 0): ?> <span style="font-size:10px;">(EXP)</span>
                            <?php elseif ($daysLeft !== null && $daysLeft <= 90): ?> <span style="font-size:10px;">(<?= $daysLeft ?>d)</span><?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Qualifications & Endorsements -->
        <div class="card mt-3" id="qualifications">
            <div class="card-header">
                <div class="card-title">Qualifications &amp; Endorsements</div>
            </div>

            <?php if (!empty($qualifications)): ?>
            <div class="table-wrap" style="margin-bottom:16px;">
                <table>
                    <thead><tr><th>Type</th><th>Qualification</th><th>Ref / Authority</th><th>Expires</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($qualifications as $q):
                        $qExpiry = $q['expiry_date'] ?? null;
                        $qDays   = $qExpiry ? (int)ceil((strtotime($qExpiry) - time()) / 86400) : null;
                        $qStyle  = '';
                        if ($qDays !== null) {
                            if ($qDays < 0)       $qStyle = 'color:var(--accent-red);font-weight:700;';
                            elseif ($qDays <= 30) $qStyle = 'color:var(--accent-red);';
                            elseif ($qDays <= 90) $qStyle = 'color:var(--accent-amber,#f59e0b);';
                        }
                        $statusColors = ['active'=>'green','expired'=>'red','pending_renewal'=>'yellow','suspended'=>'red'];
                    ?>
                    <tr>
                        <td><span class="text-xs text-muted"><?= e($q['qual_type']) ?></span></td>
                        <td>
                            <strong style="font-size:13px;"><?= e($q['qual_name']) ?></strong>
                            <?php if (!empty($q['notes'])): ?>
                                <div class="text-xs text-muted"><?= e($q['notes']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-xs text-muted">
                            <?= e($q['reference_no'] ?? '') ?>
                            <?php if (!empty($q['authority'])): ?>
                                <div><?= e($q['authority']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="<?= $qStyle ?>font-size:12px;">
                            <?= $qExpiry ? e($qExpiry) : '<span class="text-muted">—</span>' ?>
                            <?php if ($qDays !== null && $qDays < 0): ?> <span style="font-size:10px;">(EXP)</span>
                            <?php elseif ($qDays !== null && $qDays <= 90): ?> <span style="font-size:10px;">(<?= $qDays ?>d)</span><?php endif; ?>
                        </td>
                        <td>
                            <form method="POST"
                                  action="/crew-profiles/<?= $user['id'] ?>/qualifications/delete/<?= $q['id'] ?>"
                                  style="display:inline" onsubmit="return confirm('Remove this qualification?')">
                                <?= csrfField() ?>
                                <button type="submit" class="btn btn-xs btn-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state" style="padding:12px 0 8px;"><p>No qualifications recorded.</p></div>
            <?php endif; ?>

            <!-- Add Qualification Form -->
            <div style="border-top:1px solid var(--border-color);padding-top:14px;margin-top:4px;">
                <div style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">Add Qualification / Endorsement</div>
                <form method="POST" action="/crew-profiles/<?= $user['id'] ?>/qualifications/add">
                    <?= csrfField() ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Type *</label>
                            <select name="qual_type" class="form-control" required>
                                <option value="">— Select —</option>
                                <?php foreach ([
                                    'Type Rating', 'Instrument Rating', 'Instructor Authority',
                                    'Check Airman', 'CRM Course', 'Safety Course',
                                    'Endorsement', 'Approval', 'Other'
                                ] as $qt): ?>
                                <option value="<?= $qt ?>"><?= $qt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Name *</label>
                            <input type="text" name="qual_name" class="form-control"
                                   placeholder="e.g. B737-800 Type Rating" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Reference / Certificate No.</label>
                            <input type="text" name="reference_no" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Issuing Authority</label>
                            <input type="text" name="authority" class="form-control" placeholder="e.g. KCAA, UCAA">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Issue Date</label>
                            <input type="date" name="issue_date" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Expiry Date</label>
                            <input type="date" name="expiry_date" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="Optional notes">
                    </div>
                    <button type="submit" class="btn btn-outline btn-sm">+ Add Qualification</button>
                </form>
            </div>
        </div>

        <?php if ($profileType === 'pilot'): ?>
        <!-- Pilot-specific section -->
        <div class="card mt-3">
            <div class="card-header"><div class="card-title">✈ Flight Crew Details</div></div>
            <div style="font-size:13px;color:var(--text-muted);">
                Fleet: <strong style="color:var(--text-primary);"><?= e($user['fleet_name'] ?? '—') ?></strong>
                &nbsp;·&nbsp; Base: <strong style="color:var(--text-primary);"><?= e($user['base_name'] ?? '—') ?></strong>
            </div>
            <div class="empty-state" style="padding:12px 0 4px;font-size:12px;">
                <p>Flight hours and type ratings tracked via Licences &amp; Qualifications above.</p>
            </div>
        </div>
        <?php elseif ($profileType === 'engineer'): ?>
        <!-- Engineer-specific section -->
        <div class="card mt-3">
            <div class="card-header"><div class="card-title">🔧 Engineering Details</div></div>
            <div style="font-size:13px;color:var(--text-muted);">
                Fleet: <strong style="color:var(--text-primary);"><?= e($user['fleet_name'] ?? '—') ?></strong>
                &nbsp;·&nbsp; Base: <strong style="color:var(--text-primary);"><?= e($user['base_name'] ?? '—') ?></strong>
            </div>
            <div class="empty-state" style="padding:12px 0 4px;font-size:12px;">
                <p>Aircraft type approvals and Part-66 licences tracked via Licences &amp; Qualifications above.</p>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /right col -->
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/app.php'; ?>
