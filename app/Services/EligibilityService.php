<?php
/**
 * EligibilityService — computes assignment readiness for a staff member.
 *
 * Readiness signals (consumed by later roster/scheduling logic):
 *   - status: 'eligible' | 'warning' | 'blocked'
 *   - reasons: array of human-readable reasons for non-eligibility
 *   - details: structured breakdown (missing_required, expired, expiring_soon,
 *              pending_approval, inactive)
 *
 * Rules (summary):
 *   BLOCKED  — user inactive/suspended, OR any mandatory required doc is
 *              missing / expired / revoked, OR mandatory license/medical/
 *              passport/visa has expired.
 *   WARNING  — any mandatory doc expires within its warning_days threshold,
 *              OR a sensitive compliance change is pending approval.
 *   ELIGIBLE — neither blocked nor warning.
 */
class EligibilityService {

    public const STATUS_ELIGIBLE = 'eligible';
    public const STATUS_WARNING  = 'warning';
    public const STATUS_BLOCKED  = 'blocked';

    /**
     * Compute readiness for a single user.
     * Returns:
     *   [
     *     'status'   => 'eligible' | 'warning' | 'blocked',
     *     'reasons'  => [ 'Medical expired on 2026-03-11', ... ],
     *     'details'  => [ 'missing_required' => [...], 'expired' => [...],
     *                     'expiring_soon' => [...], 'pending_approval' => [...] ],
     *     'checked_at' => '2026-04-21 12:34:56',
     *   ]
     */
    public static function computeForUser(int $userId): array {
        $user = Database::fetch("SELECT * FROM users WHERE id = ?", [$userId]);
        if (!$user) {
            return self::emptyResult('blocked', ['User not found']);
        }

        $tenantId = (int) $user['tenant_id'];
        $status   = $user['status'] ?? 'active';

        $reasons = [];
        $details = [
            'missing_required' => [],
            'expired'          => [],
            'expiring_soon'    => [],
            'pending_approval' => [],
        ];
        $worstLevel = self::STATUS_ELIGIBLE;

        // 1. Employment status
        if (in_array($status, ['inactive', 'suspended'], true)) {
            $reasons[]   = 'Staff status is ' . $status;
            $worstLevel  = self::STATUS_BLOCKED;
        } elseif ($status !== 'active') {
            $reasons[]   = 'Staff status is ' . $status;
            $worstLevel  = self::worst($worstLevel, self::STATUS_WARNING);
        }

        // 2. Determine role slugs + required docs
        $roles = UserModel::getRoles($userId);
        $slugs = array_column($roles, 'slug');
        $required = RoleRequiredDocumentModel::forRoles($slugs, $tenantId);

        // 3. Gather the user's documents (approved / valid) plus licenses/qualifications
        $docs = CrewDocumentModel::forUser($userId);
        $profile       = CrewProfileModel::findByUser($userId) ?? [];
        $licenses      = CrewProfileModel::getLicenses($userId);
        $qualifications = QualificationModel::forUser($userId);

        $today = date('Y-m-d');

        // Index valid documents by doc_type
        $docsByType = [];
        foreach ($docs as $d) {
            if ($d['status'] === 'valid') {
                $docsByType[$d['doc_type']][] = $d;
            }
            if ($d['status'] === 'pending_approval') {
                $details['pending_approval'][] = [
                    'kind' => 'document', 'doc_type' => $d['doc_type'], 'title' => $d['doc_title'], 'id' => (int)$d['id'],
                ];
            }
        }

        // 4. Check each required doc type
        foreach ($required as $req) {
            $type   = $req['doc_type'];
            $label  = $req['doc_label'];
            $warn   = (int) ($req['warning_days']  ?? 60);
            $crit   = (int) ($req['critical_days'] ?? 14);
            $mand   = (bool) $req['is_mandatory'];

            // License / medical / passport / visa are also tracked on crew_profiles/licenses
            $hit = self::resolveDocStatus($type, $docsByType, $profile, $licenses, $qualifications);

            if ($hit === null) {
                // Nothing matches this requirement
                if ($mand) {
                    $reasons[] = $label . ' is missing';
                    $details['missing_required'][] = ['doc_type' => $type, 'label' => $label];
                    $worstLevel = self::worst($worstLevel, self::STATUS_BLOCKED);
                } else {
                    $details['missing_required'][] = ['doc_type' => $type, 'label' => $label . ' (optional)'];
                }
                continue;
            }

            // Evaluate expiry
            $expiry = $hit['expiry_date'] ?? null;
            if (!$expiry) continue;   // no expiry set — treat as current

            $days = (int) floor((strtotime($expiry) - strtotime($today)) / 86400);

            if ($days < 0) {
                $reasons[] = $label . ' expired on ' . $expiry;
                $details['expired'][] = ['doc_type' => $type, 'label' => $label, 'expiry_date' => $expiry, 'days' => $days];
                if ($mand) {
                    $worstLevel = self::worst($worstLevel, self::STATUS_BLOCKED);
                } else {
                    $worstLevel = self::worst($worstLevel, self::STATUS_WARNING);
                }
            } elseif ($days <= $crit) {
                $reasons[] = $label . ' expires in ' . $days . 'd (critical)';
                $details['expiring_soon'][] = ['doc_type' => $type, 'label' => $label, 'expiry_date' => $expiry, 'days' => $days, 'level' => 'critical'];
                $worstLevel = self::worst($worstLevel, self::STATUS_WARNING);
            } elseif ($days <= $warn) {
                $details['expiring_soon'][] = ['doc_type' => $type, 'label' => $label, 'expiry_date' => $expiry, 'days' => $days, 'level' => 'warning'];
                $worstLevel = self::worst($worstLevel, self::STATUS_WARNING);
            }
        }

        // 5. Pending approval on profile/license/qualification?
        $pendingCRs = Database::fetchAll(
            "SELECT target_entity, target_id FROM compliance_change_requests
             WHERE user_id = ? AND status IN ('submitted','under_review','info_requested')",
            [$userId]
        );
        foreach ($pendingCRs as $cr) {
            $details['pending_approval'][] = [
                'kind' => 'change_request',
                'target_entity' => $cr['target_entity'],
                'target_id' => $cr['target_id'] !== null ? (int)$cr['target_id'] : null,
            ];
        }

        return [
            'status'     => $worstLevel,
            'reasons'    => $reasons,
            'details'    => $details,
            'checked_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Resolve the current status/expiry record for a required doc_type.
     * Looks across crew_documents, crew_profiles (passport/medical/visa),
     * licenses and qualifications.
     */
    private static function resolveDocStatus(
        string $type, array $docsByType, array $profile, array $licenses, array $qualifications
    ): ?array {
        // 1. Crew documents table (canonical source)
        if (!empty($docsByType[$type])) {
            $rows = $docsByType[$type];
            usort($rows, fn($a, $b) => strcmp($b['expiry_date'] ?? '', $a['expiry_date'] ?? ''));
            $row = $rows[0];
            return ['expiry_date' => $row['expiry_date'] ?? null, 'source' => 'crew_documents', 'id' => (int) $row['id']];
        }

        // 2. Map well-known types to crew_profiles / licenses / qualifications
        $map = [
            'passport' => fn() => !empty($profile['passport_expiry'])
                ? ['expiry_date' => $profile['passport_expiry'], 'source' => 'crew_profiles'] : null,
            'medical'  => fn() => !empty($profile['medical_expiry'])
                ? ['expiry_date' => $profile['medical_expiry'],  'source' => 'crew_profiles'] : null,
            'visa'     => fn() => !empty($profile['visa_expiry'])
                ? ['expiry_date' => $profile['visa_expiry'],     'source' => 'crew_profiles'] : null,
            'contract' => fn() => !empty($profile['contract_expiry'])
                ? ['expiry_date' => $profile['contract_expiry'], 'source' => 'crew_profiles'] : null,
        ];
        if (isset($map[$type])) {
            $hit = $map[$type]();
            if ($hit) return $hit;
        }

        // 3. Licenses (type-agnostic; use latest expiry)
        if ($type === 'license') {
            $best = null;
            foreach ($licenses as $l) {
                if (!empty($l['expiry_date']) && ($best === null || strcmp($l['expiry_date'], $best['expiry_date']) > 0)) {
                    $best = ['expiry_date' => $l['expiry_date'], 'source' => 'licenses', 'id' => (int) $l['id']];
                }
            }
            return $best;
        }

        // 4. Qualifications (match by qual_type roughly)
        $qualMap = [
            'type_rating'        => ['type rating'],
            'type_auth'          => ['type authorization','type auth'],
            'cabin_attestation'  => ['cabin crew attestation','cabin attestation'],
            'dangerous_goods'    => ['dangerous goods'],
        ];
        if (isset($qualMap[$type])) {
            $needles = $qualMap[$type];
            $best = null;
            foreach ($qualifications as $q) {
                $qt = strtolower($q['qual_type'] ?? '');
                foreach ($needles as $n) {
                    if (str_contains($qt, $n) && !empty($q['expiry_date'])) {
                        if ($best === null || strcmp($q['expiry_date'], $best['expiry_date']) > 0) {
                            $best = ['expiry_date' => $q['expiry_date'], 'source' => 'qualifications', 'id' => (int) $q['id']];
                        }
                    }
                }
            }
            if ($best) return $best;
        }

        return null;
    }

    /**
     * Bulk eligibility for a tenant. Returns array keyed by user_id.
     * Useful for compliance list views and later roster pre-check.
     */
    public static function bulkForTenant(int $tenantId): array {
        $users = Database::fetchAll(
            "SELECT id FROM users WHERE tenant_id = ? AND status != 'inactive'",
            [$tenantId]
        );
        $out = [];
        foreach ($users as $u) {
            $out[(int) $u['id']] = self::computeForUser((int) $u['id']);
        }
        return $out;
    }

    /** Tenant-level counts by eligibility status. */
    public static function tenantSummary(int $tenantId): array {
        $bulk = self::bulkForTenant($tenantId);
        $out = ['eligible' => 0, 'warning' => 0, 'blocked' => 0, 'total' => count($bulk)];
        foreach ($bulk as $r) {
            $out[$r['status']] = ($out[$r['status']] ?? 0) + 1;
        }
        return $out;
    }

    // ─── internal ────────────────────────────────────────────────────────────

    private static function emptyResult(string $status, array $reasons): array {
        return [
            'status'  => $status,
            'reasons' => $reasons,
            'details' => [
                'missing_required' => [], 'expired' => [], 'expiring_soon' => [], 'pending_approval' => [],
            ],
            'checked_at' => date('Y-m-d H:i:s'),
        ];
    }

    private static function worst(string $current, string $candidate): string {
        $rank = [self::STATUS_ELIGIBLE => 0, self::STATUS_WARNING => 1, self::STATUS_BLOCKED => 2];
        return $rank[$candidate] > $rank[$current] ? $candidate : $current;
    }
}
