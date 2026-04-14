<?php
/**
 * OnboardingRequest Model — airline onboarding workflow
 */
class OnboardingRequest {

    public static function all(?string $status = null): array {
        $where  = $status ? "WHERE r.status = ?" : '';
        $params = $status ? [$status] : [];
        return Database::fetchAll(
            "SELECT r.*, u.name as reviewed_by_name
             FROM tenant_onboarding_requests r
             LEFT JOIN users u ON u.id = r.reviewed_by
             $where
             ORDER BY r.created_at DESC",
            $params
        );
    }

    public static function find(int $id): ?array {
        return Database::fetch(
            "SELECT r.*, u.name as reviewed_by_name
             FROM tenant_onboarding_requests r
             LEFT JOIN users u ON u.id = r.reviewed_by
             WHERE r.id = ?",
            [$id]
        );
    }

    public static function create(array $data): int {
        return Database::insert(
            "INSERT INTO tenant_onboarding_requests
                (legal_name, display_name, icao_code, iata_code, primary_country,
                 contact_name, contact_email, contact_phone,
                 expected_headcount, support_tier, requested_modules, notes, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')",
            [
                $data['legal_name'],
                $data['display_name']       ?? null,
                $data['icao_code']          ?? null,
                $data['iata_code']          ?? null,
                $data['primary_country']    ?? null,
                $data['contact_name'],
                $data['contact_email'],
                $data['contact_phone']      ?? null,
                $data['expected_headcount'] ?? null,
                $data['support_tier']       ?? 'standard',
                isset($data['requested_modules']) ? json_encode($data['requested_modules']) : null,
                $data['notes']              ?? null,
            ]
        );
    }

    public static function approve(int $id, int $reviewerId, ?string $notes = null): void {
        Database::execute(
            "UPDATE tenant_onboarding_requests
             SET status = 'approved', reviewed_by = ?, reviewed_at = NOW(), review_notes = ?
             WHERE id = ?",
            [$reviewerId, $notes, $id]
        );
    }

    public static function reject(int $id, int $reviewerId, ?string $notes = null): void {
        Database::execute(
            "UPDATE tenant_onboarding_requests
             SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), review_notes = ?
             WHERE id = ?",
            [$reviewerId, $notes, $id]
        );
    }

    public static function markProvisioned(int $id, int $tenantId): void {
        Database::execute(
            "UPDATE tenant_onboarding_requests SET status = 'provisioned', tenant_id = ? WHERE id = ?",
            [$tenantId, $id]
        );
    }

    public static function countPending(): int {
        return (int)(Database::fetch(
            "SELECT COUNT(*) as c FROM tenant_onboarding_requests WHERE status = 'pending'"
        )['c'] ?? 0);
    }
}
