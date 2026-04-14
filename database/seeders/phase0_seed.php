<?php
/**
 * Phase Zero Seeder
 *
 * Seeds:
 *   1. Updated system role_type flags (platform / tenant / end_user)
 *   2. Platform-specific roles (platform_super_admin display names, etc.)
 *   3. Module catalog with capabilities
 *   4. Role-capability templates
 *   5. Default module enablement for the demo tenant (id=1)
 *
 * Run: php database/seeders/phase0_seed.php
 * Safe to re-run (INSERT IGNORE throughout).
 */

require dirname(__DIR__, 2) . '/config/app.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require dirname(__DIR__, 2) . '/app/Helpers/functions.php';
require dirname(__DIR__, 2) . '/config/database.php';

echo "🌱 Phase Zero Seeder\n\n";

try {
    $db = Database::getInstance();

    // ─── 1. Ensure role_type column exists & update flags ─────────────────────
    echo "Updating role types... ";
    $db->exec("
        UPDATE roles SET role_type = 'platform'
        WHERE slug IN ('super_admin','platform_support','platform_security','system_monitoring')
          AND tenant_id IS NULL
    ");
    $db->exec("
        UPDATE roles SET role_type = 'end_user'
        WHERE slug IN ('pilot','cabin_crew','engineer')
          AND tenant_id IS NULL
    ");
    // Everything else remains 'tenant' (default)
    echo "✓\n";

    // ─── 2. Ensure all platform roles exist at system level ───────────────────
    echo "Ensuring platform roles exist... ";
    $platformRoles = [
        ['Platform Super Admin',    'super_admin',         'Full platform and all airline access',               'platform'],
        ['Platform Security Admin', 'platform_security',   'Platform security monitoring and audit access',      'platform'],
        ['Platform Support Admin',  'platform_support',    'Read-only platform support access',                  'platform'],
        ['System Monitoring',       'system_monitoring',   'System health and sync monitoring',                  'platform'],
    ];
    $stmt = $db->prepare(
        "INSERT IGNORE INTO roles (tenant_id, name, slug, description, is_system, role_type)
         VALUES (NULL, ?, ?, ?, 1, ?)"
    );
    foreach ($platformRoles as $r) {
        $stmt->execute($r);
    }
    echo "✓\n";

    // ─── 3. Module catalog ────────────────────────────────────────────────────
    echo "Seeding module catalog... ";

    $modules = [
        ['crew_profiles',       'Crew Profiles',           'Crew personal records, passport, and employment data',        '👤', 0, 10],
        ['licensing',           'Licensing',               'Pilot and crew licence and rating management',                 '📋', 1, 20],
        ['rostering',           'Rostering',               'Monthly duty assignment and crew scheduling',                  '📅', 1, 30],
        ['standby_pool',        'Standby Pool',            'Standby crew tracking and replacement suggestions',            '📋', 1, 35],
        ['manuals',             'Manuals & Documents',     'Document library with version control and acknowledgements',   '📄', 1, 40],
        ['notices',             'Notices',                 'Crew notices with targeting and acknowledgement tracking',     '📢', 1, 50],
        ['safety_reports',      'Safety Reports',          'Safety occurrence reporting and review workflow',              '⚠',  0, 60],
        ['fdm',                 'Flight Data Monitoring',  'FDM upload, event tagging, and analysis',                     '📊', 0, 70],
        ['compliance',          'Compliance Dashboard',    'Licence, medical, and passport expiry tracking',               '✅', 1, 80],
        ['training',            'Training Management',     'Training course assignments and records',                     '🎓', 0, 90],
        ['mobile_ipad_access',  'Mobile / iPad Access',   'iPad app access entitlement and sync control',                 '📱', 1, 100],
        ['sync_control',        'Sync Control',            'Manual sync trigger and last-sync visibility',                 '🔄', 1, 110],
        ['document_control',    'Document Control',        'Approval workflow for new documents and revisions',            '📁', 0, 120],
        ['flight_briefing',     'Flight Briefing',         'Pre-flight briefing package distribution (planned)',          '✈',  1, 200],
        ['future_jeppesen',     'Jeppesen Integration',    'Charts and navigation data integration (planned)',             '🗺',  1, 300],
        ['future_performance',  'Performance Tools',       'Take-off / landing performance calculation (planned)',         '📐', 0, 400],
    ];

    $modStmt = $db->prepare(
        "INSERT IGNORE INTO modules (code, name, description, icon, mobile_capable, sort_order, platform_status)
         VALUES (?, ?, ?, ?, ?, ?, 'available')"
    );
    foreach ($modules as $m) {
        $modStmt->execute($m);
    }
    echo "✓\n";

    // ─── 4. Module capabilities ───────────────────────────────────────────────
    echo "Seeding module capabilities... ";

    // Generic capabilities most modules support
    $genericCaps = ['view','create','edit','delete','export'];
    $advancedCaps = ['approve','publish','request_change','manage_settings','view_audit'];
    $specialCaps = [
        'rostering'       => ['view','edit','publish','assign','export','view_audit'],
        'standby_pool'    => ['view','assign','export'],
        'manuals'         => ['view','upload','publish','delete','acknowledge','export'],
        'notices'         => ['view','create','edit','delete','publish','acknowledge','export'],
        'safety_reports'  => ['view','create','edit','submit','review','approve','export','view_audit'],
        'fdm'             => ['view','upload','create','edit','delete','export'],
        'compliance'      => ['view','export','view_audit'],
        'training'        => ['view','create','edit','delete','assign','approve','export'],
        'mobile_ipad_access' => ['view','manage_settings','sync_now'],
        'sync_control'    => ['view','sync_now'],
        'document_control'=> ['view','upload','approve','publish','delete','request_change','export','view_audit'],
        'licensing'       => ['view','create','edit','delete','export','view_audit'],
        'crew_profiles'   => ['view','create','edit','delete','export','view_audit'],
        'flight_briefing' => ['view','upload','publish','export'],
        'future_jeppesen' => ['view'],
        'future_performance' => ['view'],
    ];

    $capStmt = $db->prepare(
        "INSERT IGNORE INTO module_capabilities (module_id, capability)
         SELECT id, ? FROM modules WHERE code = ?"
    );
    foreach ($specialCaps as $code => $caps) {
        foreach ($caps as $cap) {
            $capStmt->execute([$cap, $code]);
        }
    }
    echo "✓\n";

    // ─── 5. Role-capability templates ────────────────────────────────────────
    echo "Seeding role capability templates... ";

    // Map: role_slug => [module_code => [capabilities]]
    $templates = [
        'pilot' => [
            'crew_profiles'   => ['view'],
            'licensing'       => ['view'],
            'rostering'       => ['view'],
            'manuals'         => ['view','acknowledge'],
            'notices'         => ['view','acknowledge'],
            'compliance'      => ['view'],
            'mobile_ipad_access' => ['view'],
            'sync_control'    => ['view','sync_now'],
        ],
        'cabin_crew' => [
            'crew_profiles'   => ['view'],
            'licensing'       => ['view'],
            'rostering'       => ['view'],
            'manuals'         => ['view','acknowledge'],
            'notices'         => ['view','acknowledge'],
            'compliance'      => ['view'],
            'mobile_ipad_access' => ['view'],
            'sync_control'    => ['view','sync_now'],
        ],
        'engineer' => [
            'crew_profiles'   => ['view'],
            'licensing'       => ['view'],
            'rostering'       => ['view'],
            'manuals'         => ['view','acknowledge'],
            'notices'         => ['view','acknowledge'],
            'compliance'      => ['view'],
            'mobile_ipad_access' => ['view'],
            'sync_control'    => ['view','sync_now'],
        ],
        'scheduler' => [
            'rostering'       => ['view','edit','publish','assign','export'],
            'standby_pool'    => ['view','assign','export'],
            'crew_profiles'   => ['view'],
            'compliance'      => ['view','export'],
            'notices'         => ['view'],
            'mobile_ipad_access' => ['view'],
        ],
        'chief_pilot' => [
            'crew_profiles'   => ['view','edit','view_audit'],
            'licensing'       => ['view','edit','view_audit'],
            'rostering'       => ['view','edit','publish','assign','export','view_audit'],
            'standby_pool'    => ['view','assign','export'],
            'manuals'         => ['view','upload','publish','delete','export'],
            'notices'         => ['view','create','edit','publish'],
            'compliance'      => ['view','export','view_audit'],
            'fdm'             => ['view','export'],
            'mobile_ipad_access' => ['view','manage_settings'],
        ],
        'head_cabin_crew' => [
            'crew_profiles'   => ['view','edit'],
            'licensing'       => ['view','edit'],
            'rostering'       => ['view','edit','publish','assign','export'],
            'standby_pool'    => ['view','assign'],
            'manuals'         => ['view','upload','publish'],
            'notices'         => ['view','create','edit','publish'],
            'compliance'      => ['view','export'],
            'mobile_ipad_access' => ['view','manage_settings'],
        ],
        'engineering_manager' => [
            'crew_profiles'   => ['view'],
            'licensing'       => ['view','edit'],
            'rostering'       => ['view','export'],
            'manuals'         => ['view','upload','publish','export'],
            'notices'         => ['view','create','edit','publish'],
            'compliance'      => ['view','export'],
        ],
        'safety_officer' => [
            'safety_reports'  => ['view','create','edit','submit','review','approve','export','view_audit'],
            'fdm'             => ['view','upload','create','edit','export'],
            'compliance'      => ['view','export','view_audit'],
            'crew_profiles'   => ['view','view_audit'],
            'licensing'       => ['view','view_audit'],
            'notices'         => ['view','create','edit','publish'],
            'manuals'         => ['view','upload','publish'],
        ],
        'fdm_analyst' => [
            'fdm'             => ['view','upload','create','edit','delete','export'],
            'compliance'      => ['view'],
            'safety_reports'  => ['view','export'],
        ],
        'document_control' => [
            'document_control'=> ['view','upload','approve','publish','delete','request_change','export','view_audit'],
            'manuals'         => ['view','upload','publish','delete','export'],
            'notices'         => ['view','create','edit','publish','delete'],
        ],
        'hr' => [
            'crew_profiles'   => ['view','create','edit','delete','export','view_audit'],
            'licensing'       => ['view','create','edit','delete','export','view_audit'],
            'compliance'      => ['view','export','view_audit'],
            'notices'         => ['view','create','edit','publish'],
            'training'        => ['view','create','edit','assign','export'],
            'mobile_ipad_access' => ['view','manage_settings'],
        ],
        'training_admin' => [
            'training'        => ['view','create','edit','delete','assign','approve','export'],
            'crew_profiles'   => ['view'],
            'licensing'       => ['view'],
            'notices'         => ['view','create'],
            'manuals'         => ['view'],
        ],
        'base_manager' => [
            'crew_profiles'   => ['view'],
            'rostering'       => ['view','export'],
            'standby_pool'    => ['view'],
            'notices'         => ['view','create'],
            'compliance'      => ['view'],
        ],
        'airline_admin' => [
            'crew_profiles'   => ['view','create','edit','delete','export','view_audit'],
            'licensing'       => ['view','create','edit','delete','export','view_audit'],
            'rostering'       => ['view','edit','publish','assign','export','view_audit'],
            'standby_pool'    => ['view','assign','export'],
            'manuals'         => ['view','upload','publish','delete','export'],
            'notices'         => ['view','create','edit','delete','publish','export'],
            'safety_reports'  => ['view','create','review','approve','export','view_audit'],
            'fdm'             => ['view','upload','create','edit','delete','export'],
            'compliance'      => ['view','export','view_audit'],
            'training'        => ['view','create','edit','delete','assign','approve','export'],
            'mobile_ipad_access' => ['view','manage_settings','sync_now'],
            'sync_control'    => ['view','sync_now'],
            'document_control'=> ['view','upload','approve','publish','delete','request_change','export','view_audit'],
        ],
    ];

    $rctStmt = $db->prepare(
        "INSERT IGNORE INTO role_capability_templates (role_slug, module_capability_id)
         SELECT ?, mc.id
         FROM module_capabilities mc
         JOIN modules m ON m.id = mc.module_id
         WHERE m.code = ? AND mc.capability = ?"
    );
    foreach ($templates as $roleSlug => $moduleCaps) {
        foreach ($moduleCaps as $moduleCode => $caps) {
            foreach ($caps as $cap) {
                $rctStmt->execute([$roleSlug, $moduleCode, $cap]);
            }
        }
    }
    echo "✓\n";

    // ─── 6. Enable default modules for demo tenant (id=1) ────────────────────
    echo "Enabling default modules for demo tenant (id=1)... ";
    $defaultModules = [
        'crew_profiles', 'licensing', 'rostering', 'standby_pool',
        'manuals', 'notices', 'fdm', 'compliance',
        'mobile_ipad_access', 'sync_control',
    ];
    foreach ($defaultModules as $code) {
        $db->prepare(
            "INSERT IGNORE INTO tenant_modules (tenant_id, module_id, is_enabled)
             SELECT 1, id, 1 FROM modules WHERE code = ?"
        )->execute([$code]);
    }
    echo "✓\n";

    // ─── 7. Initialize demo tenant settings & policy ─────────────────────────
    echo "Initializing demo tenant settings... ";
    $db->exec("INSERT IGNORE INTO tenant_settings (tenant_id) VALUES (1)");
    $db->exec("INSERT IGNORE INTO tenant_access_policies (tenant_id) VALUES (1)");
    echo "✓\n";

    // ─── 8. Update demo tenant with enhanced fields ───────────────────────────
    echo "Updating demo tenant metadata... ";
    $db->exec("
        UPDATE tenants SET
            legal_name       = 'Gulf Wings Aviation LLC',
            display_name     = 'Gulf Wings',
            icao_code        = 'GWA',
            iata_code        = 'GW',
            primary_country  = 'UAE',
            primary_base     = 'Dubai (DXB)',
            support_tier     = 'standard',
            onboarding_status = 'active',
            onboarded_at     = NOW()
        WHERE id = 1
    ");
    echo "✓\n";

    echo "\n✅ Phase Zero seeding complete!\n";
    echo "   Module catalog: " . count($modules) . " modules seeded\n";
    echo "   Role templates: " . count($templates) . " role types configured\n";
    echo "   Demo tenant (id=1) has " . count($defaultModules) . " modules enabled\n\n";
    echo "   Next: Run migration 009 on Namecheap, then check /tenants/1 in the portal.\n";

} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (\Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
