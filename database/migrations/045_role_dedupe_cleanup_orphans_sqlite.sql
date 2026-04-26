-- Migration 045 (SQLite) — Clean orphan role-FK rows missed by migration 044.
--
-- Migration 044 deduped the roles table but `DELETE FROM roles` does not
-- cascade if FK enforcement is off in the connection (PHP's PDO does not
-- enable foreign_keys=ON by default for SQLite). Result: a few rows in
-- file_role_visibility / notice_role_visibility may point at deleted role
-- ids. This migration removes those orphans. Idempotent.

BEGIN TRANSACTION;

DELETE FROM file_role_visibility
 WHERE NOT EXISTS (SELECT 1 FROM roles r WHERE r.id = file_role_visibility.role_id);

DELETE FROM notice_role_visibility
 WHERE NOT EXISTS (SELECT 1 FROM roles r WHERE r.id = notice_role_visibility.role_id);

COMMIT;
