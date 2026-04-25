# Phase 1–13 QA + Fix Master Plan (2026-04-25)

**Author:** continuing session.
**Persisted here so the plan survives context compaction.**
**Scope rule from user:** "Do not do shallow checking. Do not skip modules. Do not only inspect the UI. Validate UI + backend + database + permissions + mobile/iPad integration together."

## Context — what's already known from earlier audits this session

- **Tenancy:** demo airline `tenant_id=1` (production MySQL `fruinxrj_opsone`).
- **Demo users:** 30 seeded — pilot (id=290 prod / 341 local), cabin (id=291 prod / 342 local), chief pilot, HR, etc.
- **Recent migrations:** 040 module-registry alignment + role-capability templates; 041 demo seed (2 flights, training, licenses, per-diem rates+claim, appraisal).
- **Auth:** RealAuthService → POST /api/auth/login → token in Keychain; **JUST FIXED** (commit `82f3878`): keychain write fails on sim, falls back to UserDefaults.
- **JUST FOUND BUG:** sign-out → sign-in as different user shows previous user's data on home dashboard because Real*Service `@Published` arrays survive across auth state changes. Fix landed in this session (`clearPerUserCaches()` in AppEnvironment) — needs sim verification then push.
- **Sim verified working as pilot 290:** Home, Notifications CSS fix, My Flights (4), Flight Folder pilot-typed (7 forms), Journey Log autofill + auto-calc (1h 35m live), Training, Per Diem, Profile, side-sheet fix.
- **Cabin crew bug found:** `/api/flights/mine` returns `{"flights":[]}` for cabin user — there is no cabin-crew assignment column on `flights` table (only `captain_id` + `fo_id`). Cabin Flight Folder/My Flights are empty by design. **This is the schema bug we will fix in P5.**

## Phase 1 — System Map + QA Matrix

Output: a single Markdown doc that captures everything.

### Modules (per `modules` table after migration 040)
crew_profiles · licensing · rostering · standby_pool · manuals · notices · safety_reports · fdm · compliance · training · mobile_ipad_access · sync_control · document_control · flight_briefing · future_jeppesen · future_performance · duty_reporting · flight_folder · per_diem · appraisals · logbook · help · reports · notifications

### Roles (per `roles` table, slugs)
super_admin · airline_admin · platform_support · platform_security · chief_pilot · head_cabin_crew · engineering_manager · base_manager · hr · safety_officer · safety_analyst · document_control · fdm_analyst · scheduler · pilot · cabin_crew · engineer · training_admin

### Web pages (`app/Web/*Controller.php`)
Home/Dashboard · Roster · Flights · Crew Profiles · Licenses · Documents · Files (Manuals) · Notices · Safety queue + dashboard · FDM · Training · Appraisals · Per Diem · Logbook · Modules (super-admin) · Tenants (super-admin) · Roles · Users · Help

### Mobile screens (`Features/*/`)
Home (Mobile/Department/Executive Dashboard) · Roster · MyFlights · FlightFolder · Logbook · Notifications inbox · Library/Manuals · Safety home + report form · MyFDM · Reports menu (FRAT/Airstrip/AfterMissionCaptain) · Training · Appraisals · Profile/Documents/Licenses · PerDiem · Help · DutyReporting

### Database — key tables
tenants · users · user_roles · roles · departments · bases · fleets · aircraft · crew_profiles · licenses · qualifications · change_requests · expiry_alerts · flights (captain_id, fo_id only) · flight_logs · flight_folder_documents · rosters · roster_periods · roster_revisions · fdm_events · duty_reports · duty_exceptions · files · file_categories · file_reads · notices · notice_reads · notifications · safety_reports · safety_actions · appraisals · appraisal_ratings · training_records · training_types · per_diem_claims · per_diem_rates · audit_logs · sync_events · api_tokens · devices · modules · tenant_modules · module_capabilities · module_roles · role_capability_templates · tenant_role_capabilities · user_capability_overrides

### Cross-module flows
- **Roster → Flights → Folder → Journey Log → Logbook**
- **Notice/Notification → Acknowledgement → notice_reads/file_reads**
- **Safety Report → safety queue → safety_actions → notification back to reporter**
- **FDM event → pilot inbox → pilot comment → fdm_pilot_comments**
- **License/Medical expiry → expiry_alerts → home dashboard alerts widget**
- **Per Diem rate + claim → finance approval → status change**

