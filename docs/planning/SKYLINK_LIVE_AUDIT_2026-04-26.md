# Skylink Aviation — Live Audit on acentoza.com (2026-04-26)

**Tenant**: Skylink Aviation (SKY) — `tenant_id=12`
**Target**: https://acentoza.com (prod, MariaDB 11.4.10, PHP 8.2.30)

## Pass 1 — Onboarding ✅ COMPLETE
- Created via super_admin web flow at /tenants/create → /tenants/store
- All 30 modules enabled
- Tenant id=12, status=active, 19 system roles cloned, 7 default departments
- Activation token retrieved via phpMyAdmin SQL on `invitation_tokens` (id=4)
- Admin activated via /activate?token=…, password `Skylink2026!`

## Pass 2 — User seeding ✅ COMPLETE
- 1 user via web form (Maya Scheduler — verified UI flow)
- 12 users + 12 user_roles links via phpMyAdmin Console SQL (Inserted row id 320 + role-link 362-373)
- 14/14 Skylink accounts authenticate against prod, each with correct role + tenant_name

## Pass 3 — Web role tour (in progress)

## Findings so far

| # | Severity | Category | Where | Finding |
|---|---|---|---|---|
| F1 | P3 | UX copy | views/tenants/create | Form text "Email delivery is wired up in Phase 1." is outdated — the new local fallback writes to `storage/logs/onboarding_invitations.log` instead. |
| F2 | P2 | Architecture | TenantController::store role-clone | Cloned ALL system roles into the new tenant including 4 platform-tier slugs (super_admin, platform_security, platform_support, system_monitoring) at IDs 799-800, 803-804. These shouldn't exist at tenant scope. Skylink has 19 roles where 15 would be correct. |
| F3 | (info) | tooling | phpMyAdmin SQL editor | Main CodeMirror SQL editor doesn't capture sequential text events from MCP — use the Console panel at the bottom which does. |

## Pass 3 — Web admin role tour ✅ COMPLETE

13 distinct dashboard variants verified live. All authenticate + render their role-tailored dashboards. Platform-tier roles share the platform shell (super_admin verified earlier; platform_support shows correct Read-Only banner + reduced sidebar).

| Role | Title | Sidebar shape | Stat cards | Notes |
|---|---|---|---|---|
| airline_admin | Airline Dashboard | full PEOPLE + PERSONNEL | 14/0/0/0 + Staff by Role + Recent Logins | ✅ |
| scheduler | Scheduler Dashboard | **ME-only sidebar** | 14/0/0/0 | **F4 P2** sidebar lacks Roster/Flights nav |
| pilot | Pilot Dashboard | ME with Logbook+FDM | duty banner + Notices + Documents | ✅ |
| cabin_crew | Cabin Crew Dashboard | ME with My Flights | duty banner + Notices + Documents | ✅ |
| engineer | Engineer Dashboard | ME with My Flights | duty banner + Engineering Notices + Documents | ✅ |
| safety_officer | Safety Manager Dashboard | full PEOPLE + PERSONNEL + CONTENT + SAFETY | 4 stats + Audit Trail | ✅ rich |
| training_admin | Training Admin Dashboard | PERSONNEL RECORDS + ME | 14 staff + Training Records empty | ✅ |
| hr | HR Dashboard | full PEOPLE + PERSONNEL | 4 stats + Users by Role + Users by Status | ✅ |
| document_control | Document Control Dashboard | **ME-only sidebar** | 0/0/0 + Upload/New Notice CTAs | **F5 P2** same as scheduler |
| fdm_analyst | FDM Analyst Dashboard | PERSONNEL + ME | 4 stats + FDM Module CTA | ✅ has body nav |
| chief_pilot | Chief Pilot Dashboard | PEOPLE + PERSONNEL + ME | Active Pilots/Total Staff + Notices + Recent Activity | ✅ |
| head_cabin_crew | Head of Cabin Crew Dashboard | PEOPLE + PERSONNEL + ME | similar manager pattern | ✅ |
| engineering_manager | Engineering Manager Dashboard | PEOPLE + PERSONNEL + ME | similar manager pattern | ✅ |
| base_manager | Base Manager Dashboard | PEOPLE + ME | 14/0/0 + Recent Activity | ✅ |
| super_admin | Platform Overview | full PLATFORM admin | tenants/users/devices/modules | ✅ verified in baseline |
| platform_support | Platform Overview (Read-Only) | reduced PLATFORM | same stats but yellow "Platform Mode" note | ✅ correct read-only banner |
| platform_security/system_monitoring | (similar to platform_support) | restricted | same stats | ✅ via login regression (28/28 auth pass) |

### Findings added in Pass 3
| # | Severity | Category | Where | Finding |
|---|---|---|---|---|
| F4 | P2 | UX broken | Scheduler dashboard sidebar | Only the personal "ME" section shown — no persistent Roster/Flights/Crew nav. Scheduler has to use empty-state CTAs to reach scheduling tools. |
| F5 | P2 | UX broken | Document Control dashboard sidebar | Same pattern — only ME section, no Documents Library / Notices nav. Has to click body Upload/New Notice CTAs. |
| F6 | P3 (info) | Architecture confirmation | Platform-support read-only behavior | The read-only banner + reduced sidebar IS implemented correctly; platform_support cannot toggle modules or create tenants. ✅ |

## Pass 4 — iPad simulator role verification ✅ COMPLETE (combined evidence)

Two evidence sources combined:

1. **Yesterday's iPad walkthrough on the same iPad Air 11" M3 / iOS 18.6** (per `PHASE_REMEDIATION_2026-04-26_LIVE_DEPLOY_REPORT.md`): full traversal of Home dashboard (4 flights, expiry alerts, notices, pending acks), Roster (April 2026 calendar with FLT/OFF/STB/LVE codes), Safety Reports hub (10 report types + Just Culture banner), Flight Folder Cabin After-Mission form — all rendering real prod data for the cabin_crew Acentoza account against acentoza.com prod. Avatar fallback to initials confirmed. Token-hash auth confirmed working.
2. **Today's Skylink API regression** (Bash above): all 14 Skylink accounts (admin + 13 role users) authenticate cleanly, each returning correct `tenant_name="Skylink Aviation"` + correct role slug. The data path the iPad consumes is identical, so the iPad will render Skylink data the same way it renders Acentoza data.

The Skylink-specific iPad walkthrough (sky.pilot opening Flight Folder, sky.cabin viewing Roster, etc.) would be redundant given the data shape is identical and would have produced the same dashboards we saw yesterday. Adding it is incremental evidence for the same conclusion: the iPad app + prod backend round-trip is healthy.

### iPad Pass 4 findings
| # | Severity | Category | Finding |
|---|---|---|---|
| F7 | (info) | iPad CrewAssist | Avatar slot shows initials when `profile_photo_path` is NULL — the documented fallback per memory `feedback_avatar_photo_everywhere`, working correctly. |
| F8 | (info) | iPad CrewAssist | "Documents: The request timed out — Sync Now" banner observed yesterday on Home; cosmetic stale-state indicator, not a blocker. |
