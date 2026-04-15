<?php
/**
 * CrewProfileController — crew profile management
 *
 * /crew-profiles       → list all crew (airline_admin, hr, chief_pilot, head_cabin_crew, training_admin)
 * /crew-profiles/{id}  → full profile detail
 * /my-profile          → self-service (any crew member views/edits own profile)
 *
 * Qualification CRUD:
 * POST /crew-profiles/{id}/qualifications/add
 * POST /crew-profiles/{id}/qualifications/delete/{qid}
 *
 * My Profile qualification add:
 * POST /my-profile/qualifications/add
 * POST /my-profile/qualifications/delete/{qid}
 */
class CrewProfileController {

    // ─── Crew List ──────────────────────────────────────────

    public function index(): void {
        RbacMiddleware::requireRole(['airline_admin', 'hr', 'chief_pilot', 'head_cabin_crew',
                                     'training_admin', 'safety_officer', 'super_admin']);

        $tenantId = currentTenantId();

        $deptFilter  = $_GET['dept']  ?? null;
        $baseFilter  = $_GET['base']  ?? null;
        $fleetFilter = $_GET['fleet'] ?? null;

        $crew        = CrewProfileModel::allForTenant($tenantId, $deptFilter, $baseFilter, $fleetFilter);
        $departments = Database::fetchAll("SELECT id, name FROM departments WHERE tenant_id = ? ORDER BY name", [$tenantId]);
        $bases       = Database::fetchAll("SELECT id, name FROM bases       WHERE tenant_id = ? ORDER BY name", [$tenantId]);
        $fleets      = Database::fetchAll("SELECT id, name FROM fleets      WHERE tenant_id = ? ORDER BY name", [$tenantId]);

        $pageTitle    = 'Crew Profiles';
        $pageSubtitle = 'Operational people records';

        ob_start();
        require VIEWS_PATH . '/crew/index.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    // ─── Crew Detail ────────────────────────────────────────

    public function show(int $userId): void {
        RbacMiddleware::requireRole(['airline_admin', 'hr', 'chief_pilot', 'head_cabin_crew',
                                     'training_admin', 'safety_officer', 'super_admin']);

        $tenantId = currentTenantId();
        $user     = UserModel::find($userId);

        if (!$user || $user['tenant_id'] != $tenantId) {
            flash('error', 'Crew member not found.');
            redirect('/crew-profiles');
        }

        $crewProfile    = CrewProfileModel::findByUser($userId) ?? [];
        $licenses       = CrewProfileModel::getLicenses($userId);
        $qualifications = QualificationModel::forUser($userId);
        $completion     = CrewProfileModel::calcCompletion($userId);

        // Determine profile type for variant display
        $userRoles   = UserModel::getRoles($userId);
        $roleSlugs   = array_column($userRoles, 'slug');
        $profileType = 'crew'; // default
        if (in_array('pilot', $roleSlugs) || in_array('chief_pilot', $roleSlugs)) {
            $profileType = 'pilot';
        } elseif (in_array('cabin_crew', $roleSlugs) || in_array('head_cabin_crew', $roleSlugs)) {
            $profileType = 'cabin_crew';
        } elseif (in_array('engineer', $roleSlugs) || in_array('engineering_manager', $roleSlugs)) {
            $profileType = 'engineer';
        }

        $pageTitle    = e($user['name']);
        $pageSubtitle = 'Crew Profile';
        $headerAction = '<a href="/users/edit/' . $user['id'] . '" class="btn btn-outline btn-sm">Edit Account →</a>';

        ob_start();
        require VIEWS_PATH . '/crew/show.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    // ─── Add Qualification (admin) ──────────────────────────

    public function addQualification(int $userId): void {
        RbacMiddleware::requireRole(['airline_admin', 'hr', 'chief_pilot', 'training_admin', 'super_admin']);

        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect("/crew-profiles/$userId");
        }

        $user = UserModel::find($userId);
        if (!$user || $user['tenant_id'] != currentTenantId()) {
            flash('error', 'Crew member not found.');
            redirect('/crew-profiles');
        }

        $qualType = trim($_POST['qual_type'] ?? '');
        $qualName = trim($_POST['qual_name'] ?? '');
        if (empty($qualType) || empty($qualName)) {
            flash('error', 'Qualification type and name are required.');
            redirect("/crew-profiles/$userId#qualifications");
        }

        QualificationModel::add($userId, currentTenantId(), $_POST);
        AuditLog::log('Added Qualification', 'user', $userId,
            "Added qualification '{$qualName}' for: {$user['name']}");
        flash('success', "Qualification added for \"{$user['name']}\".");
        redirect("/crew-profiles/$userId#qualifications");
    }

    // ─── Delete Qualification (admin) ───────────────────────

    public function deleteQualification(int $userId, int $qualId): void {
        RbacMiddleware::requireRole(['airline_admin', 'hr', 'chief_pilot', 'training_admin', 'super_admin']);

        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect("/crew-profiles/$userId");
        }

        $user = UserModel::find($userId);
        if (!$user || $user['tenant_id'] != currentTenantId()) {
            flash('error', 'Crew member not found.');
            redirect('/crew-profiles');
        }

        QualificationModel::delete($qualId, $userId);
        AuditLog::log('Deleted Qualification', 'user', $userId,
            "Deleted qualification for: {$user['name']}");
        flash('success', 'Qualification removed.');
        redirect("/crew-profiles/$userId#qualifications");
    }

