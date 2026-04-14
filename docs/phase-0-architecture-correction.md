# Phase Zero — Architecture Correction

**Date:** 2026-04-14  
**Status:** Complete  
**Scope:** opsone-web (PHP backend + web portal)

---

## What Was Changed

Phase Zero is a foundational architecture correction. No new operational workflows were built. Instead, the data model, authorization layer, module system, audit logging, and navigation were refactored to correctly support a multi-tenant SaaS platform.

---

## Data Model Summary

### Migration 009: `database/migrations/009_phase0_architecture.sql`

Run this migration on the live database before deploying. It is **additive only** — no existing columns are dropped.

#### Enhanced: `tenants`
Added columns:
| Column | Type | Purpose |
|---|---|---|
| `legal_name` | VARCHAR(255) | Full legal company name |
| `display_name` | VARCHAR(100) | Short display name |
| `icao_code` | VARCHAR(10) | ICAO airline designator |
| `iata_code` | VARCHAR(5) | IATA airline code |
| `primary_country` | VARCHAR(100) | Country of operation |
| `primary_base` | VARCHAR(100) | Main hub / HQ airport |
| `support_tier` | ENUM | standard / premium / enterprise |
| `onboarding_status` | ENUM | onboarding / active / suspended / offboarding |
| `expected_headcount` | INT | Total expected staff count |
| `headcount_pilots` | INT | Expected pilot count |
| `headcount_cabin` | INT | Expected cabin crew count |
| `headcount_engineers` | INT | Expected engineers count |
| `headcount_schedulers` | INT | Expected scheduler count |
| `headcount_training` | INT | Expected training staff count |
| `headcount_safety` | INT | Expected safety staff count |
| `headcount_hr` | INT | Expected HR staff count |
| `notes` | TEXT | Internal platform notes |
| `onboarded_at` | TIMESTAMP | When tenant was activated |
| `suspended_at` | TIMESTAMP | When tenant was suspended |

#### Enhanced: `roles`
Added column:
| Column | Type | Purpose |
|---|---|---|
| `role_type` | ENUM | `platform` / `tenant` / `end_user` |

Platform roles (`super_admin`, `platform_support`, `platform_security`, `system_monitoring`) now explicitly set to `role_type = 'platform'`.

#### Enhanced: `audit_logs`
Added columns:
| Column | Type | Purpose |
|---|---|---|
| `actor_role` | VARCHAR(100) | Role slug of the acting user |
| `result` | ENUM | success / failure / blocked |
| `reason` | TEXT | Optional reason supplied by the actor |
| `user_agent` | VARCHAR(500) | Browser/client user agent |

#### New Tables

| Table | Purpose |
|---|---|
| `modules` | Platform-level module catalog |
| `module_capabilities` | Fine-grained capabilities per module (view, edit, publish, etc.) |
| `tenant_modules` | Per-tenant module enablement controlled by platform |
| `role_capability_templates` | Default capability grants per role slug |
| `user_capability_overrides` | Per-user capability grants or revocations |
| `tenant_settings` | Per-tenant config (timezone, sync interval, device approval, etc.) |
| `tenant_contacts` | Structured contact records per tenant |
| `tenant_access_policies` | MFA, IP whitelist, session timeout, platform support access level |
| `platform_access_log` | Audited record of platform admins accessing tenant areas |
| `tenant_onboarding_requests` | Airline onboarding workflow requests |
| `invitation_tokens` | Admin invitation tokens (no plain-text password emails) |
| `mobile_sync_meta` | Lightweight per-module sync version metadata (placeholder) |

---

## Role Model Summary

Roles are now classified into three types via the `role_type` column:

### Platform Roles (`role_type = 'platform'`)
| Slug | Name | Access |
|---|---|---|
| `super_admin` | Platform Super Admin | Full platform and all airline areas (via controlled access) |
| `platform_security` | Platform Security Admin | Audit logs, security center, read-only airline summary |
| `platform_support` | Platform Support Admin | Device management, app builds, airline registry read-only |
| `system_monitoring` | System Monitoring | Platform overview, airline summary |

Platform roles see **only the platform sidebar**. They cannot casually browse airline operational data. To access airline data they must use the **Controlled Access** workflow (logged to `platform_access_log`).

### Tenant Roles (`role_type = 'tenant'`)
Airline-level management roles:
`airline_admin`, `hr`, `scheduler`, `chief_pilot`, `head_cabin_crew`, `engineering_manager`, `safety_officer`, `fdm_analyst`, `document_control`, `base_manager`, `training_admin`, `director`

### End-User Roles (`role_type = 'end_user'`)
Operational staff:
`pilot`, `cabin_crew`, `engineer`

---

## Module Model Summary

