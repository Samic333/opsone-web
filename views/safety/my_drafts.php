<?php
/**
 * OpsOne — My Draft Reports
 * Variables: $drafts (array)
 */
$pageTitle    = 'My Drafts';
$pageSubtitle = 'Reports saved but not yet submitted';

$headerAction = '<a href="/safety" class="btn btn-primary btn-sm">＋ New Report</a>';
?>

<!-- Warning Banner -->
<div style="background:rgba(245,158,11,0.1); border:1px solid rgba(245,158,11,0.35); border-radius:var(--radius-md); padding:12px 16px; margin-bottom:20px; display:flex; align-items:center; gap:10px;">
    <span style="font-size:16px;">⚠️</span>
    <p class="text-sm" style="margin:0; color:var(--text-secondary);">
        <strong>Drafts are not submitted to the safety team until you click Submit.</strong>
        They are only visible to you and are not investigated until submitted.
    </p>
</div>

<?php if (empty($drafts)): ?>
    <div class="card">
        <div class="empty-state" style="padding:48px 0;">
            <div class="icon">📝</div>
            <h3>No Saved Drafts</h3>
            <p>You have no reports in progress. Start a new report when you're ready.</p>
            <a href="/safety" class="btn btn-primary btn-sm">Start a Report</a>
        </div>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Report Type</th>
                    <th>Event Date</th>
                    <th>Title</th>
                    <th>Last Saved</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($drafts as $draft): ?>
                <tr>
                    <td style="font-family:monospace; font-size:12px; color:var(--text-muted);">
                        <?= !empty($draft['reference_no']) ? e($draft['reference_no']) : '<em class="text-muted">Draft</em>' ?>
                    </td>
                    <td style="font-size:12px; font-weight:600; color:var(--text-secondary);">
                        <?= e(ucwords(str_replace('_', ' ', $draft['report_type'] ?? '—'))) ?>
                    </td>
                    <td class="text-sm text-muted">
                        <?= !empty($draft['event_date']) ? date('d M Y', strtotime($draft['event_date'])) : '—' ?>
                    </td>
                    <td style="font-weight:500;">
                        <?= !empty($draft['title']) ? e($draft['title']) : '<em class="text-muted">Untitled</em>' ?>
                    </td>
                    <td class="text-sm text-muted">
                        <?= !empty($draft['updated_at']) ? date('d M Y, H:i', strtotime($draft['updated_at'])) : '—' ?>
                    </td>
                    <td style="text-align:right;">
                        <div class="btn-group">
                            <a href="/safety/report/edit/<?= (int)$draft['id'] ?>" class="btn btn-primary btn-xs">
                                Continue →
                            </a>
                            <form method="POST" action="/safety/report/delete/<?= (int)$draft['id'] ?>"
                                  style="display:inline;"
                                  onsubmit="return confirm('Delete this draft? This cannot be undone.')">
                                <?= csrfField() ?>
                                <button type="submit" class="btn btn-xs" style="background:none; border:1px solid #ef4444; color:#ef4444;">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
