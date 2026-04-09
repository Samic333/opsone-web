<?php
/**
 * FdmController — FDM file uploads and event management
 *
 * Accessible by: fdm_analyst, safety_officer, airline_admin, super_admin
 */
class FdmController {

    private const ALLOWED_ROLES = ['fdm_analyst', 'safety_officer', 'airline_admin', 'super_admin'];

    public function index(): void {
        RbacMiddleware::requireRole(self::ALLOWED_ROLES);
        $tenantId  = currentTenantId();
        $uploads   = FdmModel::allUploads($tenantId, 30);
        $summary   = FdmModel::summary($tenantId);
        $eventTypes = FdmModel::eventTypes();
        $severities = FdmModel::severities();

        $pageTitle    = 'FDM Data';
        $pageSubtitle = 'Flight Data Monitoring — Uploads & Events';
        $headerAction = '<a href="/fdm/upload" class="btn btn-primary">＋ Upload FDM File</a>';

        ob_start();
        require VIEWS_PATH . '/fdm/index.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function uploadForm(): void {
        RbacMiddleware::requireRole(self::ALLOWED_ROLES);

        $pageTitle    = 'Upload FDM Data';
        $pageSubtitle = 'Upload a CSV flight data file or log a manual event';

        ob_start();
        require VIEWS_PATH . '/fdm/upload.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function store(): void {
        RbacMiddleware::requireRole(self::ALLOWED_ROLES);

        if (!verifyCsrf()) {
            flash('error', 'Invalid security token.');
            redirect('/fdm/upload');
        }

        $tenantId = currentTenantId();
        $user     = currentUser();

        $flightDate   = trim($_POST['flight_date']   ?? '');
        $aircraftReg  = trim($_POST['aircraft_reg']  ?? '');
        $flightNumber = trim($_POST['flight_number'] ?? '');
        $notes        = trim($_POST['notes']         ?? '');

        $hasFile = isset($_FILES['fdm_file']) && $_FILES['fdm_file']['error'] === UPLOAD_ERR_OK;

        // Ensure storage dir exists
        $storageDir = STORAGE_PATH . '/fdm';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        if ($hasFile) {
            $file = $_FILES['fdm_file'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, ['csv', 'txt'])) {
                flash('error', 'Only CSV or TXT files are accepted.');
                redirect('/fdm/upload');
            }

            if ($file['size'] > 10 * 1024 * 1024) {
                flash('error', 'File too large. Maximum 10 MB.');
                redirect('/fdm/upload');
            }

            $storedName = 'fdm_' . $tenantId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = $storageDir . '/' . $storedName;

            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                flash('error', 'Failed to save file. Check server permissions.');
                redirect('/fdm/upload');
            }

            $uploadId = FdmModel::createUpload([
                'tenant_id'     => $tenantId,
                'uploaded_by'   => $user['id'],
                'filename'      => $storedName,
                'original_name' => $file['name'],
                'flight_date'   => $flightDate,
                'aircraft_reg'  => $aircraftReg,
                'flight_number' => $flightNumber,
                'status'        => 'pending',
                'notes'         => $notes,
            ]);

            // Parse and import events from CSV
            $count = FdmModel::importCsv($uploadId, $tenantId, $dest);
            FdmModel::updateEventCount($uploadId, $count);

