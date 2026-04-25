# Audit — 2026-04-25 Premium Alignment + Sync Pass

**Scope.** Current-truth audit of the OpsOne / CrewAssist platform that supersedes the V2 master-plan "✅ all phases complete" status with what real-runtime use surfaces. Produced as the deliverable for **Phase 0** of the 18-phase Premium Alignment + Sync pass approved on 2026-04-25.

**Authoritative sources used.**
- `CrewAssist/` — Swift/SwiftUI iPad+iPhone source of truth (~127 files, 32 feature modules)
- `opsone-web/` — PHP/MySQL admin + API
- `OpsOne Design Files/` — canonical form references (note folder typo: `Filight files and Navlog/`)
- Recent commits in both repos
- Live URL: `https://acentoza.com/api`

---

## 1. Module inventory — mobile (CrewAssist)

| Module | Primary view file | Real API service | Premium tier |
|---|---|---|---|
| Home (mobileOperational) | `Features/Dashboards/MobileOperationalDashboard.swift` | RealAuthService | ✅ Premium (baseline) |
| Home (departmentController) | `Features/Dashboards/DepartmentDashboard.swift` | RealAuthService | needs lift |
| Home (executiveOversight) | `Features/Dashboards/ExecutiveDashboard.swift` | RealAuthService | needs lift |
| Crew Reporting / Duty | `Features/DutyReporting/DutyReportingView.swift` | RealDutyReportingService | older |
| Roster | `Features/Roster/RosterView.swift` | RealRosterService | older — design-file mismatch |
| Flight Package (legacy) | `Features/FlightPackage/FlightPackageView.swift` | RealFlightService | older / stub-aliased |
| Flight Folder (real) | `Features/FlightFolder/FlightFolderRootView.swift` + 4 siblings | RealFlightFolderService | partial — new module |
| Flight Folder (legacy alias) | `Features/FlightPackage/FlightFolderView.swift` (aliased to MyFlightsView) | — | **DEDUP** |
| Flight Log / Navlog | `Features/FlightPackage/FlightLogView.swift`, `NavigationLogView.swift` | RealFlightService | older |
| Logbook | `Features/Logbook/LogbookView.swift` | RealLogbookService | older |
| FDM (admin) | `Features/FDM/FDMView.swift` | RealFDMService | older |
| My FDM | `Features/FDM/MyFDMView.swift` | RealFDMInboxService | newer |
| My Flights | `Features/MyFlights/MyFlightsView.swift` | RealFlightService | newer |
| Safety home | `Features/Safety/SafetyHomeView.swift` (+9) | RealSafetyService | older — sheet-heavy |
| Safety publications | `Features/Safety/SafetyPublicationsView.swift` | RealSafetyService | older |
| Reports menu | `Features/Reports/ReportsMenuView.swift` (+ FRAT, Airstrip, AfterMissionCaptain) | RealSafetyService | mixed |
| Safety Reports list (in Reports module) | `Features/Reports/SafetyReportsListView.swift` | RealSafetyService | **DEDUP — duplicates Safety module** |
| Profile / Me | `Features/Profile/ProfileView.swift` (+6) | LiveCrewProfile (auth) | older — needs P15 redesign |
| Licenses | `Features/Profile/LicensesView.swift` | embedded in profile | older |
| Personnel Documents | `Features/Profile/PersonnelDocumentsView.swift` | RealAuthService | newer |
| Notifications inbox | `Features/Notifications/NotificationsInboxView.swift` | RealNotificationInboxService | newer (filter pills already at top) |
| Notifications (legacy) | `Features/Notifications/NotificationsView.swift` | RealNotificationService | **DEDUP / sheet at L430** |
| Library / Manuals | `Features/Library/LibraryView.swift` + `DocumentViewer.swift` | RealFilesService | older — sync TBD |
| Per Diem | `Features/PerDiem/MyPerDiemView.swift` | RealPerDiemService | newer |
| Training | `Features/Training/MyTrainingView.swift` | RealTrainingService | newer |
| Appraisals | `Features/Appraisals/AppraisalsView.swift` (817 lines) | RealAppraisalService | **AWKWARD — central picker, monolithic** |
| Help | `Features/Help/HelpView.swift` | RealHelpService | newer |
| Acknowledgements | (alias → NotificationsInboxView via AppSidebar.swift:48–53) | — | merged correctly |
| Department-Controller dashboards | `Features/Dashboards/{HR,Scheduler,Department,Executive,Admin}Dashboard.swift` | role-gated | mixed |

