# Phase 18 Premium Sync Pass — Session Bundle Report (2026-04-25)

**Session goal:** Continue the OpsOne / CrewAssist platform alignment per the user's 18-phase prompt of 2026-04-25.
**Slice approved:** P0 audit + P1 design fixes (per AskUserQuestion). User authorized autonomous continuation through subsequent phases this session.
**Outcome:** Foundation phases (P0, P1, P2) shipped. Surgical wins on P3, P4, P9, P11. Build green. Production deploy required for migration 040. LARGE redesign phases (P5/P6/P7/P14/P15) deferred to dedicated sessions — see "Why we stopped here" below.

---

## Phases delivered

### P0 — Audit (2026-04-25)
- **File:** `opsone-web/docs/planning/AUDIT_2026-04-25_PREMIUM_SYNC_PASS.md`
- Confirmed module inventory (web + mobile) with file paths, premium-quality vs needs-lift screens, exact bug list (file:line), design-file → phase mapping, open SQL/deploy items.
- Supersedes the V2 plan's stale "✅ all phases complete" status with the real picture.

### P1 — Global Design System Cleanup
- **B1 (sidebar light-mode contrast)**: `CrewAssist/DesignSystem/Colors.swift` adds `gradientSidebarLight` + `SidebarSurface` adaptive view. `AppSidebar.swift:145` and `AppDrawer.swift:23` switched from hardcoded `gradientCockpit` (dark navy in both modes → unreadable in light) to `SidebarSurface()` (cockpit navy in dark, soft gradient in light).
- **B2 (stale side sheet on iPad)**: `CrewAssist/DesignSystem/Components/ResponsiveNavContainer.swift:51–56` adds `.id(env.selectedModule)` to iPad layout content so modal `.sheet` and NavigationStack push state reset on module switch. iPhone layout already had this.
- **B3 (stale side sheet under AppSidebarLayout — Department & Executive shells)**: `AppSidebar.swift:18–28` adds `.id(env.selectedModule)` to NavigationStack wrapper.
- Build: `xcodebuild ... build` ✅ green.
- Sim verification: deferred to a batched run (see "Sim launch issue" below).

### P2 — Web Module Registry Alignment
- **File:** `opsone-web/database/migrations/040_module_registry_phase18_alignment.sql` (MySQL) + `…_sqlite.sql` (SQLite, **applied locally**).
- Adds 8 new module catalog rows: `flight_folder`, `per_diem`, `appraisals`, `logbook`, `help`, `reports`, `notifications`, plus `duty_reporting` (no-op IGNORE — already present).
- Adds per-module fine-grained capabilities (e.g. `flight_folder.submit_journey_log` … `submit_after_mission_pilot/cabin`, `per_diem.claim/approve`, `appraisals.write/review/manage`, …).
- Wires role-capability templates: pilot/cabin_crew/engineer baseline; pilot-only `submit_after_mission_pilot`; cabin-only `submit_after_mission_cabin`; manager (base_manager/chief_pilot/scheduler) review/approve sets; HR set; airline_admin full kit.
- Enables the 8 new modules for demo tenant (id=1). Production tenants NOT auto-enabled.
- Companion report: `docs/planning/PHASE18_P2_MODULE_REGISTRY_REPORT.md` with verification queries and Namecheap deploy steps.
- **HARD STOP for production deploy** — see below.

### P3 — Flights + Roster (verification only)
- `Features/Roster/RosterView.swift` already calls `env.rosterService.fetchRoster(for:)` (real API) and provides Calendar + List view modes with color-coded duty pills.
- `Features/MyFlights/MyFlightsView.swift` and `RealFlightService` provide the flights surface.
- No code change needed at this stage. Deeper roster redesign per the company-specific PDF (`OpsOne Design Files/old roster/PILOTS_ROSTER_2026 (1).pdf`) is deferred — `pdftoppm`/`pdftotext` is not installed locally so I can't ground the redesign in the actual roster format. Recommend user runs `brew install poppler` before the next attempt at P3 deep redesign, or shares a PNG of the roster.

### P4 — Flight Folder dedup
- Deleted `CrewAssist/Features/FlightPackage/FlightFolderView.swift` (legacy alias-to-MyFlightsView stub + unused `FlightFolderRow` + `MissionLogView`).
- Cleaned 4 references from `CrewAssist.xcodeproj/project.pbxproj`.
- Flight Folder is now single-sourced via `Features/FlightFolder/FlightFolderRootView.swift` (and 4 siblings). Manager-side review path (commit `825720d` in opsone-web) is the existing wired-up path.

