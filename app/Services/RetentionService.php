<?php
/**
 * RetentionService — data retention policy enforcement
 *
 * Each tenant can override the platform defaults per module.
 * Overrides are stored in `tenant_retention_policies` (created by migration
 * 019_phase0_safety_reports_mysql.sql Section C).
 *
 * Schema reference for tenant_retention_policies:
 *   CREATE TABLE tenant_retention_policies (
 *       id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 *       tenant_id   INT UNSIGNED NOT NULL,
 *       module      VARCHAR(100) NOT NULL,
 *       retain_days INT UNSIGNED NOT NULL,
 *       note        VARCHAR(255) DEFAULT NULL,
 *       updated_by  INT UNSIGNED DEFAULT NULL,
 *       updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 *       UNIQUE KEY uq_retention (tenant_id, module),
 *       FK tenant_id  → tenants(id) ON DELETE CASCADE,
 *       FK updated_by → users(id)   ON DELETE SET NULL
 *   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 *
 * Module-to-table mapping used by purge():
 *   safety_reports   → safety_reports.created_at
 *   notices          → notices.created_at
 *   roster_changes   → roster_changes.created_at
 *   fdm_uploads      → fdm_uploads.created_at
 *   fdm_events       → fdm_events.created_at
 *   audit_log        → audit_log.created_at
 *   notifications    → notifications.created_at
 */
class RetentionService {

    // -------------------------------------------------------------------------
    // Default retention windows (days)
    // These apply when no tenant-specific override exists.
    // -------------------------------------------------------------------------

    /** @var array<string, int> module => days */
    const DEFAULTS = [
        'safety_reports'  => 2555,   // 7 years  — regulatory minimum
        'notices'         => 365,    // 1 year
        'roster_changes'  => 730,    // 2 years
        'fdm_uploads'     => 1825,   // 5 years  — ICAO Annex 6 guidance
        'fdm_events'      => 1825,   // 5 years
        'audit_log'       => 1095,   // 3 years
        'notifications'   => 90,     // 90 days  — inbox clutter management
    ];

    /**
     * Mapping from module name to the database table and timestamp column
     * that purge() will use to identify expired rows.
     *
     * @var array<string, array{table: string, tenant_col: string, ts_col: string}>
     */
    const MODULE_TABLE_MAP = [
        'safety_reports'  => ['table' => 'safety_reports',  'tenant_col' => 'tenant_id', 'ts_col' => 'created_at'],
        'notices'         => ['table' => 'notices',         'tenant_col' => 'tenant_id', 'ts_col' => 'created_at'],
        'roster_changes'  => ['table' => 'roster_changes',  'tenant_col' => 'tenant_id', 'ts_col' => 'created_at'],
        'fdm_uploads'     => ['table' => 'fdm_uploads',     'tenant_col' => 'tenant_id', 'ts_col' => 'created_at'],
        'fdm_events'      => ['table' => 'fdm_events',      'tenant_col' => 'tenant_id', 'ts_col' => 'created_at'],
        'audit_log'       => ['table' => 'audit_log',       'tenant_col' => 'tenant_id', 'ts_col' => 'created_at'],
        'notifications'   => ['table' => 'notifications',   'tenant_col' => 'tenant_id', 'ts_col' => 'created_at'],
    ];

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Return the effective retention policy for a tenant + module combination.
     *
     * Falls back to the platform default when no tenant override exists.
     *
     * @param int    $tenantId
     * @param string $module   Must be a key in self::DEFAULTS
     * @return array{
     *     module:       string,
     *     retain_days:  int,
     *     source:       'tenant'|'default',
     *     note:         string|null
     * }
     */
    public static function getPolicy(int $tenantId, string $module): array {
        // Tenant-specific override
        try {
            $stmt = self::db()->prepare(
                'SELECT retain_days, note
                   FROM tenant_retention_policies
                  WHERE tenant_id = :tenant_id
                    AND module    = :module
                  LIMIT 1'
            );
            $stmt->execute([':tenant_id' => $tenantId, ':module' => $module]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($row) {
                return [
                    'module'      => $module,
                    'retain_days' => (int) $row['retain_days'],
                    'source'      => 'tenant',
                    'note'        => $row['note'],
                ];
            }
        } catch (\Throwable $e) {
            error_log("[RetentionService] getPolicy() DB error for tenant={$tenantId} module={$module}: " . $e->getMessage());
        }

        // Platform default
        $days = self::DEFAULTS[$module] ?? 365;
        return [
            'module'      => $module,
            'retain_days' => $days,
            'source'      => 'default',
            'note'        => null,
        ];
    }

    /**
     * Purge records older than the effective retention window.
     *
     * Only rows belonging to `$tenantId` are deleted (tenant-safe).
     * Returns the number of rows deleted, or -1 on error.
     *
     * @param int    $tenantId
     * @param string $module   Must be a key in self::MODULE_TABLE_MAP
     * @return int  Number of rows deleted (-1 on error, 0 if module unknown)
     */
    public static function purge(int $tenantId, string $module): int {
        if (!isset(self::MODULE_TABLE_MAP[$module])) {
            error_log("[RetentionService] purge(): unknown module '{$module}' — no table mapping defined");
            return 0;
        }

        $policy = self::getPolicy($tenantId, $module);
        $days   = $policy['retain_days'];

        $map       = self::MODULE_TABLE_MAP[$module];
        $table     = $map['table'];
        $tenantCol = $map['tenant_col'];
        $tsCol     = $map['ts_col'];

        // Safety guard: never purge with a window < 30 days regardless of config
        if ($days < 30) {
            error_log("[RetentionService] purge(): retention window {$days}d for module '{$module}' is below 30-day safety floor — skipping");
            return -1;
        }

        try {
            $db   = self::db();
            // Use backtick-quoted identifiers; $table / $tenantCol / $tsCol are
            // only ever set from the compile-time MODULE_TABLE_MAP constant above,
            // so no SQL-injection risk.
            $sql  = "DELETE FROM `{$table}`
                      WHERE `{$tenantCol}` = :tenant_id
                        AND `{$tsCol}`     < DATE_SUB(NOW(), INTERVAL :days DAY)";
            $stmt = $db->prepare($sql);
            $stmt->execute([':tenant_id' => $tenantId, ':days' => $days]);
            $deleted = $stmt->rowCount();

            error_log("[RetentionService] purge(): tenant={$tenantId} module={$module} deleted={$deleted} (window={$days}d)");
            return $deleted;

        } catch (\Throwable $e) {
            error_log("[RetentionService] purge() DB error for tenant={$tenantId} module={$module}: " . $e->getMessage());
            return -1;
        }
    }

    /**
     * Convenience: run purge() for all known modules for a given tenant.
     *
     * @param int $tenantId
     * @return array<string, int>  module => rows deleted
     */
    public static function purgeAll(int $tenantId): array {
        $results = [];
        foreach (array_keys(self::MODULE_TABLE_MAP) as $module) {
            $results[$module] = self::purge($tenantId, $module);
        }
        return $results;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private static function db(): \PDO {
        global $pdo;
        if (!$pdo instanceof \PDO) {
            throw new \RuntimeException('RetentionService: PDO instance not available in $pdo');
        }
        return $pdo;
    }
}
