# OpsOne Web — UI / Navigation / Permission Cleanup

**Date:** 2026-04-22
**Scope:** `opsone-web/` only (no changes to `CrewAssist/` or `opsone-ipad-app/`).
**Source:** single-pass cleanup against the existing codebase; no rebuild.

## Summary

The left sidebar, top header, and module-toggle flow have been refactored from
hardcoded per-role conditionals into a **single config-driven registry** with a
thin rendering partial. Menu visibility now follows the architectural rule:

> **Level 1** Platform Super Admin enables modules per airline.
> **Level 2** Airline Super Admin assigns enabled modules/capabilities to users.
> **Level 3** Users only see menu items whose role ∧ module-enabled ∧ capability
> checks all pass.

If any check fails, the item does not render. Nothing is teased, nothing
returns "you do not have permission" — hidden means hidden.

The header moved account actions (Profile, Profile Settings, Account Security,
Sign Out) into a proper top-right dropdown. "Install App" / "App Builds" /
"Get iPad Build" web-side clutter has been removed from authenticated views.
The module enable/disable blank-page bug is fixed.

## Files added

| Path | Purpose |
|---|---|
| `config/sidebar.php` | Single-source-of-truth registry (platform + airline). Every item lists role, module, optional capability and `when` gates. |
| `app/Services/NavigationService.php` | Loads registry, resolves gates, computes all badge counts, computes active link. |
| `views/partials/sidebar.php` | Thin renderer — reads `NavigationService::build()` and `::badges()`. |
| `views/partials/header_bar.php` | Top bar with title, safety+notification bells, profile dropdown. |
| `app/Controllers/AccountController.php` | `/account/settings` — name/email/phone/avatar form. |
| `views/account/settings.php` | Account settings view. |
| `database/seeders/generate_sample_pdfs.php` | Generates four realistic demo PDFs. |
| `storage/samples/*.pdf` | Ready-to-upload test fixtures (manual, training notice, Q400 fleet notice, mandatory-ack notice). |
| `docs/ui-cleanup-2026-04-22.md` | This document. |

## Files changed

| Path | What changed |
|---|---|
| `views/layouts/app.php` | Hardcoded 600-line sidebar + header replaced with `include` of the two new partials. JS for bells/mobile menu kept. |
| `app/Controllers/ModuleCatalogController.php` | `toggleForTenant` now detects non-AJAX form POSTs and redirects with a flash message instead of returning raw JSON. |
| `app/Controllers/TenantController.php` | Same fix for `toggleModule`. |
| `views/dashboard/super_admin.php` | Removed duplicate "Quick Links" grid that repeated sidebar nav. |
| `views/dashboard/platform_support.php` | Removed duplicate "Quick Access" grid. |
| `views/dashboard/pilot.php` | Removed "Get iPad Build →" CTA pointing to `/install`. |
| `views/dashboard/engineer.php` | Same. |
| `config/routes.php` | Added `GET /account/settings` and `POST /account/settings/update`. |

## Files intentionally **not** changed

- `views/install/*`, `/install*` routes and controllers are kept so the public
  enterprise-install pages still work (they are needed by the marketing /
  download flow), but they are no longer linked from any authenticated sidebar
  or dashboard.
- `views/public/*` marketing pages left alone.
- `CrewAssist/`, `opsone-ipad-app/`, `OpsOne Design Files/` untouched.

## Behavioural changes

### Sidebar

- **Nested sections** driven by config. A section auto-hides when every item
  inside it is gated out (no more empty headers).
- **Module-aware visibility**. Each item can declare `'module' => 'manuals'`
  etc. Platform Super Admin toggles `tenant_modules.is_enabled` → `NavigationService::moduleEnabled()` returns false → the item doesn't render.
  Verified by toggling `manuals` off for tenant 1: the **Documents Library**
  link vanished from the airline admin's sidebar on the next page load.
- **Role gate at both section and item level** — section gate is a fast bail-out,
  item gate is enforced per link.
- **Platform vs airline separation** is preserved (platform-only users see the
  platform tree, airline users the airline tree — never mixed).
- **"Install App" / "App Builds"** removed from both platform and airline
  sidebars.

### Header

- Top-right user-menu pill with avatar/initials, name, role chip.
- Dropdown items: **Profile** (`/my-profile`), **Profile Settings**
  (`/account/settings`), **Account Security** (`/2fa/setup`), **Sign Out**.
- Closes on outside click and on Escape.
- Safety bell and notification inbox bell retained (unchanged behaviour).

### Module enable/disable flow (Platform Super Admin)

- Previously: form POST → controller returned raw JSON → browser rendered JSON
  as the entire page (the "blank/broken page" symptom).
- Now: controller looks at `Accept:` / `X-Requested-With:` and:
  - AJAX → JSON (existing iPad/API clients unaffected).
  - Form POST → 302 back to the detail page with a success flash
    (`Module "Manuals & Documents" enabled for OpsOne Demo Airline.`).

### Account settings

