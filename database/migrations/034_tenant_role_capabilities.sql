-- ─────────────────────────────────────────────────────────────────────────────
-- Migration 034 — Per-tenant role capability overrides
--
-- Airline admins can now grant or revoke capabilities per role inside their
-- own tenant without mutating the global role_capability_templates dictionary.
-- When a row exists in tenant_role_capabilities for (tenant_id, role_slug,
-- module_capability_id), that row wins over the template default:
--   allowed=1 → grant this capability for this role in this tenant
--   allowed=0 → explicit revoke
-- If no row exists, the system-wide template still applies.
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `tenant_role_capabilities` (
    `id`                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`             INT UNSIGNED NOT NULL,
    `role_slug`             VARCHAR(80)  NOT NULL,
    `module_capability_id`  INT UNSIGNED NOT NULL,
    `allowed`               TINYINT(1)   NOT NULL DEFAULT 1,
    `updated_by`            INT UNSIGNED DEFAULT NULL,
    `updated_at`            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                                         ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_trc_tenant_role_cap` (`tenant_id`, `role_slug`, `module_capability_id`),
    KEY `idx_trc_tenant`      (`tenant_id`),
    KEY `idx_trc_tenant_role` (`tenant_id`, `role_slug`),
    FOREIGN KEY (`tenant_id`)            REFERENCES `tenants`(`id`)               ON DELETE CASCADE,
    FOREIGN KEY (`module_capability_id`) REFERENCES `module_capabilities`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
