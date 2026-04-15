<?php
/**
 * My Profile — self-service view for crew
 * Variables: $crewProfile, $licenses, $qualifications, $completion, $profileType
 */
$me = currentUser();
$pctCol = $completion >= 80 ? 'var(--accent-green)' : ($completion >= 50 ? 'var(--accent-amber,#f59e0b)' : 'var(--accent-red)');
?>

<!-- Profile Completion Banner -->
<div class="card" style="background:linear-gradient(135deg,var(--bg-card),var(--bg-secondary));
     border-left:3px solid <?= $pctCol ?>;margin-bottom:0;">
    <div class="flex items-center gap-2" style="justify-content:space-between;flex-wrap:wrap;">
        <div>
            <div style="font-size:16px;font-weight:700;">Your Profile is <?= $completion ?>% Complete</div>
            <div class="text-muted text-sm" style="margin-top:2px;">
                <?php if ($completion < 100): ?>
                    Fill in your personal information below to reach 100%.
                <?php else: ?>
                    Your profile is fully up to date.
                <?php endif; ?>
            </div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:28px;font-weight:700;color:<?= $pctCol ?>;"><?= $completion ?>%</div>
            <div style="width:120px;height:6px;background:var(--border-color);border-radius:3px;overflow:hidden;margin-top:4px;">
                <div style="height:100%;width:<?= $completion ?>%;background:<?= $pctCol ?>;border-radius:3px;"></div>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-2 mt-3" style="align-items:start;">

    <!-- ─── Left: Editable Info ──────────────────────────── -->
    <div>

        <!-- Account Summary (read-only) -->
        <div class="card">
            <div class="card-header"><div class="card-title">Account Information</div></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <?php foreach ([
                    ['Name',        $me['name'] ?? '—'],
                    ['Email',       $me['email'] ?? '—'],
                    ['Employee ID', $me['employee_id'] ?? '—'],
                    ['Department',  $me['department_name'] ?? '—'],
                    ['Base',        $me['base_name'] ?? '—'],
                    ['Fleet',       $me['fleet_name'] ?? '—'],
                ] as [$label, $val]): ?>
                <div>
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);"><?= $label ?></div>
                    <div style="font-size:13px;margin-top:2px;"><?= e($val) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="text-xs text-muted mt-2" style="padding-top:8px;border-top:1px solid var(--border-light);">
                To change your account details or role, contact your Airline Administrator.
            </div>
        </div>

        <!-- Editable: Contact Info -->
        <div class="card mt-3">
            <div class="card-header"><div class="card-title">Contact &amp; Emergency</div></div>
            <form method="POST" action="/my-profile/update">
                <?= csrfField() ?>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control"
                           placeholder="+254 700 000 000"
                           value="<?= e($crewProfile['phone'] ?? '') ?>">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Emergency Contact Name</label>
                        <input type="text" name="emergency_name" class="form-control"
                               value="<?= e($crewProfile['emergency_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Emergency Phone</label>
                        <input type="text" name="emergency_phone" class="form-control"
                               value="<?= e($crewProfile['emergency_phone'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Emergency Relation</label>
                    <input type="text" name="emergency_relation" class="form-control"
                           placeholder="e.g. Spouse, Parent"
                           value="<?= e($crewProfile['emergency_relation'] ?? '') ?>">
                </div>
                <div class="flex gap-1 mt-2">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>

        <!-- Read-only profile data -->
        <div class="card mt-3">
            <div class="card-header">
                <div class="card-title">Personal Details</div>
                <span class="text-xs text-muted">Managed by HR / Admin</span>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <?php foreach ([
                    ['Date of Birth',   $crewProfile['date_of_birth']   ?? null],
                    ['Nationality',     $crewProfile['nationality']      ?? null],
                    ['Passport No.',    $crewProfile['passport_number']  ?? null],
                    ['Passport Expiry', $crewProfile['passport_expiry']  ?? null],
                    ['Medical Class',   $crewProfile['medical_class']    ?? null],
                    ['Medical Expiry',  $crewProfile['medical_expiry']   ?? null],
                ] as [$label, $val]): ?>
                <div>
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);"><?= $label ?></div>
                    <div style="font-size:13px;margin-top:2px;"><?= $val ? e($val) : '<span class="text-muted">—</span>' ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <!-- ─── Right: Licences & Qualifications ─────────────── -->
    <div>

        <!-- Licences (read-only for crew) -->
        <div class="card" id="licenses">
            <div class="card-header"><div class="card-title">My Licences</div></div>
            <?php if (empty($licenses)): ?>
                <div class="empty-state" style="padding:12px 0 4px;"><p>No licences on file.</p></div>
            <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Type</th><th>Number</th><th>Expires</th></tr></thead>
                    <tbody>
                    <?php foreach ($licenses as $lic):
                        $expiry   = $lic['expiry_date'] ?? null;
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
                        <td style="<?= $expiryStyle ?>font-size:12px;">
                            <?= e($expiry ?? '—') ?>
                            <?php if ($daysLeft !== null && $daysLeft < 0): ?> (EXPIRED)
                            <?php elseif ($daysLeft !== null && $daysLeft <= 90): ?> (<?= $daysLeft ?>d)<?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            <div class="text-xs text-muted mt-2" style="padding-top:8px;border-top:1px solid var(--border-light);">
                Licence entries are managed by your Airline Administrator.
            </div>
        </div>

        <!-- Qualifications (self-service add/remove) -->
        <div class="card mt-3" id="qualifications">
            <div class="card-header"><div class="card-title">My Qualifications</div></div>

            <?php if (!empty($qualifications)): ?>
            <div class="table-wrap" style="margin-bottom:16px;">
                <table>
                    <thead><tr><th>Type</th><th>Qualification</th><th>Expires</th><th></th></tr></thead>
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
                    ?>
                    <tr>
                        <td class="text-xs text-muted"><?= e($q['qual_type']) ?></td>
                        <td>
                            <strong style="font-size:13px;"><?= e($q['qual_name']) ?></strong>
                            <?php if (!empty($q['notes'])): ?>
                                <div class="text-xs text-muted"><?= e($q['notes']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="<?= $qStyle ?>font-size:12px;">
                            <?= $qExpiry ? e($qExpiry) : '—' ?>
                            <?php if ($qDays !== null && $qDays < 0): ?> (EXP)
                            <?php elseif ($qDays !== null && $qDays <= 90): ?> (<?= $qDays ?>d)<?php endif; ?>
                        </td>
                        <td>
                            <form method="POST"
                                  action="/my-profile/qualifications/delete/<?= $q['id'] ?>"
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
            <div class="empty-state" style="padding:12px 0 8px;"><p>No qualifications recorded yet.</p></div>
            <?php endif; ?>

            <div style="border-top:1px solid var(--border-color);padding-top:14px;margin-top:4px;">
                <div style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">Add Qualification</div>
                <form method="POST" action="/my-profile/qualifications/add">
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
                            <label>Issue Date</label>
                            <input type="date" name="issue_date" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Expiry Date</label>
                            <input type="date" name="expiry_date" class="form-control">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-outline btn-sm">+ Add Qualification</button>
                </form>
            </div>
        </div>

    </div><!-- /right col -->
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/app.php'; ?>
