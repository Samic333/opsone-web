<?php
/**
 * Users / People — staff directory.
 *
 * Layout (Phase K redesign):
 *   1. KPI mini-cards (Total / Active / Pending / Suspended) — also act as filter chips
 *   2. Toolbar — status tabs + client-side search input
 *   3. Modern roster table — avatar + name/email, role chips, posting (dept · base),
 *      status badge, action buttons
 *
 * Controller passes $users (already filtered by ?status=) and $statusFilter.
 * No data-shape changes; pure visual redesign + an inline JS search filter
 * that hides rows whose name / email / employee_id don't match the query.
 */
$pageTitle    = 'Users';
$pageSubtitle = 'Manage staff accounts';
$headerAction = '<a href="/users/create" class="btn btn-primary">+ New User</a>';
$statusFilter = $_GET['status'] ?? null;

// KPI counts for the header strip — derived from the current $users payload.
// These are *visible-row* counts (filtered by status if a tab is active).
$counts = [
    'total'     => count($users),
    'active'    => 0,
    'pending'   => 0,
    'suspended' => 0,
    'inactive'  => 0,
];
foreach ($users as $u) {
    $s = $u['status'] ?? 'inactive';
    if (isset($counts[$s])) $counts[$s]++;
}

// Stable colour assignments for the role chips — cycled by role-name hash so
// a given role always renders in the same colour across the table.
$roleChipColors = [
    'var(--accent-blue)',
    'var(--accent-cyan)',
    'var(--accent-purple)',
    'var(--accent-green)',
    'var(--accent-yellow)',
    'var(--accent-red)',
];

ob_start();
?>

<!-- ─── 1. KPI mini cards ────────────────────────────────────────────── -->
<div class="users-kpi-grid"
     style="display:grid; grid-template-columns:repeat(4, 1fr); gap:0.85rem; margin-bottom:1.25rem;">
    <?php
    $kpiCards = [
        ['label' => 'Total Staff',       'value' => $counts['total'],     'tone' => 'var(--accent-blue)',   'href' => '/users'],
        ['label' => 'Active',            'value' => $counts['active'],    'tone' => 'var(--status-cleared)','href' => '/users?status=active'],
        ['label' => 'Pending',           'value' => $counts['pending'],   'tone' => 'var(--status-advisory)','href' => '/users?status=pending'],
        ['label' => 'Suspended',         'value' => $counts['suspended'], 'tone' => 'var(--status-critical)','href' => '/users?status=suspended'],
    ];
    foreach ($kpiCards as $kpi):
    ?>
    <a href="<?= e($kpi['href']) ?>"
       style="display:flex; flex-direction:column; gap:6px;
              padding:14px 16px;
              background:var(--bg-card);
              border:1px solid var(--border-color);
              border-left:3px solid <?= $kpi['tone'] ?>;
              border-radius:var(--radius-md);
              text-decoration:none; color:inherit;
              transition:background 0.15s, transform 0.15s;"
       onmouseover="this.style.background='var(--bg-card-hover)';this.style.transform='translateY(-1px)';"
       onmouseout="this.style.background='var(--bg-card)';this.style.transform='translateY(0)';">
        <span style="font-size:11px; font-weight:700; text-transform:uppercase;
                     letter-spacing:.06em; color:var(--text-tertiary);">
            <?= e($kpi['label']) ?>
        </span>
        <span style="font-size:1.6rem; font-weight:700; color:<?= $kpi['tone'] ?>;
                     letter-spacing:-0.02em; line-height:1.1;">
            <?= (int) $kpi['value'] ?>
        </span>
    </a>
    <?php endforeach; ?>
</div>

