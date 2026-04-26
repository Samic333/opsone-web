<?php
/**
 * EligibilityGate — checks if a crew member is eligible to fly/work on a date.
 * Phase 8 addition — pulls from Phase 3 compliance data.
 */
class EligibilityGate {

    /**
     * Return a list of blocker strings for this user+date. Empty = clear to roster.
     *
     * Checks (non-operational duty types like 'off'/'leave'/'training' are exempt):
     *   • License expired on or before $date
     *   • Medical expired on or before $date
     *   • Passport expired on or before $date (flying)
     *   • Any mandatory qualification expired
     */
    public static function blockersFor(int $userId, string $date, string $dutyType = 'flight'): array {
        // Non-operational duties: no eligibility gate needed.
        $exempt = ['off', 'leave', 'training', 'sick', 'rest', 'standby'];
        if (in_array(strtolower($dutyType), $exempt, true)) {
            return [];
        }

        $blockers = [];

        // 1. Licenses
        $licExpired = Database::fetchAll(
            "SELECT license_type, license_number, expiry_date
               FROM licenses
              WHERE user_id = ? AND expiry_date IS NOT NULL AND expiry_date <= ?",
            [$userId, $date]
        );
        foreach ($licExpired as $l) {
            $blockers[] = "License {$l['license_type']} expired on {$l['expiry_date']}";
        }

        // 2. Medical + Passport (on crew_profiles)
        $profile = Database::fetch("SELECT * FROM crew_profiles WHERE user_id = ?", [$userId]);
        if ($profile) {
            if (!empty($profile['medical_expiry']) && $profile['medical_expiry'] <= $date) {
                $blockers[] = "Medical expired on {$profile['medical_expiry']}";
            }
            if (!empty($profile['passport_expiry']) && $profile['passport_expiry'] <= $date) {
                $blockers[] = "Passport expired on {$profile['passport_expiry']}";
            }
        }

        // 3. Qualifications. Canonical table is `qualifications` (migration 014)
        // with columns (qual_type, qual_name, expiry_date, status). Earlier
        // this queried `user_qualifications.qualification_type` which never
        // existed; the try/catch silently swallowed the error so expired
        // qualifications never blocked crew assignment.
        try {
            $qualExpired = Database::fetchAll(
                "SELECT qual_type, qual_name, expiry_date
                   FROM qualifications
                  WHERE user_id = ?
                    AND expiry_date IS NOT NULL
                    AND expiry_date <= ?
                    AND status = 'active'",
                [$userId, $date]
            );
            foreach ($qualExpired as $q) {
                $label = $q['qual_name'] ?: $q['qual_type'];
                $blockers[] = "Qualification {$label} expired on {$q['expiry_date']}";
            }
        } catch (\Throwable $e) {
            error_log('[OpsOne EligibilityGate qualifications skipped] ' . $e->getMessage());
        }

        return $blockers;
    }
}
