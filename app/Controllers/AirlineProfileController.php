<?php
/**
 * AirlineProfileController — airline-side settings and profile
 *
 * Shows and updates:
 *   - Tenant metadata (display_name, icao_code, iata_code, primary_base, primary_country)
 *   - Tenant settings (timezone, date_format, language, mobile_sync_interval, etc.)
 *   - Summary of active modules and contacts
 *
 * Accessible by: airline_admin only
 */
class AirlineProfileController {

    public function __construct() {
        RbacMiddleware::requireRole(['super_admin', 'airline_admin']);
    }

    public function show(): void {
        $tenantId = currentTenantId();
        $tenant   = Tenant::find($tenantId);

        if (!$tenant) {
            flash('error', 'Airline profile not found.');
            redirect('/dashboard');
        }

        $settings = Database::fetch(
            "SELECT * FROM tenant_settings WHERE tenant_id = ?",
            [$tenantId]
        );

        $contacts = Database::fetchAll(
            "SELECT * FROM tenant_contacts WHERE tenant_id = ? ORDER BY is_primary DESC, name ASC",
            [$tenantId]
        );

        $activeModules = Database::fetchAll(
            "SELECT m.name, m.code, m.icon FROM tenant_modules tm
             JOIN modules m ON m.id = tm.module_id
             WHERE tm.tenant_id = ? AND tm.is_enabled = 1
             ORDER BY m.sort_order",
            [$tenantId]
        );

        $pageTitle    = 'Airline Profile';
        $pageSubtitle = 'Company information and settings';

        ob_start();
        require VIEWS_PATH . '/airline/profile.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function update(): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/airline/profile');
        }

        $tenantId = currentTenantId();

        // Update tenant metadata
        $displayName    = trim($_POST['display_name']    ?? '');
        $primaryCountry = trim($_POST['primary_country'] ?? '');
        $primaryBase    = trim($_POST['primary_base']    ?? '');
        $icaoCode       = strtoupper(trim($_POST['icao_code'] ?? ''));
        $iataCode       = strtoupper(trim($_POST['iata_code'] ?? ''));

        Database::execute(
            "UPDATE tenants SET
                display_name    = ?,
                primary_country = ?,
                primary_base    = ?,
                icao_code       = ?,
                iata_code       = ?
             WHERE id = ?",
            [$displayName ?: null, $primaryCountry ?: null, $primaryBase ?: null,
             $icaoCode ?: null, $iataCode ?: null, $tenantId]
        );

        // Upsert tenant settings
        $timezone     = trim($_POST['timezone']     ?? 'UTC');
        $dateFormat   = trim($_POST['date_format']  ?? 'Y-m-d');
        $language     = trim($_POST['language']     ?? 'en');
        $syncInterval = max(15, min(1440, (int) ($_POST['mobile_sync_interval_minutes'] ?? 60)));

        $isSqlite = env('DB_DRIVER', 'mysql') === 'sqlite';
        if ($isSqlite) {
            Database::execute(
                "INSERT INTO tenant_settings (tenant_id, timezone, date_format, language, mobile_sync_interval_minutes)
                 VALUES (?, ?, ?, ?, ?)
                 ON CONFLICT(tenant_id) DO UPDATE SET
                     timezone = excluded.timezone,
                     date_format = excluded.date_format,
                     language = excluded.language,
                     mobile_sync_interval_minutes = excluded.mobile_sync_interval_minutes,
                     updated_at = datetime('now')",
                [$tenantId, $timezone, $dateFormat, $language, $syncInterval]
            );
        } else {
            Database::execute(
                "INSERT INTO tenant_settings (tenant_id, timezone, date_format, language, mobile_sync_interval_minutes)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                     timezone = VALUES(timezone),
                     date_format = VALUES(date_format),
                     language = VALUES(language),
                     mobile_sync_interval_minutes = VALUES(mobile_sync_interval_minutes),
                     updated_at = NOW()",
                [$tenantId, $timezone, $dateFormat, $language, $syncInterval]
            );
        }

        AuditLog::log('Updated Airline Profile', 'tenant', $tenantId, "Updated airline profile settings");
        flash('success', 'Airline profile updated.');
        redirect('/airline/profile');
    }
}
