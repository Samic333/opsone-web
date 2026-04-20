<?php
/**
 * SafetyReportModel — Phase 1 Safety Reporting
 *
 * Covers safety_reports, safety_report_threads, safety_report_attachments,
 * safety_report_status_history, safety_report_assignments,
 * safety_publications, safety_publication_audiences, safety_module_settings.
 */
class SafetyReportModel {

    // ─── Report Type Constants ─────────────────────────────────────────────────

    const TYPES = [
        'general_hazard'           => 'General Hazard Report',
        'flight_crew_occurrence'   => 'Flight Crew Occurrence and Hazard Report',
        'maintenance_engineering'  => 'Maintenance Engineering Report',
        'ground_ops'               => 'Ground Ops Occurrence and Hazard Report',
        'quality'                  => 'Quality Form',
        'hse'                      => 'HSE Report',
        'tcas'                     => 'TCAS Report Form',
        'environmental'            => 'Environmental Incident Report',
        'frat'                     => 'FRAT',
    ];

    // ─── Status Constants ──────────────────────────────────────────────────────

    const STATUSES = [
        'draft',
        'submitted',
        'under_review',
        'investigation',
        'action_in_progress',
        'closed',
        'reopened',
    ];

    const STATUS_DRAFT       = 'draft';
    const STATUS_SUBMITTED   = 'submitted';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_INVESTIGATION = 'investigation';
    const STATUS_ACTION      = 'action_in_progress';
    const STATUS_CLOSED      = 'closed';
    const STATUS_REOPENED    = 'reopened';

    // Legacy alias kept for backward compatibility with old controller
    const STATUS_REVIEW      = 'under_review';
    const STATUS_INVESTIG    = 'investigation';

    // ─── Role Visibility Per Type ──────────────────────────────────────────────

    const TYPE_ROLES = [
        'general_hazard'          => ['all'],
        'flight_crew_occurrence'  => ['pilot', 'captain', 'first_officer', 'safety_manager', 'safety_staff', 'airline_admin'],
        'maintenance_engineering' => ['engineer', 'maintenance_manager', 'safety_manager', 'safety_staff', 'airline_admin'],
        'ground_ops'              => ['ground_ops', 'dispatcher', 'safety_manager', 'safety_staff', 'airline_admin'],
        'quality'                 => ['quality_manager', 'safety_manager', 'safety_staff', 'airline_admin'],
        'hse'                     => ['all'],
        'tcas'                    => ['pilot', 'captain', 'first_officer', 'safety_manager', 'airline_admin'],
        'environmental'           => ['all'],
        'frat'                    => ['pilot', 'captain', 'first_officer', 'dispatcher', 'airline_admin'],
    ];

    // ─── Reference Number ─────────────────────────────────────────────────────

    /**
     * Generate a unique reference number in the format SR-YYYY-NNNNN.
     * Uses a COUNT to derive the next sequential number for the tenant/year.
     */
    public static function generateReferenceNo(int $tenantId): string {
        $year   = date('Y');
        $prefix = "SR-{$year}-";

        $row  = Database::fetch(
            "SELECT COUNT(id) AS cnt FROM safety_reports
              WHERE tenant_id = ? AND reference_no LIKE ?",
            [$tenantId, $prefix . '%']
        );
        $next = ((int) ($row['cnt'] ?? 0)) + 1;
        return $prefix . sprintf('%05d', $next);
    }

    // Legacy wrapper used by old controller
    public static function generateReference(int $tenantId, string $type = ''): string {
        return self::generateReferenceNo($tenantId);
    }

    // ─── Core Write Actions ───────────────────────────────────────────────────

