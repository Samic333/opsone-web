-- =====================================================
-- Migration 010 — Make users.tenant_id nullable
--
-- Required for Phase 0.5 platform user support.
-- Platform staff accounts (super_admin, platform_support,
-- platform_security, system_monitoring) have no airline
-- affiliation and must exist with tenant_id = NULL.
--
-- Safe to run multiple times — only modifies the column
-- if it is still NOT NULL.
--
-- MySQL 8.0+ compatible.
-- Namecheap: run via phpMyAdmin SQL tab or SSH + mysql cli.
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── Make users.tenant_id nullable ──────────────────────────
-- Preserves the FK to tenants(id) — MySQL allows nullable FK columns.
-- Existing airline users keep their tenant_id; NULLs = platform staff.
DROP PROCEDURE IF EXISTS _migration_010_nullable_tenant;
DELIMITER //
CREATE PROCEDURE _migration_010_nullable_tenant()
BEGIN
    DECLARE col_nullable VARCHAR(3);

    SELECT IS_NULLABLE
      INTO col_nullable
      FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'users'
       AND COLUMN_NAME  = 'tenant_id'
     LIMIT 1;

    IF col_nullable = 'NO' THEN
        -- Drop and re-add FK so MySQL can modify the column cleanly
        -- (Some MySQL versions require this when changing nullability on FK columns)
        ALTER TABLE `users`
            DROP FOREIGN KEY IF EXISTS `users_ibfk_1`;

        ALTER TABLE `users`
            MODIFY COLUMN `tenant_id` INT UNSIGNED NULL DEFAULT NULL;

        -- Re-add FK with ON DELETE SET NULL so platform users aren't affected
        -- if a tenant is ever deleted (and to maintain referential integrity)
        ALTER TABLE `users`
            ADD CONSTRAINT `fk_users_tenant`
            FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`)
            ON DELETE SET NULL;
    END IF;
END//
DELIMITER ;
CALL _migration_010_nullable_tenant();
DROP PROCEDURE IF EXISTS _migration_010_nullable_tenant;

-- ── Also ensure audit_logs.tenant_id FK is ON DELETE SET NULL ──
-- (Already correct in the original schema, but verify)
-- audit_logs.tenant_id is already nullable with ON DELETE SET NULL
-- in migration 001 — no change needed there.

SET FOREIGN_KEY_CHECKS = 1;
