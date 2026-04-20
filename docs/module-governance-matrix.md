# Module Governance Matrix

## 1. Module Catalog

All modules are seeded via `database/seeders/phase0_seed.php` into the `modules` table. The `platform_status` column controls platform-wide availability before any tenant can enable a module.

| Code | Name | platform_status | mobile_capable | sort_order |
|---|---|---|---|---|
| `crew_profiles` | Crew Profiles | available | No | 10 |
| `licensing` | Licensing | available | Yes | 20 |
| `rostering` | Rostering | available | Yes | 30 |
| `standby_pool` | Standby Pool | available | Yes | 35 |
| `manuals` | Manuals & Documents | available | Yes | 40 |
| `notices` | Notices | available | Yes | 50 |
| `safety_reports` | Safety Reports | available | No | 60 |
| `fdm` | Flight Data Monitoring | available | No | 70 |
| `compliance` | Compliance Dashboard | available | Yes | 80 |
| `training` | Training Management | available | No | 90 |
| `mobile_ipad_access` | Mobile / iPad Access | available | Yes | 100 |
| `sync_control` | Sync Control | available | Yes | 110 |
| `document_control` | Document Control | available | No | 120 |
| `flight_briefing` | Flight Briefing | available | Yes | 200 |
| `future_jeppesen` | Jeppesen Integration | available (planned) | Yes | 300 |
| `future_performance` | Performance Tools | available (planned) | No | 400 |

Notes on `platform_status`:
- `available` — can be assigned to tenants
- `beta` — can be assigned but marked beta in catalog UI
- `disabled` — cannot be assigned to any tenant; existing assignments suspended

Notes on `mobile_capable`:
- `Yes` — module can be surfaced in CrewAssist via `AppModule.serverSlugMap`
- `No` — module is web-only; does not appear in iPad sidebar

---

## 2. Role Access per Module

Roles are listed by decreasing privilege level. "Full" means all capabilities for that module. See the role-permission-matrix.md for per-capability detail.

| Module | Platform Roles | Airline Admin Roles | Supervisor Roles | End-User / Crew |
|---|---|---|---|---|
| `crew_profiles` | Full (bypass) | `airline_admin`: full; `hr`: full | `chief_pilot`: view+edit+audit; `head_cabin_crew`: view+edit; `safety_officer`: view+audit; `scheduler`, `training_admin`, `engineering_manager`, `base_manager`: view | `pilot`, `cabin_crew`, `engineer`: view own |
| `licensing` | Full | `airline_admin`: full; `hr`: full | `chief_pilot`: view+edit+audit; `head_cabin_crew`, `engineering_manager`: view+edit; `safety_officer`: view+audit; `training_admin`: view | `pilot`, `cabin_crew`, `engineer`: view |
| `rostering` | Full | `airline_admin`: full | `scheduler`: view+edit+publish+assign+export; `chief_pilot`: full+audit; `head_cabin_crew`: view+edit+publish+assign+export; `base_manager`, `engineering_manager`: view+export | `pilot`, `cabin_crew`, `engineer`: view only |
| `standby_pool` | Full | `airline_admin`: view+assign+export | `scheduler`: view+assign+export; `chief_pilot`: view+assign+export; `head_cabin_crew`: view+assign; `base_manager`: view | — |
| `manuals` | Full | `airline_admin`: full | `chief_pilot`: full; `head_cabin_crew`: view+upload+publish; `engineering_manager`: view+upload+publish+export; `safety_officer`: view+upload+publish; `document_control`: full | `pilot`, `cabin_crew`, `engineer`: view+ack |
| `notices` | Full | `airline_admin`: full | `chief_pilot`, `head_cabin_crew`, `engineering_manager`, `safety_officer`, `hr`: view+create+edit+publish; `document_control`: full; `training_admin`, `base_manager`: view+create; `scheduler`: view | `pilot`, `cabin_crew`, `engineer`: view+ack |
| `safety_reports` | Full | `airline_admin`: view+create+review+approve+export+audit | `safety_officer`: full; `fdm_analyst`: view+export | (crew submit via web; Phase 7 adds iPad submission) |
| `fdm` | Full | `airline_admin`: full | `safety_officer`: view+upload+create+edit+export; `fdm_analyst`: full; `chief_pilot`: view+export | — |
| `compliance` | Full | `airline_admin`: full | `hr`, `safety_officer`, `chief_pilot`: view+export+audit; `head_cabin_crew`: view+export; `fdm_analyst`, `base_manager`: view; `scheduler`: view+export | `pilot`, `cabin_crew`, `engineer`: view |
| `training` | Full | `airline_admin`: full | `hr`: view+create+edit+assign+export; `training_admin`: full | — |
| `mobile_ipad_access` | Full | `airline_admin`: full | `hr`, `chief_pilot`, `head_cabin_crew`: view+manage_settings; `scheduler`: view | `pilot`, `cabin_crew`, `engineer`: view |
| `sync_control` | Full | `airline_admin`: view+sync_now | — | `pilot`, `cabin_crew`, `engineer`: view+sync_now |
| `document_control` | Full | `airline_admin`: full | `document_control`: full | — |
| `flight_briefing` | Full | `airline_admin`: view+upload+publish+export | — | `pilot`: view (Phase 10) |
| `future_jeppesen` | Full | — | — | `pilot`: view (Phase 10) |
| `future_performance` | Full | — | — | — |

