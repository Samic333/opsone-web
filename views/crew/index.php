<?php
/**
 * Crew Profiles List
 * Variables: $crew, $departments, $bases, $fleets, $deptFilter, $baseFilter, $fleetFilter
 */
$headerAction = '<a href="/users/create" class="btn btn-primary btn-sm">+ Add Crew Member</a>';
?>

<!-- Filters -->
<form method="GET" action="/crew-profiles" class="card" style="padding:16px 20px;">
    <div class="flex items-center gap-2" style="flex-wrap:wrap;">
        <select name="dept" class="form-control" style="width:180px;">
            <option value="">All Departments</option>
            <?php foreach ($departments as $d): ?>
            <option value="<?= $d['id'] ?>" <?= $deptFilter == $d['id'] ? 'selected' : '' ?>>
                <?= e($d['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <select name="base" class="form-control" style="width:160px;">
            <option value="">All Bases</option>
            <?php foreach ($bases as $b): ?>
            <option value="<?= $b['id'] ?>" <?= $baseFilter == $b['id'] ? 'selected' : '' ?>>
                <?= e($b['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <select name="fleet" class="form-control" style="width:160px;">
            <option value="">All Fleets</option>
            <?php foreach ($fleets as $f): ?>
            <option value="<?= $f['id'] ?>" <?= $fleetFilter == $f['id'] ? 'selected' : '' ?>>
                <?= e($f['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-outline btn-sm">Filter</button>
        <?php if ($deptFilter || $baseFilter || $fleetFilter): ?>
            <a href="/crew-profiles" class="btn btn-sm" style="color:var(--text-muted);">Clear</a>
        <?php endif; ?>
        <span class="text-muted text-sm" style="margin-left:auto;"><?= count($crew) ?> crew member<?= count($crew) !== 1 ? 's' : '' ?></span>
    </div>
</form>

<!-- Crew Table -->
<?php if (empty($crew)): ?>
<div class="card">
    <div class="empty-state">
        <div class="icon">👥</div>
        <h3>No Crew Members Found</h3>
        <p>No active crew matching the current filters.</p>
        <a href="/users/create" class="btn btn-primary">Add Crew Member</a>
    </div>
</div>
<?php else: ?>
<div class="card" style="padding:0;">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Employee ID</th>
                    <th>Role</th>
                    <th>Department</th>
                    <th>Base</th>
                    <th>Fleet</th>
                    <th>Profile</th>
                    <th>Medical Exp.</th>
                    <th>Licences</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($crew as $c):
                $pct    = (int) ($c['profile_completion_pct'] ?? 0);
                $pctCol = $pct >= 80 ? 'var(--accent-green)' : ($pct >= 50 ? 'var(--accent-amber,#f59e0b)' : 'var(--accent-red)');

                $medExp   = $c['medical_expiry'] ?? null;
                $medDays  = $medExp ? (int) ceil((strtotime($medExp) - time()) / 86400) : null;
                $medStyle = '';
                if ($medDays !== null) {
                    if ($medDays < 0)    $medStyle = 'color:var(--accent-red);font-weight:700;';
                    elseif ($medDays <= 30)  $medStyle = 'color:var(--accent-red);';
                    elseif ($medDays <= 90)  $medStyle = 'color:var(--accent-amber,#f59e0b);';
                }
            ?>
            <tr style="cursor:pointer;" onclick="window.location='/crew-profiles/<?= $c['id'] ?>'">
                <td>
                    <div style="font-weight:600;"><?= e($c['name']) ?></div>
                    <div class="text-xs text-muted"><?= e($c['email'] ?? '') ?></div>
                </td>
                <td><code class="text-sm"><?= e($c['employee_id'] ?? '—') ?></code></td>
                <td class="text-sm"><?= e($c['role_names'] ?? '—') ?></td>
                <td class="text-sm"><?= e($c['department_name'] ?? '—') ?></td>
                <td class="text-sm"><?= e($c['base_name'] ?? '—') ?></td>
                <td class="text-sm"><?= e($c['fleet_name'] ?? '—') ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:6px;min-width:90px;">
                        <div style="flex:1;height:6px;background:var(--border-color);border-radius:3px;overflow:hidden;">
                            <div style="height:100%;width:<?= $pct ?>%;background:<?= $pctCol ?>;border-radius:3px;"></div>
                        </div>
                        <span style="font-size:11px;font-weight:700;color:<?= $pctCol ?>;"><?= $pct ?>%</span>
                    </div>
                </td>
                <td class="text-sm" style="<?= $medStyle ?>">
                    <?php if ($medExp): ?>
                        <?= e($medExp) ?>
                        <?php if ($medDays !== null && $medDays < 0): ?> <span style="font-size:10px;">(EXP)</span>
                        <?php elseif ($medDays !== null && $medDays <= 90): ?> <span style="font-size:10px;">(<?= $medDays ?>d)</span><?php endif; ?>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td class="text-sm"><?= (int)($c['license_count'] ?? 0) ?></td>
                <td>
                    <a href="/crew-profiles/<?= $c['id'] ?>" class="btn btn-sm btn-outline"
                       onclick="event.stopPropagation()">View</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/app.php'; ?>
