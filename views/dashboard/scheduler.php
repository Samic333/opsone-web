<?php $pageTitle = 'Scheduler Dashboard'; $pageSubtitle = 'Schedule Overview'; ob_start(); ?>
<div class="stats-grid">
    <div class="stat-card blue"><div class="stat-label">Active Staff</div><div class="stat-value"><?= $data['active_staff'] ?></div></div>
</div>
<div class="card">
    <div class="card-header"><div class="card-title">Scheduling Module</div></div>
    <div class="empty-state">
        <div class="icon">📅</div>
        <h3>Roster Integration</h3>
        <p>Future integration with roster management system. This module will connect to the existing roster functionality in the mobile app.</p>
    </div>
</div>
<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/app.php'; ?>