    /**
     * Insert a fully-submitted report.
     * Sets status = 'submitted', submitted_at = NOW().
     *
     * @return int  Inserted row ID
     */
    public static function submit(int $tenantId, array $data): int {
        $ref = self::generateReferenceNo($tenantId);

        return Database::insert(
            "INSERT INTO safety_reports
                (tenant_id, reference_no, report_type, reporter_id, is_anonymous,
                 event_date, event_utc_time, event_local_time,
                 location_name, icao_code, occurrence_type, event_type,
                 initial_risk_score, aircraft_registration, call_sign,
                 title, description, severity, extra_fields,
                 reporter_position, template_version,
                 status, is_draft, submitted_at)
             VALUES
                (?, ?, ?, ?, ?,
                 ?, ?, ?,
                 ?, ?, ?, ?,
                 ?, ?, ?,
                 ?, ?, ?, ?,
                 ?, ?,
                 'submitted', 0, NOW())",
            [
                $tenantId,
                $ref,
                $data['report_type']          ?? 'general_hazard',
                $data['reporter_id']          ?? null,
                !empty($data['is_anonymous']) ? 1 : 0,
                !empty($data['event_date'])      ? $data['event_date']      : null,
                !empty($data['event_utc_time'])  ? $data['event_utc_time']  : null,
                !empty($data['event_local_time'])? $data['event_local_time']: null,
                $data['location_name']        ?? null,
                $data['icao_code']            ?? null,
                $data['occurrence_type']      ?? 'occurrence',
                $data['event_type']           ?? null,
                isset($data['initial_risk_score']) ? (int) $data['initial_risk_score'] : null,
                $data['aircraft_registration'] ?? null,
                $data['call_sign']            ?? null,
                $data['title'],
                $data['description'],
                $data['severity']             ?? 'unassigned',
                isset($data['extra_fields'])
                    ? (is_array($data['extra_fields']) ? json_encode($data['extra_fields']) : $data['extra_fields'])
                    : null,
                $data['reporter_position']    ?? null,
                $data['template_version']     ?? 1,
            ]
        );
    }

    /**
     * Insert a draft report (is_draft = 1, status = 'draft').
     *
     * @return int  Inserted row ID
     */
    public static function saveDraft(int $tenantId, array $data): int {
        $ref = self::generateReferenceNo($tenantId);

        return Database::insert(
            "INSERT INTO safety_reports
                (tenant_id, reference_no, report_type, reporter_id, is_anonymous,
                 event_date, event_utc_time, event_local_time,
                 location_name, icao_code, occurrence_type, event_type,
                 initial_risk_score, aircraft_registration, call_sign,
                 title, description, severity, extra_fields,
                 reporter_position, template_version,
                 status, is_draft)
             VALUES
                (?, ?, ?, ?, ?,
                 ?, ?, ?,
                 ?, ?, ?, ?,
                 ?, ?, ?,
                 ?, ?, ?, ?,
                 ?, ?,
                 'draft', 1)",
            [
                $tenantId,
                $ref,
                $data['report_type']          ?? 'general_hazard',
                $data['reporter_id']          ?? null,
                !empty($data['is_anonymous']) ? 1 : 0,
                !empty($data['event_date'])      ? $data['event_date']      : null,
                !empty($data['event_utc_time'])  ? $data['event_utc_time']  : null,
                !empty($data['event_local_time'])? $data['event_local_time']: null,
                $data['location_name']        ?? null,
                $data['icao_code']            ?? null,
                $data['occurrence_type']      ?? 'occurrence',
                $data['event_type']           ?? null,
                isset($data['initial_risk_score']) ? (int) $data['initial_risk_score'] : null,
                $data['aircraft_registration'] ?? null,
                $data['call_sign']            ?? null,
                $data['title']                ?? '',
                $data['description']          ?? '',
                $data['severity']             ?? 'unassigned',
                isset($data['extra_fields'])
                    ? (is_array($data['extra_fields']) ? json_encode($data['extra_fields']) : $data['extra_fields'])
                    : null,
                $data['reporter_position']    ?? null,
                $data['template_version']     ?? 1,
            ]
        );
    }

