<?php
/**
 * Unified Roster Requests page — every roster_changes row for the logged-in
 * pilot, with Type tabs (All / Leave / Correction / Swap / Comment) and a
 * Status filter strip.
 *
 * Variables:
 *   $rows         — filtered roster_changes rows
 *   $typeFilter   — current type filter
 *   $stsFilter    — current status filter
 *   $typeCounts   — counts per type
 *   $statusCounts — counts per status
 */
$typeMeta = [
    'all'           => ['label' => 'All',         'icon' => 'clipboard-list'],
    'leave_request' => ['label' => 'Leave',       'icon' => 'calendar-days'],
    'correction'    => ['label' => 'Corrections', 'icon' => 'pencil'],
    'swap_request'  => ['label' => 'Swaps',       'icon' => 'arrow-path'],
    'comment'       => ['label' => 'Comments',    'icon' => 'chat-bubble'],
];
$statusLabel = [
    'pending'  => 'Submitted',
    'approved' => 'Approved',
    'rejected' => 'Rejected',
    'noted'    => 'Noted',
];
function ar_qs(string $type, string $status): string {
    $parts = [];
    if ($type   !== 'all') $parts[] = 'type='   . urlencode($type);
    if ($status !== 'all') $parts[] = 'status=' . urlencode($status);
    return $parts ? '?' . implode('&', $parts) : '';
}
?>
<style>
.ar-tabs{display:flex;gap:4px;background:var(--bg-secondary);border:1px solid var(--border-color);
    border-radius:10px;padding:4px;margin-bottom:14px;overflow-x:auto;flex-wrap:nowrap;}
.ar-tabs a{padding:8px 14px;font-size:13px;font-weight:600;color:var(--text-secondary);
    text-decoration:none;border-radius:7px;display:inline-flex;align-items:center;gap:8px;
    white-space:nowrap;transition:all .14s;}
.ar-tabs a.is-active{
    background:linear-gradient(135deg,var(--accent-blue),var(--accent-cyan));
    color:#fff;box-shadow:0 1px 3px rgba(59,130,246,.35);
}
.ar-tabs .pill{padding:1px 7px;border-radius:5px;font-size:10px;font-weight:700;
    background:var(--bg-card);color:var(--text-tertiary);}
