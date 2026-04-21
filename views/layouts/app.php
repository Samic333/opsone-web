<?php
/**
 * OpsOne — Admin Portal Layout
 *
 * Phase Zero: Platform users see ONLY platform navigation.
 *             Airline users see ONLY airline navigation.
 *             Cross-contamination of sidebar items is blocked.
 */
$brand = $brand ?? (file_exists(CONFIG_PATH . '/branding.php')
    ? require CONFIG_PATH . '/branding.php'
    : ['product_name' => 'OpsOne']);

$user    = currentUser();
$roles   = $_SESSION['user_roles'] ?? [];
$tenant  = $_SESSION['tenant'] ?? null;

$pendingDevices = 0;
try {
    if (!isPlatformOnly() && currentTenantId()) {
        $pendingDevices = Device::countPending(currentTenantId());
    }
} catch (\Exception $e) {}

$pendingOnboarding = 0;
try {
    if (isPlatformOnly()) {
        $pendingOnboarding = OnboardingRequest::countPending();
    }
} catch (\Exception $e) {}

$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$isPlat      = isPlatformOnly();

$roleLabelMap = [
    'super_admin'         => 'Platform Super Admin',
    'platform_support'    => 'Platform Support Admin',
    'platform_security'   => 'Platform Security Admin',
    'system_monitoring'   => 'System Monitoring',
    'airline_admin'       => 'Airline Admin',
    'hr'                  => 'HR Admin',
    'scheduler'           => 'Scheduler / Crew Control',
    'chief_pilot'         => 'Chief Pilot',
    'head_cabin_crew'     => 'Head of Cabin Crew',
    'engineering_manager' => 'Engineering Manager',
    'safety_officer'      => 'Safety Manager',
    'fdm_analyst'         => 'FDM Analyst',
    'document_control'    => 'Document Control',
    'base_manager'        => 'Base Manager',
    'training_admin'      => 'Training Admin',
    'pilot'               => 'Pilot',
    'cabin_crew'          => 'Cabin Crew',
    'engineer'            => 'Engineer',
    'director'            => 'Director',
];
$roleLabel = $roleLabelMap[$roles[0] ?? '']
           ?? ucwords(str_replace('_', ' ', $roles[0] ?? 'User'));

