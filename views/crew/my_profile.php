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

<!-- ═══ Phase 6: Eligibility + Documents + Change Requests ══════════════ -->
<?php if (!empty($eligibility)):
    $elStatusColor = [
        'eligible' => '#10b981', 'warning' => '#f59e0b', 'blocked' => '#ef4444',
    ][$eligibility['status']] ?? '#6b7280';
?>
<div class="card mt-3" style="border-left:4px solid <?= $elStatusColor ?>;">
    <div class="card-header">
        <div>
            <div class="card-title">My Assignment Readiness</div>
            <div class="text-xs text-muted">Roster eligibility based on your current licences, medicals and documents.</div>
        </div>
        <span class="status-badge" style="--badge-color:<?= $elStatusColor ?>;font-size:13px;padding:5px 12px;">
            <?= strtoupper($eligibility['status']) ?>
        </span>
    </div>
    <?php if (!empty($eligibility['reasons'])): ?>
    <ul style="margin-top:8px;">
        <?php foreach ($eligibility['reasons'] as $r): ?>
            <li style="font-size:13px;"><?= e($r) ?></li>
        <?php endforeach; ?>
    </ul>
    <?php else: ?>
        <p class="text-muted">All good — no issues detected.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ─── My Documents ──────────────────────────────────────── -->
<div class="card mt-3" id="documents">
    <div class="card-header">
        <div class="card-title">My Documents</div>
        <a href="/my-profile/change-requests" class="btn btn-outline btn-sm">My Change Requests →</a>
    </div>
    <p class="text-xs text-muted">
        Uploads go through approval. Once approved, they replace the prior record.
    </p>

    <?php if (!empty($documents)): ?>
    <script>
    function toggleMyDocPreview(id) {
        var row = document.getElementById('my-preview-row-' + id);
        if (row) row.style.display = (row.style.display === 'none' || row.style.display === '') ? 'table-row' : 'none';
    }
    </script>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Document</th><th>Type</th><th>Expiry</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($documents as $d):
                $c = [
                    'valid' => '#10b981','pending_approval' => '#f59e0b',
                    'expired' => '#ef4444','rejected' => '#dc2626','revoked' => '#6b7280',
                ][$d['status']] ?? '#6b7280';
            ?>
            <tr>
                <td><strong><?= e($d['doc_title']) ?></strong>
                    <?php if (!empty($d['rejection_reason'])): ?>
                    <br><span class="text-xs" style="color:var(--accent-red);"><?= e($d['rejection_reason']) ?></span>
                    <?php endif; ?>
                </td>
                <td><?= e($d['doc_type']) ?></td>
                <td><?= e($d['expiry_date'] ?? '—') ?></td>
                <td><span class="status-badge" style="--badge-color:<?= $c ?>">
                    <?= ucwords(str_replace('_',' ',$d['status'])) ?>
                </span></td>
                <td>
                    <?php if (!empty($d['file_path'])): ?>
                    <button type="button" class="btn btn-outline btn-xs"
                            onclick="toggleMyDocPreview(<?= (int) $d['id'] ?>)">Preview</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if (!empty($d['file_path'])): ?>
            <tr id="my-preview-row-<?= (int) $d['id'] ?>" style="display:none;">
                <td colspan="5" style="background:var(--bg-secondary,#0f0f0f);padding:8px;">
                    <?php $doc = $d; $previewHeight = 480;
                          include VIEWS_PATH . '/personnel/_doc_preview.php'; ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state" style="padding:12px 0 8px;">
        <p>No documents on file yet. Upload your first via the form below — it will enter approval.</p>
    </div>
    <?php endif; ?>

    <h4 style="margin-top:14px;">Upload a new document</h4>
    <form method="POST" action="/my-profile/change-requests/submit" enctype="multipart/form-data"
          style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <?= csrfField() ?>
        <input type="hidden" name="target_entity" value="document">
        <input type="hidden" name="change_type" value="create">

        <div>
            <label class="text-xs text-muted">Title *</label>
            <input type="text" name="doc_title" class="form-control" required>
        </div>
        <div>
            <label class="text-xs text-muted">Type *</label>
            <select name="doc_type" class="form-control" required>
                <option value="">— Select —</option>
                <option value="passport">Passport</option>
                <option value="visa">Visa</option>
                <option value="medical">Medical</option>
                <option value="license">Licence</option>
                <option value="type_rating">Type Rating</option>
                <option value="type_auth">Type Authorization</option>
                <option value="cabin_attestation">Cabin Crew Attestation</option>
                <option value="company_id">Company ID</option>
                <option value="airside_permit">Airside Permit</option>
                <option value="contract">Employment Contract</option>
                <option value="certificate">Certificate</option>
                <option value="other">Other</option>
            </select>
        </div>
        <div>
            <label class="text-xs text-muted">Number</label>
            <input type="text" name="doc_number" class="form-control">
        </div>
        <div>
            <label class="text-xs text-muted">Issuing Authority</label>
            <input type="text" name="issuing_authority" class="form-control">
        </div>
        <div>
            <label class="text-xs text-muted">Issue Date</label>
            <input type="date" name="issue_date" class="form-control">
        </div>
        <div>
            <label class="text-xs text-muted">Expiry Date</label>
            <input type="date" name="expiry_date" class="form-control">
        </div>
        <div style="grid-column:1/-1;">
            <label class="text-xs text-muted">Scan / PDF</label>
            <input type="file" name="supporting_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
        </div>
        <div style="grid-column:1/-1;">
            <button class="btn btn-primary btn-sm">Submit for Approval</button>
            <span class="text-xs text-muted" style="margin-left:10px;">
                Your existing document (if any) remains active until a reviewer approves the replacement.
            </span>
        </div>
    </form>
