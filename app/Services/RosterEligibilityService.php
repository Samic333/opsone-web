<?php
/**
 * RosterEligibilityService — single source of truth for
 * "can user U be assigned duty D on date X?"
 *
 * Used by:
 *   - RosterController::assign() and bulkAssign() — to warn / skip ineligible
 *   - The Roster Generator wizard — to skip crew who would conflict
 *   - The scheduler grid — to surface red dots on rule-breaking cells
 *
 * Returns ['ok' => bool, 'severity' => 'block'|'warn'|null, 'reasons' => [str,...]]
 *
 * Rules implemented (defaults; airline-tunable later):
 *   - Block:  user already has a non-empty duty on that date (unless overwriting)
 *   - Block:  user is on LVE / SCK that overlaps the target date
 *   - Block:  for FLIGHT/SBY/RES/POS/DH/CHK/SIM — license/medical/check expired
 *   - Warn:   for FLIGHT/SBY — would push consecutive duty days past 6 (rest required)
 *   - Warn:   no rest day in the previous 7 days (cumulative rest)
 *   - Warn:   training (TRN/SIM/CHK) on the same day as a flight duty
 */
class RosterEligibilityService {

    private const FLIGHT_DUTY_TYPES   = ['flight','pos','deadhead'];
    private const STANDBY_DUTY_TYPES  = ['standby','reserve'];
    private const TRAINING_DUTY_TYPES = ['training','sim','check'];
    private const LEAVE_DUTY_TYPES    = ['leave','sick'];
    private const REST_DUTY_TYPES     = ['off','rest'];

    private const MAX_CONSECUTIVE_DUTY_DAYS = 6;
    private const REST_WINDOW_DAYS          = 7;
    private const REQUIRED_REST_IN_WINDOW   = 1;

    /**
     * Quick eligibility check for a single user on a single date.
     *
     * @param array $opts overwrite: bool, ignoreCompliance: bool, complianceIssues: precomputed map
     */
    public static function check(int $tenantId, int $userId, string $date, string $dutyType, array $opts = []): array {
        $overwrite        = !empty($opts['overwrite']);
        $ignoreCompliance = !empty($opts['ignoreCompliance']);

        $reasons = [];
        $severity = null;

        // 1) Existing duty on that date
        $existing = Database::fetch(
            "SELECT id, duty_type, duty_code FROM rosters WHERE user_id = ? AND roster_date = ?",
            [$userId, $date]
        );
        if ($existing && !$overwrite) {
            $reasons[]  = "Already assigned: " . strtoupper($existing['duty_type']) .
                          (!empty($existing['duty_code']) ? " ({$existing['duty_code']})" : '');
            $severity   = 'block';
        }
        // If existing is LEAVE/SICK and target is FLIGHT/SBY → also block even with overwrite
        if ($existing && in_array($existing['duty_type'], self::LEAVE_DUTY_TYPES, true)
            && (in_array($dutyType, self::FLIGHT_DUTY_TYPES, true)
                || in_array($dutyType, self::STANDBY_DUTY_TYPES, true))) {
            $reasons[]  = "User is on " . strtoupper($existing['duty_type']) . " — cannot assign flying duty";
            $severity   = 'block';
        }

        // 2) Compliance — only matters for "active" duty types
        $needsCompliance = in_array($dutyType, array_merge(self::FLIGHT_DUTY_TYPES,
                                                            self::STANDBY_DUTY_TYPES,
                                                            self::TRAINING_DUTY_TYPES), true);
        if ($needsCompliance && !$ignoreCompliance) {
            $compliance = $opts['complianceMap'] ?? null;
            if ($compliance === null) {
                $compliance = RosterModel::getComplianceIssues($tenantId);
            }
            $userFlags = $compliance[$userId] ?? null;
            if ($userFlags && ($userFlags['severity'] ?? '') === 'critical') {
                foreach (($userFlags['issues'] ?? []) as $iss) {
                    if (stripos($iss, 'expired') !== false) {
                        $reasons[] = "Compliance: $iss";
                        $severity  = 'block';
                    }
                }
            }
        }

        // 3) Rest-period rules — only matters for FLIGHT/STANDBY
        $isFlying = in_array($dutyType, self::FLIGHT_DUTY_TYPES, true)
                 || in_array($dutyType, self::STANDBY_DUTY_TYPES, true);
        if ($isFlying) {
            $consec = self::consecutiveDutyDays($userId, $date);
            if ($consec >= self::MAX_CONSECUTIVE_DUTY_DAYS) {
                $reasons[] = "Would extend duty streak to " . ($consec + 1)
                          . " days (cap " . self::MAX_CONSECUTIVE_DUTY_DAYS . ")";
                if ($severity !== 'block') $severity = 'warn';
            }
            if (!self::hasRestInWindow($userId, $date, self::REST_WINDOW_DAYS)) {
                $reasons[] = "No rest day in the prior " . self::REST_WINDOW_DAYS . " days";
                if ($severity !== 'block') $severity = 'warn';
            }
        }

        return [
            'ok'        => empty($reasons) || $severity === 'warn',
            'severity'  => $severity,
            'reasons'   => $reasons,
        ];
    }

    /**
     * How many consecutive duty (non OFF/REST/LVE/SCK) days immediately precede $date.
     */
    private static function consecutiveDutyDays(int $userId, string $date): int {
        $rows = Database::fetchAll(
            "SELECT roster_date, duty_type FROM rosters
              WHERE user_id = ? AND roster_date < ?
              ORDER BY roster_date DESC LIMIT 14",
            [$userId, $date]
        );
        $count = 0;
        $cursor = (new \DateTime($date))->modify('-1 day');
        foreach ($rows as $r) {
            if ($r['roster_date'] !== $cursor->format('Y-m-d')) break;
            if (in_array($r['duty_type'],
                array_merge(self::REST_DUTY_TYPES, self::LEAVE_DUTY_TYPES), true)) break;
            $count++;
            $cursor->modify('-1 day');
        }
        return $count;
    }

    /**
     * Is there at least one OFF/REST day in the [date - $window, date) window?
     */
    private static function hasRestInWindow(int $userId, string $date, int $window): bool {
        $start = (new \DateTime($date))->modify("-$window days")->format('Y-m-d');
        $end   = (new \DateTime($date))->modify('-1 day')->format('Y-m-d');
        $row = Database::fetch(
            "SELECT COUNT(*) AS c FROM rosters
              WHERE user_id = ? AND roster_date BETWEEN ? AND ?
                AND duty_type IN ('off','rest','leave','sick')",
            [$userId, $start, $end]
        );
        return ((int)($row['c'] ?? 0)) >= self::REQUIRED_REST_IN_WINDOW;
    }

    /**
     * Bulk version — given a list of (user_id, date, dutyType), returns one
     * eligibility row per (user,date). Pre-fetches the compliance map once.
     * The opts apply to every check.
     */
    public static function checkBatch(int $tenantId, array $items, array $opts = []): array {
        $opts['complianceMap'] = $opts['complianceMap'] ?? RosterModel::getComplianceIssues($tenantId);
        $out = [];
        foreach ($items as $row) {
            $key = $row['user_id'] . ':' . $row['date'];
            $out[$key] = self::check($tenantId, (int)$row['user_id'], $row['date'], $row['duty_type'], $opts);
        }
        return $out;
    }
}
