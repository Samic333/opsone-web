-- Migration 043 (SQLite) — Hashed-at-rest API tokens.
-- Adds token_hash column. Lookups by ApiAuthMiddleware will use sha256(bearer)
-- against this column. The plaintext token column stays nullable for one
-- transition release; backfill populates token_hash for every existing row,
-- and the AuthApiController stops persisting the plaintext for new rows.
-- Phase 12 cleanup will drop the plaintext token column.

BEGIN TRANSACTION;

ALTER TABLE api_tokens ADD COLUMN token_hash TEXT;

CREATE UNIQUE INDEX IF NOT EXISTS uq_api_tokens_token_hash
    ON api_tokens(token_hash);

COMMIT;
