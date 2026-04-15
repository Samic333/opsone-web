<?php
/**
 * Route Definitions
 * Format: 'METHOD /path' => [ControllerClass, 'method']
 * Use {id} for route parameters
 *
 * Phase Zero: Added platform/* routes.
 *             Tenant routes now use /tenants/{id} for detailed view.
 */

return [
    // ─── Public Pages (No Auth) ────────────
    'GET /home'             => ['PublicController', 'home'],
    'GET /features'         => ['PublicController', 'features'],
    'GET /how-it-works'     => ['PublicController', 'howItWorks'],
    'GET /install-info'     => ['PublicController', 'installInfo'],
    'GET /support'          => ['PublicController', 'support'],
    'GET /contact'          => ['PublicController', 'contact'],
    'GET /faq'              => ['PublicController', 'faq'],
    'GET /about'            => ['PublicController', 'about'],
    'GET /privacy'          => ['PublicController', 'privacy'],
    'GET /terms'            => ['PublicController', 'terms'],

    // ─── Auth ──────────────────────────
    'GET /login'      => ['AuthController', 'showLogin'],
    'POST /login'     => ['AuthController', 'login'],
    'GET /logout'     => ['AuthController', 'logout'],

    // ─── Dashboard ─────────────────────
    'GET /'           => ['PublicController', 'home'],
    'GET /dashboard'  => ['DashboardController', 'index'],

    // ─── Tenants (Platform Super Admin) ─────────
    'GET /tenants'              => ['TenantController', 'index'],
    'GET /tenants/create'       => ['TenantController', 'create'],
    'POST /tenants/store'       => ['TenantController', 'store'],
    'GET /tenants/{id}'         => ['TenantController', 'show'],           // NEW: detail view
    'GET /tenants/edit/{id}'    => ['TenantController', 'edit'],
    'POST /tenants/update/{id}' => ['TenantController', 'update'],
    'POST /tenants/toggle/{id}' => ['TenantController', 'toggle'],

    // Module management per tenant (platform super admin)
    'POST /tenants/{id}/modules/{mid}/toggle' => ['TenantController', 'toggleModule'],
    'POST /tenants/{id}/access'               => ['TenantController', 'logAccess'],
    'POST /tenants/{id}/invite'               => ['TenantController', 'createInvitation'],

    // ─── Platform: Staff Management ────
    'GET /platform/users'              => ['PlatformUsersController', 'index'],
    'GET /platform/users/create'       => ['PlatformUsersController', 'create'],
    'POST /platform/users/store'       => ['PlatformUsersController', 'store'],
    'POST /platform/users/toggle/{id}' => ['PlatformUsersController', 'toggle'],

    // ─── Platform: Module Catalog ───────
    'GET /platform/modules'                        => ['ModuleCatalogController', 'index'],
    'GET /platform/modules/tenant/{id}'            => ['ModuleCatalogController', 'forTenant'],
    'POST /platform/modules/tenant/{id}/toggle/{mid}' => ['ModuleCatalogController', 'toggleForTenant'],

    // ─── Platform: Onboarding ──────────
    'GET /platform/onboarding'                 => ['OnboardingController', 'index'],
    'GET /platform/onboarding/create'          => ['OnboardingController', 'create'],
    'POST /platform/onboarding/store'          => ['OnboardingController', 'store'],
    'GET /platform/onboarding/{id}'            => ['OnboardingController', 'show'],
    'POST /platform/onboarding/{id}/mark-in-review' => ['OnboardingController', 'markInReview'],
    'POST /platform/onboarding/{id}/approve'        => ['OnboardingController', 'approve'],
    'POST /platform/onboarding/{id}/reject'         => ['OnboardingController', 'reject'],
    'POST /platform/onboarding/{id}/provision'      => ['OnboardingController', 'provision'],

    // ─── Airline Profile / Settings ────
    'GET /airline/profile'         => ['AirlineProfileController', 'show'],
    'POST /airline/profile/update' => ['AirlineProfileController', 'update'],

    // ─── Departments ───────────────────
    'GET /departments'               => ['DepartmentController', 'index'],
    'GET /departments/create'        => ['DepartmentController', 'create'],
    'POST /departments/store'        => ['DepartmentController', 'store'],
    'GET /departments/edit/{id}'     => ['DepartmentController', 'edit'],
    'POST /departments/update/{id}'  => ['DepartmentController', 'update'],
    'POST /departments/delete/{id}'  => ['DepartmentController', 'delete'],

    // ─── Bases ─────────────────────────
    'GET /bases'               => ['BaseController', 'index'],
    'GET /bases/create'        => ['BaseController', 'create'],
    'POST /bases/store'        => ['BaseController', 'store'],
    'GET /bases/edit/{id}'     => ['BaseController', 'edit'],
    'POST /bases/update/{id}'  => ['BaseController', 'update'],
    'POST /bases/delete/{id}'  => ['BaseController', 'delete'],

    // ─── Fleets ────────────────────────
    'GET /fleets'               => ['FleetController', 'index'],
    'GET /fleets/create'        => ['FleetController', 'create'],
    'POST /fleets/store'        => ['FleetController', 'store'],
    'GET /fleets/edit/{id}'     => ['FleetController', 'edit'],
    'POST /fleets/update/{id}'  => ['FleetController', 'update'],
    'POST /fleets/delete/{id}'  => ['FleetController', 'delete'],

    // ─── Users ─────────────────────────
    'GET /users'              => ['UserController', 'index'],
    'GET /users/create'       => ['UserController', 'create'],
    'POST /users/store'       => ['UserController', 'store'],
    'GET /users/edit/{id}'    => ['UserController', 'edit'],
    'POST /users/update/{id}' => ['UserController', 'update'],
    'POST /users/toggle/{id}'                   => ['UserController', 'toggleStatus'],
    'POST /users/profile/{id}'                  => ['UserController', 'saveProfile'],
    'POST /users/licenses/add/{id}'             => ['UserController', 'addLicense'],
    'POST /users/licenses/delete/{id}/{lid}'    => ['UserController', 'deleteLicense'],

    // ─── Devices ───────────────────────
    'GET /devices'               => ['DeviceController', 'index'],
    'POST /devices/approve/{id}' => ['DeviceController', 'approve'],
    'POST /devices/reject/{id}'  => ['DeviceController', 'reject'],
    'POST /devices/revoke/{id}'  => ['DeviceController', 'revoke'],

    // ─── Files ─────────────────────────
    'GET /files'               => ['FileController', 'index'],
    'GET /files/upload'        => ['FileController', 'showUpload'],
    'POST /files/upload'       => ['FileController', 'upload'],
    'POST /files/toggle/{id}'  => ['FileController', 'togglePublish'],
    'GET /files/download/{id}' => ['FileController', 'download'],
    'POST /files/delete/{id}'  => ['FileController', 'delete'],

    // ─── Notices ───────────────────────
    'GET /notices'               => ['NoticeController', 'index'],
    'GET /notices/create'        => ['NoticeController', 'create'],
    'POST /notices/store'        => ['NoticeController', 'store'],
    'GET /notices/edit/{id}'     => ['NoticeController', 'edit'],
    'POST /notices/update/{id}'  => ['NoticeController', 'update'],
    'POST /notices/toggle/{id}'  => ['NoticeController', 'togglePublish'],
    'POST /notices/delete/{id}'  => ['NoticeController', 'delete'],

    // ─── Roster ────────────────────────
    'GET /roster'                  => ['RosterController', 'index'],
    'GET /roster/assign'           => ['RosterController', 'assignForm'],
    'POST /roster/assign'          => ['RosterController', 'assign'],
    'POST /roster/delete/{id}'     => ['RosterController', 'delete'],
    'GET /roster/standby'          => ['RosterController', 'standbyPool'],
    'GET /roster/suggest/{id}'     => ['RosterController', 'suggest'],

    // ─── FDM ───────────────────────────
    'GET /fdm'                                       => ['FdmController', 'index'],
    'GET /fdm/upload'                                => ['FdmController', 'uploadForm'],
    'POST /fdm/store'                                => ['FdmController', 'store'],
    'GET /fdm/view/{id}'                             => ['FdmController', 'view'],
    'POST /fdm/{id}/events/add'                      => ['FdmController', 'addEvent'],
    'POST /fdm/{id}/events/delete/{lid}'             => ['FdmController', 'deleteEvent'],
    'POST /fdm/delete/{id}'                          => ['FdmController', 'deleteUpload'],

    // ─── Compliance ─────────────────────
    'GET /compliance' => ['ComplianceController', 'index'],

    // ─── Audit Log ──────────────────────
    'GET /audit-log'         => ['AuditLogController', 'index'],
    'GET /audit-log/logins'  => ['AuditLogController', 'loginActivity'],

    // ─── Install (Protected) ──────────
    'GET /install'                 => ['InstallController', 'index'],
    'GET /install/instructions'    => ['InstallController', 'instructions'],
    'GET /install/manifest'        => ['InstallController', 'manifest'],
    'GET /install/download/{id}'   => ['InstallController', 'download'],

    // ─── API: Auth ─────────────────────
    'POST /api/auth/login'  => ['AuthApiController', 'login'],
    'POST /api/auth/logout' => ['AuthApiController', 'logout'],

    // ─── API: Devices ──────────────────
    'POST /api/devices/register' => ['DeviceApiController', 'register'],
    'GET /api/devices/status'    => ['DeviceApiController', 'status'],

    // ─── API: User ─────────────────────
    'GET /api/user/profile' => ['UserApiController', 'profile'],

    // ─── API: Roster ───────────────────
    'GET /api/roster' => ['RosterApiController', 'index'],

    // ─── API: Files ────────────────────
    'GET /api/files'                   => ['FileApiController', 'index'],
    'GET /api/files/download/{id}'     => ['FileApiController', 'download'],
    'POST /api/files/{id}/acknowledge' => ['FileApiController', 'acknowledge'],

    // ─── API: Sync ─────────────────────
    'POST /api/sync/heartbeat' => ['SyncApiController', 'heartbeat'],
    'GET /api/sync/manifest'   => ['InstallApiController', 'syncManifest'],

    // ─── API: Notices ──────────────────
    'GET /api/notices'              => ['NoticeApiController', 'index'],
    'POST /api/notices/{id}/read'   => ['NoticeApiController', 'markRead'],
    'POST /api/notices/{id}/ack'    => ['NoticeApiController', 'acknowledge'],

    // ─── API: App Info ─────────────────
    'GET /api/app/version'     => ['InstallApiController', 'appVersion'],
    'GET /api/app/build'       => ['InstallApiController', 'latestBuild'],
];
