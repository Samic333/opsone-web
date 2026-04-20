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
    'GET /activate'   => ['ActivationController', 'show'],
    'POST /activate'  => ['ActivationController', 'process'],

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

    // ─── Crew Profiles ─────────────────────────────────────
    'GET /crew-profiles'                                    => ['CrewProfileController', 'index'],
    'GET /crew-profiles/{id}'                               => ['CrewProfileController', 'show'],
    'POST /crew-profiles/{id}/qualifications/add'           => ['CrewProfileController', 'addQualification'],
    'POST /crew-profiles/{id}/qualifications/delete/{qid}'  => ['CrewProfileController', 'deleteQualification'],

    // ─── My Profile (self-service) ──────────────────────────
    'GET /my-profile'                                       => ['CrewProfileController', 'myProfile'],
    'POST /my-profile/update'                               => ['CrewProfileController', 'updateMyProfile'],
    'POST /my-profile/qualifications/add'                   => ['CrewProfileController', 'addMyQualification'],
    'POST /my-profile/qualifications/delete/{qid}'          => ['CrewProfileController', 'deleteMyQualification'],

    // ─── Users ─────────────────────────
    'GET /users'              => ['UserController', 'index'],
    'GET /users/create'       => ['UserController', 'create'],
    'POST /users/store'       => ['UserController', 'store'],
    'GET /users/edit/{id}'    => ['UserController', 'edit'],
    'POST /users/update/{id}' => ['UserController', 'update'],
    'POST /users/toggle/{id}'                   => ['UserController', 'toggleStatus'],
    'POST /users/profile/{id}'                  => ['UserController', 'saveProfile'],
    'POST /users/capabilities/{id}'             => ['UserController', 'updateCapabilities'],
    'POST /users/licenses/add/{id}'             => ['UserController', 'addLicense'],
    'POST /users/licenses/delete/{id}/{lid}'    => ['UserController', 'deleteLicense'],

    // ─── Roles ─────────────────────────
    'GET /roles'                          => ['RoleController', 'index'],
    'GET /roles/{id}'                     => ['RoleController', 'show'],
    'POST /roles/capabilities/{id}'       => ['RoleController', 'updateCapabilities'],

    // ─── Devices ───────────────────────
    'GET /devices'               => ['DeviceController', 'index'],
    'POST /devices/approve/{id}' => ['DeviceController', 'approve'],
    'POST /devices/reject/{id}'  => ['DeviceController', 'reject'],
    'POST /devices/revoke/{id}'  => ['DeviceController', 'revoke'],

    // Phase 4 - Docs & Manuals
    'GET /documents'                => [FileController::class, 'index'],
    'GET /documents/upload'         => [FileController::class, 'uploadForm'],
    'POST /documents/upload'        => [FileController::class, 'upload'],
    'GET /documents/(\d+)/download' => [FileController::class, 'download'],
    'GET /documents/(\d+)/edit'     => [FileController::class, 'editForm'],
    'POST /documents/(\d+)/edit'    => [FileController::class, 'edit'],
    'POST /documents/(\d+)/delete'  => [FileController::class, 'delete'],
    // NOTE: 'GET /my-files' was duplicated here (Phase 4 block) and at the
    // Crew Files Portal section below. The duplicate has been removed; the
    // canonical entry lives under '─── Crew Files Portal ──────────────────'.
    'POST /my-files/(\d+)/ack'      => [FileController::class, 'acknowledge'],

    // ─── Safety Phase 1 — Crew Routes ────────────────────────
    'GET /safety'                               => [SafetyController::class, 'home'],
    'GET /safety/select-type'                   => [SafetyController::class, 'selectType'],
    'GET /safety/report/new/([a-z_]+)'          => [SafetyController::class, 'reportForm'],
    'POST /safety/report/draft'                 => [SafetyController::class, 'saveDraft'],
    'POST /safety/report/submit'                => [SafetyController::class, 'submitReport'],
    'GET /safety/quick-report/([a-z_]+)'        => [SafetyController::class, 'quickReportForm'],
    'POST /safety/quick-report'                 => [SafetyController::class, 'submitQuickReport'],
    'GET /safety/drafts'                        => [SafetyController::class, 'myDrafts'],
    'GET /safety/report/edit/(\d+)'             => [SafetyController::class, 'editDraft'],
    'POST /safety/report/delete/(\d+)'          => [SafetyController::class, 'deleteDraft'],
    'GET /safety/my-reports'                    => [SafetyController::class, 'myReports'],
    'GET /safety/report/(\d+)'                  => [SafetyController::class, 'reportDetail'],
    'POST /safety/report/(\d+)/reply'           => [SafetyController::class, 'addReply'],
    'POST /safety/report/(\d+)/upload'          => [SafetyController::class, 'uploadAttachment'],
    'GET /safety/follow-ups'                    => [SafetyController::class, 'myFollowUps'],

    // ─── Safety Phase 1 — Safety Team Routes ─────────────────
    'GET /safety/dashboard'                                  => [SafetyController::class, 'safetyDashboard'],
    'GET /safety/queue'                                     => [SafetyController::class, 'index'],
    'GET /safety/team/actions'                              => [SafetyController::class, 'actionsQueue'],
    'GET /safety/team/report/(\d+)'                         => [SafetyController::class, 'teamDetail'],
    'POST /safety/team/report/(\d+)/status'                 => [SafetyController::class, 'updateStatus'],
    'POST /safety/team/report/(\d+)/severity'               => [SafetyController::class, 'updateSeverity'],
    'POST /safety/team/report/(\d+)/assign'                 => [SafetyController::class, 'assignReport'],
    'POST /safety/team/report/(\d+)/internal-note'          => [SafetyController::class, 'addInternalNote'],
    'POST /safety/team/report/(\d+)/reply'                  => [SafetyController::class, 'addTeamReply'],
    'POST /safety/team/report/(\d+)/action'                 => [SafetyController::class, 'addAction'],
    'POST /safety/team/action/(\d+)/update'                 => [SafetyController::class, 'updateAction'],
    'GET /safety/publications'                              => [SafetyController::class, 'publications'],
    'GET /safety/publications/new'                          => [SafetyController::class, 'newPublication'],
    'POST /safety/publications/save'                        => [SafetyController::class, 'savePublication'],
    'POST /safety/publications/(\d+)/publish'               => [SafetyController::class, 'publishPublication'],
    'GET /safety/publication/(\d+)'                         => [SafetyController::class, 'publicationDetail'],
    'GET /safety/settings'                                  => [SafetyController::class, 'settings'],
    'POST /safety/settings'                                 => [SafetyController::class, 'saveSettings'],
    'GET /safety/notifications/count'                       => [SafetyController::class, 'notificationCount'],

    // ─── Files ─────────────────────────
    'GET /files'               => ['FileController', 'index'],
    'GET /files/upload'        => ['FileController', 'showUpload'],
    'POST /files/upload'       => ['FileController', 'upload'],
    'GET /files/edit/{id}'     => ['FileController', 'edit'],
    'POST /files/update/{id}'  => ['FileController', 'update'],
    'POST /files/toggle/{id}'  => ['FileController', 'togglePublish'],
    'GET /files/download/{id}' => ['FileController', 'download'],
    'POST /files/delete/{id}'  => ['FileController', 'delete'],

    // ─── Crew Files Portal ──────────────────
    'GET /my-files'                             => ['FileController', 'myFiles'],
    'POST /my-files/acknowledge/{id}'           => ['FileController', 'acknowledgeFile'],

    // ─── Notices ───────────────────────
    'GET /notices'                              => ['NoticeController', 'index'],
    'GET /notices/create'                       => ['NoticeController', 'create'],
    'POST /notices/store'                       => ['NoticeController', 'store'],
    'GET /notices/edit/{id}'                    => ['NoticeController', 'edit'],
    'POST /notices/update/{id}'                 => ['NoticeController', 'update'],
    'POST /notices/toggle/{id}'                 => ['NoticeController', 'togglePublish'],
    'POST /notices/delete/{id}'                 => ['NoticeController', 'delete'],
    'GET /notices/categories'                   => ['NoticeController', 'categories'],
    'GET /notices/ack-report/{id}'              => ['NoticeController', 'ackReport'],
    'POST /notices/categories/store'            => ['NoticeController', 'storeCategory'],
    'POST /notices/categories/delete/{id}'      => ['NoticeController', 'deleteCategory'],

    // ─── Crew Notices Portal ────────────────
    'GET /my-notices'                           => ['NoticeController', 'myNotices'],
    'POST /my-notices/acknowledge/{id}'         => ['NoticeController', 'acknowledgeNotice'],

    // ─── Roster ────────────────────────
    'GET /roster'                  => ['RosterController', 'index'],
    'GET /roster/assign'           => ['RosterController', 'assignForm'],
    'POST /roster/assign'          => ['RosterController', 'assign'],
    'POST /roster/delete/{id}'     => ['RosterController', 'delete'],
    'GET /roster/standby'          => ['RosterController', 'standbyPool'],
    'GET /roster/suggest/{id}'     => ['RosterController', 'suggest'],

    // ─── Roster: Bulk Assign ───────────
    'GET /roster/bulk-assign'               => ['RosterController', 'bulkAssignForm'],
    'POST /roster/bulk-assign'              => ['RosterController', 'bulkAssign'],

    // ─── Roster: Periods ───────────────
    'GET /roster/periods'                   => ['RosterController', 'periods'],
    'GET /roster/periods/create'            => ['RosterController', 'createPeriodForm'],
    'POST /roster/periods/store'            => ['RosterController', 'storePeriod'],
    'POST /roster/periods/publish/{id}'     => ['RosterController', 'publishPeriod'],
    'POST /roster/periods/freeze/{id}'      => ['RosterController', 'freezePeriod'],
    'POST /roster/periods/delete/{id}'      => ['RosterController', 'deletePeriod'],

    // ─── Roster: Change Requests ───────
    'GET /roster/changes'                   => ['RosterController', 'changes'],
    'POST /roster/changes/request'          => ['RosterController', 'requestChange'],
    'POST /roster/changes/respond/{id}'     => ['RosterController', 'respondToChange'],

    // ─── Roster: Revisions ─────────────
    'GET /roster/revisions'                 => ['RosterController', 'revisions'],
    'GET /roster/revisions/create'          => ['RosterController', 'createRevisionForm'],
    'POST /roster/revisions/store'          => ['RosterController', 'storeRevision'],

    // ─── Roster: Coverage & Conflicts ──
    'GET /roster/coverage'                  => ['RosterController', 'coverage'],

    // ─── Personal Roster (crew self-service) ──
    'GET /my-roster'                        => ['RosterController', 'myRoster'],

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

    // ─── Platform: Feature Flags ────────
    'GET /platform/feature-flags'              => ['FeatureFlagController', 'index'],
    'POST /platform/feature-flags/toggle/{id}' => ['FeatureFlagController', 'toggle'],

    // ─── Audit Log ──────────────────────
    'GET /audit-log'         => ['AuditLogController', 'index'],
    'GET /audit-log/logins'  => ['AuditLogController', 'loginActivity'],
    'GET /audit-log/export'  => ['AuditLogController', 'exportCsv'],

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
    'GET /api/user/profile'       => ['UserApiController', 'profile'],
    'GET /api/user/modules'       => ['UserApiController', 'modules'],
    'GET /api/user/capabilities'  => ['UserApiController', 'capabilities'],  // Phase 10: richer entitlements

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

    // ─── API: Safety Phase 1 ───────────────
    'GET /api/safety/types'                   => ['SafetyApiController', 'types'],
    'GET /api/safety/my-reports'              => ['SafetyApiController', 'myReports'],
    'GET /api/safety/drafts'                  => ['SafetyApiController', 'drafts'],
    'GET /api/safety/report/(\d+)'            => ['SafetyApiController', 'report'],
    'POST /api/safety/report'                 => ['SafetyApiController', 'createReport'],
    'PUT /api/safety/report/(\d+)'            => ['SafetyApiController', 'updateReport'],
    'DELETE /api/safety/report/(\d+)/draft'   => ['SafetyApiController', 'deleteDraft'],
    'POST /api/safety/report/(\d+)/reply'     => ['SafetyApiController', 'addReply'],
    'GET /api/safety/publications'            => ['SafetyApiController', 'publications'],
    'GET /api/safety/publication/(\d+)'       => ['SafetyApiController', 'publication'],
];