.ar-tabs a.is-active .pill{background:rgba(255,255,255,.22);color:#fff;}

.ar-status-row{display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap;}
.ar-status-pill{padding:6px 12px;border-radius:8px;font-size:11px;font-weight:600;
    text-decoration:none;color:var(--text-secondary);
    background:var(--bg-card);border:1px solid var(--border-color);transition:all .14s;}
.ar-status-pill:hover{border-color:var(--accent-blue);color:var(--text-primary);}
.ar-status-pill.is-active{background:var(--accent-blue);color:#fff;border-color:var(--accent-blue);}
.ar-status-pill .pc{margin-left:5px;opacity:.85;}

.ar-card{background:var(--bg-card);border:1px solid var(--border-color);border-radius:12px;overflow:hidden;}
.ar-empty{padding:80px 32px;text-align:center;color:var(--text-tertiary);font-size:13px;}
.ar-empty svg{display:block;margin:0 auto 14px;opacity:.5;}
.ar-row{padding:18px 22px;border-bottom:1px solid var(--border-light);}
.ar-row:last-child{border-bottom:none;}
.ar-row-top{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px;}
.ar-type-chip{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;
    padding:3px 9px;border-radius:5px;background:var(--bg-secondary);
    color:var(--text-primary);border:1px solid var(--border-color);
    display:inline-flex;align-items:center;gap:6px;}
.ar-status{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;
    padding:3px 9px;border-radius:5px;}
.ar-status.--pending {background:rgba(245,158,11,.15);color:#fde68a;border:1px solid rgba(245,158,11,.35);}
.ar-status.--approved{background:rgba(16,185,129,.15);color:#a7f3d0;border:1px solid rgba(16,185,129,.35);}
.ar-status.--rejected{background:rgba(239,68,68,.15);color:#fecaca;border:1px solid rgba(239,68,68,.35);}
.ar-status.--noted   {background:rgba(139,92,246,.12);color:#ddd6fe;border:1px solid rgba(139,92,246,.35);}
.ar-when{font-size:11px;color:var(--text-tertiary);}
.ar-msg{font-size:13px;color:var(--text-primary);line-height:1.55;
    background:var(--bg-secondary);border:1px solid var(--border-light);
    border-radius:8px;padding:12px 14px;white-space:pre-wrap;}
.ar-resp{margin-top:10px;padding:12px 14px;border-radius:8px;background:var(--bg-secondary);
    font-size:13px;line-height:1.5;color:var(--text-primary);
    border-left:3px solid var(--text-tertiary);}
.ar-resp.--approved{border-left-color:var(--accent-green);}
.ar-resp.--rejected{border-left-color:var(--accent-red);}
.ar-resp-l{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;
    color:var(--text-tertiary);margin-bottom:4px;}

.ar-quick-actions{display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap;}
.ar-quick-btn{
    display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border-radius:10px;
    background:var(--bg-card);border:1px solid var(--border-color);
    color:var(--text-primary);text-decoration:none;font-size:13px;font-weight:600;
    transition:all .14s;
}
.ar-quick-btn:hover{border-color:var(--accent-blue);background:var(--bg-card-hover);}
</style>

<div class="ar-quick-actions">
    <a class="ar-quick-btn" href="/leave-requests">
        <?= sidebarIcon('calendar-days', 16) ?>
        New leave request
    </a>
    <a class="ar-quick-btn" href="/roster/corrections">
        <?= sidebarIcon('pencil', 16) ?>
        New correction
    </a>
    <a class="ar-quick-btn" href="/my-roster">
        <?= sidebarIcon('calendar', 16) ?>
        Back to roster
    </a>
</div>

<!-- Type tabs -->
<div class="ar-tabs" role="tablist">
    <?php foreach ($typeMeta as $key => $tm): ?>
    <a href="<?= e(ar_qs($key, $stsFilter)) ?>"
       class="<?= $typeFilter === $key ? 'is-active' : '' ?>" role="tab">
        <?= sidebarIcon($tm['icon'], 14) ?>
        <?= e($tm['label']) ?>
        <span class="pill"><?= (int)($typeCounts[$key] ?? 0) ?></span>
    </a>
    <?php endforeach; ?>
</div>

<!-- Status filter row -->
<div class="ar-status-row">
    <?php
    $statusOpts = ['all' => 'All', 'pending' => 'Submitted', 'approved' => 'Approved',
                   'rejected' => 'Rejected', 'noted' => 'Noted'];
    foreach ($statusOpts as $key => $label):
    ?>
    <a class="ar-status-pill <?= $stsFilter === $key ? 'is-active' : '' ?>"
       href="<?= e(ar_qs($typeFilter, $key)) ?>">
        <?= e($label) ?><span class="pc">· <?= (int)($statusCounts[$key] ?? 0) ?></span>
    </a>
    <?php endforeach; ?>
</div>

<div class="ar-card">
    <?php if (empty($rows)): ?>
        <div class="ar-empty">
            <?= sidebarIcon('clipboard-list', 32) ?>
            <?php if ($typeFilter === 'all' && $stsFilter === 'all'): ?>
                You haven't submitted any roster requests yet.<br>
                <span style="font-size:12px;">
                    Open <a href="/my-roster" style="color:var(--accent-cyan);text-decoration:none;">My Roster</a>
                    and click any duty to request leave or a correction.
                </span>
            <?php else: ?>
                No requests match this filter.
                <a href="/my-roster/requests" style="color:var(--accent-cyan);text-decoration:none;">Clear filters</a>
            <?php endif; ?>
        </div>
    <?php else: foreach ($rows as $r):
        $tm = $typeMeta[$r['change_type']] ?? ['label' => ucfirst($r['change_type']), 'icon' => 'document-text'];
        $sLabel = $statusLabel[$r['status']] ?? ucfirst($r['status']);
    ?>
    <div class="ar-row">
        <div class="ar-row-top">
            <span class="ar-type-chip">
                <?= sidebarIcon($tm['icon'], 12) ?>
                <?= e($tm['label']) ?>
            </span>
            <span class="ar-status --<?= e($r['status']) ?>"><?= e($sLabel) ?></span>
            <span class="ar-when">
                Submitted <?= e(date('d M Y · H:i', strtotime($r['created_at']))) ?>
                <?php if (!empty($r['period_name'])): ?>
                    · for <?= e($r['period_name']) ?>
                <?php endif; ?>
                <?php if (!empty($r['responded_at'])): ?>
                    · responded <?= e(date('d M Y', strtotime($r['responded_at']))) ?>
                    <?php if (!empty($r['responded_by_name'])): ?>
                        by <?= e($r['responded_by_name']) ?>
                    <?php endif; ?>
                <?php endif; ?>
            </span>
        </div>
        <div class="ar-msg"><?= e($r['message']) ?></div>
        <?php if (!empty($r['response'])): ?>
        <div class="ar-resp --<?= e($r['status']) ?>">
            <div class="ar-resp-l">Scheduling response</div>
            <?= e($r['response']) ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; endif; ?>
</div>
