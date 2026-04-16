<?php
/**
 * CrewProfileModel — extended crew compliance data
 * Handles: crew_profiles (1:1 with users) and licenses (many per user)
 */
class CrewProfileModel {

    // ─── Driver-aware date helpers ──────────────────────

    private static function isSqlite(): bool {
        return env('DB_DRIVER', 'mysql') === 'sqlite';
    }

    private static function currentDate(): string {
        return self::isSqlite() ? "DATE('now')" : "CURDATE()";
    }

    private static function dateAddDays(int $days): string {
        return self::isSqlite()
            ? "DATE('now', '+{$days} days')"
            : "DATE_ADD(CURDATE(), INTERVAL {$days} DAY)";
    }

    // ─── Crew Profile ───────────────────────────────────

    public static function findByUser(int $userId): ?array {
        return Database::fetch(
            "SELECT * FROM crew_profiles WHERE user_id = ?",
            [$userId]
        );
    }

    /**
     * Upsert crew profile — works on both SQLite and MySQL.
     */
    public static function save(int $userId, int $tenantId, array $data): void {
        $fields = [
            'date_of_birth', 'nationality', 'phone',
            'emergency_name', 'emergency_phone', 'emergency_relation',
            'passport_number', 'passport_country', 'passport_expiry',
            'medical_class', 'medical_expiry',
            'contract_type', 'contract_expiry',
        ];

        $values = [];
        foreach ($fields as $f) {
            $values[] = isset($data[$f]) && $data[$f] !== '' ? $data[$f] : null;
        }

        $existing = self::findByUser($userId);

        if ($existing) {
            $setClauses = implode(', ', array_map(fn($f) => "$f = ?", $fields));
            Database::execute(
                "UPDATE crew_profiles SET $setClauses, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?",
                [...$values, $userId]
            );
        } else {
            $cols = implode(', ', $fields);
            $placeholders = implode(', ', array_fill(0, count($fields), '?'));
            Database::insert(
                "INSERT INTO crew_profiles (user_id, tenant_id, $cols) VALUES (?, ?, $placeholders)",
                [$userId, $tenantId, ...$values]
            );
        }
    }

    // ─── Licenses ───────────────────────────────────────

    public static function getLicenses(int $userId): array {
        return Database::fetchAll(
            "SELECT * FROM licenses WHERE user_id = ? ORDER BY expiry_date ASC",
            [$userId]
        );
    }

