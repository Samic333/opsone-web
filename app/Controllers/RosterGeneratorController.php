<?php
/**
 * RosterGeneratorController — wizard for generating a whole month of roster
 * entries for many crew at once.
 *
 * Routes:
 *   GET  /roster/generate   — wizard form (target month + crew picker + mode)
 *   POST /roster/generate   — runs the chosen generator and returns to /roster
 *
 * Modes:
 *   - copy_from_month: clones a previous month's pattern onto the target month
 *   - pattern:         applies a rotation pattern to every selected crew member
 */
class RosterGeneratorController {

    private function requireScheduler(): void {
        RbacMiddleware::requireRole(['scheduler', 'airline_admin', 'super_admin', 'chief_pilot', 'head_cabin_crew']);
    }

    public function form(): void {
        $this->requireScheduler();
        $tenantId = (int) currentTenantId();

        $crewList   = RosterModel::getCrewList($tenantId);
        $bases      = Base::allForTenant($tenantId);
        $periods    = RosterModel::getPeriods($tenantId);
        $patterns   = RosterGeneratorService::patternCatalogue();

        // Sensible defaults: target = next month, source = current month
        $today = new \DateTime('first day of next month');
        $tgtYear  = (int)$today->format('Y');
        $tgtMonth = (int)$today->format('n');
        $srcDate  = (clone $today)->modify('-1 month');
        $srcYear  = (int)$srcDate->format('Y');
        $srcMonth = (int)$srcDate->format('n');

        // From / to defaults for pattern mode = first/last day of target month
        $tgtDays = cal_days_in_month(CAL_GREGORIAN, $tgtMonth, $tgtYear);
        $defaultFrom = sprintf('%04d-%02d-01', $tgtYear, $tgtMonth);
        $defaultTo   = sprintf('%04d-%02d-%02d', $tgtYear, $tgtMonth, $tgtDays);

        // Pre-group crew by role for the picker
        $crewByRole = [];
        foreach ($crewList as $c) {
            $crewByRole[$c['role_name']][] = $c;
        }

        $pageTitle    = 'Generate Roster';
        $pageSubtitle = 'Build a full month of duties for many crew in one go';

        ob_start();
        require VIEWS_PATH . '/roster/generate.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function generate(): void {
        $this->requireScheduler();
        if (!verifyCsrf()) {
            flash('error', 'Invalid security token.');
            redirect('/roster/generate');
        }
        $tenantId = (int) currentTenantId();

        $mode             = trim($_POST['mode'] ?? 'copy_from_month');
        $userIds          = array_values(array_unique(array_map('intval', $_POST['user_ids'] ?? [])));
        $periodId         = (int) ($_POST['roster_period_id'] ?? 0) ?: null;
        $overwrite        = !empty($_POST['overwrite']);
        $ignoreCompliance = !empty($_POST['ignore_compliance']);

        if (empty($userIds)) {
            flash('error', 'Pick at least one crew member.');
            redirect('/roster/generate');
        }
        if (count($userIds) > 500) {
            flash('error', 'Maximum 500 crew at once.');
            redirect('/roster/generate');
        }

        // Verify every user belongs to this tenant — defence-in-depth
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $valid = Database::fetchAll(
            "SELECT id FROM users WHERE tenant_id = ? AND id IN ($placeholders)",
            array_merge([$tenantId], $userIds)
        );
        if (count($valid) !== count($userIds)) {
            flash('error', 'One or more selected crew do not belong to this airline.');
            redirect('/roster/generate');
        }

        if ($mode === 'copy_from_month') {
            $srcYear  = (int) ($_POST['src_year']  ?? 0);
            $srcMonth = (int) ($_POST['src_month'] ?? 0);
            $tgtYear  = (int) ($_POST['tgt_year']  ?? 0);
            $tgtMonth = (int) ($_POST['tgt_month'] ?? 0);
            if ($srcYear < 2020 || $srcMonth < 1 || $srcMonth > 12 ||
                $tgtYear < 2020 || $tgtMonth < 1 || $tgtMonth > 12) {
                flash('error', 'Pick valid source and target months.');
                redirect('/roster/generate');
            }
            $report = RosterGeneratorService::copyMonth(
                $tenantId, $userIds, $srcYear, $srcMonth, $tgtYear, $tgtMonth,
                $periodId, $overwrite, $ignoreCompliance
            );
            $rangeFor = sprintf('%04d-%02d', $tgtYear, $tgtMonth);
        } elseif ($mode === 'pattern') {
            $patternKey = trim($_POST['pattern_key'] ?? '');
            $fromDate   = trim($_POST['from_date']   ?? '');
            $toDate     = trim($_POST['to_date']     ?? '');
            $offsetMode = trim($_POST['offset_mode'] ?? 'stagger'); // none|stagger
            if (!$patternKey || !$fromDate || !$toDate || $fromDate > $toDate) {
                flash('error', 'Pattern, from-date, and to-date are all required.');
                redirect('/roster/generate');
            }
            $rangeDays = (new \DateTime($fromDate))->diff(new \DateTime($toDate))->days + 1;
            if ($rangeDays > 365) {
                flash('error', 'Date range cannot exceed 365 days.');
                redirect('/roster/generate');
            }

            // Stagger offset: spread users across the cycle so half are off
            // when the others are working.
            $offsetMap = [];
            if ($offsetMode === 'stagger') {
                $cycle = RosterGeneratorService::patternCatalogue()[$patternKey]['cycle'] ?? null;
                $cycleLen = $cycle ? count($cycle) : 7;
                foreach ($userIds as $i => $uid) {
                    $offsetMap[(int)$uid] = $i % $cycleLen;
                }
            }
            $report = RosterGeneratorService::applyPattern(
                $tenantId, $userIds, $fromDate, $toDate, $patternKey,
                $periodId, $overwrite, $ignoreCompliance, $offsetMap
            );
            [$y, $m] = [substr($fromDate,0,4), substr($fromDate,5,2)];
            $rangeFor = "$y-$m";
        } else {
            flash('error', 'Unknown generator mode.');
            redirect('/roster/generate');
        }

        if (!empty($report['error'])) {
            flash('error', $report['error']);
            redirect('/roster/generate');
        }

        AuditLog::log('roster_generated', 'roster', 0,
            "Generator mode={$mode}, written={$report['written']}, skipped={$report['skipped']}, blocked=" . count($report['blocked']));

        // Compose a concise success flash + drop blocked detail into session for review
        $msg = "Generated {$report['written']} entries for " . count($userIds) . " crew";
        if ($report['skipped'] > 0) $msg .= ", skipped {$report['skipped']}";
        if (!empty($report['blocked'])) {
            $_SESSION['_roster_generator_blocked'] = $report['blocked'];
            $msg .= ", " . count($report['blocked']) . " blocked by eligibility checks (review queued)";
        }
        flash('success', $msg);

        // Land on the target month so the result is visible immediately
        [$y, $m] = explode('-', $rangeFor);
        redirect("/roster?year={$y}&month=" . ltrim($m, '0'));
    }
}
