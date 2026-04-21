<?php
/**
 * ExpiryAlertService — scans compliance data and records expiry alerts.
 *
 * Scans the following sources for items nearing/after expiry:
 *   - licenses
 *   - crew_profiles (medical, passport, visa, contract)
 *   - qualifications
 *   - crew_documents
 *
 * Compares each record's expiry_date against the configured warning/critical
 * windows (role_required_documents.warning_days / critical_days; fallbacks
 * defined here). Records an entry in expiry_alerts and determines recipients
 * (crew user, HR, line manager).
 *
 * Dispatch (actual email/push) is out-of-scope for Phase 6 — we record the
 * intent so a later notification job can send via NotificationService.
 */
class ExpiryAlertService {

    public const DEFAULT_WARNING_DAYS  = 60;
    public const DEFAULT_CRITICAL_DAYS = 14;

    /**
     * Scan a tenant and record alerts for everything that needs attention.
     * Returns counts: ['warnings' => n, 'critical' => n, 'expired' => n].
     */
    public static function scanTenant(int $tenantId): array {
        $counts = ['warnings' => 0, 'critical' => 0, 'expired' => 0];

        $today     = date('Y-m-d');
        $warnCutoff = date('Y-m-d', strtotime("+" . self::DEFAULT_WARNING_DAYS . " days"));

        // 1. Licenses
        $rows = Database::fetchAll(
            "SELECT id, user_id, expiry_date FROM licenses
             WHERE tenant_id = ? AND expiry_date IS NOT NULL",
            [$tenantId]
        );
        foreach ($rows as $r) {
            $level = self::levelFor($r['expiry_date'], self::DEFAULT_WARNING_DAYS, self::DEFAULT_CRITICAL_DAYS);
            if ($level) {
                ExpiryAlertModel::record($tenantId, (int)$r['user_id'], 'license', (int)$r['id'], $level, $r['expiry_date']);
                $counts[self::countKey($level)]++;
            }
        }

        // 2. Medical / passport / visa / contract via crew_profiles
        $profiles = Database::fetchAll(
            "SELECT user_id, medical_expiry, passport_expiry, visa_expiry, contract_expiry
             FROM crew_profiles WHERE tenant_id = ?",
            [$tenantId]
        );
        foreach ($profiles as $p) {
            foreach ([
                'medical'  => $p['medical_expiry'],
                'passport' => $p['passport_expiry'],
                'visa'     => $p['visa_expiry'] ?? null,
                'contract' => $p['contract_expiry'],
            ] as $kind => $exp) {
                if (!$exp) continue;
                $level = self::levelFor($exp, self::DEFAULT_WARNING_DAYS, self::DEFAULT_CRITICAL_DAYS);
                if ($level) {
                    // entity_id: use user_id since these are on crew_profiles
                    ExpiryAlertModel::record($tenantId, (int)$p['user_id'], $kind, (int)$p['user_id'], $level, $exp);
                    $counts[self::countKey($level)]++;
                }
            }
        }

        // 3. Qualifications
        $rows = Database::fetchAll(
            "SELECT id, user_id, expiry_date FROM qualifications
             WHERE tenant_id = ? AND expiry_date IS NOT NULL",
            [$tenantId]
        );
        foreach ($rows as $r) {
            $level = self::levelFor($r['expiry_date'], self::DEFAULT_WARNING_DAYS, self::DEFAULT_CRITICAL_DAYS);
            if ($level) {
                ExpiryAlertModel::record($tenantId, (int)$r['user_id'], 'qualification', (int)$r['id'], $level, $r['expiry_date']);
                $counts[self::countKey($level)]++;
            }
        }

        // 4. Crew documents
        $rows = Database::fetchAll(
            "SELECT id, user_id, expiry_date, doc_type FROM crew_documents
             WHERE tenant_id = ? AND status = 'valid' AND expiry_date IS NOT NULL",
            [$tenantId]
        );
        foreach ($rows as $r) {
            $level = self::levelFor($r['expiry_date'], self::DEFAULT_WARNING_DAYS, self::DEFAULT_CRITICAL_DAYS);
            if ($level) {
                ExpiryAlertModel::record($tenantId, (int)$r['user_id'], 'document', (int)$r['id'], $level, $r['expiry_date']);
                $counts[self::countKey($level)]++;
            }
        }

        return $counts;
    }

    /**
     * Determine recipients for an alert.
     * Per product decision C4: crew user + HR + line manager.
     * Returns user_id list.
     */
    public static function recipientsFor(int $tenantId, int $crewUserId): array {
        $out = [$crewUserId];

        // HR users in tenant
        $hr = Database::fetchAll(
            "SELECT u.id FROM users u
             JOIN user_roles ur ON u.id = ur.user_id
             JOIN roles r ON ur.role_id = r.id
             WHERE u.tenant_id = ? AND u.status = 'active' AND r.slug = 'hr'",
            [$tenantId]
        );
        foreach ($hr as $u) $out[] = (int) $u['id'];

        // Line manager
        $user = Database::fetch("SELECT line_manager_id FROM users WHERE id = ?", [$crewUserId]);
        if ($user && !empty($user['line_manager_id'])) {
            $out[] = (int) $user['line_manager_id'];
        }

        return array_values(array_unique($out));
    }

    // ─── helpers ─────────────────────────────────────────────────────────────

    private static function levelFor(string $expiry, int $warnDays, int $critDays): ?string {
        $days = (int) floor((strtotime($expiry) - time()) / 86400);
        if ($days < 0)            return ExpiryAlertModel::LEVEL_EXPIRED;
        if ($days <= $critDays)   return ExpiryAlertModel::LEVEL_CRITICAL;
        if ($days <= $warnDays)   return ExpiryAlertModel::LEVEL_WARNING;
        return null;
    }

    private static function countKey(string $level): string {
        return match ($level) {
            ExpiryAlertModel::LEVEL_EXPIRED  => 'expired',
            ExpiryAlertModel::LEVEL_CRITICAL => 'critical',
            default                          => 'warnings',
        };
    }
}
