-- =====================================================
-- Migration 011 — Phase 1: Add 'in_review' onboarding status
--
-- Extends the tenant_onboarding_requests.status ENUM to include
-- an 'in_review' intermediate state, allowing platform admins to
-- mark a request as "under review" before approving or rejecting.
--
-- Current ENUM: ('pending','approved','rejected','provisioned')
-- New ENUM:     ('pending','in_review','approved','rejected','provisioned')
--
-- Safe to run multiple times — checks current column definition first.
-- MySQL 8.0+ / MariaDB compatible.
-- Namecheap: run via phpMyAdmin SQL tab or SSH + mysql cli.
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP PROCEDURE IF EXISTS _migration_011_onboarding_inreview;
DELIMITER //
CREATE PROCEDURE _migration_011_onboarding_inreview()
BEGIN
    DECLARE col_type TEXT;

    SELECT COLUMN_TYPE
      INTO col_type
      FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'tenant_onboarding_requests'
       AND COLUMN_NAME  = 'status'
     LIMIT 1;

    -- Only run if 'in_review' is not already in the ENUM definition
    IF col_type NOT LIKE '%in_review%' THEN
        ALTER TABLE `tenant_onboarding_requests`
            MODIFY COLUMN `status`
                ENUM('pending','in_review','approved','rejected','provisioned')
                NOT NULL DEFAULT 'pending';
    END IF;
END//
DELIMITER ;
CALL _migration_011_onboarding_inreview();
DROP PROCEDURE IF EXISTS _migration_011_onboarding_inreview;

SET FOREIGN_KEY_CHECKS = 1;
