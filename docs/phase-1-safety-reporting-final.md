# Phase 1 Safety Reporting — Final Implementation Summary

## What Was Built

### Phase 1.0 — Core Reporting Infrastructure
- Template-based safety report submission system (web + iPad)
- Single `safety_reports` table with `extra_fields` JSON for type-specific fields
- 9 report type slugs: `general_hazard`, `flight_crew_occurrence`, `maintenance_engineering`, `ground_ops`, `quality`, `hse`, `tcas`, `environmental`, `frat`
- `SafetyController` (web) and `SafetyApiController` (iPad) with tenant isolation enforced at every query
- Role-gated submission by report type via `SafetyReportModel::TYPE_ROLES`
- Anonymous reporting — `is_anonymous = 1` substitutes reporter identity at the model layer
- Reference number generation (`SafetyReportModel::generateReference()`)
- Status lifecycle: `draft → submitted → under_review → investigation → action_in_progress → closed`
- Permitted transition enforcement (`SafetyController::PERMITTED_TRANSITIONS`)
- In-app notifications per status transition via `NotificationService`
- Just Culture statement display on submission confirmation (`SafetySubmitSuccessView`)

### Phase 1.1 — Workflow Depth and Safety Team Tools
- `safety_report_threads` — threaded discussion with `public` / `internal` message types
- `safety_report_status_history` — immutable append-only audit log of all status transitions
- `safety_report_assignments` — assignment tracking per report
- Safety team queue view with status filter and aggregate dashboard counters
- Report detail view with thread, attachment list, status history, and assignment panel
- Safety publications (`safety_publications`, `safety_publication_audiences`)
- `safety_module_settings` — per-tenant configuration: enabled types, Just Culture statement, contact info
- Settings management for `safety_officer` and `airline_admin`
- AuditService integration for all safety team actions

### Phase 1.2 — Risk Matrix, Actions, Quick Report Mode, and UX Cleanup
- 5x5 ICAO aviation risk matrix: reporter initial assessment + safety team final assessment
- Corrective action management: `safety_actions` table with full lifecycle
- Nightly MySQL EVENT `mark_overdue_safety_actions` (runs 01:00 UTC)
- Quick Report mode for immediate capture in operational environments
- Progressive disclosure UX: collapsible sections, auto-fill from user profile and roster
- Actions queue web view (`/safety/team/actions`)
- Navigation and labeling cleanup: "Safety Reports", "Operational Notices"
- Dashboard counters for overdue and open actions

---

## Final Feature Set (Phase 1.2 Complete)

- 9 configurable report types, tenant-selectable via `safety_module_settings.enabled_types`
- Quick Report (5 fields, immediate) and Full Report (complete structured form) modes
- Progressive disclosure: reporter section collapsed and auto-filled; advanced type-specific sections in DisclosureGroup
- 5x5 ICAO risk matrix (likelihood A-E x severity 1-5) with reporter initial rating and safety team final rating
- Anonymous reporting with full Just Culture compliance (EU Reg 376/2014 Article 16)
- Safety team cannot overwrite original submitted content — amendments via public thread only
- Threaded discussion: public (reporter-visible) and internal (safety-team-only) message types
- Corrective action management: create, assign, update, track to completion
- Overdue action auto-marking via MySQL EVENT
- Full status lifecycle with permitted-transition enforcement and audit trail
- Immutable status history log
- Safety publications with reporter-identity validation before save
- Per-tenant module configuration
- Role-gated report types enforced at controller and model layers
- In-app notifications for all workflow events (push and email channels stubbed)
- iPad: tile grid of enabled report types, draft persistence, Quick and Full form views, SafetyMyReportsView
- FRAT with numeric risk scoring and colour-coded risk bands

---

## Architecture Decisions

### Template-Based Report System
A single `safety_reports` table with shared base fields and a JSON `extra_fields` column for type-specific data. A `template_version` column allows future schema evolution without breaking existing records. This keeps controller and model surface area small and allows iPad and web to share the same submission endpoint.

