# Safety Report — Thread & Follow-Up Flow
**Date:** 2026-04-20  
**Scope:** Discussion model, visibility rules, notification hooks, audit trail.

---

## Business Logic Overview

```
Pilot submits report
    ↓
Safety team opens report in team detail view
    ↓
Safety team can:
  (a) Add PUBLIC reply  → visible to reporter, fires notification
  (b) Add INTERNAL NOTE → hidden from reporter, team-only
    ↓
Reporter receives notification (if not anonymous)
    ↓
Reporter opens report detail → Discussion tab → sees team reply
Reporter replies (always public)
    ↓
Assigned investigator (or all safety team) notified of reporter reply
```

---

## Data Model

All messages live in `safety_report_threads`:

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK | |
| `report_id` | INT FK | Links to `safety_reports` |
| `author_id` | INT FK | User who wrote the message |
| `body` | TEXT | Message content |
| `is_internal` | TINYINT(1) | `0` = public (visible to reporter), `1` = internal (team only) |
| `parent_id` | INT FK NULL | For nested replies (future use) |
| `created_at` | TIMESTAMP | Set at insert time |

---

## Visibility Rules

| Message type | Reporter can see | Safety team can see |
|-------------|-----------------|-------------------|
| Public reply (`is_internal = 0`) | ✅ Yes | ✅ Yes |
| Internal note (`is_internal = 1`) | ❌ No | ✅ Yes |

**Implementation:**
- `SafetyReportModel::getThreads($reportId, $includeInternal)`
  - `false` → returns only `is_internal = 0` rows (reporter view)
  - `true` → returns all rows (team view)

**Reporter view** (`SafetyController::reportDetail`):
```php
$threads = SafetyReportModel::getThreads($id, false); // public only
```

**Team view** (`SafetyController::teamDetail`):
```php
$allThreads    = SafetyReportModel::getThreads($id, true);
$publicThreads = array_filter($allThreads, fn($t) => !(bool)($t['is_internal'] ?? false));
$internalNotes = array_filter($allThreads, fn($t) =>  (bool)($t['is_internal'] ?? false));
```

---

## Comment / Question Flow (Step by Step)

### 1. Safety Team Sends a Comment or Question to Reporter

**Route:** `POST /safety/team/report/{id}/reply`  
**Controller:** `SafetyController::addTeamReply()`

```php
SafetyReportModel::addThread($id, $user['id'], $body, false); // is_internal = false
AuditService::log('safety.team_reply_added', 'safety_reports', $id);

// Notify reporter if not anonymous
if (!$report['is_anonymous'] && $report['reporter_id']) {
    NotificationService::notifyUser(
        $report['reporter_id'],
        'Update on ' . $report['reference_no'],
        'The safety team has replied to your report ' . $report['reference_no'],
        '/safety/report/' . $id
    );
}
```

The reporter receives an in-app notification with a link directly to their report's Discussion tab.

---

### 2. Reporter Views the Comment

1. Reporter opens notification → directed to `/safety/report/{id}?tab=discussion`
2. `reportDetail()` loads `getThreads($id, false)` — only public messages
3. View renders messages in chat-bubble style:
   - Team messages: grey bubble, labelled "Safety Team · {name}"
   - Own messages: blue bubble, labelled "You"

**Internal notes are never passed to the reporter view** — they do not appear in the PHP output at all.

---

### 3. Reporter Replies

**Route:** `POST /safety/report/{id}/reply`  
**Controller:** `SafetyController::addReply()`

```php
SafetyReportModel::addThread($id, $user['id'], $body, false); // always public
AuditService::log('safety.reply_added', 'safety_reports', $id);

// Notify assigned investigator, or all safety team if unassigned
if ($report['assigned_to']) {
    NotificationService::notifyUser($report['assigned_to'], ...);
} else {
    self::notifySafetyTeam($tenantId, ...);
}
```

Reporter replies are always `is_internal = 0` — they can never accidentally create internal notes.

---

### 4. Safety Team Adds an Internal Note

**Route:** `POST /safety/team/report/{id}/internal-note`  
**Controller:** `SafetyController::addInternalNote()`

```php
SafetyReportModel::addThread($id, $user['id'], $body, true); // is_internal = true
AuditService::log('safety.internal_note_added', 'safety_reports', $id);
```

No notification sent to the reporter. Note is only visible in the team detail view under "Internal Notes" tab.

---

## Notification Matrix

| Event | Who gets notified | Channel |
|-------|-------------------|---------|
| New report submitted | All safety team roles | `notifySafetyTeam()` (3 role slugs) |
| Safety team replies (public) | Reporter (if not anonymous) | `notifyUser()` |
| Reporter replies | Assigned investigator (if set) | `notifyUser()` |
| Reporter replies (unassigned) | All safety team | `notifySafetyTeam()` |
| Status changed | Reporter (if not anonymous) | `notifyUser()` |
| Action assigned | Assignee | `notifyUser()` |
| Internal note added | Nobody outside team | — |

---

## Audit Trail

Every thread write goes through `AuditService::log()`:

| Event key | Trigger |
|-----------|---------|
| `safety.team_reply_added` | Safety team public reply |
| `safety.reply_added` | Reporter reply |
| `safety.internal_note_added` | Safety team internal note |
| `safety.report_submitted` | New report submitted |
| `safety.status_changed` | Status update |
| `safety.assigned` | Report assignment |

All entries include: `tenant_id`, `user_id`, `record_id` (report id), `record_type`, `created_at`.

---

## Privilege Checks

### Who can send a public reply to a report?
- **Reporter:** Own reports only (`report['reporter_id'] === user['id']`)
- **Safety team:** Any report in their tenant (`RbacMiddleware::requireRole(TEAM_ROLES)`)

### Who can add an internal note?
- **Safety team only** (`requireRole(TEAM_ROLES)`)

### Who can read the Discussion tab (reporter side)?
- Reporter of that report OR any TEAM_ROLE user

### Who can see internal notes?
- TEAM_ROLE users only (enforced in both controller query and UI tab)

---

## Original Report Protection

The submitted report body (`description`) is **never editable** via any UI or route:
- No "edit report" route exists for safety team
- Team detail overview shows the description in a read-only panel with a lock icon:
  > "🔒 Original submitted content — read only. Use Discussion or Internal Notes to add context."
- Only draft reports can be edited by the reporter (before submission)

---

## Follow-Up Queue

`GET /safety/follow-ups` → `SafetyController::myFollowUps()`

Shows the reporter a list of their reports where the safety team's last public message has not yet been replied to by the reporter. This surfaces "waiting for your response" cases prominently.

Implemented via `SafetyReportModel::followUpsForUser($tenantId, $userId)`.
