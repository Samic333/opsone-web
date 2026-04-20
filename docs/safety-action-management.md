# Safety Corrective Action Management

## Overview

Corrective actions allow the safety team to assign specific remedial tasks to responsible parties directly from a safety report. Actions have an owner, due date, status lifecycle, and full audit trail. They represent the operational output of the investigation phase — the concrete steps taken to eliminate or mitigate the identified hazard.

Actions are linked to a specific report and inherit that report's tenant scope. All action management in Phase 1.2 is web-first. The iPad provides read-only action visibility for reporters.

---

## Action Lifecycle

```
open → in_progress → completed
     ↘ overdue (auto)
     ↘ cancelled
```

| Status | Description |
|---|---|
| `open` | Action created, not yet started by the assignee |
| `in_progress` | Assignee has acknowledged the action and is working on it |
| `completed` | Assignee or safety team has marked the action done; `completed_at` timestamp recorded |
| `overdue` | Action has passed its `due_date` without reaching `completed`; set automatically by the nightly MySQL EVENT |
| `cancelled` | Action is no longer required; cancelled by the safety team |

### Overdue Auto-Marking

The MySQL EVENT `mark_overdue_safety_actions` runs nightly at 01:00 UTC and executes:

```sql
UPDATE safety_actions
SET status = 'overdue'
WHERE status IN ('open', 'in_progress')
  AND due_date < CURDATE();
```

This ensures the safety team's action queue accurately reflects urgency without requiring manual triage.

---

## Who Can Create Actions

Safety team roles only: `safety_manager`, `safety_staff`, `airline_admin`, `super_admin`.

Reporters cannot create actions. They can view actions that have been assigned from their own report via the reporter-facing report detail view, but cannot modify, add, or respond to action records.

---

## Who Can Be Assigned

Actions support two assignment mechanisms:

| Mechanism | Field | Behaviour |
|---|---|---|
| Specific user | `assigned_to` (FK to `users.id`, nullable) | Action is linked to a named user who receives an in-app notification on creation |
| Role-based guidance | `assigned_role` (VARCHAR — role slug) | Used when no specific user is identified; serves as routing guidance for queue management |

Both can be set simultaneously. If a specific user is assigned, they are the primary responsible party. `assigned_role` provides context for audit and reporting.

---

## Database Schema

```sql
safety_actions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id       INT UNSIGNED NOT NULL REFERENCES safety_reports(id),
    tenant_id       INT UNSIGNED NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    title           VARCHAR(255) NOT NULL,
    description     TEXT,
    assigned_to     INT UNSIGNED NULL REFERENCES users(id),
    assigned_by     INT UNSIGNED NOT NULL REFERENCES users(id),
    assigned_role   VARCHAR(100) NULL,
    due_date        DATE NOT NULL,
    status          ENUM('open','in_progress','completed','overdue','cancelled') NOT NULL DEFAULT 'open',
    completed_at    DATETIME NULL,
    created_at      DATETIME NOT NULL DEFAULT NOW(),
    updated_at      DATETIME NOT NULL DEFAULT NOW() ON UPDATE NOW()
)
```

Migration file: `database/migrations/021_safety_actions.sql`

---

## Web Routes

| Method | Path | Description |
|---|---|---|
| POST | `/safety/team/report/{id}/action` | Create a new corrective action linked to report |
| POST | `/safety/team/action/{id}/update` | Update action status, due date, description, or assignee |
| GET | `/safety/team/actions` | All actions queue across all reports, filterable by status |

All three routes require `safety_officer` or `airline_admin` role (`RbacMiddleware::requireRole()`).

---

## iPad

Actions are read-only on iPad in Phase 1.2. Reporters can see actions associated with their own reports in the report detail view. Safety team action creation and status management is web-first.

Future phases may extend the iPad to allow safety team members to update action status from the app.

---

## Dashboard Counters

The safety dashboard header shows two action counters:

| Counter | Query condition | Display style |
|---|---|---|
| Overdue Actions | `status = 'overdue'` | Red — requires immediate attention |
| Open Actions | `status IN ('open', 'in_progress')` | Neutral — active workload indicator |

These counters are scoped to the authenticated user's tenant and computed by `SafetyController::index()`.

---

## Notifications

On action creation, the assigned user (if `assigned_to` is set) receives an in-app notification linking directly to the report. The notification body is:

> "A corrective action has been assigned to you for report {reference_no}: {action title}"

Notification is dispatched via `NotificationService::dispatch()` using the `in_app` channel. Push and email channels are stubbed in Phase 1.2.

When an action becomes `overdue`, a notification to the assignee is a planned future enhancement requiring a scheduler or cron integration.

---

## Audit Trail

Every action creation and every status update is logged via `AuditService::log()`:

```php
AuditService::log(
    context:    'web',
    userId:     $currentUser['id'],
    tenantId:   $tenantId,
    action:     'safety_action.created' | 'safety_action.updated',
    entity:     'safety_actions',
    entityId:   $actionId,
    details:    [
        'report_id'     => $reportId,
        'title'         => $title,
        'assigned_to'   => $assignedTo,
        'assigned_role' => $assignedRole,
        'due_date'      => $dueDate,
        'status'        => $status,
    ]
);
```

These entries write to `audit_logs`, subject to the platform's 3-year retention policy (`RetentionService::DEFAULTS['audit_log'] = 1095`).