    public static function addLicense(int $userId, int $tenantId, array $data): int {
        return Database::insert(
            "INSERT INTO licenses (user_id, tenant_id, license_type, license_number, issuing_authority, issue_date, expiry_date, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $userId, $tenantId,
                $data['license_type'],
                !empty($data['license_number'])    ? $data['license_number']    : null,
                !empty($data['issuing_authority'])  ? $data['issuing_authority'] : null,
                !empty($data['issue_date'])         ? $data['issue_date']        : null,
                !empty($data['expiry_date'])        ? $data['expiry_date']       : null,
                !empty($data['notes'])              ? $data['notes']             : null,
            ]
        );
    }

    public static function findLicense(int $licenseId): ?array {
        return Database::fetch("SELECT * FROM licenses WHERE id = ?", [$licenseId]);
    }

    public static function deleteLicense(int $licenseId, int $userId): void {
        Database::execute(
            "DELETE FROM licenses WHERE id = ? AND user_id = ?",
            [$licenseId, $userId]
        );
    }

    // ─── Compliance queries ──────────────────────────────

    /** Licences expiring within $daysAhead days (not yet expired). */
    public static function expiringLicenses(int $tenantId, int $daysAhead = 90): array {
        $today  = self::currentDate();
        $future = self::dateAddDays($daysAhead);
        return Database::fetchAll(
            "SELECT l.*, u.name as user_name, u.employee_id
             FROM licenses l
             JOIN users u ON l.user_id = u.id
             WHERE l.tenant_id = ? AND l.expiry_date IS NOT NULL
               AND l.expiry_date BETWEEN $today AND $future
             ORDER BY l.expiry_date ASC",
            [$tenantId]
        );
    }

    /** Licences already expired. */
    public static function expiredLicenses(int $tenantId, int $limit = 10): array {
        $today = self::currentDate();
        return Database::fetchAll(
            "SELECT l.*, u.name as user_name, u.employee_id
             FROM licenses l
             JOIN users u ON l.user_id = u.id
             WHERE l.tenant_id = ? AND l.expiry_date IS NOT NULL AND l.expiry_date < $today
             ORDER BY l.expiry_date DESC LIMIT ?",
            [$tenantId, $limit]
        );
    }

    /** Medicals expiring within $daysAhead days. */
    public static function expiringMedicals(int $tenantId, int $daysAhead = 90): array {
        $today  = self::currentDate();
        $future = self::dateAddDays($daysAhead);
        return Database::fetchAll(
            "SELECT cp.*, u.name as user_name, u.employee_id
             FROM crew_profiles cp
             JOIN users u ON cp.user_id = u.id
             WHERE cp.tenant_id = ? AND cp.medical_expiry IS NOT NULL
               AND cp.medical_expiry BETWEEN $today AND $future
             ORDER BY cp.medical_expiry ASC",
            [$tenantId]
        );
    }

    /** Passports expiring within $daysAhead days. */
    public static function expiringPassports(int $tenantId, int $daysAhead = 180): array {
        $today  = self::currentDate();
        $future = self::dateAddDays($daysAhead);
        return Database::fetchAll(
            "SELECT cp.*, u.name as user_name, u.employee_id
             FROM crew_profiles cp
             JOIN users u ON cp.user_id = u.id
             WHERE cp.tenant_id = ? AND cp.passport_expiry IS NOT NULL
               AND cp.passport_expiry BETWEEN $today AND $future
             ORDER BY cp.passport_expiry ASC",
            [$tenantId]
        );
    }

    /** Summary counts for dashboard widgets. */
    public static function complianceSummary(int $tenantId): array {
        return [
            'expiring_licenses'      => count(self::expiringLicenses($tenantId, 90)),
            'expired_licenses'       => count(self::expiredLicenses($tenantId, 50)),
            'expiring_medicals'      => count(self::expiringMedicals($tenantId, 90)),
            'expiring_passports'     => count(self::expiringPassports($tenantId, 180)),
            'expiring_qualifications'=> count(QualificationModel::expiringForTenant($tenantId, 90)),
        ];
    }

    // ─── Profile Completion ─────────────────────────────────

    /**
     * Calculate profile completion percentage for a user.
     * Weighted scoring across user record, crew_profile, and licenses.
     * Returns 0-100 integer.
     */
    public static function calcCompletion(int $userId): int {
        $user    = Database::fetch("SELECT * FROM users WHERE id = ?", [$userId]);
        $profile = self::findByUser($userId);
        $licenses = self::getLicenses($userId);

        if (!$user) return 0;

        $score = 0;
        // User core (30 pts)
        if (!empty($user['name']))              $score += 5;
        if (!empty($user['email']))             $score += 5;
        if (!empty($user['employee_id']))       $score += 5;
        if (!empty($user['department_id']))     $score += 5;
        if (!empty($user['base_id']))           $score += 5;
        if (!empty($user['employment_status'])) $score += 5;
        // Crew profile (50 pts)
        if (!empty($profile['date_of_birth']))   $score += 5;
        if (!empty($profile['nationality']))     $score += 5;
        if (!empty($profile['phone']))           $score += 5;
        if (!empty($profile['emergency_name']))  $score += 5;
        if (!empty($profile['emergency_phone'])) $score += 5;
        if (!empty($profile['passport_number'])) $score += 5;
        if (!empty($profile['passport_expiry'])) $score += 5;
        if (!empty($profile['medical_class']))   $score += 5;
        if (!empty($profile['medical_expiry']))  $score += 5;
        if (!empty($profile['contract_type']))   $score += 5;
        // Licenses (20 pts)
        if (count($licenses) >= 1) $score += 10;
        if (count($licenses) >= 2) $score += 10;

        return min(100, $score);
    }

    /**
     * Recalculate and persist profile_completion_pct for a user.
     */
    public static function updateCompletion(int $userId): void {
        $pct = self::calcCompletion($userId);
        Database::execute(
            "UPDATE users SET profile_completion_pct = ? WHERE id = ?",
            [$pct, $userId]
        );
    }

    /**
     * Fetch all crew for a tenant with profile data for the crew list.
     */
    public static function allForTenant(int $tenantId, ?string $dept = null,
                                        ?string $base = null, ?string $fleet = null): array {
        $where  = ["u.tenant_id = ?", "u.status != 'inactive'"];
        $params = [$tenantId];

        if ($dept)  { $where[] = "u.department_id = ?"; $params[] = $dept; }
        if ($base)  { $where[] = "u.base_id = ?";       $params[] = $base; }
        if ($fleet) { $where[] = "u.fleet_id = ?";      $params[] = $fleet; }

        $sql = "SELECT u.id, u.name, u.email, u.employee_id, u.status,
                       u.profile_completion_pct, u.employment_status,
                       d.name  as department_name,
                       b.name  as base_name,
                       f.name  as fleet_name,
                       cp.medical_expiry, cp.passport_expiry,
                       (SELECT COUNT(*) FROM licenses l WHERE l.user_id = u.id) as license_count,
                       (SELECT GROUP_CONCAT(r.name, ', ')
                        FROM user_roles ur JOIN roles r ON ur.role_id = r.id
                        WHERE ur.user_id = u.id AND ur.tenant_id = u.tenant_id
                        LIMIT 3) as role_names
                FROM users u
                LEFT JOIN departments d ON u.department_id = d.id
                LEFT JOIN bases b       ON u.base_id = b.id
                LEFT JOIN fleets f      ON u.fleet_id = f.id
                LEFT JOIN crew_profiles cp ON cp.user_id = u.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY u.name ASC";

        return Database::fetchAll($sql, $params);
    }
}
