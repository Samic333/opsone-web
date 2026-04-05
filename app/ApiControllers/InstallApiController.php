<?php
/**
 * InstallApiController — API endpoints for install page data
 */
class InstallApiController {
    public function latestBuild(): void {
        $build = AppBuild::latest();
        if (!$build) {
            jsonResponse(['error' => 'No build available'], 404);
        }
        $brand = require CONFIG_PATH . '/branding.php';
        jsonResponse([
            'version'        => $build['version'],
            'build_number'   => $build['build_number'],
            'release_notes'  => $build['release_notes'],
            'min_os_version' => $build['min_os_version'],
            'created_at'     => $build['created_at'],
            'product_name'   => $brand['product_name'],
        ]);
    }

    public function notices(): void {
        $user = $_REQUEST['_api_user'] ?? null;
        if (!$user) {
            jsonResponse(['error' => 'Unauthorized'], 401);
        }
        $tenantId = $user['tenant_id'];
        $notices = Notice::allForTenant($tenantId, true);

        $result = array_map(function($n) {
            return [
                'id'           => (int) $n['id'],
                'title'        => $n['title'],
                'body'         => $n['body'],
                'priority'     => $n['priority'],
                'category'     => $n['category'],
                'author'       => $n['author_name'],
                'published_at' => $n['published_at'],
                'expires_at'   => $n['expires_at'],
            ];
        }, $notices);

        jsonResponse(['notices' => $result]);
    }

    public function syncManifest(): void {
        $user = $_REQUEST['_api_user'] ?? null;
        if (!$user) {
            jsonResponse(['error' => 'Unauthorized'], 401);
        }
        $tenantId = $user['tenant_id'];
        $roleSlugs = UserModel::getRoleSlugs($user['id']);

        // Get all published files for user's roles
        $files = FileModel::forUserRoles($tenantId, $roleSlugs);
        $notices = Notice::allForTenant($tenantId, true);

        $fileManifest = array_map(function($f) {
            return [
                'id'          => (int) $f['id'],
                'title'       => $f['title'],
                'category'    => $f['category_name'] ?? 'General',
                'version'     => $f['version'],
                'file_name'   => $f['file_name'],
                'file_size'   => (int) $f['file_size'],
                'mime_type'   => $f['mime_type'],
                'updated_at'  => $f['updated_at'],
            ];
        }, $files);

        $noticeManifest = array_map(function($n) {
            return [
                'id'           => (int) $n['id'],
                'title'        => $n['title'],
                'priority'     => $n['priority'],
                'published_at' => $n['published_at'],
            ];
        }, $notices);

        jsonResponse([
            'files'        => $fileManifest,
            'notices'      => $noticeManifest,
            'file_count'   => count($fileManifest),
            'notice_count' => count($noticeManifest),
            'generated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function appVersion(): void {
        $build = AppBuild::latest();
        $brand = require CONFIG_PATH . '/branding.php';
        jsonResponse([
            'product_name'   => $brand['product_name'],
            'latest_version' => $build ? $build['version'] : null,
            'build_number'   => $build ? $build['build_number'] : null,
            'min_os_version' => $build ? $build['min_os_version'] : '16.0',
            'update_available' => false,
        ]);
    }
}
