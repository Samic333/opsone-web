# Safety Report Workflow — Status Lifecycle

## Status Definitions

Every safety report carries a `status` column (`VARCHAR(50)`) on `safety_reports`. The following statuses are defined for Phase 1.

| Status | Slug | Set By | Description |
|---|---|---|---|
| Draft | `draft` | System (on save without submit) | Report has been saved but not yet submitted to the safety team. The reporter can edit all fields freely. The report does not appear in the safety team queue. |
| Submitted | `submitted` | Reporter | Report has been formally submitted. Content is locked from silent overwrites. The report enters the safety team queue. A reference number is confirmed at this point. |
| Under Review | `under_review` | Safety Manager / Safety Staff | The safety team has acknowledged receipt and is actively reviewing the report. The reporter receives an in-app notification of acknowledgement. |
| Investigation | `investigation` | Safety Manager | A formal investigation has been opened. The report is typically assigned to a staff member. Investigation notes are recorded via internal thread messages. |
| Action In Progress | `action_in_progress` | Safety Manager | Corrective or preventive actions are being taken. The report remains in the active queue until actions are complete. |
| Closed | `closed` | Safety Manager | The investigation is complete and findings have been recorded. The final severity is assigned. Reporter can view the closure summary. |
| Reopened | `reopened` | Safety Manager | A previously closed report has been reopened due to new information or a missed finding. Behaves identically to `under_review` for routing purposes. |

---

## Transition Rules

### Permitted Transitions

```
draft            → submitted         (by reporter — via "Submit Report" action)
submitted        → under_review      (by safety_officer or airline_admin)
under_review     → investigation     (by safety_officer)
under_review     → closed            (by safety_officer — fast-close if no investigation needed)
investigation    → action_in_progress(by safety_officer)
action_in_progress → closed          (by safety_officer)
closed           → reopened          (by safety_officer)
reopened         → under_review      (by safety_officer or airline_admin)
```

### Prohibited Transitions

The following transitions are explicitly blocked by `SafetyController::changeStatus()` and raise a `403` with an error flash if attempted:

| Attempted Transition | Reason |
|---|---|
| `submitted → draft` | Once submitted, a report enters the regulatory record. Reverting to draft is not permitted. The reporter should file an amendment via the thread instead. |
| `closed → submitted` | A closed report may only be reopened via the `reopened` status. Direct resubmission is blocked to preserve the audit chain. |
| `closed → under_review` | Must pass through `reopened` first to create a clear audit entry for the reopening event. |
| Any status → `draft` (except system-initiated on save) | Draft is a pre-submission state; no post-submission reversal to draft is allowed. |
| Skip-level transitions (e.g. `submitted → action_in_progress`) | Intermediate acknowledgement steps are required to maintain a coherent audit trail. |

---

## Reporter Editing Rules

| Report Status | Reporter Can Edit Fields | Reporter Can Add Thread Reply | Reporter Can View Internal Notes |
|---|---|---|---|
| `draft` | Yes — all base and extra fields | N/A (no thread yet) | No |
| `submitted` | No — fields are locked | Yes — public thread only | No |
| `under_review` | No | Yes — public thread only | No |
| `investigation` | No | Yes — public thread only | No |
| `action_in_progress` | No | Yes — public thread only | No |
| `closed` | No | No — thread is read-only | No — closure summary visible only |
| `reopened` | No | Yes — public thread only | No |

**Amendment process:** If a reporter needs to correct factual information after submission, they post a public thread message describing the correction. The safety team member with edit rights can apply the correction and note it in an internal thread message.

---

## Notification Events Per Status Change

`NotificationService::dispatch()` is called from `SafetyController::changeStatus()` after every successful status transition. In Phase 1, the `in_app` channel is live; `push` and `email` channels are stubbed.

