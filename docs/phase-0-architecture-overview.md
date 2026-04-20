# Phase 0 — Architecture Overview

## 1. System Overview: Dual-Stack Architecture

OpsOne / CrewAssist is a multi-tenant aviation operations platform built as two tightly coupled products:

| Layer | Technology | Location |
|---|---|---|
| Web platform | PHP 8 (custom MVC, no framework) | `opsone-web/` |
| iPad app | SwiftUI + Combine + Core Data | `CrewAssist/` |
| Database (production) | MySQL 8.0+ | Namecheap shared hosting |
| Database (development) | SQLite 3 | `database/crewassist.sqlite` |
| API transport | HTTP REST, Bearer token auth | `/api/*` routes |

The web platform serves two distinct contexts from one codebase:

- **Platform Control Plane** — used by OpsOne (the service provider) staff to manage airlines, modules, and onboarding
- **Airline Workspace** — used by airline administrators, schedulers, safety officers, and operational crew

The iPad app (CrewAssist) connects exclusively to the Airline Workspace API. Platform staff do not use the iPad app.

---

## 2. Multi-Tenancy Model

Every airline is a **tenant**. Tenant isolation is the primary security boundary of the system.

### Schema-level isolation

Every operational table carries a `tenant_id` column with a foreign key to `tenants(id)`. Tables that enforce this:

- `users` — `tenant_id NOT NULL`
- `departments`, `bases`, `files`, `notices`, `roster_entries`, `roster_periods`, `roster_changes`, `roster_revisions`, `fdm_uploads`, `fdm_events`, `safety_reports`, `safety_report_updates`, `devices`, `device_approval_logs`, `audit_logs`, `api_tokens`, `sync_events`, `mobile_sync_meta`, `tenant_modules`, `tenant_settings`, `tenant_access_policies`, `tenant_contacts`, `invitation_tokens`, `notifications`, `tenant_retention_policies`

Platform-level entities (`roles` where `is_system = 1`, `modules`, `module_capabilities`) have `tenant_id = NULL` or no tenant column because they belong to the global catalog.

### Middleware-level isolation

`AuthorizationService::canAccessTenant(int $tenantId)` is called on all tenant-scoped operations. The logic is:

```
if isPlatformUser() → allow (platform admins may act on any tenant)
else → require currentTenantId() === $tenantId
```

Cross-tenant access attempts are logged to `audit_logs` with `result = 'blocked'` and action `auth.cross_tenant_attempt`.

### Platform user isolation

Platform staff (roles `super_admin`, `platform_support`, `platform_security`, `system_monitoring`) have `tenant_id = NULL` in the `users` table. The auth system, dashboard, and sidebar all check `AuthorizationService::isPlatformOnly()` to decide which navigation context to render.

---

## 3. Module System

The module system has three interlocking layers:

### Layer 1 — Global module catalog (`modules` table)

Seeded by `database/seeders/phase0_seed.php`. Each module has:
- `code` — unique machine key (e.g. `rostering`, `safety_reports`)
- `platform_status` — `available` | `beta` | `disabled`
- `mobile_capable` — whether the module can surface on iPad
- `sort_order` — display order in catalog and UI

### Layer 2 — Tenant enablement (`tenant_modules` table)

A platform super admin enables specific modules for each tenant. The junction row records `is_enabled`, `enabled_by`, and an optional `notes`. The query used at auth time:

```sql
SELECT m.*
FROM modules m
JOIN tenant_modules tm ON tm.module_id = m.id
WHERE tm.tenant_id = ? AND tm.is_enabled = 1
```

### Layer 3 — Role capability templates (`role_capability_templates` table)

Each system role is mapped to specific capabilities per module (e.g. `safety_officer → safety_reports → [view, create, submit, review, approve, export, view_audit]`). Per-user overrides are stored in `user_capability_overrides` and evaluated first.

`AuthorizationService::canAccessModule(string $moduleCode, string $capability)` runs the full chain: tenant module check → role template check → user override check.

---

## 4. Auth Flow

### Web portal (session-based)

1. `POST /login` → `AuthController::login()` validates credentials, checks `users.status = 'active'` and `users.web_access = 1`
2. Session is populated: `$_SESSION['user_id']`, `$_SESSION['tenant_id']`, `$_SESSION['user_roles'][]`
3. All subsequent requests check roles via `hasRole()` / `hasAnyRole()` helpers that read `$_SESSION['user_roles']`
4. `AuthorizationService::navContext()` returns `'platform'` or `'airline'` to control sidebar rendering

