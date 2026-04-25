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

    // ─── POST /api/user/profile/photo ────────────────────────────────────────
    // Multipart upload of the user's avatar image.
    //   field name: "file"  (max 5 MB, jpg/jpeg/png/heic)
    // Writes to storage/uploads/profile_photos/tenant_{T}/u_{ID}.{ext}
    // and updates crew_profiles.profile_photo_path.
    public function uploadPhoto(): void {
        $user     = apiUser();
        $tenantId = (int) apiTenantId();
        $userId   = (int) $user['user_id'];

        if (empty($_FILES['file']['name']) || ($_FILES['file']['error'] ?? -1) !== UPLOAD_ERR_OK) {
            jsonResponse(['error' => 'No file uploaded'], 422);
        }
        $f = $_FILES['file'];
        if ($f['size'] > 5 * 1024 * 1024) {
            jsonResponse(['error' => 'File too large (max 5MB)'], 413);
        }

        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','heic'], true)) {
            jsonResponse(['error' => "File type .$ext not allowed"], 415);
        }

        // Light MIME sanity check.
        $mime = function_exists('mime_content_type') ? mime_content_type($f['tmp_name']) : '';
        if ($mime && !preg_match('#^image/#', $mime)) {
            jsonResponse(['error' => "Detected MIME '$mime' is not an image"], 415);
        }

        $dir = storagePath("uploads/profile_photos/tenant_$tenantId");
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            jsonResponse(['error' => 'Could not create storage directory'], 500);
        }
        // Stable filename so re-upload overwrites the previous photo.
        $rel  = "uploads/profile_photos/tenant_$tenantId/u_$userId.$ext";
        $full = storagePath($rel);

        // Remove any older photo at a different extension so we don't keep stale files.
        foreach (['jpg','jpeg','png','heic'] as $oldExt) {
            if ($oldExt === $ext) continue;
            $stale = storagePath("uploads/profile_photos/tenant_$tenantId/u_$userId.$oldExt");
            if (file_exists($stale)) @unlink($stale);
        }

        if (!move_uploaded_file($f['tmp_name'], $full)) {
            jsonResponse(['error' => 'Failed to save uploaded file'], 500);
        }

        // Path the mobile app retrieves via the view endpoint below.
        // Store as `/api/user/photo/{user_id}.{ext}` so the mobile CrewAvatar's
        // existing absolute-vs-relative URL handler resolves correctly.
        $publicPath = "/api/user/photo/$userId.$ext";

        // Upsert crew_profiles row.
        $existing = Database::fetch(
            "SELECT user_id FROM crew_profiles WHERE user_id = ? LIMIT 1",
            [$userId]
        );
        if ($existing) {
            Database::query(
                "UPDATE crew_profiles SET profile_photo_path = ? WHERE user_id = ?",
                [$publicPath, $userId]
            );
        } else {
            Database::insert(
                "INSERT INTO crew_profiles (user_id, tenant_id, profile_photo_path) VALUES (?,?,?)",
                [$userId, $tenantId, $publicPath]
            );
        }

        AuditLog::log('profile_photo_uploaded', 'user', $userId, $rel);

        jsonResponse([
            'success'            => true,
            'profile_photo_path' => $publicPath,
            'size'               => (int)$f['size'],
        ]);
    }

    // ─── GET /api/user/photo/{filename} ──────────────────────────────────────
    // Streams a stored profile photo. The "filename" route param is
    // "{user_id}.{ext}" — auth-required so other tenants can't read.
    public function viewPhoto(string $filename): void {
        $tenantId = (int) apiTenantId();
        $callerId = (int) apiUser()['user_id'];

        if (!preg_match('/^(\d+)\.(jpg|jpeg|png|heic)$/i', $filename, $m)) {
            http_response_code(400); echo 'Bad filename'; return;
        }
        $targetUserId = (int)$m[1];
        $ext          = strtolower($m[2]);

        // Tenant isolation: target user must be in the caller's tenant.
        $u = Database::fetch(
            "SELECT id FROM users WHERE id = ? AND tenant_id = ?",
            [$targetUserId, $tenantId]
        );
        if (!$u) { http_response_code(404); echo 'Not found'; return; }

        $path = storagePath("uploads/profile_photos/tenant_$tenantId/u_$targetUserId.$ext");
        if (!file_exists($path)) { http_response_code(404); echo 'Photo not found'; return; }

        $contentType = $ext === 'png' ? 'image/png'
                     : ($ext === 'heic' ? 'image/heic' : 'image/jpeg');
        header("Content-Type: $contentType");
        header('Cache-Control: private, max-age=3600');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
}
