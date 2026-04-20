# Notification Framework

## 1. Overview

OpsOne/CrewAssist delivers notifications through three channels with a single dispatch API. Channels are additive — a single event can notify across all three simultaneously.

| Channel | Platform | Status |
|---|---|---|
| **In-App (web)** | Web portal notification bell | ✅ Implemented (Phase 0) |
| **In-App (iPad)** | CrewAssist badge + notice tab | ✅ Implemented (Phase 0) |
| **Push (APNS)** | iOS background/foreground push | 🔲 Stub — ready for certificates |
| **Email (SES/SMTP)** | Crew and admin inboxes | 🔲 Stub — ready for SMTP config |

---

## 2. Dispatch API

All notifications are dispatched through `NotificationService` in `app/Services/NotificationService.php`.

### Core dispatch method

```php
NotificationService::dispatch(
    channel: 'in_app',          // 'in_app' | 'push' | 'email'
    event:   'notice.published',
    context: [
        'user_id'   => 42,
        'tenant_id' => 7,
        'title'     => 'New Notice: Winter Ops Procedures',
        'body'      => 'Please review and acknowledge by 15 Dec.',
        'link'      => '/my-notices',
        'priority'  => 'important',
    ]
);
```

### Helper methods

```php
// Notify a single user across in_app channel
NotificationService::notifyUser(
    userId:   42,
    title:    'Your roster change was approved',
    body:     'Leave request #LR-2024-0031 has been approved.',
    link:     '/my-roster'
);

// Notify all active crew with a given role for a tenant
NotificationService::notifyTenant(
    tenantId: 7,
    role:     'pilot',
    title:    'New Notice: Winter Ops Procedures',
    body:     'Please review and acknowledge by 15 Dec.',
    link:     '/my-notices'
);
```

---

## 3. Notification Priority Levels

| Priority | Use Case | Delivery |
|---|---|---|
| `critical` | Airworthiness directive, emergency procedure, NOTAM-affecting ops | All channels simultaneously, badge turns red |
| `important` | Notice requires acknowledgement, roster change decision, safety report assigned | In-app + push |
| `normal` | New document available, roster published, general notice | In-app only (default) |
| `silent` | Background data refresh hint, sync trigger | Push only (no visible badge increment) |

Priority is stored in the `notifications` table `priority` column and drives both the badge colour in the web portal and the `apns-priority` / `content-available` flags in push payloads.

---

## 4. Notification Database Schema

The `notifications` table (created by migration `019_phase0_safety_reports_mysql.sql`, Section B) stores all in-app notifications:

