<?php
/**
 * OpsOne — Safety Team Full Report View
 * Variables: $report, $publicThreads, $internalNotes, $statusHistory, $attachments, $safetyUsers
 */
$pageTitle    = $report['reference_no'] ?? 'Safety Report';
$pageSubtitle = 'Safety Team Investigation View';

// Status colour helper
function tdStatusColor(string $s): string {
    return match($s) {
        'submitted'          => '#3b82f6',
        'under_review'       => '#f59e0b',
        'investigation'      => '#ef4444',
        'action_in_progress' => '#8b5cf6',
        'closed'             => '#10b981',
        'reopened'           => '#f59e0b',
        default              => '#6b7280',
    };
}
$sc = tdStatusColor($report['status'] ?? '');
$activeTab = $_GET['tab'] ?? 'overview';
?>

<div style="margin-bottom:16px;">
    <a href="/safety" class="btn btn-ghost btn-sm">← Safety Queue</a>
</div>

<!-- Report Header -->
<div style="display:flex; align-items:center; gap:12px; margin-bottom:0; flex-wrap:wrap;">
    <h3 style="margin:0; font-size:20px; font-family:monospace; font-weight:700;"><?= e($report['reference_no'] ?? '—') ?></h3>
    <span class="status-badge" style="--badge-color:<?= $sc ?>; font-size:13px; padding:5px 12px;">
        <?= ucfirst(str_replace('_', ' ', $report['status'] ?? '')) ?>
    </span>
    <span style="font-size:12px; font-weight:600; color:var(--text-secondary);">
        <?= e(ucwords(str_replace('_', ' ', $report['report_type'] ?? ''))) ?>
    </span>
    <span class="text-sm text-muted">
        Assigned to:
        <strong><?= e($report['assigned_to_name'] ?? 'Unassigned') ?></strong>
    </span>
</div>

<!-- Tab Bar -->
<div class="nav-bar" style="margin:18px 0 20px; border-bottom:1px solid var(--border); padding-bottom:0;">
    <?php
    $tabs = [
        'overview'    => 'Overview',
        'discussion'  => 'Discussion',
        'internal'    => 'Internal Notes',
        'history'     => 'History',
        'attachments' => 'Attachments',
    ];
    foreach ($tabs as $slug => $label):
    ?>
    <a href="?tab=<?= $slug ?>"
       class="btn btn-sm <?= $activeTab === $slug ? 'btn-primary' : 'btn-ghost' ?>"
       style="border-radius:6px 6px 0 0; margin-bottom:-1px;">
        <?= $label ?>
        <?php if ($slug === 'internal' && !empty($internalNotes)): ?>
            <span style="background:#ef4444;color:#fff;border-radius:8px;padding:0 5px;font-size:10px;font-weight:700;margin-left:4px;"><?= count($internalNotes) ?></span>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- ══════════════════════════════════════
     OVERVIEW TAB
     ══════════════════════════════════════ -->
