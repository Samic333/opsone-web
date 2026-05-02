# Phase 0.5 — Platform Separation Fix

**Date:** 2026-04-14  
**Status:** Complete  
**Scope:** Enforcement-only — no new features, no schema changes.

---

## Root Cause

Phase Zero defined the architecture for strict platform/airline separation but did not enforce it at runtime. Three bugs caused platform users to see airline-operational navigation and access airline routes:

### Bug 1 — Mixed roles on platform super admin
`demo_seed.php` assigned `['super_admin', 'airline_admin']` to `demo.superadmin@opsvelo.com`. The `isPlatformOnly()` function correctly checks that a user must hold **no** airline roles, so as soon as `airline_admin` was present it returned `false` and the airline sidebar was rendered.

### Bug 2 — All users created with `tenant_id = 1`
Platform users were inserted into the `users` table with `tenant_id = 1` (the demo airline). This meant `AuthController` ran the airline tenant check against them, and their session carried airline tenant context (`$_SESSION['tenant']`, `$_SESSION['tenant_id']`).

### Bug 3 — `AuthController` required a valid active tenant for ALL logins
The tenant check (formerly lines 52–56) ran before roles were loaded. Any user with `tenant_id = NULL` would receive "Your airline account is not active" and be rejected.

### Bug 4 — No server-side route guards on airline controllers
Platform users could directly navigate to `/roster`, `/fdm`, `/users`, `/notices`, `/files`, etc. — there was no middleware or front-controller check blocking them.

### Bug 5 — Demo login panel not env-gated
The demo quick-fill panel was always visible regardless of environment, exposing credential hints in production.

---

## Changes Made

### `app/Controllers/AuthController.php`
- Roles are now loaded **before** the tenant check.
- If all of the user's roles are in `['super_admin', 'platform_support', 'platform_security', 'system_monitoring']`, the user is treated as platform-only:
  - Tenant check skipped entirely.
  - `$_SESSION['is_platform_session'] = true`
  - `$_SESSION['tenant_id'] = null`, `$_SESSION['tenant'] = null`
- Airline users still get the existing tenant-active check.

### `app/Helpers/functions.php` — `isPlatformOnly()`
- Now reads `$_SESSION['is_platform_session']` as a fast path (set at login).
- Falls back to role-checking for sessions created before Phase 0.5.

### `database/seeders/demo_seed.php`
- Platform staff accounts (`demo.superadmin`, `demo.support`, `demo.security`, `demo.sysmonitor`) are now inserted with `tenant_id = NULL`.
- Cleanup step also deletes NULL-tenant demo users before re-seeding.
- `demo.superadmin@opsvelo.com` is assigned **only** `super_admin` — `airline_admin` removed.
- Platform role assignments use system roles (`user_roles.tenant_id = NULL`).
- Airline users remain under `tenant_id = 1` with tenant-scoped roles.

### `public/index.php`
Added a platform separation guard after `WebAuthMiddleware`. Platform-only users are redirected to `/dashboard` with a flash message if they attempt to access:
- `RosterController`
- `FdmController`
- `NoticeController`
- `FileController`
- `ComplianceController`
- `UserController` (airline users — platform uses `/platform/users`)

### `views/auth/login.php`
Demo quick-fill panel is now gated behind `APP_DEMO_MODE=true` in `.env`. Hidden in production by default.

### `config/routes.php` + `app/Controllers/PlatformUsersController.php`
New `/platform/users` section for managing platform staff accounts:
- `GET /platform/users` — list all NULL-tenant platform users
- `GET /platform/users/create` — create form
- `POST /platform/users/store` — create platform staff account
- `POST /platform/users/toggle/{id}` — activate/deactivate

### `views/layouts/app.php`
Added "Platform Staff" section to the platform sidebar linking to `/platform/users`.

### `views/platform/users.php` + `views/platform/users_create.php`
New views for platform staff management.

---

## Demo Credentials

Password for **all** demo accounts: `DemoOps2026!`

> The login page quick-fill panel is only shown when `APP_DEMO_MODE=true` in `.env`.

### Platform Accounts (no airline access)

| Email | Role | Notes |
|---|---|---|
| `demo.superadmin@opsvelo.com` | Platform Super Admin | Full platform access, no airline sidebar |
| `demo.support@opsvelo.com` | Platform Support Admin | Read-only platform support |
| `demo.security@opsvelo.com` | Platform Security Admin | Audit + security monitoring |
| `demo.sysmonitor@opsvelo.com` | System Monitoring Admin | System health |

### Airline Accounts (OpsVelo Demo Airline — tenant 1)

| Email | Role |
|---|---|
| `demo.airadmin@opsvelo.com` | Airline Admin |
| `demo.hr@opsvelo.com` | HR Admin |
| `demo.scheduler@opsvelo.com` | Scheduler Admin |
| `demo.chiefpilot@opsvelo.com` | Chief Pilot |
| `demo.headcabin@opsvelo.com` | Head of Cabin Crew |
| `demo.engmanager@opsvelo.com` | Engineering Manager |
| `demo.safety@opsvelo.com` | Safety Manager |
| `demo.fdm@opsvelo.com` | FDM Analyst |
| `demo.doccontrol@opsvelo.com` | Document Control Manager |
| `demo.basemanager@opsvelo.com` | Base Manager |
| `demo.pilot@opsvelo.com` | Pilot |
| `demo.cabin@opsvelo.com` | Cabin Crew |
| `demo.engineer@opsvelo.com` | Engineer |
| `demo.training@opsvelo.com` | Training Admin |

---

## Deploying to Namecheap

1. Pull the latest code on the server.
2. Run the demo seeder to re-create platform accounts with correct structure:
   ```
   php database/seeders/demo_seed.php
   ```
3. Add `APP_DEMO_MODE=true` to `.env` on the demo server if you want the quick-fill panel.
4. Clear any existing browser sessions (or use incognito) to test with fresh session state.

**No new database migrations are required for Phase 0.5** — all changes are application-layer only.

---

## Verification Checklist

- [ ] `demo.superadmin@opsvelo.com` logs in → sees Platform sidebar (Airlines, Onboarding, Platform Staff, Module Catalog, Audit Log) — no Roster, FDM, Notices etc.
- [ ] `demo.superadmin@opsvelo.com` navigates to `/roster` directly → redirected to `/dashboard` with flash message
- [ ] `demo.airadmin@opsvelo.com` logs in → sees airline sidebar, can access all airline sections
- [ ] `demo.pilot@opsvelo.com` logs in → sees airline sidebar, limited to crew-appropriate items
- [ ] Login page shows demo panel only when `APP_DEMO_MODE=true`
- [ ] `/platform/users` lists the 4 platform staff accounts
- [ ] `/platform/users/create` creates a new platform account with NULL tenant_id

---

## Phase 1 Gaps (Not in Scope Here)

- **Controlled tenant access**: Platform users entering an airline workspace should generate a `platform_access_log` entry. The `platform_access_log` table and `AuditService::logPlatformAccess()` exist but the enforcement flow (requiring a logged access session before navigating airline routes) is not yet wired.
- **`/platform/users` edit / password reset**: Only create and toggle are implemented.
- **Role filtering per platform user type**: `platform_support` and `platform_security` should have further read-only restrictions enforced per-controller, not just by sidebar omission.
- **Per-module capability enforcement**: `canAccessModule()` is wired but individual controllers don't all check it yet.
