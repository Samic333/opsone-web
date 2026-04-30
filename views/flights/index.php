<?php
/**
 * Flights index — scheduler / operations board.
 *
 * Layout (Phase K redesign):
 *   1. KPI mini-cards (Total / In Progress / Scheduled / Completed) for the active window
 *   2. Toolbar — date range pickers + client-side search + Refresh + + New Flight
 *   3. Modern flight board: route as IATA chips, STD/STA monospace,
 *      A/C reg chip, Captain / FO with initials avatars, status badge,
 *      flight-bag count chip, "Open" button on the right
 *
 * Controller passes $flights (already filtered to date range), $from, $to.
 * `ob_start()` lives in the controller — this template only emits markup.
 */

// Bucket flights by status for the KPI strip.
$counts = ['total' => count($flights), 'in_progress' => 0, 'scheduled' => 0, 'completed' => 0];
foreach ($flights as $f) {
    $s = strtolower((string)($f['status'] ?? ''));
    if ($s === 'in_progress' || $s === 'in-progress' || $s === 'airborne' || $s === 'departed') $counts['in_progress']++;
    elseif ($s === 'scheduled' || $s === 'planned' || $s === 'pending') $counts['scheduled']++;
    elseif ($s === 'completed' || $s === 'arrived' || $s === 'closed' || $s === 'archived') $counts['completed']++;
}

// Stable initials helper for avatars.
$__initials = static function (?string $name): string {
    $name = trim((string) $name);
    if ($name === '') return '–';
    $parts = preg_split('/\s+/', $name);
    $i = '';
    foreach ($parts as $p) {
        if ($p !== '' && strlen($i) < 2) $i .= mb_substr($p, 0, 1);
    }
    return strtoupper($i ?: 'U');
};

// Tone palette for the avatar circles — hashed by name so it's stable.
$__avatarTones = [
    'var(--accent-blue)',
    'var(--accent-cyan)',
    'var(--accent-purple)',
    'var(--accent-green)',
    'var(--accent-yellow)',
];
$__avatarTone = static function (?string $name) use ($__avatarTones): string {
    $name = trim((string) $name);
    if ($name === '') return 'var(--text-tertiary)';
    return $__avatarTones[abs(crc32($name)) % count($__avatarTones)];
};
?>

<!-- ─── 1. KPI mini cards ────────────────────────────────────────────── -->
<div class="flights-kpi-grid"
     style="display:grid; grid-template-columns:repeat(4, 1fr); gap:0.85rem; margin-bottom:1.25rem;">
    <?php
    $kpiCards = [
        ['label' => 'In Window',    'value' => $counts['total'],       'tone' => 'var(--accent-blue)'],
        ['label' => 'In Progress',  'value' => $counts['in_progress'], 'tone' => 'var(--status-info)'],
        ['label' => 'Scheduled',    'value' => $counts['scheduled'],   'tone' => 'var(--status-advisory)'],
        ['label' => 'Completed',    'value' => $counts['completed'],   'tone' => 'var(--status-cleared)'],
    ];
    foreach ($kpiCards as $kpi):
    ?>
    <div style="display:flex; flex-direction:column; gap:6px;
                padding:14px 16px;
                background:var(--bg-card);
                border:1px solid var(--border-color);
                border-left:3px solid <?= $kpi['tone'] ?>;
                border-radius:var(--radius-md);">
        <span style="font-size:11px; font-weight:700; text-transform:uppercase;
                     letter-spacing:.06em; color:var(--text-tertiary);">
            <?= e($kpi['label']) ?>
        </span>
        <span style="font-size:1.6rem; font-weight:700; color:<?= $kpi['tone'] ?>;
                     letter-spacing:-0.02em; line-height:1.1;">
            <?= (int) $kpi['value'] ?>
        </span>
    </div>
    <?php endforeach; ?>
</div>