    /**
     * Update an existing draft. Only possible if the report is still a draft
     * and the reporter_id matches the requesting user.
     */
    public static function updateDraft(int $tenantId, int $id, int $userId, array $data): bool {
        $existing = Database::fetch(
            "SELECT id, is_draft, reporter_id FROM safety_reports WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$existing || !$existing['is_draft'] || (int) $existing['reporter_id'] !== $userId) {
            return false;
        }

        $allowed = [
            'report_type', 'is_anonymous', 'event_date', 'event_utc_time',
            'event_local_time', 'location_name', 'icao_code', 'occurrence_type',
            'event_type', 'initial_risk_score', 'aircraft_registration',
            'call_sign', 'title', 'description', 'severity', 'extra_fields',
            'reporter_position', 'template_version',
        ];

        $sets   = [];
        $params = [];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $sets[]   = "`{$col}` = ?";
                $val      = $data[$col];
                if ($col === 'extra_fields' && is_array($val)) {
                    $val = json_encode($val);
                }
                $params[] = $val;
            }
        }

        if (empty($sets)) return true; // nothing to do

        $params[] = $id;
        $params[] = $tenantId;

        Database::execute(
            "UPDATE safety_reports SET " . implode(', ', $sets) . " WHERE id = ? AND tenant_id = ?",
            $params
        );
        return true;
    }

    // ─── Fetching ──────────────────────────────────────────────────────────────

    /**
     * Find a single report with reporter and assignee names.
     */
    public static function find(int $tenantId, int $id): ?array {
        $row = Database::fetch(
            "SELECT r.*,
                    u.name         AS reporter_name,
                    u.employee_id  AS reporter_employee_id,
                    a.name         AS assigned_to_name
               FROM safety_reports r
          LEFT JOIN users u ON u.id = r.reporter_id
          LEFT JOIN users a ON a.id = r.assigned_to
              WHERE r.id = ? AND r.tenant_id = ?",
            [$id, $tenantId]
        );

        if ($row && $row['is_anonymous']) {
            $row['reporter_name']        = 'Anonymous';
            $row['reporter_employee_id'] = null;
        }
        return $row ?: null;
    }

    /**
     * All non-draft reports for a tenant with optional status and field filters.
     *
     * @param array $filters  Keys: type, assigned_to, severity, date_from, date_to
     */
    public static function allForTenant(
        int    $tenantId,
        string $statusFilter = 'all',
        array  $filters      = []
    ): array {
        $sql    = "SELECT r.*,
                          u.name AS reporter_name,
                          a.name AS assigned_to_name
                     FROM safety_reports r
                LEFT JOIN users u ON u.id = r.reporter_id
                LEFT JOIN users a ON a.id = r.assigned_to
                    WHERE r.tenant_id = ?
                      AND r.is_draft  = 0";
        $params = [$tenantId];

        // Status filter
        if ($statusFilter && $statusFilter !== 'all') {
            if ($statusFilter === 'open') {
                $sql .= " AND r.status != 'closed'";
            } else {
                $sql .= " AND r.status = ?";
                $params[] = $statusFilter;
            }
        }

        // Additional filters
        if (!empty($filters['type'])) {
            $sql .= " AND r.report_type = ?";
            $params[] = $filters['type'];
        }
        if (!empty($filters['assigned_to'])) {
            $sql .= " AND r.assigned_to = ?";
            $params[] = (int) $filters['assigned_to'];
        }
        if (!empty($filters['severity'])) {
            $sql .= " AND r.severity = ?";
            $params[] = $filters['severity'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND r.event_date >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND r.event_date <= ?";
            $params[] = $filters['date_to'];
        }

        $sql .= " ORDER BY r.created_at DESC";

        $rows = Database::fetchAll($sql, $params);
        foreach ($rows as &$r) {
            if ($r['is_anonymous']) {
                $r['reporter_name'] = 'Anonymous';
            }
        }
        return $rows;
    }

    /**
     * Submitted/active reports for a specific reporter (their own submissions).
     */
    public static function forUser(int $tenantId, int $userId): array {
        return Database::fetchAll(
            "SELECT r.*,
                    a.name AS assigned_to_name
               FROM safety_reports r
          LEFT JOIN users a ON a.id = r.assigned_to
              WHERE r.tenant_id  = ?
                AND r.reporter_id = ?
                AND r.is_draft    = 0
           ORDER BY r.created_at DESC",
            [$tenantId, $userId]
        );
    }

    /**
     * Drafts belonging to a specific reporter.
     */
    public static function draftsForUser(int $tenantId, int $userId): array {
        return Database::fetchAll(
            "SELECT * FROM safety_reports
              WHERE tenant_id   = ?
                AND reporter_id = ?
                AND is_draft    = 1
           ORDER BY updated_at DESC",
            [$tenantId, $userId]
        );
    }

    // ─── Status & Assignment ───────────────────────────────────────────────────

    /**
     * Update report status, record history row, and optionally set closed_at.
     */
    public static function updateStatus(
        int     $tenantId,
        int     $id,
        int     $userId,
        string  $newStatus,
        ?string $comment = null
    ): bool {
        $report = Database::fetch(
            "SELECT id, status FROM safety_reports WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );
        if (!$report) return false;

        $oldStatus = $report['status'];

        // Build update
        $sets   = ["status = ?"];
        $params = [$newStatus];

        if ($newStatus === self::STATUS_CLOSED) {
            $sets[]   = "closed_at = NOW()";
        }

        $params[] = $id;
        $params[] = $tenantId;

        Database::execute(
            "UPDATE safety_reports SET " . implode(', ', $sets) . " WHERE id = ? AND tenant_id = ?",
            $params
        );

        // Record history
        Database::insert(
            "INSERT INTO safety_report_status_history
                (report_id, changed_by, from_status, to_status, comment)
             VALUES (?, ?, ?, ?, ?)",
            [$id, $userId, $oldStatus, $newStatus, $comment]
        );

        return true;
    }

    /**
     * Assign a report to a user (or un-assign with null).
     * Records an assignment history row.
     */
    public static function assign(
        int  $tenantId,
        int  $id,
        int  $assignedBy,
        ?int $assignedTo
    ): bool {
        $report = Database::fetch(
            "SELECT id FROM safety_reports WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );
        if (!$report) return false;

        Database::execute(
            "UPDATE safety_reports SET assigned_to = ? WHERE id = ? AND tenant_id = ?",
            [$assignedTo, $id, $tenantId]
        );

        Database::insert(
            "INSERT INTO safety_report_assignments
                (report_id, assigned_by, assigned_to)
             VALUES (?, ?, ?)",
            [$id, $assignedBy, $assignedTo]
        );

        return true;
    }

    // ─── Threads ───────────────────────────────────────────────────────────────

    /**
     * Add a thread entry (public reply or internal note).
     *
     * @return int  Inserted row ID
     */
    public static function addThread(
        int    $reportId,
        int    $authorId,
        string $body,
        bool   $isInternal = false,
        ?int   $parentId   = null
    ): int {
        return Database::insert(
            "INSERT INTO safety_report_threads
                (report_id, author_id, body, is_internal, parent_id)
             VALUES (?, ?, ?, ?, ?)",
            [$reportId, $authorId, $body, $isInternal ? 1 : 0, $parentId]
        );
    }

    /**
     * Fetch thread entries for a report, optionally including internal notes.
     * Returns author name from users table.
     */
    public static function getThreads(int $reportId, bool $includeInternal = false): array {
        $sql    = "SELECT t.*, u.name AS author_name
                     FROM safety_report_threads t
                LEFT JOIN users u ON u.id = t.author_id
                    WHERE t.report_id = ?";
        $params = [$reportId];

        if (!$includeInternal) {
            $sql .= " AND t.is_internal = 0";
        }

        $sql .= " ORDER BY t.created_at ASC";
        return Database::fetchAll($sql, $params);
    }

    // ─── Attachments ───────────────────────────────────────────────────────────

    /**
     * Add an attachment record.
     *
     * @param array $fileData  Keys: file_name, file_path, file_type, file_size
     * @return int  Inserted row ID
     */
    public static function addAttachment(
        int   $reportId,
        int   $uploadedBy,
        array $fileData,
        ?int  $threadId = null
    ): int {
        return Database::insert(
            "INSERT INTO safety_report_attachments
                (report_id, thread_id, uploaded_by, file_name, file_path, file_type, file_size)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $reportId,
                $threadId,
                $uploadedBy,
                $fileData['file_name'],
                $fileData['file_path'],
                $fileData['file_type'],
                $fileData['file_size'] ?? 0,
            ]
        );
    }

    /**
     * Get all attachments for a report.
     */
    public static function getAttachments(int $reportId): array {
        return Database::fetchAll(
            "SELECT a.*, u.name AS uploader_name
               FROM safety_report_attachments a
          LEFT JOIN users u ON u.id = a.uploaded_by
              WHERE a.report_id = ?
           ORDER BY a.created_at ASC",
            [$reportId]
        );
    }

    // ─── Status History ────────────────────────────────────────────────────────

    /**
     * Get the status change history for a report.
     */
    public static function getStatusHistory(int $reportId): array {
        return Database::fetchAll(
            "SELECT h.*, u.name AS changed_by_name
               FROM safety_report_status_history h
          LEFT JOIN users u ON u.id = h.changed_by
              WHERE h.report_id = ?
           ORDER BY h.created_at ASC",
            [$reportId]
        );
    }

    // ─── Publications ─────────────────────────────────────────────────────────

    /**
     * List publications for a tenant, filtered by status.
     */
    public static function getPublications(int $tenantId, string $status = 'published'): array {
        $sql    = "SELECT p.*, u.name AS author_name
                     FROM safety_publications p
                LEFT JOIN users u ON u.id = p.created_by
                    WHERE p.tenant_id = ?";
        $params = [$tenantId];

        if ($status !== 'all') {
            $sql .= " AND p.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY p.created_at DESC";
        return Database::fetchAll($sql, $params);
    }

    /**
     * Find a single publication. Returns null if not found or tenant mismatch.
     */
    public static function getPublication(int $tenantId, int $id): ?array {
        $row = Database::fetch(
            "SELECT p.*, u.name AS author_name
               FROM safety_publications p
          LEFT JOIN users u ON u.id = p.created_by
              WHERE p.id = ? AND p.tenant_id = ?",
            [$id, $tenantId]
        );
        return $row ?: null;
    }

    /**
     * Insert or update a safety publication.
     *
     * @return int  Inserted row ID
     */
    public static function savePublication(int $tenantId, int $createdBy, array $data): int {
        return Database::insert(
            "INSERT INTO safety_publications
                (tenant_id, created_by, title, summary, content, related_report_id, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $tenantId,
                $createdBy,
                $data['title'],
                $data['summary']           ?? null,
                $data['content'],
                !empty($data['related_report_id']) ? (int) $data['related_report_id'] : null,
                $data['status']            ?? 'draft',
            ]
        );
    }

    /**
     * Publish a publication (set status = 'published', published_at = NOW()).
     */
    public static function publishPublication(int $id, int $tenantId): bool {
        $rows = Database::execute(
            "UPDATE safety_publications
                SET status = 'published', published_at = NOW()
              WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );
        return $rows > 0;
    }

    // ─── Module Settings ───────────────────────────────────────────────────────

    /**
     * Return tenant safety settings, creating a default row if missing.
     */
    public static function getSettings(int $tenantId): array {
        $row = Database::fetch(
            "SELECT * FROM safety_module_settings WHERE tenant_id = ?",
            [$tenantId]
        );

        if (!$row) {
            // Create default
            $defaultTypes = json_encode([
                'general_hazard','flight_crew_occurrence','maintenance_engineering',
                'ground_ops','quality','hse','tcas','environmental','frat',
            ]);
            Database::insert(
                "INSERT IGNORE INTO safety_module_settings (tenant_id, enabled_types)
                 VALUES (?, ?)",
                [$tenantId, $defaultTypes]
            );
            $row = Database::fetch(
                "SELECT * FROM safety_module_settings WHERE tenant_id = ?",
                [$tenantId]
            );
        }

        // Decode JSON field
        if ($row && is_string($row['enabled_types'])) {
            $row['enabled_types'] = json_decode($row['enabled_types'], true) ?? [];
        }

        return $row ?? [];
    }

    /**
     * Update safety module settings for a tenant.
     */
    public static function updateSettings(int $tenantId, array $data): bool {
        // Ensure row exists
        self::getSettings($tenantId);

        $allowed = [
            'enabled_types', 'allow_anonymous', 'require_aircraft_reg',
            'risk_matrix_enabled', 'retention_days',
        ];

        $sets   = [];
        $params = [];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $sets[]   = "`{$col}` = ?";
                $val      = $data[$col];
                if ($col === 'enabled_types' && is_array($val)) {
                    $val = json_encode($val);
                }
                $params[] = $val;
            }
        }

        if (empty($sets)) return true;

        $params[] = $tenantId;
        Database::execute(
            "UPDATE safety_module_settings SET " . implode(', ', $sets) . " WHERE tenant_id = ?",
            $params
        );
        return true;
    }

    // ─── Corrective Actions ───────────────────────────────────────────────────

    /**
     * Create a corrective action linked to a report.
     *
     * Required keys in $data: title
     * Optional keys: description, assigned_to, assigned_role, due_date
     *
     * @return int  Inserted row ID
     */
    public static function addAction(int $tenantId, int $reportId, int $assignedBy, array $data): int {
        return Database::insert(
            "INSERT INTO safety_actions
                (tenant_id, report_id, assigned_by, title, description,
                 assigned_to, assigned_role, due_date)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $tenantId,
                $reportId,
                $assignedBy,
                $data['title'],
                $data['description']   ?? null,
                !empty($data['assigned_to'])   ? (int) $data['assigned_to']   : null,
                !empty($data['assigned_role']) ? $data['assigned_role']       : null,
                !empty($data['due_date'])      ? $data['due_date']            : null,
            ]
        );
    }

    /**
     * Get all actions for a report, joined with assignee and assigner names.
     */
    public static function getActions(int $reportId, int $tenantId): array {
        return Database::fetchAll(
            "SELECT sa.*,
                    u.name  AS assignee_name,
                    ab.name AS assigned_by_name
               FROM safety_actions sa
          LEFT JOIN users u  ON u.id  = sa.assigned_to
          LEFT JOIN users ab ON ab.id = sa.assigned_by
              WHERE sa.report_id = ? AND sa.tenant_id = ?
           ORDER BY sa.created_at DESC",
            [$reportId, $tenantId]
        );
    }

    /**
     * Update fields on a corrective action.
     * Only fields present in $data are updated.
     * If status is set to 'completed', completed_at is set automatically.
     */
    public static function updateAction(int $id, int $tenantId, array $data): bool {
        $allowed = ['status', 'title', 'description', 'assigned_to', 'due_date', 'completed_at'];

        $sets   = [];
        $params = [];

        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $sets[]   = "`{$col}` = ?";
                $params[] = $data[$col];
            }
        }

        // Auto-set completed_at when marking completed (unless caller supplied it)
        if (isset($data['status']) && $data['status'] === 'completed' && !array_key_exists('completed_at', $data)) {
            $sets[]   = "`completed_at` = NOW()";
        }

        if (empty($sets)) return true;

        $params[] = $id;
        $params[] = $tenantId;

        Database::execute(
            "UPDATE safety_actions SET " . implode(', ', $sets) . " WHERE id = ? AND tenant_id = ?",
            $params
        );
        return true;
    }

    /**
     * Get all actions for a tenant, optionally filtered by status.
     * Joined with report reference_no, report title, and assignee name.
     */
    public static function tenantActions(int $tenantId, string $statusFilter = 'all'): array {
        $sql    = "SELECT sa.*,
                          sr.reference_no,
                          sr.title AS report_title,
                          u.name   AS assignee_name
                     FROM safety_actions sa
                LEFT JOIN safety_reports sr ON sr.id = sa.report_id
                LEFT JOIN users u           ON u.id  = sa.assigned_to
                    WHERE sa.tenant_id = ?";
        $params = [$tenantId];

        if ($statusFilter && $statusFilter !== 'all') {
            $sql .= " AND sa.status = ?";
            $params[] = $statusFilter;
        }

        $sql .= " ORDER BY sa.due_date ASC, sa.created_at DESC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Get all actions assigned to a specific user within a tenant.
     */
    public static function actionsForUser(int $tenantId, int $userId): array {
        return Database::fetchAll(
            "SELECT sa.*,
                    sr.reference_no,
                    sr.title AS report_title,
                    u.name   AS assignee_name
               FROM safety_actions sa
          LEFT JOIN safety_reports sr ON sr.id = sa.report_id
          LEFT JOIN users u           ON u.id  = sa.assigned_to
              WHERE sa.tenant_id  = ?
                AND sa.assigned_to = ?
           ORDER BY sa.due_date ASC, sa.created_at DESC",
            [$tenantId, $userId]
        );
    }

    // ─── Statistics ────────────────────────────────────────────────────────────

    /**
     * Return report counts broken down by status and type for a tenant.
     * Only counts non-draft reports.
     *
     * @return array{
     *     total: int,
     *     open: int,
     *     closed: int,
     *     by_status: array<string,int>,
     *     by_type: array<string,int>
     * }
     */
    public static function stats(int $tenantId): array {
        $byStatus = Database::fetchAll(
            "SELECT status, COUNT(*) AS cnt
               FROM safety_reports
              WHERE tenant_id = ? AND is_draft = 0
           GROUP BY status",
            [$tenantId]
        );

        $byType = Database::fetchAll(
            "SELECT report_type, COUNT(*) AS cnt
               FROM safety_reports
              WHERE tenant_id = ? AND is_draft = 0
           GROUP BY report_type",
            [$tenantId]
        );

        $statusMap = [];
        $total     = 0;
        $open      = 0;
        $closed    = 0;

        foreach ($byStatus as $row) {
            $cnt                          = (int) $row['cnt'];
            $statusMap[$row['status']]    = $cnt;
            $total                       += $cnt;
            if ($row['status'] === self::STATUS_CLOSED) {
                $closed += $cnt;
            } else {
                $open   += $cnt;
            }
        }

        $typeMap = [];
        foreach ($byType as $row) {
            $typeMap[$row['report_type']] = (int) $row['cnt'];
        }

        // ── Action stats ──────────────────────────────────────────────────────
        $overdueRow = Database::fetch(
            "SELECT COUNT(*) AS cnt FROM safety_actions
              WHERE tenant_id = ? AND status = 'overdue'",
            [$tenantId]
        );
        $openActionsRow = Database::fetch(
            "SELECT COUNT(*) AS cnt FROM safety_actions
              WHERE tenant_id = ? AND status IN ('open','in_progress')",
            [$tenantId]
        );

        // ── By severity (submitted reports only) ─────────────────────────────
        // initial_risk_code uses letters A-E (consequence/likelihood columns)
        // and numbers 1-5 (row axis). We group by the raw initial_risk_score
        // for simplicity; views may interpret the mapping.
        $bySeverity = Database::fetchAll(
            "SELECT severity, COUNT(*) AS cnt
               FROM safety_reports
              WHERE tenant_id = ? AND is_draft = 0
                AND status != 'draft'
           GROUP BY severity",
            [$tenantId]
        );

        $severityMap = [];
        foreach ($bySeverity as $row) {
            $severityMap[$row['severity']] = (int) $row['cnt'];
        }

        return [
            'total'           => $total,
            'open'            => $open,
            'closed'          => $closed,
            'by_status'       => $statusMap,
            'by_type'         => $typeMap,
            'overdue_actions' => (int) ($overdueRow['cnt']      ?? 0),
            'open_actions'    => (int) ($openActionsRow['cnt']  ?? 0),
            'by_severity'     => $severityMap,
        ];
    }

    // ─── Legacy Compatibility ─────────────────────────────────────────────────

    /**
     * Legacy: get raw update entries (safety_report_updates table).
     * Kept for backward-compat; prefer getStatusHistory() / getThreads().
     */
    public static function getUpdates(int $reportId): array {
        return Database::fetchAll(
            "SELECT u.*, us.name AS user_name
               FROM safety_report_updates u
          LEFT JOIN users us ON us.id = u.user_id
              WHERE u.report_id = ?
           ORDER BY u.created_at ASC",
            [$reportId]
        );
    }

    /**
     * Legacy: add a raw update entry.
     * Kept for backward-compat; prefer updateStatus() / assign().
     */
    public static function addUpdate(int $tenantId, int $reportId, int $userId, array $update): void {
        Database::insert(
            "INSERT INTO safety_report_updates
                (report_id, user_id, status_change, severity_change, comment)
             VALUES (?, ?, ?, ?, ?)",
            [
                $reportId,
                $userId,
                $update['status_change']   ?? null,
                $update['severity_change'] ?? null,
                $update['comment']         ?? null,
            ]
        );

        $sets   = [];
        $params = [];
        if (!empty($update['status_change'])) {
            $sets[]   = "status = ?";
            $params[] = $update['status_change'];
        }
        if (!empty($update['severity_change'])) {
            $sets[]   = "severity = ?";
            $params[] = $update['severity_change'];
        }
        if (array_key_exists('assigned_to', $update)) {
            $sets[]   = "assigned_to = ?";
            $params[] = $update['assigned_to'];
        }

        if ($sets) {
            $params[] = $reportId;
            $params[] = $tenantId;
            Database::execute(
                "UPDATE safety_reports SET " . implode(', ', $sets) . " WHERE id = ? AND tenant_id = ?",
                $params
            );
        }
    }
}
