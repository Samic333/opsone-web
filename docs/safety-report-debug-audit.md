# Safety Module Debug Audit — Phase 1.2
**Date:** 2026-04-20  
**Scope:** End-to-end audit of the Safety Reporting web flow after Phase 1.2 implementation.  
**Trigger:** (1) Reporter receives 404 after viewing submitted report. (2) Safety team cannot see submitted reports in queue.

---

## Summary of Bugs Found and Fixed

| # | File | Bug | Fix |
|---|------|-----|-----|
| 1 | `views/safety/my_reports.php` | "View" button linked to `/safety/my-reports/{id}` (no such route) | Changed to `/safety/report/{id}` |
| 2 | `SafetyController::index()` | Loaded `views/safety/index.php` (old stub) instead of `queue.php` | Changed require to `queue.php` |
| 3 | `SafetyController::newPublication()` | Loaded `views/safety/new_publication.php` (didn't exist) | Changed require to `publication_form.php` |
| 4 | `SafetyController::addAction()` | `AuditService::log()` called with 6-arg legacy signature | Fixed to 4-arg canonical signature |
| 5 | `SafetyController::updateAction()` | Same AuditService signature mismatch | Fixed to 4-arg canonical signature |
| 6 | `views/safety/select_type.php` | File didn't exist — fatal error on `/safety/select-type` | Created the view |
| 7 | `views/safety/quick_report.php` | Form action pointed to `/safety/report/quick/{type}` (no such route) | Changed to `/safety/quick-report` |
| 8 | `views/safety/my_drafts.php` | "Continue" draft linked to `/safety/report/edit/{id}` (no route/method) | Added `editDraft()` controller method + route |
| 9 | `views/safety/my_drafts.php` | Delete form posted to `/safety/report/delete/{id}` (no route/method) | Added `deleteDraft()` controller method + route |
| 10 | `views/safety/my_reports.php` | Status filter tabs sent `?status=` but view never filtered `$reports` | Added PHP `array_filter()` in view before rendering |

---

## Root Cause Analysis

### Bug 1 — Reporter 404 (PRIMARY)
The most impactful bug. After submitting a report, `/safety/my-reports` showed the correct list but the "View" button linked to `/safety/my-reports/{id}`. That route does not exist — only `GET /safety/report/(\d+)` maps to `reportDetail()`. Every reporter who tried to view their own submission got a 404.

### Bug 2 — Safety Team Queue Empty (PRIMARY)
`GET /safety/queue` is routed to `SafetyController::index()`. The method was loading `views/safety/index.php` — an old minimal stub from before Phase 1 redesign. The queue view with stats cards, filter tabs, and the report table lives in `views/safety/queue.php`. Safety team members saw a broken old page with none of the submitted reports visible.

### Bug 3 — Publications Creation Fatal Error
`newPublication()` tried to require `views/safety/new_publication.php`. The Phase 1 implementation created the file as `publication_form.php`. Any attempt to create a publication caused a fatal PHP error.

### Bugs 4 & 5 — AuditService Signature Mismatch
The canonical `AuditService::log()` signature is:
```php
AuditService::log(string $action, ?string $entityType, ?int $entityId, mixed $details): void
```
The `addAction()` and `updateAction()` methods used the old 6-argument form:
```php
AuditService::log($userId, $tenantId, $action, $entity, $id, $details)
```
This would throw a PHP `TypeError` on any action creation or update, crashing those flows entirely.

### Bug 6 — Select Type Page Fatal Error
`GET /safety/select-type` → `selectType()` → `require views/safety/select_type.php`. The file was never created. Users clicking "Start a Report" from the safety home would hit a fatal PHP error.

### Bug 7 — Quick Report Submit 404
`quick_report.php` form action was `/safety/report/quick/{type}`. The actual route is `POST /safety/quick-report`. Every quick report submission resulted in a 404.

### Bugs 8 & 9 — Draft Management Broken
`my_drafts.php` linked to `/safety/report/edit/{id}` and `/safety/report/delete/{id}`. Neither route existed and neither controller method existed. Draft management was completely non-functional.

### Bug 10 — Filter Tabs Cosmetic Issue
The filter tabs in `my_reports.php` passed `?status=` in the URL but the view never applied filtering to the `$reports` array. All tabs always showed all reports.

---

## Files Changed

### Modified
- `app/Controllers/SafetyController.php` — fixed `index()`, `newPublication()`, `addAction()`, `updateAction()`; added `editDraft()` and `deleteDraft()` methods
- `config/routes.php` — added `GET /safety/report/edit/(\d+)` and `POST /safety/report/delete/(\d+)` routes
- `views/safety/my_reports.php` — fixed "View" button URL, added status filter logic
- `views/safety/quick_report.php` — fixed form action URL

### Created
- `views/safety/select_type.php` — type selection grid with icons, descriptions, and Quick Report shortcut

---

## Verified Clean
- All 16 `require VIEWS_PATH . '/safety/*'` calls in `SafetyController.php` now map to existing view files
- All routes referenced in safety views exist in `config/routes.php`
- All `AuditService::log()` calls in `SafetyController.php` use the canonical 3–4 arg signature
- `SafetyReportModel::allForTenant()` correctly filters `is_draft = 0` (team queue excludes drafts)
- `SafetyReportModel::forUser()` correctly filters `is_draft = 0` (reporter list excludes drafts)
- `SafetyReportModel::find()` fetches by `id` + `tenant_id` with no draft restriction (correct — allows viewing closed/submitted by ID)
- PHP syntax: 0 errors across all safety controller, model, and view files

---

## Flow Verification (Expected Post-Fix)

### Reporter Flow
1. `/safety` → home.php (role-aware, Quick/Full CTA) ✓
2. `/safety/select-type` → **select_type.php (NEW)** → type grid ✓
3. `/safety/report/new/{type}` → report_form.php (full form with risk matrix, prefill) ✓
4. `POST /safety/report/submit` → submitted, redirect to `/safety/my-reports` ✓
5. `/safety/my-reports` → my_reports.php → table with "View" → `/safety/report/{id}` **FIXED** ✓
6. `/safety/report/{id}` → report_detail.php (4 tabs: Overview, Discussion, Attachments, History) ✓
7. `/safety/quick-report/{type}` → quick_report.php → `POST /safety/quick-report` **FIXED** ✓
8. `/safety/drafts` → my_drafts.php → "Continue" → `/safety/report/edit/{id}` **FIXED** ✓
9. Draft delete → `POST /safety/report/delete/{id}` **FIXED** ✓

### Safety Team Flow
1. `/safety/dashboard` → safety_dashboard.php (stats + overdue marking) ✓
2. `/safety/queue` → **queue.php (FIXED, was loading index.php)** → report list with filter tabs ✓
3. `/safety/team/report/{id}` → team_detail.php (6 tabs: Overview, Discussion, Actions, Internal Notes, Attachments, History) ✓
4. Status changes, assignments, internal notes, actions → all correct routes ✓
5. `POST /safety/team/report/{id}/action` → `addAction()` → **AuditService fixed** ✓
6. `POST /safety/team/action/{id}/update` → `updateAction()` → **AuditService fixed** ✓
7. `/safety/publications/new` → **publication_form.php (FIXED, was new_publication.php)** ✓