---

## 3. Module Dependencies

Some modules are operationally meaningless without prerequisite modules being active.

| Module | Hard Prerequisites | Soft Prerequisites | Notes |
|---|---|---|---|
| `licensing` | `crew_profiles` | — | License records reference crew profile records |
| `rostering` | `crew_profiles` | `licensing` | Roster assignments target crew profile records; legal compliance check needs licensing |
| `standby_pool` | `rostering` | `crew_profiles` | Standby pool replaces/supplements roster slots |
| `compliance` | `crew_profiles`, `licensing` | `rostering` | Expiry dashboard aggregates crew, license, and roster data |
| `safety_reports` | — | `crew_profiles` | Reporter lookup uses crew profile data; can exist standalone |
| `fdm` | — | `safety_reports`, `rostering` | FDM events can be linked to safety reports; flight data ideally correlates with roster |
| `document_control` | `manuals` | — | Document control module adds approval workflow on top of the base manuals library |
| `sync_control` | `mobile_ipad_access` | — | Sync control surfaces on iPad only when iPad access module is active |
| `flight_briefing` | `mobile_ipad_access`, `rostering` | `manuals` | Briefing packages require duty assignments and document library |
| `future_jeppesen` | `mobile_ipad_access`, `flight_briefing` | — | Chart integration is an extension of the briefing package |
| `future_performance` | `crew_profiles`, `rostering` | `flight_briefing` | Performance calculations are pilot-duty-specific |
| `training` | `crew_profiles` | `licensing` | Training records are assigned to crew; affect licensing validity |

---

## 4. Coming-Soon Modules

Modules that are seeded with `platform_status = 'available'` but are architectural stubs with no implementation yet:

| Module Code | What it requires before implementation |
|---|---|
| `flight_briefing` | Phase 7 (CrewAssist alignment) + a dedicated `flight_briefings` table, briefing package assembly service, and `GET /api/briefings` endpoint |
| `future_jeppesen` | Commercial agreement with Jeppesen/Boeing, `jeppesen_chart_tokens` table, OAuth token service, Phase 10 |
| `future_performance` | Performance data provider (e.g. TOPCAT, Runway Analysis), OPT data tables, Phase 10 |
| `training` | `training_courses`, `training_assignments`, `training_records` tables; `TrainingController`; Phase 5.5 roster interlock |
| `document_control` | `document_revisions` table with approval state machine; `DocumentControlController`; Phase 4 refinement |

---

## 5. How Module Activation Works Per Tenant

### Activation flow (platform side)

1. Platform super admin navigates to `/tenants/{id}` or `/platform/modules/tenant/{id}`
2. Calls `POST /tenants/{id}/modules/{mid}/toggle` → `TenantController::toggleModule()`
3. This creates or updates a row in `tenant_modules` with `is_enabled = 1 or 0`
4. `AuditService::logModuleToggle()` records the change to `audit_logs`
5. The change takes effect immediately — next API call from the iPad app that fetches modules will return the updated set

### Module query used at API time

```sql
SELECT m.*
FROM modules m
JOIN tenant_modules tm ON tm.module_id = m.id
WHERE tm.tenant_id = :tenant_id AND tm.is_enabled = 1
ORDER BY m.sort_order ASC
```

This is called by `UserApiController::modules()` → `GET /api/user/modules`, which returns the list to `RealAuthService.fetchAndApplyModules()` on the iPad app.

### Default enabled modules for new tenants

When a tenant is provisioned (demo tenant as example), the following modules are enabled by default:
`crew_profiles`, `licensing`, `rostering`, `standby_pool`, `manuals`, `notices`, `fdm`, `compliance`, `mobile_ipad_access`, `sync_control`

Modules not enabled by default (require explicit platform enablement): `safety_reports`, `training`, `document_control`, `flight_briefing`, `future_jeppesen`, `future_performance`

---

## 6. Feature Flags Relationship to Modules

Feature flags (`/platform/feature-flags`) are managed by `FeatureFlagController` and stored in a `feature_flags` table. They serve a different purpose from module enablement:

| Concept | Module enablement | Feature flags |
|---|---|---|
| Scope | Per-tenant | Platform-wide (affects all tenants) |
| Purpose | Which product areas the airline has purchased / been granted | Incremental feature rollout, A/B testing, kill-switch for unstable features |
| Managed by | Platform super admin via `/platform/modules` | Platform super admin via `/platform/feature-flags` |
| Example | Airline XYZ has `safety_reports` enabled | `enhanced_sync` flag is ON → iPad uses 15-minute sync interval instead of 60 |

Feature flags currently defined (from routes): `enhanced_sync` controls whether the iPad uses a 15-minute vs 60-minute sync polling interval. Additional flags can be added per-sprint without schema changes.

A module being enabled does not imply all its features are active — the feature flag layer can suppress specific sub-features within an enabled module across the entire platform.
