# Role Permission Matrix

## 1. Role Taxonomy

Roles are stored in the `roles` table with a `role_type` ENUM (`platform`, `tenant`, `end_user`) and `is_system = 1` for all seeded roles. System roles have `tenant_id = NULL` and apply globally. Tenant-specific custom roles (future) would have a non-null `tenant_id`.

### Platform Roles (`role_type = 'platform'`)

These users have `tenant_id = NULL` in the `users` table. They can access the Platform Control Plane and bypass all tenant-scope and module-enabled checks in `AuthorizationService`.

| Slug | Display Name | Description |
|---|---|---|
| `super_admin` | Platform Super Admin | Full platform access and all airline access; only role that can provision tenants and manage module catalog |
| `platform_support` | Platform Support Admin | Read-only platform support access; can view tenant details and audit logs but cannot modify |
| `platform_security` | Platform Security Admin | Security monitoring and audit access; can view `platform_access_log` and all `audit_logs` |
| `system_monitoring` | System Monitoring Admin | System health and sync monitoring; primarily used for automated health-check accounts |

### Tenant / Admin Roles (`role_type = 'tenant'`)

These roles belong to airline staff who manage the airline. They see the Airline Workspace sidebar.

| Slug | Display Name | Description |
|---|---|---|
| `airline_admin` | Airline Admin | Full airline workspace access; equivalent to a super admin within the airline tenant |
| `hr` | HR | Human resources management; crew records, licensing, training |
| `scheduler` | Scheduler | Scheduling and crew control; roster management, standby pool |
| `chief_pilot` | Chief Pilot | Flight operations oversight; roster approval, FDM review, compliance |
| `head_cabin_crew` | Head of Cabin Crew | Cabin crew department management; roster and compliance |
| `engineering_manager` | Engineering Manager | Engineering/maintenance management; licensing, manuals |
| `safety_officer` | Safety Manager | Safety management; full safety report lifecycle, FDM |
| `fdm_analyst` | FDM Analyst | Flight data monitoring analysis; FDM upload and event review |
| `document_control` | Document Control | Document approval workflow; manuals, document control module |
| `training_admin` | Training Admin | Training programme administration |
| `base_manager` | Base Manager | Base-level operational oversight; roster view, notices |

### End-User / Crew Roles (`role_type = 'end_user'`)

Operational crew who primarily use the iPad app (CrewAssist). Limited web portal access.

| Slug | Display Name | Description |
|---|---|---|
| `pilot` | Pilot | Flight crew; roster view, manuals, notices, compliance, safety submissions |
| `cabin_crew` | Cabin Crew | Cabin crew member; roster, manuals, notices, compliance |
| `engineer` | Engineer | Engineering/maintenance crew; manuals, notices, compliance |

---

## 2. Module Capability Permission Matrix

Capability keys: `view` `create` `edit` `delete` `publish` `upload` `assign` `submit` `review` `approve` `export` `acknowledge` `request_change` `manage_settings` `sync_now` `view_audit`

### crew_profiles module

| Role | view | create | edit | delete | export | view_audit |
|---|---|---|---|---|---|---|
| `super_admin` | Y | Y | Y | Y | Y | Y |
| `airline_admin` | Y | Y | Y | Y | Y | Y |
| `hr` | Y | Y | Y | Y | Y | Y |
| `chief_pilot` | Y | — | Y | — | — | Y |
| `head_cabin_crew` | Y | — | Y | — | — | — |
| `safety_officer` | Y | — | — | — | — | Y |
| `base_manager` | Y | — | — | — | — | — |
| `scheduler` | Y | — | — | — | — | — |
| `training_admin` | Y | — | — | — | — | — |
| `engineering_manager` | Y | — | — | — | — | — |
| `pilot` | Y | — | — | — | — | — |
| `cabin_crew` | Y | — | — | — | — | — |
| `engineer` | Y | — | — | — | — | — |

### licensing module

| Role | view | create | edit | delete | export | view_audit |
|---|---|---|---|---|---|---|
| `airline_admin` | Y | Y | Y | Y | Y | Y |
| `hr` | Y | Y | Y | Y | Y | Y |
| `chief_pilot` | Y | — | Y | — | — | Y |
| `head_cabin_crew` | Y | — | Y | — | — | — |
| `engineering_manager` | Y | — | Y | — | — | — |
| `safety_officer` | Y | — | — | — | — | Y |
| `training_admin` | Y | — | — | — | — | — |
| `base_manager` | — | — | — | — | — | — |
| `pilot` | Y | — | — | — | — | — |
| `cabin_crew` | Y | — | — | — | — | — |
| `engineer` | Y | — | — | — | — | — |

