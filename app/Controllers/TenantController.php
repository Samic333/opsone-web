<?php
/**
 * TenantController — airline tenant management (platform super admin only)
 *
 * Phase Zero: enhanced onboarding form, module assignment,
 * tenant detail view, invitation token creation.
 */
class TenantController {
    public function __construct() {
        RbacMiddleware::requirePlatformSuperAdmin();
        if (isSingleTenant()) {
            flash('error', 'Tenant management is not available in single-tenant mode.');
            redirect('/dashboard');
        }
    }

    public function index(): void {
        $tenants = Tenant::platformSummary();
        $pendingOnboarding = OnboardingRequest::countPending();
        require VIEWS_PATH . '/tenants/index.php';
    }

    public function show(int $id): void {
        $tenant  = Tenant::find($id);
        if (!$tenant) {
            flash('error', 'Airline not found.');
            redirect('/tenants');
        }
        $stats    = Tenant::stats($id);
        $modules  = Module::allWithTenantStatus($id);
        $contacts = Tenant::getContacts($id);
        $settings = Tenant::getSettings($id);
        $policy   = Tenant::getAccessPolicy($id);
        $accessLog = PlatformAccessLog::forTenant($id, 10);
        $invitations = InvitationToken::pendingForTenant($id);
        require VIEWS_PATH . '/tenants/show.php';
    }

    public function create(): void {
        $allModules = Module::all();
        require VIEWS_PATH . '/tenants/create.php';
    }

