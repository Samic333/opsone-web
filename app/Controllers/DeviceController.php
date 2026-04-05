<?php
/**
 * DeviceController — device approval management
 */
class DeviceController {
    public function __construct() {
        RbacMiddleware::requireRole(['super_admin', 'airline_admin', 'hr']);
    }

    public function index(): void {
        $tenantId = currentTenantId();
        $statusFilter = $_GET['status'] ?? null;
        $devices = Device::allForTenant($tenantId, $statusFilter);
        $stats = Device::countByStatus($tenantId);
        $statsMap = [];
        foreach ($stats as $s) {
            $statsMap[$s['approval_status']] = (int) $s['count'];
        }
        require VIEWS_PATH . '/devices/index.php';
    }

    public function approve(int $id): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/devices');
        }
        $device = Device::find($id);
        if (!$device || $device['tenant_id'] != currentTenantId()) {
            flash('error', 'Device not found.');
            redirect('/devices');
        }
        Device::approve($id, currentUser()['id']);
        AuditLog::log('Approved Device', 'device', $id, "Approved device {$device['device_uuid']} for {$device['user_name']}");
        flash('success', "Device approved for {$device['user_name']}.");
        redirect('/devices');
    }

    public function reject(int $id): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/devices');
        }
        $device = Device::find($id);
        if (!$device || $device['tenant_id'] != currentTenantId()) {
            flash('error', 'Device not found.');
            redirect('/devices');
        }
        Device::reject($id, currentUser()['id']);
        AuditLog::log('Rejected Device', 'device', $id, "Rejected device {$device['device_uuid']} for {$device['user_name']}");
        flash('success', "Device rejected for {$device['user_name']}.");
        redirect('/devices');
    }

    public function revoke(int $id): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/devices');
        }
        $device = Device::find($id);
        if (!$device || $device['tenant_id'] != currentTenantId()) {
            flash('error', 'Device not found.');
            redirect('/devices');
        }
        Device::revoke($id, currentUser()['id']);
        AuditLog::log('Revoked Device', 'device', $id, "Revoked device {$device['device_uuid']} for {$device['user_name']}");
        flash('success', "Device revoked for {$device['user_name']}. API tokens invalidated.");
        redirect('/devices');
    }
}
