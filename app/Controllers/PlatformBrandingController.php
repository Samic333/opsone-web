<?php
/**
 * PlatformBrandingController — Manage the Opsvelo brand icon.
 *
 * Lets a platform super admin replace public/images/brand/opsvelo-icon.png
 * (the file used by the favicon and the public-site nav icon) without a
 * code deploy. PNG only, ≤ 2 MB. Audit-logged on successful replace.
 *
 * Accessible by: super_admin only.
 */
class PlatformBrandingController {

    private const ICON_PATH    = 'images/brand/opsvelo-icon.png';
    private const MAX_BYTES    = 2 * 1024 * 1024;
    private const ALLOWED_MIME = ['image/png'];

    public function index(): void {
        RbacMiddleware::requirePlatformSuperAdmin();

        $iconAbs = BASE_PATH . '/public/' . self::ICON_PATH;
        $iconExists = file_exists($iconAbs);
        $iconBytes  = $iconExists ? filesize($iconAbs) : 0;
        $iconMtime  = $iconExists ? filemtime($iconAbs) : 0;

        $pageTitle    = 'Branding';
        $pageSubtitle = 'Replace the Opsvelo brand icon used by the favicon and public site';

        ob_start();
        require VIEWS_PATH . '/platform/branding.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function upload(): void {
        RbacMiddleware::requirePlatformSuperAdmin();

        if (!verifyCsrf()) {
            flash('error', 'Invalid security token.');
            redirect('/platform/branding');
        }

        if (empty($_FILES['icon']) || ($_FILES['icon']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            flash('error', 'Choose a PNG file to upload.');
            redirect('/platform/branding');
        }

        $tmp  = $_FILES['icon']['tmp_name'];
        $size = (int) $_FILES['icon']['size'];

        if ($size === 0 || $size > self::MAX_BYTES) {
            flash('error', 'File must be between 1 byte and 2 MB.');
            redirect('/platform/branding');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = $finfo ? finfo_file($finfo, $tmp) : null;
        if ($finfo) finfo_close($finfo);

        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            flash('error', 'Only PNG files are accepted (got: ' . e((string) $mime) . ').');
            redirect('/platform/branding');
        }

        $iconAbs = BASE_PATH . '/public/' . self::ICON_PATH;
        $backup  = $iconAbs . '.bak.' . date('YmdHis');

        if (file_exists($iconAbs) && !@copy($iconAbs, $backup)) {
            flash('error', 'Could not back up the existing icon. Aborted.');
            redirect('/platform/branding');
        }

        if (!@move_uploaded_file($tmp, $iconAbs)) {
            flash('error', 'Could not write the new icon to disk.');
            redirect('/platform/branding');
        }

        @chmod($iconAbs, 0644);

        AuditLog::log(
            'branding.icon_replaced',
            'branding',
            null,
            'New brand icon uploaded (' . number_format($size) . ' bytes); previous saved at ' . basename($backup)
        );

        flash('success', 'Brand icon updated. Hard-refresh your browser to see it everywhere (favicon may take longer due to browser cache).');
        redirect('/platform/branding');
    }
}
