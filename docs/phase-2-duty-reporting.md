# Phase — Duty Reporting

> Operational crew check-in / clock-out module for pilots, cabin crew, and engineers,
> with management visibility on the web platform. Not a generic attendance tool —
> behaves like an airline duty-event system with role-based access, station awareness,
> geo-fence rules, auditability, and real-world exception handling.

## Scope

- iPad app (CrewAssist) is the primary crew surface: **Report for Duty**, **Clock Out**, duty state at a glance.
- Web (OpsOne-web) is the management surface: **On Duty Now**, **Duty History**, **Duty Exceptions**, **Settings**.
- All data is tenant-isolated, role-gated, and audit-logged.
- Designed to integrate later with Roster, Per Diem, and Flight Assignment logic.

## Architecture (where things live)

### Web platform (`opsone-web/`)

| Concern | File |
| --- | --- |
| Schema (MySQL) | `database/migrations/022_duty_reporting.sql` |
| Schema (SQLite dev) | `database/migrations/022_duty_reporting_sqlite.sql` |
| Dev SQLite applier | `database/apply_022_duty_reporting.php` |
| Seed row in module catalogue | `database/seeders/phase0_seed.php` (adds `duty_reporting`) |
| Domain models | `app/Models/DutyReport.php`, `DutyException.php`, `DutyReportingSettings.php` |
| Business logic | `app/Services/DutyReportingService.php` |
| Retention | `app/Services/RetentionService.php` (`duty_reporting` → 180 days default) |
| iPad API endpoints | `app/ApiControllers/DutyReportingApiController.php` |
| Admin web controller | `app/Controllers/DutyReportController.php` |
| Admin views | `views/duty-reporting/index.php`, `history.php`, `exceptions.php`, `detail.php`, `settings.php` |
| Sidebar gate | `views/layouts/app.php` (Duty Reporting section) |
| Routes | `config/routes.php` |

### iPad app (`CrewAssist/`)

| Concern | File |
| --- | --- |
| Domain models | `Core/Models/Models.swift` (DutyReport, DutyReportingState, DutyExceptionReason, …) |
| Service protocol + real impl | `Core/Services/DutyReportingService.swift` |
| Mock for previews / tests | `Core/Mocks/MockServices.swift` |
| DI wiring | `Core/DI/AppEnvironment.swift` |
| Main screen | `Features/DutyReporting/DutyReportingView.swift` |
| Dashboard card | `Features/DutyReporting/DutyStatusCard.swift` |
| Navigation wiring | `App/DashboardRouter.swift` |
| Location | `Core/Services/LocationManager.swift` |
| Biometrics | `Core/Services/BiometricAuthService.swift` |
| Offline queue | `Core/Services/SyncStore.swift` + `DutySyncManager.swift` |
| Info.plist | `App/Info.plist` (NSLocationWhenInUseUsageDescription) |

## Tables (new in migration 022)

### `duty_reports`
One row per check-in / check-out cycle. State lifecycle covers the full event: `checked_in → on_duty → checked_out`, with branches into `missed_report`, `exception_pending_review → exception_approved / exception_rejected`. Coordinates, base match, and geofence evaluation captured at check-in; clock-out fills the return-side fields and `duration_minutes`. Indexed on `(tenant_id, user_id)`, `(tenant_id, state)`, `(tenant_id, check_in_at_utc)`.

### `duty_exceptions`
Exception reasons with manager review lifecycle (`pending / approved / rejected`). Each row ties to a `duty_reports.id` via FK with cascade delete. Reason codes: `outside_geofence`, `gps_unavailable`, `offline`, `forgot_clock_out`, `wrong_base_detected`, `duplicate_attempt`, `outstation`, `manual_correction`, `other`.

### `duty_reporting_settings`
Per-tenant configuration (primary key = `tenant_id`). Covers: module enabled flag, allowed role CSV, geofence-required flag, default radius, outstation allowance, exception approval requirement, clock-out reminder window, trusted-device / biometric requirements, retention days. Default row auto-inserted for every active tenant by the migration, and lazily via `DutyReportingSettings::ensureRow()` for new tenants.

### `bases` (extended)
New nullable columns: `latitude`, `longitude`, `geofence_radius_m`, `timezone`. Nullable means geofence is optional per base — tenants adopt it incrementally without breaking existing bases.

### `modules` / `tenant_modules` / `module_capabilities`
A `duty_reporting` module row is inserted (sort 55, mobile-capable) alongside 10 capabilities: `view`, `check_in`, `clock_out`, `view_history`, `view_all`, `approve_exception`, `correct_record`, `manage_settings`, `export`, `view_audit`. Enabled by default for every active tenant.

## iPad API surface

Bearer-token auth via existing `ApiAuthMiddleware`. Every endpoint enforces module enablement, tenant `duty_reporting_settings.enabled`, and role allowance.

| Method | Path | Purpose |
| --- | --- | --- |
| `GET`  | `/api/duty-reporting/status`    | current state + settings + recent history |
| `POST` | `/api/duty-reporting/check-in`  | Report for Duty |
| `POST` | `/api/duty-reporting/clock-out` | Clock Out |
| `GET`  | `/api/duty-reporting/history`   | this user's history |
| `GET`  | `/api/duty-reporting/bases`     | bases + geo data for on-device matching |

Admin-side review flows live on the web, not on iPad — the API stays intentionally crew-focused.

## Integration points with other modules

- **Roster** — on check-in, `DutyReportingService::findRosterAssignment()` looks up today's row in `rosters` for this user and links it via `duty_reports.roster_id`. Non-fatal if absent.
- **Bases** — geofence match resolved via `DutyReportingService::resolveBase()` (Haversine). Per-base radius overrides the tenant default.
- **Notifications** — `NotificationService::notifyTenant` fires to `airline_admin` + `chief_pilot` on exception submission; `notifyUser` fires to the crew member on approve/reject.
- **Audit log** — every state-changing call routes through `AuditService::log` / `logApi`. Actions: `duty_reporting.check_in`, `duty_reporting.check_in.blocked`, `duty_reporting.clock_out`, `duty_reporting.clock_out.blocked`, `duty_reporting.exception.approved`, `duty_reporting.exception.rejected`, `duty_reporting.record.corrected`, `duty_reporting.settings.updated`.
- **Retention** — managed by `RetentionService` with `duty_reporting → duty_reports.created_at → 180 days` (tenant-overridable via `tenant_retention_policies`).
- **Trusted device** — `duty_reports.trusted_device_id` FK is set when the iPad passes the device id. Heartbeat/approval stays in `RealDeviceSyncService`.
- **Biometric** — iPad-side LocalAuthentication prompt; only a flag is persisted (`check_in_method = 'biometric'`). No biometric data leaves the device.

## Deploy checklist

Per MASTER_PHASE_PLAN:

1. phpMyAdmin → import `database/migrations/022_duty_reporting.sql`.
2. `cd /home/fruinxrj/acentoza.com && git pull origin main`.
3. (Optional) `php database/seeders/demo_seed.php` if seed data was updated.
4. Verify `/duty-reporting` loads for an airline admin and the sidebar entry appears.
5. Spot-check the iPad flow: open app → Report for Duty → status flips to On Duty.

## Out of scope for this phase

- Full push-notification / APNS for clock-out reminders (stub still logs to `error_log`).
- Per diem calculation from duty records (will consume `duration_minutes` later).
- Background job to auto-mark missed reports (service method `markOverdue` exists; scheduled task not yet wired).
- Export to CSV / PDF for duty history.