- `/account/settings` gives every logged-in user (including platform staff who
  have no crew record) a page to edit name, email, phone, timezone, avatar URL.
  Optional columns are auto-detected — the form only shows fields the `users`
  table actually has, so no migration is required.

## QA results — local browser

Dev server: `php -S localhost:8080 -t public/` on `database/crewassist.sqlite`
seeded with `database/seeders/demo_seed.php`.

Login / dashboard checked for every role. Sidebar sections rendered:

| Role | Sections rendered |
|---|---|
| super_admin | Platform, Airlines, Platform Staff, Configuration, Security, Support |
| platform_support | Platform, Airlines, Support |
| platform_security | Platform, Security |
| airline_admin | Main, People, Personnel Records, Duty Reporting, Content, Safety, Security, Fleet, People Ops, Administration, Scheduling |
| hr | Main, People, Personnel Records, Duty Reporting, Content, Safety, People Ops, Administration |
| chief_pilot | Main, People, Personnel Records, Me, Scheduling, Duty Reporting, Content, Safety, Fleet, People Ops, Administration |
| head_cabin_crew | (same shape as chief_pilot, role-filtered) |
| engineering_manager | Main, People, Personnel Records, Me, Duty Reporting, Content, Fleet, Administration |
| safety_officer | Main, People, Personnel Records, Me, Content, Safety, Security |
| fdm_analyst | Main, Personnel Records, Me, Content, Safety |
| document_control | Main, Me, Content |
| base_manager | Main, People, Personnel Records, Me, Scheduling, Duty Reporting, Content, Fleet, Administration |
| scheduler | Main, Personnel Records, Me, Scheduling, Duty Reporting |
| training_admin | Main, People, Personnel Records, Me, Content, People Ops |
| pilot | Main, Me, Scheduling, Duty Reporting |
| cabin_crew | Main, Me, Scheduling, Duty Reporting |
| engineer | Main, Me, Scheduling, Duty Reporting |

No admin-only menu leaked into any operational-crew role. No "Install App" /
"App Builds" link anywhere in an authenticated sidebar.

Specific flows verified end-to-end:

- ✅ Login → dashboard → sidebar sections correct per role.
- ✅ Header dropdown opens, contains Profile / Profile Settings / Account Security / Sign Out.
- ✅ `/account/settings` loads and saves (302 + flash "Your settings have been saved").
- ✅ `/files`, `/notices`, `/compliance`, `/audit-log`, `/my-files` all return 200.
- ✅ Platform Super Admin toggles module off for a tenant — redirects cleanly with flash, DB updates, airline admin's sidebar re-renders without that link on next request.
- ✅ Toggling **all** Content-driving modules off hides the Content section entirely (no empty header).
- ✅ Mock PDFs verified with `file(1)`: valid PDF-1.4, 1 page each.

## Staged / known limitations

These are intentionally not tackled in this pass — they need design inputs or
are larger than a cleanup:

1. **Collapsible sidebar groups** — registry and renderer support it, but we
   ship as always-expanded for this pass. Adding a per-section collapse is a
   small CSS/JS delta later.
2. **Per Diem / "parting" long-cycle logic** (42/10, 2mo/15d, station = outstation
   flag) is staged. Current `/per-diem` pages still work but don't yet express
   outstation-type rotations. Needs real station data + rate sheets from the
   design files.
3. **Base Manager expense/receipt flow** needs upload + receipt-optional
   capture — not built yet. `/bases` CRUD still works.
4. **FDM CSV ingestion** — `/fdm/upload` page exists and accepts files; rule
   parsing of the `OpsOne Design Files/FDM files` CSVs into event rows is a
   future phase.
5. **Flight plan / flight-bag generation from templates** in the design files
   (navlog / flight files) is staged; `/flights` still only lets a scheduler
   create a bare flight.
6. **Role creation UI for airline admins** — the data model supports custom
   tenant roles (table `roles` with `tenant_id`) and a full capability assignment
   UI exists at `/roles` for `airline_admin`, but there is no explicit
   "create role" form yet. That page currently shows system roles only.
7. **Platform Security dashboard** is still the stock audit-log/login-activity
   pair — future work should add a suspicious-login panel and tenant-scoped
   access anomaly view.
8. **Platform Support console** is cleaner than before (no duplicated grid) but
   still does not yet model airline support tickets. `OnboardingRequest` is
   the closest existing artefact and can be extended.
9. **Mobile parity for Profile Settings** — iPad still reads its own
   `/api/user/profile` endpoint; wiring the web `/account/settings` write into
   the same endpoint is a future task.

## Anti-Gravity browser audit recommendation

- Exercise each of the sample PDFs via `/files/upload` with role targeting
  (manuals → all crew, Q400 notice → engineering_manager+chief_pilot, training
  notice → training_admin, mandatory-ack → `requires_ack = 1`).
- Then log in as `demo.pilot@acentoza.com` and confirm they appear in
  **My Documents** and acknowledge properly.
- Toggle modules off/on from `demo.superadmin@acentoza.com` and re-load an
  airline user's dashboard to confirm live visibility changes.
