<?php
$pageTitle = 'Safety Report: ' . $report['reference_no'];
$pageSubtitle = "Filed on " . date('d M Y, H:i', strtotime($report['created_at']));
?>

<div style="margin-bottom:20px;">
    <a href="/safety" class="btn btn-ghost">← Back to Inbox</a>
</div>

<div style="display:grid; grid-template-columns: 2fr 1fr; gap:24px; align-items:start;">
    
    <!-- Left Column: Report Details & Timeline -->
    <div style="display:flex; flex-direction:column; gap:24px;">
        <div class="card">
            <h3 style="margin-top:0; font-size:18px; color:var(--text-primary);"><?= e($report['title']) ?></h3>
            
            <div style="display:flex; gap:16px; margin-bottom:20px; flex-wrap:wrap;">
                <div class="text-sm">
                    <strong>Type:</strong> <span style="color:var(--accent-blue);"><?= e($report['report_type']) ?></span>
                </div>
                <div class="text-sm">
                    <strong>Date of Event:</strong> <?= $report['event_date'] ? date('d M Y', strtotime($report['event_date'])) : 'Not Specified' ?>
                </div>
                <div class="text-sm">
                    <strong>Reporter:</strong> 
                    <?php if ($report['is_anonymous']): ?>
                        <span style="color:var(--text-muted); font-style:italic;">🔒 Anonymous Submitter</span>
                    <?php else: ?>
                        <?= e($report['reporter_name']) ?>
                        <?php if ($report['reporter_employee_id']): ?>
                            <span class="text-muted">(<?= e($report['reporter_employee_id']) ?>)</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <h4 style="margin:0 0 8px; font-size:14px; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-muted);">Detailed Description</h4>
            <div style="background:var(--bg-body); padding:16px; border-radius:var(--radius-md); border:1px solid var(--border); color:var(--text-primary); line-height:1.6; white-space:pre-wrap; font-size:14px;"><?= e($report['description']) ?></div>
        </div>

        <div class="card">
            <h4 style="margin:0 0 16px; font-size:15px;">Investigation Timeline</h4>
            
            <div style="position:relative; margin-left:16px; border-left:2px solid var(--border); padding-left:24px; display:flex; flex-direction:column; gap:20px;">
                <!-- Initial Submission -->
                <div style="position:relative;">
                    <div style="position:absolute; left:-32px; top:-2px; width:14px; height:14px; border-radius:50%; background:var(--accent-blue); outline:4px solid var(--bg-card);"></div>
                    <div class="text-xs text-muted" style="margin-bottom:4px;"><?= date('d M Y, H:i', strtotime($report['created_at'])) ?></div>
                    <div class="text-sm" style="font-weight:500;">Report Filed securely</div>
                </div>

                <!-- Updates -->
                <?php foreach ($updates as $upd): ?>
                <div style="position:relative;">
                    <div style="position:absolute; left:-32px; top:-2px; width:14px; height:14px; border-radius:50%; background:var(--border); outline:4px solid var(--bg-card);"></div>
                    <div class="text-xs text-muted" style="margin-bottom:4px;"><?= date('d M Y, H:i', strtotime($upd['created_at'])) ?> &middot; Updated by <?= e($upd['user_name']) ?></div>
                    <div class="text-sm">
                        <?php if ($upd['status_change']): ?>
                            <span class="status-badge" style="margin-right:4px;">Status &rarr; <?= e(ucfirst(str_replace('_',' ',$upd['status_change']))) ?></span>
                        <?php endif; ?>
                        <?php if ($upd['severity_change']): ?>
                            <span class="status-badge" style="margin-right:4px;">Severity &rarr; <?= e(ucfirst($upd['severity_change'])) ?></span>
                        <?php endif; ?>
                        
                        <?php if ($upd['comment']): ?>
                            <div style="margin-top:8px; padding:10px 12px; background:var(--bg-body); border-radius:6px; border:1px solid var(--border);"><?= nl2br(e($upd['comment'])) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Admin Tools -->
    <div style="display:flex; flex-direction:column; gap:16px;">
        <div class="card">
            <h4 style="margin:0 0 16px; font-size:15px;">Admin Controls</h4>
            <form method="POST" action="/safety/report/<?= $report['id'] ?>/update">
                <?= csrfField() ?>
                
                <div class="form-group">
                    <label>Assigned Investigator</label>
                    <select name="assigned_to" class="form-control">
                        <option value="">— Unassigned —</option>
                        <?php foreach ($crewList as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $report['assigned_to'] === $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Severity Rating</label>
                    <select name="severity" class="form-control">
                        <option value="unassigned" <?= $report['severity']==='unassigned'?'selected':'' ?>>Unassigned</option>
                        <option value="low" <?= $report['severity']==='low'?'selected':'' ?>>Low (Routine)</option>
                        <option value="medium" <?= $report['severity']==='medium'?'selected':'' ?>>Medium (Hazard)</option>
                        <option value="high" <?= $report['severity']==='high'?'selected':'' ?>>High (Incident)</option>
                        <option value="critical" <?= $report['severity']==='critical'?'selected':'' ?>>Critical (Accident/AOG)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Current Status</label>
                    <select name="status" class="form-control">
                        <option value="submitted" <?= $report['status']==='submitted'?'selected':'' ?>>Submitted (New)</option>
                        <option value="under_review" <?= $report['status']==='under_review'?'selected':'' ?>>Under Review</option>
                        <option value="investigation" <?= $report['status']==='investigation'?'selected':'' ?>>Active Investigation</option>
                        <option value="closed" <?= $report['status']==='closed'?'selected':'' ?>>Closed / Resolved</option>
                    </select>
                </div>

                <div class="form-group" style="margin-top:16px;">
                    <label>Investigation Notes / Comments</label>
                    <textarea name="comment" class="form-control" rows="4" placeholder="Enter findings or updates securely..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%; margin-top:8px;">Save Updates</button>
            </form>
        </div>
    </div>
</div>

