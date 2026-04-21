<?php
/**
 * OnboardingController — airline onboarding request workflow
 *
 * Platform admins can view and action incoming onboarding requests.
 * The "create" path is for platform super admins to initiate a request
 * on behalf of a new airline (internal creation path).
 */
class OnboardingController {
    public function __construct() {
        RbacMiddleware::requirePlatformRole();
    }

    public function index(): void {
        $pending     = OnboardingRequest::all('pending');
        $inReview    = OnboardingRequest::all('in_review');
        $approved    = OnboardingRequest::all('approved');
        $rejected    = OnboardingRequest::all('rejected');
        $provisioned = OnboardingRequest::all('provisioned');
        $allModules  = Module::all(true);
        require VIEWS_PATH . '/onboarding/index.php';
    }

    public function show(int $id): void {
        $request    = OnboardingRequest::find($id);
        if (!$request) {
            flash('error', 'Onboarding request not found.');
            redirect('/platform/onboarding');
        }
        $allModules = Module::all(true);
        require VIEWS_PATH . '/onboarding/show.php';
    }

    public function create(): void {
        RbacMiddleware::requirePlatformSuperAdmin();
        $allModules = Module::all(true);
        require VIEWS_PATH . '/onboarding/create.php';
    }

    public function store(): void {
        RbacMiddleware::requirePlatformSuperAdmin();
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/platform/onboarding/create');
        }

        $legalName = trim($_POST['legal_name']  ?? '');
        $contactName  = trim($_POST['contact_name']  ?? '');
        $contactEmail = trim($_POST['contact_email'] ?? '');

        if (empty($legalName) || empty($contactName) || empty($contactEmail)) {
            flash('error', 'Legal name, contact name, and contact email are required.');
            redirect('/platform/onboarding/create');
        }

        $requestedModules = $_POST['modules'] ?? [];

        $id = OnboardingRequest::create([
            'legal_name'          => $legalName,
            'display_name'        => trim($_POST['display_name']    ?? '') ?: null,
            'icao_code'           => strtoupper(trim($_POST['icao_code'] ?? '')) ?: null,
            'iata_code'           => strtoupper(trim($_POST['iata_code'] ?? '')) ?: null,
            'primary_country'     => trim($_POST['primary_country'] ?? '') ?: null,
            'contact_name'        => $contactName,
            'contact_email'       => $contactEmail,
            'contact_phone'       => trim($_POST['contact_phone']   ?? '') ?: null,
            'expected_headcount'  => ((int)($_POST['expected_headcount'] ?? 0)) ?: null,
            'support_tier'        => $_POST['support_tier']         ?? 'standard',
            'requested_modules'   => $requestedModules,
            'notes'               => trim($_POST['notes']           ?? '') ?: null,
        ]);

        AuditService::log('onboarding.request_created', 'onboarding_request', $id,
            "Created onboarding request for: $legalName");

