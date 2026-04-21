<?php
/**
 * PerDiemApiController — Phase 11 Per Diem (mobile).
 *
 * Crew self-service only. Admin review stays on web.
 *
 * Routes:
 *   GET  /api/per-diem/mine       — my claims
 *   GET  /api/per-diem/rates      — active rate catalog
 *   POST /api/per-diem/submit     — submit a new claim
 */
class PerDiemApiController {

    /** GET /api/per-diem/mine */
    public function mine(): void {
        $tenantId = apiTenantId();
        $userId   = (int) apiUser()['user_id'];

        $rows = Database::fetchAll(
            "SELECT id, period_from, period_to, station, country, days, rate, currency, amount,
                    status, notes, created_at, reviewed_at, paid_at
               FROM per_diem_claims
              WHERE tenant_id = ? AND user_id = ?
              ORDER BY period_from DESC, id DESC",
            [$tenantId, $userId]
        );

        $claims = array_map(fn($r) => [
            'id'          => (int)$r['id'],
            'period_from' => (string)($r['period_from'] ?? ''),
            'period_to'   => (string)($r['period_to']   ?? ''),
            'station'     => $r['station'] ?? null,
            'country'     => (string)($r['country'] ?? ''),
            'days'        => (float)$r['days'],
            'rate'        => (float)$r['rate'],
            'currency'    => (string)($r['currency'] ?? 'USD'),
            'amount'      => (float)$r['amount'],
            'status'      => (string)($r['status'] ?? 'submitted'),
            'notes'       => $r['notes']       ?? null,
            'created_at'  => $r['created_at']  ?? null,
            'reviewed_at' => $r['reviewed_at'] ?? null,
            'paid_at'     => $r['paid_at']     ?? null,
        ], $rows);

        jsonResponse(['claims' => $claims]);
    }

    /** GET /api/per-diem/rates */
    public function rates(): void {
        $tenantId = apiTenantId();
        $today = dbToday();
        $rows = Database::fetchAll(
            "SELECT id, country, station, currency, daily_rate, effective_from, effective_to, notes
               FROM per_diem_rates
              WHERE tenant_id = ?
                AND (effective_to IS NULL OR effective_to >= $today)
              ORDER BY country, COALESCE(station,'')",
            [$tenantId]
        );

        $rates = array_map(fn($r) => [
            'id'              => (int)$r['id'],
            'country'         => (string)($r['country'] ?? ''),
            'station'         => $r['station'] ?? null,
            'currency'        => (string)($r['currency'] ?? 'USD'),
            'daily_rate'      => (float)$r['daily_rate'],
            'effective_from'  => (string)($r['effective_from'] ?? ''),
            'effective_to'    => $r['effective_to'] ?? null,
            'notes'           => $r['notes']        ?? null,
        ], $rows);

        jsonResponse(['rates' => $rates]);
    }

    /** POST /api/per-diem/submit */
    public function submit(): void {
        $tenantId = apiTenantId();
        $userId   = (int) apiUser()['user_id'];

        $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $rateId   = (int)($body['rate_id'] ?? 0);
        $rate     = $rateId ? Database::fetch(
            "SELECT * FROM per_diem_rates WHERE id = ? AND tenant_id = ?",
            [$rateId, $tenantId]
        ) : null;

        $days    = (float)($body['days'] ?? 0);
        if ($days <= 0) jsonResponse(['error' => 'days must be > 0'], 422);

        $dayRate = $rate ? (float)$rate['daily_rate'] : (float)($body['rate'] ?? 0);
        if ($dayRate <= 0) jsonResponse(['error' => 'rate must be > 0'], 422);

        $curr    = $rate ? $rate['currency'] : strtoupper((string)($body['currency'] ?? 'USD'));
        $amount  = round($days * $dayRate, 2);
        $periodFrom = $body['period_from'] ?? date('Y-m-d');
        $periodTo   = $body['period_to']   ?? $periodFrom;

        $id = Database::insert(
            "INSERT INTO per_diem_claims
                (tenant_id, user_id, period_from, period_to, station, country, days, rate_id, rate,
                 currency, amount, status, notes)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $tenantId, $userId,
                $periodFrom, $periodTo,
                trim((string)($body['station'] ?? '')) ?: null,
                $rate ? $rate['country'] : trim((string)($body['country'] ?? 'Unknown')),
                $days, $rateId ?: null, $dayRate, $curr, $amount, 'submitted',
                trim((string)($body['notes'] ?? '')),
            ]
        );
        AuditLog::log('perdiem_claim_submitted', 'per_diem_claim', (int)$id,
            "$days days @ $dayRate $curr = $amount (mobile)");

        jsonResponse([
            'success' => true,
            'claim_id' => (int)$id,
            'amount' => $amount,
            'currency' => $curr,
        ]);
    }
}
