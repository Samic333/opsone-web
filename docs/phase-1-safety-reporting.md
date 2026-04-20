# Phase 1 — Safety Reporting Module

## Overview

Phase 1 delivers a production-grade, multi-tenant aviation safety reporting module for the OpsOne platform. The module enables airline crew to submit confidential safety occurrence reports and provides safety management teams with a structured workflow for review, investigation, and closure.

### Regulatory Context

The module is designed to support compliance with the following regulatory frameworks:

- **EU Regulation 376/2014** — Mandatory reporting of aviation safety occurrences. Requires that all safety reports be collected, stored, and processed in a manner that protects reporters from punitive action. Article 16 mandates application of the Just Culture principle.
- **ICAO Annex 13** — Aircraft Accident and Incident Investigation. Establishes standards for occurrence reporting, investigation processes, and minimum 7-year retention of safety records.
- **ICAO Doc 9859 (SMS Manual)** — Safety Management System framework informing the hazard identification, risk assessment, and corrective action lifecycle embedded in the status workflow.

### Just Culture Principle

Per EU Regulation 376/2014 Article 16 and the Just Culture framework, reporters are legally protected from prosecution, disciplinary action, or adverse treatment for unintentional errors disclosed through the safety reporting system. The platform enforces this in two ways:

1. **Anonymous reporting** — `is_anonymous = 1` removes all reporter identity from every view, including safety team views. The system records `reporter_id` for internal traceability but substitutes `'Anonymous'` in all queries via `SafetyReportModel::allForTenant()` and `SafetyReportModel::find()`.
2. **Access control** — Reporter identity is restricted to `safety_officer` and `airline_admin` roles only. Colleagues cannot view who filed a report.

---

## Architecture

### Template-Based Report System

Rather than maintaining separate tables or controllers per report type, the module uses a single `safety_reports` table with shared base fields and a JSON column (`extra_fields`) for type-specific data. This design:

- Minimises schema migration cost when adding new report types
- Keeps the controller and model surface area small
- Allows the iPad and web form to share the same submission endpoint

A `template_version` column on `safety_reports` allows future schema evolution of `extra_fields` without breaking existing records.

### Report Type Slugs and Labels

| Slug | Label |
|---|---|
| `general_hazard` | General Hazard |
| `flight_crew_occurrence` | FHR / Flight Crew Occurrence |
| `maintenance_engineering` | MOR / Maintenance Engineering |
| `ground_ops` | Ground Ops Hazard |
| `quality` | Quality Form |
| `hse` | HSE Report |
| `tcas` | TCAS Report |
| `environmental` | Environmental |
| `frat` | Flight Risk Assessment Tool (FRAT) |

These slugs are reflected in `SafetyReportModel::TYPES` and in the iPad `SafetyReportsListView` tile grid.

### Status Lifecycle

```
draft → submitted → under_review → investigation → action_in_progress → closed
                                ↘ closed (no investigation path)
closed → reopened → under_review
```

Full status definitions and transition rules are documented in `docs/safety-workflow-statuses.md`.

### Tenant Isolation

Tenant isolation is enforced at every layer:

- All Phase 1 safety tables carry `tenant_id NOT NULL` with a foreign key to `tenants(id) ON DELETE CASCADE`
- `SafetyReportModel::allForTenant()`, `find()`, and `forUser()` all scope queries by `tenant_id`
- `SafetyController` calls `currentTenantId()` on every request; no cross-tenant access is architecturally possible
- Per-tenant module configuration is stored in `safety_module_settings` (enabled report types, Just Culture statement text, contact information)

---

## Database Schema

### Phase 1 Tables

> Note: `safety_reports` and `safety_report_updates` were originally created in `database/patches/phase6_safety.sql` (SQLite) and formalised for MySQL in `database/migrations/019_phase0_safety_reports_mysql.sql`. The Phase 1 migration `020_phase1_safety_module.sql` ALTERs `safety_reports` to add the new columns and creates all additional tables below.