        flash('success', "Onboarding request #$id created for $legalName.");
        redirect("/platform/onboarding/$id");
    }

    public function markInReview(int $id): void {
        RbacMiddleware::requirePlatformSuperAdmin();
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect("/platform/onboarding/$id");
        }

        $request = OnboardingRequest::find($id);
        if (!$request || $request['status'] !== 'pending') {
            flash('error', 'Request not found or cannot be moved to in-review (must be pending).');
            redirect('/platform/onboarding');
        }

        $user  = currentUser();
        $notes = trim($_POST['review_notes'] ?? '') ?: null;
        OnboardingRequest::markInReview($id, $user['id'], $notes);

        AuditService::log('onboarding.marked_in_review', 'onboarding_request', $id,
            "Marked in review: {$request['legal_name']}");

        flash('success', "Onboarding request #{$id} marked as In Review.");
        redirect("/platform/onboarding/$id");
    }

    public function approve(int $id): void {
        RbacMiddleware::requirePlatformSuperAdmin();
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect("/platform/onboarding/$id");
        }

        $request = OnboardingRequest::find($id);
        if (!$request || !in_array($request['status'], ['pending', 'in_review'])) {
            flash('error', 'Request not found or already actioned.');
            redirect('/platform/onboarding');
        }

        $user  = currentUser();
        $notes = trim($_POST['review_notes'] ?? '') ?: null;
        OnboardingRequest::approve($id, $user['id'], $notes);

        AuditService::log('onboarding.approved', 'onboarding_request', $id,
            "Approved: {$request['legal_name']}");

        flash('success', "Onboarding request approved. Now provision the tenant to complete setup.");
        redirect("/platform/onboarding/$id");
    }

    public function reject(int $id): void {
        RbacMiddleware::requirePlatformSuperAdmin();
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect("/platform/onboarding/$id");
        }

        $request = OnboardingRequest::find($id);
        if (!$request || !in_array($request['status'], ['pending', 'in_review'])) {
            flash('error', 'Request not found or already actioned.');
            redirect('/platform/onboarding');
        }

        $user  = currentUser();
        $notes = trim($_POST['review_notes'] ?? '') ?: null;
        OnboardingRequest::reject($id, $user['id'], $notes);

        AuditService::log('onboarding.rejected', 'onboarding_request', $id,
            "Rejected: {$request['legal_name']}");

        flash('success', "Onboarding request rejected.");
        redirect('/platform/onboarding');
    }

    /**
     * Provision: convert an approved request into a live tenant.
     */
    public function provision(int $id): void {
        RbacMiddleware::requirePlatformSuperAdmin();
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect("/platform/onboarding/$id");
        }

        $request = OnboardingRequest::find($id);
        if (!$request || $request['status'] !== 'approved') {
            flash('error', 'Request must be approved before provisioning.');
            redirect("/platform/onboarding/$id");
        }

        // Build airline code from ICAO or derive from name
        $code = $request['icao_code']
             ?? strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $request['legal_name']), 0, 6));

        $tenantId = Tenant::create([
            'name'              => $request['display_name'] ?? $request['legal_name'],
            'legal_name'        => $request['legal_name'],
            'display_name'      => $request['display_name'],
            'code'              => $code,
            'icao_code'         => $request['icao_code'],
            'iata_code'         => $request['iata_code'],
            'contact_email'     => $request['contact_email'],
            'primary_country'   => $request['primary_country'],
            'support_tier'      => $request['support_tier'],
            'expected_headcount'=> $request['expected_headcount'],
            'onboarding_status' => 'active',
        ]);

        // Enable requested modules
        $requestedModules = $request['requested_modules']
            ? json_decode($request['requested_modules'], true)
            : [];
        if (!empty($requestedModules)) {
            $actor = currentUser();
            TenantModule::bulkEnable($tenantId, $requestedModules, $actor['id'] ?? null);
        }

        // Initialize settings/policy defaults
        Tenant::initializeDefaults($tenantId);

        // Create default roles
        $systemRoles = Database::fetchAll(
            "SELECT name, slug, description, role_type FROM roles WHERE tenant_id IS NULL AND is_system = 1"
        );
        foreach ($systemRoles as $role) {
            Database::insert(
                Database::insertIgnore() . " INTO roles (tenant_id, name, slug, description, is_system, role_type)
                 VALUES (?, ?, ?, ?, 0, ?)",
                [$tenantId, $role['name'], $role['slug'], $role['description'], $role['role_type']]
            );
        }

        // Create default departments
        foreach (['Flight Operations','Cabin Operations','Engineering','Human Resources',
                  'Safety','Operations','IT'] as $dept) {
            Database::insert("INSERT INTO departments (tenant_id, name) VALUES (?, ?)", [$tenantId, $dept]);
        }

        // Create default file categories
        foreach ([['Manuals','manuals'],['Safety Bulletins','safety_bulletins'],
                  ['General Documents','general']] as [$cn, $cs]) {
            Database::insert("INSERT INTO file_categories (tenant_id, name, slug) VALUES (?, ?, ?)",
                             [$tenantId, $cn, $cs]);
        }

        // Create primary admin invitation
        $actor = currentUser();
        $token = InvitationToken::create(
            $tenantId,
            $request['contact_email'],
            $request['contact_name'],
            'airline_admin',
            $actor['id'] ?? null
        );

        // Record the activation URL in the audit log so a platform admin
        // can retrieve it until the mail pipeline is wired. InvitationToken::create
        // returns either the raw token string or an array — normalise both shapes.
        $rawToken = is_array($token)
            ? ($token['token'] ?? $token['raw'] ?? null)
            : (is_string($token) ? $token : null);
        $baseUrl   = rtrim(env('APP_URL', 'https://acentoza.com'), '/');
        $activateUrl = $rawToken
            ? "$baseUrl/activate?token=" . urlencode($rawToken)
            : "$baseUrl/activate";
        AuditService::log(
            'invitation_created',
            'onboarding_request',
            $id,
            "Airline-admin invitation for {$request['contact_email']} ({$request['contact_name']}). Activation URL: {$activateUrl}"
        );

        // Mark request as provisioned
        OnboardingRequest::markProvisioned($id, $tenantId);
        AuditService::logTenantCreated($tenantId, $request['legal_name']);
        AuditService::log('onboarding.provisioned', 'onboarding_request', $id,
            "Provisioned as tenant #{$tenantId}: {$request['legal_name']}");

        // Surface the activation URL to the platform admin until email dispatch is live.
        $devMode = in_array(env('APP_ENV', 'production'), ['development','local','dev'], true)
                && env('APP_DEBUG', 'false') === 'true';
        if ($devMode && $rawToken) {
            flash('success',
                "Airline provisioned as tenant #{$tenantId}. Activation URL for admin: {$activateUrl}");
        } else {
            flash('success',
                "Airline provisioned as tenant #{$tenantId}. Activation link recorded in audit log ({$request['contact_email']}). Email delivery is the next platform phase.");
        }
        redirect("/tenants/{$tenantId}");
    }
}
