<?php


/**
 * User API Controller — user profile endpoint
 */
class UserApiController {
    public function profile(): void {
        $user = apiUser();
        $roles = apiUserRoles();
        $tenantId = apiTenantId();

        $fullUser = \UserModel::find($user['user_id']);
        if (!$fullUser) {
            jsonResponse(['error' => 'User not found'], 404);
        }

        $tenant = \Tenant::find($tenantId);
        $profile = \CrewProfileModel::findByUser($user['user_id']) ?? [];
        $licenses = \CrewProfileModel::getLicenses($user['user_id']);
        $qualifications = \QualificationModel::forUser($user['user_id']);

        jsonResponse([
            'success' => true,
            'user' => [
                'id'            => (string) $fullUser['id'],
                'name'          => $fullUser['name'],
                'email'         => $fullUser['email'],
                'employee_id'   => $fullUser['employee_id'],
                'tenant_id'     => (string) $tenantId,
                'status'        => $fullUser['status'],
                'department'    => $fullUser['department_name'] ?? null,
                'base'          => $fullUser['base_code'] ?? null,
                'roles'         => $roles,
                'mobile_access' => (bool) $fullUser['mobile_access'],
                'last_login_at' => $fullUser['last_login_at'],
            ],
            'crew_profile' => empty($profile) ? null : [
                'date_of_birth'      => $profile['date_of_birth']      ?? null,
                'nationality'        => $profile['nationality']         ?? null,
                'phone'              => $profile['phone']               ?? null,
                'address'            => $profile['address']             ?? null,
                'profile_photo_path' => $profile['profile_photo_path']  ?? null,
                'emergency_name'     => $profile['emergency_name']      ?? null,
                'emergency_phone'    => $profile['emergency_phone']     ?? null,
                'emergency_relation' => $profile['emergency_relation']  ?? null,
                'passport_number'    => $profile['passport_number']     ?? null,
                'passport_country'   => $profile['passport_country']    ?? null,
                'passport_expiry'    => $profile['passport_expiry']     ?? null,
                'visa_number'        => $profile['visa_number']         ?? null,
                'visa_country'       => $profile['visa_country']        ?? null,
                'visa_type'          => $profile['visa_type']           ?? null,
                'visa_expiry'        => $profile['visa_expiry']         ?? null,
                'medical_class'      => $profile['medical_class']       ?? null,
                'medical_expiry'     => $profile['medical_expiry']      ?? null,
                'contract_type'      => $profile['contract_type']       ?? null,
                'contract_expiry'    => $profile['contract_expiry']     ?? null,
            ],
            'eligibility' => \EligibilityService::computeForUser($user['user_id']),
            'licenses' => array_map(fn($l) => [
                'id'                => $l['id'],
                'license_type'      => $l['license_type'],
                'license_number'    => $l['license_number'] ?? null,
                'issuing_authority' => $l['issuing_authority'] ?? null,
                'issue_date'        => $l['issue_date'] ?? null,
                'expiry_date'       => $l['expiry_date'] ?? null,
            ], $licenses),
            'qualifications' => array_map(fn($q) => [
                'id'           => $q['id'],
                'qual_type'    => $q['qual_type'],
                'qual_name'    => $q['qual_name'],
                'reference_no' => $q['reference_no'] ?? null,
                'authority'    => $q['authority'] ?? null,
                'issue_date'   => $q['issue_date'] ?? null,
                'expiry_date'  => $q['expiry_date'] ?? null,
                'status'       => $q['status'],
                'notes'        => $q['notes'] ?? null,
            ], $qualifications),
            'tenant' => [
                'id'   => $tenant['id'],
                'name' => $tenant['name'],
                'code' => $tenant['code'],
            ],
        ]);
    }

    // ─── GET /api/user/modules ─────────────────────────────────────────────────
    // Returns the module codes enabled for the current user's tenant.
    // The iPad app uses these slugs to filter its sidebar/navigation at runtime,
    // replacing the hardcoded RoleConfig when a server response is available.
    public function modules(): void {
        $tenantId = apiTenantId();

        $rows = \Database::fetchAll(
            "SELECT m.code
             FROM modules m
             JOIN tenant_modules tm ON tm.module_id = m.id
             WHERE tm.tenant_id = ? AND tm.is_enabled = 1
               AND m.platform_status = 'available'
             ORDER BY m.sort_order",
            [$tenantId]
        );

        $codes = array_column($rows, 'code');

        jsonResponse([
            'success' => true,
            'tenant_id' => $tenantId,
            'modules' => $codes,
        ]);
    }

    // ─── GET /api/user/capabilities ───────────────────────────────────────────
    // Phase 10: richer mobile entitlements.
    // Returns the user's role-level capabilities grouped by module, plus
    // the tenant's active feature flags.  The iPad app can use this for
    // fine-grained UI decisions (e.g. show/hide "Upload" button vs read-only).
    public function capabilities(): void {
        $user     = apiUser();
        $tenantId = apiTenantId();
        $roles    = apiUserRoles();   // array of role slugs

        // Role capabilities from role_capabilities table
        $capRows = \Database::fetchAll(
            "SELECT DISTINCT mc.capability, m.code AS module_code
             FROM user_roles ur
             JOIN roles r ON r.id = ur.role_id
             JOIN role_capabilities rc ON rc.role_id = r.id
             JOIN module_capabilities mc ON mc.id = rc.module_capability_id
             JOIN modules m ON m.id = mc.module_id
             WHERE ur.user_id = ?
             ORDER BY m.code, mc.capability",
            [$user['user_id']]
        );

        // Group by module
        $byModule = [];
        foreach ($capRows as $row) {
            $byModule[$row['module_code']][] = $row['capability'];
        }

        // Active feature flags for this tenant (global OR tenant-specific enabled)
        $flagRows = \Database::fetchAll(
            "SELECT ff.code, ff.name, ff.category
             FROM feature_flags ff
             LEFT JOIN tenant_feature_flags tff
                   ON tff.flag_id = ff.id AND tff.tenant_id = ?
             WHERE ff.is_global = 1
                OR (tff.enabled = 1)
             ORDER BY ff.category, ff.code",
            [$tenantId]
        );
        $activeFlags = array_column($flagRows, 'code');

        jsonResponse([
            'success'         => true,
            'roles'           => $roles,
            'capabilities'    => $byModule,   // { "rostering": ["view","create"], ... }
            'active_flags'    => $activeFlags, // ["jeppesen_charts_beta", ...]
            'api_version'     => '2',
        ]);
    }
}