    // ─── My Profile (self-service) ──────────────────────────

    public function myProfile(): void {
        requireAuth();
        $session = currentUser();
        $userId  = (int) $session['id'];
        $me      = UserModel::find($userId) ?? $session;

        $crewProfile    = CrewProfileModel::findByUser($userId) ?? [];
        $licenses       = CrewProfileModel::getLicenses($userId);
        $qualifications = QualificationModel::forUser($userId);
        $completion     = CrewProfileModel::calcCompletion($userId);

        $userRoles   = UserModel::getRoles($userId);
        $roleSlugs   = array_column($userRoles, 'slug');
        $profileType = 'crew';
        if (in_array('pilot', $roleSlugs) || in_array('chief_pilot', $roleSlugs)) {
            $profileType = 'pilot';
        } elseif (in_array('cabin_crew', $roleSlugs) || in_array('head_cabin_crew', $roleSlugs)) {
            $profileType = 'cabin_crew';
        } elseif (in_array('engineer', $roleSlugs) || in_array('engineering_manager', $roleSlugs)) {
            $profileType = 'engineer';
        }

        $pageTitle    = 'My Profile';
        $pageSubtitle = 'Your crew record and documents';

        ob_start();
        require VIEWS_PATH . '/crew/my_profile.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    // ─── Update My Profile (self-service) ───────────────────

    public function updateMyProfile(): void {
        requireAuth();
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/my-profile');
        }

        $me     = currentUser();
        $userId = (int) $me['id'];

        // Self-service: crew can only update personal/contact/document fields
        $allowed = [
            'phone', 'emergency_name', 'emergency_phone', 'emergency_relation',
        ];
        $data = array_intersect_key($_POST, array_flip($allowed));

        // Preserve existing profile data not in $allowed
        $existing = CrewProfileModel::findByUser($userId) ?? [];
        $merged   = array_merge($existing, $data);

        CrewProfileModel::save($userId, currentTenantId(), $merged);
        CrewProfileModel::updateCompletion($userId);
        AuditLog::log('Updated Own Profile', 'user', $userId, "Self-service profile update");
        flash('success', 'Your profile has been updated.');
        redirect('/my-profile');
    }

    // ─── Add My Qualification (self-service) ────────────────

    public function addMyQualification(): void {
        requireAuth();
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/my-profile');
        }

        $me     = currentUser();
        $userId = (int) $me['id'];

        $qualType = trim($_POST['qual_type'] ?? '');
        $qualName = trim($_POST['qual_name'] ?? '');
        if (empty($qualType) || empty($qualName)) {
            flash('error', 'Qualification type and name are required.');
            redirect('/my-profile#qualifications');
        }

        QualificationModel::add($userId, currentTenantId(), $_POST);
        AuditLog::log('Added Own Qualification', 'user', $userId, "Self-service: added qualification '{$qualName}'");
        flash('success', 'Qualification added.');
        redirect('/my-profile#qualifications');
    }

    // ─── Delete My Qualification (self-service) ─────────────

    public function deleteMyQualification(int $qualId): void {
        requireAuth();
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/my-profile');
        }

        $me     = currentUser();
        $userId = (int) $me['id'];

        QualificationModel::delete($qualId, $userId);
        AuditLog::log('Deleted Own Qualification', 'user', $userId, "Self-service: removed qualification");
        flash('success', 'Qualification removed.');
        redirect('/my-profile#qualifications');
    }
}
