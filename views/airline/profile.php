<div class="grid grid-2" style="gap:1.5rem; align-items:start">

    <!-- ─── Company Info & Settings Form ─────────────────── -->
    <div>
        <div class="card">
            <h3 class="card-title">Company Information</h3>
            <form method="POST" action="/airline/profile/update">
                <?= csrfField() ?>
                <div class="form-group">
                    <label class="form-label">Display Name</label>
                    <input type="text" name="display_name" class="form-control"
                           placeholder="Short name shown in the app"
                           value="<?= e($tenant['display_name'] ?? '') ?>">
                </div>
                <div class="grid grid-2" style="gap:1rem">
                    <div class="form-group">
                        <label class="form-label">ICAO Code</label>
                        <input type="text" name="icao_code" class="form-control" maxlength="4"
                               placeholder="e.g. ODA" style="text-transform:uppercase"
                               value="<?= e($tenant['icao_code'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">IATA Code</label>
                        <input type="text" name="iata_code" class="form-control" maxlength="3"
                               placeholder="e.g. OD" style="text-transform:uppercase"
                               value="<?= e($tenant['iata_code'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Primary Country</label>
                    <input type="text" name="primary_country" class="form-control"
                           placeholder="e.g. UAE" value="<?= e($tenant['primary_country'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Primary Base / HQ</label>
                    <input type="text" name="primary_base" class="form-control"
                           placeholder="e.g. Dubai (DXB)" value="<?= e($tenant['primary_base'] ?? '') ?>">
                </div>

                <h3 class="card-title mt-3">App &amp; Sync Settings</h3>
                <div class="form-group">
                    <label class="form-label">Timezone</label>
                    <input type="text" name="timezone" class="form-control"
                           placeholder="e.g. Asia/Dubai"
                           value="<?= e($settings['timezone'] ?? 'UTC') ?>">
                </div>
                <div class="grid grid-2" style="gap:1rem">
                    <div class="form-group">
                        <label class="form-label">Date Format</label>
                        <select name="date_format" class="form-control">
                            <?php $df = $settings['date_format'] ?? 'Y-m-d'; ?>
                            <option value="Y-m-d"   <?= $df === 'Y-m-d'   ? 'selected' : '' ?>>YYYY-MM-DD</option>
                            <option value="d/m/Y"   <?= $df === 'd/m/Y'   ? 'selected' : '' ?>>DD/MM/YYYY</option>
                            <option value="m/d/Y"   <?= $df === 'm/d/Y'   ? 'selected' : '' ?>>MM/DD/YYYY</option>
                            <option value="d M Y"   <?= $df === 'd M Y'   ? 'selected' : '' ?>>DD Mon YYYY</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Language</label>
                        <select name="language" class="form-control">
                            <?php $lang = $settings['language'] ?? 'en'; ?>
                            <option value="en" <?= $lang === 'en' ? 'selected' : '' ?>>English</option>
                            <option value="ar" <?= $lang === 'ar' ? 'selected' : '' ?>>Arabic</option>
                            <option value="fr" <?= $lang === 'fr' ? 'selected' : '' ?>>French</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Mobile Sync Interval (minutes)</label>
                    <input type="number" name="mobile_sync_interval_minutes" class="form-control"
                           min="15" max="1440"
                           value="<?= (int) ($settings['mobile_sync_interval_minutes'] ?? 60) ?>">
                    <div class="form-help">How often CrewAssist checks for content updates (15–1440 min).</div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Profile</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── Summary Panels ────────────────────────────────── -->
    <div>
        <!-- Active Modules -->
        <div class="card mb-3">
            <h3 class="card-title">Active Modules</h3>
            <?php if (empty($activeModules)): ?>
                <p class="text-muted text-sm">No modules currently enabled.</p>
            <?php else: ?>
                <div class="tag-list">
                    <?php foreach ($activeModules as $mod): ?>
                    <span class="badge badge-success"><?= e($mod['icon'] ?? '') ?> <?= e($mod['name']) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Contacts -->
        <div class="card mb-3">
            <h3 class="card-title">Contacts</h3>
            <?php if (empty($contacts)): ?>
                <p class="text-muted text-sm">No contacts on file.</p>
            <?php else: ?>
                <?php foreach ($contacts as $c): ?>
                <div class="detail-row">
                    <span class="detail-label"><?= e(ucfirst(str_replace('_', ' ', $c['contact_type']))) ?></span>
                    <span><?= e($c['name']) ?> — <?= e($c['email']) ?></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Status Summary -->
        <div class="card">
            <h3 class="card-title">Status</h3>
            <div class="detail-row">
                <span class="detail-label">Legal Name</span>
                <span><?= e($tenant['legal_name'] ?? $tenant['name'] ?? '—') ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Support Tier</span>
                <span class="badge badge-info"><?= e(ucfirst($tenant['support_tier'] ?? 'standard')) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <?php $st = $tenant['onboarding_status'] ?? 'active'; ?>
                <span class="badge <?= $st === 'active' ? 'badge-success' : 'badge-warning' ?>">
                    <?= e(ucfirst($st)) ?>
                </span>
            </div>
            <?php if (!empty($tenant['onboarded_at'])): ?>
            <div class="detail-row">
                <span class="detail-label">Onboarded</span>
                <span><?= e(date('d M Y', strtotime($tenant['onboarded_at']))) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>