**Build target.** Bundle id `com.sam.CrewAssist`, universal (iPad + iPhone), iOS deployment 17.6.

---

## 2. Module inventory — web (opsone-web)

39 migrations to date (latest `039_appraisal_ratings.sql`). 44 web controllers, 20 API controllers.

**Key tables.** `tenants`, `users` (single `name` col — confirmed), `roles`, `user_roles`, `modules`, `module_capabilities`, `module_roles`, `tenant_modules`, `api_tokens`, `devices`, `departments`, `bases`, `fleets`, `aircraft`, `crew_profiles`, `crew_documents`, `licenses`, `qualifications`, `change_requests`, `expiry_alerts`, `personnel_compliance`, `flights`, `flight_logs`, `rosters`, `roster_revisions`, `roster_periods`, `fdm_events`, `duty_reports`, `duty_exceptions`, `files`, `file_categories`, `file_reads`, `file_department_visibility`, `file_base_visibility`, `notices`, `notifications`, `safety_reports`, `safety_actions`, `appraisals`, `appraisal_ratings`, `training_records`, `training_types`, `per_diem_claims`, `per_diem_rates`, `audit_logs`, `sync_events`.

**Mobile-facing API endpoints (under `/api/`).** auth, safety/{my-reports, create, reply}, duty-reporting/{check-in, clock-out}, personnel/{profile, change-request}, flights/{my-flights, :id/bag}, fdm/{my-events, :id/ack}, logbook/submit, training/my-training, notifications/{inbox, bell}, files/my-documents, per-diem/my-claims, appraisals/my-appraisals, sync/{pull, push} (stubbed).

**Manager review pages (web).** safety queue + dashboard, duty-exception review, flight-folder review (commit `825720d`), personnel change-request review, training dashboard + logbook overview, appraisal reviews, FDM admin, per-diem approval, file/manuals upload + visibility targeting + acknowledgement (`file_reads`).

**Module/permission system.** `TenantModule::enable($tenantId, $moduleCode)` enables modules per airline; `module_roles` join controls per-role capability; mobile entitlement gated by `users.mobile_access`.

---

## 3. Confirmed bugs (file:line, verified against working tree)

| # | Bug | File(s) | Phase to fix |
|---|---|---|---|
| B1 | Sidebar uses `Color.gradientCockpit` (hardcoded dark navy) regardless of light/dark trait → text-on-dark in light mode is unreadable. | `CrewAssist/DesignSystem/Components/AppSidebar.swift:145`, `CrewAssist/DesignSystem/Components/AppDrawer.swift:23`, `CrewAssist/DesignSystem/Colors.swift:98` (token def) | **P1** |
| B2 | iPad layout in `ResponsiveNavContainer.iPadLayout` does NOT apply `.id(env.selectedModule)` to its content ZStack — modal `.sheet`s and NavigationStack push state stick when the user selects a different module from the sidebar. iPhone layout already has `.id(...)` at line 66. | `CrewAssist/DesignSystem/Components/ResponsiveNavContainer.swift:51` | **P1** |
| B3 | `AppSidebarLayout` (used by Department & Executive shells) wraps its content in NavigationStack without `.id(env.selectedModule)` — same stale-stack bug for non-mobile-operational roles. | `CrewAssist/DesignSystem/Components/AppSidebar.swift:18–28` | **P1** |
| B4 | Two `FlightFolderView`/Flight Folder entry points coexist — `Features/FlightFolder/*` (new module) and `Features/FlightPackage/FlightFolderView.swift` (legacy alias to `MyFlightsView`). Confusing for any future routing change. | `CrewAssist/Features/FlightPackage/FlightFolderView.swift:1–11` | **P4** |
| B5 | Two safety-report surfaces: `Features/Safety/` (10 files, primary) and `Features/Reports/SafetyReportsListView.swift` (also opens a form sheet at L59). Different forms, different styling. | `CrewAssist/Features/Reports/SafetyReportsListView.swift:1–80`, `CrewAssist/Features/Safety/*` | **P11** |
| B6 | Legacy `NotificationsView.swift` still presents its own sheet at L430 — likely the source of the user's "filters in the middle" complaint. The new `NotificationsInboxView` already has filter pills at the top (L51–62). | `CrewAssist/Features/Notifications/NotificationsView.swift:430` | **P9** |
| B7 | `AppraisalsView.swift` is an 817-line monolith with a centred picker + Write button instead of a premium dashboard layout. | `CrewAssist/Features/Appraisals/AppraisalsView.swift` | **P14** |
| B8 | Profile/Me page uses older GlassCard pattern, doesn't match the new home dashboard. Photo upload, license expiry alerts, status cards missing. | `CrewAssist/Features/Profile/ProfileView.swift` (358 lines) | **P15** |
| B9 | Per Diem side-sheet bug: when navigating away from a Reports detail to Per Diem, old detail sticks. Same root cause as B2. Fix in P1; verify in P16. | shared root with B2 | **P1 + P16** |

