# P2 — Web Module Registry Alignment

**Phase**: P2 of the 2026-04-25 Premium Sync pass.
**Status**: Local SQLite ✅ migrated. **Production MySQL deploy required** (see below).

## What changed
Added migration **`040_module_registry_phase18_alignment`** (MySQL + SQLite variants). Purely additive (`INSERT IGNORE` / `INSERT OR IGNORE`), no DDL.

### New module catalog rows
| code | name |
|---|---|
| `flight_folder` | Flight Folder |
| `per_diem` | Per Diem & Parting |
| `appraisals` | Appraisals |
| `logbook` | Electronic Logbook |
| `help` | Help & Support |
| `reports` | Operational Reports |
| `notifications` | Notifications Inbox (distinct from `notices`) |
| `duty_reporting` | (already in catalog — INSERT IGNORE no-op) |

### Capabilities
Per-module fine-grained capabilities, e.g.
- `flight_folder.submit_journey_log`, `submit_risk_assessment`, `submit_crew_briefing`, `submit_navlog`, `submit_post_arrival`, `submit_verification`, `submit_after_mission_pilot`, `submit_after_mission_cabin`, `review`, `approve`
- `per_diem.claim`, `review`, `approve`
- `appraisals.write`, `review`, `manage`
- `logbook.submit`, `edit`, `review`
- `help.submit_request`, `manage_tickets`, `manage_content`
- `reports.submit_after_mission`, `submit_airstrip`, `submit_verification`, `view_all`, `review`
- `notifications.acknowledge`, `create`

### Role templates (defaults)
- **pilot / cabin_crew / engineer**: view + own-submit caps on each module; pilot-only `submit_after_mission_pilot`; cabin_crew-only `submit_after_mission_cabin`.
- **base_manager / chief_pilot / scheduler**: review/approve/export on flight_folder, duty_reporting, reports, logbook, appraisals; create/acknowledge on notifications.
- **hr**: appraisals (write/review/manage/export), logbook (review/export), per_diem (review/approve/export), help (manage_tickets).
- **airline_admin**: full kit on all 8 new modules.

### Tenant enablement
Demo tenant (id=1) — all 8 new modules enabled (so dev/test sees them).
Production tenants — **not auto-enabled**; remains a platform-admin choice (correct).

## Why this was needed
`NavigationService::moduleEnabled($code)` returns `false` for any module whose code is not in the `modules` table. Until 040 was applied, module gating for `flight_folder`, `per_diem`, `appraisals`, `logbook`, `help`, `reports`, `notifications` was effectively a no-op — there was no admin UI knob to enable/disable them per airline. Mobile screens still rendered (because they use the iPad app's own `AppModule` enum), but web admin couldn't gate them. P2 closes that gap so subsequent phases (P4 Flight Folder, P10 Help, P11 Reports split, P14 Appraisals, P16 Per Diem, P17 Logbook) can rely on real role/module enforcement on the API side.

## Files
- `database/migrations/040_module_registry_phase18_alignment.sql` (MySQL — for production)
- `database/migrations/040_module_registry_phase18_alignment_sqlite.sql` (SQLite — applied locally)
- `docs/planning/PHASE18_P2_MODULE_REGISTRY_REPORT.md` (this file)

## Verification
**Local SQLite (already done):**
```
sqlite3 database/crewassist.sqlite "SELECT code FROM modules ORDER BY sort_order;"
# 24 rows — all new codes present
sqlite3 database/crewassist.sqlite "SELECT m.code, tm.is_enabled FROM tenant_modules tm JOIN modules m ON m.id = tm.module_id WHERE tm.tenant_id = 1;"
# All 8 new modules enabled for demo tenant
```

## Production deploy steps (HARD STOP — user action required)
1. Pull the latest `main` on Namecheap:
   ```
   cd /home/fruinxrj/acentoza.com && git pull origin main
   ```
2. Open phpMyAdmin → `fruinxrj_opsone` → SQL tab → paste the contents of:
   ```
   database/migrations/040_module_registry_phase18_alignment.sql
   ```
3. Run. Expected output: all `INSERT IGNORE` statements succeed; transaction commits.
4. Smoke check:
   ```sql
   SELECT code, name FROM modules WHERE code IN
     ('flight_folder','per_diem','appraisals','logbook','help','reports','notifications');
   -- expect 7 rows (8 if duty_reporting was missing)
   ```
5. The `tenant_modules` insert for tenant_id=1 only enables for the **demo** tenant. For real production airlines, use the platform-admin UI to enable per-tenant.

## Out of scope (deferred to later phases)
- P3 Flights/Roster integration verification — next.
- API-side enforcement of new capability checks — most controllers already check `moduleEnabled($code)`; per-capability `userCan($code, $cap)` calls will be added per-module as we touch them in P4–P17.
- Mobile-side: `AppEnvironment.visibleModules` already filters by user role; once the auth API returns the new capability set after deploy, the gating will apply automatically.
