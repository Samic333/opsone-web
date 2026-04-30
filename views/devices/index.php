<?php
/**
 * iPad / Mobile Device Approval Registry.
 *
 * Layout (Phase K redesign):
 *   1. KPI mini-cards (Pending / Approved / Rejected / Revoked) — clickable filter shortcuts
 *   2. Toolbar — status tabs + client-side search
 *   3. Modern device table:
 *      - User cell with initials avatar + name/email stacked
 *      - Device UUID truncated as code chip
 *      - Platform with icon (iOS / iPadOS / Android)
 *      - Model
 *      - Status badge (cockpit-light)
 *      - Registered + Last sync as time stamps
 *      - Right-aligned action buttons (Approve / Reject / Revoke) — POST forms
 *        with CSRF preserved verbatim
 *
 * Controller passes $devices, $statsMap, and uses ?status= filter.
 * No data-shape changes; pure visual redesign.
 */
$pageTitle    = 'Device Approval';
$pageSubtitle = 'Manage mobile device access';
$statusFilter = $_GET['status'] ?? null;

$__initials = static function (?string $name): string {
    $name = trim((string) $name);
    if ($name === '') return '?';
    $parts = preg_split('/\s+/', $name);
    $i = '';
    foreach ($parts as $p) {
        if ($p !== '' && strlen($i) < 2) $i .= mb_substr($p, 0, 1);
    }
    return strtoupper($i ?: 'U');
};

ob_start();
?>

