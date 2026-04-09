<?php
/**
 * FdmModel — Flight Data Monitoring uploads and events
 */
class FdmModel {

    // ─── Metadata ─────────────────────────────────────────────────────────────

    public static function eventTypes(): array {
        return [
            'exceedance'              => ['label' => 'Parameter Exceedance', 'icon' => '⚡'],
            'hard_landing'            => ['label' => 'Hard Landing',         'icon' => '💥'],
            'unstabilised_approach'   => ['label' => 'Unstabilised Approach','icon' => '⚠'],
            'gpws'                    => ['label' => 'GPWS Alert',           'icon' => '🏔'],
            'tcas'                    => ['label' => 'TCAS RA',              'icon' => '✈'],
            'overspeed'               => ['label' => 'Overspeed',            'icon' => '🔴'],
            'tail_strike'             => ['label' => 'Tail Strike Risk',     'icon' => '⛔'],
            'windshear'               => ['label' => 'Windshear',            'icon' => '🌪'],
            'other'                   => ['label' => 'Other',                'icon' => '📋'],
        ];
    }

    public static function severities(): array {
        return [
            'low'      => ['label' => 'Low',      'color' => '#10b981'],
            'medium'   => ['label' => 'Medium',   'color' => '#f59e0b'],
            'high'     => ['label' => 'High',     'color' => '#ef4444'],
            'critical' => ['label' => 'Critical', 'color' => '#7c3aed'],
        ];
    }

    // ─── Uploads ──────────────────────────────────────────────────────────────

    public static function allUploads(int $tenantId, int $limit = 50): array {
        return Database::fetchAll(
            "SELECT f.*, u.name AS uploader_name
             FROM fdm_uploads f
             JOIN users u ON u.id = f.uploaded_by
             WHERE f.tenant_id = ?
             ORDER BY f.created_at DESC
             LIMIT ?",
            [$tenantId, $limit]
        );
    }

    public static function findUpload(int $id, int $tenantId): ?array {
        return Database::fetch(
            "SELECT f.*, u.name AS uploader_name
             FROM fdm_uploads f
             JOIN users u ON u.id = f.uploaded_by
             WHERE f.id = ? AND f.tenant_id = ?",
            [$id, $tenantId]
        );
    }

