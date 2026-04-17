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
                'emergency_name'     => $profile['emergency_name']      ?? null,
                'emergency_phone'    => $profile['emergency_phone']     ?? null,
                'emergency_relation' => $profile['emergency_relation']  ?? null,
                'passport_expiry'    => $profile['passport_expiry']     ?? null,
                'medical_class'      => $profile['medical_class']       ?? null,
                'medical_expiry'     => $profile['medical_expiry']      ?? null,
                'contract_type'      => $profile['contract_type']       ?? null,
                'contract_expiry'    => $profile['contract_expiry']     ?? null,
            ],
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
}