<?php if ($activeTab === 'overview'): ?>
<div style="display:grid; grid-template-columns:2fr 1fr; gap:24px; align-items:start;">

    <!-- Left: Full Report Fields -->
    <div style="display:flex; flex-direction:column; gap:20px;">
        <div class="card">
            <h4 style="margin:0 0 16px; font-size:16px; font-weight:700;"><?= e($report['title'] ?? '') ?></h4>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px 20px; margin-bottom:18px;">
                <?php
                $fields = [
                    'Event Date'     => !empty($report['event_date'])   ? date('d M Y', strtotime($report['event_date'])) : '—',
                    'Event Time UTC' => e($report['event_time_utc']     ?? '—'),
                    'Location'       => e($report['location']           ?? '—'),
                    'ICAO'           => e($report['icao_code']          ?? '—'),
                    'Occurrence Type'=> e(ucfirst($report['occurrence_type'] ?? '—')),
                    'Event Type'     => e($report['event_type']         ?? '—'),
                    'Reporter'       => !empty($report['is_anonymous']) ? '🔒 Anonymous' : e($report['reporter_name'] ?? '—'),
                    'Reporter Role'  => e($report['reporter_position']  ?? '—'),
                    'Initial Risk'   => e($report['initial_risk']       ?? '—') . '/5',
                ];
                foreach ($fields as $lbl => $val):
                ?>
                <div>
                    <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:.05em; margin-bottom:2px;"><?= $lbl ?></div>
                    <div class="text-sm" style="font-weight:600;"><?= $val ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <h4 style="margin:0 0 8px; font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted);">Description</h4>
            <div style="background:var(--bg-body); padding:14px 16px; border-radius:var(--radius-md); border:1px solid var(--border); line-height:1.65; white-space:pre-wrap; font-size:14px;"><?= e($report['description'] ?? '') ?></div>
        </div>

        <!-- Aircraft / Crew sections if present -->
        <?php if (!empty($report['aircraft_reg']) || !empty($report['call_sign'])): ?>
        <div class="card">
            <h4 style="margin:0 0 14px; font-size:15px;">✈️ Aircraft Information</h4>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px 16px;">
                <?php foreach ([
                    'Registration' => $report['aircraft_reg'] ?? '—',
                    'Call Sign'    => $report['call_sign']    ?? '—',
                    'Phase'        => $report['phase_of_flight'] ?? '—',
                ] as $lbl => $val): ?>
                <div>
                    <div class="text-xs text-muted" style="text-transform:uppercase; letter-spacing:.05em; margin-bottom:2px;"><?= $lbl ?></div>
                    <div class="text-sm" style="font-weight:600;"><?= e($val) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right: Control Panel -->
    <div style="display:flex; flex-direction:column; gap:16px;">

        <!-- Status Change -->
        <div class="card">
            <h4 style="margin:0 0 14px; font-size:15px;">Update Status</h4>
            <form method="POST" action="/safety/team/report/<?= (int)$report['id'] ?>/status">
                <?= csrfField() ?>
                <div class="form-group">
                    <label>Change Status To</label>
                    <select name="status" class="form-control">
                        <?php foreach ([
                            'submitted' => 'Submitted', 'under_review' => 'Under Review',
                            'investigation' => 'Investigation', 'action_in_progress' => 'Action In Progress',
                            'closed' => 'Closed', 'reopened' => 'Reopened'
                        ] as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= ($report['status'] ?? '') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Comment (optional)</label>
                    <textarea name="comment" class="form-control" rows="2" placeholder="Reason for status change…"></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-sm" style="width:100%;">Update Status</button>
            </form>
        </div>

        <!-- Assignment -->
        <div class="card">
            <h4 style="margin:0 0 14px; font-size:15px;">Assignment</h4>
            <form method="POST" action="/safety/team/report/<?= (int)$report['id'] ?>/assign">
                <?= csrfField() ?>
                <div class="form-group">
                    <select name="assigned_to" class="form-control">
                        <option value="">— Unassigned —</option>
                        <?php foreach ($safetyUsers as $u): ?>
                        <option value="<?= (int)$u['id'] ?>" <?= (int)($report['assigned_to'] ?? 0) === (int)$u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-outline btn-sm" style="width:100%;">Assign</button>
            </form>
        </div>

        <!-- Severity Classification -->
        <div class="card">
            <h4 style="margin:0 0 14px; font-size:15px;">Severity Classification</h4>
            <form method="POST" action="/safety/team/report/<?= (int)$report['id'] ?>/severity">
                <?= csrfField() ?>
                <div class="form-group">
                    <select name="final_severity" class="form-control">
                        <option value="">— Not Classified —</option>
                        <?php foreach (['negligible','minor','moderate','significant','critical'] as $sev): ?>
                        <option value="<?= $sev ?>" <?= ($report['final_severity'] ?? '') === $sev ? 'selected' : '' ?>><?= ucfirst($sev) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-outline btn-sm" style="width:100%;">Save Severity</button>
            </form>
        </div>

        <!-- Publication Link -->
        <div class="card" style="padding:14px 16px;">
            <a href="/safety/publications/create?from_report=<?= (int)$report['id'] ?>" class="btn btn-ghost btn-sm" style="width:100%; display:block; text-align:center;">
                📢 Create Publication from Report
            </a>
        </div>

    </div>
</div>

<?php endif; ?>

<!-- ══════════════════════════════════════
     DISCUSSION TAB (Public)
     ══════════════════════════════════════ -->
<?php if ($activeTab === 'discussion'): ?>
<div class="card">
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:16px; padding:10px 14px; background:rgba(59,130,246,0.06); border-radius:var(--radius-sm); border:1px solid rgba(59,130,246,0.2);">
        <span>👤</span>
        <p class="text-sm" style="margin:0; color:var(--text-secondary);">This thread is <strong>visible to the reporter</strong>. Keep content appropriate for the reporter to read.</p>
    </div>

    <div style="display:flex; flex-direction:column; gap:12px; margin-bottom:20px;">
        <?php if (empty($publicThreads)): ?>
            <div style="padding:24px 0; text-align:center; color:var(--text-muted);">
                <div style="font-size:28px; margin-bottom:8px;">💬</div>
                <p class="text-sm">No messages yet in the public thread.</p>
            </div>
        <?php else: ?>
            <?php foreach ($publicThreads as $msg): ?>
            <div style="display:flex; gap:10px;">
                <div style="flex-shrink:0; width:32px; height:32px; border-radius:50%; background:var(--bg-secondary); display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700;">
                    <?= strtoupper(substr($msg['author_name'] ?? 'U', 0, 1)) ?>
                </div>
                <div style="flex:1;">
                    <div style="font-size:11px; color:var(--text-muted); margin-bottom:3px;">
                        <?= e($msg['author_name'] ?? '—') ?> · <?= !empty($msg['created_at']) ? date('d M Y, H:i', strtotime($msg['created_at'])) : '' ?>
                    </div>
                    <div style="background:var(--bg-secondary); padding:10px 14px; border-radius:4px 12px 12px 12px; font-size:14px; line-height:1.5; white-space:pre-wrap;">
                        <?= e($msg['body'] ?? '') ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <form method="POST" action="/safety/team/report/<?= (int)$report['id'] ?>/thread">
        <?= csrfField() ?>
        <input type="hidden" name="is_internal" value="0">
        <div class="form-group">
            <label>Reply to Reporter</label>
            <textarea name="body" class="form-control" rows="3" placeholder="Message visible to the reporter…"></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Send to Reporter</button>
    </form>
</div>
<?php endif; ?>

<!-- ══════════════════════════════════════
     INTERNAL NOTES TAB
     ══════════════════════════════════════ -->
<?php if ($activeTab === 'internal'): ?>
<div class="card">
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:16px; padding:12px 14px; background:rgba(239,68,68,0.08); border-radius:var(--radius-sm); border:1px solid rgba(239,68,68,0.25);">
        <span style="font-size:18px;">⚠️</span>
        <p class="text-sm" style="margin:0; color:#b91c1c; font-weight:600;">Internal Only — Reporter cannot see these notes.</p>
    </div>

    <div style="display:flex; flex-direction:column; gap:12px; margin-bottom:20px;">
        <?php if (empty($internalNotes)): ?>
            <div style="padding:24px 0; text-align:center; color:var(--text-muted);">
                <div style="font-size:28px; margin-bottom:8px;">🔒</div>
                <p class="text-sm">No internal notes yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($internalNotes as $msg): ?>
            <div style="display:flex; gap:10px;">
                <div style="flex-shrink:0; width:32px; height:32px; border-radius:50%; background:rgba(239,68,68,0.12); display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; color:#b91c1c;">
                    <?= strtoupper(substr($msg['author_name'] ?? 'U', 0, 1)) ?>
                </div>
                <div style="flex:1;">
                    <div style="font-size:11px; color:var(--text-muted); margin-bottom:3px;">
                        <?= e($msg['author_name'] ?? '—') ?> · <?= !empty($msg['created_at']) ? date('d M Y, H:i', strtotime($msg['created_at'])) : '' ?>
                    </div>
                    <div style="background:rgba(239,68,68,0.06); border:1px solid rgba(239,68,68,0.15); padding:10px 14px; border-radius:4px 12px 12px 12px; font-size:14px; line-height:1.5; white-space:pre-wrap;">
                        <?= e($msg['body'] ?? '') ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <form method="POST" action="/safety/team/report/<?= (int)$report['id'] ?>/thread">
        <?= csrfField() ?>
        <input type="hidden" name="is_internal" value="1">
        <div class="form-group">
            <label>Add Internal Note</label>
            <textarea name="body" class="form-control" rows="3" placeholder="Investigation notes, findings, or team comments — not visible to the reporter…"></textarea>
        </div>
        <button type="submit" class="btn btn-sm" style="background:#ef4444; color:#fff; border:none; cursor:pointer; padding:8px 16px; border-radius:var(--radius-sm);">Add Internal Note</button>
    </form>
</div>
<?php endif; ?>

<!-- ══════════════════════════════════════
     HISTORY TAB
     ══════════════════════════════════════ -->
<?php if ($activeTab === 'history'): ?>
<div class="card">
    <h4 style="margin:0 0 20px; font-size:15px;">Status History Timeline</h4>

    <div style="position:relative; margin-left:12px; border-left:2px solid var(--border); padding-left:24px; display:flex; flex-direction:column; gap:20px;">

        <!-- Initial -->
        <div style="position:relative;">
            <div style="position:absolute; left:-30px; top:1px; width:14px; height:14px; border-radius:50%; background:var(--accent-blue); outline:4px solid var(--bg-card);"></div>
            <div class="text-xs text-muted" style="margin-bottom:3px;"><?= !empty($report['created_at']) ? date('d M Y, H:i', strtotime($report['created_at'])) : '' ?></div>
            <div class="text-sm" style="font-weight:600;">Report Submitted</div>
        </div>

        <?php if (!empty($statusHistory)): ?>
            <?php foreach ($statusHistory as $sh): ?>
            <div style="position:relative;">
                <?php $dotColor = tdStatusColor($sh['new_status'] ?? ''); ?>
                <div style="position:absolute; left:-30px; top:1px; width:14px; height:14px; border-radius:50%; background:<?= $dotColor ?>; outline:4px solid var(--bg-card);"></div>
                <div class="text-xs text-muted" style="margin-bottom:3px;">
                    <?= !empty($sh['created_at']) ? date('d M Y, H:i', strtotime($sh['created_at'])) : '' ?>
                    <?php if (!empty($sh['user_name'])): ?> · <?= e($sh['user_name']) ?><?php endif; ?>
                </div>
                <div class="text-sm" style="font-weight:600; display:flex; align-items:center; gap:8px;">
                    <span style="color:var(--text-muted);"><?= e(ucfirst(str_replace('_', ' ', $sh['old_status'] ?? ''))) ?></span>
                    <span style="color:var(--text-muted);">→</span>
                    <span style="color:<?= $dotColor ?>;"><?= e(ucfirst(str_replace('_', ' ', $sh['new_status'] ?? ''))) ?></span>
                </div>
                <?php if (!empty($sh['comment'])): ?>
                    <div style="margin-top:6px; padding:8px 12px; background:var(--bg-body); border-radius:var(--radius-sm); border:1px solid var(--border); font-size:13px; color:var(--text-secondary);"><?= e($sh['comment']) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="position:relative;">
                <div style="position:absolute; left:-30px; top:1px; width:14px; height:14px; border-radius:50%; background:var(--border); outline:4px solid var(--bg-card);"></div>
                <p class="text-sm text-muted">No status changes recorded yet.</p>
            </div>
        <?php endif; ?>

    </div>
</div>
<?php endif; ?>

<!-- ══════════════════════════════════════
     ATTACHMENTS TAB
     ══════════════════════════════════════ -->
<?php if ($activeTab === 'attachments'): ?>
<div style="display:flex; flex-direction:column; gap:20px;">

    <?php if (!empty($attachments)): ?>
    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:14px;">
        <?php foreach ($attachments as $att): ?>
        <?php
        $isImage = str_starts_with($att['mime_type'] ?? '', 'image/');
        $isPdf   = str_contains($att['mime_type'] ?? '', 'pdf');
        $icon    = $isImage ? '🖼️' : ($isPdf ? '📄' : '🎥');
        ?>
        <div class="card" style="padding:16px; display:flex; flex-direction:column; gap:8px; align-items:center; text-align:center;">
            <div style="font-size:36px;"><?= $icon ?></div>
            <div class="text-sm" style="font-weight:600; word-break:break-all;"><?= e($att['original_name'] ?? $att['filename'] ?? 'File') ?></div>
            <div class="text-xs text-muted">
                <?= isset($att['file_size']) ? round($att['file_size'] / 1024, 1) . ' KB' : '' ?>
                <?php if (!empty($att['uploader_name'])): ?> · <?= e($att['uploader_name']) ?><?php endif; ?>
            </div>
            <div class="text-xs text-muted"><?= !empty($att['created_at']) ? date('d M Y', strtotime($att['created_at'])) : '' ?></div>
            <a href="/safety/attachments/<?= (int)$att['id'] ?>/download" class="btn btn-outline btn-xs" style="width:100%;">Download</a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <div class="card">
            <div class="empty-state" style="padding:32px 0;">
                <div class="icon">📎</div>
                <p>No attachments uploaded for this report.</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Upload new -->
    <div class="card">
        <h4 style="margin:0 0 14px; font-size:15px;">Upload New Attachment</h4>
        <form method="POST" action="/safety/team/report/<?= (int)$report['id'] ?>/attachments" enctype="multipart/form-data">
            <?= csrfField() ?>
            <div class="form-group">
                <input type="file" name="attachments[]" class="form-control" multiple
                       accept="image/*,application/pdf,video/mp4,video/quicktime">
                <p class="text-xs text-muted" style="margin-top:6px;">Max 25MB per file. Images, PDFs, videos accepted.</p>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Upload</button>
        </form>
    </div>
</div>
<?php endif; ?>