<!-- ─── 2. Toolbar (status tabs + search) ───────────────────────────── -->
<div class="users-toolbar"
     style="display:flex; flex-wrap:wrap; align-items:center; gap:12px;
            justify-content:space-between; margin-bottom:1rem;">

    <div class="filter-tabs" style="display:flex; flex-wrap:wrap; gap:6px;">
        <a href="/users" class="filter-tab <?= !$statusFilter ? 'active' : '' ?>">All</a>
        <a href="/users?status=active"    class="filter-tab <?= $statusFilter === 'active' ? 'active' : '' ?>">Active</a>
        <a href="/users?status=pending"   class="filter-tab <?= $statusFilter === 'pending' ? 'active' : '' ?>">Pending</a>
        <a href="/users?status=suspended" class="filter-tab <?= $statusFilter === 'suspended' ? 'active' : '' ?>">Suspended</a>
        <a href="/users?status=inactive"  class="filter-tab <?= $statusFilter === 'inactive' ? 'active' : '' ?>">Inactive</a>
    </div>

    <div style="position:relative; min-width:280px; flex:1; max-width:380px;">
        <span style="position:absolute; left:10px; top:50%; transform:translateY(-50%);
                     color:var(--text-tertiary); display:inline-flex;">
            <?= sidebarIcon('chevron-right', 14) ?>
        </span>
        <input id="users-search" type="search" autocomplete="off"
               placeholder="Search name, email, or employee ID…"
               style="width:100%; padding:8px 12px 8px 32px;
                      background:var(--bg-input); color:var(--text-primary);
                      border:1px solid var(--border-color); border-radius:var(--radius-sm);
                      font-size:13px; font-family:inherit; outline:none;">
    </div>
</div>

