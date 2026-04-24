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

        // Dev convenience: when the tenant has
        //   settings.auto_approve_simulator_devices = true
        // AND the registering device looks like an iOS Simulator, mark it
        // `approved` directly.  Production tenants stay opt-out.
        $autoApprove = $this->shouldAutoApproveSimulator($tenantId, $input);

        // Register new device
        $deviceId = \Device::register([
            'tenant_id'  => $tenantId,
            'user_id'    => $user['user_id'],
            'device_uuid'=> $deviceUuid,
            'platform'   => $input['platform']   ?? null,
            'model'      => $input['model']      ?? null,
            'os_version' => $input['os_version'] ?? null,
            'app_version'=> $input['app_version']?? null,
            'approval_status' => $autoApprove ? 'approved' : 'pending',
        ]);

        // Link token to device
        $token = $GLOBALS['api_token'] ?? '';
        if ($token) {
            \Database::execute("UPDATE api_tokens SET device_id = ? WHERE token = ?", [$deviceId, $token]);
        }

        $platform = $input['platform'] ?? 'unknown';
        $msg = $autoApprove
            ? "New device auto-approved (simulator): $deviceUuid ($platform)"
            : "New device: $deviceUuid ($platform)";
        \AuditLog::apiLog(
            $autoApprove ? 'Device Auto-Approved' : 'Device Registered',
            'device', $deviceId, $msg
        );

        jsonResponse([
            'success' => true,
            'device_id' => $deviceId,
            'approval_status' => $autoApprove ? 'approved' : 'pending',
            'message' => $autoApprove
                ? 'Device auto-approved (simulator)'
                : 'Device registered and awaiting approval',
        ], 201);
    }

    /**
     * Decide whether to auto-approve this device.  Guarded on BOTH:
     *   - tenant has explicit opt-in via `tenants.settings.auto_approve_simulator_devices`
     *   - device identifies itself as a simulator
     *
     * Both guards matter — the tenant flag without a simulator check would
     * auto-approve real production devices; the simulator check without the
     * tenant flag would auto-approve any fake payload.
     */
    private function shouldAutoApproveSimulator(int $tenantId, array $input): bool {
        try {
            $row = \Database::fetch("SELECT settings FROM tenants WHERE id = ?", [$tenantId]);
        } catch (\Throwable $e) {
            return false;
        }
        $settings = [];
        if ($row && !empty($row['settings'])) {
            $decoded = json_decode((string)$row['settings'], true);
            if (is_array($decoded)) $settings = $decoded;
        }
        if (empty($settings['auto_approve_simulator_devices'])) return false;

        $platform = strtolower((string)($input['platform']   ?? ''));
        $model    = strtolower((string)($input['model']      ?? ''));
        $isSim = str_contains($platform, 'simulator')
              || str_contains($model,    'simulator')
              || $platform === 'ios simulator';
        return $isSim;
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