<!-- ─── 2. Toolbar ──────────────────────────────────────────────────── -->
<div class="flights-toolbar"
     style="display:flex; flex-wrap:wrap; align-items:center; gap:12px;
            margin-bottom:1rem;">

    <a href="/flights/create" class="btn btn-primary">+ New Flight</a>

    <form method="GET" action="/flights"
          style="display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin-left:auto;">
        <label style="font-size:12px; color:var(--text-tertiary); display:inline-flex; align-items:center; gap:6px;">
            From
            <input type="date" name="from" value="<?= e($_GET['from'] ?? date('Y-m-d')) ?>"
                   class="form-control" style="width:150px; padding:6px 8px;">
        </label>
        <label style="font-size:12px; color:var(--text-tertiary); display:inline-flex; align-items:center; gap:6px;">
            To
            <input type="date" name="to"   value="<?= e($_GET['to']   ?? date('Y-m-d', strtotime('+14 days'))) ?>"
                   class="form-control" style="width:150px; padding:6px 8px;">
        </label>
        <button class="btn btn-outline btn-sm" type="submit">Refresh</button>
    </form>

    <div style="position:relative; min-width:240px; flex-basis:240px;">
        <span style="position:absolute; left:10px; top:50%; transform:translateY(-50%);
                     color:var(--text-tertiary); display:inline-flex;">
            <?= sidebarIcon('chevron-right', 14) ?>
        </span>
        <input id="flights-search" type="search" autocomplete="off"
               placeholder="Search flight # or route…"
               style="width:100%; padding:8px 12px 8px 32px;
                      background:var(--bg-input); color:var(--text-primary);
                      border:1px solid var(--border-color); border-radius:var(--radius-sm);
                      font-size:13px; font-family:inherit; outline:none;">
    </div>
</div>

