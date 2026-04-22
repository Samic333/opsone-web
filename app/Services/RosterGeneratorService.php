<?php
/**
 * RosterGeneratorService — builds a month of roster entries in one shot.
 *
 * Two modes (plus combinations):
 *   1) copy_from_month — clone a prior month's pattern for the same crew
 *      onto a new target month; date offsets handled day-by-day with
 *      day-of-week alignment.
 *   2) pattern         — apply a rotation pattern (e.g. 5-on / 2-off) to
 *      every selected crew member, with optional offset per user so the
 *      whole airline isn't off on the same day.
 *
 * Both modes:
 *   - Skip dates where the user already has an entry, unless overwrite=true.
 *   - Ask RosterEligibilityService::check() and skip blocked items
 *     (still counted in the report).
 *   - Write into a target $periodId so the result lives in the chosen
 *     draft period.
 *
 * Returns a report:
 *   ['written' => n, 'skipped' => n, 'blocked' => [['user_id','date','reasons']]]
 */
class RosterGeneratorService {

    /**
     * Copy a source month onto a target month for the given user_ids.
     */
    public static function copyMonth(
        int    $tenantId,
        array  $userIds,
        int    $srcYear,
        int    $srcMonth,
        int    $tgtYear,
        int    $tgtMonth,
        ?int   $periodId = null,
        bool   $overwrite = false,
        bool   $ignoreCompliance = false
    ): array {
        $srcDays = cal_days_in_month(CAL_GREGORIAN, $srcMonth, $srcYear);
        $tgtDays = cal_days_in_month(CAL_GREGORIAN, $tgtMonth, $tgtYear);

        $written = 0; $skipped = 0; $blocked = [];
        $compliance = RosterModel::getComplianceIssues($tenantId);

        $srcFrom = sprintf('%04d-%02d-01', $srcYear, $srcMonth);
        $srcTo   = sprintf('%04d-%02d-%02d', $srcYear, $srcMonth, $srcDays);

        // Pull source rows once for all users
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $srcRows = $userIds ? Database::fetchAll(
            "SELECT user_id, roster_date, duty_type, duty_code, notes
               FROM rosters
              WHERE tenant_id = ?
                AND user_id IN ($placeholders)
                AND roster_date BETWEEN ? AND ?
              ORDER BY user_id, roster_date",
            array_merge([$tenantId], $userIds, [$srcFrom, $srcTo])
        ) : [];

        // Index by user→day-of-month
        $byUserDay = [];
        foreach ($srcRows as $r) {
            $dom = (int) substr($r['roster_date'], 8, 2);
            $byUserDay[(int)$r['user_id']][$dom] = $r;
        }

        $today = date('Y-m-d');
        for ($d = 1; $d <= $tgtDays; $d++) {
            $tgtDate = sprintf('%04d-%02d-%02d', $tgtYear, $tgtMonth, $d);
            // We allow overwriting historical entries only if explicitly enabled,
            // but we don't refuse past dates outright (might be backfilling).
            foreach ($userIds as $uid) {
                $uid = (int) $uid;
                $src = $byUserDay[$uid][$d] ?? null;
                if (!$src) { $skipped++; continue; }

                $elig = RosterEligibilityService::check($tenantId, $uid, $tgtDate, $src['duty_type'], [
                    'overwrite' => $overwrite,
                    'ignoreCompliance' => $ignoreCompliance,
                    'complianceMap' => $compliance,
                ]);

                if (($elig['severity'] ?? '') === 'block') {
                    $blocked[] = ['user_id'=>$uid, 'date'=>$tgtDate, 'duty_type'=>$src['duty_type'], 'reasons'=>$elig['reasons']];
                    $skipped++;
                    continue;
                }

                self::upsert($tenantId, $uid, $tgtDate, $src['duty_type'],
                             $src['duty_code'] ?? null, $src['notes'] ?? null,
                             $periodId, $overwrite);
                $written++;
            }
        }

        return ['written'=>$written, 'skipped'=>$skipped, 'blocked'=>$blocked];
    }

