<?php
/**
 * RoleRequiredDocumentModel — role-based required document catalogue.
 *
 * Defines which document types each role must hold to be assignment-eligible.
 * Rows with tenant_id = NULL are system defaults applicable to all tenants;
 * tenants may override or add entries with their own tenant_id.
 *
 * Tenant-specific rows take precedence over system defaults for the same
 * (role_slug, doc_type) combination.
 */
class RoleRequiredDocumentModel {

    /** Requirements for a single role (tenant-specific overrides system defaults). */
    public static function forRole(string $roleSlug, ?int $tenantId): array {
        // Return tenant-specific rows if any exist for this (tenant, role);
        // otherwise fall back to system defaults.
        $tenantRows = $tenantId
            ? Database::fetchAll(
                "SELECT * FROM role_required_documents
                 WHERE tenant_id = ? AND role_slug = ? AND is_active = 1
                 ORDER BY doc_label",
                [$tenantId, $roleSlug]
            )
            : [];

        if (!empty($tenantRows)) return $tenantRows;

        return Database::fetchAll(
            "SELECT * FROM role_required_documents
             WHERE tenant_id IS NULL AND role_slug = ? AND is_active = 1
             ORDER BY doc_label",
            [$roleSlug]
        );
    }

    /** Combined required doc types across an array of role slugs. */
    public static function forRoles(array $roleSlugs, ?int $tenantId): array {
        if (empty($roleSlugs)) return [];
        $map = [];
        foreach ($roleSlugs as $slug) {
            foreach (self::forRole($slug, $tenantId) as $row) {
                // De-dup by doc_type — keep the strictest (mandatory > optional, shortest warning)
                $key = $row['doc_type'];
                if (!isset($map[$key])) {
                    $map[$key] = $row;
                } else {
                    if ((int)$row['is_mandatory'] > (int)$map[$key]['is_mandatory']) {
                        $map[$key] = $row;
                    }
                }
            }
        }
        return array_values($map);
    }

    /** All active requirements for a tenant (including system defaults). */
    public static function allForTenant(?int $tenantId): array {
        if ($tenantId) {
            // Merge tenant + system defaults
            return Database::fetchAll(
                "SELECT * FROM role_required_documents
                 WHERE (tenant_id = ? OR tenant_id IS NULL) AND is_active = 1
                 ORDER BY role_slug, doc_label",
                [$tenantId]
            );
        }
        return Database::fetchAll(
            "SELECT * FROM role_required_documents WHERE tenant_id IS NULL AND is_active = 1
             ORDER BY role_slug, doc_label"
        );
    }

    public static function find(int $id): ?array {
        return Database::fetch("SELECT * FROM role_required_documents WHERE id = ?", [$id]);
    }

    public static function create(array $data): int {
        return Database::insert(
            "INSERT INTO role_required_documents
             (tenant_id, role_slug, doc_type, doc_label, is_mandatory, warning_days, critical_days, description, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['tenant_id'] ?? null,
                $data['role_slug'],
                $data['doc_type'],
                $data['doc_label'],
                !empty($data['is_mandatory']) ? 1 : 0,
                (int) ($data['warning_days']  ?? 60),
                (int) ($data['critical_days'] ?? 14),
                $data['description'] ?? null,
                isset($data['is_active']) ? (int) (bool) $data['is_active'] : 1,
            ]
        );
    }

    public static function delete(int $id, ?int $tenantId): void {
        // Can only delete a tenant's own overrides, not system defaults.
        $sql = "DELETE FROM role_required_documents WHERE id = ?";
        $params = [$id];
        if ($tenantId) {
            $sql .= " AND tenant_id = ?";
            $params[] = $tenantId;
        } else {
            $sql .= " AND tenant_id IS NULL";
        }
        Database::execute($sql, $params);
    }
}
