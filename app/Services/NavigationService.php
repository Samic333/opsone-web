<?php
/**
 * NavigationService — builds the visible sidebar for the current user.
 *
 * Loads config/sidebar.php, applies permission / role / module gates, and
 * returns a compact tree of sections + items that the partial renders as-is.
 *
 * All gating logic lives here so the layout stays dumb.
 */
class NavigationService {

    private static ?array $cached = null;
    private static ?array $badgesCache = null;

    // Module codes used by the sidebar, mapped so sections/items can ask
    // "is this module enabled for my tenant?" without N round-trips.
    private static ?array $tenantModulesCache = null;

    /**
     * Build the sidebar the current user should see.
     *
     * @return array<array{title:string, items:array}>  zero or more sections
     */
    public static function build(): array {
        if (self::$cached !== null) return self::$cached;

        $cfg = require CONFIG_PATH . '/sidebar.php';
        $key = isPlatformOnly() ? 'platform' : 'airline';
        $sections = $cfg[$key] ?? [];

        $out = [];
        foreach ($sections as $sec) {
            if (!self::sectionPassesGate($sec)) continue;

            $visibleItems = [];
            foreach (($sec['items'] ?? []) as $item) {
                if (!self::itemPassesGate($item)) continue;
                $visibleItems[] = $item;
            }
            if (empty($visibleItems)) continue;   // hide empty sections

            $out[] = [
                'title' => $sec['title'] ?? '',
                'items' => $visibleItems,
            ];
        }

        return self::$cached = $out;
    }

    /**
     * Section-level gate: platform/airline scope + any roles listed.
     */
    private static function sectionPassesGate(array $sec): bool {
        if (!empty($sec['platform']) && !isPlatformOnly())   return false;
        if (!empty($sec['airline'])  && isPlatformOnly())    return false;

        if (!empty($sec['roles']) && !hasAnyRole($sec['roles'])) return false;

        if (!empty($sec['module']) && !self::moduleEnabled($sec['module'])) return false;

        if (!empty($sec['when']) && is_callable($sec['when'])) {
            if (!call_user_func($sec['when'])) return false;
        }

        return true;
    }

    /**
     * Item-level gate: all the usual checks + module enabled.
     */
    private static function itemPassesGate(array $item): bool {
        if (!empty($item['platform']) && !isPlatformOnly()) return false;
        if (!empty($item['airline'])  && isPlatformOnly())  return false;

        if (!empty($item['roles']) && !hasAnyRole($item['roles'])) return false;

        // Strict module gate: if the item references a module code, that
        // module must be enabled for the tenant (platform users bypass).
        if (!empty($item['module']) && !self::moduleEnabled($item['module'])) return false;

        // Fine-grained capability check (module + capability), e.g. 'rostering.edit'
        if (!empty($item['capability'])) {
            [$mod, $cap] = array_pad(explode('.', $item['capability'], 2), 2, 'view');
            if (!canAccessModule($mod, $cap)) return false;
        }

        if (!empty($item['when']) && is_callable($item['when'])) {
            if (!call_user_func($item['when'])) return false;
        }

        return true;
    }

    /**
     * Is the given module code enabled for the current tenant?
     * Platform users → always true (they bypass module gates).
     */
    public static function moduleEnabled(string $code): bool {
        if (isPlatformUser()) return true;

        $tenantId = (int) currentTenantId();
        if ($tenantId <= 0) return false;

        if (self::$tenantModulesCache === null) {
            try {
                $rows = Database::fetchAll(
                    "SELECT m.code, tm.is_enabled
                     FROM modules m
                     LEFT JOIN tenant_modules tm
                       ON tm.module_id = m.id AND tm.tenant_id = ?",
                    [$tenantId]
                );
                $map = [];
                foreach ($rows as $r) {
                    $map[$r['code']] = !empty($r['is_enabled']);
                }
                self::$tenantModulesCache = $map;
            } catch (\Throwable $e) {
                // DB not ready — be permissive so sidebar doesn't disappear
                // in dev. Enforcement still happens at page level.
                self::$tenantModulesCache = [];
                return true;
            }
        }

        // If module not listed in tenant_modules at all, treat as disabled.
        // If module code is not even in the catalog, default to true (non-gated).
        if (!array_key_exists($code, self::$tenantModulesCache)) {
            return false;
        }
        return self::$tenantModulesCache[$code];
    }

    /**
     * Is a link active for the given current path?
     *
     *  match              — substring prefix (default: str_starts_with)
     *  match_exact        — exact match required
     *  match_prefixes     — additional prefixes that should also light up
     */
    public static function isActive(array $item, string $currentPath): bool {
        $m = $item['match'] ?? $item['href'] ?? '';
        if (!$m) return false;

        if (!empty($item['match_exact'])) {
            if ($currentPath === $m) return true;
        } else {
            if ($currentPath === $m || str_starts_with($currentPath, rtrim($m, '/') . '/')
                || $currentPath === rtrim($m, '/')
                || str_starts_with($currentPath, $m)) return true;
        }

        foreach (($item['match_prefixes'] ?? []) as $p) {
            if (str_starts_with($currentPath, $p)) return true;
        }
        return false;
    }