    /**
     * Apply a pattern across a date range.
     *  $pattern is a string like "5-on/2-off" or "4-on/3-off" — also accepts
     *  arbitrary csv strings of duty codes (e.g. "FLT,FLT,SBY,OFF,OFF").
     *  Per-user offsetMap can shift each user's pattern start so the whole
     *  fleet is not off on the same day.
     *
     * @param int[]    $userIds
     * @param string   $patternKey  '5on2off' | '4on3off' | '5on4off' | '6on1off' | 'flt-sby-off'
     * @param int[]    $offsetMap   user_id => offset days
     */
    public static function applyPattern(
        int    $tenantId,
        array  $userIds,
        string $fromDate,
        string $toDate,
        string $patternKey,
        ?int   $periodId = null,
        bool   $overwrite = false,
        bool   $ignoreCompliance = false,
        array  $offsetMap = []
    ): array {
        $pattern = self::resolvePattern($patternKey);
        if (!$pattern) {
            return ['written'=>0,'skipped'=>0,'blocked'=>[],'error'=>"Unknown pattern $patternKey"];
        }
        $patLen = count($pattern);
        $written = 0; $skipped = 0; $blocked = [];

        $compliance = RosterModel::getComplianceIssues($tenantId);

        $cur = new \DateTime($fromDate);
        $end = new \DateTime($toDate);
        $startEpoch = (int) ($cur->getTimestamp() / 86400);

        while ($cur <= $end) {
            $dateStr = $cur->format('Y-m-d');
            $dayIdx  = ((int)($cur->getTimestamp() / 86400)) - $startEpoch;
            foreach ($userIds as $uid) {
                $uid = (int) $uid;
                $userOffset = (int) ($offsetMap[$uid] ?? 0);
                $patIdx = (($dayIdx - $userOffset) % $patLen + $patLen) % $patLen;
                $dutyType = $pattern[$patIdx];
                if ($dutyType === 'skip') { continue; }

                $elig = RosterEligibilityService::check($tenantId, $uid, $dateStr, $dutyType, [
                    'overwrite' => $overwrite,
                    'ignoreCompliance' => $ignoreCompliance,
                    'complianceMap' => $compliance,
                ]);
                if (($elig['severity'] ?? '') === 'block') {
                    $blocked[] = ['user_id'=>$uid,'date'=>$dateStr,'duty_type'=>$dutyType,'reasons'=>$elig['reasons']];
                    $skipped++;
                    continue;
                }
                self::upsert($tenantId, $uid, $dateStr, $dutyType, null, null, $periodId, $overwrite);
                $written++;
            }
            $cur->modify('+1 day');
        }
        return ['written'=>$written,'skipped'=>$skipped,'blocked'=>$blocked];
    }

    /**
     * Catalogue of pattern presets. Each is an ordered list of duty types
     * — one entry per day of the cycle.
     */
    public static function patternCatalogue(): array {
        return [
            '5on2off'      => ['key'=>'5on2off','label'=>'5 on / 2 off (Mon–Fri flying)','cycle'=>['flight','flight','flight','flight','flight','off','off']],
            '4on3off'      => ['key'=>'4on3off','label'=>'4 on / 3 off',                 'cycle'=>['flight','flight','flight','flight','off','off','off']],
            '5on4off'      => ['key'=>'5on4off','label'=>'5 on / 4 off (long-haul)',     'cycle'=>['flight','flight','flight','flight','flight','off','off','off','off']],
            '6on1off'      => ['key'=>'6on1off','label'=>'6 on / 1 off',                 'cycle'=>['flight','flight','flight','flight','flight','flight','off']],
            'flt-sby-off'  => ['key'=>'flt-sby-off','label'=>'2 flight / 1 standby / 1 off',
                               'cycle'=>['flight','flight','standby','off']],
            'sby-rotation' => ['key'=>'sby-rotation','label'=>'Standby rotation (3 standby / 4 off)',
                               'cycle'=>['standby','standby','standby','off','off','off','off']],
            'eng-shift'    => ['key'=>'eng-shift','label'=>'Engineering shift (4 on-base / 3 off)',
                               'cycle'=>['base_duty','base_duty','base_duty','base_duty','off','off','off']],
        ];
    }

    private static function resolvePattern(string $key): ?array {
        $cat = self::patternCatalogue();
        return $cat[$key]['cycle'] ?? null;
    }

    private static function upsert(int $tenantId, int $userId, string $date,
                                   string $dutyType, ?string $dutyCode, ?string $notes,
                                   ?int $periodId, bool $overwrite): void {
        $existing = Database::fetch("SELECT id FROM rosters WHERE user_id = ? AND roster_date = ?",
                                    [$userId, $date]);
        if ($existing) {
            if ($overwrite) {
                Database::execute(
                    "UPDATE rosters SET duty_type=?, duty_code=?, notes=?, roster_period_id=?, updated_at=" . dbNow() .
                    " WHERE id = ?",
                    [$dutyType, $dutyCode, $notes, $periodId, $existing['id']]
                );
            }
            return;
        }
        Database::insert(
            "INSERT INTO rosters (tenant_id, user_id, roster_date, duty_type, duty_code, notes, roster_period_id)
             VALUES (?,?,?,?,?,?,?)",
            [$tenantId, $userId, $date, $dutyType, $dutyCode, $notes, $periodId]
        );
    }
}