## Phase 2 — Critical login/role/permission fixes (in progress)
- ✅ Keychain fallback (commit 82f3878 already pushed)
- ⏳ Per-user cache clear on user change (built green, needs install + sim verify, then push)
- TODO: ensure /user/modules response is cleared too (modules cached on User struct)
- TODO: any other state that survives auth changes

## Phase 3 — Airline onboarding
- Verify Platform admin can create new tenant via web `/tenants/create`
- Verify migration 041-style demo seed CAN be re-run safely as a tenant template
- Document: need a `seed_new_tenant.php` runner that takes a tenant_id arg

## Phase 4 — User and role management
- Web airline admin → /users page → create user → assign role → mobile_access toggle
- Verify user_roles join works
- Verify role_capability_templates apply on first login
- Verify mobile sees correct module list

## Phase 5 — Flight scheduling + rostering
- **PRIMARY BUG to fix:** cabin crew + engineers have NO flight assignment column on `flights`. Need either:
  - (a) New `flight_crew_assignments` table (flight_id, user_id, role_on_flight)
  - (b) Generic JSON column `crew_ids` on flights
  - Recommendation: (a) — proper foreign keys + supports purser/SCCM/F1/F2 etc.
- Update FlightApiController `/flights/mine` to query the join for non-pilot users
- Update FlightFolderApiController so cabin crew can view + submit Crew Briefing + After-Mission Cabin per assignment
- Backfill demo seed (042) to assign Noor (291) to MZ-224, MZ-225, MZ-218

## Phase 6 — iPad/iPhone integration
- Cabin Folder shows correct doc-types per role (already wired via `FlightFolderDocType.{pilotDocs, cabinCrewDocs}`)
- Verify after the P5 fix that cabin crew can open Flight Folder for an assigned flight and see only Crew Briefing + After-Mission Cabin
- Light/dark mode visual spot-check (prior session verified, re-verify this session)

## Phase 7 — Duty reporting + flight lifecycle
- Roster published → mobile sees → check-in → in-flight → clock-out → exception flow
- Verify duty_exceptions land on web admin queue

## Phase 8 — Training/compliance
- Web admin assigns training → mobile shows in Training tab
- Mobile completes (currently no completion UI — needs scoping)
- Expiry triggers expiry_alerts row → home dashboard widget

## Phase 9 — Safety reporting
- Mobile submits safety report → status=submitted
- Safety officer reviews on web → safety_actions recorded
- Reporter sees status update via /api/notifications

## Phase 10 — FDM / Manuals / Licenses
- FDM event created on web → pilot sees in MyFDM → pilot comment → web sees comment
- Manuals folder created + PDF uploaded → mobile sees → ack required → mobile acks → web shows ack
- Licenses uploaded → expiry tracked → home dashboard alert

## Phase 11 — Dashboards / counters / notifications
- Notification counts in top bar bell match actual unread/unack
- Pending acks widget reflects /notifications + /notices reads
- Latest notices widget paginates correctly

## Phase 12 — Code cleanup + duplicates + security
- Identify and remove any remaining MockServices (already mostly done)
- Verify no production secrets in code
- Ensure cPanel deploy uses .env (already done)

## Phase 13 — Final regression
- Walk all roles via sim
- Submit one of each form per role
- Verify on web + DB

## Operational rules

1. **Build green per phase** — `xcodebuild ... build`.
2. **Install via `xcrun simctl install booted`** (UDID-form hangs on this Mac).
3. **Production deploy:** prepare SQL, push to GitHub, pull on Namecheap via cPanel terminal, run `mysql -h "$DB_HOST" ... < migration.sql` (env-loaded password — wipe scrollback after).
4. **No GitHub force-push, no destructive git, no schema-DROP without explicit confirmation.**
5. **Don't claim done unless tested** — saved memory rule.
6. **Commit per phase** with traceable scope in the commit message.

## Current state when this file was written

| Check | State |
|---|---|
| HEAD opsone-web | `3d13fe6` (live = same) |
| HEAD opsone-ipad-app | `82f3878` (keychain fallback shipped) |
| Local CrewAssist build | green |
| AppEnvironment cache-clear fix | built locally green, NOT installed/verified yet |
| Sim | iPad Air 11 (M3) iOS 18.6, booted, pilot 290 logged out, cabin 291 last logged in |

## Next concrete step
Install + verify cache-clear fix; if passes, push to GitHub; then move to P5 (cabin flight assignment schema fix).
