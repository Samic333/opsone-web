<?php
/**
 * DutyReportingSettings — per-tenant configuration for Duty Reporting.
 *
 * One row per tenant (PK = tenant_id). Migration 022 seeds defaults for all
 * active tenants; new tenants get a row on first read via ensureRow().
 */
class DutyReportingSettings {

    const DEFAULTS = [
        'enabled'                     => 1,
        'allowed_roles'               => 'pilot,cabin_crew,engineer',
        'geofence_required'           => 0,
        'default_radius_m'            => 500,
        'allow_outstation'            => 1,
        'exception_approval_required' => 1,
        'clock_out_reminder_minutes'  => 840,  // 14 hours
        'trusted_device_required'     => 0,
        'biometric_required'          => 0,
        'retention_days'              => 180,
    ];

    /** @var array<int, array> per-request cache keyed by tenant_id */
    private static array $cache = [];

    /**
     * Load settings for a tenant, creating a defaults row if none exists.
     * Returns an associative array with all fields.
     *
     * Cached for the lifetime of the request — the sidebar and multiple
     * dashboards all call this, and it should not round-trip to the DB
     * more than once per request.
     */
    public static function forTenant(int $tenantId): array {
        if (isset(self::$cache[$tenantId])) {
            return self::$cache[$tenantId];
        }

        $row = Database::fetch(
            "SELECT * FROM duty_reporting_settings WHERE tenant_id = ?",
            [$tenantId]
        );

        if (!$row) {
            self::ensureRow($tenantId);
            $row = Database::fetch(
                "SELECT * FROM duty_reporting_settings WHERE tenant_id = ?",
                [$tenantId]
            );
        }

        $normalised = self::cast($row ?: array_merge(['tenant_id' => $tenantId], self::DEFAULTS));
        self::$cache[$tenantId] = $normalised;
        return $normalised;
    }

    /** Invalidate the per-request cache (call after save()). */
    public static function clearCache(?int $tenantId = null): void {
        if ($tenantId === null) { self::$cache = []; return; }
        unset(self::$cache[$tenantId]);
    }

    public static function ensureRow(int $tenantId): void {
        // Portable INSERT-if-not-exists. "INSERT OR IGNORE" (SQLite) and
        // "INSERT IGNORE" (MySQL/MariaDB) have different syntax, so we
        // do the existence check explicitly in PHP.
        $row = Database::fetch(
            "SELECT 1 FROM duty_reporting_settings WHERE tenant_id = ?",
            [$tenantId]
        );
        if ($row) return;
        Database::execute(
            "INSERT INTO duty_reporting_settings (tenant_id) VALUES (?)",
            [$tenantId]
        );
    }

    /**
     * Return the allowed roles as an array of slugs.
     */
    public static function allowedRoles(int $tenantId): array {
        $s = self::forTenant($tenantId);
        return array_values(array_filter(array_map('trim', explode(',', $s['allowed_roles'] ?? ''))));
    }

    /**
     * Whether a user holding the given roles may use Duty Reporting on this
     * tenant. Role gate: any allowed role matches any of the user's roles.
     */
    public static function userAllowed(int $tenantId, array $userRoleSlugs): bool {
        if (!$userRoleSlugs) return false;
        $allowed = self::allowedRoles($tenantId);
        if (!$allowed) return false;
        return (bool) array_intersect($allowed, $userRoleSlugs);
    }

    /**
     * Save settings. $fields is a whitelist of writable fields.
     * Caller must audit the change.
     */
    public static function save(int $tenantId, array $fields, ?int $updatedBy = null): void {
        $writable = array_keys(self::DEFAULTS);
        $sets = [];
        $params = [];
        foreach ($writable as $key) {
            if (!array_key_exists($key, $fields)) continue;
            $sets[]  = "{$key} = ?";
            $params[] = self::normalise($key, $fields[$key]);
        }
        if (!$sets) return;

        $sets[]   = "updated_at = CURRENT_TIMESTAMP";
        $sets[]   = "updated_by = ?";
        $params[] = $updatedBy;
        $params[] = $tenantId;

        self::ensureRow($tenantId);

        Database::execute(
            "UPDATE duty_reporting_settings SET " . implode(', ', $sets) . " WHERE tenant_id = ?",
            $params
        );

        // Settings just changed — drop the cached copy so the new values are
        // visible on the next fetch (including sidebar gates).
        self::clearCache($tenantId);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /** Cast raw DB row into normalised types for callers. */
    private static function cast(array $row): array {
        return [
            'tenant_id'                   => (int)  ($row['tenant_id']                   ?? 0),
            'enabled'                     => (bool) ($row['enabled']                     ?? 1),
            'allowed_roles'               =>         $row['allowed_roles']               ?? self::DEFAULTS['allowed_roles'],
            'geofence_required'           => (bool) ($row['geofence_required']           ?? 0),
            'default_radius_m'            => (int)  ($row['default_radius_m']            ?? 500),
            'allow_outstation'            => (bool) ($row['allow_outstation']            ?? 1),
            'exception_approval_required' => (bool) ($row['exception_approval_required'] ?? 1),
            'clock_out_reminder_minutes'  => (int)  ($row['clock_out_reminder_minutes']  ?? 840),
            'trusted_device_required'     => (bool) ($row['trusted_device_required']     ?? 0),
            'biometric_required'          => (bool) ($row['biometric_required']          ?? 0),
            'retention_days'              => (int)  ($row['retention_days']              ?? 180),
            'updated_at'                  =>         $row['updated_at']                  ?? null,
            'updated_by'                  =>         $row['updated_by']                  ?? null,
        ];
    }

    private static function normalise(string $key, mixed $val): mixed {
        return match ($key) {
            'enabled', 'geofence_required', 'allow_outstation',
            'exception_approval_required', 'trusted_device_required',
            'biometric_required'
                => (int) (bool) $val,

            'default_radius_m', 'clock_out_reminder_minutes', 'retention_days'
                => max(0, (int) $val),

            'allowed_roles'
                => is_array($val)
                    ? implode(',', array_map('trim', $val))
                    : trim((string) $val),

            default => $val,
        };
    }
}
