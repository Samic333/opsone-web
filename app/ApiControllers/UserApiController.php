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

        jsonResponse([
            'success' => true,
            'user' => [
                'id'            => $fullUser['id'],
                'name'          => $fullUser['name'],
                'email'         => $fullUser['email'],
                'employee_id'   => $fullUser['employee_id'],
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
            'tenant' => [
                'id'   => $tenant['id'],
                'name' => $tenant['name'],
                'code' => $tenant['code'],
            ],
        ]);
    }
}