| Transition | Recipient | Channel | Message |
|---|---|---|---|
| `draft → submitted` | Safety Manager (all in tenant) | in_app | New safety report submitted: {reference_no} — {title} |
| `submitted → under_review` | Reporter (if not anonymous) | in_app | Your report {reference_no} is now under review by the safety team. |
| `under_review → investigation` | Reporter (if not anonymous) | in_app | A formal investigation has been opened for report {reference_no}. |
| `under_review → investigation` | Assigned staff member | in_app | You have been assigned to investigate report {reference_no}. |
| `investigation → action_in_progress` | Reporter (if not anonymous) | in_app | Corrective actions are in progress for report {reference_no}. |
| `action_in_progress → closed` | Reporter (if not anonymous) | in_app | Report {reference_no} has been closed. You may view the findings summary. |
| `closed → reopened` | Safety Manager (all in tenant) | in_app | Report {reference_no} has been reopened. |
| `reopened → under_review` | Assigned staff member (if set) | in_app | Report {reference_no} has been returned to the review queue. |

**Anonymous reports:** Notifications to the reporter are suppressed when `is_anonymous = 1`. The safety team still receives all queue notifications.

**Push and email:** `NotificationService::pushToDevice()` and `NotificationService::emailUser()` are both stubbed and return `false` in Phase 1. These channels are planned for a future phase.

---

## Status Colors and Badges

Visual representation is consistent across the web portal and iPad app.

| Status | Color Name | Hex (approx) | SwiftUI Color | Web CSS Class |
|---|---|---|---|---|
| `draft` | Warning Yellow | `#F5A623` | `.statusWarning` | `badge-warning` |
| `submitted` | Info Blue | `#4A90D9` | `.statusInfo` | `badge-info` |
| `under_review` | Purple | `#7B5EA7` | `.statusPurple` | `badge-purple` |
| `investigation` | Orange | `#E8720C` | `.statusOrange` | `badge-orange` |
| `action_in_progress` | Amber | `#D4A017` | `.statusAmber` | `badge-amber` |
| `closed` | Success Green | `#27AE60` | `.statusSuccess` | `badge-success` |
| `reopened` | Critical Red | `#E74C3C` | `.statusCritical` | `badge-danger` |

In `ReportsView.swift` and `SafetyMyReportsView`, the status badge background uses `.opacity(0.2)` of the color with the full-opacity color applied to the text label, matching the pattern established for draft/submitted badges in the existing codebase.

---

## Audit Trail

Every status change produces a three-part audit record:

### 1. `safety_report_status_history` row

```sql
INSERT INTO safety_report_status_history
    (report_id, from_status, to_status, changed_by, change_reason, created_at)
VALUES
    (?, ?, ?, ?, ?, NOW())
```

This table is **append-only**. No rows are ever updated or deleted (outside of retention purge after 7 years per `RetentionService::DEFAULTS['safety_reports']`).

### 2. `AuditService::log()` entry

```php
AuditService::log(
    context:    'web',
    userId:     $currentUser['id'],
    tenantId:   $tenantId,
    action:     'safety_report.status_change',
    entity:     'safety_reports',
    entityId:   $reportId,
    details:    [
        'from'   => $fromStatus,
        'to'     => $toStatus,
        'reason' => $reason,
        'ref'    => $report['reference_no']
    ]
);
```

This writes to `audit_logs` which is subject to a 3-year default retention policy (`RetentionService::DEFAULTS['audit_log'] = 1095`).

### 3. `NotificationService` dispatch

As documented in the Notification Events table above. Notification rows in the `notifications` table are subject to a 90-day retention policy (`RetentionService::DEFAULTS['notifications'] = 90`).

---

## Status Validation in SafetyController

The permitted transition map is defined as a PHP constant in `SafetyController` and evaluated before any write:

```php
const PERMITTED_TRANSITIONS = [
    'draft'              => ['submitted'],
    'submitted'          => ['under_review'],
    'under_review'       => ['investigation', 'closed'],
    'investigation'      => ['action_in_progress'],
    'action_in_progress' => ['closed'],
    'closed'             => ['reopened'],
    'reopened'           => ['under_review'],
];
```

If the requested `to_status` is not in the array for the report's current status, the controller returns a `403` flash error: `"Transition from {from} to {to} is not permitted."` No write is performed.