```sql
CREATE TABLE notifications (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id   INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    title       VARCHAR(255) NOT NULL,
    body        TEXT,
    link        VARCHAR(500),
    priority    ENUM('critical','important','normal','silent') NOT NULL DEFAULT 'normal',
    event       VARCHAR(100),          -- e.g. 'notice.published', 'roster.approved'
    read_at     TIMESTAMP NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    INDEX idx_notifications_user   (user_id, read_at),
    INDEX idx_notifications_tenant (tenant_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Read/unread state:** `read_at IS NULL` = unread. The web portal marks notifications read when the bell dropdown opens or when the user navigates to the linked page.

---

## 5. Event Taxonomy

The following events are defined. Each row specifies which channels fire and the default priority.

### Notices

| Event | Trigger | Channels | Priority | Notes |
|---|---|---|---|---|
| `notice.published` | Admin publishes a notice | in_app + push | important | Sent to all crew matching role visibility |
| `notice.ack_reminder` | 24h after publish, if crew hasn't acked | in_app + push | important | Only for `requires_ack = 1` notices |
| `notice.expiring` | 48h before `expires_at` | in_app | normal | Admin reminder to renew |

### Roster

| Event | Trigger | Channels | Priority | Notes |
|---|---|---|---|---|
| `roster.published` | Scheduler publishes a roster period | in_app + push | important | Sent to all affected crew |
| `roster.change_approved` | Roster change request approved | in_app + push | important | Sent to requesting crew member |
| `roster.change_rejected` | Roster change request rejected | in_app | normal | Includes rejection reason |
| `roster.change_received` | New swap/leave request received | in_app | normal | Sent to scheduler / base manager |

### Safety Reports

| Event | Trigger | Channels | Priority | Notes |
|---|---|---|---|---|
| `safety.submitted` | Crew submits a safety report | in_app | normal | Sent to Safety Manager |
| `safety.assigned` | Report assigned to investigator | in_app + push | important | Sent to assignee |
| `safety.status_changed` | Report moves to new status | in_app | normal | Sent to reporter + assignee |
| `safety.closed` | Report closed with findings | in_app + email | important | Full outcome summary to reporter |

### Documents

| Event | Trigger | Channels | Priority | Notes |
|---|---|---|---|---|
| `document.published` | New document published | in_app | normal | Sent to crew with access |
| `document.expiring_soon` | 30 days before `expires_at` | in_app | important | Sent to document controller |
| `document.expired` | Past `expires_at` | in_app + email | critical | Airworthiness concern — sent to DQ + admin |

### System

| Event | Trigger | Channels | Priority | Notes |
|---|---|---|---|---|
| `device.approved` | Device approval granted | in_app + push | normal | Sent to device owner |
| `device.revoked` | Device access revoked | push | critical | Forces logout on device |
| `retention.purge_completed` | Nightly retention job completes | in_app | silent | Admin-only, summary stats |

---

## 6. iPad Notification System (Implemented — Phase 0)

### Badge logic

`NotificationBellButton` in CrewAssist shows a combined badge:

```swift
var badgeCount: Int {
    noticeService.unreadCount + noticeService.pendingAckCount
}
var badgeColor: Color {
    noticeService.pendingAckCount > 0 ? .orange : .red
}
```

The badge turns amber (orange) when there are pending acknowledgements, red when there are only unread notices.

### Notice tabs

`NotificationsView` has three tabs driven by a `FilterMode` enum:
- **All** — all notices
- **Unread** — `read_at == nil`
- **Needs Acknowledgement** — `requiresAck && !isAcknowledged`

### NoticeRow states

| State | Visual |
|---|---|
| Unread + Needs Ack | Amber border glow + "Acknowledge" button with spinner during submission |
| Unread | Normal row, no border |
| Read + Acknowledged | Green "✓ Acknowledged [timestamp]" strip |
| Read, no ack required | Faded row |

### Acknowledgement persistence

Acknowledgements are written to `UserDefaults["notice_ack_state_v1"]` immediately on tap. The server call fires in the background. This means the button disappears instantly even on poor connectivity.

---

## 7. Web Portal Notification Bell (Planned)

A notification bell will be added to the admin portal navigation bar in a future phase. It will:

1. Show unread `notifications` count via API `GET /api/notifications/unread-count`
2. Render a dropdown of the 5 most recent notifications on click
3. Mark all as read via `POST /api/notifications/mark-read` when dropdown opens
4. Include a "View all" link to a full notification inbox page

---

## 8. Push Notifications — APNS Integration Plan

When APNS certificates are provisioned:

### Server side

1. Store device APNS tokens in `devices.push_token` column (add via migration)
2. In `NotificationService::dispatchPush()`, replace the `error_log()` stub with an HTTP/2 call to `api.sandbox.push.apple.com` / `api.push.apple.com`
3. Payload structure:
   ```json
   {
     "aps": {
       "alert": { "title": "New Notice", "body": "Winter Ops Procedures published" },
       "badge": 3,
       "sound": "default",
       "content-available": 1
     },
     "event": "notice.published",
     "notice_id": 42,
     "link": "/my-notices"
   }
   ```
4. APNS error handling: `410 Gone` → revoke token in `devices` table; `429 Too Many Requests` → backoff

### iPad side

1. Request push permission in `AppDelegate.didFinishLaunching` via `UNUserNotificationCenter.requestAuthorization`
2. Register device token via `UIApplication.registerForRemoteNotifications`
3. On `didRegisterForRemoteNotificationsWithDeviceToken`: POST to `PUT /api/device/push-token`
4. Handle `UNUserNotificationCenterDelegate` methods:
   - `willPresent`: show in-app banner when app is foregrounded
   - `didReceive`: navigate to relevant screen (parse `link` from payload)

---

## 9. Email Notifications — Integration Plan

When email is configured (SMTP or AWS SES):

### Server side

1. Add SMTP credentials to `tenant_settings` or platform `.env`:
   ```
   MAIL_HOST=email-smtp.eu-west-1.amazonaws.com
   MAIL_USER=AKIA...
   MAIL_PASS=...
   MAIL_FROM=noreply@crewassist.io
   ```
2. Replace the `error_log()` stub in `NotificationService::dispatchEmail()` with PHPMailer or a direct SMTP call
3. Template system: `views/emails/{event}.php` — rendered as HTML with airline branding via `tenant_settings.logo_url`

### Per-user preferences (future)

A `user_notification_prefs` table will allow crew to opt out of email for non-critical events:

```sql
CREATE TABLE user_notification_prefs (
    user_id     INT UNSIGNED NOT NULL,
    event       VARCHAR(100) NOT NULL,
    channel     ENUM('push','email') NOT NULL,
    enabled     TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (user_id, event, channel)
);
```

`critical` priority events cannot be opted out of.

---

## 10. Tracking Read / Open / Acknowledged States

| State | Where tracked | How |
|---|---|---|
| **Delivered** | `notifications.created_at` | Row existence |
| **Read (web)** | `notifications.read_at` | TIMESTAMP set on bell open |
| **Read (iPad)** | `SyncStore.notice_ack_state_v1` + server `notice_reads.read_at` | Local + server |
| **Acknowledged** | `notice_reads.acknowledged_at` | Explicit crew action |
| **Push delivered** | APNS delivery receipts (future) | APNS feedback service |
| **Email opened** | Tracking pixel (future, optional) | SES open event webhook |

---

## 11. Adding Notifications to a New Module

1. Define your events in this document under Section 5 (event taxonomy table)
2. Call `NotificationService::notifyUser()` or `notifyTenant()` from the relevant controller
3. Specify channels and priority based on the event's operational urgency
4. If the event should appear in the iPad notice tab: ensure the `in_app` channel creates a row in `notifications` with a deep link
5. Update `module-governance-matrix.md` to mark `notification_triggers: yes` for the module
