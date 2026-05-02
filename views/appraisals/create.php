<?php
/**
 * Appraisals — create
 *
 * URL params:
 *   ?kind=          → chooser (Self vs Peer)
 *   ?kind=self      → self appraisal form (Section One + Section Two)
 *   ?kind=peer      → peer appraisal form (Section One + Section Three)
 *
 * Variables in scope (set by AppraisalController::showCreate):
 *   $kind, $subjects, $attributes, $ratingScale
 */

// 18 questions from Section Two of the paper form (mirrors
// `SelfAppraisalForm.questions` in CrewAssist/Features/Appraisals/AppraisalsView.swift).
$selfQuestions = [
    "What is the current score of the quarterly client (UN) evaluation for the station/base you're currently deployed to, and how did you contribute to that ranking?",
    "What has been your experience in this station/base on a personal level?",
    "What has been your experience in this station/base with clients?",
    "What has been your experience in this station/base with the aircraft operated there?",
    "Areas of improvement for the station and the team based on your experience above.",
    "What were your goals and objectives during this evaluation period?",
    "Achievements, accomplishments and responsibilities — what difference have you made in this station since you arrived?",
    "How did you see your role at the station during this period?",
    "Strengths and areas for development.",
    "What makes your contribution at the station / base unique?",
    "Communication & interaction with travellers / clients & UN — strengths, weaknesses, areas of improvement.",
    "Communication & interaction with colleagues at the station / base — strengths, weaknesses, areas of improvement.",
    "Communication & interaction with the Base Manager — strengths, weaknesses, areas of improvement.",
    "Communication & interaction with the Nairobi base (HQ) team — strengths, weaknesses, areas of improvement.",
    "Do you understand the terms of the current contract? What are your deliverables, and do you feel able to fulfil them?",
    "Career progression plan — where do you see yourself in 5–10 years?",
    "Do you know the company's policy on Microsoft OneDrive? Please explain.",
    "Goals and objectives for the next evaluation period.",
];

if ($kind === ''): ?>

    <!-- ─── Chooser ─────────────────────────────────────────────── -->
    <div class="page-header" style="margin-bottom:24px;">
        <h1 style="margin:0; font-size:24px; letter-spacing:-0.02em;">New Appraisal</h1>
        <p class="text-muted" style="margin:6px 0 0;">
            Field Stations Staff Appraisal Form (Form No. 150, Rev 01).
            Pick the form type that matches your role for this submission.
        </p>
    </div>

    <div class="grid grid-2" style="max-width:920px;">
        <a href="/appraisals/new?kind=self" class="card stat-card-link"
           style="text-decoration:none; padding:24px;">
            <div style="display:flex; align-items:flex-start; gap:14px;">
                <div style="font-size:28px; line-height:1;">🪞</div>
                <div>
                    <h3 style="margin:0 0 6px;">Self Appraisal</h3>
                    <p class="text-muted text-sm" style="margin:0;">
                        You appraise yourself for your current field-station deployment.
                        Section Two of the paper form — 18 free-text questions covering
                        client relationships, role, contribution and goals.
                    </p>
                </div>
            </div>
        </a>

        <?php if (!empty($subjects)): ?>
        <a href="/appraisals/new?kind=peer" class="card stat-card-link"
           style="text-decoration:none; padding:24px;">
            <div style="display:flex; align-items:flex-start; gap:14px;">
                <div style="font-size:28px; line-height:1;">🤝</div>
                <div>
                    <h3 style="margin:0 0 6px;">Peer Appraisal</h3>
                    <p class="text-muted text-sm" style="margin:0;">
                        You appraise a colleague at the same station or related department.
                        Section Three of the paper form — five competency attributes rated
                        1–5, with strengths, improvements and a confidential flag.
                    </p>
                </div>
            </div>
        </a>
        <?php else: ?>
        <div class="card" style="padding:24px; opacity:0.6;">
            <h3 style="margin:0 0 6px;">Peer Appraisal</h3>
            <p class="text-muted text-sm" style="margin:0;">
                Your role does not currently authorise peer appraisals in this airline.
                Contact HR or your Chief Pilot if this looks wrong.
            </p>
        </div>
        <?php endif; ?>
    </div>

    <div style="margin-top:20px;">
        <a href="/appraisals" class="btn btn-outline">← Back to appraisals</a>
    </div>

