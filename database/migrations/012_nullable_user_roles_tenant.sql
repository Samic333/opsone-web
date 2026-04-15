-- =====================================================
-- Migration 012 — Make user_roles.tenant_id nullable
--
-- Required for Phase 0.5+ platform user support.
-- Platform staff (super_admin, platform_support,
-- platform_security, system_monitoring) exist in the
-- users table with tenant_id = NULL.
-- Their user_roles records must also use tenant_id = NULL
-- because they have no airline affiliation.
--
-- Without this migration the INSERT IGNORE in the demo
-- seeder silently drops every platform user role
-- assignment, leaving those users with zero roles in
-- session → sidebar shows only "Platform Overview" and
-- role label shows "User".
--
-- Safe to run multiple times — guarded by INFORMATION_SCHEMA.
-- MySQL 8.0+ compatible.
-- Namecheap: run via phpMyAdmin SQL tab.
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP PROCEDURE IF EXISTS _migration_012_nullable_user_roles_tenant;
DELIMITER //
CREATE PROCEDURE _migration_012_nullable_user_roles_tenant()
BEGIN
    DECLARE col_nullable VARCHAR(3);

    SELECT IS_NULLABLE
      INTO col_nullable
      FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'user_roles'
       AND COLUMN_NAME  = 'tenant_id'
     LIMIT 1;

    IF col_nullable = 'NO' THEN
        -- Drop the NOT-NULL FK constraint first so MySQL allows the column change
        ALTER TABLE `user_roles`
            DROP FOREIGN KEY IF EXISTS `user_roles_ibfk_2`;

        ALTER TABLE `user_roles`
            MODIFY COLUMN `tenant_id` INT UNSIGNED NULL DEFAULT NULL;

        -- Re-add FK with ON DELETE SET NULL so platform user roles aren't
        -- orphaned if a tenant is ever deleted
        ALTER TABLE `user_roles`
            ADD CONSTRAINT `fk_user_roles_tenant`
            FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`)
            ON DELETE SET NULL;
    END IF;
END//
DELIMITER ;
CALL _migration_012_nullable_user_roles_tenant();
DROP PROCEDURE IF EXISTS _migration_012_nullable_user_roles_tenant;

SET FOREIGN_KEY_CHECKS = 1;
