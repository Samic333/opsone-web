<?php
/**
 * InstallController — Protected enterprise app install page
 * Only authenticated users with approved status can access.
 */
class InstallController {
    public function index(): void {
        $user = currentUser();
        $tenantId = currentTenantId();

        // Log page view
        InstallLog::log('page_view', $user['id'] ?? null, $tenantId);

        // Get latest build info
        $latestBuild = AppBuild::latest();
        $allBuilds = AppBuild::all();

        $brand = require CONFIG_PATH . '/branding.php';
        $pageTitle = 'Install ' . $brand['product_name'];
        $pageSubtitle = 'Internal enterprise iPad deployment';

        ob_start();
        require VIEWS_PATH . '/install/index.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/public.php';
    }

    public function instructions(): void {
        $user = currentUser();
        $tenantId = currentTenantId();

        InstallLog::log('instructions_view', $user['id'] ?? null, $tenantId);

        $brand = require CONFIG_PATH . '/branding.php';
        $pageTitle = 'Installation Guide';
        $pageSubtitle = 'Step-by-step instructions for iPad installation';

        ob_start();
        require VIEWS_PATH . '/install/instructions.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/public.php';
    }

    public function manifest(): void {
        $user = currentUser();
        InstallLog::log('manifest_request', $user['id'] ?? null, currentTenantId());

        $build = AppBuild::latest();
        if (!$build) {
            http_response_code(404);
            echo 'No build available';
            exit;
        }

        $brand = require CONFIG_PATH . '/branding.php';
        $appUrl = config('app.url');

        header('Content-Type: application/xml');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">' . "\n";
        echo '<plist version="1.0"><dict>' . "\n";
        echo '<key>items</key><array><dict>' . "\n";
        echo '<key>assets</key><array>' . "\n";
        // Software asset
        echo '<dict><key>kind</key><string>software-package</string>';
        echo '<key>url</key><string>' . $appUrl . '/install/download/' . $build['id'] . '</string></dict>' . "\n";
        echo '</array>' . "\n";
        echo '<key>metadata</key><dict>' . "\n";
        echo '<key>bundle-identifier</key><string>com.opsone.crewassist</string>' . "\n";
        echo '<key>bundle-version</key><string>' . e($build['version']) . '</string>' . "\n";
        echo '<key>kind</key><string>software</string>' . "\n";
        echo '<key>title</key><string>' . e($brand['product_name']) . '</string>' . "\n";
        echo '</dict></dict></array></dict></plist>';
        exit;
    }

    public function download(int $buildId): void {
        $user = currentUser();

        $build = AppBuild::find($buildId);
        if (!$build || !$build['file_path']) {
            http_response_code(404);
            echo 'Build not found';
            exit;
        }

        InstallLog::log('build_download', $user['id'] ?? null, currentTenantId(), $buildId);

        $filePath = storagePath('builds/' . $build['file_path']);
        if (!file_exists($filePath)) {
            http_response_code(404);
            echo 'Build file not found on server. Contact administrator.';
            exit;
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="OpsOne-v' . $build['version'] . '.ipa"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
}
