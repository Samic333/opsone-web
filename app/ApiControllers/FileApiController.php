<?php


/**
 * File API Controller — mobile file/content access
 */
class FileApiController {
    public function index(): void {
        $user = apiUser();
        $tenantId = apiTenantId();
        $roles = apiUserRoles();

        if (!AuthorizationService::isModuleEnabledForTenant('manuals', $tenantId)) {
            jsonResponse(['success' => true, 'files' => [], 'count' => 0, 'module_disabled' => true]);
        }

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

        if (!AuthorizationService::isModuleEnabledForTenant('manuals', $tenantId)) {
            jsonResponse(['error' => 'Module disabled'], 403);
        }

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

    // ─── POST /api/files/{id}/acknowledge ─────────────────────
    /**
     * Records that the authenticated user has acknowledged a file/manual.
     * The file must be published, require acknowledgement, and belong to this tenant.
     *
     * Response: { success: true, acknowledged_at: "..." }
     */
    public function acknowledge(int $id): void {
        $user     = apiUser();
        $tenantId = apiTenantId();

        if (!AuthorizationService::isModuleEnabledForTenant('manuals', $tenantId)) {
            jsonResponse(['error' => 'Module disabled'], 403);
            return;
        }

        $file = \FileModel::find($id);
        if (!$file || (int)$file['tenant_id'] !== $tenantId) {
            jsonResponse(['error' => 'File not found'], 404);
            return;
        }
        if ($file['status'] !== 'published') {
            jsonResponse(['error' => 'File is not published'], 403);
            return;
        }
        if (empty($file['requires_ack'])) {
            jsonResponse(['error' => 'This file does not require acknowledgement'], 422);
            return;
        }

        // dbNow() returns a SQL fragment ("NOW()" / "datetime('now')") meant
        // for interpolation, NOT for binding as a parameter. When passed as a
        // PDO parameter it's stored verbatim as the literal string. Use a
        // PHP-formatted UTC timestamp instead — works on both drivers.
        $now = date('Y-m-d H:i:s');

        // Upsert — re-acknowledging after a version change updates the record
        $existing = Database::fetch(
            "SELECT id, acknowledged_at, version FROM file_acknowledgements
             WHERE file_id = ? AND user_id = ?",
            [$id, $user['user_id']]
        );

        if (!$existing) {
            Database::insert(
                "INSERT INTO file_acknowledgements
                    (file_id, user_id, tenant_id, version, device_id, acknowledged_at)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$id, $user['user_id'], $tenantId, $file['version'], null, $now]
            );
        } else {
            // Update if re-acknowledging a new version
            Database::execute(
                "UPDATE file_acknowledgements
                 SET acknowledged_at = ?, version = ?
                 WHERE file_id = ? AND user_id = ?",
                [$now, $file['version'], $id, $user['user_id']]
            );
        }

        AuditLog::apiLog(
            'file_acknowledged',
            'file',
            $id,
            "File '{$file['title']}' v{$file['version']} acknowledged via iPad"
        );

        jsonResponse(['success' => true, 'acknowledged_at' => $now]);
    }
}
