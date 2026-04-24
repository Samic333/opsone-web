<?php /** Phase 5 (V2) — Flight Folder review (station manager). */ ?>
<style>
  .ff-grid { display:grid; grid-template-columns: repeat(auto-fit,minmax(300px,1fr)); gap:14px; }
  .ff-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; box-shadow:0 1px 2px rgba(0,0,0,.04); }
  .ff-card h4 { margin:0 0 6px 0; font-size:15px; }
  .ff-card .meta { color:#6b7280; font-size:12px; margin-bottom:10px; }
  .ff-status { display:inline-block; padding:3px 10px; border-radius:9999px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; }
  .ff-status.not_started { background:#f3f4f6; color:#6b7280; }
  .ff-status.draft       { background:#fef3c7; color:#92400e; }
  .ff-status.submitted   { background:#dbeafe; color:#1e40af; }
  .ff-status.accepted    { background:#d1fae5; color:#065f46; }
  .ff-status.rejected    { background:#fee2e2; color:#991b1b; }
  .ff-status.returned_for_info { background:#fde68a; color:#92400e; }
  .ff-payload-row { display:flex; justify-content:space-between; gap:10px; padding:6px 0; border-bottom:1px dashed #e5e7eb; font-size:13px; }
  .ff-payload-row:last-child { border-bottom:0; }
  .ff-payload-row .k { color:#6b7280; text-transform:uppercase; font-size:10.5px; letter-spacing:.6px; font-weight:600; }
  .ff-payload-row .v { color:#111; max-width:65%; text-align:right; word-break:break-word; }
  .ff-actions { display:flex; gap:8px; margin-top:12px; flex-wrap:wrap; }
  .ff-actions form { display:inline; }
  .ff-actions .btn { padding:6px 12px; font-size:12px; border-radius:8px; }
  .ff-history { background:#fafafa; border:1px solid #f0f0f0; border-radius:12px; padding:14px; font-size:13px; }
  .ff-history .row { display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px dashed #ececec; }
  .ff-history .row:last-child { border-bottom:0; }
  .ff-review { background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:10px; margin-top:10px; }
  .ff-review textarea { width:100%; padding:6px; border:1px solid #ddd; border-radius:6px; resize:vertical; }
</style>

<div class="page-header">
  <h1><?= e($pageTitle ?? 'Flight Folder') ?></h1>
  <p class="subtitle">Submissions from iPad / iPhone crew · reviewed on web</p>
</div>

<div style="display:flex; gap:16px; margin-bottom:16px; flex-wrap:wrap;">
  <div style="background:#0b1a2f; color:#fff; padding:14px 18px; border-radius:12px; min-width:260px;">
    <div style="font-size:11px; letter-spacing:1px; opacity:.7; font-weight:700;">FLIGHT</div>
    <div style="font-size:22px; font-weight:700; margin-top:2px; font-family:'SFMono-Regular',monospace;">
      <?= e($flight['departure']) ?> → <?= e($flight['arrival']) ?>
    </div>
    <div style="font-size:13px; opacity:.85; margin-top:6px;">
      <?= e($flight['flight_number']) ?> · <?= e($flight['flight_date']) ?>
      <?php if (!empty($flight['std'])): ?> · STD <?= e($flight['std']) ?><?php endif; ?>
      <?php if (!empty($flight['aircraft_reg'])): ?> · <?= e($flight['aircraft_reg']) ?><?php endif; ?>
    </div>
  </div>
  <div style="display:flex; flex-direction:column; gap:4px;">
    <div style="font-size:12px; color:#6b7280;">Captain: <strong><?= e($flight['captain_name'] ?? '—') ?></strong></div>
    <div style="font-size:12px; color:#6b7280;">First Officer: <strong><?= e($flight['fo_name'] ?? '—') ?></strong></div>
    <div style="font-size:12px; color:#6b7280;">Status: <?= statusBadge($flight['status']) ?></div>
  </div>
</div>

<div class="ff-grid">
<?php foreach ($docLabels as $docType => $label): $row = $docs[$docType] ?? null; $status = $row['status'] ?? 'not_started'; ?>
  <div class="ff-card">
    <h4><?= e($label) ?></h4>
    <div class="meta">
      <span class="ff-status <?= e($status) ?>"><?= e(str_replace('_',' ',$status)) ?></span>
      <?php if (!empty($row['submitter_name'])): ?>
        · submitted by <strong><?= e($row['submitter_name']) ?></strong>
      <?php endif; ?>
      <?php if (!empty($row['submitted_at'])): ?>
        · <?= e(formatDateTime($row['submitted_at'])) ?>
      <?php endif; ?>
    </div>

    <?php if ($row && !empty($row['payload'])): ?>
      <?php $payload = json_decode($row['payload'], true) ?: []; ?>
      <?php if (!empty($payload)): ?>
        <?php foreach ($payload as $k => $v): ?>
          <div class="ff-payload-row">
            <div class="k"><?= e(str_replace('_',' ', (string)$k)) ?></div>
            <div class="v"><?= e(is_array($v) ? json_encode($v) : (string)$v) ?></div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div style="color:#9ca3af; font-size:12px;">Draft is empty.</div>
      <?php endif; ?>
    <?php elseif (!$row): ?>
      <div style="color:#9ca3af; font-size:12px;">Not started by crew yet.</div>
    <?php endif; ?>

    <?php if ($canReview && $row && $status === 'submitted'): ?>
      <div class="ff-review">
        <form method="POST" action="/flights/<?= (int)$flight['id'] ?>/folder/<?= e($docType) ?>/review">
          <?= csrfField() ?>
          <textarea name="notes" rows="2" placeholder="Notes for crew (optional)"></textarea>
          <div class="ff-actions">
            <button class="btn btn-primary"  name="decision" value="accept">Accept</button>
            <button class="btn btn-outline"  name="decision" value="info">Return for info</button>
            <button class="btn btn-danger"   name="decision" value="reject">Reject</button>
          </div>
        </form>
      </div>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
</div>

<h3 style="margin-top:28px;">Review history</h3>
<?php if (empty($history)): ?>
  <div style="color:#9ca3af; font-size:13px;">No review actions yet.</div>
<?php else: ?>
  <div class="ff-history">
    <?php foreach ($history as $h): ?>
      <div class="row">
        <div>
          <strong><?= e($h['changed_by_name'] ?? 'system') ?></strong>
          changed <em><?= e(str_replace('_',' ', $h['doc_type'])) ?></em>
          from <code><?= e($h['old_status'] ?? '—') ?></code>
          to <code><?= e($h['new_status']) ?></code>
          <?php if (!empty($h['notes'])): ?>
            — <span style="color:#374151;"><?= e($h['notes']) ?></span>
          <?php endif; ?>
        </div>
        <div style="color:#6b7280; font-size:12px;"><?= e(formatDateTime($h['changed_at'])) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div style="margin-top:16px;">
  <a href="/flights/<?= (int)$flight['id'] ?>" class="btn btn-outline">← Back to flight</a>
</div>
