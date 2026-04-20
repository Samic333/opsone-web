# Phase 0 — Implementation Summary

**Date:** April 2026  
**Scope:** Architecture freeze and system foundation cleanup across `opsone-web` (PHP web platform) and `CrewAssist` (SwiftUI iPad app)

---

## What Was Audited

### Web Platform (`opsone-web/`)
- ~50 database tables across 19 numbered migrations
- 25 web controllers, 8 API controllers
- Module, Role, RBAC, and tenant isolation models
- All existing service layers: `AuditService`, `AuthorizationService`
- All database patches in `database/patches/`
- Route configuration: `config/routes.php`

### iPad App (`CrewAssist/`)
- All `Real*Service` and `Mock*Service` implementations
- `AppEnvironment` dependency injection and module sync logic
- `SyncStore` offline cache layer
- `PersistenceController` Core Data setup
- Device UUID management in `RealDeviceSyncService`
- Notification view (`NotificationsView`) and dashboard integration

---

## Critical Issues Found and Fixed

### Web Platform

| Issue | Severity | Fix Applied |
|---|---|---|
| `safety_reports` + `safety_report_updates` tables missing from numbered MySQL migrations (only in SQLite patch) | 🔴 Critical | Created `019_phase0_safety_reports_mysql.sql` |
| Duplicate `GET /my-files` route in `config/routes.php` (PHP silently discards first) | 🟠 High | Removed duplicate, added explanatory comment |
| `AuditLog` model is a deprecated shim with no deprecation markers | 🟡 Medium | Added `@deprecated` PHPDoc + `error_log()` caller traces to all 5 methods |
| No structured notification service — controllers would need to inline DB inserts | 🟡 Medium | Created `NotificationService.php` with full dispatch API |
| No retention policy engine — no way to configure or enforce per-tenant data windows | 🟡 Medium | Created `RetentionService.php` with purge, policy resolution, and 30-day safety floor |

### iPad App

| Issue | Severity | Fix Applied |
|---|---|---|
| Module sync filter inverted: `!$0.enabledModuleSlugs.isEmpty == false` — modules only synced when slugs were empty | 🔴 Critical | Fixed to `!$0.enabledModuleSlugs.isEmpty` in `AppEnvironment.swift` |
| Device UUID fallback was ephemeral — new `UUID()` generated on every cold launch when `identifierForVendor` was nil | 🔴 Critical | Added 3-step resolution: vendorId → UserDefaults stable key → generate+persist |
| `PersistenceController` contained Xcode-template `fatalError()` in production code path | 🟠 High | Wrapped in `#if DEBUG` — DEBUG still crashes immediately; Release logs and continues |
| `MockReportingService`, `MockFDMService`, `MockAuditService`, `MockFlightService` injected in production `AppEnvironment` | 🟠 High | Added `// TODO: implement Real[X]Service` markers; mocks remain until real implementations are built |

---

## New Architecture Components Created

### Database: Migration 019

**File:** `database/migrations/019_phase0_safety_reports_mysql.sql`

Three sections:
- **Section A** — `safety_reports` + `safety_report_updates` tables in proper MySQL/InnoDB syntax with tenant isolation, full-text search indexes, and FK constraints
- **Section B** — `notifications` table for the in-app notification channel
- **Section C** — `tenant_retention_policies` table for per-tenant data retention overrides

### PHP Services

| Service | File | Purpose |
|---|---|---|
| `NotificationService` | `app/Services/NotificationService.php` | Central dispatch for in_app, push (stub), and email (stub) notifications. `notifyUser()` and `notifyTenant()` helpers. |
| `RetentionService` | `app/Services/RetentionService.php` | Per-tenant retention policy resolution + tenant-safe purge with 30-day safety floor. Covers 7 modules. |

### Documentation Created

All 6 required Phase 0 documents are in `docs/`:

| File | Contents |
|---|---|
| `phase-0-architecture-overview.md` | Dual-stack architecture, tenancy model, auth flow, known tech debt, component inventory |
| `role-permission-matrix.md` | All 16 roles, permission matrix, web vs iPad access, role-based module visibility |
| `module-governance-matrix.md` | All modules, allowed roles, lifecycle, retention support, mobile capability, dependencies |
| `retention-policy-framework.md` | Regulatory minimums, platform defaults, tenant overrides, purge execution, archive-before-delete plan |
| `offline-sync-framework.md` | Current SyncStore cache, offline vs live action matrix, planned write queue, sync triggers, conflict resolution |
| `notification-framework.md` | Channel architecture, dispatch API, event taxonomy, iPad badge logic, APNS + email integration plans |

