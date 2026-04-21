# Phase 3 → Phase 6 Extension — Personnel Compliance Record System

## Overview

The Personnel Compliance Record System extends the original Phase 3 crew
profile + licensing foundation into a full, role-aware compliance module
covering every category of staff in an airline tenant: pilots, cabin crew,
engineers, base managers, schedulers, document control, HR, safety,
training, and any other station-assigned personnel.

The module is deliberately **not** a simple employee profile page. It is a
controlled compliance system designed to serve as the source of truth for:

- roster / scheduling eligibility
- expiry alerting
- HR / admin compliance review
- management visibility
- later training-due logic
- station / assignment readiness

## What was already in place (Phase 3)

- `crew_profiles` table (1:1 with `users`) — personal, emergency, passport,
  medical, contract fields
- `licenses` table — one or many licences per user
- `qualifications` table — type ratings, endorsements, courses
- `CrewProfileModel`, `QualificationModel`
- `CrewProfileController` (admin list + self-service `/my-profile`)
- `ComplianceController` with high-level dashboard
- iPad `ProfileView`, `LicensesView`

## What Phase 6 adds

### Data model
- `crew_documents` — unified document vault with approval lifecycle
- `compliance_change_requests` — approval workflow for sensitive changes
- `role_required_documents` — role-based required document catalogue
  (system defaults + tenant overrides)
- `expiry_alerts` — notification ledger (crew / HR / line manager)
- `emergency_contacts` — additional/secondary contacts
- Extended `crew_profiles` (visa, address, profile photo)
- Extended `licenses` / `qualifications` (approval, file link, pending CR)
- Added `users.line_manager_id` for alert routing

### Services
- `EligibilityService` — computes assignment readiness per staff member,
  returning `eligible | warning | blocked` + reasons + structured details
- `ChangeRequestApplier` — atomic approval + mutation of profile / license
  / qualification / document / emergency_contact / assignment targets
- `ExpiryAlertService` — tenant-wide scan that records open alerts for
  everything expiring within configured windows

### Controllers (web)
- `CrewDocumentController` — reviewer list, per-staff documents, approve /
  reject / revoke, download
- `ChangeRequestController` — reviewer queue, per-request review, approve /
  reject / request-info / mark-review; crew submit / withdraw
- `EligibilityController` — tenant-wide readiness list + single-staff detail
- Extended `ComplianceController` with `/compliance/expiring`,
  `/compliance/missing`, and `POST /compliance/alert-scan`

### API (iPad consumption)
- `GET /api/personnel/documents` — user's documents
- `GET /api/personnel/required-docs` — role-required docs for current user
- `GET /api/personnel/eligibility` — user's readiness
- `GET /api/personnel/eligibility/{id}` — reviewer: any staff
- `GET /api/personnel/change-requests` — user's CRs
- `POST /api/personnel/change-request` — submit CR
- `POST /api/personnel/change-requests/{id}/withdraw` — withdraw

### Views (web)
- `views/personnel/documents_index.php`
- `views/personnel/documents_user.php`
- `views/personnel/change_requests_index.php`
- `views/personnel/change_request_review.php`
- `views/personnel/my_change_requests.php`
- `views/personnel/eligibility_index.php`
- `views/personnel/eligibility_show.php`
- `views/compliance/expiring.php`
- `views/compliance/missing.php`
- Extensions to `views/compliance/index.php`, `views/crew/my_profile.php`,
  `views/layouts/app.php` (sidebar)

### iPad (CrewAssist)
- Models: `CrewDocument`, `ComplianceChangeRequest`, `RequiredDocument`,
  `Eligibility`
- Service: `RealComplianceService`
- Views: `PersonnelDocumentsView`, `SubmitDocumentChangeRequestSheet`,
  `MyChangeRequestsView`, `EligibilityBadge`
- Extended `ProfileView` with links to the above + readiness badge

## Editing model (A2)

- Staff can directly edit **limited personal fields** via `/my-profile`:
  `phone`, `emergency_name`, `emergency_phone`, `emergency_relation`.
- Everything else — licence data, medical, passport, visa, contract,
  uploaded documents, role/base/department assignment — goes through
  `compliance_change_requests` and requires **company approval** before
  the change takes effect.
- The original approved record remains visible and in effect until a
  replacement change request is approved.

## Expiry alerts (C4)

Alerts go to three recipients:

1. **Crew / user** — owns the expiring record
2. **HR** — all users holding the `hr` role in the tenant
3. **Line manager / base manager** — `users.line_manager_id`

`ExpiryAlertService::scanTenant()` records entries in `expiry_alerts` with
`alert_level` of `warning`, `critical`, or `expired`. The ledger dedupes by
`(tenant, user, entity_type, entity_id, alert_level)` — the actual email /
push dispatch is intended to plug into `NotificationService` (a later job).

## Roster dependency

The roster / scheduling module will consume
`EligibilityService::computeForUser($id)` (and the bulk variant) as a
pre-check before duty assignment. A `blocked` status prevents assignment;
`warning` produces a soft flag for the scheduler.

## See also

- [`personnel-compliance-data-model.md`](personnel-compliance-data-model.md)
- [`compliance-change-approval-flow.md`](compliance-change-approval-flow.md)
- [`eligibility-and-readiness-model.md`](eligibility-and-readiness-model.md)
- [`role-permission-matrix.md`](role-permission-matrix.md) (Phase 3)
