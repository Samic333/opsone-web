<?php /** OpsOne — Create Roster Period */ ?>

<div style="max-width:640px;">
    <form method="POST" action="/roster/periods/store">
        <?= csrfField() ?>

        <div class="card">
            <h3 style="margin:0 0 20px; font-size:15px;">Period Details</h3>

            <div class="form-group">
                <label>Period Name <span style="color:#ef4444;">*</span></label>
                <input type="text" name="name" class="form-control"
                       placeholder="e.g. April 2026 or W14–W17 2026"
                       value="<?= e($_POST['name'] ?? '') ?>" required>
                <p class="text-xs text-muted" style="margin-top:4px;">
                    Descriptive name shown to schedulers. Crew see the roster, not the period name.
                </p>
            </div>

            <div class="grid grid-2" style="gap:16px;">
                <div class="form-group">
                    <label>Start Date <span style="color:#ef4444;">*</span></label>
                    <input type="date" name="start_date" class="form-control"
                           value="<?= e($_POST['start_date'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>End Date <span style="color:#ef4444;">*</span></label>
                    <input type="date" name="end_date" class="form-control"
                           value="<?= e($_POST['end_date'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Notes <span class="text-muted text-xs">(optional)</span></label>
                <textarea name="notes" class="form-control" rows="3"
                          placeholder="e.g. Summer season, short-haul only, includes bank holidays..."><?= e($_POST['notes'] ?? '') ?></textarea>
            </div>

            <div style="display:flex; gap:12px; margin-top:8px;">
                <button type="submit" class="btn btn-primary">Create Period</button>
                <a href="/roster/periods" class="btn btn-ghost">Cancel</a>
            </div>
        </div>

    </form>

    <div class="card" style="margin-top:16px; padding:16px 20px; background:var(--bg-card); border:1px solid var(--border);">
        <p class="text-sm text-muted" style="margin:0;">
            <strong>New periods start as Draft.</strong> Build the roster for the period dates,
            then publish when ready. Crew only see published or frozen periods.
        </p>
    </div>
</div>
