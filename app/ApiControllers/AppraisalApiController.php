<?php
/**
 * AppraisalApiController — Phase 13 Crew Appraisal (mobile).
 *
 * Confidentiality rule: an appraisal about me is visible only if
 *   - confidential = 0, OR
 *   - status = 'accepted'
 * (Mirrors AppraisalController::index.)
 *
 * Routes:
 *   GET  /api/appraisals/mine        — appraisals I've written
 *   GET  /api/appraisals/about-me    — appraisals about me (per confidentiality rule)
 *   POST /api/appraisals             — submit a new appraisal
 */
class AppraisalApiController {

    /** GET /api/appraisals/mine */
    public function mine(): void {
        $tenantId = apiTenantId();
        $userId   = (int) apiUser()['user_id'];

        $rows = Database::fetchAll(
            "SELECT a.*, us.name AS subject_name
               FROM appraisals a
               JOIN users us ON a.subject_id = us.id
              WHERE a.tenant_id = ? AND a.appraiser_id = ?
              ORDER BY a.period_to DESC, a.id DESC",
            [$tenantId, $userId]
        );
        jsonResponse(['appraisals' => array_map([self::class, 'format'], $rows)]);
    }

    /** GET /api/appraisals/about-me */
    public function aboutMe(): void {
        $tenantId = apiTenantId();
        $userId   = (int) apiUser()['user_id'];

        $rows = Database::fetchAll(
            "SELECT a.*, ua.name AS appraiser_name
               FROM appraisals a
               JOIN users ua ON a.appraiser_id = ua.id
              WHERE a.tenant_id = ? AND a.subject_id = ?
                AND (a.confidential = 0 OR a.status = 'accepted')
              ORDER BY a.period_to DESC, a.id DESC",
            [$tenantId, $userId]
        );
        jsonResponse(['appraisals' => array_map([self::class, 'format'], $rows)]);
    }

    /** POST /api/appraisals */
    public function create(): void {
        $tenantId = apiTenantId();
        $me       = (int) apiUser()['user_id'];

        $body    = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $subject = (int)($body['subject_id'] ?? 0);
        if ($subject === 0 || $subject === $me) {
            jsonResponse(['error' => 'subject_id required and must differ from caller'], 422);
        }

        // Validate subject is in same tenant
        $ok = Database::fetch(
            "SELECT id FROM users WHERE id = ? AND tenant_id = ? AND status = 'active'",
            [$subject, $tenantId]
        );
        if (!$ok) jsonResponse(['error' => 'Subject not found in this airline'], 422);

        // Per-dimension ratings stored as JSON. Caller may send an associative
        // array of competency → score, e.g.:
        //   {"communication":4,"teamwork":5,"punctuality":3}
        $ratingsJson = null;
        if (isset($body['ratings']) && is_array($body['ratings']) && !empty($body['ratings'])) {
            $ratingsJson = json_encode($body['ratings']);
        }

        $id = Database::insert(
            "INSERT INTO appraisals
                (tenant_id, subject_id, appraiser_id, rotation_ref, period_from, period_to,
                 status, rating_overall, ratings, strengths, improvements, comments, confidential)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $tenantId, $subject, $me,
                trim((string)($body['rotation_ref'] ?? '')),
                (string)($body['period_from'] ?? date('Y-m-d')),
                (string)($body['period_to']   ?? date('Y-m-d')),
                (string)($body['status']      ?? 'submitted'),
                isset($body['rating_overall']) ? (int)$body['rating_overall'] : null,
                $ratingsJson,
                trim((string)($body['strengths']    ?? '')),
                trim((string)($body['improvements'] ?? '')),
                trim((string)($body['comments']     ?? '')),
                !empty($body['confidential']) ? 1 : 0,
            ]
        );
        AuditLog::log('appraisal_created', 'appraisal', (int)$id,
            "Subject $subject rotation " . ($body['rotation_ref'] ?? '') . ' (mobile)');
        jsonResponse(['success' => true, 'id' => (int)$id]);
    }

    private static function format(array $r): array {
        $ratings = null;
        if (!empty($r['ratings'])) {
            $decoded = json_decode((string)$r['ratings'], true);
            if (is_array($decoded)) $ratings = $decoded;
        }
        return [
            'id'             => (int)$r['id'],
            'subject_id'     => (int)$r['subject_id'],
            'subject_name'   => $r['subject_name']   ?? null,
            'appraiser_id'   => (int)$r['appraiser_id'],
            'appraiser_name' => $r['appraiser_name'] ?? null,
            'rotation_ref'   => (string)($r['rotation_ref']   ?? ''),
            'period_from'    => (string)($r['period_from']    ?? ''),
            'period_to'      => (string)($r['period_to']      ?? ''),
            'status'         => (string)($r['status']         ?? 'submitted'),
            'rating_overall' => isset($r['rating_overall']) ? (int)$r['rating_overall'] : null,
            'ratings'        => $ratings,
            'strengths'      => $r['strengths']    ?? null,
            'improvements'   => $r['improvements'] ?? null,
            'comments'       => $r['comments']     ?? null,
            'confidential'   => !empty($r['confidential']),
            'submitted_at'   => $r['submitted_at'] ?? null,
            'reviewed_at'    => $r['reviewed_at']  ?? null,
        ];
    }
}