### Module Catalog (`modules` table)

16 modules seeded (run `phase0_seed.php`):

| Code | Name | iPad | Notes |
|---|---|---|---|
| `crew_profiles` | Crew Profiles | — | HR records |
| `licensing` | Licensing | ✓ | Pilot/crew licences |
| `rostering` | Rostering | ✓ | Monthly duty scheduling |
| `standby_pool` | Standby Pool | ✓ | Standby management |
| `manuals` | Manuals & Documents | ✓ | Document library |
| `notices` | Notices | ✓ | Crew notices + ack |
| `safety_reports` | Safety Reports | — | Occurrence reporting |
| `fdm` | FDM | — | Flight data monitoring |
| `compliance` | Compliance Dashboard | ✓ | Licence/medical expiry |
| `training` | Training Management | — | Training records |
| `mobile_ipad_access` | Mobile / iPad Access | ✓ | iPad entitlement control |
| `sync_control` | Sync Control | ✓ | Manual sync trigger |
| `document_control` | Document Control | — | Approval workflow |
| `flight_briefing` | Flight Briefing | ✓ | Pre-flight packs (planned) |
| `future_jeppesen` | Jeppesen Integration | ✓ | Charts (planned) |
| `future_performance` | Performance Tools | — | T/O performance (planned) |

### Enablement flow
1. Module exists in platform catalog.
2. Platform super admin enables it for a specific tenant (`tenant_modules` table).
3. Role capability templates define what each role can do in that module.
4. User-level overrides can grant or revoke individual capabilities.

---

## Capability Model Summary

Capabilities are fine-grained actions within a module:

`view`, `create`, `edit`, `delete`, `approve`, `publish`, `request_change`, `manage_settings`, `view_audit`, `sync_now`, `assign`, `upload`, `acknowledge`, `export`, `submit`, `review`

Examples:
- `rostering.view` — read the roster
- `rostering.publish` — publish the monthly roster
- `manuals.acknowledge` — acknowledge receipt of a manual
- `notices.publish` — publish a notice to crew
- `safety_reports.approve` — close out a safety report
- `mobile_ipad_access.sync_now` — trigger a manual sync

### Authorization chain (checked in order)
1. Is user a platform admin? → full access (bypass module checks)
2. Is the module enabled for this tenant?
3. Does the user's role have capability via `role_capability_templates`?
4. Is there a `user_capability_overrides` row that grants or revokes?

---

## Audit Log Events Implemented

All events go through `AuditService` which writes to `audit_logs` with full context (role, result, reason, IP, user agent).

| Event | Trigger |
|---|---|
| `auth.login.success` | Successful web or API login |
| `auth.login.failure` | Failed login attempt |
| `auth.logout` | User logout |
| `auth.access_denied` | Route access denied by RBAC |
| `auth.cross_tenant_attempt` | User attempted cross-tenant data access |
| `auth.module_access_denied` | Module capability check failed |
| `tenant.created` | New tenant created |
| `tenant.updated` | Tenant record updated |
| `tenant.status_changed` | Tenant activated or suspended |
| `module.enabled` | Module enabled for a tenant |
| `module.disabled` | Module disabled for a tenant |
| `user.created` | New user account created |
| `user.updated` | User record updated |
| `user.status_changed` | User activated/suspended/etc |
| `user.role_changed` | User roles changed |
| `user.password_changed` | Password updated |
| `device.approve` | Device approved |
| `device.reject` | Device rejected |
| `device.revoke` | Device revoked |
| `capability.granted` | Per-user capability grant added |
| `capability.revoked` | Per-user capability revoked |
| `platform.tenant_access` | Platform admin accessed tenant area (+ `platform_access_log`) |
| `onboarding.request_created` | New onboarding request |
| `onboarding.approved` | Onboarding request approved |
| `onboarding.rejected` | Onboarding request rejected |
| `onboarding.provisioned` | Airline provisioned from onboarding request |
| `invitation.created` | Admin invitation token created |

All existing controller calls to `AuditLog::log()` continue to work — `AuditLog` is now a thin wrapper delegating to `AuditService`.

---

## Route / Navigation Changes

### New routes added (`config/routes.php`)

