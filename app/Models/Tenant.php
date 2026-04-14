<?php
/**
 * Tenant Model — airline tenant CRUD + Phase Zero enhanced fields
 */
class Tenant {

    public static function all(?string $status = null): array {
        if ($status === 'active') {
            return Database::fetchAll(
                "SELECT * FROM tenants WHERE is_active = 1 ORDER BY name ASC"
            );
        }
        return Database::fetchAll("SELECT * FROM tenants ORDER BY name ASC");
    }

    public static function find(int $id): ?array {
        return Database::fetch("SELECT * FROM tenants WHERE id = ?", [$id]);
    }

    public static function create(array $data): int {
        return Database::insert(
            "INSERT INTO tenants
                (name, legal_name, display_name, code, icao_code, iata_code,
                 contact_email, primary_country, primary_base,
                 support_tier, onboarding_status, is_active,
                 expected_headcount, headcount_pilots, headcount_cabin,
                 headcount_engineers, headcount_schedulers, headcount_training,
                 headcount_safety, headcount_hr, notes, onboarded_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                $data['name'],
                $data['legal_name']           ?? $data['name'],
                $data['display_name']         ?? null,
                $data['code'],
                $data['icao_code']            ?? null,
                $data['iata_code']            ?? null,
                $data['contact_email']        ?? null,
                $data['primary_country']      ?? null,
                $data['primary_base']         ?? null,
                $data['support_tier']         ?? 'standard',
                $data['onboarding_status']    ?? 'active',
                $data['expected_headcount']   ?? null,
                $data['headcount_pilots']     ?? null,
                $data['headcount_cabin']      ?? null,
                $data['headcount_engineers']  ?? null,
                $data['headcount_schedulers'] ?? null,
                $data['headcount_training']   ?? null,
                $data['headcount_safety']     ?? null,
                $data['headcount_hr']         ?? null,
                $data['notes']                ?? null,
            ]
        );
    }

    public static function update(int $id, array $data): void {
        Database::execute(
            "UPDATE tenants SET
                name = ?, legal_name = ?, display_name = ?, code = ?,
                icao_code = ?, iata_code = ?,
                contact_email = ?, primary_country = ?, primary_base = ?,
                support_tier = ?, onboarding_status = ?,
                expected_headcount = ?, headcount_pilots = ?, headcount_cabin = ?,
                headcount_engineers = ?, headcount_schedulers = ?, headcount_training = ?,
                headcount_safety = ?, headcount_hr = ?, notes = ?,
                updated_at = CURRENT_TIMESTAMP
             WHERE id = ?",
            [
                $data['name'],
                $data['legal_name']           ?? $data['name'],
                $data['display_name']         ?? null,
                $data['code'],
                $data['icao_code']            ?? null,
                $data['iata_code']            ?? null,
                $data['contact_email']        ?? null,
                $data['primary_country']      ?? null,
                $data['primary_base']         ?? null,
                $data['support_tier']         ?? 'standard',
                $data['onboarding_status']    ?? 'active',
                $data['expected_headcount']   ?? null,
                $data['headcount_pilots']     ?? null,
                $data['headcount_cabin']      ?? null,
                $data['headcount_engineers']  ?? null,
                $data['headcount_schedulers'] ?? null,
                $data['headcount_training']   ?? null,
                $data['headcount_safety']     ?? null,
                $data['headcount_hr']         ?? null,
                $data['notes']                ?? null,
                $id,
            ]
        );
    }

    public static function toggleActive(int $id): void {
        $tenant = self::find($id);
        if (!$tenant) return;

        $newActive = $tenant['is_active'] ? 0 : 1;
        $newStatus = $newActive ? 'active' : 'suspended';
        $suspendedAt = $newActive ? null : date('Y-m-d H:i:s');

        Database::execute(
            "UPDATE tenants
             SET is_active = ?, onboarding_status = ?,
                 suspended_at = ?,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = ?",
            [$newActive, $newStatus, $suspendedAt, $id]
        );
    }