<!-- ─── 3. Flight board ─────────────────────────────────────────────── -->
<?php if (empty($flights)): ?>
    <div class="card">
        <div class="empty-state" style="padding:40px 0;">
            <div class="icon"><?= sidebarIcon('paper-airplane', 32) ?></div>
            <h3>No flights</h3>
            <p>Create a flight to start assigning crew and uploading briefing packages.</p>
            <a href="/flights/create" class="btn btn-primary" style="margin-top:0.75rem;">
                + New Flight
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card" style="padding:0; overflow:hidden;">
        <div class="table-wrap" style="margin:0;">
            <table id="flights-table" style="margin:0;">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Flight</th>
                        <th>Route</th>
                        <th>STD / STA</th>
                        <th>Aircraft</th>
                        <th>Captain</th>
                        <th>F/O</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:center;">Bag</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($flights as $f):
                    $haystack = strtolower(
                        ($f['flight_number'] ?? '') . ' ' .
                        ($f['departure'] ?? '') . ' ' .
                        ($f['arrival'] ?? '') . ' ' .
                        ($f['captain_name'] ?? '') . ' ' .
                        ($f['fo_name'] ?? '') . ' ' .
                        ($f['reg'] ?? '')
                    );
                ?>
                    <tr data-search="<?= e($haystack) ?>">
                        <td style="white-space:nowrap;">
                            <span style="font-size:12px; color:var(--text-secondary); font-variant-numeric:tabular-nums;">
                                <?= e($f['flight_date']) ?>
                            </span>
                        </td>
                        <td>
                            <strong style="color:var(--text-primary); font-variant-numeric:tabular-nums;
                                           letter-spacing:0.02em;">
                                <?= e($f['flight_number']) ?>
                            </strong>
                        </td>
                        <td style="white-space:nowrap;">
                            <span style="display:inline-flex; align-items:center; gap:6px;
                                         font-size:12px; color:var(--text-primary);
                                         font-variant-numeric:tabular-nums;">
                                <span style="background:var(--bg-input); padding:2px 7px;
                                             border-radius:4px; font-weight:600;
                                             color:var(--accent-cyan);">
                                    <?= e($f['departure']) ?>
                                </span>
                                <span style="color:var(--text-tertiary);">→</span>
                                <span style="background:var(--bg-input); padding:2px 7px;
                                             border-radius:4px; font-weight:600;
                                             color:var(--accent-cyan);">
                                    <?= e($f['arrival']) ?>
                                </span>
                            </span>
                        </td>
                        <td style="white-space:nowrap;">
                            <span style="font-size:11px; font-family:ui-monospace,monospace;
                                         color:var(--text-secondary);">
                                <?= e($f['std'] ?? '—') ?> / <?= e($f['sta'] ?? '—') ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($f['reg'])): ?>
                                <code style="font-size:11px; color:var(--text-secondary);
                                             background:var(--bg-input);
                                             padding:2px 6px; border-radius:4px;">
                                    <?= e($f['reg']) ?>
                                </code>
                            <?php else: ?>
                                <span style="color:var(--text-tertiary); font-size:12px;">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- Captain -->
                        <td>
                            <?php if (!empty($f['captain_name'])):
                                $tone = $__avatarTone($f['captain_name']);
                            ?>
                                <div style="display:flex; align-items:center; gap:8px; min-width:0;">
                                    <span style="width:24px; height:24px; border-radius:50%;
                                                 display:inline-flex; align-items:center; justify-content:center;
                                                 background:<?= $tone ?>; color:#fff;
                                                 font-size:9px; font-weight:700; flex-shrink:0;">
                                        <?= e($__initials($f['captain_name'])) ?>
                                    </span>
                                    <span style="font-size:12px; color:var(--text-primary);
                                                 overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
                                                 max-width:140px;">
                                        <?= e($f['captain_name']) ?>
                                    </span>
                                </div>
                            <?php else: ?>
                                <span style="color:var(--text-tertiary); font-size:12px;">Unassigned</span>
                            <?php endif; ?>
                        </td>

                        <!-- First officer -->
                        <td>
                            <?php if (!empty($f['fo_name'])):
                                $tone = $__avatarTone($f['fo_name']);
                            ?>
                                <div style="display:flex; align-items:center; gap:8px; min-width:0;">
                                    <span style="width:24px; height:24px; border-radius:50%;
                                                 display:inline-flex; align-items:center; justify-content:center;
                                                 background:<?= $tone ?>; color:#fff;
                                                 font-size:9px; font-weight:700; flex-shrink:0;">
                                        <?= e($__initials($f['fo_name'])) ?>
                                    </span>
                                    <span style="font-size:12px; color:var(--text-primary);
                                                 overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
                                                 max-width:140px;">
                                        <?= e($f['fo_name']) ?>
                                    </span>
                                </div>
                            <?php else: ?>
                                <span style="color:var(--text-tertiary); font-size:12px;">Unassigned</span>
                            <?php endif; ?>
                        </td>

                        <td style="text-align:center;"><?= statusBadge($f['status']) ?></td>

                        <td style="text-align:center;">
                            <?php $bag = (int) ($f['bag_count'] ?? 0); ?>
                            <?php if ($bag > 0): ?>
                                <span style="display:inline-flex; align-items:center; gap:4px;
                                             font-size:11px; font-weight:600;
                                             padding:2px 7px; border-radius:10px;
                                             background:rgba(139,92,246,0.10);
                                             color:var(--accent-purple);">
                                    <?= sidebarIcon('folder-open', 11) ?>
                                    <?= $bag ?>
                                </span>
                            <?php else: ?>
                                <span style="color:var(--text-tertiary); font-size:12px;">—</span>
                            <?php endif; ?>
                        </td>

                        <td style="text-align:right;">
                            <a href="/flights/<?= (int) $f['id'] ?>" class="btn btn-xs btn-outline">Open</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="flights-empty-search"
         style="display:none; margin-top:1rem; padding:1.5rem; text-align:center;
                color:var(--text-tertiary); font-size:14px;
                background:var(--bg-card); border:1px solid var(--border-color);
                border-radius:var(--radius-md);">
        No flights match your search. <a href="#" id="flights-clear-search" style="color:var(--accent-blue);">Clear search</a>
    </div>

    <script>
    (function () {
        var input    = document.getElementById('flights-search');
        var table    = document.getElementById('flights-table');
        var emptyMsg = document.getElementById('flights-empty-search');
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

        var clear = document.getElementById('flights-clear-search');
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
    .flights-kpi-grid { grid-template-columns: repeat(2, 1fr) !important; }
}
@media (max-width: 720px) {
    .flights-toolbar { flex-direction: column; align-items: stretch; }
    .flights-toolbar form { margin-left: 0 !important; }
}
</style>