| Route | Controller | Notes |
|---|---|---|
| `GET /tenants/{id}` | `TenantController::show` | Airline detail with modules, contacts, access log |
| `POST /tenants/{id}/modules/{mid}/toggle` | `TenantController::toggleModule` | Toggle module per tenant |
| `POST /tenants/{id}/access` | `TenantController::logAccess` | Log controlled platform→tenant access |
| `POST /tenants/{id}/invite` | `TenantController::createInvitation` | Create admin invitation token |
| `GET /platform/modules` | `ModuleCatalogController::index` | Module catalog view |
| `GET /platform/modules/tenant/{id}` | `ModuleCatalogController::forTenant` | Per-tenant module management |
| `GET /platform/onboarding` | `OnboardingController::index` | Onboarding queue |
| `GET /platform/onboarding/create` | `OnboardingController::create` | New request form |
| `POST /platform/onboarding/store` | `OnboardingController::store` | Submit request |
| `GET /platform/onboarding/{id}` | `OnboardingController::show` | Review request |
| `POST /platform/onboarding/{id}/approve` | `OnboardingController::approve` | Approve request |
| `POST /platform/onboarding/{id}/reject` | `OnboardingController::reject` | Reject request |
| `POST /platform/onboarding/{id}/provision` | `OnboardingController::provision` | Convert to live tenant |

### Sidebar correction

The sidebar now renders **two completely separate nav trees**:

**Platform context** (`isPlatformOnly()` = true):
- Platform Overview (dashboard)
- Airlines → Airline Registry + Onboarding Queue
- Configuration → Module Catalog
- Security → Audit Log + Login Activity
- Support → All Devices + App Builds
- Warning notice: "To access airline data, use Controlled Access"

**Airline context** (all other users):
- Dashboard
- People → Users + Devices (for admins/HR)
- Scheduling → Roster + Standby Pool
- Content → Documents + Notices
- Safety → FDM + Compliance
- Security → Audit Log (airline-scoped)
- App → Install App

Platform users can **never casually see** airline operational sidebar items (roster, notices, documents, FDM, compliance). This cross-contamination is completely removed.

---

## Files Changed

### New files
| Path | Description |
|---|---|
| `database/migrations/009_phase0_architecture.sql` | All new tables and column additions |
| `app/Services/AuditService.php` | Central audit logging service |
| `app/Services/AuthorizationService.php` | Platform/tenant/capability authorization |
| `app/Models/Module.php` | Module catalog model |
| `app/Models/TenantModule.php` | Per-tenant module enablement |
| `app/Models/OnboardingRequest.php` | Onboarding workflow model |
| `app/Models/InvitationToken.php` | Admin invitation token model |
| `app/Models/PlatformAccessLog.php` | Platform→tenant access log model |
| `app/Controllers/ModuleCatalogController.php` | Module catalog management |
| `app/Controllers/OnboardingController.php` | Onboarding workflow |
| `views/tenants/show.php` | Airline detail view |
| `views/modules/index.php` | Module catalog view |
| `views/modules/tenant.php` | Per-tenant module toggle view |
| `views/onboarding/index.php` | Onboarding queue view |
| `views/onboarding/create.php` | New onboarding request form |
| `views/onboarding/show.php` | Review / provision view |
| `database/seeders/phase0_seed.php` | Phase Zero data seeder |
| `docs/phase-0-architecture-correction.md` | This document |