No mock-only modules detected. The `Real*Service` family covers every active screen.

---

## 4. Design-file → phase mapping

Folder: `/Users/samic/Desktop/Antigravity/OpsOne Design Files/`. Folder spelling preserved verbatim (note `Filight files and Navlog/` — typo in source).

| Phase | Mobile feature | Design file(s) |
|---|---|---|
| P3 | Roster page | `old roster/PILOTS_ROSTER_2026 (1).pdf` + `iPad app design ideas/IMG_0070–0072.PNG` |
| P4 | Flight Folder index | `iPad app design ideas/IMG_0039.PNG`, `IMG_0050–IMG_0078.PNG` |
| P5 | Journey Log | `Filight files and Navlog/JOURNEY_LOG.pdf` |
| P6 | Flight Risk Assessment | `Filight files and Navlog/FLIGHT_RISK-ASSESSMENT.pdf` |
| P7 | Crew Briefing Sheet | `Filight files and Navlog/CREW_BRIEFING-SHEET---ENTEBBE-04.04.2026.pdf` + `Briefing_04-04.04.2026.pdf` |
| P7 | Navigation log | `Filight files and Navlog/Navlog--0035N02928E---HUEN-(created-Apr-2-05-51-51Z).pdf` |
| P7 | Verification (preflight) | `Filight files and Navlog/VERIFICATION_PREFLIGHT-CHECKLIST.pdf` |
| P7 | Post-arrival | `Filight files and Navlog/POST_ARRIVAL-CHECKLIST.pdf` |
| P7 | Mass & Balance | `Filight files and Navlog/M&B-Load-Summary_edited-(135).pdf` |
| P7 | After-Mission Pilot | `After Mission Forms for Pilots/IMG_4301–4309.PNG` (9 pages) |
| P7 | After-Mission Cabin Crew | `After Mission Forms for cabin crews/AIRCRAFT_SEARCH-CHKLIST-DHC8.pdf` + `IMG_2151.HEIC` |
| P12 | FDM events | `FDM files/Events Analysis Samuel Bekele 2025-2026.pptx` |
| P14 | Appraisal | `Appraisal Form/IMG_7398–7406.HEIC` (9 pages) + `IMG_648B6489-...JPG` |
| Premium UI standard (cross-cutting) | All | `iPad app design ideas/IMG_0039–IMG_0078.PNG` (~48 reference screens) |
| "Before" baseline (cross-cutting) | All | `current ipad app pics/IMG_3971–IMG_3979.PNG` |

**Pre-phase rule.** For every redesign phase, read the matching design files BEFORE editing code (per `feedback_design_files_first.md`).

---

## 5. Open questions / SQL or deploy items deferred