### rostering module

| Role | view | edit | publish | assign | export | view_audit |
|---|---|---|---|---|---|---|
| `airline_admin` | Y | Y | Y | Y | Y | Y |
| `scheduler` | Y | Y | Y | Y | Y | — |
| `chief_pilot` | Y | Y | Y | Y | Y | Y |
| `head_cabin_crew` | Y | Y | Y | Y | Y | — |
| `base_manager` | Y | — | — | — | Y | — |
| `engineering_manager` | Y | — | — | — | Y | — |
| `pilot` | Y | — | — | — | — | — |
| `cabin_crew` | Y | — | — | — | — | — |
| `engineer` | Y | — | — | — | — | — |

### manuals module

| Role | view | upload | publish | delete | acknowledge | export |
|---|---|---|---|---|---|---|
| `airline_admin` | Y | Y | Y | Y | — | Y |
| `chief_pilot` | Y | Y | Y | Y | — | Y |
| `head_cabin_crew` | Y | Y | Y | — | — | — |
| `engineering_manager` | Y | Y | Y | — | — | Y |
| `safety_officer` | Y | Y | Y | — | — | — |
| `document_control` | Y | Y | Y | Y | — | Y |
| `pilot` | Y | — | — | — | Y | — |
| `cabin_crew` | Y | — | — | — | Y | — |
| `engineer` | Y | — | — | — | Y | — |

### notices module

| Role | view | create | edit | delete | publish | acknowledge | export |
|---|---|---|---|---|---|---|---|
| `airline_admin` | Y | Y | Y | Y | Y | — | Y |
| `chief_pilot` | Y | Y | Y | — | Y | — | — |
| `head_cabin_crew` | Y | Y | Y | — | Y | — | — |
| `engineering_manager` | Y | Y | Y | — | Y | — | — |
| `safety_officer` | Y | Y | Y | — | Y | — | — |
| `document_control` | Y | Y | Y | Y | Y | — | — |
| `hr` | Y | Y | Y | — | Y | — | — |
| `training_admin` | Y | Y | — | — | — | — | — |
| `base_manager` | Y | Y | — | — | — | — | — |
| `scheduler` | Y | — | — | — | — | — | — |
| `pilot` | Y | — | — | — | — | Y | — |
| `cabin_crew` | Y | — | — | — | — | Y | — |
| `engineer` | Y | — | — | — | — | Y | — |

### safety_reports module

| Role | view | create | edit | submit | review | approve | export | view_audit |
|---|---|---|---|---|---|---|---|---|
| `airline_admin` | Y | Y | — | — | Y | Y | Y | Y |
| `safety_officer` | Y | Y | Y | Y | Y | Y | Y | Y |
| `fdm_analyst` | Y | — | — | — | — | — | Y | — |

### fdm module

| Role | view | upload | create | edit | delete | export |
|---|---|---|---|---|---|---|
| `airline_admin` | Y | Y | Y | Y | Y | Y |
| `safety_officer` | Y | Y | Y | Y | — | Y |
| `fdm_analyst` | Y | Y | Y | Y | Y | Y |
| `chief_pilot` | Y | — | — | — | — | Y |

### document_control module

| Role | view | upload | approve | publish | delete | request_change | export | view_audit |
|---|---|---|---|---|---|---|---|---|
| `airline_admin` | Y | Y | Y | Y | Y | Y | Y | Y |
| `document_control` | Y | Y | Y | Y | Y | Y | Y | Y |

### training module

| Role | view | create | edit | delete | assign | approve | export |
|---|---|---|---|---|---|---|---|
| `airline_admin` | Y | Y | Y | Y | Y | Y | Y |
| `hr` | Y | Y | Y | — | Y | — | Y |
| `training_admin` | Y | Y | Y | Y | Y | Y | Y |

### compliance module

| Role | view | export | view_audit |
|---|---|---|---|
| `airline_admin` | Y | Y | Y |
| `hr` | Y | Y | Y |
| `chief_pilot` | Y | Y | Y |
| `head_cabin_crew` | Y | Y | — |
| `safety_officer` | Y | Y | Y |
| `fdm_analyst` | Y | — | — |
| `scheduler` | Y | Y | — |
| `base_manager` | Y | — | — |
| `pilot` | Y | — | — |
| `cabin_crew` | Y | — | — |
| `engineer` | Y | — | — |

