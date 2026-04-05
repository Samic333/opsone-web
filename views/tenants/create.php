<?php $pageTitle = 'Create Airline'; ob_start(); ?>
<div class="card" style="max-width: 600px;">
    <form method="POST" action="/tenants/store">
        <?= csrfField() ?>
        <div class="form-group">
            <label for="name">Airline Name *</label>
            <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Gulf Wings Aviation" required>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="code">Airline Code (ICAO/IATA) *</label>
                <input type="text" id="code" name="code" class="form-control" placeholder="e.g. GWA" maxlength="10" required style="text-transform:uppercase">
            </div>
            <div class="form-group">
                <label for="contact_email">Contact Email</label>
                <input type="email" id="contact_email" name="contact_email" class="form-control" placeholder="admin@airline.com">
            </div>
        </div>
        <div class="flex gap-1">
            <button type="submit" class="btn btn-primary">Create Airline</button>
            <a href="/tenants" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/app.php'; ?>
