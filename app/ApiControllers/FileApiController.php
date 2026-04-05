<?php


/**
 * File API Controller — mobile file/content access
 */
class FileApiController {
    public function index(): void {
        $user = apiUser();
        $tenantId = apiTenantId();
        $roles = apiUserRoles();

        // Check device approval
        $deviceUuid = $_GET['device_uuid'] ?? '';
        if ($deviceUuid) {
            $device = \Device::findByUuid($deviceUuid, $user['user_id']);
            if ($device && $device['approval_status'] !== 'approved') {
                jsonResponse(['error' => 'Device is not approved', 'approval_status' => $device['approval_status']], 403);
            }
        }

        $files = \FileModel::forUserRoles($tenantId, $roles);

        $result = array_map(function($f) {
            return [
                'id' => $f['id'],
                'title' => $f['title'],
                'description' => $f['description'],
                'category' => $f['category_name'],
                'version' => $f['version'],
                'file_name' => $f['file_name'],
                'file_size' => $f['file_size'],
                'mime_type' => $f['mime_type'],
                'effective_date' => $f['effective_date'],
                'requires_ack' => (bool) $f['requires_ack'],
                'created_at' => $f['created_at'],
            ];
        }, $files);

        jsonResponse(['success' => true, 'files' => $result, 'count' => count($result)]);
    }

    public function download(int $id): void {
        $user = apiUser();
        $tenantId = apiTenantId();

        $file = \FileModel::find($id);
        if (!$file || $file['tenant_id'] != $tenantId) {
            jsonResponse(['error' => 'File not found'], 404);
        }

        if ($file['status'] !== 'published') {
            jsonResponse(['error' => 'File is not published'], 403);
        }

        $fullPath = storagePath($file['file_path']);
        if (!file_exists($fullPath)) {
            jsonResponse(['error' => 'File not found on server'], 404);
        }

        header('Content-Type: ' . ($file['mime_type'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . $file['file_name'] . '"');
        header('Content-Length: ' . filesize($fullPath));
        readfile($fullPath);
        exit;
    }
}