- **P4 Flight Folder.** Is the existing `FlightFolderRootView` already wired to the recent web-side flight-folder review loop (commit `825720d`)? — Verify in P4 by reading `RealFlightFolderService` against `app/ApiControllers/FlightFolderApiController.php`.
- **P5 Journey Log → Logbook feed.** Is `flight_logs` already populated by `/api/logbook/submit`, and does `/api/duty-reporting/clock-out` create a journey-log row? Decide whether journey-log POST creates `flight_logs` directly or routes via a new `journey_logs` table.
- **P7 After-Mission per role.** Need to confirm role IDs for "Pilot" vs "Cabin Crew" vs "Engineer" in `roles` table; the form to render should key off `users.user_roles` (primary).
- **P8 Manuals.** `file_department_visibility` + `file_base_visibility` exist; need to confirm whether `file_role_visibility` exists or whether role targeting reuses `module_roles`.
- **P9 Notifications layout.** Decide whether to delete `NotificationsView.swift` or keep it as a `/notices` (legacy) feed under a different module.
- **P11 Safety dedup.** Confirm with user: is `SafetyReportsListView` (under Reports menu) a different *intent* (read-only audit list) or the same as `MyReportsView` (Safety module)? If same, delete; if different, rename + clarify.
- **P12 FDM real sync.** Need to verify pilot can post a comment on an FDM event from mobile and see it appear on the web admin; previous commit `0c2933d` mentions FDM-comment shipping but no E2E verification was logged.
- **No new MySQL migrations are required for P1.** P0 is documentation-only.

---

## 6. Module/permission status snapshot (from web)

Inferred from `MASTER_PLAN_V2_OPS1_COASSIST_PHASED_UPGRADE.md` + `PHASE_V2_PROGRESS_LOG.md`. Live `tenant_modules` rows should be queried in P2 to confirm. All modules below are coded; the open question is whether the demo tenant has them all enabled and assigned to the demo roles.

```
Flight Folder       | code: flight_folder | mobile + web review | enabled status: TBD (P2)
Journey Log         | code: journey_log   | mobile (P5)         | enabled status: TBD
Flight Risk         | code: flight_risk   | mobile (P6)         | enabled status: TBD
Crew Briefing       | code: crew_briefing | mobile (P7)         | enabled status: TBD
Navigation/Navlog   | code: navlog        | mobile (P7)         | enabled status: TBD
Verification        | code: verification  | mobile (P7)         | enabled status: TBD
After Mission       | code: after_mission | mobile (P7), per role | enabled status: TBD
Manuals             | code: manuals       | mobile + web        | enabled
Notifications       | code: notifications | mobile + web        | enabled
Safety              | code: safety        | mobile + web        | enabled
FDM                 | code: fdm           | mobile + web        | enabled
Reports             | code: reports       | mobile + web        | enabled
Training            | code: training      | mobile + web        | enabled
Appraisals          | code: appraisals    | mobile + web        | enabled
Profile/Documents   | code: personnel     | mobile + web        | enabled
Per Diem            | code: per_diem      | mobile + web        | enabled
Logbook             | code: logbook       | mobile + web        | enabled
Help/Hub            | code: help          | mobile + web        | enabled (per `0c2933d`)
```

---

## 7. Where this audit changes the V2 plan's status

The V2 plan in `MASTER_PLAN_V2_OPS1_COASSIST_PHASED_UPGRADE.md` marks Phases 0–16 ✅. **That status reflects "code shipped + build-green," not "verified against design files + sim-tested."** Per `feedback_simulator_verification_required`, those checkmarks do not satisfy the user's bar. This audit re-opens the verification surface for: P9 Notifications, P11 Safety dedup, P12 FDM sync, P14 Appraisal, P15 Profile, P16 Per Diem, plus the cross-cutting P1 design system fixes (B1, B2, B3) that were never specifically called out in V2.

**Next step.** Apply the P1 fixes (B1 + B2 + B3), sim-verify on iPad + iPhone in both light and dark mode, then proceed to P2 module-registry verification.

— end audit —
