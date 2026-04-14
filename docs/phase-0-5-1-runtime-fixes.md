# Phase 0.5.1 — Runtime Hotfix

**Date:** 2026-04-14  
**Status:** Complete  
**Scope:** Production-blocking bug fixes only. No new features.

---

## Root Causes Confirmed

### 1. `Tenant::find(): Argument #1 must be of type int, null given`

**File:** `app/Controllers/AuthController.php` (line 74 in Phase 0.5)  
**Trigger:** Platform login — platform user has `tenant_id = NULL` in database  
**Root cause:** Even though Phase 0.5 added a `$isPlatformOnlyUser` check, a user with zero roles (e.g., seeder not re-run on server, roles not migrated yet) falls through with `$isPlatformOnlyUser = false`. The else-branch then calls `Tenant::find($user['tenant_id'])` where `tenant_id = NULL`, which PHP 8's strict typing rejects.

**Fix:** Added explicit null-guard before `Tenant::find()`:
```php
if (empty($user['tenant_id'])) {
    flash('error', 'Your account has no airline association. Contact your administrator.');
    redirect('/login');
}
$tenant = Tenant::find((int) $user['tenant_id']);
```

### 2. `FK constraint fails — audit_logs.tenant_id → tenants.id`

**File:** `app/Controllers/AuthController.php` + `app/Models/AuditLog.php`  
**Trigger:** Any login (platform or airline) where `tenant_id` is NULL  
**Root cause:** `$user['tenant_id'] ?? 0` was passed to `AuditLog::logLogin()`. The fallback `0` is not a valid `tenants.id`, so the FK on `audit_logs.tenant_id` rejected it. Same issue in the failed-login path.  
`AuditLog::logLogin(int $userId, int $tenantId, ...)` also had strict `int` types, forcing any `null` passed from PHP through integer coercion to `0`.

**Fix:**
- `$user['tenant_id'] ?? null` in all `logLogin` call sites in `AuthController`
- `AuditLog::logLogin(?int $userId, ?int $tenantId, ...)` — changed to nullable
- `AuditService::logLogin(?int $userId, ?int $tenantId, ...)` — already nullable, confirmed correct

### 3. `SQLSTATE[23000]: Integrity constraint violation: 1048 Column 'tenant_id' cannot be null`

**File:** `database/migrations/001_create_schema.sql` — `users.tenant_id INT UNSIGNED NOT NULL`  
**Trigger:** Running `demo_seed.php` with platform users that have `tenant_id = NULL`  
**Root cause:** The original schema defined `users.tenant_id` as `NOT NULL`. Phase 0.5 introduced platform users with no airline affiliation (NULL tenant), but no migration was added to make the column nullable.  
**Namecheap workaround:** The column was manually altered on the live server. This hotfix adds the proper migration so all environments are consistent.

**Fix:** Added `database/migrations/010_nullable_users_tenant.sql`

### 4. `Unknown column 'u.first_name' in SELECT`

**Status:** NOT present in current codebase.  
**Confirmed:** Exhaustive `grep` of all PHP files and views found zero occurrences of `first_name` or `last_name`. The users table has only `name`. These errors in `public/error_log` are from pre-Phase Zero code that was on the server before the Phase 0/0.5 code was deployed. They will be resolved by `git pull` + deploying the current code.

### 5. Dashboard `int $tenantId` strict type errors (defensive fix)

**File:** `app/Controllers/DashboardController.php`  
**Risk:** If a platform user somehow reached an airline dashboard method (e.g., due to a misconfigured session before Phase 0.5.1 is pulled), the `int` type hints on all private dashboard methods would fatal with null.  
**Fix:** Changed all private airline dashboard methods to `?int $tenantId` with an early redirect guard.

---

## Migration Added

**File:** `database/migrations/010_nullable_users_tenant.sql`

**What it does:**
- Alters `users.tenant_id` from `INT UNSIGNED NOT NULL` → `INT UNSIGNED NULL DEFAULT NULL`  
- Drops the old FK, modifies the column, re-adds FK with `ON DELETE SET NULL`
- Uses a stored procedure + `INFORMATION_SCHEMA` check — **safe to re-run**

**Must be run on Namecheap** — see deployment steps below.

---

## Files Changed

| File | What changed |
|---|---|
| `app/Controllers/AuthController.php` | Null-guard before `Tenant::find()`; changed `?? 0` to `?? null` in both `logLogin` call sites |
| `app/Models/AuditLog.php` | `logLogin` signature: `int` → `?int` for both `$userId` and `$tenantId` |
| `app/Controllers/DashboardController.php` | All private airline dashboard methods: `int $tenantId` → `?int` with early redirect guard |
| `database/migrations/010_nullable_users_tenant.sql` | New migration — makes `users.tenant_id` nullable |

---

## Auth / Session Fix Summary