| Table | Purpose |
|---|---|
| `safety_reports` | Core report record. Carries all base fields, `extra_fields` JSON, `template_version`, status, severity, anonymity flag, reference number, and timestamps. Altered from Phase 0 baseline to add `extra_fields`, `template_version`, `event_utc_time`, `event_local_time`, `location_name`, `icao_code`, `occurrence_type`, `event_type`, `initial_risk_score`, `reporter_position`. |
| `safety_report_threads` | Threaded discussion per report. Replaces the simpler `safety_report_updates` table. Each row carries `message_type` (`public` or `internal`), allowing the same table to serve both reporter-visible messages and safety-team-only internal notes. |
| `safety_report_attachments` | File attachment metadata. Stores `file_name`, `mime_type`, `file_size`, `storage_path`, and uploader reference. Actual file transfer from iPad is deferred (MVP stores metadata only). |
| `safety_report_status_history` | Immutable audit log of every status transition. Records `from_status`, `to_status`, `changed_by`, and `change_reason`. |
| `safety_report_assignments` | Tracks assignment history. A safety manager can assign a report to a safety staff member; each assignment is a row, allowing reassignment tracking. |
| `safety_publications` | Safety bulletins and lessons-learned publications derived from one or more reports. Authored by safety team; must not include identifiable reporter information. |
| `safety_publication_audiences` | Junction table linking `safety_publications` to the roles or departments that should receive the publication. |
| `safety_module_settings` | Per-tenant configuration: `enabled_types` (JSON array of active report type slugs), `just_culture_statement` (custom text for the submission form), `safety_contact_name`, `safety_contact_email`. One row per `tenant_id`. |

---

## Web Routes

### Crew Routes (authenticated, any role)

| Method | Path | Controller | Method |
|---|---|---|---|
| `GET` | `/safety/submit` | `SafetyController` | `submitForm()` |
| `POST` | `/safety/submit` | `SafetyController` | `submit()` |
| `GET` | `/safety/my-reports` | `SafetyController` | `myReports()` |

### Safety Team Routes (requires `safety_officer` or `airline_admin`)

| Method | Path | Controller | Method |
|---|---|---|---|
| `GET` | `/safety` | `SafetyController` | `index()` |
| `GET` | `/safety/report/{id}` | `SafetyController` | `view()` |
| `POST` | `/safety/report/{id}/update` | `SafetyController` | `update()` |
| `GET` | `/safety/report/{id}/thread` | `SafetyController` | `thread()` |
| `POST` | `/safety/report/{id}/thread` | `SafetyController` | `postThread()` |
| `POST` | `/safety/report/{id}/assign` | `SafetyController` | `assign()` |
| `POST` | `/safety/report/{id}/status` | `SafetyController` | `changeStatus()` |
| `GET` | `/safety/publications` | `SafetyController` | `publications()` |
| `GET` | `/safety/publications/create` | `SafetyController` | `createPublication()` |
| `POST` | `/safety/publications/store` | `SafetyController` | `storePublication()` |
| `GET` | `/safety/settings` | `SafetyController` | `settings()` |
| `POST` | `/safety/settings/update` | `SafetyController` | `updateSettings()` |

These routes are defined in `config/routes.php` under the Phase 6 / Phase 1 safety block.

### API Routes (iPad, Bearer token)

| Method | Path | Controller | Method |
|---|---|---|---|
| `GET` | `/api/safety/reports` | `SafetyApiController` | `index()` |
| `POST` | `/api/safety/reports` | `SafetyApiController` | `store()` |
| `GET` | `/api/safety/reports/{id}` | `SafetyApiController` | `show()` |
| `GET` | `/api/safety/types` | `SafetyApiController` | `types()` |
| `GET` | `/api/safety/settings` | `SafetyApiController` | `moduleSettings()` |

---

## Controllers

### SafetyController (`app/Controllers/SafetyController.php`)

**Crew methods (open to all authenticated users):**

| Method | Description |
|---|---|
| `submitForm()` | Renders the submission form (`views/safety/submit.php`). Pulls enabled types from `safety_module_settings` to populate the type selector. |
| `submit()` | Validates required fields (`title`, `description`), generates a `reference_no` via `SafetyReportModel::generateReference()`, inserts the report with `status = 'draft'` or `'submitted'` depending on the `save_draft` flag, logs to `AuditService`. |
| `myReports()` | Returns the reporter's own reports via `SafetyReportModel::forUser()`. Shows status badges but not internal notes. |

**Safety team methods (requires `safety_officer` or `airline_admin`):**