### iPad app (bearer token)

1. `POST /api/auth/login` → `AuthApiController::login()` validates credentials, creates row in `api_tokens` with 30-day expiry
2. Device must be registered via `POST /api/devices/register` → `DeviceApiController` — creates row in `devices` with `approval_status = 'pending'`
3. Admin approves device in web portal → `devices.approval_status = 'approved'`
4. All subsequent API requests carry `Authorization: Bearer {token}`; middleware validates token against `api_tokens`, checks `revoked = 0` and `expires_at > NOW()`
5. Module entitlements returned by `GET /api/user/modules` — consumed by `RealAuthService.fetchAndApplyModules()` in SwiftUI app
6. `AppEnvironment.visibleModules` intersects `RoleConfig.modules(for: role)` with server-returned slugs via `AppModule.serverSlugMap`

### RBAC enforcement

Web: Each controller calls `requireRole()` or `AuthorizationService::requireModuleAccess()` at the top of action methods.

API: API controllers validate the bearer token, extract the user's roles from `user_roles` join, and run equivalent checks.

---

## 5. Database Dual-Driver Strategy

The app auto-detects driver from the `DB_DRIVER` environment variable (`.env`):

- `DB_DRIVER=mysql` — production Namecheap MySQL 8.0+
- `DB_DRIVER=sqlite` — local development SQLite

Migration files exist in parallel pairs:
- `009_phase0_architecture.sql` (MySQL) + `009_phase0_architecture_sqlite.sql` (SQLite)
- `013_phase2_airline_admin.sql` + `013_phase2_airline_admin_sqlite.sql`
- etc.

The `Database` class in `config/database.php` wraps PDO and switches connection strings based on driver. All application code uses `Database::fetch()`, `Database::fetchAll()`, and `Database::insert()` — no driver-specific SQL escapes in business logic.

---

## 6. Key Design Principles

1. **Tenant isolation first** — no query returns data across tenants except for platform admins
2. **Audit trail on all writes** — every significant state change calls `AuditService::log()` which writes to `audit_logs`; `result` column records `success`, `failure`, or `blocked`
3. **Module-gated access** — no feature is accessible unless the module is enabled for the tenant AND the user holds the required capability
4. **Platform / airline separation** — platform admins see only the platform control plane; airline users see only the airline workspace; navigation is driven by `AuthorizationService::navContext()`
5. **Local-first verification** — no phase is deployed until verified locally in browser/Xcode
6. **Safety floor on destructive operations** — `RetentionService::purge()` enforces a hard minimum of 30 days regardless of policy override
7. **Graceful audit failure** — `AuditService::write()` catches all `\Throwable`; audit failures never break the request

---

## 7. Phase 0 Goals

Phase 0 establishes the architectural foundation that all subsequent phases build on:

- Strict tenant-aware data model with `tenant_id` on every operational table
- Three-tier role taxonomy: `platform`, `tenant`, `end_user` (via `roles.role_type`)
- Module catalog with capability templates and per-user overrides
- Central `AuditService` covering web and API contexts
- `AuthorizationService` with full authorization chain (platform bypass → tenant scope → module enabled → capability check → user override)
- Platform/airline navigation separation via `navContext()`
- `tenant_settings`, `tenant_access_policies`, `tenant_contacts` for future configuration
- `mobile_sync_meta` table as placeholder for future sync engine
- `invitation_tokens` for secure admin activation (no plain-text password emails)
- `platform_access_log` for audited platform-staff-to-tenant access
- Basic iPad API endpoints: login, device registration, user/module/roster/notices/files sync
- `SyncStore` (SwiftUI) for offline content cache with UserDefaults persistence
- Migration 019 formalizes `safety_reports`, `notifications`, and `tenant_retention_policies` tables

---

## 8. Known Technical Debt

