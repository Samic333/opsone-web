<?php
/**
 * Sidebar Navigation Registry — consolidated to 6 groups (Phase C).
 *
 * Single source of truth for the left-hand navigation. The layout
 * (views/partials/sidebar.php) renders sections/items from this file
 * using filter rules declared inline on each item.
 *
 * Each item can gate visibility by:
 *   - roles      (array<string>)  — user must hold ANY of these roles
 *   - module     (?string)        — tenant module code; if set, module must be enabled
 *                                    for the current tenant (ignored for platform users)
 *   - capability (?string)        — capability check (module.cap via canAccessModule)
 *   - platform   (bool)           — item only renders for platform users
 *   - airline    (bool)           — item only renders for airline (tenant) users
 *   - when       (callable)       — arbitrary callable returning bool (power user gate)
 *
 * Sections can ALSO set `roles` / `platform` / `airline` for early bail-out.
 * A section with zero visible items is hidden automatically.
 *
 * The "Me" personal-workspace items live in the avatar dropdown
 * (views/partials/header_bar.php), not in the sidebar.
 *
 * icon values map to sidebarIcon() keys in app/Helpers/functions.php.
 */

return [

    // ─────────────────────────────────────────────────────────────
    //  PLATFORM SIDE  (Platform Super Admin, Support, Security, etc.)
    // ─────────────────────────────────────────────────────────────

    'platform' => [

        [
            'title'    => 'Platform',
            'platform' => true,
            'items'    => [
                ['label' => 'Platform Overview', 'href' => '/dashboard', 'icon' => 'squares',
                 'match' => '/dashboard'],
            ],
        ],

        [
            'title'    => 'Airlines',
            'platform' => true,
            'roles'    => ['super_admin', 'platform_support', 'system_monitoring'],
            'items'    => [
                ['label' => 'Airline Registry', 'href' => '/tenants', 'icon' => 'building-office',
                 'match' => '/tenants',
                 'roles' => ['super_admin', 'platform_support', 'system_monitoring']],
                ['label' => 'Onboarding', 'href' => '/platform/onboarding', 'icon' => 'rocket-launch',
                 'match' => '/platform/onboarding', 'badge' => 'pending_onboarding',
                 'roles' => ['super_admin']],
            ],
        ],

        [
            'title'    => 'Platform Staff',
            'platform' => true,
            'roles'    => ['super_admin'],
            'items'    => [
                ['label' => 'Staff Accounts', 'href' => '/platform/users', 'icon' => 'user',
                 'match' => '/platform/users', 'roles' => ['super_admin']],
            ],
        ],

        [
            'title'    => 'Configuration',
            'platform' => true,
            'roles'    => ['super_admin'],
            'items'    => [
                ['label' => 'Module Catalog',  'href' => '/platform/modules',       'icon' => 'puzzle-piece',
                 'match' => '/platform/modules',       'roles' => ['super_admin']],
                ['label' => 'Feature Flags',   'href' => '/platform/feature-flags', 'icon' => 'flag',
                 'match' => '/platform/feature-flags', 'roles' => ['super_admin']],
            ],
        ],

        [
            'title'    => 'Security',
            'platform' => true,
            'roles'    => ['super_admin', 'platform_security'],
            'items'    => [
                ['label' => 'Login Activity', 'href' => '/audit-log/logins', 'icon' => 'key',
                 'match' => '/audit-log/logins',
                 'roles' => ['super_admin', 'platform_security']],
                ['label' => 'Audit Log', 'href' => '/audit-log', 'icon' => 'lock-closed',
                 'match' => '/audit-log', 'match_exact' => true,
                 'roles' => ['super_admin', 'platform_security']],
            ],
        ],

        [
            'title'    => 'Support',
            'platform' => true,
            'roles'    => ['super_admin', 'platform_support'],
            'items'    => [
                ['label' => 'All Devices', 'href' => '/devices', 'icon' => 'device-tablet',
                 'match' => '/devices',
                 'roles' => ['super_admin', 'platform_support']],
            ],
        ],

    ],

    // ─────────────────────────────────────────────────────────────
    //  AIRLINE SIDE  (tenant users — any airline role)
    //  6 groups: Main / People / Operations / Content / Safety / Administration
    // ─────────────────────────────────────────────────────────────

    'airline' => [

        // ── 1. MAIN ──────────────────────────────────────────────
        [
            'title'   => 'Main',
            'airline' => true,
            'items'   => [
                ['label' => 'Dashboard', 'href' => '/dashboard', 'icon' => 'squares',
                 'match' => '/dashboard'],
            ],
        ],

        // ── 2. PEOPLE (people + personnel records merged) ────────
        [
            'title'   => 'People',
            'airline' => true,
            'roles'   => ['airline_admin','hr','chief_pilot','head_cabin_crew',
                          'engineering_manager','base_manager','safety_officer',
                          'training_admin','fdm_analyst'],
            'items'   => [
                ['label' => 'Users',         'href' => '/users',         'icon' => 'users',
                 'match' => '/users',
                 'roles' => ['airline_admin','hr']],
                ['label' => 'Crew Profiles', 'href' => '/crew-profiles', 'icon' => 'identification',
                 'match' => '/crew-profiles',
                 'roles' => ['airline_admin','hr','chief_pilot','head_cabin_crew',
                             'engineering_manager','base_manager','safety_officer','training_admin']],
                ['label' => 'iPad Devices',  'href' => '/devices',       'icon' => 'device-tablet',
                 'match' => '/devices', 'badge' => 'pending_devices',
                 'roles' => ['airline_admin','hr']],
                ['label' => 'Licensing & Compliance', 'href' => '/compliance', 'icon' => 'shield-check',
                 'match' => '/compliance', 'match_exact' => true,
                 'module' => 'compliance',
                 'roles' => ['airline_admin','hr','chief_pilot','safety_officer','fdm_analyst']],
                ['label' => 'Personnel Documents', 'href' => '/personnel/documents', 'icon' => 'folder-open',
                 'match' => '/personnel/documents', 'badge' => 'pending_personnel_docs',
                 'roles' => ['airline_admin','hr','chief_pilot','head_cabin_crew',
                             'engineering_manager','training_admin']],
                ['label' => 'Change Requests', 'href' => '/personnel/change-requests', 'icon' => 'document-text',
                 'match' => '/personnel/change-requests', 'badge' => 'pending_change_requests',
                 'roles' => ['airline_admin','hr','chief_pilot','head_cabin_crew',
                             'engineering_manager','training_admin']],
                ['label' => 'Eligibility Status', 'href' => '/personnel/eligibility', 'icon' => 'check-badge',
                 'match' => '/personnel/eligibility',
                 'roles' => ['airline_admin','hr','chief_pilot','head_cabin_crew',
                             'engineering_manager','safety_officer','training_admin','fdm_analyst']],
                ['label' => 'Expiry Alerts', 'href' => '/compliance/expiring', 'icon' => 'clock',
                 'match' => '/compliance/expiring',
                 'module' => 'compliance',
                 'roles' => ['airline_admin','hr','chief_pilot','head_cabin_crew',
                             'safety_officer','fdm_analyst']],
            ],
        ],

        // ── 3. OPERATIONS (scheduling + duty + fleet + content merged) ─
        [
            'title'   => 'Operations',
            'airline' => true,
            'roles'   => ['airline_admin','scheduler','chief_pilot','head_cabin_crew',
                          'base_manager','engineering_manager','pilot','cabin_crew','engineer',
                          'hr','document_control','safety_officer','training_admin'],
            'items'   => [
                ['label' => 'Flights', 'href' => '/flights', 'icon' => 'paper-airplane',
                 'match' => '/flights',
                 'roles' => ['airline_admin','scheduler','chief_pilot','base_manager']],
                ['label' => 'Roster Workbench', 'href' => '/roster', 'icon' => 'calendar',
                 'match' => '/roster', 'match_exact' => true,
                 'roles' => ['airline_admin','scheduler','chief_pilot','head_cabin_crew'],
                 'module' => 'rostering'],
                ['label' => 'Roster Periods', 'href' => '/roster/periods', 'icon' => 'calendar-days',
                 'match' => '/roster/periods',
                 'roles' => ['airline_admin','scheduler'],
                 'module' => 'rostering'],
                ['label' => 'Roster Revisions', 'href' => '/roster/revisions', 'icon' => 'pencil',
                 'match' => '/roster/revisions', 'badge' => 'roster_draft_revisions',
                 'roles' => ['airline_admin','scheduler','chief_pilot','head_cabin_crew'],
                 'module' => 'rostering'],
                ['label' => 'Reserve / Standby', 'href' => '/roster/standby', 'icon' => 'shield',
                 'match' => '/roster/standby',
                 'roles' => ['airline_admin','scheduler'],
                 'module' => 'standby_pool'],
                ['label' => 'Coverage & Conflicts', 'href' => '/roster/coverage', 'icon' => 'chart-pie',
                 'match' => '/roster/coverage',
                 'roles' => ['airline_admin','scheduler','chief_pilot','head_cabin_crew'],
                 'module' => 'rostering'],
                ['label' => 'Roster Change Requests', 'href' => '/roster/changes', 'icon' => 'chat-bubble',
                 'match' => '/roster/changes', 'badge' => 'roster_pending_changes',
                 'roles' => ['airline_admin','scheduler','chief_pilot','head_cabin_crew'],
                 'module' => 'rostering'],
                // Duty Reporting (was its own group)
                ['label' => 'On Duty Now', 'href' => '/duty-reporting', 'icon' => 'users',
                 'match' => '/duty-reporting', 'match_exact' => true,
                 'match_prefixes' => ['/duty-reporting/report','/duty-reporting/history'],
                 'module' => 'duty_reporting',
                 'roles' => ['airline_admin','chief_pilot','head_cabin_crew',
                             'engineering_manager','base_manager']],
                ['label' => 'Duty Exceptions', 'href' => '/duty-reporting/exceptions', 'icon' => 'exclamation',
                 'match' => '/duty-reporting/exceptions', 'badge' => 'duty_exceptions_pending',
                 'module' => 'duty_reporting',
                 'roles' => ['airline_admin','chief_pilot','head_cabin_crew',
                             'engineering_manager','base_manager']],
                ['label' => 'Duty Settings', 'href' => '/duty-reporting/settings', 'icon' => 'cog',
                 'match' => '/duty-reporting/settings',
                 'module' => 'duty_reporting',
                 'roles' => ['airline_admin']],
                // Fleet (was its own group)
                ['label' => 'Aircraft Registry', 'href' => '/aircraft', 'icon' => 'paper-airplane',
                 'match' => '/aircraft', 'badge' => 'aog_count',
                 'roles' => ['airline_admin','engineering_manager','chief_pilot','base_manager']],
                // Briefings & Manuals (formerly the "Content" group — folded
                // into Operations in Phase I to match the 5-group brief)
                ['label' => 'Manuals & Documents', 'href' => '/files', 'icon' => 'folder-open',
                 'match' => '/files',
                 'module' => 'manuals',
                 'roles' => ['airline_admin','hr','document_control','safety_officer']],
                ['label' => 'Notices & Briefings', 'href' => '/notices', 'icon' => 'megaphone',
                 'match' => '/notices',
                 'module' => 'notices',
                 'roles' => ['airline_admin','safety_officer','document_control','chief_pilot',
                             'head_cabin_crew','engineering_manager','hr','training_admin']],
            ],
        ],

        // ── 4. SAFETY (safety + FDM + audit log) ─────────────────
        [
            'title'   => 'Safety',
            'airline' => true,
            'roles'   => ['airline_admin','safety_officer','fdm_analyst','chief_pilot','hr'],
            'items'   => [
                ['label' => 'Safety Dashboard', 'href' => '/safety/dashboard', 'icon' => 'chart-bar',
                 'match' => '/safety/dashboard',
                 'module' => 'safety_reports',
                 'roles' => ['airline_admin','safety_officer']],
                ['label' => 'Reports Queue', 'href' => '/safety/queue', 'icon' => 'clipboard-list',
                 'match' => '/safety/queue', 'match_prefixes' => ['/safety/team/report/'],
                 'module' => 'safety_reports',
                 'roles' => ['airline_admin','safety_officer']],
                ['label' => 'Corrective Actions', 'href' => '/safety/team/actions', 'icon' => 'wrench',
                 'module' => 'safety_reports',
                 'match' => '/safety/team/actions',
                 'roles' => ['airline_admin','safety_officer']],
                ['label' => 'Publications', 'href' => '/safety/publications', 'icon' => 'megaphone',
                 'match' => '/safety/publication',
                 'module' => 'safety_reports',
                 'roles' => ['airline_admin','safety_officer']],
                ['label' => 'Submit a Report', 'href' => '/safety/select-type', 'icon' => 'shield-exclamation',
                 'match' => '/safety/select-type',
                 'match_prefixes' => ['/safety/report/new','/safety/quick-report'],
                 'module' => 'safety_reports',
                 'roles' => ['airline_admin','safety_officer']],
                ['label' => 'Safety Settings', 'href' => '/safety/settings', 'icon' => 'cog',
                 'match' => '/safety/settings',
                 'roles' => ['airline_admin','safety_officer']],
                ['label' => 'FDM Data', 'href' => '/fdm', 'icon' => 'signal',
                 'match' => '/fdm', 'match_exact' => true,
                 'module' => 'fdm',
                 'roles' => ['airline_admin','safety_officer','fdm_analyst']],
                ['label' => 'Audit Log', 'href' => '/audit-log', 'icon' => 'lock-closed',
                 'match' => '/audit-log',
                 'roles' => ['airline_admin','safety_officer']],
            ],
        ],

        // ── 6. ADMINISTRATION (people-ops + admin merged) ────────
        [
            'title'   => 'Administration',
            'airline' => true,
            'roles'   => ['airline_admin','hr','base_manager','chief_pilot','engineering_manager',
                          'head_cabin_crew','training_admin'],
            'items'   => [
                // People Ops
                ['label' => 'HR Workflow', 'href' => '/hr', 'icon' => 'briefcase',
                 'match' => '/hr', 'match_exact' => true,
                 'roles' => ['airline_admin','hr']],
                ['label' => 'Per Diem Claims', 'href' => '/per-diem/claims', 'icon' => 'currency-dollar',
                 'match' => '/per-diem', 'badge' => 'per_diem_submitted',
                 'roles' => ['airline_admin','hr']],
                ['label' => 'Per Diem Rates', 'href' => '/per-diem/rates', 'icon' => 'adjustments',
                 'match' => '/per-diem/rates', 'match_exact' => true,
                 'roles' => ['airline_admin','hr']],
                ['label' => 'Training Dashboard', 'href' => '/training', 'icon' => 'academic-cap',
                 'match' => '/training', 'match_exact' => true,
                 'module' => 'training',
                 'roles' => ['airline_admin','hr','training_admin','chief_pilot','head_cabin_crew']],
                ['label' => 'Logbook Overview', 'href' => '/logbook', 'icon' => 'book-open',
                 'match' => '/logbook', 'match_exact' => true,
                 'roles' => ['airline_admin','chief_pilot','hr','training_admin']],
                ['label' => 'Appraisals Review', 'href' => '/appraisals', 'icon' => 'star',
                 'match' => '/appraisals', 'match_exact' => true,
                 'roles' => ['airline_admin','hr','chief_pilot','head_cabin_crew']],
                // Admin
                ['label' => 'Departments', 'href' => '/departments', 'icon' => 'building-library',
                 'match' => '/departments',
                 'roles' => ['airline_admin','hr']],
                ['label' => 'Bases', 'href' => '/bases', 'icon' => 'map-pin',
                 'match' => '/bases',
                 'roles' => ['airline_admin','base_manager']],
                ['label' => 'Fleets', 'href' => '/fleets', 'icon' => 'truck',
                 'match' => '/fleets',
                 'roles' => ['airline_admin','chief_pilot','engineering_manager']],
                ['label' => 'Roles & Permissions', 'href' => '/roles', 'icon' => 'key',
                 'match' => '/roles',
                 'roles' => ['airline_admin']],
                ['label' => 'Airline Profile', 'href' => '/airline/profile', 'icon' => 'cog',
                 'match' => '/airline',
                 'roles' => ['airline_admin']],
                ['label' => 'Integrations', 'href' => '/integrations', 'icon' => 'plug',
                 'match' => '/integrations',
                 'roles' => ['airline_admin']],
            ],
        ],

    ],

];
