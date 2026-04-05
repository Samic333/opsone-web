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

        jsonResponse([
            'success' => true,
            'user' => [
                'id' => $fullUser['id'],
                'name' => $fullUser['name'],
                'email' => $fullUser['email'],
                'employee_id' => $fullUser['employee_id'],
                'status' => $fullUser['status'],
                'department' => $fullUser['department_name'] ?? null,
                'base' => $fullUser['base_code'] ?? null,
                'roles' => $roles,
                'mobile_access' => (bool) $fullUser['mobile_access'],
                'last_login_at' => $fullUser['last_login_at'],
            ],
            'tenant' => [
                'id' => $tenant['id'],
                'name' => $tenant['name'],
                'code' => $tenant['code'],
            ],
        ]);
    }
}