| Item | Location | Risk | Notes |
|---|---|---|---|
| Safety reports migration gap | `database/patches/phase6_safety.sql` existed only as SQLite patch; not in numbered MySQL migration sequence | Medium | Resolved in migration 019 (Section A) |
| Mock services in production DI | `CrewAssist/Core/DI/AppEnvironment.swift` | Medium | `MockFlightService`, `MockReportingService`, `MockFDMService`, `MockAuditService` are wired into production `AppEnvironment`; TODO comments track each |
| Duplicate `/my-files` route | `config/routes.php` lines 143 and 163 | Low | Both map to `FileController` but via different patterns (Phase 4 class-based vs legacy string-based); one will shadow the other depending on router match order |
| AuditLog model shim | `app/Models/AuditLog.php` | Low | Model exists as data-access wrapper but the primary interface is `AuditService`; risk of divergence if future code calls the model directly |
| Inverted module sync check | `AppEnvironment.visibleModules` | Low | The guard `filter { !$0.enabledModuleSlugs.isEmpty }` prevents sync triggering when slugs are empty (correct) but also silently skips sync for users with genuinely zero modules assigned |
| Ephemeral device UUID | `DeviceApiController::register()` | Low | UUID is provided by the client; no server-side verification that the UUID is stable across app reinstalls — a reinstalled app registers as a new device requiring re-approval |

---

## 9. Component Inventory

### Web Controllers (`app/Controllers/`)

| Controller | Scope | Phase |
|---|---|---|
| `AuthController` | Web login/logout | 0 |
| `ActivationController` | Invitation token activation | 0 |
| `DashboardController` | Post-login landing | 0 |
| `TenantController` | Airline registry CRUD + module toggle | 0 |
| `PlatformUsersController` | Platform staff management | 1 |
| `ModuleCatalogController` | Module catalog + per-tenant assignment | 1 |
| `OnboardingController` | Onboarding request lifecycle | 1 |
| `AirlineProfileController` | Airline self-service profile | 2 |
| `DepartmentController` | Departments CRUD | 2 |
| `BaseController` | Bases CRUD | 2 |
| `FleetController` | Fleets CRUD | 2 |
| `UserController` | Airline user management + licenses | 2 |
| `RoleController` | Role viewing + capability assignment | 2 |
| `DeviceController` | Device approval workflow | 2 |
| `CrewProfileController` | Crew profiles + qualifications | 3 |
| `FileController` | Document library (admin + crew portal) | 4 |
| `NoticeController` | Notices + categories + ack report | 4 |
| `RosterController` | Roster grid, periods, bulk assign, changes, revisions, coverage | 5 |
| `FdmController` | FDM upload + event tagging | 6 |
| `SafetyController` | Safety report submission + review | 6 |
| `ComplianceController` | Expiry dashboard | 6 |
| `AuditLogController` | Audit log viewer + CSV export | 0 |
| `FeatureFlagController` | Platform feature flags | 1 |
| `InstallController` | iPad app install manifest + download | 7 |
| `PublicController` | Public marketing pages | — |

### API Controllers (`app/Controllers/`)

| Controller | Endpoints |
|---|---|
| `AuthApiController` | `POST /api/auth/login`, `POST /api/auth/logout` |
| `DeviceApiController` | `POST /api/devices/register`, `GET /api/devices/status` |
| `UserApiController` | `GET /api/user/profile`, `GET /api/user/modules`, `GET /api/user/capabilities` |
| `RosterApiController` | `GET /api/roster` |
| `FileApiController` | `GET /api/files`, `GET /api/files/download/{id}`, `POST /api/files/{id}/acknowledge` |
| `NoticeApiController` | `GET /api/notices`, `POST /api/notices/{id}/read`, `POST /api/notices/{id}/ack` |
| `SyncApiController` | `POST /api/sync/heartbeat` |
| `InstallApiController` | `GET /api/sync/manifest`, `GET /api/app/version`, `GET /api/app/build` |

### Models (`app/Models/`)

`AppBuild`, `AuditLog`, `Base`, `CrewProfileModel`, `Department`, `Device`, `FdmModel`, `FileModel`, `Fleet`, `InstallLog`, `InvitationToken`, `Module`, `Notice`, `OnboardingRequest`, `PlatformAccessLog`, `QualificationModel`, `RosterModel`, `SafetyReportModel`, `Tenant`, `TenantModule`, `UserModel`

### Services (`app/Services/`)

| Service | Purpose |
|---|---|
| `AuditService` | Centralised audit log writes (web + API contexts) |
| `AuthorizationService` | Full auth chain: platform → tenant → module → capability |
| `NotificationService` | Multi-channel notification dispatch (in_app live; push/email stubbed) |
| `RetentionService` | Data retention policy enforcement + purge |