<?php elseif ($kind === 'self'): ?>

    <!-- ─── Self Appraisal Form ────────────────────────────────── -->
    <div class="page-header" style="margin-bottom:20px;">
        <h1 style="margin:0; font-size:24px; letter-spacing:-0.02em;">Self Appraisal</h1>
        <p class="text-muted" style="margin:6px 0 0;">
            Section One — Appraisal Details. Section Two — 18 questions (Form No. 150, Rev 01).
        </p>
    </div>

    <form method="POST" action="/appraisals/store" class="appraisal-form" id="selfForm">
        <?= csrfField() ?>
        <input type="hidden" name="kind" value="self">

        <!-- Section One -->
        <details class="card" open style="margin-bottom:14px; padding:0;">
            <summary style="padding:18px 20px; cursor:pointer; font-weight:600; font-size:15px;">
                Section One — Appraisal Details
            </summary>
            <div style="padding:0 20px 20px;">
                <div class="form-row">
                    <div class="form-group">
                        <label>Rotation reference</label>
                        <input type="text" name="rotation_ref" class="form-control" placeholder="e.g. Q2-2026 / P-2026-04">
                    </div>
                    <div class="form-group">
                        <label>Period from *</label>
                        <input type="date" name="period_from" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Period to *</label>
                        <input type="date" name="period_to" class="form-control" required>
                    </div>
                </div>
            </div>
        </details>

        <!-- Section Two — 18 questions -->
        <details class="card" open style="margin-bottom:14px; padding:0;">
            <summary style="padding:18px 20px; cursor:pointer; font-weight:600; font-size:15px;">
                Section Two — Self Appraisal (18 questions)
            </summary>
            <div style="padding:0 20px 20px;">
                <?php foreach ($selfQuestions as $i => $q): ?>
                    <div class="form-group" style="margin-bottom:18px;">
                        <label style="font-weight:500;">
                            <span class="text-muted" style="margin-right:6px;"><?= ($i + 1) ?>.</span>
                            <?= e($q) ?>
                        </label>
                        <textarea name="answers[<?= (int)$i ?>]" class="form-control" rows="3"></textarea>
                    </div>
                <?php endforeach; ?>
            </div>
        </details>

        <!-- Section Three — Wrap-up -->
        <details class="card" style="margin-bottom:14px; padding:0;">
            <summary style="padding:18px 20px; cursor:pointer; font-weight:600; font-size:15px;">
                Wrap-up — Strengths, Improvements & Comments
            </summary>
            <div style="padding:0 20px 20px;">
                <div class="form-group">
                    <label>Strengths</label>
                    <textarea name="strengths" class="form-control" rows="3" placeholder="Where did you perform well during this period?"></textarea>
                </div>
                <div class="form-group">
                    <label>Areas for improvement</label>
                    <textarea name="improvements" class="form-control" rows="3" placeholder="Where would you like to develop further?"></textarea>
                </div>
                <div class="form-group">
                    <label>General comments</label>
                    <textarea name="comments" class="form-control" rows="3"></textarea>
                </div>
            </div>
        </details>

        <input type="hidden" name="confidential" value="1">

        <!-- Actions -->
        <div class="card" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
            <button class="btn btn-primary" type="submit" name="status" value="submitted">Submit appraisal</button>
            <button class="btn btn-outline" type="submit" name="status" value="draft">Save draft</button>
            <a href="/appraisals" class="btn btn-outline">Cancel</a>
            <span class="text-xs text-muted" style="margin-left:auto;">
                Self appraisals are confidential by default — visible to HR and yourself only.
            </span>
        </div>
    </form>