    /**
     * Compute every badge value used by the sidebar at once, so the renderer
     * never has to run SQL inline. Empty / zero badges render as nothing.
     */
    public static function badges(): array {
        if (self::$badgesCache !== null) return self::$badgesCache;

        $b = [];
        $tid = currentTenantId();
        $uid = currentUser()['id'] ?? null;

        // Platform badges
        if (isPlatformOnly()) {
            try {
                $b['pending_onboarding'] = OnboardingRequest::countPending();
            } catch (\Throwable $e) { $b['pending_onboarding'] = 0; }
            return self::$badgesCache = $b;
        }

        if (!$tid || !$uid) return self::$badgesCache = [];

        // Helper: run a single-value count, swallow exceptions
        $cnt = function (string $sql, array $args = []) {
            try { return (int)(Database::fetch($sql, $args)['c'] ?? 0); }
            catch (\Throwable $e) { return 0; }
        };

        $b['pending_devices']         = (int) (Device::countPending($tid) ?? 0);

        $b['pending_personnel_docs']  = 0;
        $b['pending_change_requests'] = 0;
        try {
            if (class_exists('CrewDocumentModel')) {
                $b['pending_personnel_docs'] = (int) CrewDocumentModel::pendingApprovalCount($tid);
            }
            if (class_exists('ChangeRequestModel')) {
                $b['pending_change_requests'] = (int) ChangeRequestModel::pendingCount($tid);
            }
        } catch (\Throwable $e) { /* ignore */ }

        $b['safety_pending_replies'] = $cnt(
            "SELECT COUNT(DISTINCT sr.id) AS c
               FROM safety_reports sr
               JOIN safety_report_threads lt
                 ON lt.report_id = sr.id
                AND lt.is_internal = 0
                AND lt.created_at = (SELECT MAX(t2.created_at) FROM safety_report_threads t2
                                     WHERE t2.report_id = sr.id AND t2.is_internal = 0)
              WHERE sr.tenant_id = ? AND sr.reporter_id = ?
                AND sr.is_draft = 0 AND sr.status NOT IN ('closed','draft')
                AND lt.author_id != sr.reporter_id",
            [$tid, $uid]
        );

        $b['safety_draft_count'] = $cnt(
            "SELECT COUNT(*) c FROM safety_reports
              WHERE tenant_id = ? AND reporter_id = ? AND is_draft = 1",
            [$tid, $uid]
        );

        $b['notif_unread'] = $cnt(
            "SELECT COUNT(*) c FROM notifications
              WHERE user_id = ? AND tenant_id = ? AND is_read = 0",
            [$uid, $tid]
        );

        $b['my_fdm_pending'] = $cnt(
            "SELECT COUNT(*) c FROM fdm_events
              WHERE tenant_id = ? AND pilot_user_id = ? AND pilot_ack_at IS NULL",
            [$tid, $uid]
        );

        $b['roster_draft_revisions'] = $cnt(
            "SELECT COUNT(*) c FROM roster_revisions
              WHERE tenant_id = ? AND status = 'draft'",
            [$tid]
        );

        $b['roster_pending_changes'] = $cnt(
            "SELECT COUNT(*) c FROM roster_changes
              WHERE tenant_id = ? AND status = 'pending'",
            [$tid]
        );

        $b['duty_exceptions_pending'] = $cnt(
            "SELECT COUNT(*) c FROM duty_exceptions
              WHERE tenant_id = ? AND status = 'pending'",
            [$tid]
        );

        $b['aog_count'] = $cnt(
            "SELECT COUNT(*) c FROM aircraft
              WHERE tenant_id = ? AND status = 'aog'",
            [$tid]
        );

        $b['per_diem_submitted'] = $cnt(
            "SELECT COUNT(*) c FROM per_diem_claims
              WHERE tenant_id = ? AND status = 'submitted'",
            [$tid]
        );

        return self::$badgesCache = $b;
    }
}

// ─── Sidebar custom predicates (used by the `when` field) ────────────────────

/**
 * Duty Reporting — show the whole section if either (a) user is an admin
 * for any of the allowed admin roles, or (b) the tenant has duty reporting
 * enabled AND the user's role is whitelisted as a crew self-reporter.
 */
function sidebar_show_duty_group(): bool {
    $admin = hasAnyRole(['airline_admin','hr','chief_pilot','head_cabin_crew',
                         'engineering_manager','base_manager','scheduler']);
    if ($admin) return true;
    return sidebar_duty_crew_allowed();
}

function sidebar_duty_crew_allowed(): bool {
    $tid = (int) currentTenantId();
    if ($tid <= 0 || !class_exists('DutyReportingSettings')) return false;
    try {
        $drs = DutyReportingSettings::forTenant($tid);
        if (empty($drs['enabled'])) return false;
        return DutyReportingSettings::userAllowed($tid, $_SESSION['user_roles'] ?? []);
    } catch (\Throwable $e) {
        return false;
    }
}
