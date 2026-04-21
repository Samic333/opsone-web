# Phase V2 Progress Log

> Source of truth for phase status. Resumable after context compaction —
> always re-read `MASTER_PLAN_V2_OPS1_COASSIST_PHASED_UPGRADE.md` first,
> then this log to pick up where work left off.

Last updated: 2026-04-21 (autonomous run complete — awaiting review)

## Status summary

| Phase | Title | Status | Migration | Key files |
|---|---|---|---|---|
| 0 | Foundation cleanup | ✅ done (V1) | — | — |
| 1 | Safety Reporting | ✅ done (V1) | — | — |
| 2 | Duty Reporting | ✅ done (V2) | — | `DutyReportController` (markOverdue wired) |
| 3 | Crew Profiles + Licensing | ✅ done (V2) | — | `CrewProfileModel::save` (visa/address preserved) |
| 4 | Manuals + Document Distribution | ✅ done (V2) | 024 | `FileModel`, `FileController`, dept/base targeting, version chain, read receipts, notifications |
| 5 | Notification Engine Refinement | ✅ done (V2) | 025 | priority/event/ack_required on `notifications`; `NotificationController` inbox + bell + API |
| 6 | Fleet Management + Aircraft | ✅ done (V2) | 026 | `Aircraft` model + `AircraftController`; aircraft, docs, maintenance; KPI dashboard |
| 7 | Electronic Logbook | ✅ done (V2) | 027 | `LogbookController`: my-logbook, admin overview, CSV export; auto block/airborne compute |
| 8 | Rostering Eligibility Gate | ✅ done (V2) | — | `EligibilityGate` service; override with audit; UI checkbox on assign form |
| 9 | Flight Assignment + Bag | ✅ done (V2) | 028 | `FlightController`; flights table, flight_bag_files; my-flights; publish notifies crew |
| 10 | FDM Refinement | ✅ done (V2) | 029 | pilot_user_id + pilot_ack_at on events; auto-match pilot from flights table; /my-fdm |
| 11 | Per Diem Management | ✅ done (V2) | 030 | `PerDiemController`; rates, claims, submit/approve/reject/pay |
| 12 | Training Management | ✅ done (V2) | 031 | `TrainingController`; training_types + training_records; auto-expiry from validity_months |
| 13 | Crew Appraisal | ✅ done (V2) | 032 | `AppraisalController`; draft/submitted/accepted workflow; confidentiality guard |
| 14 | HR Workflow Hardening | ✅ done (V2) | — | `HrController`; lifecycle KPIs, onboarding queue, probation, contract expiry, deactivate/reactivate UI |
| 15 | Help Hub | ✅ done (V2) | — | `HelpController`; role-aware topic list + 16 topic pages |
| 16 | Advanced Integrations | ✅ done (stubs) | 033 | `IntegrationsController`; integration registry + status transition + audit |

## Cross-cutting fixes landed during autonomous run

- **Dev-server static file bypass** (`public/index.php`) — CSS/JS serve directly under PHP built-in server.
- **NotificationService** — switched from dead `global $pdo` to `Database::getInstance()`.
- **`formatBytes()` helper** added to `Helpers/functions.php`.
- **`dbNow()` misuse in ack inserts** — replaced with `date('Y-m-d H:i:s')`.
- **FileController admin guard** moved out of constructor (was blocking crew from `/my-files`).
- **`documents` → `manuals`** module code corrected in ModuleAccess checks.
- **CrewProfileModel::save** fixed field list (visa/address/photo were being dropped).
- **DutyReportingService::markOverdue** now wired into duty dashboard load.
- **Sidebar navigation** extended with every new module + role-based visibility (see `views/layouts/app.php`).

## Database migrations introduced (Phases 4-16)

| # | File | Tables / Columns |
|---|---|---|
| 024 | `024_phase4_document_distribution*.sql` | `file_department_visibility`, `file_base_visibility`, `file_reads`, `files.replaces_file_id`, `files.superseded_at` |
| 025 | `025_phase5_notification_refinement*.sql` | `notifications.priority`, `.event`, `.ack_required`, `.acknowledged_at` + indexes |
| 026 | `026_phase6_aircraft_registry*.sql` | `aircraft`, `aircraft_documents`, `aircraft_maintenance` |
| 027 | `027_phase7_electronic_logbook*.sql` | `flight_logs` |
| 028 | `028_phase9_flight_bag*.sql` | `flights`, `flight_bag_files` |
| 029 | `029_phase10_fdm_refinement*.sql` | `fdm_events.pilot_user_id`, `.pilot_ack_at`, `.management_visible` |
| 030 | `030_phase11_per_diem*.sql` | `per_diem_rates`, `per_diem_claims` |
| 031 | `031_phase12_training*.sql` | `training_types`, `training_records` |
| 032 | `032_phase13_appraisals*.sql` | `appraisals` |
| 033 | `033_phase16_integrations*.sql` | `integrations` |

All migrations ship in both MySQL (`*.sql`) and SQLite (`*_sqlite.sql`) variants.
SQLite versions applied to local `database/crewassist.sqlite`; **MySQL versions must be run in production**.

## Local browser checks performed

- **Admin sidebar** (airadmin): Fleet → Aircraft Registry, People Ops → HR / Per Diem / Training / Logbook Overview / Appraisals Review, Admin → Integrations, Scheduling → Flights. All visible and functional.
- **Pilot sidebar** (pilot): Me → My Documents, Notifications, My Logbook, My FDM Events, My Flights, My Training, My Per Diem, Appraisals, Help. Badges live.
- **Notifications** (Phase 5): inbox loads 3 seeded rows with priority chips; mark-all-read hits DB; bell JSON = `{unread,unack,loud}`.
- **Aircraft detail** (Phase 6): Mark-done closes a maintenance item; overdue/expired rows highlighted.
- **Logbook** (Phase 7): pilot submit → block 1:58 / air 1:33 auto-computed → admin view tallies per-crew totals.
- **Roster assign** (Phase 8): operational duty on expired-licence pilot blocked; override checkbox lets through with audit trail noting both blockers.
- **Flights** (Phase 9): flight row + detail page + bag upload form render for admin; pilot sees "My Flights".
- **FDM** (Phase 10): pilot inbox shows 2 events; ack button present.
- **Per Diem** (Phase 11): admin approves a seeded claim; status flips to `approved` with reviewed_by timestamp.
- **Training** (Phase 12): dashboard KPIs render; crew view colour-codes days-to-expiry.
- **Appraisals** (Phase 13): accept flow flips status to `accepted`.
- **HR** (Phase 14): 5 KPI cards + 24 deactivate buttons; employment-status set works.
- **Help** (Phase 15): 16 topic files present; topic page loads.
- **Integrations** (Phase 16): Jeppesen row toggled `disabled → pending`; persisted.

Zero PHP warnings/errors in the server log across all the above flows.