### Quick vs Full Report Modes
Two submission paths POST to the same controller methods. Quick Report captures 5 essential fields for immediate operational capture. Full Report provides complete structured documentation with type-specific sections. The `is_quick_report` flag stored in `extra_fields` distinguishes them in audit logs. A Quick Report in Draft status can be upgraded to Full Report editing by the reporter.

### Anonymous Reporting with Audit Preservation
`is_anonymous = 1` substitutes `'Anonymous'` for all reporter identity fields at the model layer in `SafetyReportModel::allForTenant()` and `SafetyReportModel::find()`. The raw `reporter_id` foreign key is retained in the database for platform-level traceability only, accessible solely to super admins via direct database query.

### Safety Team Cannot Overwrite Original Content
Once a report reaches `submitted` status, all base and extra fields are locked. Safety team corrections are made via public thread amendments. Internal notes are recorded via internal thread messages. This preserves the integrity of the original reporter's submission for regulatory purposes.

### Action Management Linked to Reports
`safety_actions` carries a `report_id` foreign key. Actions have owner, due date, status lifecycle, and full audit trail via AuditService. Reporters cannot create actions; they can only view actions attached to their own reports.

### Progressive Disclosure UX
The reporter section on the Full Report form is collapsed by default and auto-filled from the authenticated user's profile and roster data. Type-specific advanced sections are in DisclosureGroup components. This reduces form friction while preserving complete data capture.

### 5x5 ICAO Aviation Risk Matrix
Two risk assessments per report: reporter's initial assessment (submitted with the report) and safety team's final assessment (recorded at or before closure). Matrix axes: likelihood (A-E) x severity (1-5) per ICAO Doc 9859. Simple 1-5 initial risk score available in Quick Report mode.

### Role-Gated Report Types per Tenant Settings
`SafetyReportModel::TYPE_ROLES` defines allowed filer roles per type. `safety_module_settings.enabled_types` controls which types are active for the tenant. Both filters are applied before rendering forms and before accepting submissions. A type disabled at tenant level is hidden from all submission surfaces; existing reports of that type remain in the queue unaffected.

---

## Database Tables

| Table | Purpose |
|---|---|
| `safety_reports` | Core report record. Base fields, `extra_fields` JSON, `template_version`, status, severity, anonymity flag, reference number, timestamps. |
| `safety_report_threads` | Threaded discussion per report. `message_type` = `public` (reporter-visible) or `internal` (safety team only). |
| `safety_report_attachments` | File attachment metadata: `file_name`, `mime_type`, `file_size`, `storage_path`, uploader reference. |
| `safety_report_status_history` | Immutable append-only audit log of every status transition: `from_status`, `to_status`, `changed_by`, `change_reason`. |
| `safety_report_assignments` | Assignment history per report. Each assignment (and reassignment) is a row. |
| `safety_publications` | Safety bulletins and lessons-learned derived from one or more reports. Reporter identity validation enforced before save. |
| `safety_publication_audiences` | Junction table linking publications to target roles or departments. |
| `safety_module_settings` | Per-tenant configuration: `enabled_types` JSON, `just_culture_statement`, `safety_contact_name`, `safety_contact_email`. One row per tenant. |
| `safety_actions` | Corrective action records linked to reports. `assigned_to` (user FK), `assigned_by`, `assigned_role`, `due_date`, `status` ENUM, `completed_at`. |
| `safety_action_audit` | Audit trail for every action creation and status update, logged via AuditService with entity = `safety_actions`. |

> Migration origin: `safety_reports` and `safety_report_updates` were originally created in `database/patches/phase6_safety.sql` (SQLite) and formalised for MySQL in `database/migrations/019_phase0_safety_reports_mysql.sql`. Migration `020_phase1_safety_module.sql` ALTERs `safety_reports` and creates all Phase 1 additional tables. Migration `021_safety_actions.sql` creates `safety_actions`.

---

## API Endpoints

### Web (authenticated session)

#### Crew Routes
| Method | Path | Description |
|---|---|---|
| GET | `/safety/submit` | Submission form (enabled types filtered per tenant + role) |
| POST | `/safety/submit` | Submit report (quick or full) |
| GET | `/safety/my-reports` | Reporter's own report history |

