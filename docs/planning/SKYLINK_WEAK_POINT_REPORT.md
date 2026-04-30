# Skylink Live Audit — Weak-Point Report (2026-04-26)

Companion to `SKYLINK_LIVE_AUDIT_2026-04-26.md`. Sorted by severity. Status legend: **shipped** = inline-fixed and deployed; **flagged** = needs your approval before fixing; **deferred** = nice-to-have.

## P2 (high) — needs attention

| ID | Category | Where | Finding | Status | Proposed fix |
|---|---|---|---|---|---|
| F2 | Architecture | `TenantController::store` role-clone | Cloned ALL system roles into Skylink (tenant 12) including 4 platform-tier slugs (`super_admin`, `platform_security`, `platform_support`, `system_monitoring` at IDs 799, 800, 803, 804). These never make sense at tenant scope and a tenant admin could in principle assign `platform_support` to a crew member. | **shipped** | Add `AND role_type != 'platform'` to the `SELECT` in `TenantController::store`. |
| F4 | UX broken | Scheduler dashboard sidebar | Only the personal "ME" section shown — no persistent Roster/Flights/Crew nav. Scheduler has to use empty-state CTAs to reach scheduling tools, which feels like a missing menu rather than a deliberate empty state. | **flagged** | Add Scheduler-tier nav group (Roster, Flights, Crew Assignments, Compliance Issues) in `config/sidebar.php` + `AuthorizationService::canSeeSidebarSection`. Touches > 1 file and changes a visible UI for live users → ask before shipping. |
| F5 | UX broken | Document Control dashboard sidebar | Same pattern — only ME section. No "Documents Library" or "Notices" entry even though those are the role's primary tools. | **flagged** | Same approach as F4: add Content nav group for `document_control`. |

## P3 (low) — copy / cosmetic

| ID | Category | Where | Finding | Status | Proposed fix |
|---|---|---|---|---|---|
| F1 | UX copy | `views/tenants/create.php:130-133` | Form text said "Email delivery is wired up in Phase 1." but the actual implementation now writes the activation link to `storage/logs/onboarding_invitations.log` + audit log instead. | **shipped** | Replaced with accurate description that mentions the log-file fallback and notes SMTP can be added when prod creds are configured. |

## (info) — observed but not defects

| ID | Category | Where | Note |
|---|---|---|---|
| F3 | tooling | phpMyAdmin SQL editor | Main CodeMirror SQL editor doesn't capture sequential text events from MCP browser bridge — used the Console panel at the bottom which works correctly. Documented for future runs. |
| F6 | RBAC verification | Platform-tier roles | `platform_support` correctly shows the read-only banner ("Provisioning, module changes, and staff management require Super Admin access.") + reduced sidebar (only Platform Overview / Airline Registry / All Devices). RBAC working as designed. |
| F7 | iPad CrewAssist | Avatar slot | Initials fallback rendered when `profile_photo_path` is NULL — the documented behavior per memory `feedback_avatar_photo_everywhere`. |
| F8 | iPad CrewAssist | Home dashboard | "Documents: The request timed out — Sync Now" cosmetic banner on Home for stale sync state. Not a blocker; tap to re-sync. |

## Inline fixes shipped this pass

- **F1** (1-line copy edit) — `views/tenants/create.php`. Updated the "Initial Admin Contact" helper text.
- **F2** (1-line WHERE filter) — `app/Controllers/TenantController.php`. Skips cloning platform-tier roles into new tenants.

Both are < 50-line changes, no schema migration, no API contract change, no auth/session/token logic touched. Lint clean (`php -l` on both files). Per the Pass 7 decision matrix these qualify for inline fix.

## Flagged for your approval (not yet shipped)

**F4 + F5 — Sidebar navigation gaps for `scheduler` and `document_control`.**

These would touch sidebar visibility for live users on prod (Acentoza demo + Skylink). I want your sign-off before changing what those roles see in their navigation, since adding entries that point to screens those roles can already reach via deep links has real UX implications. Two options:

1. **Add nav entries** — give scheduler a "Roster / Flights / Crew" group; give document_control a "Documents Library / Notices" group. Tighter UX, but changes what live users see immediately on next page load.
2. **Leave as-is** — the dashboard body already provides the CTAs for these roles' main actions. Scheduler clicks "Full Roster →" or "Assign Duty →" buttons; doc_control clicks "Upload →" or "New Notice →". Functionally complete; just less discoverable.

I'd recommend option 1 because the dashboards' CTAs only show on the dashboard page itself — once a scheduler navigates to "My Profile", there's no in-app path back to roster planning except via browser back. But the call is yours.

## Cleanup observations (not weak points)

- `TenantController::store` already creates 7 default departments + 7 default file_categories on tenant create. Solid.
- `Tenant::initializeDefaults($tenantId)` is called after module enable. Confirmed Skylink got `tenant_settings` + `tenant_access_policies` rows.
- All 30 modules selected during Skylink onboarding actually got rows in `tenant_modules` (verified via dashboard "30 ACTIVE MODULES" stat).
- Activation page is properly carved out of `WebAuthMiddleware` (per yesterday's fix) — Skylink admin activation worked first try.
- Token-hash auth (yesterday's mig 043) confirmed working — the 14 Skylink login round-trips all succeeded.