    public static function createUpload(array $data): int {
        return Database::insert(
            "INSERT INTO fdm_uploads (tenant_id, uploaded_by, filename, original_name, flight_date, aircraft_reg, flight_number, event_count, status, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['tenant_id'],
                $data['uploaded_by'],
                $data['filename'],
                $data['original_name'],
                $data['flight_date'] ?: null,
                $data['aircraft_reg'] ?: null,
                $data['flight_number'] ?: null,
                $data['event_count'] ?? 0,
                $data['status'] ?? 'processed',
                $data['notes'] ?: null,
            ]
        );
    }

    public static function updateEventCount(int $uploadId, int $count): void {
        Database::execute(
            "UPDATE fdm_uploads SET event_count = ?, status = 'processed' WHERE id = ?",
            [$count, $uploadId]
        );
    }

    public static function deleteUpload(int $id, int $tenantId): void {
        $upload = self::findUpload($id, $tenantId);
        if ($upload) {
            // Delete the stored file if it exists
            $path = STORAGE_PATH . '/fdm/' . $upload['filename'];
            if (file_exists($path)) {
                unlink($path);
            }
            Database::execute("DELETE FROM fdm_events  WHERE fdm_upload_id = ?", [$id]);
            Database::execute("DELETE FROM fdm_uploads WHERE id = ? AND tenant_id = ?", [$id, $tenantId]);
        }
    }

    // ─── Events ───────────────────────────────────────────────────────────────

    public static function getEvents(int $uploadId): array {
        return Database::fetchAll(
            "SELECT * FROM fdm_events WHERE fdm_upload_id = ? ORDER BY flight_date DESC, created_at DESC",
            [$uploadId]
        );
    }

    public static function allEvents(int $tenantId, int $limit = 100): array {
        return Database::fetchAll(
            "SELECT e.*, f.original_name AS upload_name
             FROM fdm_events e
             LEFT JOIN fdm_uploads f ON f.id = e.fdm_upload_id
             WHERE e.tenant_id = ?
             ORDER BY e.flight_date DESC, e.created_at DESC
             LIMIT ?",
            [$tenantId, $limit]
        );
    }

    public static function recentEvents(int $tenantId, int $limit = 10): array {
        return Database::fetchAll(
            "SELECT * FROM fdm_events WHERE tenant_id = ? ORDER BY created_at DESC LIMIT ?",
            [$tenantId, $limit]
        );
    }

    public static function createEvent(array $data): int {
        return Database::insert(
            "INSERT INTO fdm_events (tenant_id, fdm_upload_id, event_type, severity, flight_date, aircraft_reg, flight_number, flight_phase, parameter, value_recorded, threshold, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['tenant_id'],
                $data['fdm_upload_id'] ?: null,
                $data['event_type']    ?? 'other',
                $data['severity']      ?? 'medium',
                $data['flight_date']   ?: null,
                $data['aircraft_reg']  ?: null,
                $data['flight_number'] ?: null,
                $data['flight_phase']  ?: null,
                $data['parameter']     ?: null,
                isset($data['value_recorded']) && $data['value_recorded'] !== '' ? (float) $data['value_recorded'] : null,
                isset($data['threshold'])      && $data['threshold']      !== '' ? (float) $data['threshold']      : null,
                $data['notes'] ?: null,
            ]
        );
    }

    public static function deleteEvent(int $id, int $tenantId): void {
        Database::execute("DELETE FROM fdm_events WHERE id = ? AND tenant_id = ?", [$id, $tenantId]);
    }

    // ─── Summary stats ────────────────────────────────────────────────────────

    public static function summary(int $tenantId): array {
        $totalUploads = (int) Database::fetch(
            "SELECT COUNT(*) as c FROM fdm_uploads WHERE tenant_id = ?", [$tenantId]
        )['c'];

        $totalEvents = (int) Database::fetch(
            "SELECT COUNT(*) as c FROM fdm_events WHERE tenant_id = ?", [$tenantId]
        )['c'];

        $criticalHigh = (int) Database::fetch(
            "SELECT COUNT(*) as c FROM fdm_events WHERE tenant_id = ? AND severity IN ('critical','high')", [$tenantId]
        )['c'];

        $eventsByType = Database::fetchAll(
            "SELECT event_type, COUNT(*) as count FROM fdm_events WHERE tenant_id = ? GROUP BY event_type ORDER BY count DESC",
            [$tenantId]
        );

        return [
            'total_uploads'  => $totalUploads,
            'total_events'   => $totalEvents,
            'critical_high'  => $criticalHigh,
            'events_by_type' => $eventsByType,
        ];
    }

    // ─── CSV import ───────────────────────────────────────────────────────────

    /**
     * Parse a CSV file and insert fdm_events rows.
     * Expected columns (case-insensitive):
     *   flight_date, aircraft_reg, flight_number, event_type, severity,
     *   flight_phase, parameter, value_recorded, threshold, notes
     *
     * Returns count of events inserted.
     */
    public static function importCsv(int $uploadId, int $tenantId, string $filePath): int {
        if (!file_exists($filePath)) return 0;

        $handle = fopen($filePath, 'r');
        if (!$handle) return 0;

        $headers = null;
        $count   = 0;
        $validEventTypes = array_keys(self::eventTypes());
        $validSeverities = array_keys(self::severities());

        while (($row = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                $headers = array_map(fn($h) => strtolower(trim($h)), $row);
                continue;
            }

            if (count($row) < 2) continue;
            $data = array_combine($headers, array_pad($row, count($headers), ''));

            $eventType = trim($data['event_type'] ?? 'other');
            if (!in_array($eventType, $validEventTypes)) $eventType = 'other';

            $severity = trim($data['severity'] ?? 'medium');
            if (!in_array($severity, $validSeverities)) $severity = 'medium';

            self::createEvent([
                'tenant_id'      => $tenantId,
                'fdm_upload_id'  => $uploadId,
                'event_type'     => $eventType,
                'severity'       => $severity,
                'flight_date'    => trim($data['flight_date']    ?? ''),
                'aircraft_reg'   => trim($data['aircraft_reg']   ?? ''),
                'flight_number'  => trim($data['flight_number']  ?? ''),
                'flight_phase'   => trim($data['flight_phase']   ?? ''),
                'parameter'      => trim($data['parameter']      ?? ''),
                'value_recorded' => trim($data['value_recorded'] ?? ''),
                'threshold'      => trim($data['threshold']      ?? ''),
                'notes'          => trim($data['notes']          ?? ''),
            ]);
            $count++;
        }

        fclose($handle);
        return $count;
    }
}