<!-- ─── 3. Roster table ─────────────────────────────────────────────── -->
<?php if (empty($users)): ?>
    <div class="card">
        <div class="empty-state">
            <h3>No Users Found</h3>
            <p>
                <?= $statusFilter
                    ? 'No staff with this status. Try a different filter or clear filters.'
                    : 'Create your first staff user to get started.' ?>
            </p>
            <a href="/users/create" class="btn btn-primary" style="margin-top:0.5rem;">
                + Create User
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card" style="padding:0; overflow:hidden;">
        <div class="table-wrap" style="margin:0;">
            <table id="users-table" style="margin:0;">
                <thead>
                    <tr>
                        <th>Staff Member</th>
                        <th>Employee ID</th>
                        <th>Roles</th>
                        <th>Posting</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u):
                    // Initials + avatar source
                    $name      = $u['name'] ?? '';
                    $initials  = '';
                    foreach (preg_split('/\s+/', trim($name)) as $part) {
                        if ($part !== '' && strlen($initials) < 2) {
                            $initials .= mb_substr($part, 0, 1);
                        }
                    }
                    $initials = strtoupper($initials ?: 'U');
                    $avatarUrl = $u['profile_photo_path'] ?? $u['avatar'] ?? null;

                    // Role chips — split comma list, ignore empty values
                    $rolesList = [];
                    foreach (preg_split('/,\s*/', (string) ($u['role_names'] ?? '')) as $r) {
                        $r = trim($r, ", \t");
                        if ($r !== '') $rolesList[] = $r;
                    }

                    // Posting line: department + base if both, fallback to whichever is set.
                    $postingParts = [];
                    if (!empty($u['department_name'])) $postingParts[] = $u['department_name'];
                    if (!empty($u['base_code']))       $postingParts[] = $u['base_code'];
                    $posting = $postingParts ? implode(' · ', $postingParts) : '—';

                    $haystack = strtolower(($u['name'] ?? '') . ' ' . ($u['email'] ?? '') . ' ' . ($u['employee_id'] ?? ''));
                ?>
                    <tr data-search="<?= e($haystack) ?>">
                        <td>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <?php if ($avatarUrl): ?>
                                    <img src="<?= e($avatarUrl) ?>" alt=""
                                         style="width:32px; height:32px; border-radius:50%;
                                                object-fit:cover; flex-shrink:0;">
                                <?php else: ?>
                                    <span style="width:32px; height:32px; border-radius:50%;
                                                 display:inline-flex; align-items:center; justify-content:center;
                                                 background:var(--accent-blue); color:#fff;
                                                 font-size:11px; font-weight:700; flex-shrink:0;">
                                        <?= e($initials) ?>
                                    </span>
                                <?php endif; ?>
                                <div style="min-width:0;">
                                    <div style="font-weight:600; color:var(--text-primary);
                                                overflow:hidden; text-overflow:ellipsis;">
                                        <?= e($name) ?>
                                    </div>
                                    <div style="font-size:11px; color:var(--text-tertiary);
                                                overflow:hidden; text-overflow:ellipsis;">
                                        <?= e($u['email'] ?? '') ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <code style="font-size:11px; color:var(--text-secondary);
                                         background:var(--bg-input);
                                         padding:2px 6px; border-radius:4px;">
                                <?= e($u['employee_id'] ?? '—') ?>
                            </code>
                        </td>
                        <td>
                            <?php if (empty($rolesList)): ?>
                                <span style="color:var(--text-tertiary); font-size:12px;">—</span>
                            <?php else: ?>
                                <div style="display:flex; flex-wrap:wrap; gap:4px;">
                                    <?php foreach ($rolesList as $i => $rn):
                                        $color = $roleChipColors[abs(crc32($rn)) % count($roleChipColors)];
                                    ?>
                                        <span style="font-size:10px; font-weight:600;
                                                     padding:2px 8px; border-radius:10px;
                                                     background:<?= $color ?>22;
                                                     color:<?= $color ?>;
                                                     white-space:nowrap;">
                                            <?= e($rn) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="font-size:12px; color:var(--text-secondary);">
                                <?= e($posting) ?>
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <?= statusBadge($u['status']) ?>
                        </td>
                        <td style="text-align:right;">
                            <div class="btn-group" style="justify-content:flex-end; gap:6px;">
                                <a href="/users/edit/<?= (int) $u['id'] ?>" class="btn btn-xs btn-outline">Edit</a>
                                <form method="POST" action="/users/toggle/<?= (int) $u['id'] ?>"
                                      style="display:inline; margin:0;">
                                    <?= csrfField() ?>
                                    <button type="submit"
                                            class="btn btn-xs <?= $u['status'] === 'active' ? 'btn-warning' : 'btn-success' ?>"
                                            title="<?= $u['status'] === 'active' ? 'Suspend this user' : 'Activate this user' ?>">
                                        <?= $u['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Empty state for the *search* filter (kept hidden by default; shown only
         when the search input filters every row out). -->
    <div id="users-empty-search"
         style="display:none; margin-top:1rem; padding:2rem; text-align:center;
                color:var(--text-tertiary); font-size:14px;
                background:var(--bg-card); border:1px solid var(--border-color);
                border-radius:var(--radius-md);">
        No staff match your search. <a href="#" id="users-clear-search" style="color:var(--accent-blue);">Clear search</a>
    </div>

    <script>
    (function () {
        var input = document.getElementById('users-search');
        var table = document.getElementById('users-table');
        var emptyMsg = document.getElementById('users-empty-search');
        if (!input || !table) return;

        function filter() {
            var q = input.value.trim().toLowerCase();
            var rows = table.querySelectorAll('tbody tr[data-search]');
            var visible = 0;
            rows.forEach(function (row) {
                var match = q === '' || row.getAttribute('data-search').indexOf(q) !== -1;
                row.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            if (emptyMsg) emptyMsg.style.display = (visible === 0 && q !== '') ? '' : 'none';
        }

        input.addEventListener('input', filter);

        var clear = document.getElementById('users-clear-search');
        if (clear) clear.addEventListener('click', function (e) {
            e.preventDefault();
            input.value = '';
            filter();
            input.focus();
        });
    })();
    </script>
<?php endif; ?>

<!-- Responsive collapse for narrower windows. -->
<style>
@media (max-width: 1100px) {
    .users-kpi-grid { grid-template-columns: repeat(2, 1fr) !important; }
}
@media (max-width: 720px) {
    .users-toolbar { flex-direction: column; align-items: stretch; }
}
</style>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/app.php'; ?>