### Modified files
| Path | What changed |
|---|---|
| `app/Models/Tenant.php` | Full rewrite: all new fields, `platformSummary()`, `initializeDefaults()` |
| `app/Models/AuditLog.php` | Now delegates to `AuditService` (backward-compatible wrapper) |
| `app/Helpers/functions.php` | Added `isPlatformUser()`, `isPlatformOnly()`, `isAirlineUser()`, `canAccessModule()`, `canAccessTenant()`, `navContext()` |
| `app/Middleware/RbacMiddleware.php` | Added `requirePlatformRole()`, `requirePlatformSuperAdmin()`, `requireAirlineRole()`, `requireModuleCapability()`, `requireTenantScope()` |
| `app/Controllers/TenantController.php` | Enhanced onboarding: all new fields, module assignment, invitation tokens, controlled access logging |
| `views/layouts/app.php` | Platform vs airline sidebar fully separated |
| `views/tenants/create.php` | Full rewrite: richer onboarding form with headcounts, modules, admin contact |
| `views/tenants/index.php` | Enhanced listing with module counts, ICAO, country, tier |
| `config/routes.php` | Added all new platform/* routes + `GET /tenants/{id}` |

---

## Temporary Limitations (Reserved for Phase 1)

1. **Email delivery not wired up.** Invitation tokens are created and stored but no email is sent. The token is shown in the flash message for now. Phase 1 must add SMTP/SES and send the activation link.

2. **Invitation acceptance flow not built.** The `invitation_tokens` table and `InvitationToken` model are ready. The web form for accepting an invite (set password, activate account) is Phase 1.

3. **Platform users still have `tenant_id` in users table.** Existing `super_admin` demo user (`admin@airline.com`) was seeded with `tenant_id = 1`. Ideally platform users have `tenant_id = NULL`. Migration of existing data is Phase 1 — the architecture and middleware already handles both cases correctly via `isPlatformOnly()`.

4. **IP whitelist enforcement not wired up.** `tenant_access_policies.ip_whitelist` column exists but is not checked in middleware yet. Phase 1.

5. **MFA not implemented.** `mfa_required` column exists in `tenant_access_policies`. Phase 1.

6. **Platform access session tracking is one-way.** The controlled access log records entry but does not auto-close the session on logout. Phase 1 can wire `access_ended_at` to the logout flow.

7. **`user_capability_overrides` not surfaced in UI.** The table and service exist. Per-user capability assignment UI is Phase 1.

8. **`mobile_sync_meta` is a placeholder.** Populated in Phase 2 when the full sync engine is built.

9. **No email/password reset flow.** Exists as a table concept only. Phase 1.

10. **`future_jeppesen` and `future_performance` modules** are catalog entries only. No functionality built.

---

## Migration Notes

### What must run on Namecheap before deploying

1. **Migration 009** — `database/migrations/009_phase0_architecture.sql`  
   Run via phpMyAdmin → SQL tab, or MySQL CLI.  
   Safe to run on the existing live database (all `IF NOT EXISTS`, no destructive changes).

2. **Phase Zero Seeder** — `database/seeders/phase0_seed.php`  
   Run via SSH: `php database/seeders/phase0_seed.php`  
   Seeds module catalog, role_type flags, role-capability templates, and default module enablement for the demo tenant.  
   Safe to re-run (`INSERT IGNORE` throughout).

### Order matters
Run the migration **first**, then the seeder. The seeder references the new tables.

---

## What To Check After Deployment

### Authorization checks
- [ ] Login as `super_admin` → sidebar shows **Platform** nav only (Airlines, Module Catalog, Audit Log, Devices, App Builds)
- [ ] Login as `super_admin` → **no roster, notices, FDM, compliance, documents** in the sidebar
- [ ] Login as `platform_support` → sidebar shows support-relevant items only (Airlines read-only, Devices, App Builds)
- [ ] Login as `platform_security` → sidebar shows Audit Log, Login Activity only
- [ ] Login as `airline_admin` (e.g. `admin@airline.com`) → sidebar shows airline nav (People, Scheduling, Content, Safety, Security, App)
- [ ] Login as `pilot` → sidebar shows only: Dashboard, Scheduling, Content, App
- [ ] Direct URL `/platform/onboarding` as `pilot` → redirected to dashboard
- [ ] Direct URL `/tenants` as `pilot` → redirected to dashboard

### Tenant separation
- [ ] `/tenants` loads and shows airline list with user/module/device counts
- [ ] `/tenants/1` loads the Gulf Wings detail page with modules, access log section, invitation form
- [ ] Module toggles on `/tenants/1` work (enable/disable a module, check `tenant_modules` table)
- [ ] Audit log on `/audit-log` shows new event columns (actor_role, result)

### Module catalog
- [ ] `/platform/modules` loads and shows all 16 modules with their capabilities
- [ ] `/platform/modules/tenant/1` loads Gulf Wings module toggle page

### Onboarding
- [ ] `/platform/onboarding` loads (empty initially, shows "no requests" state)
- [ ] `/platform/onboarding/create` loads the new request form
- [ ] Submit a request → appears in pending queue
- [ ] Approve → status changes to approved
- [ ] Provision → creates a new tenant, redirects to tenant detail

### Audit
- [ ] Login and logout generate audit records visible in `/audit-log`
- [ ] Using the "Log Access & Proceed" form on `/tenants/1` creates a `platform_access_log` row

### Regression
- [ ] Existing login (`admin@airline.com` / `demo` or `DemoOps2026!`) still works
- [ ] `/dashboard` loads without fatal errors
- [ ] `/roster`, `/files`, `/notices`, `/fdm`, `/compliance` all load for airline users
- [ ] iPad API login (`/api/auth/login`) still works

---

## What Must Be Done in Phase 1

| Item | Priority |
|---|---|
| Email delivery for invitation tokens (SMTP/SES) | Critical |
| Invitation acceptance UI (set password + activate account) | Critical |
| Migrate platform users to `tenant_id = NULL` | High |
| Password reset flow | High |
| Per-user capability override UI | Medium |
| IP whitelist enforcement (`tenant_access_policies`) | Medium |
| MFA support | Medium |
| Platform access session auto-close on logout | Medium |
| Capability checks wired into existing airline controllers | Medium |
| Platform support queue (ticket management) | Low |
| Richer platform security center UI | Low |
| `mobile_sync_meta` population in sync heartbeat | Low (Phase 2) |
| Full iPad sync engine | Phase 2 |
| Jeppesen integration | Future |
| Performance calculation module | Future |
