<?php


/**
 * Sync API Controller — heartbeat / last-seen tracking
 */
class SyncApiController {
    public function heartbeat(): void {
        $user = apiUser();
        $tenantId = apiTenantId();
        $input = json_decode(file_get_contents('php://input'), true);
        $deviceUuid = $input['device_uuid'] ?? '';

        // Update device last sync
        if ($deviceUuid) {
            $device = \Device::findByUuid($deviceUuid, $user['user_id']);
            if ($device) {
                \Device::updateLastSync($device['id']);

                // Check if device is still approved
                if ($device['approval_status'] !== 'approved') {
                    jsonResponse([
                        'success' => false,
                        'device_approved' => false,
                        'approval_status' => $device['approval_status'],
                        'message' => 'Device access has been revoked or is pending',
                    ], 403);
                }
            }
        }

        // Log sync event
        \Database::insert(
            "INSERT INTO sync_events (tenant_id, user_id, device_id, event_type, ip_address)
             VALUES (?, ?, ?, 'heartbeat', ?)",
            [
                $tenantId, $user['user_id'],
                isset($device) ? ($device['id'] ?? null) : null,
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]
        );

        jsonResponse([
            'success' => true,
            'server_time' => date('c'),
            'device_approved' => true,
        ]);
    }
}