    public static function countActive(): int {
        return (int)(Database::fetch("SELECT COUNT(*) as c FROM tenants WHERE is_active = 1")['c'] ?? 0);
    }

    public static function countAll(): int {
        return (int)(Database::fetch("SELECT COUNT(*) as c FROM tenants")['c'] ?? 0);
    }

    public static function stats(int $tenantId): array {
        $users    = Database::fetch("SELECT COUNT(*) as c FROM users WHERE tenant_id = ?", [$tenantId]);
        $devices  = Database::fetch(
            "SELECT COUNT(*) as c FROM devices WHERE tenant_id = ? AND approval_status = 'pending'",
            [$tenantId]
        );
        $modules  = Database::fetch(
            "SELECT COUNT(*) as c FROM tenant_modules WHERE tenant_id = ? AND is_enabled = 1",
            [$tenantId]
        );
        return [
            'user_count'       => (int)($users['c'] ?? 0),
            'pending_devices'  => (int)($devices['c'] ?? 0),
            'enabled_modules'  => (int)($modules['c'] ?? 0),
        ];
    }

    /**
     * Full summary row for the platform overview (one query per tenant).
     */
    public static function platformSummary(): array {
        return Database::fetchAll(
            "SELECT t.*,
                    COUNT(DISTINCT u.id) as user_count,
                    COUNT(DISTINCT CASE WHEN d.approval_status = 'pending' THEN d.id END) as pending_devices,
                    COUNT(DISTINCT CASE WHEN tm.is_enabled = 1 THEN tm.id END) as enabled_modules
             FROM tenants t
             LEFT JOIN users u ON u.tenant_id = t.id
             LEFT JOIN devices d ON d.tenant_id = t.id
             LEFT JOIN tenant_modules tm ON tm.tenant_id = t.id
             GROUP BY t.id
             ORDER BY t.name ASC"
        );
    }

    /**
     * Get tenant contacts.
     */
    public static function getContacts(int $tenantId): array {
        return Database::fetchAll(
            "SELECT * FROM tenant_contacts WHERE tenant_id = ? ORDER BY is_primary DESC, contact_type ASC",
            [$tenantId]
        );
    }

    /**
     * Get or create tenant settings row.
     */
    public static function getSettings(int $tenantId): array {
        $settings = Database::fetch(
            "SELECT * FROM tenant_settings WHERE tenant_id = ?",
            [$tenantId]
        );
        if (!$settings) {
            Database::insert(
                "INSERT INTO tenant_settings (tenant_id) VALUES (?)",
                [$tenantId]
            );
            $settings = Database::fetch(
                "SELECT * FROM tenant_settings WHERE tenant_id = ?",
                [$tenantId]
            );
        }
        return $settings ?: [];
    }

    /**
     * Get access policies for a tenant.
     */
    public static function getAccessPolicy(int $tenantId): array {
        $policy = Database::fetch(
            "SELECT * FROM tenant_access_policies WHERE tenant_id = ?",
            [$tenantId]
        );
        if (!$policy) {
            Database::insert(
                "INSERT INTO tenant_access_policies (tenant_id) VALUES (?)",
                [$tenantId]
            );
            $policy = Database::fetch(
                "SELECT * FROM tenant_access_policies WHERE tenant_id = ?",
                [$tenantId]
            );
        }
        return $policy ?: [];
    }

    /**
     * Initialize default settings and policies for a newly created tenant.
     */
    public static function initializeDefaults(int $tenantId): void {
        // Settings
        $existing = Database::fetch("SELECT id FROM tenant_settings WHERE tenant_id = ?", [$tenantId]);
        if (!$existing) {
            Database::insert("INSERT INTO tenant_settings (tenant_id) VALUES (?)", [$tenantId]);
        }

        // Access policy
        $existing = Database::fetch("SELECT id FROM tenant_access_policies WHERE tenant_id = ?", [$tenantId]);
        if (!$existing) {
            Database::insert("INSERT INTO tenant_access_policies (tenant_id) VALUES (?)", [$tenantId]);
        }
    }
}