| Method | Description |
|---|---|
| `index()` | Queue view with status filter (`?status=open|submitted|under_review|investigation|action_in_progress|closed|all`). Computes aggregate counts for the dashboard header. |
| `view()` | Full report detail with thread, attachment list, status history, and assignment panel. |
| `update()` | Processes status change, severity change, assignment, and/or comment in a single POST. Delegates to `SafetyReportModel::addUpdate()`. Validates CSRF. |
| `thread()` | Loads thread messages; filters out `message_type = 'internal'` for reporter-context requests. |
| `postThread()` | Inserts a row into `safety_report_threads`. Safety team can choose `internal`; crew are forced to `public`. |
| `assign()` | Creates a row in `safety_report_assignments`, updates `safety_reports.assigned_to`. |
| `changeStatus()` | Validates the transition is permitted (see `safety-workflow-statuses.md`), inserts into `safety_report_status_history`, calls `NotificationService`. |
| `publications()` | Lists `safety_publications` for the tenant. |
| `createPublication()` / `storePublication()` | Authors a bulletin linked to one or more report IDs. Validates that no identifiable reporter data is included. |
| `settings()` / `updateSettings()` | Reads and writes `safety_module_settings` for the tenant. |

### SafetyApiController (`app/Controllers/SafetyApiController.php`)

| Method | Description |
|---|---|
| `index()` | Returns paginated list of the authenticated user's own reports (crew context) or all tenant reports (safety team context). |
| `store()` | Accepts JSON body; validates required fields; creates report via `SafetyReportModel::submit()`. Returns `{ "reference_no": "...", "id": ... }`. |
| `show()` | Returns full report detail for one ID. Enforces tenant scope and reporter/team visibility rules. |
| `types()` | Returns the enabled report type slugs and labels for the tenant, sourced from `safety_module_settings`. iPad uses this to build the tile grid. |
| `moduleSettings()` | Returns `just_culture_statement` and `safety_contact_*` fields for display on the iPad submission flow. |

---

## iPad Module (CrewAssist)

### SafetyService Protocol

```swift
protocol SafetyService {
    func fetchReportTypes() async throws -> [SafetyReportType]
    func fetchMyReports() async throws -> [SafetyReportSummary]
    func submitReport(_ payload: SafetyReportPayload) async throws -> SafetySubmitResponse
    func fetchModuleSettings() async throws -> SafetyModuleSettings
}
```

### RealSafetyService

`RealSafetyService: SafetyService` performs authenticated calls to the `/api/safety/*` endpoints using the shared `APIClient` that handles Bearer token injection and tenant-scoped error handling.

Draft persistence uses `UserDefaults` keyed by `safety_draft_{reportType}`. A full offline write queue is deferred to a later phase.

### Views

| View | Description |
|---|---|
| `SafetyReportsListView` | Home screen tile grid — 9 report type cards using `SafetyReportTypeCard`. Fetches enabled types from `SafetyService.fetchReportTypes()`. |
| `SafetyReportTypeCard` | Tappable card; presents the appropriate form as a sheet. |
| `GeneralSafetyFormView` | Base form used for all types that do not have a dedicated specialist view. Collects title, event description, severity. |
| `FRATFormView` | Specialised FRAT checklist. Items grouped by category (Pilot, Environment, Mission). Computes a numeric risk score and displays a colour-coded risk band (Low / Caution / High). Requires Base Manager approval at High tier. |
| `SafetyMyReportsView` | Crew-facing history list; shows reference number, type, date, and status badge. |
| `SafetySubmitSuccessView` | Confirmation screen shown after successful submission. Displays the assigned reference number and the tenant's Just Culture statement. |

---

## What Is Not Yet Implemented

The following items are stubbed, deferred, or known gaps as of Phase 1:

| Feature | Status | Notes |
|---|---|---|
| APNS push notifications for safety events | Stub | `NotificationService` dispatches in-app only; push channel returns `false` at `NotificationService::pushToDevice()` |
| Email confirmation to reporter | Stub | `NotificationService::emailUser()` is defined but not wired to an SMTP transport |
| Offline write queue for safety drafts | Partial | iPad stores a single draft per report type in `UserDefaults`; multi-draft queue and background sync are Phase 2+ |
| Attachment uploads from iPad | Metadata only | `safety_report_attachments` schema is in place; file transfer endpoint (`POST /api/safety/reports/{id}/attachments`) is deferred |
| Safety analytics dashboard | Not started | Aggregate charts (occurrences by type/month, open vs closed trend) are planned for the Phase 8 compliance reporting module |
| `SafetyApiController` | Planned | The API controller is documented here and in routes but is not yet in the controller inventory from Phase 0; it is a Phase 1 deliverable |
| Specialist iPad forms for all 9 types | Partial | `FRATFormView` is complete; `GeneralSafetyFormView` serves remaining types; dedicated forms for `maintenance_engineering` and `flight_crew_occurrence` are Phase 1 stretch goals |
