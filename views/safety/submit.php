<?php
$pageTitle = 'Submit Safety Report';
$pageSubtitle = 'Confidential safety, hazard, and incident reporting. Protected under Just Culture policy.';
?>

<div style="max-width:800px; margin:0 auto;">
    <div style="margin-bottom:20px;">
        <a href="/safety/my-reports" class="btn btn-ghost">← My Submissions</a>
    </div>

    <form method="POST" action="/safety/submit" class="card">
        <?= csrfField() ?>
        
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
            <div class="form-group">
                <label>Report Type *</label>
                <select name="report_type" class="form-control" required>
                    <option value="ASR">Air Safety Report (ASR)</option>
                    <option value="HAZARD">Hazard Observation</option>
                    <option value="INCIDENT">Incident / Near Miss</option>
                    <option value="FATIGUE">Fatigue Report</option>
                    <option value="MOR">Mandatory Occurrence Report (MOR)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Date of Event <span class="text-muted">(optional)</span></label>
                <input type="date" name="event_date" class="form-control">
            </div>
        </div>

        <div class="form-group" style="margin-top:16px;">
            <label>Title / Brief Summary *</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. Laser strike on approach" required>
        </div>

        <div class="form-group">
            <label>Detailed Description *</label>
            <textarea name="description" class="form-control" rows="8" placeholder="Please provide as much factual detail as possible. Aircraft registration, location, sequence of events..." required></textarea>
        </div>

        <div class="card" style="background:var(--bg-body); border-color:var(--border);">
            <label style="display:flex; align-items:flex-start; gap:12px; cursor:pointer;">
                <input type="checkbox" name="is_anonymous" style="margin-top:4px;">
                <div>
                    <h4 style="margin:0 0 4px; font-size:14px;">Submit Anonymously</h4>
                    <p class="text-xs text-muted" style="margin:0;">
                        If checked, your identity will be permanently decoupled from this report on the Safety Admin dashboard. 
                        Safety Officers will not be able to follow up with you for further details.
                    </p>
                </div>
            </label>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:24px;">
            <a href="/dashboard" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary" style="background:#ef4444; border-color:#ef4444;">Submit Secure Report</button>
        </div>
    </form>
</div>