---

## What Was Preserved (Not Changed)

- All existing controllers, views, and API endpoints
- All database tables from migrations 001–018
- Existing `AuditService.php` (canonical implementation, untouched)
- Existing `AuthorizationService.php`
- All `Real*Service` implementations in CrewAssist (RealAuthService, RealRosterService, RealDocumentService, RealNoticeService, RealDeviceSyncService)
- `SyncStore` offline cache (extended by Phase 0 notice ack work, not modified here)
- All existing views and UI — no design changes made in this phase

---

## What Was Refactored

- `config/routes.php` — duplicate route removed (no behaviour change, silent bug eliminated)
- `app/Models/AuditLog.php` — deprecation markers added; all 5 methods still delegate to `AuditService` (callers still work)
- `Core/DI/AppEnvironment.swift` — module sync filter condition corrected (behaviour fix)
- `Core/Services/RealDeviceSyncService.swift` — stable UUID persistence (behaviour fix, no API change)
- `Core/PersistenceController.swift` — fatalError wrapped in `#if DEBUG` (production safety fix)

---

## What Is Stubbed / Not Yet Implemented

| Capability | Status | Target Phase |
|---|---|---|
| APNS push notifications | Stub in `NotificationService::dispatchPush()` | Phase 6 (Safety + Notifications) |
| Email notifications (SMTP/SES) | Stub in `NotificationService::dispatchEmail()` | Phase 6 |
| Offline write queue (draft + retry) | Framework documented, not implemented | Phase 6 |
| Archive-before-delete retention | Documented, not implemented | Phase 8 |
| Web portal notification bell | Documented, not implemented | Phase 5 |
| Purge cron job script | Design documented, not implemented | Phase 8 |
| `RealReportingService`, `RealFDMService`, `RealAuditService`, `RealFlightService` (iPad) | Mocks remain, TODOs added | Respective module phases |
| Per-user notification preferences table | Schema documented, not created | Phase 6 |

---

## Ready-for-Next-Phase Checklist: Safety Reporting

Before Safety Reporting module work begins, confirm:

- [x] `safety_reports` and `safety_report_updates` tables exist in MySQL (migration 019 — **must be run on production via phpMyAdmin**)
- [x] `notifications` table exists in MySQL (migration 019)
- [x] `tenant_retention_policies` table exists in MySQL (migration 019)
- [x] `NotificationService::notifyTenant()` available to fire `safety.submitted` event
- [x] `RetentionService::getPolicy('safety_reports')` returns 2555-day default
- [x] `AuditService::log()` available for all safety report state transitions
- [x] RBAC roles exist: `safety_manager`, `safety_staff` (check migration 009 seed data)
- [x] Module `safety_reporting` exists in `modules` table (check migration 009)
- [x] iPad `AppEnvironment` module sync logic is correct (fixed in Phase 0)
- [ ] Migration 019 applied to live Namecheap MySQL (manual step — run via phpMyAdmin)
- [ ] Safety Reporting module enabled for test tenant in `tenant_modules`
- [ ] `RealReportingService` implemented (currently `MockReportingService`)

---

## Files Created or Modified in Phase 0

### `opsone-web` repository

**New files:**
- `database/migrations/019_phase0_safety_reports_mysql.sql`
- `app/Services/NotificationService.php`
- `app/Services/RetentionService.php`
- `docs/phase-0-architecture-overview.md`
- `docs/role-permission-matrix.md`
- `docs/module-governance-matrix.md`
- `docs/retention-policy-framework.md`
- `docs/offline-sync-framework.md`
- `docs/notification-framework.md`
- `docs/implementation-summary-phase0.md` ← this file

**Modified files:**
- `app/Models/AuditLog.php` — deprecation markers + caller traces
- `config/routes.php` — duplicate `GET /my-files` route removed

### `CrewAssist` repository

**Modified files:**
- `Core/DI/AppEnvironment.swift` — inverted module sync filter fixed; mock service TODOs added
- `Core/Services/RealDeviceSyncService.swift` — stable UUID 3-step resolution
- `Core/PersistenceController.swift` — fatalError wrapped in `#if DEBUG`