**Login flow (platform users):**
1. Find user by email (no tenant filter — works for NULL tenant users)
2. Check password
3. Check `status = active`
4. Check `web_access = 1`
5. Load roles
6. Detect platform-only: all roles in `['super_admin','platform_support','platform_security','system_monitoring']`
7. **If platform-only:** skip tenant lookup entirely; set `is_platform_session = true`, `tenant_id = null`, `tenant = null`
8. **If NOT platform-only AND `tenant_id` is null:** block with error (orphaned account)
9. **If airline user:** `Tenant::find((int) $user['tenant_id'])` — cast-safe, null-guarded
10. Log login with `null` tenant_id for platform users (FK-safe)

---

## Audit Logging Fix Summary

- `AuditLog::logLogin(?int $userId, ?int $tenantId, ...)` — both params nullable
- `AuditService::logLogin(?int $userId, ?int $tenantId, ...)` — already nullable (no change needed)
- `audit_logs.tenant_id` FK: nullable, `ON DELETE SET NULL` — NULL is valid, only non-NULL values are checked against `tenants.id`
- `login_activity.tenant_id`: no FK, already nullable — NULL safe
- All platform/global events write `tenant_id = NULL` to audit_logs (not `0`)

---

## Query / Schema Fix Summary

- `first_name` / `last_name`: **zero occurrences** in current PHP files, views, or SQL migrations. Already resolved — errors in server log are from old server code before Phase Zero was deployed. `git pull` clears them.
- `users.tenant_id`: made nullable via migration 010

---

## Demo Credentials

Password for **all** demo accounts: `DemoOps2026!`

### Platform Accounts (`tenant_id = NULL`, platform-only nav)

| Email | Role |
|---|---|
| `demo.superadmin@acentoza.com` | Platform Super Admin |
| `demo.support@acentoza.com` | Platform Support Admin |
| `demo.security@acentoza.com` | Platform Security Admin |
| `demo.sysmonitor@acentoza.com` | System Monitoring Admin |

### Airline Accounts (tenant 1 — OpsOne Demo Airline)

`demo.airadmin@acentoza.com`, `demo.hr@acentoza.com`, `demo.scheduler@acentoza.com`, `demo.chiefpilot@acentoza.com`, `demo.headcabin@acentoza.com`, `demo.engmanager@acentoza.com`, `demo.safety@acentoza.com`, `demo.fdm@acentoza.com`, `demo.doccontrol@acentoza.com`, `demo.basemanager@acentoza.com`, `demo.pilot@acentoza.com`, `demo.cabin@acentoza.com`, `demo.engineer@acentoza.com`, `demo.training@acentoza.com`

---

## What to Do on Namecheap After Deployment

### 1. Pull latest code
```bash
cd /path/to/opsone-web
git pull origin main
```

### 2. Run migration 010 (REQUIRED — adds nullable users.tenant_id)
Run via phpMyAdmin SQL tab, or SSH:
```sql
source /path/to/opsone-web/database/migrations/010_nullable_users_tenant.sql;
```
Or SSH:
```bash
mysql -u USER -p DATABASE < database/migrations/010_nullable_users_tenant.sql
```
**This migration is idempotent — safe to run even if Namecheap already has the column nullable.**

### 3. Re-run the demo seeder (REQUIRED — creates platform users with NULL tenant_id)
```bash
php database/seeders/demo_seed.php
```
This will:
- Delete old demo users (both tenant_id=1 and tenant_id=NULL)
- Re-create platform users with NULL tenant_id and correct roles
- Re-create airline users under tenant_id=1
- Re-seed notices, file categories

### 4. Set `APP_DEMO_MODE=true` in `.env` to show the demo quick-fill panel (optional)

### 5. Clear any existing browser sessions before testing

---

## What to Check After Deployment

**A. Platform login**
- Log in as `demo.superadmin@acentoza.com` / `DemoOps2026!`
- Must succeed — no fatal errors
- Must land on dashboard showing platform-only sidebar (Airlines, Onboarding, Platform Staff, Module Catalog, Audit Log)
- Must NOT show: Roster, Standby, Notices, FDM, Compliance, Files, Users

**B. Airline login**  
- Log in as `demo.airadmin@acentoza.com` / `DemoOps2026!`
- Must succeed with full airline sidebar
- Dashboard shows airline admin view

**C. Platform route guard**  
- While logged in as `demo.superadmin@acentoza.com`, navigate to `/roster` directly
- Must redirect to `/dashboard` with flash message

**D. Audit log clean**  
- After any login, check `storage/logs/app.log`
- Must not contain `AuditService write failed`
- Must not contain `foreign key constraint`

**E. Demo seeder ran cleanly**  
- Check output — must end with `✓` on all steps
- No `SQLSTATE[23000]` errors

**F. first_name / last_name errors gone**  
- Navigate to `/dashboard` while logged in as any airline user
- Navigate to `/roster`
- Check `public/error_log` — no `Unknown column 'u.first_name'` entries

---

## Remaining Issues / Phase 1 Gaps

- **Controlled tenant access not yet enforced**: Platform users can visit airline routes IF they have a controlled-access session (future Phase 1). Currently all airline routes are blocked for platform-only users.
- **`/platform/users` edit / password reset**: Create and toggle exist; edit/reset not implemented.
- **`platform_support` / `platform_security` read-only enforcement**: Currently enforced by sidebar omission only; per-controller capability checks are Phase 1.
