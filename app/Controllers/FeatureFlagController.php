<?php
/**
 * FeatureFlagController — Platform-level feature flag management
 *
 * Phase 10: controlled rollout infrastructure.
 * Super admin can view all flags and toggle them globally or per-tenant.
 *
 * Accessible by: super_admin only
 */
class FeatureFlagController {

    public function index(): void {
        RbacMiddleware::requireRole(['super_admin']);

        $flags = Database::fetchAll(
            "SELECT ff.*,
                    COUNT(tff.id) AS tenant_count,
                    SUM(CASE WHEN tff.enabled = 1 THEN 1 ELSE 0 END) AS enabled_tenant_count
             FROM feature_flags ff
             LEFT JOIN tenant_feature_flags tff ON tff.flag_id = ff.id
             GROUP BY ff.id
             ORDER BY ff.category, ff.name"
        );

        $categories = array_unique(array_column($flags, 'category'));
        sort($categories);

        $pageTitle    = 'Feature Flags';
        $pageSubtitle = 'Controlled rollout of experimental and beta features';

        ob_start();
        require VIEWS_PATH . '/platform/feature_flags.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function toggle(int $id): void {
        RbacMiddleware::requireRole(['super_admin']);

        if (!verifyCsrf()) {
            flash('error', 'Invalid security token.');
            redirect('/platform/feature-flags');
        }

        $flag = Database::fetch("SELECT * FROM feature_flags WHERE id = ?", [$id]);
        if (!$flag) {
            flash('error', 'Feature flag not found.');
            redirect('/platform/feature-flags');
        }

        $action  = trim($_POST['action'] ?? '');
        $tenantId = (int) ($_POST['tenant_id'] ?? 0);

        if ($action === 'global_toggle') {
            // Toggle is_global
            $newVal = $flag['is_global'] ? 0 : 1;
            Database::execute(
                "UPDATE feature_flags SET is_global = ?, updated_at = NOW() WHERE id = ?",
                [$newVal, $id]
            );
            AuditLog::log(
                'feature_flag.' . ($newVal ? 'enabled_global' : 'disabled_global'),
                'feature_flags', $id,
                "Flag '{$flag['code']}' global=" . ($newVal ? 'on' : 'off')
            );
            flash('success', "Flag '{$flag['name']}' global setting updated.");

        } elseif ($action === 'tenant_toggle' && $tenantId > 0) {
            // Toggle for a specific tenant
            $existing = Database::fetch(
                "SELECT * FROM tenant_feature_flags WHERE flag_id = ? AND tenant_id = ?",
                [$id, $tenantId]
            );
            if ($existing) {
                $newVal = $existing['enabled'] ? 0 : 1;
                Database::execute(
                    "UPDATE tenant_feature_flags SET enabled = ?, enabled_at = " . ($newVal ? 'NOW()' : 'NULL') . " WHERE id = ?",
                    [$newVal, $existing['id']]
                );
            } else {
                Database::execute(
                    "INSERT INTO tenant_feature_flags (tenant_id, flag_id, enabled, enabled_at, enabled_by)
                     VALUES (?, ?, 1, NOW(), ?)",
                    [$tenantId, $id, currentUser()['id']]
                );
                $newVal = 1;
            }
            AuditLog::log(
                'feature_flag.' . ($newVal ? 'tenant_enabled' : 'tenant_disabled'),
                'feature_flags', $id,
                "Flag '{$flag['code']}' tenant_id={$tenantId} set to " . ($newVal ? 'on' : 'off')
            );
            flash('success', "Flag '{$flag['name']}' updated for airline #{$tenantId}.");
        }

        redirect('/platform/feature-flags');
    }
}