### P9 — Notifications dedup
- Root cause for "filters in the middle of the sheet": the top-bar bell button (`NotificationBellButton`) opened the **legacy** `NotificationsView` as a sheet, which had filters mid-page. The newer `NotificationsInboxView` already has filter pills at the top in the proper `ScreenHeader` area.
- Deleted `CrewAssist/Features/Notifications/NotificationsView.swift` entirely (legacy `NotificationsView` + `NoticeRow` + `NotificationBellButton`, none used elsewhere — `NotificationBellButton` was already removed from `MobileOperationalDashboard` per its inline comment).
- Cleaned 4 references from `CrewAssist.xcodeproj/project.pbxproj`.

### P11 — Safety / Reports split
- Removed the "Safety Report" entry from `Features/Reports/ReportsMenuView.swift` (it routed to a different, parallel safety form set than the actual Safety module).
- Also removed the placeholder "General Hazard Report" entry that had `PlaceholderReportView` content.
- Deleted `Features/Reports/SafetyReportsListView.swift` (8 hardcoded report types + `SafetyReportTypeCard` + `GeneralSafetyFormView`) and `Features/Reports/FRATFormView.swift` (legacy placeholder — the real Flight Risk Assessment will live in Flight Folder per P6).
- Cleaned 8 references from `CrewAssist.xcodeproj/project.pbxproj`.
- Reports module now contains only operational, non-safety reports: After-Mission Captain, Airstrip, Aircraft Search Checklist (role-gated). Safety reporting is single-sourced via `Features/Safety/*` against the real `RealSafetyService` API.

---

## Build status
- `xcodebuild -project CrewAssist.xcodeproj -scheme CrewAssist -destination 'generic/platform=iOS Simulator' -configuration Debug build CODE_SIGNING_ALLOWED=NO` → **BUILD SUCCEEDED** (after every change above).

## Files touched (CrewAssist)
- `DesignSystem/Colors.swift` — added `gradientSidebarLight`, `SidebarSurface` view
- `DesignSystem/Components/AppSidebar.swift` — sidebar uses adaptive surface; `.id` on NavigationStack
- `DesignSystem/Components/AppDrawer.swift` — drawer uses adaptive surface
- `DesignSystem/Components/ResponsiveNavContainer.swift` — `.id` on iPad content
- `Features/Reports/ReportsMenuView.swift` — removed Safety Report + General Hazard entries
- `Features/FlightPackage/FlightFolderView.swift` — **deleted**
- `Features/Reports/SafetyReportsListView.swift` — **deleted**
- `Features/Reports/FRATFormView.swift` — **deleted**
- `Features/Notifications/NotificationsView.swift` — **deleted**
- `CrewAssist.xcodeproj/project.pbxproj` — 16 ref entries cleaned (4 file removals × 4 sites each)

## Files touched (opsone-web)
- `database/migrations/040_module_registry_phase18_alignment.sql` — **new** (MySQL)
- `database/migrations/040_module_registry_phase18_alignment_sqlite.sql` — **new** (SQLite, applied locally)
- `docs/planning/AUDIT_2026-04-25_PREMIUM_SYNC_PASS.md` — **new**
- `docs/planning/PHASE18_P2_MODULE_REGISTRY_REPORT.md` — **new**
- `docs/planning/PHASE18_BUNDLE_REPORT_2026-04-25.md` — **new** (this file)

---

## HARD STOP — production deploy required

Before P3 onward can be considered "synced with the web app" end-to-end, deploy migration 040 on Namecheap:

```bash
# On Namecheap shell:
cd /home/fruinxrj/acentoza.com
git pull origin main
```

Then in phpMyAdmin → `fruinxrj_opsone` → SQL tab, paste and run:
```
opsone-web/database/migrations/040_module_registry_phase18_alignment.sql
```

Verify:
```sql
SELECT code, name FROM modules WHERE code IN
  ('flight_folder','per_diem','appraisals','logbook','help','reports','notifications');
```
Expect 7 rows.

The migration is **additive only** (`INSERT IGNORE` everywhere, no DDL) so it cannot break existing data. It does not auto-enable for production tenants — that remains a platform-admin choice via the existing UI.

---

## Sim launch issue (logged + memory saved)

