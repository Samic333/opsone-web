# Safety Management — Navigation & Dashboard Refinement
**Date:** 2026-04-20  
**Scope:** Sidebar navigation restructure, default landing page, dashboard layout, privilege model.

---

## Summary of Changes

### 1. Default Landing Page for Safety Management
**Before:** Clicking "Safety Management" in the sidebar opened `/safety`, which displayed the crew-facing "Start a Report" submission home page.  
**After:** Safety-team users are immediately redirected to `/safety/dashboard` when they hit `/safety`.

**Where it's implemented:**
- `SafetyController::home()` — added early redirect if user has any TEAM_ROLES.
- The sidebar "Safety Dashboard" link now points directly to `/safety/dashboard`.

---

### 2. Sidebar Navigation Restructure

**Safety section — before:**
```
Safety (section)
  🚨 Safety Management  →  /safety
  📊 FDM Data
  ✅ Compliance
```

**Safety section — after (for safety_officer / airline_admin):**
```
Safety (section)
  📊 Safety Dashboard       →  /safety/dashboard
  📋 Reports Queue          →  /safety/queue
  ⚙️  Corrective Actions    →  /safety/team/actions
  📢 Publications           →  /safety/publications
  ✏️  Submit a Report       →  /safety/select-type   (safety staff can also submit)
  🔧 Safety Settings        →  /safety/settings      (safety_officer + airline_admin only)
  📈 FDM Data               →  /fdm
  ✅ Compliance             →  /compliance
```

**Me section — before (included safety_officer):**
```
Me (section)
  👤 My Profile
  📬 Operational Notices
  🛡️ Safety Reports  →  /safety/my-reports
```

**Me section — after:**
- `safety_officer` removed from the Me section condition — they use the team queue, not `/safety/my-reports`.
- Remaining crew roles still see `🛡️ My Safety Reports → /safety/my-reports`.
- Label changed from "Safety Reports" to "My Safety Reports" to disambiguate.

**File changed:** `views/layouts/app.php`

---

### 3. Active State Logic
| Link | Active when `$currentPath` starts with |
|------|---------------------------------------|
| Safety Dashboard | `/safety/dashboard` |
| Reports Queue | `/safety/queue` OR `/safety/team/report` |
| Corrective Actions | `/safety/team/actions` |
| Publications | `/safety/publication` |
| Submit a Report | `/safety/select-type`, `/safety/report/new`, `/safety/quick-report` |
| Safety Settings | `/safety/settings` |

---

### 4. Dashboard Layout Improvements
**Before:**
- Row 1: 4 large cards (Open, Under Review, Investigation, Action In Progress)  
- Row 2: 3 large cards (Overdue Actions, Open Actions, Closed This Month)
- Row 3: Severity breakdown
- Row 4: Recent Reports + Overdue Actions tables

**Stats bug fixed:** Dashboard previously referenced `$stats['under_review']` (undefined) instead of `$stats['by_status']['under_review']`. Fixed throughout.

**After:**
- **Row 1:** Compact 6-stat strip — Submitted / Under Review / Investigation / Action In Progress / Overdue Actions / Closed (clickable, each links to the filtered queue)
- **Alert bar:** Shown only when overdue actions > 0; red warning with direct link
- **Row 2:** Two-column — Recent Reports (up to 8) | Overdue Actions (up to 8)
- **Row 3:** Open pending actions table (shown only when actions exist)
- **Row 4:** Severity breakdown (condensed, lowest priority)

**File changed:** `views/safety/safety_dashboard.php`

---

### 5. Privilege-Based Module Model (Preserved & Confirmed)

Safety access is controlled by `SafetyController::TEAM_ROLES`:
```php
private const TEAM_ROLES = [
    'safety_manager',  // future role slug
    'safety_staff',    // future role slug
    'safety_officer',  // current DB slug
    'airline_admin',
    'super_admin',
];
```

- Any user assigned one of these roles gains full safety team access.
- `airline_admin` can assign `safety_officer` role to any user.
- All safety-team routes use `RbacMiddleware::requireRole(self::TEAM_ROLES)`.
- Settings restricted further to `safety_officer`, `airline_admin`, `super_admin`.
- Crew safety submission is open to all authenticated users (role-filtered by report type).

---

### 6. Back-link Fix in Team Detail View
**Before:** "← Safety Queue" button on team report detail linked to `/safety` (wrong — sent to crew home).  
**After:** Links to `/safety/queue`.

**File changed:** `views/safety/team_detail.php`

---

## Route Map (Safety Module)

### Crew Routes
| Method | Path | Controller | Notes |
|--------|------|------------|-------|
| GET | `/safety` | `home()` | Crew submission home; team users redirected to dashboard |
| GET | `/safety/select-type` | `selectType()` | Choose report type |
| GET | `/safety/report/new/{type}` | `reportForm()` | Full submission form |
| POST | `/safety/report/submit` | `submitReport()` | Submit report |
| GET | `/safety/my-reports` | `myReports()` | Reporter's report list |
| GET | `/safety/report/{id}` | `reportDetail()` | Reporter's report detail (public threads only) |
| POST | `/safety/report/{id}/reply` | `addReply()` | Reporter replies to safety team |
| GET | `/safety/follow-ups` | `myFollowUps()` | Reports with unanswered team messages |

### Safety Team Routes
| Method | Path | Controller | Notes |
|--------|------|------------|-------|
| GET | `/safety/dashboard` | `safetyDashboard()` | **Default team landing** |
| GET | `/safety/queue` | `index()` | Full report queue with filters |
| GET | `/safety/team/report/{id}` | `teamDetail()` | Team report detail with all tabs |
| POST | `/safety/team/report/{id}/reply` | `addTeamReply()` | Public reply (visible to reporter) |
| POST | `/safety/team/report/{id}/internal-note` | `addInternalNote()` | Internal-only note |
| POST | `/safety/team/report/{id}/status` | `updateStatus()` | Change report status |
| POST | `/safety/team/report/{id}/assign` | `assignReport()` | Assign investigator |
| GET | `/safety/team/actions` | `actionsQueue()` | All tenant corrective actions |
| GET | `/safety/publications` | `publications()` | Safety bulletins |
| GET | `/safety/settings` | `settings()` | Module config (senior roles only) |
