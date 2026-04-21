# Compliance Change — Approval Flow

Every change to a sensitive compliance field flows through
`compliance_change_requests`. The original approved record stays in
effect until a CR is approved and applied.

```
┌──────────┐   submit    ┌───────────┐   reviewer   ┌───────────────┐
│  Crew    │ ──────────▶ │ submitted │ ───────────▶ │ under_review  │
└──────────┘             └───────────┘              └───────────────┘
                               │                       │    │    │
                               │ withdraw              │    │    └─ reject ─┐
                               ▼                       │    │               ▼
                         ┌───────────┐                 │    └─ request_info ▶ info_requested
                         │ withdrawn │                 │                          │
                         └───────────┘                 └─ approve (applier) ▶ approved
                                                                                  │
                                                                  target mutated ─┘
```

## Target entities

A CR targets one of:

- `profile` — `crew_profiles` row (one per user)
- `license` — a row in `licenses`
- `qualification` — a row in `qualifications`
- `document` — a row in `crew_documents` (usually just flipping status to valid)
- `emergency_contact` — a row in `emergency_contacts`
- `assignment` — user's `department_id / base_id / line_manager_id / status`

`target_id` is NULL for `create`, set for `update / delete / replace`.

## Submission

### Web
`POST /my-profile/change-requests/submit` with form fields:
- `target_entity` (one of the above)
- `target_id` (optional)
- `change_type` (defaults to `update`)
- whitelisted payload fields (see `ChangeRequestController::payloadWhitelist`)
- `supporting_file` (optional file upload; becomes a `crew_documents` row
  with `status = 'pending_approval'`)

### iPad
`POST /api/personnel/change-request`
```json
{
  "target_entity": "profile",
  "change_type":   "update",
  "payload": {
    "medical_class": "Class 1",
    "medical_expiry": "2027-04-01"
  }
}
```

## Review

Reviewers are: `airline_admin`, `hr`, `chief_pilot`, `head_cabin_crew`,
`engineering_manager`, `training_admin`, `super_admin`.

### Transitions
- `submitted → under_review` via `POST /personnel/change-requests/{id}/mark-review`
- `submitted | under_review | info_requested → approved`
  via `POST /personnel/change-requests/{id}/approve`
- `submitted | under_review | info_requested → rejected`
  via `POST /personnel/change-requests/{id}/reject` (notes required)
- `submitted | under_review → info_requested`
  via `POST /personnel/change-requests/{id}/request-info` (notes required)
- `submitted | info_requested → withdrawn` (self-service only)

### Approval side effects

`ChangeRequestApplier::apply($requestId, $reviewerId, $notes)` wraps the
mutation in a transaction:

1. Mark the CR `approved` with reviewer id, notes, timestamp.
2. Dispatch to the target applier:
   - `profile` → `CrewProfileModel::save()` with whitelisted merge
   - `license` create/update/delete on `licenses`
   - `qualification` create/update/delete on `qualifications`
   - `document` → `CrewDocumentModel::approve($docId, $reviewer)`,
     cascading `revoked` to any row this one replaces
   - `emergency_contact` create/update/delete on `emergency_contacts`
   - `assignment` → update `users` fields
3. Audit log: `compliance.change_request.approved` with details.

### Rejection
Keeps the proposed payload stored for audit; does not mutate the target.
A supporting document (if any) remains `pending_approval` until revoked —
consider adding auto-revoke on reject if UX calls for it.

## Audit trail

Every action lands in `audit_logs` via `AuditService`:

- `compliance.change_request.submitted`
- `compliance.change_request.under_review`
- `compliance.change_request.info_requested`
- `compliance.change_request.approved`
- `compliance.change_request.rejected`
- `compliance.change_request.withdrawn`
- `compliance.document.uploaded`
- `compliance.document.approved` / `rejected` / `revoked` / `downloaded`

## Document upload specifics

When a crew member uploads a scan (via `/my-profile` or the iPad
`SubmitDocumentChangeRequestSheet`), the flow is:

1. File written to `storage/crew_documents/{tenant}/{user}/{safe_name}`.
2. `crew_documents` row inserted with `status = 'pending_approval'`.
3. A `compliance_change_requests` row is inserted with
   `target_entity = 'document'` and `supporting_document_id = <new doc id>`.
4. On approval, `CrewDocumentModel::approve` flips the doc to `valid`
   and — if `replaces_document_id` is set — revokes the superseded doc.
