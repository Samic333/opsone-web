<?php


/**
 * Device API Controller — mobile device registration and status check
 */
class DeviceApiController {
    public function register(): void {
        $input = json_decode(file_get_contents('php://input'), true);
        $user = apiUser();
        $tenantId = apiTenantId();

        $deviceUuid = trim($input['device_uuid'] ?? '');
        if (empty($deviceUuid)) {
            jsonResponse(['error' => 'device_uuid is required'], 400);
        }

        // Check if device already registered for this user
        $existing = \Device::findByUuid($deviceUuid, $user['user_id']);
        if ($existing) {
            // Link token to device
            $token = $GLOBALS['api_token'] ?? '';
            if ($token) {
                \Database::execute("UPDATE api_tokens SET device_id = ? WHERE token = ?", [$existing['id'], $token]);
            }

            jsonResponse([
                'success' => true,
                'device_id' => $existing['id'],
                'approval_status' => $existing['approval_status'],
                'message' => 'Device already registered',
            ]);
        }

        // Register new device
        $deviceId = \Device::register([
            'tenant_id' => $tenantId,
            'user_id' => $user['user_id'],
            'device_uuid' => $deviceUuid,
            'platform' => $input['platform'] ?? null,
            'model' => $input['model'] ?? null,
            'os_version' => $input['os_version'] ?? null,
            'app_version' => $input['app_version'] ?? null,
        ]);

        // Link token to device
        $token = $GLOBALS['api_token'] ?? '';
        if ($token) {
            \Database::execute("UPDATE api_tokens SET device_id = ? WHERE token = ?", [$deviceId, $token]);
        }

        $platform = $input['platform'] ?? 'unknown';
        \AuditLog::apiLog('Device Registered', 'device', $deviceId, "New device: $deviceUuid ($platform)");

        jsonResponse([
            'success' => true,
            'device_id' => $deviceId,
            'approval_status' => 'pending',
            'message' => 'Device registered and awaiting approval',
        ], 201);
    }

    public function status(): void {
        $user = apiUser();
        $deviceUuid = $_GET['device_uuid'] ?? '';

        if (empty($deviceUuid)) {
            // Return all devices for user
            $devices = \Device::forUser($user['user_id']);
            jsonResponse(['success' => true, 'devices' => $devices]);
        }

        $device = \Device::findByUuid($deviceUuid, $user['user_id']);
        if (!$device) {
            jsonResponse(['error' => 'Device not found', 'approval_status' => 'unknown'], 404);
        }

        $accessAllowed = $device['approval_status'] === 'approved';
        jsonResponse([
            'success' => true,
            'device_id' => $device['id'],
            'approval_status' => $device['approval_status'],
            'access_allowed' => $accessAllowed,
            'approved_at' => $device['approved_at'],
            'last_sync_at' => $device['last_sync_at'],
        ]);
    }
}
