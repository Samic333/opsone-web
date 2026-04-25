<?php
/**
 * TrainingApiController — Phase 12 Training Records (mobile).
 *
 * Routes:
 *   GET /api/training/mine     — caller's training records with type/expiry
 */
class TrainingApiController {

    /** GET /api/training/mine */
    public function mine(): void {
        $tenantId = apiTenantId();
        $userId   = (int) apiUser()['user_id'];

        $rows = Database::fetchAll(
            "SELECT tr.id, tr.type_code, tr.completed_date, tr.expires_date, tr.provider,
                    tr.result, tr.notes, tt.name AS type_name, tt.validity_months
               FROM training_records tr
               LEFT JOIN training_types tt ON tr.training_type_id = tt.id
              WHERE tr.tenant_id = ? AND tr.user_id = ?
              ORDER BY COALESCE(tr.expires_date, '9999-12-31') ASC, tr.completed_date DESC",
            [$tenantId, $userId]
        );

        $records = array_map(function($r) {
            $expiry = $r['expires_date'] ?? null;
            $daysToExpiry = null;
            if ($expiry) {
                $ts = strtotime($expiry);
                if ($ts !== false) {
                    $daysToExpiry = (int) floor(($ts - time()) / 86400);
                }
            }
            return [
                'id'               => (int)$r['id'],
                'type_code'        => (string)($r['type_code'] ?? ''),
                'type_name'        => $r['type_name']       ?? null,
                'completed_date'   => $r['completed_date']  ?? null,
                'expires_date'     => $expiry,
                'days_to_expiry'   => $daysToExpiry,
                'provider'         => $r['provider']        ?? null,
                'result'           => (string)($r['result'] ?? 'pass'),
                'validity_months'  => isset($r['validity_months']) ? (int)$r['validity_months'] : null,
                'notes'            => $r['notes']           ?? null,
            ];
        }, $rows);

        jsonResponse(['records' => $records]);
    }
}