</div>

<!-- ─── Request change to sensitive compliance field ───── -->
<div class="card mt-3">
    <div class="card-header"><div class="card-title">Request Compliance Update</div></div>
    <p class="text-xs text-muted">
        Use this when you need to change a field that requires approval — licence number,
        medical/passport/visa expiry, contract details, etc. Your original record
        stays in effect until approved.
    </p>
    <form method="POST" action="/my-profile/change-requests/submit"
          style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <?= csrfField() ?>
        <input type="hidden" name="target_entity" value="profile">
        <input type="hidden" name="change_type" value="update">

        <div>
            <label class="text-xs text-muted">Passport Number</label>
            <input type="text" name="passport_number" class="form-control" value="<?= e($crewProfile['passport_number'] ?? '') ?>">
        </div>
        <div>
            <label class="text-xs text-muted">Passport Expiry</label>
            <input type="date" name="passport_expiry" class="form-control" value="<?= e($crewProfile['passport_expiry'] ?? '') ?>">
        </div>
        <div>
            <label class="text-xs text-muted">Visa Number</label>
            <input type="text" name="visa_number" class="form-control" value="<?= e($crewProfile['visa_number'] ?? '') ?>">
        </div>
        <div>
            <label class="text-xs text-muted">Visa Expiry</label>
            <input type="date" name="visa_expiry" class="form-control" value="<?= e($crewProfile['visa_expiry'] ?? '') ?>">
        </div>
        <div>
            <label class="text-xs text-muted">Medical Class</label>
            <input type="text" name="medical_class" class="form-control" value="<?= e($crewProfile['medical_class'] ?? '') ?>">
        </div>
        <div>
            <label class="text-xs text-muted">Medical Expiry</label>
            <input type="date" name="medical_expiry" class="form-control" value="<?= e($crewProfile['medical_expiry'] ?? '') ?>">
        </div>
        <div style="grid-column:1/-1;">
            <button class="btn btn-primary btn-sm">Submit Change Request</button>
        </div>
    </form>
</div>

<!-- ─── My pending change requests summary ────────────── -->
<?php if (!empty($myChangeRequests)): ?>
<div class="card mt-3">
    <div class="card-header">
        <div class="card-title">Recent Change Requests</div>
        <a href="/my-profile/change-requests" class="btn btn-outline btn-sm">View all →</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>Target</th><th>Status</th><th>Submitted</th></tr></thead>
            <tbody>
            <?php foreach (array_slice($myChangeRequests, 0, 5) as $r):
                $c = ['submitted' => '#f59e0b','under_review' => '#3b82f6','info_requested' => '#8b5cf6',
                      'approved' => '#10b981','rejected' => '#ef4444','withdrawn' => '#6b7280',][$r['status']] ?? '#6b7280';
            ?>
            <tr>
                <td>#<?= (int) $r['id'] ?></td>
                <td><code><?= e($r['target_entity']) ?></code></td>
                <td><span class="status-badge" style="--badge-color:<?= $c ?>">
                    <?= ucwords(str_replace('_',' ',$r['status'])) ?></span></td>
                <td><?= formatDateTime($r['submitted_at']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/app.php'; ?>