#### Safety Team Routes
| Method | Path | Description |
|---|---|---|
| GET | `/safety` | Queue view with status filter and dashboard counters |
| GET | `/safety/report/{id}` | Full report detail with thread, attachments, status history, assignment |
| POST | `/safety/report/{id}/update` | Status change, severity, assignment, comment |
| GET | `/safety/report/{id}/thread` | Load thread messages |
| POST | `/safety/report/{id}/thread` | Post thread message (public or internal) |
| POST | `/safety/report/{id}/assign` | Assign report to staff member |
| POST | `/safety/report/{id}/status` | Change status (validated against permitted transitions) |
| POST | `/safety/team/report/{id}/action` | Create corrective action |
| POST | `/safety/team/action/{id}/update` | Update action status or details |
| GET | `/safety/team/actions` | All actions queue across all reports |
| GET | `/safety/publications` | List safety publications |
| GET | `/safety/publications/create` | New publication form |
| POST | `/safety/publications/store` | Save publication (validates no reporter identity in body) |
| GET | `/safety/settings` | Tenant safety module settings |
| POST | `/safety/settings/update` | Update tenant settings |

### iPad API (Bearer token)

| Method | Path | Description |
|---|---|---|
| GET | `/api/safety/reports` | Paginated report list (crew: own; safety team: all tenant) |
| POST | `/api/safety/reports` | Submit report. Returns `{ "reference_no": "...", "id": ... }` |
| POST | `/api/safety/reports/quick` | Submit quick report |
| GET | `/api/safety/reports/{id}` | Full report detail (tenant scoped, role-visibility applied) |
| GET | `/api/safety/types` | Enabled report type slugs and labels for tenant |
| GET | `/api/safety/settings` | `just_culture_statement` and contact fields for iPad display |

---

## Status Lifecycle

```
draft → submitted → under_review → investigation → action_in_progress → closed
                               ↘ closed (fast-close, no investigation required)
closed → reopened → under_review
```

Full status definitions, transition rules, badge colours, and notification events are documented in `docs/safety-workflow-statuses.md`.

---

## What Remains for Future Phases

| Feature | Status | Target Phase |
|---|---|---|
| APNS push notifications | Stubbed — `NotificationService::pushToDevice()` returns `false` | Phase 2 |
| Email confirmation to reporter | Stubbed — `NotificationService::emailUser()` not wired to SMTP | Phase 2 |
| Offline write queue (multi-draft, background sync) | Partial — single draft per type in `UserDefaults` only | Phase 2 |
| Attachment file transfer from iPad | Metadata schema in place; `POST /api/safety/reports/{id}/attachments` not built | Phase 2 |
| Dedicated `safety_staff` / `investigator` role (narrower permissions) | Phase 1 uses `safety_officer` for all team access | Phase 2 |
| Engineering manager supervised-reports visibility | Treated as crew-level in Phase 1 | Phase 2 |
| Safety analytics dashboard (occurrences by type/month, open vs closed trend) | Not started | Phase 8 |
| Dedicated iPad forms for `maintenance_engineering` and `flight_crew_occurrence` | `GeneralSafetyFormView` serves these in Phase 1 | Phase 1 stretch / Phase 2 |
| Overdue action scheduler notifications | Action marked overdue nightly; assignee notification on overdue is future work | Phase 2 |

---

## Migration Checklist — Production Deployment

All three migrations must be applied in order before deploying Phase 1.2 to production:

| Migration | File | Action |
|---|---|---|
| 019 | `database/migrations/019_phase0_safety_reports_mysql.sql` | Creates base `safety_reports` and `safety_report_updates` tables in MySQL |
| 020 | `database/migrations/020_phase1_safety_module.sql` | ALTERs `safety_reports`; creates all Phase 1 additional tables |
| 021 | `database/migrations/021_safety_actions.sql` | Creates `safety_actions` table and the `mark_overdue_safety_actions` MySQL EVENT |

> If applying to an environment that has run Phase 0 patches against a SQLite database, ensure a full schema reconciliation is performed before running these migrations. Do not run 020 before 019, and do not run 021 before 020.
