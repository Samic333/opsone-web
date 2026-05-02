# Phase V2 — Mobile (CrewAssist) catch-up pass

Date: 2026-04-21
Scope: `CrewAssist/` (mobile source of truth) + minimal additive API surface in `opsone-web/`.
Prereq: V2 web phases 1–16 complete (see `PHASE_V2_PROGRESS_LOG.md`).

## A — Detected mobile stack and run method

- **Stack**: Native iOS, Swift 6.2 / SwiftUI. Xcode project at
  `CrewAssist/CrewAssist.xcodeproj`.
- **Auth**: Bearer token persisted in iOS Keychain (`com.crewassist.api`).
  Migrated from UserDefaults on first launch. 401 fires `.userUnauthorized`
  → AppEnvironment forces logout.
- **API base URL**: `AppConfig.baseURL` — production `https://opsvelo.com/api`,
  override via `UserDefaults("api_base_url")`. Local dev default
  `http://localhost:8081/api`.
- **Decoder**: `.convertFromSnakeCase`, date format `yyyy-MM-dd HH:mm:ss` UTC.
- **Run method**:
  - Without Xcode installed, only `swiftc -parse`/`-typecheck` against the
    macOS SDK is possible (UIKit-dependent files can't fully resolve). All
    new Swift files parse clean; the V2 services/models subset typechecks
    clean against the macOS SDK.
  - Backend smoke-tested locally with `php -S 127.0.0.1:8081 -t public`
    against `database/crewassist.sqlite` (demo pilot token, user 245).

## B — Modules added/refined on mobile

All wired to **real** `/api/*` endpoints (no placeholders):

| Module             | Route in mobile nav (pilot) | Backed by                      |
|--------------------|-----------------------------|--------------------------------|
| My Flights         | `.myFlights`                | `/api/flights/mine|/{id}|/{id}/bag` |
| My FDM Events      | `.myFDM`                    | `/api/fdm/mine`, `/event/{id}/ack` |
| My Per Diem        | `.perDiem`                  | `/api/per-diem/mine|/rates|/submit` |
| My Training        | `.training`                 | `/api/training/mine`           |
| Appraisals         | `.appraisals`               | `/api/appraisals/mine|/about-me|POST /appraisals` |
| Help Hub           | `.help`                     | `/api/help/topics|/topic?slug=` |
| Notifications inbox| `.notifications`            | `/api/notifications*` (Phase 5) |
| Logbook (service)  | existing `.logbook`         | `/api/logbook/mine|POST /logbook` (service-only this pass) |

Home dashboard refined with:
- **Crew Quick Access** horizontal chips (Flights/FDM/Per Diem/Training/Appraisals/Help)
- **My Flights Upcoming** widget — live from `/api/flights/mine`
- **My FDM Pending** widget — live from `/api/fdm/mine`, counts unack events
- **Training Expiring** widget — live from `/api/training/mine`

Navigation:
- `AppModule` gained cases `.myFlights`, `.myFDM`, `.perDiem`, `.training`, `.appraisals`, `.help`
  with correct icons/groups. `ModuleGroup` assignments respect ops/safety/
  management/communications/personal buckets.
- `RoleConfig` updated so pilot, cabin crew, engineer, base manager, chief pilot,
  HR, and director all see the right sub-set (no admin leakage to crew).
- `AppModule.serverSlugMap` now unlocks new modules from tenant slugs
  (`per_diem`, `appraisals`, `help`, `training`, existing `fdm`, `flight_briefing`).
- `DashboardRouter.moduleDestination` and `AppSidebar.moduleContent` both
  wired to the new views.

## C — Files changed in CrewAssist

**New files (17)** — registered in `CrewAssist.xcodeproj/project.pbxproj` via
`add_phase_v2_crew_modules.py` (plutil-clean):

- `Core/Models/V2ModuleModels.swift`
- `Core/Services/RealFlightService.swift`
- `Core/Services/RealFDMService.swift`
- `Core/Services/RealPerDiemService.swift`
- `Core/Services/RealTrainingService.swift`
- `Core/Services/RealAppraisalService.swift`
- `Core/Services/RealNotificationInboxService.swift`
- `Core/Services/RealHelpService.swift`
- `Core/Services/RealLogbookService.swift`
- `Features/MyFlights/MyFlightsView.swift`  (list + detail + bag display)
- `Features/MyFDM/MyFDMView.swift`          (list + ack action)
- `Features/PerDiem/MyPerDiemView.swift`    (list + submit flow)
- `Features/Training/MyTrainingView.swift`
- `Features/Appraisals/AppraisalsView.swift` (about-me + mine tabs)
- `Features/Help/HelpView.swift`            (role-aware topics + topic viewer)
- `Features/Notifications/NotificationsInboxView.swift`  (Phase 5 inbox)
- `Features/Dashboards/V2DashboardWidgets.swift`

**Modified files:**
- `App/CrewAssistApp.swift` — inject new @EnvironmentObjects
- `App/DashboardRouter.swift` — route new modules
- `Core/Models/Models.swift` — AppModule cases/icons/group + serverSlugMap
- `Core/Config/RoleConfig.swift` — crew visibility
- `Core/DI/AppEnvironment.swift` — instantiate new real services (+@MainActor init)
- `DesignSystem/Components/AppSidebar.swift` — new modules in iPad sidebar
- `Features/Dashboards/MobileOperationalDashboard.swift` — new widgets

## D — Supporting backend/API changes in opsone-web

Minimal, additive. All routes bearer-token-gated via existing ApiAuthMiddleware;
all queries are tenant-scoped (`apiTenantId()`) and user-scoped
(`apiUser()['user_id']`).

**New API controllers (8)** — `opsone-web/app/ApiControllers/`:
- `FlightApiController.php` — GET `/api/flights/mine|/{id}|/{id}/bag`, GET `/api/flights/bag/{id}/download`
- `FdmApiController.php` — GET `/api/fdm/mine`, POST `/api/fdm/event/{id}/ack`
- `PerDiemApiController.php` — GET `/api/per-diem/mine|/rates`, POST `/api/per-diem/submit`
- `TrainingApiController.php` — GET `/api/training/mine`
- `AppraisalApiController.php` — GET `/api/appraisals/mine|/about-me`, POST `/api/appraisals` (confidentiality-aware)
- `LogbookApiController.php` — GET `/api/logbook/mine`, POST `/api/logbook`
- `NotificationApiController.php` — GET `/api/notifications|/counts`, POST `/.../read|/ack|/read-all`
- `HelpApiController.php` — GET `/api/help/topics|/topic?slug=`

**Routes added** to `opsone-web/config/routes.php` (20 entries).

**No schema changes.** The controllers reuse Phase 4–16 tables already shipped
(flights, flight_bag_files, fdm_events, per_diem_*, training_*, appraisals,
flight_logs, notifications).

## E — Local run result

Local PHP dev server (`php -S 127.0.0.1:8081 -t public`) with demo pilot token
(user 245 @ tenant 1). Each endpoint verified with `curl -H "Authorization: Bearer ..."`:

| Endpoint                              | Result        |
|---------------------------------------|---------------|
| GET `/api/flights/mine`               | `{"flights":[]}` (no seeded flights) |
| GET `/api/fdm/mine`                   | `{"events":[]}` |
| GET `/api/per-diem/mine`              | seed claim returned, verified shape |
| GET `/api/per-diem/rates`             | 2 seeded rates (Kenya, UAE) |
| POST `/api/per-diem/submit`           | ✅ `{"success":true,"claim_id":4,"amount":360,"currency":"USD"}` |
| GET `/api/training/mine`              | `{"records":[]}` |
| GET `/api/appraisals/mine`            | `{"appraisals":[]}` |
| GET `/api/appraisals/about-me`        | `{"appraisals":[]}` |
| POST `/api/appraisals`                | ✅ `{"success":true,"id":3}` |
| GET `/api/logbook/mine`               | 1 seeded entry, totals correct |
| POST `/api/logbook`                   | ✅ `{"success":true,"id":2,"block_minutes":285,"air_minutes":255}` |
| GET `/api/notifications`              | `{"notifications":[]}` |
| GET `/api/notifications/counts`       | `{"total":0,"unread":0,"unack":0}` |
| GET `/api/help/topics`                | 12 role-aware topics returned |
| GET `/api/help/topic?slug=getting-started` | HTML body streamed |

**Swift side:**
- `swiftc -parse` clean on every new file.
- `swiftc -typecheck` of the full V2 models+services subset (including
  existing APIClient/MockServices/Services/RealAuthService/AppEnvironment)
  passes with **0 errors, 2 pre-existing warnings** (unrelated Sendable
  captures in `MockAuditService` and `RealDeviceSyncService`).
- Full-project typecheck requires the **iphonesimulator SDK** (Xcode).
  Attempted with macOS SDK surfaced only the pre-existing UIKit-dependent
  colour extension in `DesignSystem/Colors.swift`, not any V2 code.

**xcodeproj:**
- 17 new file references + build files inserted via `add_phase_v2_crew_modules.py`.
- `plutil -lint` reports OK.
- Pre-V2 backup kept at `CrewAssist.xcodeproj/project.pbxproj.pre_v2`.

**Middleware fix applied:**
- Existing API controllers use `apiUser()['user_id']`; the bundled
  `SafetyApiController` was the outlier using `['id']` (which resolves to
  the api_token PK, not the user id). All V2 controllers follow the
  `user_id` convention — matches DeviceApi/PersonnelApi/UserApi.

## F — What was browser/preview/simulator checked

- **Browser**: PHP web portal not re-audited in this pass — V1/V2 web is
  already live per `PHASE_V2_PROGRESS_LOG.md`.
- **Preview / simulator**: **not run** — Xcode still downloading on host.
- **curl**: every new mobile endpoint exercised with a live pilot token
  against the local PHP server, verified JSON shape against the Swift
  `Codable` models.

## G — What still requires Xcode

1. Open `CrewAssist/CrewAssist.xcodeproj`.
2. Select iPad simulator (e.g. "iPad Pro (12.9-inch)") and/or iPhone 15.
3. **Build** — expected to succeed; all 17 new files are already in the
   target's Sources build phase.
4. Run to simulator and exercise the flows:
   - Log in as a pilot / cabin crew (demo seed).
   - Home → verify new widgets render (Quick Access, My Flights upcoming,
     My FDM pending, Training Expiring).
   - Sidebar (iPad): expand Operations/Communications/Management/Safety
     groups — the new modules must be visible.
   - TabView (iPhone): first 5 visible modules shown; remainder via More.
   - Tap each: `.myFlights`, `.myFDM`, `.perDiem`, `.training`,
     `.appraisals`, `.help`, `.notifications`.
   - Per Diem → Submit → pick a rate → confirm. Should POST and refresh.
   - Appraisals → switch tabs About Me / Mine.
   - Help → open a topic → body renders (markdown or stripped HTML).
5. Physical-device QA for Keychain bearer auth and hardware GPS
   interactions on the duty reporting screen.

If the Xcode build does flag UIKit import issues after new Xcode versions,
the `DesignSystem/Colors.swift` UIKit imports may need to be replaced by
SwiftUI-native `Color(red:green:blue:)` literals — not touched in this pass.

## H — Exact steps for user validation

```bash
# 1) Mobile: open the Xcode project after Xcode finishes installing
open /Users/samic/Desktop/Antigravity/CrewAssist/CrewAssist.xcodeproj

# 2) Local backend — for the simulator to reach it
cd /Users/samic/Desktop/Antigravity/opsone-web
php -S 127.0.0.1:8081 -t public public/index.php

# 3) In the simulator's CrewAssist app, override the API URL once:
#    Settings app → UserDefaults-inspector or via Swift:
#       UserDefaults.standard.set("http://host.docker.internal:8081/api",
#                                  forKey: AppConfig.baseURLKey)
#    (Simulator uses `127.0.0.1` directly; device uses the Mac's LAN IP.)

# 4) Log in as demo.pilot@opsvelo.com (seeded password or grant via admin)
#    Exercise My Flights / Per Diem / Training / FDM / Appraisals / Help.

# 5) Verify endpoint-level behavior:
TOKEN=... # from /api/auth/login
curl -s -H "Authorization: Bearer $TOKEN" http://127.0.0.1:8081/api/help/topics | head
curl -s -H "Authorization: Bearer $TOKEN" http://127.0.0.1:8081/api/per-diem/rates
```

## I — Is it ready to commit/push?

**Mostly yes — one round of Xcode simulator validation is outstanding.**

- Backend changes (PHP): complete, syntax-clean, endpoint-verified.
- Mobile changes (Swift): semantically clean, all new files registered
  in the Xcode project.
- **Blocker before ship**: run the app in the iOS simulator once Xcode is
  available, confirm there are no linker complaints about the 17 new
  files, and that at least `MyFlightsView` + `MyPerDiemView` render against
  the local backend.

## Notes / follow-ups

- **Mobile Logbook UI** still uses `MockDataProvider.logbookEntries`. The
  new `RealLogbookInboxService` is wired in the DI container but the
  existing `LogbookView.swift` has not been refactored in this pass — a
  focused rewrite is the obvious next step.
- **Help topic rendering** is plain-text/markdown-only. For rich HTML a
  `WKWebView` wrapper would be cleaner — deferred.
- **Flight bag downloads** resolve the URL but don't yet stream the bytes
  in-app; current UX shows a contextual URL on long-press. Add a PDF
  previewer pass in the next mobile iteration.
- **Push notifications** not touched — the in-app inbox is live via
  `/api/notifications`, which is the pre-requisite for push wiring later.
