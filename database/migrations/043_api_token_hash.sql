-- Migration 043 (MySQL) — Hashed-at-rest API tokens.
-- Mirror of 043_api_token_hash_sqlite.sql. See that file for the rationale.
-- Idempotent via INFORMATION_SCHEMA stored-procedure pattern (works on MySQL 8).

DELIMITER $$
DROP PROCEDURE IF EXISTS apply_043 $$
CREATE PROCEDURE apply_043()
BEGIN
    IF NOT EXISTS (
        SELECT 1
          FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME   = 'api_tokens'
           AND COLUMN_NAME  = 'token_hash'
    ) THEN
        ALTER TABLE api_tokens
            ADD COLUMN token_hash CHAR(64) NULL AFTER token;
    END IF;

    IF NOT EXISTS (
        SELECT 1
          FROM INFORMATION_SCHEMA.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME   = 'api_tokens'
           AND INDEX_NAME   = 'uq_api_tokens_token_hash'
    ) THEN
        CREATE UNIQUE INDEX uq_api_tokens_token_hash ON api_tokens(token_hash);
    END IF;
END $$
DELIMITER ;

CALL apply_043();
DROP PROCEDURE apply_043;