    public function store(): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/tenants/create');
        }

        $name    = trim($_POST['name']           ?? '');
        $code    = strtoupper(trim($_POST['code'] ?? ''));
        $email   = trim($_POST['contact_email']  ?? '');

        if (empty($name) || empty($code)) {
            flash('error', 'Airline name and code are required.');
            redirect('/tenants/create');
        }
        if (strlen($code) > 10) {
            flash('error', 'Airline code must be 10 characters or less.');
            redirect('/tenants/create');
        }

        $tenantData = [
            'name'                => $name,
            'legal_name'          => trim($_POST['legal_name']         ?? '') ?: $name,
            'display_name'        => trim($_POST['display_name']        ?? '') ?: null,
            'code'                => $code,
            'icao_code'           => strtoupper(trim($_POST['icao_code'] ?? '')) ?: null,
            'iata_code'           => strtoupper(trim($_POST['iata_code'] ?? '')) ?: null,
            'contact_email'       => $email ?: null,
            'primary_country'     => trim($_POST['primary_country']    ?? '') ?: null,
            'primary_base'        => trim($_POST['primary_base']        ?? '') ?: null,
            'support_tier'        => $_POST['support_tier']             ?? 'standard',
            'onboarding_status'   => 'active',
            'expected_headcount'  => ((int)($_POST['expected_headcount'] ?? 0)) ?: null,
            'headcount_pilots'    => ((int)($_POST['headcount_pilots']   ?? 0)) ?: null,
            'headcount_cabin'     => ((int)($_POST['headcount_cabin']    ?? 0)) ?: null,
            'headcount_engineers' => ((int)($_POST['headcount_engineers'] ?? 0)) ?: null,
            'headcount_schedulers'=> ((int)($_POST['headcount_schedulers'] ?? 0)) ?: null,
            'headcount_training'  => ((int)($_POST['headcount_training']  ?? 0)) ?: null,
            'headcount_safety'    => ((int)($_POST['headcount_safety']    ?? 0)) ?: null,
            'headcount_hr'        => ((int)($_POST['headcount_hr']        ?? 0)) ?: null,
            'notes'               => trim($_POST['notes'] ?? '') ?: null,
        ];

        $tenantId = Tenant::create($tenantData);

        // Create default roles for the new tenant
        $systemRoles = Database::fetchAll(
            "SELECT name, slug, description, role_type FROM roles WHERE tenant_id IS NULL AND is_system = 1"
        );
        foreach ($systemRoles as $role) {
            Database::insert(
                "INSERT IGNORE INTO roles (tenant_id, name, slug, description, is_system, role_type)
                 VALUES (?, ?, ?, ?, 0, ?)",
                [$tenantId, $role['name'], $role['slug'], $role['description'], $role['role_type']]
            );
        }

        // Create default departments
        foreach (['Flight Operations','Cabin Operations','Engineering','Human Resources',
                  'Safety','Operations','IT'] as $dept) {
            Database::insert(
                "INSERT INTO departments (tenant_id, name) VALUES (?, ?)",
                [$tenantId, $dept]
            );
        }

        // Create default file categories
        foreach ([
            ['Manuals','manuals'],['Notices','notices'],['Licenses','licenses'],
            ['Training','training'],['Memos','memos'],['Safety Bulletins','safety_bulletins'],
            ['General Documents','general'],
        ] as [$catName, $catSlug]) {
            Database::insert(
                "INSERT INTO file_categories (tenant_id, name, slug) VALUES (?, ?, ?)",
                [$tenantId, $catName, $catSlug]
            );
        }

        // Enable selected modules
        $selectedModules = $_POST['modules'] ?? [];
        if (!empty($selectedModules) && is_array($selectedModules)) {
            $actor = currentUser();
            TenantModule::bulkEnable($tenantId, $selectedModules, $actor['id'] ?? null);
        }

        // Initialize settings and access policy defaults
        Tenant::initializeDefaults($tenantId);

        // Handle primary admin contact
        $adminContactName  = trim($_POST['admin_contact_name']  ?? '');
        $adminContactEmail = trim($_POST['admin_contact_email'] ?? '');
        if ($adminContactName && $adminContactEmail) {
            Database::insert(
                "INSERT INTO tenant_contacts (tenant_id, contact_type, name, email, is_primary)
                 VALUES (?, 'primary_admin', ?, ?, 1)",
                [$tenantId, $adminContactName, $adminContactEmail]
            );

            // Create invitation token for initial admin (no plain-text password)
            $actor = currentUser();
            $token = InvitationToken::create(
                $tenantId,
                $adminContactEmail,
                $adminContactName,
                'airline_admin',
                $actor['id'] ?? null
            );
            // TODO Phase 1: send $token via email with activation link
        }

        AuditService::logTenantCreated($tenantId, $name);
        flash('success', "Airline \"{$name}\" created successfully.");
        redirect("/tenants/{$tenantId}");
    }

    public function edit(int $id): void {
        $tenant     = Tenant::find($id);
        if (!$tenant) {
            flash('error', 'Tenant not found.');
            redirect('/tenants');
        }
        $allModules = Module::allWithTenantStatus($id);
        require VIEWS_PATH . '/tenants/edit.php';
    }

    public function update(int $id): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect("/tenants/edit/$id");
        }

        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));

        if (empty($name) || empty($code)) {
            flash('error', 'Airline name and code are required.');
            redirect("/tenants/edit/$id");
        }

        Tenant::update($id, [
            'name'                => $name,
            'legal_name'          => trim($_POST['legal_name']         ?? '') ?: $name,
            'display_name'        => trim($_POST['display_name']        ?? '') ?: null,
            'code'                => $code,
            'icao_code'           => strtoupper(trim($_POST['icao_code'] ?? '')) ?: null,
            'iata_code'           => strtoupper(trim($_POST['iata_code'] ?? '')) ?: null,
            'contact_email'       => trim($_POST['contact_email']       ?? '') ?: null,
            'primary_country'     => trim($_POST['primary_country']     ?? '') ?: null,
            'primary_base'        => trim($_POST['primary_base']         ?? '') ?: null,
            'support_tier'        => $_POST['support_tier']             ?? 'standard',
            'onboarding_status'   => $_POST['onboarding_status']        ?? 'active',
            'expected_headcount'  => ((int)($_POST['expected_headcount'] ?? 0)) ?: null,
            'headcount_pilots'    => ((int)($_POST['headcount_pilots']   ?? 0)) ?: null,
            'headcount_cabin'     => ((int)($_POST['headcount_cabin']    ?? 0)) ?: null,
            'headcount_engineers' => ((int)($_POST['headcount_engineers'] ?? 0)) ?: null,
            'headcount_schedulers'=> ((int)($_POST['headcount_schedulers'] ?? 0)) ?: null,
            'headcount_training'  => ((int)($_POST['headcount_training']  ?? 0)) ?: null,
            'headcount_safety'    => ((int)($_POST['headcount_safety']    ?? 0)) ?: null,
            'headcount_hr'        => ((int)($_POST['headcount_hr']        ?? 0)) ?: null,
            'notes'               => trim($_POST['notes'] ?? '') ?: null,
        ]);

        AuditService::logTenantUpdated($id, $name);
        flash('success', "Airline \"{$name}\" updated.");
        redirect("/tenants/{$id}");
    }

    public function toggle(int $id): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/tenants');
        }
        $tenant = Tenant::find($id);
        if (!$tenant) {
            flash('error', 'Airline not found.');
            redirect('/tenants');
        }
        Tenant::toggleActive($id);
        $newStatus = $tenant['is_active'] ? 'suspended' : 'activated';
        AuditService::logTenantStatusChange($id, $tenant['name'], $newStatus);
        flash('success', "Airline \"{$tenant['name']}\" {$newStatus}.");
        redirect('/tenants');
    }

    /**
     * Toggle a module on/off for a specific tenant (AJAX-friendly POST).
     */
    public function toggleModule(int $tenantId, int $moduleId): void {
        if (!verifyCsrf()) {
            jsonResponse(['error' => 'Invalid CSRF token'], 400);
        }

        $tenant = Tenant::find($tenantId);
        $module = Module::find($moduleId);
        if (!$tenant || !$module) {
            jsonResponse(['error' => 'Not found'], 404);
        }

        $enabled = TenantModule::toggle($tenantId, $moduleId);
        AuditService::logModuleToggle($tenantId, $tenant['name'], $module['code'], $enabled);

        jsonResponse(['success' => true, 'enabled' => $enabled, 'module' => $module['code']]);
    }

    /**
     * Create an invitation token for a new airline admin.
     */
    public function createInvitation(int $tenantId): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect("/tenants/$tenantId");
        }

        $email    = trim($_POST['invite_email']    ?? '');
        $name     = trim($_POST['invite_name']     ?? '');
        $roleSlug = trim($_POST['invite_role_slug'] ?? 'airline_admin');

        if (!$email || !$name) {
            flash('error', 'Name and email are required for invitation.');
            redirect("/tenants/$tenantId");
        }

        $actor = currentUser();
        $token = InvitationToken::create($tenantId, $email, $name, $roleSlug, $actor['id'] ?? null);

        AuditService::log('invitation.created', 'tenant', $tenantId,
            "Invited $name ($email) as $roleSlug — token expires 72h");
        // TODO Phase 1: send activation email with $token

        flash('success', "Invitation created for {$name} ({$email}). " .
                         "Phase 1 will wire up email delivery. Token: " . substr($token, 0, 8) . "...");
        redirect("/tenants/$tenantId");
    }

    /**
     * Log a platform admin's controlled access into a tenant area.
     */
    public function logAccess(int $tenantId): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect("/tenants/$tenantId");
        }

        $reason     = trim($_POST['access_reason'] ?? '');
        $ticketRef  = trim($_POST['ticket_ref']    ?? '') ?: null;
        $moduleArea = trim($_POST['module_area']   ?? 'general');

        if (empty($reason)) {
            flash('error', 'A reason is required to access airline data.');
            redirect("/tenants/$tenantId");
        }

        $logId = AuditService::logPlatformAccess($tenantId, $moduleArea, $reason, $ticketRef);
        $_SESSION['platform_access_log_id'] = $logId;
        $_SESSION['platform_access_tenant_id'] = $tenantId;

        flash('success', "Access logged. You may now navigate the airline area. Access ID: #{$logId}");
        redirect("/tenants/$tenantId");
    }
}