<?php elseif ($kind === 'peer'): ?>

    <!-- ─── Peer Appraisal Form ────────────────────────────────── -->
    <div class="page-header" style="margin-bottom:20px;">
        <h1 style="margin:0; font-size:24px; letter-spacing:-0.02em;">Peer Appraisal</h1>
        <p class="text-muted" style="margin:6px 0 0;">
            Section Three of the paper form. Rate the appraisee on five competency
            attributes (1 = Poor … 5 = Excellent).
        </p>
    </div>

    <form method="POST" action="/appraisals/store" class="appraisal-form" id="peerForm">
        <?= csrfField() ?>
        <input type="hidden" name="kind" value="peer">

        <!-- Section One -->
        <details class="card" open style="margin-bottom:14px; padding:0;">
            <summary style="padding:18px 20px; cursor:pointer; font-weight:600; font-size:15px;">
                Section One — Subject &amp; Period
            </summary>
            <div style="padding:0 20px 20px;">
                <div class="form-row">
                    <div class="form-group">
                        <label>Subject (appraisee) *</label>
                        <select name="subject_id" class="form-control" required>
                            <option value="">— Select crew member —</option>
                            <?php foreach ($subjects as $s): ?>
                                <option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Rotation / flight / duty reference</label>
                        <input type="text" name="rotation_ref" class="form-control" placeholder="e.g. Q2-2026 / FLT-742">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Period from *</label>
                        <input type="date" name="period_from" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Period to *</label>
                        <input type="date" name="period_to" class="form-control" required>
                    </div>
                </div>
            </div>
        </details>

        <!-- Section Three — Rating grid -->
        <details class="card" open style="margin-bottom:14px; padding:0;">
            <summary style="padding:18px 20px; cursor:pointer; font-weight:600; font-size:15px;">
                Section Two — Competency Ratings (1–5 per attribute)
            </summary>
            <div style="padding:0 20px 20px;">
                <p class="text-xs text-muted" style="margin-top:0;">
                    P = Poor &middot; F = Fair &middot; G = Good &middot; VG = Very Good &middot; E = Excellent.
                    The overall rating is auto-calculated from the average of all five attributes.
                </p>
                <div class="table-wrap" style="margin-top:8px;">
                    <table class="table" style="font-size:13px;">
                        <thead>
                            <tr>
                                <th>Attribute</th>
                                <?php foreach ($ratingScale as $score => $r): ?>
                                    <th style="text-align:center;">
                                        <?= e($r['code']) ?>
                                        <div class="text-xs text-muted" style="font-weight:400; margin-top:2px;">(<?= (int)$score ?>) <?= e($r['label']) ?></div>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attributes as $key => $attr): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:600;"><?= e($attr['title']) ?></div>
                                        <div class="text-xs text-muted"><?= e($attr['desc']) ?></div>
                                    </td>
                                    <?php foreach ($ratingScale as $score => $r): ?>
                                        <td style="text-align:center;">
                                            <label style="cursor:pointer; display:inline-block; padding:6px 10px;">
                                                <input type="radio" name="ratings[<?= e($key) ?>]" value="<?= (int)$score ?>" required>
                                            </label>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </details>

        <!-- Strengths / Improvements / Comments -->
        <details class="card" open style="margin-bottom:14px; padding:0;">
            <summary style="padding:18px 20px; cursor:pointer; font-weight:600; font-size:15px;">
                Section Three — Strengths, Improvements &amp; Recommendation
            </summary>
            <div style="padding:0 20px 20px;">
                <div class="form-group">
                    <label>Strengths</label>
                    <textarea name="strengths" class="form-control" rows="3" placeholder="What did the appraisee do well?"></textarea>
                </div>
                <div class="form-group">
                    <label>Areas for improvement</label>
                    <textarea name="improvements" class="form-control" rows="3" placeholder="If any areas of performance require improvement, please provide details."></textarea>
                </div>
                <div class="form-group">
                    <label>General comments / final recommendation</label>
                    <textarea name="comments" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-check" style="display:flex; gap:8px; align-items:center;">
                        <input type="checkbox" name="confidential" value="1" checked>
                        <span>Confidential — visible to HR / Chief Pilot only until accepted.</span>
                    </label>
                </div>
            </div>
        </details>

        <!-- Actions -->
        <div class="card" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
            <button class="btn btn-primary" type="submit" name="status" value="submitted">Submit for review</button>
            <button class="btn btn-outline" type="submit" name="status" value="draft">Save draft</button>
            <a href="/appraisals/new" class="btn btn-outline">← Back</a>
            <span class="text-xs text-muted" style="margin-left:auto;">
                Submitted appraisals are visible to HR / Chief Pilot for acceptance. The appraisee
                only sees the record once it is accepted (unless you uncheck Confidential above).
            </span>
        </div>
    </form>

    <script>
    // Soft client-side validation: warn if no rating row is fully filled.
    document.getElementById('peerForm')?.addEventListener('submit', function (e) {
        const button = e.submitter;
        if (button && button.value === 'draft') return; // drafts can be incomplete
        const rows = this.querySelectorAll('input[type=radio][name^="ratings["]');
        const seen = new Set();
        rows.forEach(r => { if (r.checked) seen.add(r.name); });
        const expected = <?= count($attributes) ?>;
        if (seen.size < expected) {
            if (!confirm('You have not rated every attribute. Submit anyway?')) e.preventDefault();
        }
    });
    </script>

<?php endif; ?>
