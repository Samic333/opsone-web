<?php $pageTitle = 'Edit User'; ob_start(); ?>
<div class="card" style="max-width: 700px;">
    <form method="POST" action="/users/update/<?= $user['id'] ?>">
        <?= csrfField() ?>
        <div class="form-row">
            <div class="form-group">
                <label for="name">Full Name *</label>
                <input type="text" id="name" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="password">New Password <small class="text-muted">(leave blank to keep current)</small></label>
                <input type="password" id="password" name="password" class="form-control" minlength="6">
            </div>
            <div class="form-group">
                <label for="employee_id">Employee ID</label>
                <input type="text" id="employee_id" name="employee_id" class="form-control" value="<?= e($user['employee_id'] ?? '') ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="department_id">Department</label>
                <select id="department_id" name="department_id" class="form-control">
                    <option value="">— Select —</option>
                    <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= ($user['department_id'] ?? '') == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="base_id">Base</label>
                <select id="base_id" name="base_id" class="form-control">
                    <option value="">— Select —</option>
                    <?php foreach ($bases as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= ($user['base_id'] ?? '') == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?> (<?= e($b['code']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="fleet_id">Fleet</label>
                <select id="fleet_id" name="fleet_id" class="form-control">
                    <option value="">— Select —</option>
                    <?php foreach ($fleets as $f): ?>
                    <option value="<?= $f['id'] ?>" <?= ($user['fleet_id'] ?? '') == $f['id'] ? 'selected' : '' ?>>
                        <?= e($f['name']) ?><?= $f['code'] ? ' (' . e($f['code']) . ')' : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="employment_status">Employment Type</label>
                <select id="employment_status" name="employment_status" class="form-control">
                    <option value="">— Select —</option>
                    <?php foreach (['full_time'=>'Full Time','part_time'=>'Part Time','contract'=>'Contract','secondment'=>'Secondment','trainee'=>'Trainee'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= ($user['employment_status'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" class="form-control">
                    <?php foreach (['pending','active','suspended','inactive'] as $s): ?>
                    <option value="<?= $s ?>" <?= $user['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="display:flex;flex-direction:column;justify-content:flex-end;padding-bottom:22px;gap:8px;">
                <label class="form-check">
                    <input type="checkbox" name="web_access" <?= !empty($user['web_access']) ? 'checked' : '' ?>> Allow web portal access
                </label>
                <label class="form-check">
                    <input type="checkbox" name="mobile_access" <?= !empty($user['mobile_access']) ? 'checked' : '' ?>> Allow mobile app access
                </label>
            </div>
        </div>
        <div class="form-group">
            <label>Assign Roles</label>
            <div class="checkbox-grid">
                <?php foreach ($roles as $r): ?>
                <label class="form-check">
                    <input type="checkbox" name="roles[]" value="<?= $r['id'] ?>" <?= in_array($r['id'], $userRoleIds) ? 'checked' : '' ?>> <?= e($r['name']) ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="flex gap-1 mt-2">
            <button type="submit" class="btn btn-primary">Update User</button>
            <a href="/users" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<?php if (!empty($devices)): ?>
<div class="card mt-3" style="max-width: 700px;">
    <div class="card-header"><div class="card-title">📱 Registered Devices</div></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Device</th><th>Platform</th><th>Status</th><th>First Login</th><th>Last Sync</th></tr></thead>
            <tbody>
            <?php foreach ($devices as $dev): ?>
            <tr>
                <td><code><?= e(substr($dev['device_uuid'], 0, 20)) ?>...</code></td>
                <td><?= e($dev['platform'] ?? '—') ?> <?= e($dev['model'] ?? '') ?></td>
                <td><?= statusBadge($dev['approval_status']) ?></td>
                <td class="text-sm"><?= formatDateTime($dev['first_login_at']) ?></td>
                <td class="text-sm"><?= formatDateTime($dev['last_sync_at']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>


<!-- ─── Crew Profile ─────────────────────────────────── -->
<div class="card mt-3" style="max-width: 700px;" id="crew-profile">
    <div class="card-header"><div class="card-title">👤 Crew Profile</div></div>
    <form method="POST" action="/users/profile/<?= $user['id'] ?>">
        <?= csrfField() ?>
        <div class="form-row">
            <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" name="date_of_birth" class="form-control" value="<?= e($crewProfile['date_of_birth'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Nationality</label>
                <input type="text" name="nationality" class="form-control" placeholder="e.g. Kenyan" value="<?= e($crewProfile['nationality'] ?? '') ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" class="form-control" placeholder="+254 700 000 000" value="<?= e($crewProfile['phone'] ?? '') ?>">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Emergency Contact Name</label>
                <input type="text" name="emergency_name" class="form-control" value="<?= e($crewProfile['emergency_name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Emergency Phone</label>
                <input type="text" name="emergency_phone" class="form-control" value="<?= e($crewProfile['emergency_phone'] ?? '') ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Emergency Relation</label>
            <input type="text" name="emergency_relation" class="form-control" placeholder="e.g. Spouse, Parent" value="<?= e($crewProfile['emergency_relation'] ?? '') ?>">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Passport Number</label>
                <input type="text" name="passport_number" class="form-control" value="<?= e($crewProfile['passport_number'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Passport Country</label>
                <input type="text" name="passport_country" class="form-control" placeholder="e.g. Kenya" value="<?= e($crewProfile['passport_country'] ?? '') ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Passport Expiry</label>
            <input type="date" name="passport_expiry" class="form-control" value="<?= e($crewProfile['passport_expiry'] ?? '') ?>">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Medical Class</label>
                <select name="medical_class" class="form-control">
                    <option value="">— Select —</option>
                    <?php foreach (['Class 1','Class 2','Class 3','LAPL','Cabin Crew Medical'] as $mc): ?>
                    <option value="<?= $mc ?>" <?= ($crewProfile['medical_class'] ?? '') === $mc ? 'selected' : '' ?>><?= $mc ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Medical Expiry</label>
                <input type="date" name="medical_expiry" class="form-control" value="<?= e($crewProfile['medical_expiry'] ?? '') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Contract Type</label>
                <select name="contract_type" class="form-control">
                    <option value="">— Select —</option>
                    <?php foreach (['permanent','fixed_term','probation','contractor'] as $ct): ?>
                    <option value="<?= $ct ?>" <?= ($crewProfile['contract_type'] ?? '') === $ct ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $ct)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Contract Expiry <small class="text-muted">(fixed-term/probation)</small></label>
                <input type="date" name="contract_expiry" class="form-control" value="<?= e($crewProfile['contract_expiry'] ?? '') ?>">
            </div>
        </div>

        <div class="flex gap-1 mt-2">
            <button type="submit" class="btn btn-primary">Save Crew Profile</button>
        </div>
    </form>
</div>

<!-- ─── Capabilities Override ─────────────────────────────────── -->
<div class="card mt-3" style="max-width: 700px;" id="capabilities">
    <div class="card-header">
        <div class="card-title">🔐 Capabilities Override</div>
        <p style="font-size: 12px; color: var(--text-muted); margin: 0;">Explicitly grant or revoke specific module capabilities for this user, overriding their base role permissions.</p>
    </div>
    <div style="padding: 1rem;">
        <form method="POST" action="/users/capabilities/<?= $user['id'] ?>">
            <?= csrfField() ?>
            <div class="table-wrap mb-3">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="padding: 8px;">Module &amp; Capability</th>
                            <th style="padding: 8px; width: 100px;">Role Default</th>
                            <th style="padding: 8px; width: 180px;">User Override</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($allCapabilities)): ?>
                        <tr><td colspan="3" class="empty-state" style="padding: 16px;">No modular capabilities available.</td></tr>
                    <?php else: ?>
                        <?php 
                        $currentModule = null;
                        foreach ($allCapabilities as $cap): 
                            if ($currentModule !== $cap['module_name']):
                                $currentModule = $cap['module_name'];
                        ?>
                        <tr><td colspan="3" style="background: var(--bg-body); font-weight: 600; font-size: 11px; text-transform: uppercase; padding: 6px 12px; color: var(--accent-magenta);">■ <?= e($currentModule) ?></td></tr>
                        <?php endif; ?>
                        
                        <?php 
                            $isGrantedByRole = in_array($cap['id'], $roleCaps);
                            $override = isset($overrides[$cap['id']]) ? $overrides[$cap['id']] : null;
                        ?>
                        <tr>
                            <td style="padding: 8px 12px;">
                                <div style="font-size: 13px; font-weight: 500; font-family: monospace; color: var(--text);"><?= e($cap['capability']) ?></div>
                                <?php if ($cap['description']): ?>
                                    <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;"><?= e($cap['description']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 8px 12px;">
                                <?php if ($isGrantedByRole): ?>
                                    <span style="display:inline-block; padding: 2px 6px; background-color: rgba(16, 185, 129, 0.1); color: var(--accent-green); border-radius: 4px; font-size: 11px; font-weight: 600;">Granted</span>
                                <?php else: ?>
                                    <span style="font-size: 11px; color: var(--text-muted);">None</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 8px 12px;">
                                <select name="overrides[<?= $cap['id'] ?>]" class="form-control" style="font-size: 12px; padding: 4px 8px;">
                                    <option value="default" <?= $override === null ? 'selected' : '' ?>>Inherit from Role</option>
                                    <option value="grant" <?= $override === true ? 'selected' : '' ?>>Explicitly GRANT</option>
                                    <option value="revoke" <?= $override === false ? 'selected' : '' ?>>Explicitly DENY</option>
                                </select>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="flex gap-1">
                <button type="submit" class="btn btn-primary">Save Overrides</button>
            </div>
        </form>
    </div>
</div>

<!-- ─── Licences & Ratings ──────────────────────────── -->
<div class="card mt-3" style="max-width: 700px;" id="licenses">
    <div class="card-header"><div class="card-title">🪪 Licences &amp; Ratings</div></div>

    <?php if (!empty($licenses)): ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Type</th><th>Number</th><th>Authority</th><th>Issued</th><th>Expires</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($licenses as $lic):
                $expiry = $lic['expiry_date'] ?? null;
                $daysLeft = $expiry ? (int) ceil((strtotime($expiry) - time()) / 86400) : null;
                $expiryColor = '';
                if ($daysLeft !== null) {
                    if ($daysLeft < 0)   $expiryColor = 'color:var(--accent-red);font-weight:700;';
                    elseif ($daysLeft <= 30) $expiryColor = 'color:var(--accent-red);';
                    elseif ($daysLeft <= 90) $expiryColor = 'color:var(--accent-amber,#f59e0b);';
                }
            ?>
            <tr>
                <td><strong><?= e($lic['license_type']) ?></strong></td>
                <td><code><?= e($lic['license_number'] ?? '—') ?></code></td>
                <td class="text-sm"><?= e($lic['issuing_authority'] ?? '—') ?></td>
                <td class="text-sm"><?= e($lic['issue_date'] ?? '—') ?></td>
                <td class="text-sm" style="<?= $expiryColor ?>">
                    <?= e($expiry ?? '—') ?>
                    <?php if ($daysLeft !== null && $daysLeft < 0): ?> <span style="font-size:10px;">(EXPIRED)</span>
                    <?php elseif ($daysLeft !== null && $daysLeft <= 90): ?> <span style="font-size:10px;">(<?= $daysLeft ?>d)</span><?php endif; ?>
                </td>
                <td>
                    <form method="POST" action="/users/licenses/delete/<?= $user['id'] ?>/<?= $lic['id'] ?>" style="display:inline" onsubmit="return confirm('Remove this licence?')">
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
        <div class="empty-state" style="padding: 16px 0 8px;"><p>No licences recorded yet.</p></div>
    <?php endif; ?>

    <div style="border-top: 1px solid var(--border-color); margin-top: 12px; padding-top: 14px;">
        <div style="font-size:12px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">Add Licence / Rating</div>
        <form method="POST" action="/users/licenses/add/<?= $user['id'] ?>">
            <?= csrfField() ?>
            <div class="form-row">
                <div class="form-group">
                    <label>Type *</label>
                    <input type="text" name="license_type" class="form-control" placeholder="e.g. ATPL, Type Rating B737, ME/IR" required>
                </div>
                <div class="form-group">
                    <label>Number</label>
                    <input type="text" name="license_number" class="form-control" placeholder="e.g. KEN-ATPL-12345">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Issuing Authority</label>
                    <input type="text" name="issuing_authority" class="form-control" placeholder="e.g. KCAA, UCAA">
                </div>
                <div class="form-group">
                    <label>Issue Date</label>
                    <input type="date" name="issue_date" class="form-control">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Expiry Date</label>
                    <input type="date" name="expiry_date" class="form-control">
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <input type="text" name="notes" class="form-control" placeholder="Optional notes">
                </div>
            </div>
            <button type="submit" class="btn btn-outline btn-sm">+ Add Licence</button>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/app.php'; ?>
