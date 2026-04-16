<?php
/**
 * SafetyReportModel — Handles Air Safety Reports, Hazards, Incidents
 */
class SafetyReportModel {

    // ─── Constants ──────────────────────────────────────────────
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_REVIEW    = 'under_review';
    public const STATUS_INVESTIG  = 'investigation';
    public const STATUS_CLOSED    = 'closed';

    public const SEVERITY_LOW     = 'low';
    public const SEVERITY_MED     = 'medium';
    public const SEVERITY_HIGH    = 'high';
    public const SEVERITY_CRIT    = 'critical';
    public const SEVERITY_UNASS   = 'unassigned';

    // ─── Fetching ──────────────────────────────────────────────

    public static function allForTenant(int $tenantId, ?string $statusFilter = null): array {
        $sql = "SELECT r.*,
                       u.name AS reporter_name,
                       a.name AS assignee_name
                FROM safety_reports r
                LEFT JOIN users u ON r.reporter_id = u.id
                LEFT JOIN users a ON r.assigned_to = a.id
                WHERE r.tenant_id = ?";
        $params = [$tenantId];

        if ($statusFilter && $statusFilter !== 'all') {
            if ($statusFilter === 'open') {
                $sql .= " AND r.status != 'closed'";
            } else {
                $sql .= " AND r.status = ?";
                $params[] = $statusFilter;
            }
        }
        $sql .= " ORDER BY r.created_at DESC";
        
        $reports = Database::fetchAll($sql, $params);
        foreach ($reports as &$r) {
            if ($r['is_anonymous']) {
                $r['reporter_name'] = 'Anonymous';
            }
        }
        return $reports;
    }

    public static function forUser(int $tenantId, int $userId): array {
        return Database::fetchAll(
            "SELECT * FROM safety_reports
             WHERE tenant_id = ? AND (reporter_id = ? OR assigned_to = ?)
             ORDER BY created_at DESC",
            [$tenantId, $userId, $userId]
        );
    }

    public static function find(int $tenantId, int $id): ?array {
        $r = Database::fetch(
            "SELECT r.*,
                    u.name AS reporter_name,
                    u.employee_id AS reporter_employee_id,
                    a.name AS assignee_name
             FROM safety_reports r
             LEFT JOIN users u ON r.reporter_id = u.id
             LEFT JOIN users a ON r.assigned_to = a.id
             WHERE r.id = ? AND r.tenant_id = ?",
            [$id, $tenantId]
        );
        if ($r && $r['is_anonymous']) {
            $r['reporter_name'] = 'Anonymous';
            $r['reporter_employee_id'] = null;
        }
        return $r;
    }

    // ─── Updates / Timeline ────────────────────────────────────

    public static function getUpdates(int $reportId): array {
        return Database::fetchAll(
            "SELECT u.*, us.name AS user_name
             FROM safety_report_updates u
             JOIN users us ON u.user_id = us.id
             WHERE u.report_id = ?
             ORDER BY u.created_at ASC",
            [$reportId]
        );
    }

    // ─── Core Actions ──────────────────────────────────────────

    public static function generateReference(int $tenantId, string $type): string {
        $year = date('Y');
        // e.g. ASR-2026-0001
        $prefix = strtoupper($type) . "-{$year}-";
        
        $row = Database::fetch(
            "SELECT COUNT(id) AS cnt FROM safety_reports WHERE tenant_id = ? AND reference_no LIKE ?",
            [$tenantId, $prefix . '%']
        );
        $next = ($row['cnt'] ?? 0) + 1;
        return $prefix . sprintf('%04d', $next);
    }

    public static function submit(int $tenantId, array $data): int {
        $type = $data['report_type'] ?? 'ASR';
        $ref  = self::generateReference($tenantId, $type);
        
        return Database::insert(
            "INSERT INTO safety_reports
                (tenant_id, reference_no, report_type, reporter_id, is_anonymous, event_date, title, description)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $tenantId,
                $ref,
                $type,
                $data['reporter_id'] ?? null,
                $data['is_anonymous'] ? 1 : 0,
                !empty($data['event_date']) ? $data['event_date'] : null,
                $data['title'],
                $data['description']
            ]
        );
    }

    public static function addUpdate(int $tenantId, int $reportId, int $userId, array $update): void {
        Database::insert(
            "INSERT INTO safety_report_updates
                (report_id, user_id, status_change, severity_change, comment)
             VALUES (?, ?, ?, ?, ?)",
            [
                $reportId,
                $userId,
                $update['status_change'] ?? null,
                $update['severity_change'] ?? null,
                $update['comment'] ?? null
            ]
        );

        $sets = [];
        $params = [];
        if (!empty($update['status_change'])) {
            $sets[] = "status = ?";
            $params[] = $update['status_change'];
        }
        if (!empty($update['severity_change'])) {
            $sets[] = "severity = ?";
            $params[] = $update['severity_change'];
        }
        if (!empty($update['assigned_to'])) {
            $sets[] = "assigned_to = ?";
            $params[] = $update['assigned_to'];
        }

        if ($sets) {
            $params[] = $reportId;
            $params[] = $tenantId;
            $query = "UPDATE safety_reports SET " . implode(', ', $sets) . " WHERE id = ? AND tenant_id = ?";
            Database::execute($query, $params);
        }
    }
}