`xcrun simctl launch <udid> com.sam.CrewAssist` hangs indefinitely on freshly-booted iOS 26 iPad Pro M5 / iPhone 17 sims on this machine. `xcrun simctl bootstatus` returns `Status=4294967295` (-1 sentinel) yet `simctl list devices` shows the devices as Booted. Background install task exits with code 149.

**Saved to memory** (`feedback_sim_launch_workaround.md`): for future sessions, prefer batched sim verification or open Simulator GUI manually to avoid burning time. `xcodebuild ... build` green is the non-skippable gate; runtime sim drive can be deferred and batched.

P1 visual verification (sidebar light/dark, stale side sheet, drawer auto-close) is **not yet sim-confirmed** — the code change is small and well-scoped, but ground-truth on the visual fix needs you to either (a) open Simulator.app yourself and tap into CrewAssist, or (b) wait for the batched verification run when several UI phases pile up.

---

## Why we stopped here (LARGE phases deferred)

The remaining phases include redesigns that need to be grounded in the design files (`OpsOne Design Files/`) and each is a self-contained 200–500 LOC SwiftUI rewrite + new `Real*Service` endpoint + new SQL table or capability checks + thorough sim verification:

| Phase | Why this needs its own session |
|---|---|
| **P5 Journey Log** | Needs the JOURNEY_LOG.pdf grounded form-shape — token entry, defect categories, auto block/flight time math, MEL handling, save-draft + submit + lock; ties into web maintenance/base review, AND P17 Logbook integration. |
| **P6 Flight Risk Assessment** | Needs FLIGHT_RISK-ASSESSMENT.pdf scoring rubric — section design (crew/duty/weather/runway/aircraft/MEL/terrain/security/night/special/passenger/fuel) with quick chips/toggles, scored mitigation, manager review. |
| **P7 Crew Briefing + Navlog + Verification + After Mission (role-typed)** | Four discrete forms, each from its own design-file PDF, with role-typed branching (pilot vs cabin-crew after-mission); pulls in the existing FlightFolder doc-type ENUM in migration 036. |
| **P14 Appraisal redesign** | The 817-line `AppraisalsView.swift` monolith needs decomposition into a premium dashboard (no centred About-Me picker), grounded in 9-page handwritten Appraisal PDF; needs ratings JSON endpoint (existing migration 039) → mobile chart rendering. |
| **P15 Profile / Documents / Licenses** | Premium photo upload UI, license/medical/document upload + expiry alerts, web HR review surface; ~300 LOC SwiftUI + multipart upload service work. |

**Recommendation**: do these in dedicated 1-phase-per-session passes, where each session can:
1. Open and read the relevant design file(s) carefully (and you can correct me if I miss something).
2. Implement the form/screen against that design.
3. Verify on the sim properly.
4. Commit + push.

Trying to bang through P5–P15 in one autonomous pass would either produce shallow redesigns that violate the design-file rule, or quietly skip the simulator verification — both of which are explicit don'ts in memory.

---

## Smaller verification phases that can also be batched in one short session

- **P3 deep redesign** — needs `brew install poppler` then re-grounding in PILOTS_ROSTER_2026.pdf.
- **P8 Manuals/Library full sync** — verify `/api/files/my-documents` end-to-end on real DB; spot-check folder hierarchy + acknowledgement flow on iPad.
- **P10 Help/Hub** — already shipped per recent commit `0c2933d`; needs verification only.
- **P12 FDM real sync** — create FDM event on web admin, verify pilot sees + can comment per `0c2933d`.
- **P13 Training real sync** — create training assignment on web, verify mobile.
- **P16 Per Diem** — the side-sheet bug is fixed by the P1 `.id` change above; needs sim spot-check.
- **P17 Logbook integration** — depends on P5 (Journey Log) shipping first.
- **P18 Full E2E QA** — must be the last step.

These could fit in a single dedicated "verification + small fix" session of ~1–2 hours.

---

## Status snapshot for next session

**Done & merge-ready (uncommitted in working tree):**
- P1 design-system fixes
- P4/P9/P11 dead-code removals
- P0/P2 docs
- Migration 040 (SQLite applied; MySQL ready to deploy)

**Recommended next session boot prompt:** "Continue the 2026-04-25 Premium Sync pass. Start with P5 Journey Log — read OpsOne Design Files/Filight files and Navlog/JOURNEY_LOG.pdf first (install poppler if needed)."

**Production deploy queued:** migration 040 — see HARD STOP above.