$brandName  = $isPlat ? ($brand['product_name'] . ' Platform') : $brand['product_name'];
$brandSmall = $isPlat ? 'Platform Administration' : ($tenant['name'] ?? 'Airline Portal');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Dashboard') ?> — <?= e($brand['product_name']) ?></title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="stylesheet" href="/css/app.css">
</head>
<body class="<?= $isPlat ? 'ctx-platform' : 'ctx-airline' ?>">
<div class="app-layout">
    <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle menu">
        <span></span><span></span><span></span>
    </button>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar <?= $isPlat ? 'sidebar-platform' : 'sidebar-airline' ?>" id="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon"><?= $isPlat ? '🛡' : '✈' ?></div>
            <div>
                <h1><?= e($brandName) ?></h1>
                <small><?= e($brandSmall) ?></small>
            </div>
        </div>

        <nav class="sidebar-nav">

        <?php if ($isPlat): ?>
        <!-- ═══════════════════════════════════════════════════════
             PLATFORM NAVIGATION — visible to platform admins only
             ═══════════════════════════════════════════════════════ -->

            <div class="sidebar-section">
                <div class="sidebar-section-title">Platform</div>
                <a href="/dashboard" class="sidebar-link <?= str_starts_with($currentPath, '/dashboard') ? 'active' : '' ?>">
                    <span class="icon">📊</span> Platform Overview
                </a>
            </div>

            <?php if (hasAnyRole(['super_admin', 'platform_support', 'system_monitoring'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Airlines</div>
                <a href="/tenants" class="sidebar-link <?= str_starts_with($currentPath, '/tenants') ? 'active' : '' ?>">
                    <span class="icon">🏢</span> Airline Registry
                </a>
                <?php if (hasRole('super_admin')): ?>
                <a href="/platform/onboarding" class="sidebar-link <?= str_starts_with($currentPath, '/platform/onboarding') ? 'active' : '' ?>">
                    <span class="icon">✈</span> Onboarding
                    <?php if ($pendingOnboarding > 0): ?>
                        <span class="badge"><?= $pendingOnboarding ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (hasRole('super_admin')): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Platform Staff</div>
                <a href="/platform/users" class="sidebar-link <?= str_starts_with($currentPath, '/platform/users') ? 'active' : '' ?>">
                    <span class="icon">👤</span> Staff Accounts
                </a>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Configuration</div>
                <a href="/platform/modules" class="sidebar-link <?= str_starts_with($currentPath, '/platform/modules') ? 'active' : '' ?>">
                    <span class="icon">🧩</span> Module Catalog
                </a>
                <a href="/platform/feature-flags" class="sidebar-link <?= str_starts_with($currentPath, '/platform/feature-flags') ? 'active' : '' ?>">
                    <span class="icon">🚩</span> Feature Flags
                </a>
            </div>
            <?php endif; ?>

            <?php if (hasAnyRole(['super_admin', 'platform_security'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Security</div>
                <a href="/audit-log" class="sidebar-link <?= str_starts_with($currentPath, '/audit-log') ? 'active' : '' ?>">
                    <span class="icon">🔒</span> Audit Log
                </a>
                <a href="/audit-log/logins" class="sidebar-link <?= str_starts_with($currentPath, '/audit-log/logins') ? 'active' : '' ?>">
                    <span class="icon">🔑</span> Login Activity
                </a>
            </div>
            <?php endif; ?>

            <?php if (hasAnyRole(['super_admin', 'platform_support'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Support</div>
                <a href="/devices" class="sidebar-link <?= str_starts_with($currentPath, '/devices') ? 'active' : '' ?>">
                    <span class="icon">📱</span> All Devices
                </a>
                <a href="/install" class="sidebar-link <?= str_starts_with($currentPath, '/install') ? 'active' : '' ?>">
                    <span class="icon">📲</span> App Builds
                </a>
            </div>
            <?php endif; ?>

            <!-- Platform notice: accessing airline data -->
            <div class="sidebar-section" style="margin-top: auto;">
                <div style="padding: 10px 12px; background: rgba(245,158,11,0.1); border-radius: 6px;
                            border-left: 3px solid #f59e0b; font-size: 11px; color: var(--text-muted);">
                    <strong style="color: #f59e0b;">⚠ Platform Mode</strong><br>
                    To access airline operational data, open the airline record and use
                    <em>Controlled Access</em>.
                </div>
            </div>

        <?php else: ?>
        <!-- ═══════════════════════════════════════════════════════
             AIRLINE NAVIGATION — visible to airline/tenant users
             ═══════════════════════════════════════════════════════ -->

            <div class="sidebar-section">
                <div class="sidebar-section-title">Main</div>
                <a href="/dashboard" class="sidebar-link <?= str_starts_with($currentPath, '/dashboard') || $currentPath === '/' ? 'active' : '' ?>">
                    <span class="icon">📊</span> Dashboard
                </a>
            </div>

            <!-- ─── People & Devices ─────────────────────── -->
            <?php if (hasAnyRole(['airline_admin', 'hr', 'training_admin'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">People</div>
                <a href="/users" class="sidebar-link <?= str_starts_with($currentPath, '/users') ? 'active' : '' ?>">
                    <span class="icon">👥</span> Users
                </a>
                <a href="/crew-profiles" class="sidebar-link <?= str_starts_with($currentPath, '/crew-profiles') ? 'active' : '' ?>">
                    <span class="icon">🪪</span> Crew Profiles
                </a>
                <a href="/devices" class="sidebar-link <?= str_starts_with($currentPath, '/devices') ? 'active' : '' ?>">
                    <span class="icon">📱</span> Devices
                    <?php if ($pendingDevices > 0): ?>
                        <span class="badge"><?= $pendingDevices ?></span>
                    <?php endif; ?>
                </a>
            </div>
            <?php elseif (hasAnyRole(['chief_pilot','head_cabin_crew','engineering_manager','base_manager','safety_officer'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">People</div>
                <a href="/crew-profiles" class="sidebar-link <?= str_starts_with($currentPath, '/crew-profiles') ? 'active' : '' ?>">
                    <span class="icon">🪪</span> Crew Profiles
                </a>
                <a href="/devices" class="sidebar-link <?= str_starts_with($currentPath, '/devices') ? 'active' : '' ?>">
                    <span class="icon">📱</span> iPad Devices
                    <?php if ($pendingDevices > 0): ?>
                        <span class="badge"><?= $pendingDevices ?></span>
                    <?php endif; ?>
                </a>
            </div>
            <?php endif; ?>

            <!-- ─── Personnel Records (Phase 6) ──────────────── -->
            <?php if (hasAnyRole(['airline_admin','hr','chief_pilot','head_cabin_crew',
                                   'engineering_manager','safety_officer','training_admin',
                                   'scheduler','base_manager','super_admin','fdm_analyst'])):
                $pendingCR = 0; $pendingDocs = 0;
                try {
                    if (currentTenantId()) {
                        $pendingCR   = ChangeRequestModel::pendingCount(currentTenantId());
                        $pendingDocs = CrewDocumentModel::pendingApprovalCount(currentTenantId());
                    }
                } catch (\Throwable $e) {}
            ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Personnel Records</div>
                <a href="/compliance" class="sidebar-link <?= $currentPath === '/compliance' ? 'active' : '' ?>">
                    <span class="icon">🛡</span> Licensing &amp; Compliance
                </a>
                <a href="/personnel/documents" class="sidebar-link <?= str_starts_with($currentPath, '/personnel/documents') ? 'active' : '' ?>">
                    <span class="icon">📄</span> Documents
                    <?php if ($pendingDocs > 0): ?><span class="badge"><?= $pendingDocs ?></span><?php endif; ?>
                </a>
                <a href="/personnel/change-requests" class="sidebar-link <?= str_starts_with($currentPath, '/personnel/change-requests') ? 'active' : '' ?>">
                    <span class="icon">📝</span> Change Requests
                    <?php if ($pendingCR > 0): ?><span class="badge"><?= $pendingCR ?></span><?php endif; ?>
                </a>
                <a href="/personnel/eligibility" class="sidebar-link <?= str_starts_with($currentPath, '/personnel/eligibility') ? 'active' : '' ?>">
                    <span class="icon">✅</span> Eligibility Status
                </a>
                <a href="/compliance/expiring" class="sidebar-link <?= str_starts_with($currentPath, '/compliance/expiring') ? 'active' : '' ?>">
                    <span class="icon">⏳</span> Expiry Alerts
                </a>
            </div>
            <?php endif; ?>

            <!-- ─── Me (all logged-in crew) ─────────────── -->
            <?php if (hasAnyRole(['pilot','cabin_crew','engineer','scheduler','chief_pilot',
                                   'head_cabin_crew','engineering_manager','base_manager',
                                   'training_admin','fdm_analyst','document_control'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Me</div>
                <a href="/my-profile" class="sidebar-link <?= $currentPath === '/my-profile' ? 'active' : '' ?>">
                    <span class="icon">👤</span> My Profile
                </a>
                <a href="/my-profile/change-requests" class="sidebar-link <?= str_starts_with($currentPath, '/my-profile/change-requests') ? 'active' : '' ?>">
                    <span class="icon">📝</span> My Change Requests
                </a>
                <a href="/my-notices" class="sidebar-link <?= str_starts_with($currentPath, '/my-notices') ? 'active' : '' ?>">
                    <span class="icon">📬</span> Operational Notices
                </a>
                <?php
                // Badge: count reports where safety team has replied and crew hasn't responded yet
                $safetyPendingReplies = 0;
                try {
                    $safetyPendingReplies = (int)(Database::fetch(
                        "SELECT COUNT(DISTINCT sr.id) AS cnt
                           FROM safety_reports sr
                           JOIN safety_report_threads lt
                             ON lt.report_id = sr.id
                            AND lt.is_internal = 0
                            AND lt.created_at = (
                                SELECT MAX(t2.created_at)
                                  FROM safety_report_threads t2
                                 WHERE t2.report_id = sr.id AND t2.is_internal = 0
                            )
                          WHERE sr.tenant_id   = ?
                            AND sr.reporter_id = ?
                            AND sr.is_draft    = 0
                            AND sr.status NOT IN ('closed','draft')
                            AND lt.author_id  != sr.reporter_id",
                        [currentTenantId(), currentUser()['id']]
                    )['cnt'] ?? 0);
                } catch (\Throwable $e) { /* threads table may not exist yet */ }
                ?>
                <a href="/safety/my-reports" class="sidebar-link <?= str_starts_with($currentPath, '/safety/my-reports') || str_starts_with($currentPath, '/safety/report/') ? 'active' : '' ?>">
                    <span class="icon">🛡️</span> My Safety Reports
                    <?php if ($safetyPendingReplies > 0): ?>
                        <span style="margin-left:auto;background:#f59e0b;color:#fff;font-size:9px;font-weight:800;padding:1px 5px;border-radius:3px;"><?= $safetyPendingReplies ?></span>
                    <?php endif; ?>
                </a>
                <?php
                $safetyDraftCount = 0;
                try {
                    $safetyDraftCount = (int)(Database::fetch(
                        "SELECT COUNT(*) AS cnt FROM safety_reports WHERE tenant_id = ? AND reporter_id = ? AND is_draft = 1",
                        [currentTenantId(), currentUser()['id']]
                    )['cnt'] ?? 0);
                } catch (\Throwable $e) {}
                ?>
                <a href="/safety/drafts" class="sidebar-link <?= str_starts_with($currentPath, '/safety/drafts') || str_starts_with($currentPath, '/safety/report/edit/') ? 'active' : '' ?>">
                    <span class="icon">📝</span> Draft Reports
                    <?php if ($safetyDraftCount > 0): ?>
                        <span style="margin-left:auto;background:#6b7280;color:#fff;font-size:9px;font-weight:800;padding:1px 5px;border-radius:3px;"><?= $safetyDraftCount ?></span>
                    <?php endif; ?>
                </a>
            </div>
            <?php endif; ?>

            <!-- ─── Scheduling ───────────────────────────── -->
            <?php if (hasAnyRole(['airline_admin','scheduler','chief_pilot','head_cabin_crew','base_manager','pilot','cabin_crew','engineer'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Scheduling</div>

                <?php if (hasAnyRole(['airline_admin','scheduler','chief_pilot','head_cabin_crew','base_manager'])): ?>
                <!-- Scheduler workbench -->
                <a href="/roster" class="sidebar-link <?= str_starts_with($currentPath, '/roster') && !str_starts_with($currentPath,'/roster/standby') && !str_starts_with($currentPath,'/roster/changes') && !str_starts_with($currentPath,'/roster/revisions') && !str_starts_with($currentPath,'/roster/coverage') ? 'active' : '' ?>">
                    <span class="icon">🗓</span> Roster Workbench
                </a>
                <a href="/roster/periods" class="sidebar-link <?= str_starts_with($currentPath, '/roster/periods') ? 'active' : '' ?>">
                    <span class="icon">📅</span> Roster Periods
                </a>
                <a href="/roster/revisions" class="sidebar-link <?= str_starts_with($currentPath, '/roster/revisions') ? 'active' : '' ?>">
                    <span class="icon">✏️</span> Revisions
                    <?php
                    // Show pending indicator if draft revisions exist
                    $draftRevCount = 0;
                    try { $draftRevCount = (int)(Database::fetch("SELECT COUNT(*) AS c FROM roster_revisions WHERE tenant_id = ? AND status = 'draft'", [currentTenantId()])['c'] ?? 0); } catch(\Exception $e) {}
                    if ($draftRevCount > 0): ?>
                        <span style="margin-left:auto;background:#f59e0b;color:#fff;font-size:9px;font-weight:800;padding:1px 5px;border-radius:3px;"><?= $draftRevCount ?></span>
                    <?php endif; ?>
                </a>
                <a href="/roster/standby" class="sidebar-link <?= str_starts_with($currentPath, '/roster/standby') ? 'active' : '' ?>">
                    <span class="icon">🛡</span> Reserve / Standby
                </a>
                <a href="/roster/coverage" class="sidebar-link <?= str_starts_with($currentPath, '/roster/coverage') ? 'active' : '' ?>">
                    <span class="icon">📊</span> Coverage &amp; Conflicts
                </a>
                <a href="/roster/changes" class="sidebar-link <?= str_starts_with($currentPath, '/roster/changes') ? 'active' : '' ?>">
                    <span class="icon">💬</span> Change Requests
                    <?php
                    $pendingCrCount = 0;
                    try { $pendingCrCount = (int)(Database::fetch("SELECT COUNT(*) AS c FROM roster_changes WHERE tenant_id = ? AND status = 'pending'", [currentTenantId()])['c'] ?? 0); } catch(\Exception $e) {}
                    if ($pendingCrCount > 0): ?>
                        <span style="margin-left:auto;background:#ef4444;color:#fff;font-size:9px;font-weight:800;padding:1px 5px;border-radius:3px;"><?= $pendingCrCount ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>

                <?php if (hasAnyRole(['pilot','cabin_crew','engineer','chief_pilot','head_cabin_crew','base_manager'])): ?>
                <!-- Crew personal roster -->
                <a href="/my-roster" class="sidebar-link <?= str_starts_with($currentPath, '/my-roster') ? 'active' : '' ?>">
                    <span class="icon">📋</span> My Roster
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- ─── Duty Reporting ───────────────────────── -->
            <?php
            // Compute once: is the current user permitted to self-report per
            // the tenant's duty_reporting_settings.allowed_roles list?
            $dutyCrewAllowed  = false;
            $dutyAdmin        = hasAnyRole(['airline_admin','hr','chief_pilot','head_cabin_crew','engineering_manager','base_manager','scheduler']);
            try {
                $_tid = (int) currentTenantId();
                if ($_tid > 0 && class_exists('DutyReportingSettings')) {
                    $_drs = DutyReportingSettings::forTenant($_tid);
                    if (!empty($_drs['enabled'])) {
                        $dutyCrewAllowed = DutyReportingSettings::userAllowed($_tid, $_SESSION['user_roles'] ?? []);
                    }
                }
            } catch (\Throwable $e) { /* never break the sidebar */ }
            ?>
            <?php if ($dutyAdmin || $dutyCrewAllowed): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Duty Reporting</div>

                <?php if ($dutyCrewAllowed): ?>
                <a href="/my-duty" class="sidebar-link <?= str_starts_with($currentPath, '/my-duty') ? 'active' : '' ?>">
                    <span class="icon">✈️</span> My Duty
                </a>
                <?php endif; ?>

                <?php if ($dutyAdmin): ?>
                <a href="/duty-reporting" class="sidebar-link <?= (rtrim($currentPath, '/') === '/duty-reporting' || str_starts_with($currentPath, '/duty-reporting/report') || str_starts_with($currentPath, '/duty-reporting/history')) ? 'active' : '' ?>">
                    <span class="icon">🟢</span> On Duty Now
                </a>
                <a href="/duty-reporting/exceptions" class="sidebar-link <?= str_starts_with($currentPath, '/duty-reporting/exceptions') ? 'active' : '' ?>">
                    <span class="icon">⚠️</span> Duty Exceptions
                    <?php
                    $pendingDutyEx = 0;
                    try {
                        $pendingDutyEx = (int)(Database::fetch(
                            "SELECT COUNT(*) AS c FROM duty_exceptions WHERE tenant_id = ? AND status = 'pending'",
                            [currentTenantId()]
                        )['c'] ?? 0);
                    } catch (\Exception $e) {}
                    if ($pendingDutyEx > 0): ?>
                        <span style="margin-left:auto;background:#f59e0b;color:#fff;font-size:9px;font-weight:800;padding:1px 5px;border-radius:3px;"><?= $pendingDutyEx ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>

                <?php if (hasAnyRole(['airline_admin','super_admin'])): ?>
                <a href="/duty-reporting/settings" class="sidebar-link <?= str_starts_with($currentPath, '/duty-reporting/settings') ? 'active' : '' ?>">
                    <span class="icon">⚙️</span> Settings
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- ─── Content: Documents & Notices ─────────── -->
            <?php if (hasAnyRole(['airline_admin','hr','document_control','safety_officer','chief_pilot',
                                   'head_cabin_crew','engineering_manager','base_manager','training_admin',
                                   'fdm_analyst','pilot','cabin_crew','engineer'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Content</div>
                <a href="/files" class="sidebar-link <?= str_starts_with($currentPath, '/files') ? 'active' : '' ?>">
                    <span class="icon">📄</span> Documents
                </a>
                <?php if (hasAnyRole(['airline_admin','safety_officer','document_control','chief_pilot',
                                       'head_cabin_crew','engineering_manager','hr','training_admin'])): ?>
                <a href="/notices" class="sidebar-link <?= str_starts_with($currentPath, '/notices') ? 'active' : '' ?>">
                    <span class="icon">📢</span> Notices
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- ─── Safety & Compliance ──────────────────── -->
            <?php if (hasAnyRole(['airline_admin','safety_officer','fdm_analyst','chief_pilot','hr'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Safety</div>

                <?php if (hasAnyRole(['airline_admin','safety_officer'])): ?>
                <!-- Safety Management sub-menu (team only) -->
                <a href="/safety/dashboard" class="sidebar-link <?= str_starts_with($currentPath, '/safety/dashboard') ? 'active' : '' ?>">
                    <span class="icon">📊</span> Safety Dashboard
                </a>
                <a href="/safety/queue" class="sidebar-link <?= (str_starts_with($currentPath, '/safety/queue') || (str_starts_with($currentPath, '/safety/team/report') )) ? 'active' : '' ?>">
                    <span class="icon">📋</span> Reports Queue
                </a>
                <a href="/safety/team/actions" class="sidebar-link <?= str_starts_with($currentPath, '/safety/team/actions') ? 'active' : '' ?>">
                    <span class="icon">⚙️</span> Corrective Actions
                </a>
                <a href="/safety/publications" class="sidebar-link <?= str_starts_with($currentPath, '/safety/publication') ? 'active' : '' ?>">
                    <span class="icon">📢</span> Publications
                </a>
                <a href="/safety/select-type" class="sidebar-link <?=
                    (str_starts_with($currentPath, '/safety/select-type')
                     || str_starts_with($currentPath, '/safety/report/new')
                     || str_starts_with($currentPath, '/safety/quick-report'))
                    ? 'active' : '' ?>">
                    <span class="icon">✏️</span> Submit a Report
                </a>
                <?php if (hasAnyRole(['safety_officer','airline_admin','super_admin'])): ?>
                <a href="/safety/settings" class="sidebar-link <?= str_starts_with($currentPath, '/safety/settings') ? 'active' : '' ?>">
                    <span class="icon">🔧</span> Safety Settings
                </a>
                <?php endif; ?>
                <?php endif; ?>

                <?php if (hasAnyRole(['airline_admin','safety_officer','fdm_analyst'])): ?>
                <a href="/fdm" class="sidebar-link <?= str_starts_with($currentPath, '/fdm') ? 'active' : '' ?>">
                    <span class="icon">📈</span> FDM Data
                </a>
                <?php endif; ?>

                <a href="/compliance" class="sidebar-link <?= str_starts_with($currentPath, '/compliance') ? 'active' : '' ?>">
                    <span class="icon">✅</span> Compliance
                </a>
            </div>
            <?php endif; ?>

            <!-- ─── Audit Log (airline level) ────────────── -->
            <?php if (hasAnyRole(['airline_admin','safety_officer'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Security</div>
                <a href="/audit-log" class="sidebar-link <?= str_starts_with($currentPath, '/audit-log') ? 'active' : '' ?>">
                    <span class="icon">🔒</span> Audit Log
                </a>
            </div>
            <?php endif; ?>

            <!-- ─── Administration ───────────────────────── -->
            <?php if (hasAnyRole(['airline_admin', 'hr', 'base_manager', 'chief_pilot', 'engineering_manager'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Administration</div>
                <?php if (hasAnyRole(['airline_admin', 'hr'])): ?>
                <a href="/departments" class="sidebar-link <?= str_starts_with($currentPath, '/departments') ? 'active' : '' ?>">
                    <span class="icon">🏢</span> Departments
                </a>
                <?php endif; ?>
                <?php if (hasAnyRole(['airline_admin', 'base_manager'])): ?>
                <a href="/bases" class="sidebar-link <?= str_starts_with($currentPath, '/bases') ? 'active' : '' ?>">
                    <span class="icon">📍</span> Bases
                </a>
                <?php endif; ?>
                <?php if (hasAnyRole(['airline_admin', 'chief_pilot', 'engineering_manager'])): ?>
                <a href="/fleets" class="sidebar-link <?= str_starts_with($currentPath, '/fleets') ? 'active' : '' ?>">
                    <span class="icon">✈</span> Fleets
                </a>
                <?php endif; ?>
                <?php if (hasRole('airline_admin')): ?>
                <a href="/roles" class="sidebar-link <?= str_starts_with($currentPath, '/roles') ? 'active' : '' ?>">
                    <span class="icon">🛡</span> Roles & Permissions
                </a>
                <a href="/airline/profile" class="sidebar-link <?= str_starts_with($currentPath, '/airline') ? 'active' : '' ?>">
                    <span class="icon">⚙️</span> Airline Profile
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- ─── App Install ───────────────────────────── -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">App</div>
                <a href="/install" class="sidebar-link <?= str_starts_with($currentPath, '/install') ? 'active' : '' ?>">
                    <span class="icon">📲</span> Install App
                </a>
            </div>

        <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-user-avatar">
                    <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?= e($user['name'] ?? '') ?></div>
                    <div class="sidebar-user-role"><?= e($roleLabel) ?></div>
                </div>
            </div>
            <a href="/logout" class="sidebar-link mt-1" style="color: var(--accent-red);">
                <span class="icon">🚪</span> Sign Out
            </a>
        </div>
    </aside>

    <main class="main-content">
        <div class="content-header">
            <div>
                <h2><?= e($pageTitle ?? 'Dashboard') ?></h2>
                <?php if (!empty($pageSubtitle)): ?>
                    <p><?= e($pageSubtitle) ?></p>
                <?php endif; ?>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <?php if (!empty($headerAction)): ?>
                    <?= $headerAction ?>
                <?php endif; ?>
                <!-- Safety message notification bell -->
                <?php
                $_safetyBellRoles = ['safety_manager','safety_staff','safety_officer','airline_admin','super_admin'];
                $_userRoles = $_SESSION['user_roles'] ?? [];
                $_isTeamBell = (bool) array_intersect($_safetyBellRoles, $_userRoles);
                $_bellLink = $_isTeamBell ? '/safety/queue' : '/safety/my-reports';
                $_bellTitle = $_isTeamBell ? 'Pilot replies waiting' : 'Safety team messages';
                ?>
                <a id="safety-bell-btn" href="<?= $_bellLink ?>" title="<?= $_bellTitle ?>"
                   style="position:relative;display:inline-flex;align-items:center;justify-content:center;
                          width:36px;height:36px;border-radius:8px;background:var(--bg-card,#1e2535);
                          border:1px solid var(--border-color,rgba(255,255,255,0.08));
                          color:var(--text-secondary,#94a3b8);text-decoration:none;
                          font-size:17px;transition:background 0.15s,border-color 0.15s;"
                   onmouseover="this.style.background='rgba(99,102,241,0.12)';this.style.borderColor='rgba(99,102,241,0.4)'"
                   onmouseout="this.style.background='var(--bg-card,#1e2535)';this.style.borderColor='var(--border-color,rgba(255,255,255,0.08))'">
                    🛡️
                    <span id="safety-bell-badge" style="
                        display:none;
                        position:absolute;top:-4px;right:-4px;
                        min-width:16px;height:16px;padding:0 4px;
                        background:#ef4444;color:#fff;
                        font-size:9px;font-weight:800;line-height:16px;
                        border-radius:8px;text-align:center;
                        border:2px solid var(--bg-main,#111827);
                        pointer-events:none;">0</span>
                </a>
            </div>
        </div>

        <div class="content-body">
            <?php if ($msg = flash('success')): ?>
                <div class="alert alert-success">✓ <?= e($msg) ?></div>
            <?php endif; ?>
            <?php if ($msg = flash('error')): ?>
                <div class="alert alert-error">⚠ <?= e($msg) ?></div>
            <?php endif; ?>

            <?= $content ?? '' ?>
        </div>
    </main>
</div>
<script>
(function() {
    const toggle  = document.getElementById('mobileToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (toggle && sidebar && overlay) {
        toggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        });
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });
    }
})();

// ── Safety Notification Bell — live poll every 30 s ──────────────────────
(function() {
    var badge = document.getElementById('safety-bell-badge');
    var btn   = document.getElementById('safety-bell-btn');
    if (!badge || !btn) return;

    function updateBell() {
        fetch('/safety/notifications/count', {credentials: 'same-origin'})
            .then(function(r) { return r.ok ? r.json() : null; })
            .then(function(data) {
                if (!data) return;
                var n = parseInt(data.count, 10) || 0;
                badge.textContent = n > 99 ? '99+' : String(n);
                if (n > 0) {
                    badge.style.display = 'block';
                    btn.style.borderColor = 'rgba(239,68,68,0.6)';
                    btn.title = (data.for_team
                        ? 'Pilot replies waiting: ' + n
                        : 'Safety team messages: ' + n);
                } else {
                    badge.style.display = 'none';
                    btn.style.borderColor = 'var(--border-color,rgba(255,255,255,0.08))';
                }
            })
            .catch(function() { /* silently ignore network errors */ });
    }

    updateBell();                        // run immediately on page load
    setInterval(updateBell, 30000);      // then every 30 seconds
})();
</script>
</body>
</html>