### mobile_ipad_access module

| Role | view | manage_settings | sync_now |
|---|---|---|---|
| `airline_admin` | Y | Y | Y |
| `hr` | Y | Y | — |
| `chief_pilot` | Y | Y | — |
| `head_cabin_crew` | Y | Y | — |
| `scheduler` | Y | — | — |
| `pilot` | Y | — | — |
| `cabin_crew` | Y | — | — |
| `engineer` | Y | — | — |

---

## 3. Web Portal vs iPad App Permissions

| Permission Type | Web Portal | iPad (CrewAssist) |
|---|---|---|
| Admin functions (CRUD users, modules, tenants) | Platform roles + `airline_admin` | Not available |
| Roster management (publish, edit) | `scheduler`, `chief_pilot`, `airline_admin` | Not available |
| Notice publishing | Admin/supervisor roles | Not available |
| Document upload/publish | `document_control`, admin roles | Not available |
| Safety report review/approve | `safety_officer`, `airline_admin` | Not available |
| View roster | All roles | `pilot`, `cabin_crew`, `engineer` (read-only) |
| View/ack notices | All roles | All mobile users |
| View/ack documents | All roles | All mobile users |
| Safety report submission | All roles (web) | `pilot`, `cabin_crew`, `engineer` |
| Compliance view | All roles | All mobile users (own records) |
| Sync control | `airline_admin`, `hr`, `chief_pilot`, `head_cabin_crew` | All mobile users (`sync_now`) |

---

## 4. Role-Based Module Visibility in iPad App

The iPad app uses two layers to compute visible modules:

**Layer 1 — `RoleConfig.swift`**: Defines which `AppModule` enum cases a role is permitted to see. Example: `pilot` → `[.home, .crewReporting, .roster, .flightPackage, .logbook, .manuals, .notifications, .acknowledgements, .safety, .profile, .licenses, .fdm, .reports, .flightFolder]`

**Layer 2 — `AppModule.serverSlugMap`**: Maps web-side module codes to iPad `AppModule` cases:

```
"rostering"       → [.roster, .scheduleControl]
"manuals"         → [.manuals, .acknowledgements]
"notices"         → [.notifications, .acknowledgements]
"compliance"      → [.complianceSummary]
"fdm"             → [.fdm, .fdmReports]
"safety_reports"  → [.safety, .safetyQueue]
"crew_profiles"   → [.employeeRecords]
"licensing"       → [.licenses]
"document_control"→ [.documentUpload]
"flight_briefing" → [.flightPackage, .flightFolder]
```

`AppEnvironment.visibleModules` is the intersection of RoleConfig-permitted modules and server-allowed modules. Modules in `AppModule.alwaysVisible` (`home`, `profile`, `notifications`, `crewReporting`) are always shown regardless of tenant module status.

---

## 5. Admin vs Crew Distinction

**Admin roles** (`airline_admin`, `hr`, `scheduler`, `chief_pilot`, `head_cabin_crew`, `engineering_manager`, `safety_officer`, `fdm_analyst`, `document_control`, `training_admin`, `base_manager`):
- Have web portal access (`web_access = 1`)
- Can view/manage other users' data within their scope
- May or may not have iPad access depending on their operational duties
- `AuthorizationService::isAirlineUser()` returns `true`

**Crew roles** (`pilot`, `cabin_crew`, `engineer`):
- Primarily iPad users; may have web portal access (`web_access = 1` if set)
- Can only view their own operational data (roster, my-notices, my-files)
- `DashboardFamily` in SwiftUI = `mobileOperational`
- `AuthorizationService::canSeeSidebarSection('operations')` returns `true` only for these roles when they do not also hold an admin role

---

## 6. Platform Super-Admin Access

`super_admin` (platform role) has blanket access because `AuthorizationService::isPlatformUser()` returns `true`, causing `canAccessTenant()`, `canAccessModule()`, and `hasCapability()` to all short-circuit to `true`.

In the iPad app context, `super_admin` is not a usable iPad role — platform staff do not log in to CrewAssist. The SwiftUI `AppRole.superAdmin` case maps to full `AppModule.allCases` access and is reserved for internal testing only.

`platform_support` can view but not modify tenant data. Enforcement is currently at the controller level (read-only routes only); no separate capability table entries exist for platform support.

`platform_security` has read access to all `audit_logs`, `login_activity`, and `platform_access_log` records across all tenants.
