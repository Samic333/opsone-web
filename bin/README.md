# bin/

CLI utilities. **Never** put files here in `public/` — they expose internals
and (in the case of `seed-db.php`) can wipe and reseed the database from any
HTTP client. Move from Phase 2 of the 2026-04-26 remediation:

| File | Purpose | Run |
|------|---------|-----|
| `diag.php`       | Dump request/server/session diagnostics | `php bin/diag.php` |
| `diag_roles.php` | Role-resolution diagnostic for the current user | `php bin/diag_roles.php` |
| `seed-db.php`    | Wipe + reseed the dev SQLite DB (LOCAL ONLY) | `php bin/seed-db.php` |
| `audit_route_guards.php` | Scan `config/routes.php` for routes whose handler lacks a `requireRole/requirePlatformRole/requireAirlineRole/requireAuth` guard. | `php bin/audit_route_guards.php` |

These must never be served from the webroot. Production deploys (Namecheap
cPanel) point at `public/`, so anything in `bin/` is automatically out of reach.