<!-- ─── 1. KPI mini cards ────────────────────────────────────────────── -->
<div class="devices-kpi-grid"
     style="display:grid; grid-template-columns:repeat(4, 1fr); gap:0.85rem; margin-bottom:1.25rem;">
    <?php
    $kpiCards = [
        ['label' => 'Pending',  'value' => $statsMap['pending']  ?? 0, 'tone' => 'var(--status-advisory)','href' => '/devices?status=pending'],
        ['label' => 'Approved', 'value' => $statsMap['approved'] ?? 0, 'tone' => 'var(--status-cleared)', 'href' => '/devices?status=approved'],
        ['label' => 'Rejected', 'value' => $statsMap['rejected'] ?? 0, 'tone' => 'var(--status-critical)','href' => '/devices?status=rejected'],
        ['label' => 'Revoked',  'value' => $statsMap['revoked']  ?? 0, 'tone' => 'var(--accent-purple)',  'href' => '/devices?status=revoked'],
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

<!-- ─── 2. Toolbar ──────────────────────────────────────────────────── -->
<div class="devices-toolbar"
     style="display:flex; flex-wrap:wrap; align-items:center; gap:12px;
            justify-content:space-between; margin-bottom:1rem;">

    <div class="filter-tabs" style="display:flex; flex-wrap:wrap; gap:6px;">
        <a href="/devices" class="filter-tab <?= !$statusFilter ? 'active' : '' ?>">All</a>
        <a href="/devices?status=pending"  class="filter-tab <?= $statusFilter === 'pending'  ? 'active' : '' ?>">Pending</a>
        <a href="/devices?status=approved" class="filter-tab <?= $statusFilter === 'approved' ? 'active' : '' ?>">Approved</a>
        <a href="/devices?status=rejected" class="filter-tab <?= $statusFilter === 'rejected' ? 'active' : '' ?>">Rejected</a>
        <a href="/devices?status=revoked"  class="filter-tab <?= $statusFilter === 'revoked'  ? 'active' : '' ?>">Revoked</a>
    </div>

    <div style="position:relative; min-width:280px; flex:1; max-width:380px;">
        <span style="position:absolute; left:10px; top:50%; transform:translateY(-50%);
                     color:var(--text-tertiary); display:inline-flex;">
            <?= sidebarIcon('chevron-right', 14) ?>
        </span>
        <input id="devices-search" type="search" autocomplete="off"
               placeholder="Search user, email, or UUID…"
               style="width:100%; padding:8px 12px 8px 32px;
                      background:var(--bg-input); color:var(--text-primary);
                      border:1px solid var(--border-color); border-radius:var(--radius-sm);
                      font-size:13px; font-family:inherit; outline:none;">
    </div>
</div>

<!-- ─── 3. Device table ─────────────────────────────────────────────── -->
<?php if (empty($devices)): ?>
    <div class="card">
        <div class="empty-state">
            <div class="icon"><?= sidebarIcon('device-tablet', 32) ?></div>
            <h3>No Devices Found</h3>
            <p>Devices will appear here when users log in from the mobile app.</p>
        </div>
    </div>
<?php else: ?>
    <div class="card" style="padding:0; overflow:hidden;">
        <div class="table-wrap" style="margin:0;">
            <table id="devices-table" style="margin:0;">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Device UUID</th>
                        <th>Platform</th>
                        <th>Model</th>
                        <th style="text-align:center;">Status</th>
                        <th>Registered</th>
                        <th>Last Sync</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($devices as $d):
                    $userName = $d['user_name'] ?? '';
                    $platform = strtolower((string)($d['platform'] ?? ''));
                    $platformIcon = (str_contains($platform, 'ipad') || str_contains($platform, 'ios'))
                        ? 'device-tablet' : 'cloud-arrow-up';
                    $platformTone = (str_contains($platform, 'ipad') || str_contains($platform, 'ios'))
                        ? 'var(--accent-cyan)' : 'var(--accent-green)';
                    $haystack = strtolower(
                        ($d['user_name'] ?? '') . ' ' .
                        ($d['user_email'] ?? '') . ' ' .
                        ($d['device_uuid'] ?? '') . ' ' .
                        ($d['model'] ?? '')
                    );
                ?>
                    <tr data-search="<?= e($haystack) ?>">
                        <td>
                            <div style="display:flex; align-items:center; gap:10px; min-width:0;">
                                <span style="width:32px; height:32px; border-radius:50%;
                                             display:inline-flex; align-items:center; justify-content:center;
                                             background:var(--accent-blue); color:#fff;
                                             font-size:11px; font-weight:700; flex-shrink:0;">
                                    <?= e($__initials($userName)) ?>
                                </span>
                                <div style="min-width:0;">
                                    <div style="font-weight:600; color:var(--text-primary);
                                                overflow:hidden; text-overflow:ellipsis;">
                                        <?= e($userName) ?>
                                    </div>
                                    <div style="font-size:11px; color:var(--text-tertiary);
                                                overflow:hidden; text-overflow:ellipsis;">
                                        <?= e($d['user_email'] ?? '') ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <code title="<?= e($d['device_uuid'] ?? '') ?>"
                                  style="font-size:11px; color:var(--text-secondary);
                                         background:var(--bg-input);
                                         padding:2px 6px; border-radius:4px;
                                         font-family:ui-monospace,monospace;">
                                <?= e(substr((string)($d['device_uuid'] ?? ''), 0, 18)) ?>…
                            </code>
                        </td>
                        <td>
                            <span style="display:inline-flex; align-items:center; gap:6px;
                                         font-size:12px; color:var(--text-primary);">
                                <span style="display:inline-flex; color:<?= $platformTone ?>;">
                                    <?= sidebarIcon($platformIcon, 14) ?>
                                </span>
                                <?= e($d['platform'] ?? '—') ?>
                            </span>
                        </td>
                        <td>
                            <span style="font-size:12px; color:var(--text-secondary);">
                                <?= e($d['model'] ?? '—') ?>
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <?= statusBadge($d['approval_status']) ?>
                        </td>
                        <td>
                            <span style="font-size:11px; color:var(--text-tertiary);
                                         font-variant-numeric:tabular-nums; white-space:nowrap;">
                                <?= formatDateTime($d['first_login_at']) ?>
                            </span>
                        </td>
                        <td>
                            <span style="font-size:11px; color:var(--text-tertiary);
                                         font-variant-numeric:tabular-nums; white-space:nowrap;">
                                <?= formatDateTime($d['last_sync_at']) ?>
                            </span>
                        </td>
                        <td style="text-align:right;">
                            <div class="btn-group" style="justify-content:flex-end; gap:6px;">
                                <?php if ($d['approval_status'] === 'pending'): ?>
                                    <form method="POST" action="/devices/approve/<?= (int) $d['id'] ?>"
                                          style="display:inline; margin:0;">
                                        <?= csrfField() ?>
                                        <button type="submit" class="btn btn-xs btn-success">Approve</button>
                                    </form>
                                    <form method="POST" action="/devices/reject/<?= (int) $d['id'] ?>"
                                          style="display:inline; margin:0;">
                                        <?= csrfField() ?>
                                        <button type="submit" class="btn btn-xs btn-danger">Reject</button>
                                    </form>
                                <?php elseif ($d['approval_status'] === 'approved'): ?>
                                    <form method="POST" action="/devices/revoke/<?= (int) $d['id'] ?>"
                                          style="display:inline; margin:0;">
                                        <?= csrfField() ?>
                                        <button type="submit" class="btn btn-xs btn-danger"
                                                onclick="return confirm('Revoke device access? This will invalidate API tokens.')">
                                            Revoke
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size:11px; color:var(--text-tertiary);">No actions</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="devices-empty-search"
         style="display:none; margin-top:1rem; padding:1.5rem; text-align:center;
                color:var(--text-tertiary); font-size:14px;
                background:var(--bg-card); border:1px solid var(--border-color);
                border-radius:var(--radius-md);">
        No devices match your search. <a href="#" id="devices-clear-search" style="color:var(--accent-blue);">Clear search</a>
    </div>

    <script>
    (function () {
        var input    = document.getElementById('devices-search');
        var table    = document.getElementById('devices-table');
        var emptyMsg = document.getElementById('devices-empty-search');
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

        var clear = document.getElementById('devices-clear-search');
        if (clear) clear.addEventListener('click', function (e) {
            e.preventDefault();
            input.value = '';
            filter();
            input.focus();
        });
    })();
    </script>
<?php endif; ?>

<style>
@media (max-width: 1100px) {
    .devices-kpi-grid { grid-template-columns: repeat(2, 1fr) !important; }
}
@media (max-width: 720px) {
    .devices-toolbar { flex-direction: column; align-items: stretch; }
}
</style>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/app.php'; ?>