            AuditLog::log('fdm_upload', 'fdm_upload', $uploadId, "Uploaded FDM file: {$file['name']} ({$count} events)");
            flash('success', "FDM file uploaded. {$count} event(s) imported.");
            redirect('/fdm/view/' . $uploadId);
        } else {
            // Manual event entry (no file uploaded)
            $eventType = trim($_POST['event_type'] ?? 'other');
            $severity  = trim($_POST['severity']   ?? 'medium');

            $uploadId = FdmModel::createUpload([
                'tenant_id'     => $tenantId,
                'uploaded_by'   => $user['id'],
                'filename'      => 'manual_' . time(),
                'original_name' => 'Manual Entry',
                'flight_date'   => $flightDate,
                'aircraft_reg'  => $aircraftReg,
                'flight_number' => $flightNumber,
                'status'        => 'processed',
                'notes'         => $notes,
            ]);

            FdmModel::createEvent([
                'tenant_id'      => $tenantId,
                'fdm_upload_id'  => $uploadId,
                'event_type'     => $eventType,
                'severity'       => $severity,
                'flight_date'    => $flightDate,
                'aircraft_reg'   => $aircraftReg,
                'flight_number'  => $flightNumber,
                'flight_phase'   => trim($_POST['flight_phase']   ?? ''),
                'parameter'      => trim($_POST['parameter']      ?? ''),
                'value_recorded' => trim($_POST['value_recorded'] ?? ''),
                'threshold'      => trim($_POST['threshold']      ?? ''),
                'notes'          => $notes,
            ]);
            FdmModel::updateEventCount($uploadId, 1);

            AuditLog::log('fdm_manual_event', 'fdm_event', $uploadId, "Manual FDM event: {$eventType} ({$severity})");
            flash('success', 'Event logged successfully.');
            redirect('/fdm/view/' . $uploadId);
        }
    }

    public function view(int $id): void {
        RbacMiddleware::requireRole(self::ALLOWED_ROLES);
        $tenantId = currentTenantId();
        $upload   = FdmModel::findUpload($id, $tenantId);

        if (!$upload) {
            flash('error', 'FDM record not found.');
            redirect('/fdm');
        }

        $events     = FdmModel::getEvents($id);
        $eventTypes = FdmModel::eventTypes();
        $severities = FdmModel::severities();

        $pageTitle    = 'FDM Record';
        $pageSubtitle = $upload['original_name'] . ($upload['flight_date'] ? ' — ' . $upload['flight_date'] : '');

        ob_start();
        require VIEWS_PATH . '/fdm/view.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function addEvent(int $id): void {
        RbacMiddleware::requireRole(self::ALLOWED_ROLES);

        if (!verifyCsrf()) {
            flash('error', 'Invalid security token.');
            redirect('/fdm/view/' . $id);
        }

        $tenantId = currentTenantId();
        $upload   = FdmModel::findUpload($id, $tenantId);
        if (!$upload) {
            flash('error', 'FDM record not found.');
            redirect('/fdm');
        }

        FdmModel::createEvent([
            'tenant_id'      => $tenantId,
            'fdm_upload_id'  => $id,
            'event_type'     => trim($_POST['event_type']     ?? 'other'),
            'severity'       => trim($_POST['severity']       ?? 'medium'),
            'flight_date'    => trim($_POST['flight_date']    ?? '') ?: $upload['flight_date'],
            'aircraft_reg'   => trim($_POST['aircraft_reg']   ?? '') ?: $upload['aircraft_reg'],
            'flight_number'  => trim($_POST['flight_number']  ?? '') ?: $upload['flight_number'],
            'flight_phase'   => trim($_POST['flight_phase']   ?? ''),
            'parameter'      => trim($_POST['parameter']      ?? ''),
            'value_recorded' => trim($_POST['value_recorded'] ?? ''),
            'threshold'      => trim($_POST['threshold']      ?? ''),
            'notes'          => trim($_POST['notes']          ?? ''),
        ]);

        $newCount = count(FdmModel::getEvents($id));
        FdmModel::updateEventCount($id, $newCount);

        flash('success', 'Event added.');
        redirect('/fdm/view/' . $id);
    }

    public function deleteEvent(int $uploadId, int $eventId): void {
        RbacMiddleware::requireRole(['fdm_analyst', 'airline_admin', 'super_admin']);

        if (!verifyCsrf()) {
            flash('error', 'Invalid security token.');
            redirect('/fdm/view/' . $uploadId);
        }

        $tenantId = currentTenantId();
        FdmModel::deleteEvent($eventId, $tenantId);

        $newCount = count(FdmModel::getEvents($uploadId));
        FdmModel::updateEventCount($uploadId, $newCount);

        flash('success', 'Event removed.');
        redirect('/fdm/view/' . $uploadId);
    }

    public function deleteUpload(int $id): void {
        RbacMiddleware::requireRole(['fdm_analyst', 'airline_admin', 'super_admin']);

        if (!verifyCsrf()) {
            flash('error', 'Invalid security token.');
            redirect('/fdm');
        }

        FdmModel::deleteUpload($id, currentTenantId());
        AuditLog::log('fdm_delete', 'fdm_upload', $id, "Deleted FDM upload #{$id}");
        flash('success', 'FDM record deleted.');
        redirect('/fdm');
    }
}
