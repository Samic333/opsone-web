<?php /** Phase 9 — Flight detail + bag */ ?>
<div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">

  <div class="card">
    <h3 style="margin-top:0;">Flight Details</h3>
    <table>
      <tr><td>Number</td><td><strong><?= e($flight['flight_number']) ?></strong></td></tr>
      <tr><td>Date</td><td><?= e($flight['flight_date']) ?></td></tr>
      <tr><td>Route</td><td><?= e($flight['departure']) ?> → <?= e($flight['arrival']) ?></td></tr>
      <tr><td>STD / STA</td><td><?= e($flight['std'] ?? '—') ?> / <?= e($flight['sta'] ?? '—') ?></td></tr>
      <tr><td>Aircraft</td><td><?= e($flight['reg'] ?? '—') ?> <?= $flight['aircraft_type'] ? '(' . e($flight['aircraft_type']) . ')' : '' ?></td></tr>
      <tr><td>Captain</td><td><?= e($flight['captain_name'] ?? '—') ?></td></tr>
      <tr><td>First Officer</td><td><?= e($flight['fo_name'] ?? '—') ?></td></tr>
      <tr><td>Status</td><td><?= statusBadge($flight['status']) ?></td></tr>
    </table>

    <?php if (($flight['status'] ?? '') === 'draft'
              && hasAnyRole(['super_admin','airline_admin','scheduler','chief_pilot','base_manager'])): ?>
        <form method="POST" action="/flights/<?= (int)$flight['id'] ?>/publish" style="margin-top:12px;">
            <?= csrfField() ?>
            <button class="btn btn-primary" type="submit">Publish & Notify Crew</button>
        </form>
    <?php endif; ?>

    <?php /* Flight Folder — review summary + link for managers only. */ ?>
    <?php if (hasAnyRole(['super_admin','airline_admin','scheduler','chief_pilot','base_manager','safety_officer'])
              && isset($folderSummary)): ?>
      <div style="margin-top:16px; padding:12px; border:1px solid #e5e7eb; border-radius:10px; background:#f9fafb;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
          <div>
            <div style="font-size:11px; font-weight:700; letter-spacing:1.1px; color:#6b7280; text-transform:uppercase;">
              Flight Folder
            </div>
            <div style="font-size:13px; color:#111; margin-top:4px;">
              <?php
                $parts = [];
                if (($folderSummary['submitted']  ?? 0) > 0) $parts[] = $folderSummary['submitted'] . ' submitted';
                if (($folderSummary['accepted']   ?? 0) > 0) $parts[] = $folderSummary['accepted']  . ' accepted';
                if (($folderSummary['returned']   ?? 0) > 0) $parts[] = $folderSummary['returned']  . ' need info';
                if (($folderSummary['rejected']   ?? 0) > 0) $parts[] = $folderSummary['rejected']  . ' rejected';
                $not_started = (int)($folderSummary['not_started'] ?? 0);
                if ($not_started > 0) $parts[] = $not_started . ' not started';
                echo $parts ? e(implode(' · ', $parts)) : 'No crew submissions yet';
              ?>
            </div>
          </div>
          <a href="/flights/<?= (int)$flight['id'] ?>/folder" class="btn btn-primary btn-sm">
            Review Flight Folder
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($canUpload): ?>
  <div class="card">
    <h3 style="margin-top:0;">Add to Flight Bag</h3>
    <form method="POST" action="/flights/<?= (int)$flight['id'] ?>/bag/upload" enctype="multipart/form-data">
        <?= csrfField() ?>
        <div class="form-row">
            <div class="form-group"><label>Type</label>
                <select name="file_type" class="form-control">
                    <option value="nav_plan">Nav Plan</option>
                    <option value="notam">NOTAMs</option>
                    <option value="weather">Weather</option>
                    <option value="wb">Weight &amp; Balance</option>
                    <option value="opt">OPT / Performance</option>
                    <option value="company">Company Doc</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group"><label>Title</label><input type="text" name="title" class="form-control"></div>
        </div>
        <div class="form-group"><label>File</label>
            <input type="file" name="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.txt,.csv" required>
            <small class="text-xs text-muted">PDF / image / text, max 50MB.</small>
        </div>
        <button class="btn btn-primary btn-sm" type="submit">Upload</button>
    </form>
  </div>
  <?php endif; ?>
</div>

<div class="card" style="margin-top:16px;">
    <h3 style="margin-top:0;">Flight Bag (<?= count($bag) ?>)</h3>
    <?php if (empty($bag)): ?>
        <p class="text-muted">No briefing documents yet.</p>
    <?php else: ?>
    <table>
        <thead><tr><th>Type</th><th>Title</th><th>File</th><th>Size</th><th>Added</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($bag as $b): ?>
            <tr>
                <td class="text-sm"><?= e($b['file_type']) ?></td>
                <td class="text-sm"><?= e($b['title']) ?></td>
                <td class="text-xs text-muted"><?= e($b['file_name']) ?></td>
                <td class="text-xs"><?= formatBytes((int)$b['file_size']) ?></td>
                <td class="text-xs text-muted"><?= formatDateTime($b['created_at']) ?></td>
                <td><a href="/flights/bag/<?= (int)$b['id'] ?>/download" class="btn btn-xs btn-outline">Download</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div style="margin-top:16px;"><a href="/flights" class="btn btn-outline">← Back</a></div>
