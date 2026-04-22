<?php
/**
 * Sidebar Navigation Registry
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
                ['label' => 'Platform Overview', 'href' => '/dashboard', 'icon' => '📊',
                 'match' => '/dashboard'],
            ],
        ],

        [
            'title'    => 'Airlines',
            'platform' => true,
            'roles'    => ['super_admin', 'platform_support', 'system_monitoring'],
            'items'    => [
                ['label' => 'Airline Registry', 'href' => '/tenants', 'icon' => '🏢',
                 'match' => '/tenants',
                 'roles' => ['super_admin', 'platform_support', 'system_monitoring']],
                ['label' => 'Onboarding', 'href' => '/platform/onboarding', 'icon' => '✈',
                 'match' => '/platform/onboarding', 'badge' => 'pending_onboarding',
                 'roles' => ['super_admin']],
            ],
        ],

        [
            'title'    => 'Platform Staff',
            'platform' => true,
            'roles'    => ['super_admin'],
            'items'    => [
                ['label' => 'Staff Accounts', 'href' => '/platform/users', 'icon' => '👤',
                 'match' => '/platform/users', 'roles' => ['super_admin']],
            ],
        ],

        [
            'title'    => 'Configuration',
            'platform' => true,
            'roles'    => ['super_admin'],
            'items'    => [
                ['label' => 'Module Catalog',  'href' => '/platform/modules',       'icon' => '🧩',
                 'match' => '/platform/modules',       'roles' => ['super_admin']],
                ['label' => 'Feature Flags',   'href' => '/platform/feature-flags', 'icon' => '🚩',
                 'match' => '/platform/feature-flags', 'roles' => ['super_admin']],
            ],
        ],

        [
            'title'    => 'Security',
            'platform' => true,
            'roles'    => ['super_admin', 'platform_security'],
            'items'    => [
                ['label' => 'Login Activity', 'href' => '/audit-log/logins', 'icon' => '🔑',
                 'match' => '/audit-log/logins',
                 'roles' => ['super_admin', 'platform_security']],
                ['label' => 'Audit Log', 'href' => '/audit-log', 'icon' => '🔒',
                 'match' => '/audit-log', 'match_exact' => true,
                 'roles' => ['super_admin', 'platform_security']],
            ],
        ],

        [
            'title'    => 'Support',
            'platform' => true,
            'roles'    => ['super_admin', 'platform_support'],
            'items'    => [
                ['label' => 'All Devices', 'href' => '/devices', 'icon' => '📱',
                 'match' => '/devices',
                 'roles' => ['super_admin', 'platform_support']],
                // Note: "App Builds" / install pages intentionally omitted from web nav.
                // Mobile-app distribution is handled externally (email / invitation flow).
            ],
        ],

    ],

    // ─────────────────────────────────────────────────────────────
    //  AIRLINE SIDE  (tenant users — any airline role)
    // ─────────────────────────────────────────────────────────────

    'airline' => [

        [
            'title'   => 'Main',
            'airline' => true,
            'items'   => [
                ['label' => 'Dashboard', 'href' => '/dashboard', 'icon' => '📊',
                 'match' => '/dashboard'],
            ],
        ],

        // ── PEOPLE ──────────────────────────────────────────────
        [
            'title'   => 'People',
            'airline' => true,
            'roles'   => ['airline_admin','hr','training_admin','chief_pilot',
                          'head_cabin_crew','engineering_manager','base_manager','safety_officer'],
            'items'   => [
                ['label' => 'Users',         'href' => '/users',         'icon' => '👥',
                 'match' => '/users',
                 'roles' => ['airline_admin','hr','training_admin']],
                ['label' => 'Crew Profiles', 'href' => '/crew-profiles', 'icon' => '🪪',
                 'match' => '/crew-profiles',
                 'roles' => ['airline_admin','hr','training_admin','chief_pilot',
                             'head_cabin_crew','engineering_manager','base_manager','safety_officer']],
                ['label' => 'iPad Devices',  'href' => '/devices',       'icon' => '📱',
                 'match' => '/devices', 'badge' => 'pending_devices',
                 'roles' => ['airline_admin','hr','training_admin','chief_pilot',
                             'head_cabin_crew','engineering_manager','base_manager','safety_officer']],
            ],
        ],

        // ── PERSONNEL RECORDS (formerly produced "Documents" duplicate) ──
        [
            'title'   => 'Personnel Records',
            'airline' => true,
            'roles'   => ['airline_admin','hr','chief_pilot','head_cabin_crew',
                          'engineering_manager','safety_officer','training_admin',
                          'scheduler','base_manager','fdm_analyst'],
            'items'   => [
                ['label' => 'Licensing & Compliance', 'href' => '/compliance', 'icon' => '🛡',
                 'match' => '/compliance', 'match_exact' => true,
                 'module' => 'compliance'],
                ['label' => 'Personnel Documents', 'href' => '/personnel/documents', 'icon' => '📁',
                 'match' => '/personnel/documents', 'badge' => 'pending_personnel_docs'],
                ['label' => 'Change Requests', 'href' => '/personnel/change-requests', 'icon' => '📝',
                 'match' => '/personnel/change-requests', 'badge' => 'pending_change_requests',
                 'roles' => ['airline_admin','hr','chief_pilot','head_cabin_crew',
                             'engineering_manager','safety_officer','training_admin','fdm_analyst']],
                ['label' => 'Eligibility Status', 'href' => '/personnel/eligibility', 'icon' => '✅',
                 'match' => '/personnel/eligibility'],
                ['label' => 'Expiry Alerts', 'href' => '/compliance/expiring', 'icon' => '⏳',
                 'match' => '/compliance/expiring',
                 'module' => 'compliance'],
            ],
        ],

        // ── ME (self-service; crew + management) ───────────────
        [
            'title'   => 'Me',
            'airline' => true,
            'roles'   => ['pilot','cabin_crew','engineer','scheduler','chief_pilot',
                          'head_cabin_crew','engineering_manager','base_manager',
                          'training_admin','fdm_analyst','document_control'],
            'items'   => [
                ['label' => 'My Profile', 'href' => '/my-profile', 'icon' => '👤',
                 'match' => '/my-profile', 'match_exact' => true],
                ['label' => 'My Change Requests', 'href' => '/my-profile/change-requests', 'icon' => '📝',
                 'match' => '/my-profile/change-requests'],
                ['label' => 'Operational Notices', 'href' => '/my-notices', 'icon' => '📬',
                 'match' => '/my-notices', 'module' => 'notices'],
                ['label' => 'My Safety Reports', 'href' => '/safety/my-reports', 'icon' => '🛡️',
                 'match' => '/safety/my-reports', 'match_prefixes' => ['/safety/report/'],
                 'badge' => 'safety_pending_replies',
                 'module' => 'safety_reports'],
                ['label' => 'Draft Reports', 'href' => '/safety/drafts', 'icon' => '📝',
                 'match' => '/safety/drafts', 'match_prefixes' => ['/safety/report/edit/'],
                 'badge' => 'safety_draft_count',
                 'module' => 'safety_reports'],
                ['label' => 'My Documents', 'href' => '/my-files', 'icon' => '📄',
                 'match' => '/my-files'],
                ['label' => 'Notifications', 'href' => '/notifications', 'icon' => '🔔',
                 'match' => '/notifications', 'badge' => 'notif_unread'],
                ['label' => 'My Logbook', 'href' => '/my-logbook', 'icon' => '📒',
                 'match' => '/my-logbook',
                 'roles' => ['pilot']],
                ['label' => 'My FDM Events', 'href' => '/my-fdm', 'icon' => '📈',
                 'match' => '/my-fdm', 'badge' => 'my_fdm_pending',
                 'roles' => ['pilot'],
                 'module' => 'fdm'],
                ['label' => 'My Flights', 'href' => '/my-flights', 'icon' => '🛫',
                 'match' => '/my-flights',
                 'roles' => ['pilot','cabin_crew','engineer']],
                ['label' => 'My Training', 'href' => '/my-training', 'icon' => '🎓',
                 'match' => '/my-training', 'module' => 'training'],
                ['label' => 'My Per Diem', 'href' => '/my-per-diem', 'icon' => '💼',
                 'match' => '/my-per-diem'],
                ['label' => 'Appraisals', 'href' => '/appraisals', 'icon' => '📝',
                 'match' => '/appraisals'],
                ['label' => 'Help & Guides', 'href' => '/help', 'icon' => '❓',
                 'match' => '/help'],
            ],
        ],

        // ── SCHEDULING ─────────────────────────────────────────
        [
            'title'   => 'Scheduling',
            'airline' => true,
            'roles'   => ['airline_admin','scheduler','chief_pilot','head_cabin_crew',
                          'base_manager','pilot','cabin_crew','engineer'],
            'items'   => [
                // Workbench (admin-ish)
                ['label' => 'Flights', 'href' => '/flights', 'icon' => '✈️',
                 'match' => '/flights',
                 'roles' => ['airline_admin','scheduler','chief_pilot','head_cabin_crew','base_manager']],
                ['label' => 'Roster Workbench', 'href' => '/roster', 'icon' => '🗓',
                 'match' => '/roster', 'match_exact' => true,
                 'roles' => ['airline_admin','scheduler','chief_pilot','head_cabin_crew','base_manager'],
                 'module' => 'rostering'],
                ['label' => 'Roster Periods', 'href' => '/roster/periods', 'icon' => '📅',
                 'match' => '/roster/periods',
                 'roles' => ['airline_admin','scheduler','chief_pilot','head_cabin_crew','base_manager'],
                 'module' => 'rostering'],
                ['label' => 'Revisions', 'href' => '/roster/revisions', 'icon' => '✏️',
                 'match' => '/roster/revisions', 'badge' => 'roster_draft_revisions',
                 'roles' => ['airline_admin','scheduler','chief_pilot','head_cabin_crew','base_manager'],
                 'module' => 'rostering'],
                ['label' => 'Reserve / Standby', 'href' => '/roster/standby', 'icon' => '🛡',
                 'match' => '/roster/standby',
                 'roles' => ['airline_admin','scheduler','chief_pilot','head_cabin_crew','base_manager'],
                 'module' => 'standby_pool'],
                ['label' => 'Coverage & Conflicts', 'href' => '/roster/coverage', 'icon' => '📊',
                 'match' => '/roster/coverage',
                 'roles' => ['airline_admin','scheduler','chief_pilot','head_cabin_crew','base_manager'],
                 'module' => 'rostering'],
                ['label' => 'Change Requests', 'href' => '/roster/changes', 'icon' => '💬',
                 'match' => '/roster/changes', 'badge' => 'roster_pending_changes',
                 'roles' => ['airline_admin','scheduler','chief_pilot','head_cabin_crew','base_manager'],
                 'module' => 'rostering'],
                // Crew-side
                ['label' => 'My Roster', 'href' => '/my-roster', 'icon' => '📋',
                 'match' => '/my-roster',
                 'roles' => ['pilot','cabin_crew','engineer','chief_pilot','head_cabin_crew','base_manager']],
            ],
        ],

        // ── DUTY REPORTING ────────────────────────────────────
        [
            'title'   => 'Duty Reporting',
            'airline' => true,
            'module'  => 'duty_reporting',
            'when'    => 'sidebar_show_duty_group',
            'items'   => [
                ['label' => 'My Duty', 'href' => '/my-duty', 'icon' => '✈️',
                 'match' => '/my-duty', 'when' => 'sidebar_duty_crew_allowed'],
                ['label' => 'On Duty Now', 'href' => '/duty-reporting', 'icon' => '🟢',
                 'match' => '/duty-reporting', 'match_exact' => true,
                 'match_prefixes' => ['/duty-reporting/report','/duty-reporting/history'],
                 'roles' => ['airline_admin','hr','chief_pilot','head_cabin_crew',
                             'engineering_manager','base_manager','scheduler']],
                ['label' => 'Duty Exceptions', 'href' => '/duty-reporting/exceptions', 'icon' => '⚠️',
                 'match' => '/duty-reporting/exceptions', 'badge' => 'duty_exceptions_pending',
                 'roles' => ['airline_admin','hr','chief_pilot','head_cabin_crew',
                             'engineering_manager','base_manager','scheduler']],
                ['label' => 'Settings', 'href' => '/duty-reporting/settings', 'icon' => '⚙️',
                 'match' => '/duty-reporting/settings',
                 'roles' => ['airline_admin']],
            ],
        ],

        // ── CONTENT: Documents & Notices (org-wide library) ────
        [
            'title'   => 'Content',
            'airline' => true,
            'roles'   => ['airline_admin','hr','document_control','safety_officer','chief_pilot',
                          'head_cabin_crew','engineering_manager','base_manager','training_admin','fdm_analyst'],
            'items'   => [
                ['label' => 'Documents Library', 'href' => '/files', 'icon' => '📄',
                 'match' => '/files',
                 'module' => 'manuals',
                 'roles' => ['airline_admin','hr','document_control','safety_officer','chief_pilot',
                             'head_cabin_crew','engineering_manager','base_manager','training_admin','fdm_analyst']],
                ['label' => 'Notices', 'href' => '/notices', 'icon' => '📢',
                 'match' => '/notices',
                 'module' => 'notices',
                 'roles' => ['airline_admin','safety_officer','document_control','chief_pilot',
                             'head_cabin_crew','engineering_manager','hr','training_admin']],
            ],
        ],

        // ── SAFETY & COMPLIANCE ────────────────────────────────
        [
            'title'   => 'Safety',
            'airline' => true,
            'roles'   => ['airline_admin','safety_officer','fdm_analyst','chief_pilot','hr'],
            'items'   => [
                ['label' => 'Safety Dashboard', 'href' => '/safety/dashboard', 'icon' => '📊',
                 'match' => '/safety/dashboard',
                 'module' => 'safety_reports',
                 'roles' => ['airline_admin','safety_officer']],
                ['label' => 'Reports Queue', 'href' => '/safety/queue', 'icon' => '📋',
                 'match' => '/safety/queue', 'match_prefixes' => ['/safety/team/report/'],
                 'module' => 'safety_reports',
                 'roles' => ['airline_admin','safety_officer']],
                ['label' => 'Corrective Actions', 'href' => '/safety/team/actions', 'icon' => '⚙️',
                 'match' => '/safety/team/actions',
                 'module' => 'safety_reports',
                 'roles' => ['airline_admin','safety_officer']],
                ['label' => 'Publications', 'href' => '/safety/publications', 'icon' => '📢',
                 'match' => '/safety/publication',
                 'module' => 'safety_reports',
                 'roles' => ['airline_admin','safety_officer']],
                ['label' => 'Submit a Report', 'href' => '/safety/select-type', 'icon' => '✏️',
                 'match' => '/safety/select-type',
                 'match_prefixes' => ['/safety/report/new','/safety/quick-report'],
                 'module' => 'safety_reports',
                 'roles' => ['airline_admin','safety_officer']],
                ['label' => 'Safety Settings', 'href' => '/safety/settings', 'icon' => '🔧',
                 'match' => '/safety/settings',
                 'roles' => ['airline_admin','safety_officer']],
                ['label' => 'FDM Data', 'href' => '/fdm', 'icon' => '📈',
                 'match' => '/fdm', 'match_exact' => true,
                 'module' => 'fdm',
                 'roles' => ['airline_admin','safety_officer','fdm_analyst']],
                ['label' => 'Compliance', 'href' => '/compliance', 'icon' => '✅',
                 'match' => '/compliance',
                 'module' => 'compliance'],
            ],
        ],

        // ── SECURITY (airline) ─────────────────────────────────
        [
            'title'   => 'Security',
            'airline' => true,
            'roles'   => ['airline_admin','safety_officer'],
            'items'   => [
                ['label' => 'Audit Log', 'href' => '/audit-log', 'icon' => '🔒',
                 'match' => '/audit-log',
                 'roles' => ['airline_admin','safety_officer']],
            ],
        ],

        // ── FLEET ──────────────────────────────────────────────
        [
            'title'   => 'Fleet',
            'airline' => true,
            'roles'   => ['airline_admin','engineering_manager','chief_pilot','base_manager'],
            'items'   => [
                ['label' => 'Aircraft Registry', 'href' => '/aircraft', 'icon' => '✈️',
                 'match' => '/aircraft', 'badge' => 'aog_count'],
            ],
        ],

        // ── PEOPLE OPS ─────────────────────────────────────────
        [
            'title'   => 'People Ops',
            'airline' => true,
            'roles'   => ['airline_admin','hr','training_admin','chief_pilot','head_cabin_crew'],
            'items'   => [
                ['label' => 'HR Workflow', 'href' => '/hr', 'icon' => '🧑‍💼',
                 'match' => '/hr', 'match_exact' => true,
                 'roles' => ['airline_admin','hr']],
                ['label' => 'Per Diem Claims', 'href' => '/per-diem/claims', 'icon' => '💱',
                 'match' => '/per-diem', 'badge' => 'per_diem_submitted',
                 'roles' => ['airline_admin','hr']],
                ['label' => 'Per Diem Rates', 'href' => '/per-diem/rates', 'icon' => '🧮',
                 'match' => '/per-diem/rates', 'match_exact' => true,
                 'roles' => ['airline_admin','hr']],
                ['label' => 'Training Dashboard', 'href' => '/training', 'icon' => '🎓',
                 'match' => '/training', 'match_exact' => true,
                 'module' => 'training',
                 'roles' => ['airline_admin','hr','training_admin','chief_pilot','head_cabin_crew']],
                ['label' => 'Logbook Overview', 'href' => '/logbook', 'icon' => '📒',
                 'match' => '/logbook', 'match_exact' => true,
                 'roles' => ['airline_admin','chief_pilot','hr','training_admin']],
                ['label' => 'Appraisals Review', 'href' => '/appraisals', 'icon' => '📝',
                 'match' => '/appraisals', 'match_exact' => true,
                 'roles' => ['airline_admin','hr','chief_pilot','head_cabin_crew']],
            ],
        ],

        // ── ADMINISTRATION ─────────────────────────────────────
        [
            'title'   => 'Administration',
            'airline' => true,
            'roles'   => ['airline_admin','hr','base_manager','chief_pilot','engineering_manager'],
            'items'   => [
                ['label' => 'Departments', 'href' => '/departments', 'icon' => '🏢',
                 'match' => '/departments',
                 'roles' => ['airline_admin','hr']],
                ['label' => 'Bases', 'href' => '/bases', 'icon' => '📍',
                 'match' => '/bases',
                 'roles' => ['airline_admin','base_manager']],
                ['label' => 'Fleets', 'href' => '/fleets', 'icon' => '✈',
                 'match' => '/fleets',
                 'roles' => ['airline_admin','chief_pilot','engineering_manager']],
                ['label' => 'Roles & Permissions', 'href' => '/roles', 'icon' => '🛡',
                 'match' => '/roles',
                 'roles' => ['airline_admin']],
                ['label' => 'Airline Profile', 'href' => '/airline/profile', 'icon' => '⚙️',
                 'match' => '/airline',
                 'roles' => ['airline_admin']],
                ['label' => 'Integrations', 'href' => '/integrations', 'icon' => '🔌',
                 'match' => '/integrations',
                 'roles' => ['airline_admin']],
            ],
        ],

        // ── App Install intentionally REMOVED.
        // Mobile distribution is handled externally (email / invite).

    ],

];
