<?php
/**
 * AuditLogController — view audit log + login activity
 *
 * Accessible by: super_admin, airline_admin, safety_officer
 */
class AuditLogController {

    public function index(): void {
        RbacMiddleware::requireRole(['super_admin', 'platform_security', 'airline_admin', 'safety_officer']);

        // Prefer session user's tenant_id — more reliable than currentTenantId()
        // which returns null in single-tenant mode when FIXED_TENANT_ID is not set.
        $sessionUser  = currentUser();
        $tenantId     = $sessionUser['tenant_id'] ?? currentTenantId();
        $isPlatformSecurity = hasAnyRole(['super_admin', 'platform_security']);

        // Filters
        $filterAction     = trim($_GET['action']       ?? '');
        $filterUser       = trim($_GET['user']         ?? '');
        $filterEntity     = trim($_GET['entity']       ?? '');
        $filterDateFrom   = trim($_GET['date_from']    ?? '');
        $filterDateTo     = trim($_GET['date_to']      ?? '');
        $filterTenantId   = $isPlatformSecurity ? (int)($_GET['tenant_id']     ?? 0) : 0;
        $filterPlatform   = $isPlatformSecurity && !empty($_GET['platform_only']);
        $page             = max(1, (int) ($_GET['page'] ?? 1));
        $perPage          = 50;
        $offset           = ($page - 1) * $perPage;

        // Build query
        $where  = [];
        $params = [];

        if (!$isPlatformSecurity) {
            // Airline users see only their own tenant's logs
            $where[]  = 'al.tenant_id = ?';
            $params[] = $tenantId;
        } elseif ($filterTenantId > 0) {
            // Platform admin filtered to a specific airline
            $where[]  = 'al.tenant_id = ?';
            $params[] = $filterTenantId;
        } elseif ($filterPlatform) {
            // Platform admin: platform-level events only (actor was a platform staff role)
            $where[] = "al.actor_role IN ('super_admin','platform_support','platform_security','system_monitoring')";
        }

        if ($filterAction) {
            $where[]  = 'al.action LIKE ?';
            $params[] = '%' . $filterAction . '%';
        }
        if ($filterUser) {
            $where[]  = 'al.user_name LIKE ?';
            $params[] = '%' . $filterUser . '%';
        }
        if ($filterEntity) {
            $where[]  = 'al.entity_type = ?';
            $params[] = $filterEntity;
        }
        if ($filterDateFrom !== '') {
            $where[]  = 'al.created_at >= ?';
            $params[] = $filterDateFrom . ' 00:00:00';
        }
        if ($filterDateTo !== '') {
            $where[]  = 'al.created_at <= ?';
            $params[] = $filterDateTo . ' 23:59:59';
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $logs = Database::fetchAll(
            "SELECT al.*, t.name AS tenant_name
             FROM audit_logs al
             LEFT JOIN tenants t ON al.tenant_id = t.id
             $whereClause
             ORDER BY al.created_at DESC
             LIMIT ? OFFSET ?",
            [...$params, $perPage, $offset]
        );

        $totalRow = Database::fetch(
            "SELECT COUNT(*) AS c FROM audit_logs al $whereClause",
            $params
        );
        $totalLogs  = (int) ($totalRow['c'] ?? 0);
        $totalPages = max(1, (int) ceil($totalLogs / $perPage));

        // Distinct entity types for filter dropdown
        $entityTypes = Database::fetchAll(
            "SELECT DISTINCT entity_type FROM audit_logs WHERE entity_type IS NOT NULL ORDER BY entity_type"
        );

        // Tenant list for platform tenant filter dropdown
        $allTenants = $isPlatformSecurity ? Tenant::all() : [];

        $isSuperAdmin = $isPlatformSecurity; // alias expected by view

        $pageTitle    = 'Audit Log';
        $pageSubtitle = 'Security & action history';

        ob_start();
        require VIEWS_PATH . '/audit/index.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }

    public function exportCsv(): void {
        RbacMiddleware::requireRole(['super_admin', 'platform_security', 'airline_admin', 'safety_officer']);

        $sessionUser        = currentUser();
        $tenantId           = $sessionUser['tenant_id'] ?? currentTenantId();
        $isPlatformSecurity = hasAnyRole(['super_admin', 'platform_security']);

        $filterAction   = trim($_GET['action']    ?? '');
        $filterUser     = trim($_GET['user']      ?? '');
        $filterEntity   = trim($_GET['entity']    ?? '');
        $filterDateFrom = trim($_GET['date_from'] ?? '');
        $filterDateTo   = trim($_GET['date_to']   ?? '');
        $filterTenantId = $isPlatformSecurity ? (int)($_GET['tenant_id'] ?? 0) : 0;

        $where  = [];
        $params = [];

        if (!$isPlatformSecurity) {
            $where[]  = 'al.tenant_id = ?';
            $params[] = $tenantId;
        } elseif ($filterTenantId > 0) {
            $where[]  = 'al.tenant_id = ?';
            $params[] = $filterTenantId;
        }
        if ($filterAction)   { $where[] = 'al.action LIKE ?';     $params[] = '%' . $filterAction . '%'; }
        if ($filterUser)     { $where[] = 'al.user_name LIKE ?';  $params[] = '%' . $filterUser . '%'; }
        if ($filterEntity)   { $where[] = 'al.entity_type = ?';   $params[] = $filterEntity; }
        if ($filterDateFrom) { $where[] = 'al.created_at >= ?';   $params[] = $filterDateFrom . ' 00:00:00'; }
        if ($filterDateTo)   { $where[] = 'al.created_at <= ?';   $params[] = $filterDateTo . ' 23:59:59'; }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Export up to 10,000 rows (no pagination for CSV)
        $logs = Database::fetchAll(
            "SELECT al.*, t.name AS tenant_name
             FROM audit_logs al
             LEFT JOIN tenants t ON al.tenant_id = t.id
             $whereClause
             ORDER BY al.created_at DESC
             LIMIT 10000",
            $params
        );

        $filename = 'audit-log-' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Date/Time', 'Action', 'User', 'Role', 'Airline', 'Entity Type', 'Entity ID', 'Details', 'IP Address']);

        foreach ($logs as $row) {
            fputcsv($out, [
                $row['created_at'],
                $row['action'],
                $row['user_name'] ?? '',
                $row['actor_role'] ?? '',
                $row['tenant_name'] ?? '',
                $row['entity_type'] ?? '',
                $row['entity_id'] ?? '',
                $row['details'] ?? '',
                $row['ip_address'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }

    public function loginActivity(): void {
        RbacMiddleware::requireRole(['super_admin', 'platform_security', 'airline_admin', 'safety_officer']);

        $sessionUser  = currentUser();
        $tenantId     = $sessionUser['tenant_id'] ?? currentTenantId();
        $isPlatformSecurity = hasAnyRole(['super_admin', 'platform_security']);

        $filterEmail  = trim($_GET['email']  ?? '');
        $filterResult = trim($_GET['result'] ?? ''); // 'success' | 'fail' | ''
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 50;
        $offset  = ($page - 1) * $perPage;

        $where  = [];
        $params = [];

        if (!$isPlatformSecurity) {
            $where[]  = 'la.tenant_id = ?';
            $params[] = $tenantId;
        }
        if ($filterEmail) {
            $where[]  = 'la.email LIKE ?';
            $params[] = '%' . $filterEmail . '%';
        }
        if ($filterResult === 'success') {
            $where[] = 'la.success = 1';
        } elseif ($filterResult === 'fail') {
            $where[] = 'la.success = 0';
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $activity = Database::fetchAll(
            "SELECT la.*, t.name AS tenant_name, u.name AS user_name
             FROM login_activity la
             LEFT JOIN tenants t ON la.tenant_id = t.id
             LEFT JOIN users u ON la.user_id = u.id
             $whereClause
             ORDER BY la.created_at DESC
             LIMIT ? OFFSET ?",
            [...$params, $perPage, $offset]
        );

        $totalRow = Database::fetch(
            "SELECT COUNT(*) AS c FROM login_activity la $whereClause",
            $params
        );
        $totalPages = max(1, (int) ceil(($totalRow['c'] ?? 0) / $perPage));

        $pageTitle    = 'Login Activity';
        $pageSubtitle = 'Authentication history — web + API';

        ob_start();
        require VIEWS_PATH . '/audit/login.php';
        $content = ob_get_clean();
        require VIEWS_PATH . '/layouts/app.php';
    }
}
